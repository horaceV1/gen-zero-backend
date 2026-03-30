<?php

namespace Drupal\gen_zero_subscriptions\Plugin\rest\resource;

use Drupal\gen_zero_subscriptions\SubscriptionManager;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * REST resource for creating subscriptions.
 *
 * @RestResource(
 *   id = "subscription_resource",
 *   label = @Translation("Subscription Resource"),
 *   uri_paths = {
 *     "create" = "/api/subscriptions"
 *   }
 * )
 */
class SubscriptionResource extends ResourceBase {

  /**
   * The subscription manager.
   */
  protected SubscriptionManager $subscriptionManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->subscriptionManager = $container->get('gen_zero_subscriptions.subscription_manager');
    return $instance;
  }

  /**
   * Responds to POST requests to create a subscription.
   *
   * Expected payload:
   * {
   *   "tier_id": "hopeful_acorn",
   *   "gateway": "manual",
   *   "payment_data": {},
   *   "email": "user@example.com"
   * }
   */
  public function post(Request $request): ModifiedResourceResponse {
    $content = json_decode($request->getContent(), TRUE);

    if (empty($content['tier_id'])) {
      return new ModifiedResourceResponse([
        'error' => 'Missing required field: tier_id',
      ], 400);
    }

    $tier_id = $content['tier_id'];
    $gateway = $content['gateway'] ?? 'manual';
    $payment_data = $content['payment_data'] ?? [];
    $email = $content['email'] ?? NULL;

    // Validate email format if provided.
    if ($email !== NULL && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
      return new ModifiedResourceResponse([
        'error' => 'Invalid email address.',
      ], 400);
    }

    $result = $this->subscriptionManager->createSubscription(
      $tier_id,
      $gateway,
      $payment_data,
      $email
    );

    if (!$result['success']) {
      return new ModifiedResourceResponse([
        'error' => $result['message'],
      ], 422);
    }

    return new ModifiedResourceResponse([
      'data' => $result,
    ], 201);
  }

}
