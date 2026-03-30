<?php

namespace Drupal\gen_zero_subscriptions\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\gen_zero_subscriptions\SubscriptionManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for user subscription lifecycle API endpoints.
 */
class UserSubscriptionController extends ControllerBase {

  public function __construct(
    protected SubscriptionManager $subscriptionManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('gen_zero_subscriptions.subscription_manager')
    );
  }

  /**
   * POST /api/subscriptions/subscribe
   *
   * Creates a subscription for the authenticated user.
   *
   * Payload: {"tier_id": "semente", "gateway": "manual", "email": "optional"}
   * - tier_id: required — the machine name of the subscription tier.
   * - gateway: optional — defaults to "manual". Will be "stripe" when ready.
   * - payment_data: optional — gateway-specific data (e.g. Stripe token).
   * - email: optional — subscriber email override.
   */
  public function subscribe(Request $request): JsonResponse {
    $content = json_decode($request->getContent(), TRUE);

    if (empty($content['tier_id']) || !is_string($content['tier_id'])) {
      return new JsonResponse([
        'success' => FALSE,
        'message' => 'Missing required field: tier_id',
      ], 400);
    }

    $tier_id = $content['tier_id'];
    $gateway = $content['gateway'] ?? 'manual';
    $payment_data = $content['payment_data'] ?? [];
    $email = $content['email'] ?? NULL;

    if (!is_string($gateway) || !is_array($payment_data)) {
      return new JsonResponse([
        'success' => FALSE,
        'message' => 'Invalid payload format.',
      ], 400);
    }

    if ($email !== NULL && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
      return new JsonResponse([
        'success' => FALSE,
        'message' => 'Invalid email address.',
      ], 400);
    }

    // Check if user already has an active subscription to this tier.
    $uid = (int) $this->currentUser()->id();
    if ($this->subscriptionManager->hasActiveSubscription($uid, $tier_id)) {
      return new JsonResponse([
        'success' => FALSE,
        'message' => 'You already have an active subscription to this tier.',
      ], 409);
    }

    $result = $this->subscriptionManager->createSubscription(
      $tier_id,
      $gateway,
      $payment_data,
      $email
    );

    if (!$result['success']) {
      return new JsonResponse($result, 422);
    }

    return new JsonResponse($result, 201);
  }

  /**
   * GET /api/subscriptions/mine
   */
  public function mySubscriptions(): JsonResponse {
    $uid = (int) $this->currentUser()->id();
    $subscriptions = $this->subscriptionManager->getUserSubscriptions($uid);
    return new JsonResponse(['data' => $subscriptions]);
  }

  /**
   * POST /api/subscriptions/{subscription_id}/cancel
   */
  public function cancel(int $subscription_id): JsonResponse {
    $result = $this->subscriptionManager->cancelSubscription($subscription_id);
    $code = $result['success'] ? 200 : 422;
    return new JsonResponse($result, $code);
  }

  /**
   * POST /api/subscriptions/{subscription_id}/pause
   */
  public function pause(int $subscription_id): JsonResponse {
    $result = $this->subscriptionManager->pauseSubscription($subscription_id);
    $code = $result['success'] ? 200 : 422;
    return new JsonResponse($result, $code);
  }

  /**
   * POST /api/subscriptions/{subscription_id}/resume
   */
  public function resume(int $subscription_id): JsonResponse {
    $result = $this->subscriptionManager->resumeSubscription($subscription_id);
    $code = $result['success'] ? 200 : 422;
    return new JsonResponse($result, $code);
  }

}
