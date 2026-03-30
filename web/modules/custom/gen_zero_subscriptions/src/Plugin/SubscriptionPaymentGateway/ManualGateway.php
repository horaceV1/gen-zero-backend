<?php

namespace Drupal\gen_zero_subscriptions\Plugin\SubscriptionPaymentGateway;

use Drupal\gen_zero_subscriptions\Entity\SubscriptionTierInterface;

/**
 * Manual/offline subscription gateway for testing or admin-managed subscriptions.
 *
 * @SubscriptionPaymentGateway(
 *   id = "manual",
 *   label = @Translation("Manual / Offline"),
 *   description = @Translation("Records subscriptions without processing payment. Useful for admin-managed or test subscriptions."),
 * )
 */
class ManualGateway extends SubscriptionPaymentGatewayBase {

  /**
   * {@inheritdoc}
   */
  public function createSubscription(SubscriptionTierInterface $tier, array $payment_data, ?string $email = NULL): array {
    return [
      'success' => TRUE,
      'subscription_id' => 'manual_' . bin2hex(random_bytes(8)),
      'message' => 'Subscription recorded. Payment will be handled offline.',
      'initial_status' => 'active',
      'data' => [
        'tier_id' => $tier->id(),
        'tier_label' => $tier->label(),
        'price' => $tier->getPrice(),
        'currency' => $tier->getCurrency(),
        'billing_period' => $tier->getBillingPeriod(),
        'gateway' => 'manual',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function cancelSubscription(string $subscription_id): array {
    return [
      'success' => TRUE,
      'message' => 'Manual subscription cancelled.',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getSubscriptionStatus(string $subscription_id): array {
    return [
      'status' => 'active',
      'message' => 'Manual subscriptions are always reported as active.',
    ];
  }

}
