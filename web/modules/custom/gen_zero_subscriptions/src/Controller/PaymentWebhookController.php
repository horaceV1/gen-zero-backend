<?php

namespace Drupal\gen_zero_subscriptions\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\gen_zero_subscriptions\SubscriptionManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Handles incoming webhooks from payment gateway providers.
 *
 * Route: POST /api/subscriptions/webhook/{gateway_id}
 * No authentication — external services call these endpoints.
 */
class PaymentWebhookController extends ControllerBase {

  public function __construct(
    protected SubscriptionManager $subscriptionManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('gen_zero_subscriptions.subscription_manager'),
    );
  }

  /**
   * Processes an incoming webhook for the given gateway.
   */
  public function handle(string $gateway_id, Request $request): Response {
    $result = $this->subscriptionManager->processGatewayWebhook($gateway_id, $request);

    if (!$result['success']) {
      // Return 200 even on "failure" to prevent providers from retrying
      // endlessly for events we deliberately ignore.
      return new JsonResponse($result, 200);
    }

    return new JsonResponse($result, 200);
  }

}
