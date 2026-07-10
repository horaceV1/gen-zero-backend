<?php

namespace Drupal\gen_zero_subscriptions\Plugin\SubscriptionPaymentGateway;

use Drupal\gen_zero_subscriptions\Entity\SubscriptionTierInterface;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\HttpFoundation\Request;

/**
 * EuPago payment gateway.
 *
 * Supports Multibanco (ATM reference), MB WAY, and Credit Card payments.
 * For subscriptions, generates a single payment per billing period;
 * recurring charges are triggered via cron on each billing date.
 *
 * payment_data options from frontend:
 *   - method: 'multibanco' | 'mbway' | 'cc' (default: multibanco)
 *   - phone: required for mbway (e.g. "351#912345678")
 *
 * @SubscriptionPaymentGateway(
 *   id = "eupago",
 *   label = @Translation("EuPago"),
 *   description = @Translation("Portuguese payments via Multibanco, MB WAY, and Credit Card."),
 * )
 */
class EuPagoGateway extends SubscriptionPaymentGatewayBase {

  /**
   * {@inheritdoc}
   */
  public function requiresRedirect(): bool {
    // CC method redirects; Multibanco/MB WAY do not.
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function createSubscription(SubscriptionTierInterface $tier, array $payment_data, ?string $email = NULL): array {
    $config = $this->getGatewayConfig();

    if (empty($config['api_key'])) {
      return ['success' => FALSE, 'message' => 'EuPago gateway is not configured.'];
    }

    $method = $payment_data['method'] ?? 'multibanco';
    $amount = (float) $tier->getPrice();
    // Use tier + timestamp as unique reference key.
    $internalId = 'eupago_' . $tier->id() . '_' . time();

    switch ($method) {
      case 'mbway':
        return $this->createMbWayPayment($config, $tier, $payment_data, $internalId, $amount);

      case 'cc':
        return $this->createCreditCardPayment($config, $tier, $internalId, $amount, $email);

      case 'multibanco':
      default:
        return $this->createMultibancoPayment($config, $tier, $internalId, $amount);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function cancelSubscription(string $subscription_id): array {
    // EuPago doesn't have a "subscription" concept.
    // Cancellation simply stops us from generating new payment references.
    return [
      'success' => TRUE,
      'message' => 'Subscription cancelled. No further payments will be generated.',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function processWebhook(Request $request): array {
    $config = $this->getGatewayConfig();
    $body = json_decode($request->getContent(), TRUE);

    // EuPago sends payment confirmations as JSON or form data.
    // Try JSON first, fall back to POST params.
    if (empty($body)) {
      $body = $request->request->all();
    }

    $transactionId = $body['transacao'] ?? $body['transaction'] ?? $body['transactionID'] ?? '';
    $reference = $body['referencia'] ?? $body['reference'] ?? '';
    $value = $body['valor'] ?? $body['value'] ?? '';
    $internalId = $body['identificador'] ?? $body['identifier'] ?? '';
    $paymentStatus = strtolower((string) ($body['estado'] ?? $body['status'] ?? $body['transactionStatus'] ?? ''));

    $this->logger->info('EuPago webhook: ref=@ref, id=@id, status=@status, value=@val', [
      '@ref' => $reference,
      '@id' => $internalId,
      '@status' => $paymentStatus,
      '@val' => $value,
    ]);

    // EuPago uses "pago" (paid) as the success status.
    if (!in_array($paymentStatus, ['pago', 'paid', 'confirmed', 'success', 'sucesso'], TRUE)) {
      return [
        'success' => FALSE,
        'external_id' => $internalId,
        'message' => 'Payment not confirmed: status=' . $paymentStatus,
      ];
    }

    return [
      'success' => TRUE,
      'external_id' => $internalId,
      'new_status' => 'active',
      'message' => 'Payment confirmed. Subscription activated.',
    ];
  }

  /**
   * Creates a Multibanco payment reference.
   */
  protected function createMultibancoPayment(array $config, SubscriptionTierInterface $tier, string $internalId, float $amount): array {
    $baseUrl = $this->getApiBaseUrl($config);
    $payment = [
      'amount' => ['currency' => 'EUR', 'value' => round($amount, 2)],
      'identifier' => $internalId,
    ];

    // Add expiration date (deadline days) if configured.
    if (!empty($config['multibanco_deadline_days'])) {
      $payment['expirationDate'] = date('Y-m-d', strtotime('+' . (int) $config['multibanco_deadline_days'] . ' days'));
    }

    try {
      $response = $this->httpClient->request('POST', $baseUrl . '/api/v1.02/multibanco/create', [
        'json' => ['payment' => $payment],
        'headers' => $this->apiHeaders($config),
        'http_errors' => FALSE,
      ]);

      $data = json_decode((string) $response->getBody(), TRUE) ?: [];

      if (!$this->isSuccess($data)) {
        $errorMsg = $data['text'] ?? $data['message'] ?? $data['resposta'] ?? 'Unknown error';
        $this->logger->error('EuPago Multibanco failed: @msg', ['@msg' => $errorMsg]);
        return ['success' => FALSE, 'message' => 'Could not generate Multibanco reference.'];
      }

      $reference = $data['reference'] ?? [];
      return [
        'success' => TRUE,
        'subscription_id' => $internalId,
        'message' => 'Multibanco reference generated. Awaiting payment.',
        'initial_status' => 'pending',
        'data' => [
          'payment_method' => 'multibanco',
          'entity' => $reference['entity'] ?? $data['entidade'] ?? '',
          'reference' => $reference['reference'] ?? $data['referencia'] ?? '',
          'amount' => $amount,
          'currency' => $tier->getCurrency(),
          'deadline' => $reference['expirationDate'] ?? $payment['expirationDate'] ?? NULL,
        ],
      ];
    }
    catch (GuzzleException $e) {
      $this->logger->error('EuPago Multibanco error: @msg', ['@msg' => $e->getMessage()]);
      return ['success' => FALSE, 'message' => 'Failed to generate payment reference.'];
    }
  }

  /**
   * Creates an MB WAY payment request.
   */
  protected function createMbWayPayment(array $config, SubscriptionTierInterface $tier, array $payment_data, string $internalId, float $amount): array {
    if (empty($payment_data['phone'])) {
      return ['success' => FALSE, 'message' => 'Phone number is required for MB WAY payments.'];
    }

    $baseUrl = $this->getApiBaseUrl($config);
    [$countryCode, $localPhone] = $this->splitPhone((string) $payment_data['phone']);
    $payload = [
      'payment' => [
        'amount' => ['currency' => 'EUR', 'value' => round($amount, 2)],
        'identifier' => $internalId,
        'countryCode' => $countryCode,
        'customerPhone' => $localPhone,
      ],
      'customer' => [
        'notify' => TRUE,
        'phone' => $localPhone,
        'countryCode' => $countryCode,
      ],
    ];

    try {
      $response = $this->httpClient->request('POST', $baseUrl . '/api/v1.02/mbway/create', [
        'json' => $payload,
        'headers' => $this->apiHeaders($config),
        'http_errors' => FALSE,
      ]);

      $data = json_decode((string) $response->getBody(), TRUE) ?: [];

      if (!$this->isSuccess($data)) {
        $errorMsg = $data['text'] ?? $data['message'] ?? $data['resposta'] ?? 'Unknown error';
        $this->logger->error('EuPago MB WAY failed: @msg', ['@msg' => $errorMsg]);
        return ['success' => FALSE, 'message' => 'Could not initiate MB WAY payment.'];
      }

      return [
        'success' => TRUE,
        'subscription_id' => $internalId,
        'message' => 'MB WAY payment sent. Confirm on your phone.',
        'initial_status' => 'pending',
        'data' => [
          'payment_method' => 'mbway',
          'reference' => $data['transactionID'] ?? $data['referencia'] ?? '',
          'amount' => $amount,
          'currency' => $tier->getCurrency(),
        ],
      ];
    }
    catch (GuzzleException $e) {
      $this->logger->error('EuPago MB WAY error: @msg', ['@msg' => $e->getMessage()]);
      return ['success' => FALSE, 'message' => 'Failed to initiate MB WAY payment.'];
    }
  }

  /**
   * Creates a credit card payment via redirect.
   */
  protected function createCreditCardPayment(array $config, SubscriptionTierInterface $tier, string $internalId, float $amount, ?string $email): array {
    $baseUrl = $this->getApiBaseUrl($config);
    $returnUrl = $config['return_url'] ?? '';

    $payment = [
      'amount' => ['currency' => 'EUR', 'value' => round($amount, 2)],
      'identifier' => $internalId,
      'lang' => 'PT',
    ];

    if (!empty($returnUrl)) {
      $payment['successUrl'] = $returnUrl;
      $payment['failUrl'] = $returnUrl;
      $payment['backUrl'] = $returnUrl;
    }

    $payload = ['payment' => $payment];
    if ($email) {
      $payload['customer'] = ['email' => $email, 'notify' => TRUE];
    }

    try {
      $response = $this->httpClient->request('POST', $baseUrl . '/api/v1.02/creditcard/create', [
        'json' => $payload,
        'headers' => $this->apiHeaders($config),
        'http_errors' => FALSE,
      ]);

      $data = json_decode((string) $response->getBody(), TRUE) ?: [];

      if (!$this->isSuccess($data)) {
        $errorMsg = $data['text'] ?? $data['message'] ?? $data['resposta'] ?? 'Unknown error';
        $this->logger->error('EuPago CC failed: @msg', ['@msg' => $errorMsg]);
        return ['success' => FALSE, 'message' => 'Could not initiate credit card payment.'];
      }

      $redirectUrl = $data['redirectUrl'] ?? $data['url'] ?? '';
      if (empty($redirectUrl)) {
        return ['success' => FALSE, 'message' => 'EuPago did not return a payment URL.'];
      }

      return [
        'success' => TRUE,
        'subscription_id' => $internalId,
        'message' => 'Redirect to complete credit card payment.',
        'initial_status' => 'pending',
        'redirect_url' => $redirectUrl,
        'data' => [
          'payment_method' => 'cc',
          'amount' => $amount,
          'currency' => $tier->getCurrency(),
        ],
      ];
    }
    catch (GuzzleException $e) {
      $this->logger->error('EuPago CC error: @msg', ['@msg' => $e->getMessage()]);
      return ['success' => FALSE, 'message' => 'Failed to initiate credit card payment.'];
    }
  }

  /**
   * Returns the EuPago API base URL based on mode.
   */
  protected function getApiBaseUrl(array $config): string {
    $mode = $config['mode'] ?? 'sandbox';
    return $mode === 'live'
      ? 'https://clientes.eupago.pt'
      : 'https://sandbox.eupago.pt';
  }

  /**
   * Builds the request headers, including ApiKey header authentication.
   */
  protected function apiHeaders(array $config): array {
    return [
      'Accept' => 'application/json',
      'Content-Type' => 'application/json',
      'Authorization' => 'ApiKey ' . trim((string) ($config['api_key'] ?? '')),
    ];
  }

  /**
   * Determines whether an EuPago v1.02 response indicates success.
   */
  protected function isSuccess(array $data): bool {
    $status = strtolower((string) ($data['transactionStatus'] ?? ''));
    return $status === 'success'
      // Backwards-compatible with legacy body-auth responses.
      || ($data['sucesso'] ?? FALSE) === TRUE
      || (string) ($data['estado'] ?? '') === '0';
  }

  /**
   * Splits a phone alias into [countryCode, localNumber].
   *
   * Accepts "351#912345678", "351912345678" or "912345678".
   */
  protected function splitPhone(string $phone): array {
    $phone = trim($phone);
    if (str_contains($phone, '#')) {
      [$code, $local] = explode('#', $phone, 2);
      return [ltrim($code, '+') ?: '351', preg_replace('/\D+/', '', $local)];
    }
    $digits = preg_replace('/\D+/', '', $phone);
    if (strlen($digits) > 9 && str_starts_with($digits, '351')) {
      return ['351', substr($digits, 3)];
    }
    return ['351', $digits];
  }

}
