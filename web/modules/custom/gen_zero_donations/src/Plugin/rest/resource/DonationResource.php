<?php

namespace Drupal\gen_zero_donations\Plugin\rest\resource;

use Drupal\gen_zero_donations\DonationManager;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Provides a resource to create and configure donations.
 *
 * @RestResource(
 *   id = "donation_resource",
 *   label = @Translation("Donation Resource"),
 *   uri_paths = {
 *     "create" = "/api/donations"
 *   }
 * )
 */
class DonationResource extends ResourceBase {

  /**
   * The donation manager service.
   */
  protected DonationManager $donationManager;

  /**
   * Constructs a DonationResource object.
   *
   * @param array $configuration
   *   A configuration array.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param array $serializer_formats
   *   The available serialization formats.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\gen_zero_donations\DonationManager $donation_manager
   *   The donation manager service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    DonationManager $donation_manager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
    $this->donationManager = $donation_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('gen_zero_donations'),
      $container->get('gen_zero_donations.donation_manager'),
    );
  }

  /**
   * Responds to POST requests to create a donation.
   *
   * Expected payload:
   * {
   *   "amount": 25,
   *   "projeto_id": 42,
   *   "currency": "EUR",  // optional, defaults to EUR
   *   "email": "donor@example.com"  // optional
   * }
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Drupal\rest\ModifiedResourceResponse
   *   The response containing the created order details.
   */
  public function post(Request $request): ModifiedResourceResponse {
    $content = json_decode($request->getContent(), TRUE);

    if (empty($content) || !is_array($content)) {
      throw new BadRequestHttpException('Invalid JSON payload.');
    }

    // Check permission.
    $current_user = \Drupal::currentUser();
    if (!$current_user->hasPermission('create donations')) {
      throw new AccessDeniedHttpException('You do not have permission to create donations.');
    }

    // Validate required fields.
    if (!isset($content['amount'])) {
      throw new BadRequestHttpException('Missing required field: amount.');
    }
    if (empty($content['projeto_id'])) {
      throw new BadRequestHttpException('Missing required field: projeto_id.');
    }

    // Validate amount.
    $amount_validation = $this->donationManager->validateAmount($content['amount']);
    if (!$amount_validation['valid']) {
      throw new BadRequestHttpException($amount_validation['message']);
    }

    // Validate projeto.
    $projeto_validation = $this->donationManager->validateProjeto((int) $content['projeto_id']);
    if (!$projeto_validation['valid']) {
      throw new BadRequestHttpException($projeto_validation['message']);
    }

    $projeto = $projeto_validation['node'];
    $amount = number_format((float) $content['amount'], 2, '.', '');
    $currency = $content['currency'] ?? DonationManager::DEFAULT_CURRENCY;

    // Validate currency (only EUR supported for now).
    if ($currency !== 'EUR') {
      throw new BadRequestHttpException('Only EUR currency is currently supported.');
    }

    // Create the donation order.
    try {
      $order = $this->donationManager->createDonation(
        $amount,
        $projeto,
        $currency,
        $content['email'] ?? NULL,
      );
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to create donation: @message', ['@message' => $e->getMessage()]);
      throw new BadRequestHttpException('Failed to create donation. Please try again.');
    }

    return new ModifiedResourceResponse([
      'data' => [
        'type' => 'donation',
        'id' => (int) $order->id(),
        'attributes' => [
          'order_number' => $order->getOrderNumber(),
          'amount' => $amount,
          'currency' => $currency,
          'state' => $order->getState()->getId(),
          'created' => date('c', $order->getCreatedTime()),
        ],
        'relationships' => [
          'projeto' => [
            'id' => (int) $projeto->id(),
            'title' => $projeto->label(),
          ],
          'user' => [
            'id' => (int) $current_user->id(),
          ],
        ],
      ],
      'meta' => [
        'message' => 'Donation order created successfully.',
      ],
    ], 201);
  }

}
