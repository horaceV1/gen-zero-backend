<?php

namespace Drupal\gen_zero_subscriptions\Plugin\SubscriptionPaymentGateway;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\gen_zero_subscriptions\Entity\SubscriptionTierInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Interface for subscription payment gateway plugins.
 *
 * Implement this interface to add a new payment provider (PayPal, EuPago, etc.).
 * Each gateway handles creating, cancelling, and managing subscriptions
 * through the provider's API.
 */
interface SubscriptionPaymentGatewayInterface extends PluginInspectionInterface {

  /**
   * Returns the gateway label.
   */
  public function getLabel(): string;

  /**
   * Whether this gateway requires redirecting the user to complete payment.
   *
   * If TRUE, createSubscription() should return 'redirect_url' in its data
   * and the subscription will be created with PENDING status.
   */
  public function requiresRedirect(): bool;

  /**
   * Creates a subscription through the payment provider.
   *
   * @param \Drupal\gen_zero_subscriptions\Entity\SubscriptionTierInterface $tier
   *   The subscription tier being purchased.
   * @param array $payment_data
   *   Provider-specific payment data (e.g., token, payment method ID).
   * @param string|null $email
   *   The subscriber's email address.
   *
   * @return array
   *   Result array with keys:
   *   - 'success' (bool): Whether the subscription was initiated.
   *   - 'subscription_id' (string): External subscription ID.
   *   - 'message' (string): Human-readable status message.
   *   - 'data' (array): Any additional provider-specific data.
   *   - 'redirect_url' (string|null): URL to redirect user for payment.
   *   - 'initial_status' (string): 'active' or 'pending' (default: 'active').
   */
  public function createSubscription(SubscriptionTierInterface $tier, array $payment_data, ?string $email = NULL): array;

  /**
   * Cancels a subscription.
   *
   * @param string $subscription_id
   *   The external subscription ID.
   *
   * @return array
   *   Result with 'success' and 'message' keys.
   */
  public function cancelSubscription(string $subscription_id): array;

  /**
   * Gets the current status of a subscription.
   *
   * @param string $subscription_id
   *   The external subscription ID.
   *
   * @return array
   *   Result with 'status', 'next_billing_date', etc.
   */
  public function getSubscriptionStatus(string $subscription_id): array;

  /**
   * Processes an incoming webhook from the payment provider.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The incoming webhook request.
   *
   * @return array
   *   Result array with keys:
   *   - 'success' (bool): Whether the webhook was processed.
   *   - 'external_id' (string): The external subscription ID affected.
   *   - 'new_status' (string): The new subscription status (active, cancelled).
   *   - 'message' (string): Description of what happened.
   */
  public function processWebhook(Request $request): array;

}
