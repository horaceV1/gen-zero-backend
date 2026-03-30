<?php

namespace Drupal\gen_zero_subscriptions;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\gen_zero_subscriptions\Entity\SubscriptionTierInterface;
use Drupal\gen_zero_subscriptions\Entity\UserSubscriptionInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Service for managing subscriptions, tiers, and gateway delegation.
 */
class SubscriptionManager {

  /**
   * The logger channel.
   */
  protected LoggerInterface $logger;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected AccountProxyInterface $currentUser,
    LoggerChannelFactoryInterface $loggerFactory,
    protected SubscriptionPaymentGatewayManager $gatewayManager,
  ) {
    $this->logger = $loggerFactory->get('gen_zero_subscriptions');
  }

  /**
   * Gets all active tier groups with their tiers.
   *
   * @return array
   *   Nested array keyed by group ID with group info and tiers.
   */
  public function getGroupedTiers(): array {
    $groupStorage = $this->entityTypeManager->getStorage('subscription_tier_group');
    $tierStorage = $this->entityTypeManager->getStorage('subscription_tier');

    $groups = $groupStorage->loadMultiple();
    $tiers = $tierStorage->loadMultiple();

    // Sort groups by weight.
    uasort($groups, fn($a, $b) => $a->getWeight() <=> $b->getWeight());

    $result = [];
    foreach ($groups as $group) {
      if (!$group->status()) {
        continue;
      }
      $result[$group->id()] = [
        'id' => $group->id(),
        'label' => $group->label(),
        'description' => $group->getDescription(),
        'tiers' => [],
      ];
    }

    // Sort tiers by weight, then assign to groups.
    uasort($tiers, fn($a, $b) => $a->getWeight() <=> $b->getWeight());

    foreach ($tiers as $tier) {
      if (!$tier->status()) {
        continue;
      }
      $groupId = $tier->getGroup();
      if (!isset($result[$groupId])) {
        continue;
      }
      $result[$groupId]['tiers'][] = $this->formatTier($tier);
    }

    return array_values($result);
  }

  /**
   * Gets tiers for a specific group.
   *
   * @param string $group_id
   *   The tier group machine name.
   *
   * @return array
   *   Array of formatted tier data.
   */
  public function getTiersByGroup(string $group_id): array {
    $tierStorage = $this->entityTypeManager->getStorage('subscription_tier');
    $tiers = $tierStorage->loadByProperties([
      'group' => $group_id,
      'status' => TRUE,
    ]);

    uasort($tiers, fn($a, $b) => $a->getWeight() <=> $b->getWeight());

    return array_values(array_map(
      fn($tier) => $this->formatTier($tier),
      $tiers
    ));
  }

  /**
   * Loads and validates a tier by ID.
   *
   * @param string $tier_id
   *   The tier machine name.
   *
   * @return array
   *   Validation result with 'valid', 'message', and optionally 'tier'.
   */
  public function validateTier(string $tier_id): array {
    $tier = $this->entityTypeManager->getStorage('subscription_tier')->load($tier_id);

    if (!$tier) {
      return ['valid' => FALSE, 'message' => 'Subscription tier not found.'];
    }

    if (!$tier->status()) {
      return ['valid' => FALSE, 'message' => 'This subscription tier is currently unavailable.'];
    }

    return ['valid' => TRUE, 'tier' => $tier];
  }

  /**
   * Gets available payment gateway plugin definitions.
   *
   * @return array
   *   Array of gateway plugin definitions.
   */
  public function getAvailableGateways(): array {
    return $this->gatewayManager->getDefinitions();
  }

  /**
   * Creates a subscription, records it in the database, and delegates to the payment gateway.
   *
   * @param string $tier_id
   *   The subscription tier ID.
   * @param string $gateway_id
   *   The payment gateway plugin ID.
   * @param array $payment_data
   *   Gateway-specific payment data.
   * @param string|null $email
   *   Optional subscriber email.
   *
   * @return array
   *   Result array with subscription details or error.
   */
  public function createSubscription(string $tier_id, string $gateway_id, array $payment_data = [], ?string $email = NULL): array {
    $validation = $this->validateTier($tier_id);
    if (!$validation['valid']) {
      return ['success' => FALSE, 'message' => $validation['message']];
    }

    /** @var \Drupal\gen_zero_subscriptions\Entity\SubscriptionTierInterface $tier */
    $tier = $validation['tier'];

    if (!$this->gatewayManager->hasDefinition($gateway_id)) {
      return ['success' => FALSE, 'message' => 'Payment gateway not available.'];
    }

    try {
      /** @var \Drupal\gen_zero_subscriptions\Plugin\SubscriptionPaymentGateway\SubscriptionPaymentGatewayInterface $gateway */
      $gateway = $this->gatewayManager->createInstance($gateway_id);

      $gatewayResult = $gateway->createSubscription($tier, $payment_data, $email);

      if (empty($gatewayResult['success'])) {
        return $gatewayResult;
      }

      // Determine initial status — redirect gateways start as PENDING.
      $initialStatus = ($gatewayResult['initial_status'] ?? 'active') === 'pending'
        ? UserSubscriptionInterface::STATUS_PENDING
        : UserSubscriptionInterface::STATUS_ACTIVE;

      // Only set next billing date for immediately active subscriptions.
      $nextBilling = $initialStatus === UserSubscriptionInterface::STATUS_ACTIVE
        ? $this->calculateNextBillingDate($tier->getBillingPeriod())
        : 0;

      // Create the UserSubscription entity.
      $storage = $this->entityTypeManager->getStorage('user_subscription');
      $subscription = $storage->create([
        'tier_id' => $tier_id,
        'gateway_id' => $gateway_id,
        'external_id' => $gatewayResult['subscription_id'] ?? '',
        'subscription_status' => $initialStatus,
        'price' => $tier->getPrice(),
        'currency' => $tier->getCurrency(),
        'billing_period' => $tier->getBillingPeriod(),
        'next_billing_date' => $nextBilling,
        'email' => $email,
        'uid' => $this->currentUser->id(),
      ]);
      $subscription->save();

      $this->logger->info('Subscription created: id=%id, tier=%tier, gateway=%gateway, user=%user, status=%status', [
        '%id' => $subscription->id(),
        '%tier' => $tier_id,
        '%gateway' => $gateway_id,
        '%user' => $this->currentUser->id(),
        '%status' => $initialStatus,
      ]);

      $result = [
        'success' => TRUE,
        'message' => $gatewayResult['message'] ?? 'Subscription created successfully.',
        'subscription_id' => (int) $subscription->id(),
        'external_id' => $gatewayResult['subscription_id'] ?? '',
        'tier' => $this->formatTier($tier),
        'status' => $initialStatus,
        'next_billing_date' => $nextBilling,
        'gateway_data' => $gatewayResult['data'] ?? [],
      ];

      // Include redirect_url if the gateway requires redirection.
      if (!empty($gatewayResult['redirect_url'])) {
        $result['redirect_url'] = $gatewayResult['redirect_url'];
      }

      return $result;
    }
    catch (\Exception $e) {
      $this->logger->error('Subscription creation failed: @message', [
        '@message' => $e->getMessage(),
      ]);
      return ['success' => FALSE, 'message' => 'Failed to create subscription. Please try again.'];
    }
  }

  /**
   * Cancels an existing subscription.
   *
   * @param int $subscription_id
   *   The local UserSubscription entity ID.
   *
   * @return array
   *   Result with 'success' and 'message'.
   */
  public function cancelSubscription(int $subscription_id): array {
    $subscription = $this->loadSubscription($subscription_id);
    if (!$subscription) {
      return ['success' => FALSE, 'message' => 'Subscription not found.'];
    }

    $access = $this->checkSubscriptionAccess($subscription);
    if (!$access['allowed']) {
      return ['success' => FALSE, 'message' => $access['message']];
    }

    if ($subscription->getSubscriptionStatus() === UserSubscriptionInterface::STATUS_CANCELLED) {
      return ['success' => FALSE, 'message' => 'Subscription is already cancelled.'];
    }

    try {
      // Delegate to gateway if there's an external subscription.
      if ($subscription->getExternalId() && $this->gatewayManager->hasDefinition($subscription->getGatewayId())) {
        $gateway = $this->gatewayManager->createInstance($subscription->getGatewayId());
        $gateway->cancelSubscription($subscription->getExternalId());
      }

      $subscription->setSubscriptionStatus(UserSubscriptionInterface::STATUS_CANCELLED);
      $subscription->save();

      $this->logger->info('Subscription cancelled: id=%id, user=%user', [
        '%id' => $subscription_id,
        '%user' => $this->currentUser->id(),
      ]);

      return ['success' => TRUE, 'message' => 'Subscription cancelled.'];
    }
    catch (\Exception $e) {
      $this->logger->error('Subscription cancel failed: @message', ['@message' => $e->getMessage()]);
      return ['success' => FALSE, 'message' => 'Failed to cancel subscription.'];
    }
  }

  /**
   * Pauses an active subscription.
   *
   * @param int $subscription_id
   *   The local UserSubscription entity ID.
   *
   * @return array
   *   Result with 'success' and 'message'.
   */
  public function pauseSubscription(int $subscription_id): array {
    $subscription = $this->loadSubscription($subscription_id);
    if (!$subscription) {
      return ['success' => FALSE, 'message' => 'Subscription not found.'];
    }

    $access = $this->checkSubscriptionAccess($subscription);
    if (!$access['allowed']) {
      return ['success' => FALSE, 'message' => $access['message']];
    }

    if ($subscription->getSubscriptionStatus() !== UserSubscriptionInterface::STATUS_ACTIVE) {
      return ['success' => FALSE, 'message' => 'Only active subscriptions can be paused.'];
    }

    $subscription->setSubscriptionStatus(UserSubscriptionInterface::STATUS_PAUSED);
    $subscription->save();

    $this->logger->info('Subscription paused: id=%id', ['%id' => $subscription_id]);
    return ['success' => TRUE, 'message' => 'Subscription paused.'];
  }

  /**
   * Resumes a paused subscription.
   *
   * @param int $subscription_id
   *   The local UserSubscription entity ID.
   *
   * @return array
   *   Result with 'success' and 'message'.
   */
  public function resumeSubscription(int $subscription_id): array {
    $subscription = $this->loadSubscription($subscription_id);
    if (!$subscription) {
      return ['success' => FALSE, 'message' => 'Subscription not found.'];
    }

    $access = $this->checkSubscriptionAccess($subscription);
    if (!$access['allowed']) {
      return ['success' => FALSE, 'message' => $access['message']];
    }

    if ($subscription->getSubscriptionStatus() !== UserSubscriptionInterface::STATUS_PAUSED) {
      return ['success' => FALSE, 'message' => 'Only paused subscriptions can be resumed.'];
    }

    $nextBilling = $this->calculateNextBillingDate(
      $subscription->getBillingPeriod()
    );

    $subscription->setSubscriptionStatus(UserSubscriptionInterface::STATUS_ACTIVE);
    $subscription->setNextBillingDate($nextBilling);
    $subscription->save();

    $this->logger->info('Subscription resumed: id=%id', ['%id' => $subscription_id]);
    return [
      'success' => TRUE,
      'message' => 'Subscription resumed.',
      'next_billing_date' => $nextBilling,
    ];
  }

  /**
   * Gets all subscriptions for a given user.
   *
   * @param int $uid
   *   The user ID.
   * @param string|null $status
   *   Optional status filter.
   *
   * @return array
   *   Array of formatted subscription data.
   */
  public function getUserSubscriptions(int $uid, ?string $status = NULL): array {
    $properties = ['uid' => $uid];
    if ($status !== NULL) {
      $properties['subscription_status'] = $status;
    }

    $subscriptions = $this->entityTypeManager
      ->getStorage('user_subscription')
      ->loadByProperties($properties);

    return array_values(array_map(
      fn($sub) => $this->formatSubscription($sub),
      $subscriptions
    ));
  }

  /**
   * Gets a single subscription by ID, formatted.
   */
  public function getSubscription(int $subscription_id): ?array {
    $subscription = $this->loadSubscription($subscription_id);
    if (!$subscription) {
      return NULL;
    }
    return $this->formatSubscription($subscription);
  }

  /**
   * Checks if a user has an active subscription to a specific tier.
   */
  public function hasActiveSubscription(int $uid, string $tier_id): bool {
    $subscriptions = $this->entityTypeManager
      ->getStorage('user_subscription')
      ->loadByProperties([
        'uid' => $uid,
        'tier_id' => $tier_id,
        'subscription_status' => UserSubscriptionInterface::STATUS_ACTIVE,
      ]);
    return !empty($subscriptions);
  }

  /**
   * Loads a UserSubscription entity.
   */
  protected function loadSubscription(int $id): ?UserSubscriptionInterface {
    $entity = $this->entityTypeManager->getStorage('user_subscription')->load($id);
    return $entity instanceof UserSubscriptionInterface ? $entity : NULL;
  }

  /**
   * Checks if the current user can manage the given subscription.
   */
  protected function checkSubscriptionAccess(UserSubscriptionInterface $subscription): array {
    if ($this->currentUser->hasPermission('administer subscriptions')) {
      return ['allowed' => TRUE];
    }

    if ($this->currentUser->hasPermission('manage own subscription')
      && (int) $subscription->getOwnerId() === (int) $this->currentUser->id()) {
      return ['allowed' => TRUE];
    }

    return ['allowed' => FALSE, 'message' => 'You do not have permission to manage this subscription.'];
  }

  /**
   * Calculates the next billing date from now based on the period.
   */
  public function calculateNextBillingDate(string $period): int {
    $now = new \DateTimeImmutable();
    $next = match ($period) {
      'monthly' => $now->modify('+1 month'),
      'quarterly' => $now->modify('+3 months'),
      'yearly' => $now->modify('+1 year'),
      default => $now->modify('+1 month'),
    };
    return $next->getTimestamp();
  }

  /**
   * Processes an incoming webhook from a payment gateway.
   *
   * Finds the subscription by external_id and updates its status.
   */
  public function processGatewayWebhook(string $gateway_id, Request $request): array {
    if (!$this->gatewayManager->hasDefinition($gateway_id)) {
      $this->logger->warning('Webhook received for unknown gateway: @gw', ['@gw' => $gateway_id]);
      return ['success' => FALSE, 'message' => 'Unknown gateway.'];
    }

    try {
      $gateway = $this->gatewayManager->createInstance($gateway_id);
      $result = $gateway->processWebhook($request);

      if (empty($result['success']) || empty($result['external_id'])) {
        return $result;
      }

      $externalId = $result['external_id'];
      $newStatus = $result['new_status'] ?? NULL;

      // Find the subscription by external_id.
      $subscriptions = $this->entityTypeManager
        ->getStorage('user_subscription')
        ->loadByProperties(['external_id' => $externalId]);

      if (empty($subscriptions)) {
        $this->logger->warning('Webhook: No subscription found for external_id @id', ['@id' => $externalId]);
        return ['success' => FALSE, 'message' => 'Subscription not found.'];
      }

      /** @var \Drupal\gen_zero_subscriptions\Entity\UserSubscriptionInterface $subscription */
      $subscription = reset($subscriptions);

      $statusMap = [
        'active' => UserSubscriptionInterface::STATUS_ACTIVE,
        'cancelled' => UserSubscriptionInterface::STATUS_CANCELLED,
        'paused' => UserSubscriptionInterface::STATUS_PAUSED,
        'expired' => UserSubscriptionInterface::STATUS_EXPIRED,
      ];

      if ($newStatus && isset($statusMap[$newStatus])) {
        $subscription->setSubscriptionStatus($statusMap[$newStatus]);

        // If activating a pending subscription, set the billing date now.
        if ($newStatus === 'active' && $subscription->getNextBillingDate() === 0) {
          $nextBilling = $this->calculateNextBillingDate($subscription->getBillingPeriod());
          $subscription->setNextBillingDate($nextBilling);
        }

        $subscription->save();

        $this->logger->info('Webhook: Subscription @id status updated to @status via @gw', [
          '@id' => $subscription->id(),
          '@status' => $newStatus,
          '@gw' => $gateway_id,
        ]);
      }

      return [
        'success' => TRUE,
        'message' => $result['message'] ?? 'Webhook processed.',
        'subscription_id' => (int) $subscription->id(),
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Webhook processing failed for @gw: @msg', [
        '@gw' => $gateway_id,
        '@msg' => $e->getMessage(),
      ]);
      return ['success' => FALSE, 'message' => 'Webhook processing error.'];
    }
  }

  /**
   * Returns available gateways formatted for the API.
   *
   * Excludes 'manual' from public-facing API responses.
   */
  public function getAvailableGatewaysForApi(): array {
    $definitions = $this->gatewayManager->getDefinitions();
    $gateways = [];

    foreach ($definitions as $id => $definition) {
      if ($id === 'manual') {
        continue;
      }
      $gateways[] = [
        'id' => $id,
        'label' => (string) $definition['label'],
        'description' => (string) ($definition['description'] ?? ''),
      ];
    }

    return $gateways;
  }

  /**
   * Formats a tier entity for API output.
   */
  protected function formatTier(SubscriptionTierInterface $tier): array {
    $data = [
      'id' => $tier->id(),
      'label' => $tier->label(),
      'price' => $tier->getPrice(),
      'currency' => $tier->getCurrency(),
      'billing_period' => $tier->getBillingPeriod(),
      'description' => $tier->getDescription(),
      'benefits' => $tier->getBenefits(),
      'badge_label' => $tier->getBadgeLabel(),
    ];
    return $data;
  }

  /**
   * Formats a UserSubscription entity for API output.
   */
  protected function formatSubscription(UserSubscriptionInterface $subscription): array {
    return [
      'id' => (int) $subscription->id(),
      'tier_id' => $subscription->getTierId(),
      'gateway_id' => $subscription->getGatewayId(),
      'external_id' => $subscription->getExternalId(),
      'status' => $subscription->getSubscriptionStatus(),
      'price' => $subscription->getPrice(),
      'currency' => $subscription->getCurrency(),
      'billing_period' => $subscription->getBillingPeriod(),
      'next_billing_date' => $subscription->getNextBillingDate(),
      'created' => $subscription->getCreatedTime(),
    ];
  }

}
