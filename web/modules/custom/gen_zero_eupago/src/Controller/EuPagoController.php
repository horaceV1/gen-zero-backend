<?php

declare(strict_types=1);

namespace Drupal\gen_zero_eupago\Controller;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Entity\Payment;
use Drupal\commerce_payment\Entity\PaymentGateway;
use Drupal\commerce_price\Price;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Url;
use Drupal\gen_zero_eupago\EuPagoClient;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * HTTP endpoints for the EuPago integration.
 */
final class EuPagoController extends ControllerBase {

  public function __construct(
    private readonly EuPagoClient $client,
    LoggerChannelFactoryInterface $loggerFactory,
  ) {
    $this->logger = $loggerFactory->get('gen_zero_eupago');
  }

  /**
   * @var \Psr\Log\LoggerInterface
   */
  private $logger;

  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('gen_zero_eupago.client'),
      $container->get('logger.factory'),
    );
  }

  /**
   * POST /api/eupago/checkout
   *
   * Body: {
   *   order_id: int,
   *   method: 'multibanco' | 'mbway' | 'cc',
   *   phone?: string  (e.g. "351#912345678", required for mbway),
   *   email?: string,
   *   return_url?: string
   * }
   *
   * Response: {
   *   success: bool,
   *   method, entity?, reference?, amount, expires_at?, payment_url?, message?
   * }
   */
  public function checkout(Request $request): JsonResponse {
    if (!$this->client->isConfigured()) {
      return new JsonResponse(['success' => FALSE, 'message' => 'EuPago is not configured.'], 503);
    }

    $body = json_decode($request->getContent(), TRUE) ?: [];
    $orderId = (int) ($body['order_id'] ?? 0);
    $method = $body['method'] ?? 'multibanco';

    if ($orderId <= 0) {
      return new JsonResponse(['success' => FALSE, 'message' => 'order_id is required.'], 400);
    }

    $settings = $this->client->getSettings();
    if (!in_array($method, $settings['allowed_methods'], TRUE)) {
      return new JsonResponse(['success' => FALSE, 'message' => "Method '$method' is not enabled."], 400);
    }

    /** @var \Drupal\commerce_order\Entity\OrderInterface|null $order */
    $order = $this->entityTypeManager()->getStorage('commerce_order')->load($orderId);
    if (!$order instanceof OrderInterface) {
      return new JsonResponse(['success' => FALSE, 'message' => 'Order not found.'], 404);
    }

    // Authorize: owner or admin.
    $account = $this->currentUser();
    if ((int) $order->getCustomerId() !== (int) $account->id() && !$account->hasPermission('administer commerce_order')) {
      return new JsonResponse(['success' => FALSE, 'message' => 'Access denied to this order.'], 403);
    }

    $totalPrice = $order->getTotalPrice();
    if (!$totalPrice || (float) $totalPrice->getNumber() <= 0) {
      return new JsonResponse(['success' => FALSE, 'message' => 'Order total is invalid.'], 400);
    }

    $amount = (float) $totalPrice->getNumber();
    $currency = $totalPrice->getCurrencyCode();
    if ($currency !== 'EUR') {
      return new JsonResponse(['success' => FALSE, 'message' => 'EuPago only supports EUR.'], 400);
    }

    $internalId = sprintf('ORDER-%d-%d', $orderId, time());
    $email = $body['email'] ?? $order->getEmail();

    $result = match ($method) {
      'mbway' => $this->client->createMbWay($internalId, $amount, (string) ($body['phone'] ?? '')),
      'cc' => $this->client->createCreditCard(
        $internalId,
        $amount,
        $email,
        (string) ($body['return_url'] ?? $settings['return_url'] ?: $this->defaultReturnUrl($orderId)),
      ),
      default => $this->client->createMultibanco($internalId, $amount),
    };

    if (empty($result['success'])) {
      return new JsonResponse([
        'success' => FALSE,
        'message' => $result['message'] ?? 'EuPago request failed.',
      ], 502);
    }

    $raw = $result['raw'] ?? [];

    // Persist the EuPago identifier on the order for later reconciliation.
    $order->setData('eupago', [
      'internal_id' => $internalId,
      'method' => $method,
      'amount' => number_format($amount, 2, '.', ''),
      'currency' => $currency,
      'entity' => $raw['entidade'] ?? NULL,
      'reference' => $raw['referencia'] ?? NULL,
      'payment_url' => $raw['url'] ?? NULL,
      'created' => (new \DateTimeImmutable())->format(DATE_ATOM),
      'status' => 'pending',
    ]);
    $order->save();

    return new JsonResponse([
      'success' => TRUE,
      'method' => $method,
      'internal_id' => $internalId,
      'amount' => number_format($amount, 2, '.', ''),
      'currency' => $currency,
      'entity' => $raw['entidade'] ?? NULL,
      'reference' => $raw['referencia'] ?? NULL,
      'expires_at' => $raw['per_dup'] ?? NULL,
      'payment_url' => $raw['url'] ?? NULL,
      'message' => $raw['resposta'] ?? NULL,
    ]);
  }

  /**
   * EuPago webhook handler. POST /api/eupago/notify
   *
   * EuPago sends `identificador`, `valor`, `referencia`, `entidade`, `estado`.
   */
  public function notify(Request $request): JsonResponse {
    $body = json_decode($request->getContent(), TRUE) ?: [];
    if (!$body) {
      $body = $request->request->all() ?: $request->query->all();
    }

    $internalId = (string) ($body['identificador'] ?? $body['identifier'] ?? $body['id'] ?? '');
    $reference = (string) ($body['referencia'] ?? $body['reference'] ?? '');
    $entity = (string) ($body['entidade'] ?? $body['entity'] ?? '');
    $value = (string) ($body['valor'] ?? $body['value'] ?? '0');
    $status = (string) ($body['estado'] ?? $body['status'] ?? $body['transactionStatus'] ?? 'pago');

    $this->logger->info('EuPago notify: id=@id ref=@ref status=@status val=@val', [
      '@id' => $internalId, '@ref' => $reference, '@status' => $status, '@val' => $value,
    ]);

    if ($internalId === '' || !preg_match('/^ORDER-(\d+)-/', $internalId, $m)) {
      return new JsonResponse(['success' => FALSE, 'message' => 'Unknown identifier.'], 400);
    }
    $orderId = (int) $m[1];

    /** @var \Drupal\commerce_order\Entity\OrderInterface|null $order */
    $order = $this->entityTypeManager()->getStorage('commerce_order')->load($orderId);
    if (!$order instanceof OrderInterface) {
      return new JsonResponse(['success' => FALSE, 'message' => 'Order not found.'], 404);
    }

    if (!in_array(strtolower($status), ['pago', 'paid', 'confirmed', '0', 'sucesso', 'success'], TRUE)) {
      // Update tracking but don't place the order.
      $data = (array) ($order->getData('eupago') ?? []);
      $data['status'] = $status;
      $order->setData('eupago', $data);
      $order->save();
      return new JsonResponse(['success' => TRUE, 'message' => 'Status recorded.']);
    }

    // Idempotency.
    $data = (array) ($order->getData('eupago') ?? []);
    if (($data['status'] ?? '') === 'paid') {
      return new JsonResponse(['success' => TRUE, 'message' => 'Already processed.']);
    }

    $this->recordPayment($order, (float) str_replace(',', '.', $value), $reference, $entity, $internalId);

    $data['status'] = 'paid';
    $data['paid_at'] = (new \DateTimeImmutable())->format(DATE_ATOM);
    $data['entity'] = $entity ?: ($data['entity'] ?? NULL);
    $data['reference'] = $reference ?: ($data['reference'] ?? NULL);
    $order->setData('eupago', $data);

    // Transition order to a non-draft state if it's still a cart.
    if ($order->getState()->getId() === 'draft') {
      $transitions = $order->getState()->getTransitions();
      if (isset($transitions['place'])) {
        $order->getState()->applyTransitionById('place');
      }
    }
    $order->save();

    return new JsonResponse(['success' => TRUE]);
  }

  /**
   * GET /api/eupago/status/{order_id}
   *
   * Returns the stored EuPago tracking data for an order (owner only).
   */
  public function status(int $order_id): JsonResponse {
    /** @var \Drupal\commerce_order\Entity\OrderInterface|null $order */
    $order = $this->entityTypeManager()->getStorage('commerce_order')->load($order_id);
    if (!$order instanceof OrderInterface) {
      return new JsonResponse(['success' => FALSE, 'message' => 'Order not found.'], 404);
    }
    $account = $this->currentUser();
    if ((int) $order->getCustomerId() !== (int) $account->id() && !$account->hasPermission('administer commerce_order')) {
      return new JsonResponse(['success' => FALSE, 'message' => 'Access denied.'], 403);
    }
    return new JsonResponse([
      'success' => TRUE,
      'order_id' => $order_id,
      'state' => $order->getState()->getId(),
      'eupago' => $order->getData('eupago') ?? NULL,
    ]);
  }

  /**
   * Records a completed commerce_payment for the given order.
   */
  private function recordPayment(OrderInterface $order, float $amount, string $reference, string $entity, string $internalId): void {
    $settings = $this->client->getSettings();
    $gatewayId = $settings['payment_gateway_id'] ?: 'eupago';

    $gateway = PaymentGateway::load($gatewayId);
    if (!$gateway) {
      $this->logger->error('EuPago: commerce_payment_gateway "@id" not found; payment not recorded.', ['@id' => $gatewayId]);
      return;
    }

    try {
      $payment = Payment::create([
        'state' => 'completed',
        'amount' => new Price(
          $amount > 0 ? number_format($amount, 2, '.', '') : $order->getTotalPrice()->getNumber(),
          $order->getTotalPrice()->getCurrencyCode(),
        ),
        'payment_gateway' => $gateway->id(),
        'order_id' => $order->id(),
        'remote_id' => $reference ?: $internalId,
        'remote_state' => 'paid',
      ]);
      $payment->save();
    }
    catch (\Throwable $e) {
      $this->logger->error('EuPago: failed to record payment for order @oid: @msg', [
        '@oid' => $order->id(), '@msg' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Computes a default return URL when none is configured.
   */
  private function defaultReturnUrl(int $orderId): string {
    try {
      return Url::fromUserInput('/cart')->setAbsolute(TRUE)->toString();
    }
    catch (\Throwable) {
      return '';
    }
  }

}
