<?php

namespace Drupal\Tests\gen_zero_subscriptions\Unit;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\gen_zero_subscriptions\Entity\SubscriptionTierGroupInterface;
use Drupal\gen_zero_subscriptions\Entity\SubscriptionTierInterface;
use Drupal\gen_zero_subscriptions\Entity\UserSubscriptionInterface;
use Drupal\gen_zero_subscriptions\Plugin\SubscriptionPaymentGateway\SubscriptionPaymentGatewayInterface;
use Drupal\gen_zero_subscriptions\SubscriptionManager;
use Drupal\gen_zero_subscriptions\SubscriptionPaymentGatewayManager;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for SubscriptionManager.
 *
 * @group gen_zero_subscriptions
 * @coversDefaultClass \Drupal\gen_zero_subscriptions\SubscriptionManager
 */
class SubscriptionManagerTest extends UnitTestCase {

  protected EntityTypeManagerInterface $entityTypeManager;
  protected AccountProxyInterface $currentUser;
  protected SubscriptionPaymentGatewayManager $gatewayManager;
  protected LoggerChannelFactoryInterface $loggerFactory;
  protected LoggerInterface $logger;
  protected SubscriptionManager $manager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->currentUser = $this->createMock(AccountProxyInterface::class);
    $this->gatewayManager = $this->createMock(SubscriptionPaymentGatewayManager::class);
    $this->loggerFactory = $this->createMock(LoggerChannelFactoryInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    $this->loggerFactory->method('get')
      ->with('gen_zero_subscriptions')
      ->willReturn($this->logger);

    $this->manager = new SubscriptionManager(
      $this->entityTypeManager,
      $this->currentUser,
      $this->loggerFactory,
      $this->gatewayManager,
    );
  }

  /**
   * @covers ::validateTier
   */
  public function testValidateTierNotFound(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with('nonexistent')->willReturn(NULL);
    $this->entityTypeManager->method('getStorage')
      ->with('subscription_tier')
      ->willReturn($storage);

    $result = $this->manager->validateTier('nonexistent');

    $this->assertFalse($result['valid']);
    $this->assertEquals('Subscription tier not found.', $result['message']);
  }

  /**
   * @covers ::validateTier
   */
  public function testValidateTierDisabled(): void {
    $tier = $this->createMock(SubscriptionTierInterface::class);
    $tier->method('status')->willReturn(FALSE);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with('disabled_tier')->willReturn($tier);
    $this->entityTypeManager->method('getStorage')
      ->with('subscription_tier')
      ->willReturn($storage);

    $result = $this->manager->validateTier('disabled_tier');

    $this->assertFalse($result['valid']);
    $this->assertEquals('This subscription tier is currently unavailable.', $result['message']);
  }

  /**
   * @covers ::validateTier
   */
  public function testValidateTierSuccess(): void {
    $tier = $this->createMock(SubscriptionTierInterface::class);
    $tier->method('status')->willReturn(TRUE);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with('hopeful_acorn')->willReturn($tier);
    $this->entityTypeManager->method('getStorage')
      ->with('subscription_tier')
      ->willReturn($storage);

    $result = $this->manager->validateTier('hopeful_acorn');

    $this->assertTrue($result['valid']);
    $this->assertSame($tier, $result['tier']);
  }

  /**
   * @covers ::getGroupedTiers
   */
  public function testGetGroupedTiersFiltersDisabled(): void {
    $activeGroup = $this->createMock(SubscriptionTierGroupInterface::class);
    $activeGroup->method('status')->willReturn(TRUE);
    $activeGroup->method('id')->willReturn('active');
    $activeGroup->method('label')->willReturn('Active Group');
    $activeGroup->method('getDescription')->willReturn('Active desc');
    $activeGroup->method('getWeight')->willReturn(0);

    $disabledGroup = $this->createMock(SubscriptionTierGroupInterface::class);
    $disabledGroup->method('status')->willReturn(FALSE);
    $disabledGroup->method('id')->willReturn('disabled');
    $disabledGroup->method('getWeight')->willReturn(1);

    $tier = $this->createMock(SubscriptionTierInterface::class);
    $tier->method('status')->willReturn(TRUE);
    $tier->method('id')->willReturn('test_tier');
    $tier->method('label')->willReturn('Test');
    $tier->method('getGroup')->willReturn('active');
    $tier->method('getPrice')->willReturn('10.00');
    $tier->method('getCurrency')->willReturn('EUR');
    $tier->method('getBillingPeriod')->willReturn('monthly');
    $tier->method('getDescription')->willReturn('Test desc');
    $tier->method('getBenefits')->willReturn(['Benefit 1']);
    $tier->method('getBadgeLabel')->willReturn('Badge');
    $tier->method('getWeight')->willReturn(0);

    $groupStorage = $this->createMock(EntityStorageInterface::class);
    $groupStorage->method('loadMultiple')->willReturn([
      'active' => $activeGroup,
      'disabled' => $disabledGroup,
    ]);

    $tierStorage = $this->createMock(EntityStorageInterface::class);
    $tierStorage->method('loadMultiple')->willReturn([
      'test_tier' => $tier,
    ]);

    $this->entityTypeManager->method('getStorage')
      ->willReturnMap([
        ['subscription_tier_group', $groupStorage],
        ['subscription_tier', $tierStorage],
      ]);

    $result = $this->manager->getGroupedTiers();

    $this->assertCount(1, $result);
    $this->assertEquals('active', $result[0]['id']);
    $this->assertCount(1, $result[0]['tiers']);
    $this->assertEquals('test_tier', $result[0]['tiers'][0]['id']);
  }

  /**
   * @covers ::getGroupedTiers
   */
  public function testGetGroupedTiersSortsByWeight(): void {
    $group1 = $this->createMock(SubscriptionTierGroupInterface::class);
    $group1->method('status')->willReturn(TRUE);
    $group1->method('id')->willReturn('heavy');
    $group1->method('label')->willReturn('Heavy');
    $group1->method('getDescription')->willReturn('');
    $group1->method('getWeight')->willReturn(10);

    $group2 = $this->createMock(SubscriptionTierGroupInterface::class);
    $group2->method('status')->willReturn(TRUE);
    $group2->method('id')->willReturn('light');
    $group2->method('label')->willReturn('Light');
    $group2->method('getDescription')->willReturn('');
    $group2->method('getWeight')->willReturn(0);

    $groupStorage = $this->createMock(EntityStorageInterface::class);
    $groupStorage->method('loadMultiple')->willReturn([
      'heavy' => $group1,
      'light' => $group2,
    ]);

    $tierStorage = $this->createMock(EntityStorageInterface::class);
    $tierStorage->method('loadMultiple')->willReturn([]);

    $this->entityTypeManager->method('getStorage')
      ->willReturnMap([
        ['subscription_tier_group', $groupStorage],
        ['subscription_tier', $tierStorage],
      ]);

    $result = $this->manager->getGroupedTiers();

    $this->assertCount(2, $result);
    $this->assertEquals('light', $result[0]['id']);
    $this->assertEquals('heavy', $result[1]['id']);
  }

  /**
   * @covers ::getGroupedTiers
   */
  public function testGetGroupedTiersSkipsOrphanedTiers(): void {
    $group = $this->createMock(SubscriptionTierGroupInterface::class);
    $group->method('status')->willReturn(TRUE);
    $group->method('id')->willReturn('exists');
    $group->method('label')->willReturn('Exists');
    $group->method('getDescription')->willReturn('');
    $group->method('getWeight')->willReturn(0);

    $orphanedTier = $this->createMock(SubscriptionTierInterface::class);
    $orphanedTier->method('status')->willReturn(TRUE);
    $orphanedTier->method('getGroup')->willReturn('deleted_group');
    $orphanedTier->method('getWeight')->willReturn(0);

    $groupStorage = $this->createMock(EntityStorageInterface::class);
    $groupStorage->method('loadMultiple')->willReturn(['exists' => $group]);

    $tierStorage = $this->createMock(EntityStorageInterface::class);
    $tierStorage->method('loadMultiple')->willReturn(['orphan' => $orphanedTier]);

    $this->entityTypeManager->method('getStorage')
      ->willReturnMap([
        ['subscription_tier_group', $groupStorage],
        ['subscription_tier', $tierStorage],
      ]);

    $result = $this->manager->getGroupedTiers();

    $this->assertCount(1, $result);
    $this->assertEmpty($result[0]['tiers']);
  }

  /**
   * @covers ::createSubscription
   */
  public function testCreateSubscriptionInvalidTier(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with('fake_tier')->willReturn(NULL);
    $this->entityTypeManager->method('getStorage')
      ->with('subscription_tier')
      ->willReturn($storage);

    $result = $this->manager->createSubscription('fake_tier', 'manual');

    $this->assertFalse($result['success']);
    $this->assertEquals('Subscription tier not found.', $result['message']);
  }

  /**
   * @covers ::createSubscription
   */
  public function testCreateSubscriptionInvalidGateway(): void {
    $tier = $this->createMock(SubscriptionTierInterface::class);
    $tier->method('status')->willReturn(TRUE);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with('valid_tier')->willReturn($tier);
    $this->entityTypeManager->method('getStorage')
      ->with('subscription_tier')
      ->willReturn($storage);

    $this->gatewayManager->method('hasDefinition')
      ->with('nonexistent_gateway')
      ->willReturn(FALSE);

    $result = $this->manager->createSubscription('valid_tier', 'nonexistent_gateway');

    $this->assertFalse($result['success']);
    $this->assertEquals('Payment gateway not available.', $result['message']);
  }

  /**
   * @covers ::createSubscription
   */
  public function testCreateSubscriptionGatewayFailure(): void {
    $tier = $this->createMock(SubscriptionTierInterface::class);
    $tier->method('status')->willReturn(TRUE);

    $tierStorage = $this->createMock(EntityStorageInterface::class);
    $tierStorage->method('load')->with('valid_tier')->willReturn($tier);

    $this->entityTypeManager->method('getStorage')
      ->willReturnCallback(function ($type) use ($tierStorage) {
        if ($type === 'subscription_tier') {
          return $tierStorage;
        }
        return $this->createMock(EntityStorageInterface::class);
      });

    $this->gatewayManager->method('hasDefinition')->with('manual')->willReturn(TRUE);

    $gateway = $this->createMock(SubscriptionPaymentGatewayInterface::class);
    $gateway->method('createSubscription')
      ->willReturn(['success' => FALSE, 'message' => 'Gateway error.']);
    $this->gatewayManager->method('createInstance')->with('manual')->willReturn($gateway);

    $result = $this->manager->createSubscription('valid_tier', 'manual');

    $this->assertFalse($result['success']);
    $this->assertEquals('Gateway error.', $result['message']);
  }

  /**
   * @covers ::createSubscription
   */
  public function testCreateSubscriptionGatewayException(): void {
    $tier = $this->createMock(SubscriptionTierInterface::class);
    $tier->method('status')->willReturn(TRUE);

    $tierStorage = $this->createMock(EntityStorageInterface::class);
    $tierStorage->method('load')->with('valid_tier')->willReturn($tier);

    $this->entityTypeManager->method('getStorage')
      ->willReturnCallback(function ($type) use ($tierStorage) {
        if ($type === 'subscription_tier') {
          return $tierStorage;
        }
        return $this->createMock(EntityStorageInterface::class);
      });

    $this->gatewayManager->method('hasDefinition')->with('manual')->willReturn(TRUE);

    $gateway = $this->createMock(SubscriptionPaymentGatewayInterface::class);
    $gateway->method('createSubscription')
      ->willThrowException(new \RuntimeException('Connection failed'));
    $this->gatewayManager->method('createInstance')->with('manual')->willReturn($gateway);

    $this->logger->expects($this->once())->method('error');

    $result = $this->manager->createSubscription('valid_tier', 'manual');

    $this->assertFalse($result['success']);
    $this->assertEquals('Failed to create subscription. Please try again.', $result['message']);
  }

  /**
   * @covers ::createSubscription
   */
  public function testCreateSubscriptionSuccess(): void {
    $tier = $this->createMock(SubscriptionTierInterface::class);
    $tier->method('status')->willReturn(TRUE);
    $tier->method('id')->willReturn('hopeful_acorn');
    $tier->method('label')->willReturn('Hopeful Acorn');
    $tier->method('getPrice')->willReturn('5.00');
    $tier->method('getCurrency')->willReturn('EUR');
    $tier->method('getBillingPeriod')->willReturn('monthly');
    $tier->method('getDescription')->willReturn('Test');
    $tier->method('getBenefits')->willReturn(['Benefit']);
    $tier->method('getBadgeLabel')->willReturn('Starter');

    $tierStorage = $this->createMock(EntityStorageInterface::class);
    $tierStorage->method('load')->with('hopeful_acorn')->willReturn($tier);

    // Mock the user_subscription entity and storage.
    $subscription = $this->createMock(UserSubscriptionInterface::class);
    $subscription->method('id')->willReturn(42);

    $subStorage = $this->createMock(EntityStorageInterface::class);
    $subStorage->method('create')->willReturn($subscription);

    $this->entityTypeManager->method('getStorage')
      ->willReturnMap([
        ['subscription_tier', $tierStorage],
        ['user_subscription', $subStorage],
      ]);

    $this->gatewayManager->method('hasDefinition')->with('manual')->willReturn(TRUE);
    $gateway = $this->createMock(SubscriptionPaymentGatewayInterface::class);
    $gateway->method('createSubscription')->willReturn([
      'success' => TRUE,
      'subscription_id' => 'ext_123',
      'message' => 'OK',
      'data' => ['gateway' => 'manual'],
    ]);
    $this->gatewayManager->method('createInstance')->with('manual')->willReturn($gateway);

    $this->currentUser->method('id')->willReturn(1);

    $result = $this->manager->createSubscription('hopeful_acorn', 'manual', [], 'test@example.com');

    $this->assertTrue($result['success']);
    $this->assertEquals(42, $result['subscription_id']);
    $this->assertEquals('ext_123', $result['external_id']);
    $this->assertArrayHasKey('next_billing_date', $result);
    $this->assertArrayHasKey('tier', $result);
    $this->assertEquals('hopeful_acorn', $result['tier']['id']);
  }

  /**
   * @covers ::cancelSubscription
   */
  public function testCancelSubscriptionNotFound(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with(999)->willReturn(NULL);
    $this->entityTypeManager->method('getStorage')
      ->with('user_subscription')
      ->willReturn($storage);

    $result = $this->manager->cancelSubscription(999);

    $this->assertFalse($result['success']);
    $this->assertEquals('Subscription not found.', $result['message']);
  }

  /**
   * @covers ::cancelSubscription
   */
  public function testCancelSubscriptionNoPermission(): void {
    $subscription = $this->createMock(UserSubscriptionInterface::class);
    $subscription->method('getOwnerId')->willReturn(99);
    $subscription->method('getSubscriptionStatus')->willReturn(UserSubscriptionInterface::STATUS_ACTIVE);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with(1)->willReturn($subscription);
    $this->entityTypeManager->method('getStorage')
      ->with('user_subscription')
      ->willReturn($storage);

    // Current user is different from owner, no admin permissions.
    $this->currentUser->method('id')->willReturn(50);
    $this->currentUser->method('hasPermission')->willReturn(FALSE);

    $result = $this->manager->cancelSubscription(1);

    $this->assertFalse($result['success']);
    $this->assertStringContainsString('permission', $result['message']);
  }

  /**
   * @covers ::cancelSubscription
   */
  public function testCancelAlreadyCancelledSubscription(): void {
    $subscription = $this->createMock(UserSubscriptionInterface::class);
    $subscription->method('getOwnerId')->willReturn(1);
    $subscription->method('getSubscriptionStatus')->willReturn(UserSubscriptionInterface::STATUS_CANCELLED);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with(1)->willReturn($subscription);
    $this->entityTypeManager->method('getStorage')
      ->with('user_subscription')
      ->willReturn($storage);

    $this->currentUser->method('id')->willReturn(1);
    $this->currentUser->method('hasPermission')
      ->willReturnCallback(fn($perm) => $perm === 'manage own subscription');

    $result = $this->manager->cancelSubscription(1);

    $this->assertFalse($result['success']);
    $this->assertStringContainsString('already cancelled', $result['message']);
  }

  /**
   * @covers ::cancelSubscription
   */
  public function testCancelSubscriptionSuccess(): void {
    $subscription = $this->createMock(UserSubscriptionInterface::class);
    $subscription->method('getOwnerId')->willReturn(1);
    $subscription->method('getSubscriptionStatus')->willReturn(UserSubscriptionInterface::STATUS_ACTIVE);
    $subscription->method('getExternalId')->willReturn('ext_123');
    $subscription->method('getGatewayId')->willReturn('manual');
    $subscription->expects($this->once())->method('setSubscriptionStatus')
      ->with(UserSubscriptionInterface::STATUS_CANCELLED);
    $subscription->expects($this->once())->method('save');

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with(1)->willReturn($subscription);
    $this->entityTypeManager->method('getStorage')
      ->with('user_subscription')
      ->willReturn($storage);

    $this->currentUser->method('id')->willReturn(1);
    $this->currentUser->method('hasPermission')
      ->willReturnCallback(fn($perm) => $perm === 'manage own subscription');

    $gateway = $this->createMock(SubscriptionPaymentGatewayInterface::class);
    $gateway->expects($this->once())->method('cancelSubscription')
      ->with('ext_123');
    $this->gatewayManager->method('hasDefinition')->with('manual')->willReturn(TRUE);
    $this->gatewayManager->method('createInstance')->with('manual')->willReturn($gateway);

    $result = $this->manager->cancelSubscription(1);

    $this->assertTrue($result['success']);
  }

  /**
   * @covers ::pauseSubscription
   */
  public function testPauseNonActiveSubscription(): void {
    $subscription = $this->createMock(UserSubscriptionInterface::class);
    $subscription->method('getOwnerId')->willReturn(1);
    $subscription->method('getSubscriptionStatus')->willReturn(UserSubscriptionInterface::STATUS_CANCELLED);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with(1)->willReturn($subscription);
    $this->entityTypeManager->method('getStorage')
      ->with('user_subscription')
      ->willReturn($storage);

    $this->currentUser->method('id')->willReturn(1);
    $this->currentUser->method('hasPermission')
      ->willReturnCallback(fn($perm) => $perm === 'manage own subscription');

    $result = $this->manager->pauseSubscription(1);

    $this->assertFalse($result['success']);
    $this->assertStringContainsString('Only active', $result['message']);
  }

  /**
   * @covers ::pauseSubscription
   */
  public function testPauseSubscriptionSuccess(): void {
    $subscription = $this->createMock(UserSubscriptionInterface::class);
    $subscription->method('getOwnerId')->willReturn(1);
    $subscription->method('getSubscriptionStatus')->willReturn(UserSubscriptionInterface::STATUS_ACTIVE);
    $subscription->expects($this->once())->method('setSubscriptionStatus')
      ->with(UserSubscriptionInterface::STATUS_PAUSED);
    $subscription->expects($this->once())->method('save');

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with(1)->willReturn($subscription);
    $this->entityTypeManager->method('getStorage')
      ->with('user_subscription')
      ->willReturn($storage);

    $this->currentUser->method('id')->willReturn(1);
    $this->currentUser->method('hasPermission')
      ->willReturnCallback(fn($perm) => $perm === 'manage own subscription');

    $result = $this->manager->pauseSubscription(1);

    $this->assertTrue($result['success']);
  }

  /**
   * @covers ::resumeSubscription
   */
  public function testResumeNonPausedSubscription(): void {
    $subscription = $this->createMock(UserSubscriptionInterface::class);
    $subscription->method('getOwnerId')->willReturn(1);
    $subscription->method('getSubscriptionStatus')->willReturn(UserSubscriptionInterface::STATUS_ACTIVE);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with(1)->willReturn($subscription);
    $this->entityTypeManager->method('getStorage')
      ->with('user_subscription')
      ->willReturn($storage);

    $this->currentUser->method('id')->willReturn(1);
    $this->currentUser->method('hasPermission')
      ->willReturnCallback(fn($perm) => $perm === 'manage own subscription');

    $result = $this->manager->resumeSubscription(1);

    $this->assertFalse($result['success']);
    $this->assertStringContainsString('Only paused', $result['message']);
  }

  /**
   * @covers ::resumeSubscription
   */
  public function testResumeSubscriptionSuccess(): void {
    $subscription = $this->createMock(UserSubscriptionInterface::class);
    $subscription->method('getOwnerId')->willReturn(1);
    $subscription->method('getSubscriptionStatus')->willReturn(UserSubscriptionInterface::STATUS_PAUSED);
    $subscription->method('getBillingPeriod')->willReturn('monthly');
    $subscription->expects($this->once())->method('setSubscriptionStatus')
      ->with(UserSubscriptionInterface::STATUS_ACTIVE);
    $subscription->expects($this->once())->method('setNextBillingDate');
    $subscription->expects($this->once())->method('save');

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with(1)->willReturn($subscription);
    $this->entityTypeManager->method('getStorage')
      ->with('user_subscription')
      ->willReturn($storage);

    $this->currentUser->method('id')->willReturn(1);
    $this->currentUser->method('hasPermission')
      ->willReturnCallback(fn($perm) => $perm === 'manage own subscription');

    $result = $this->manager->resumeSubscription(1);

    $this->assertTrue($result['success']);
    $this->assertArrayHasKey('next_billing_date', $result);
  }

  /**
   * @covers ::calculateNextBillingDate
   */
  public function testCalculateNextBillingDate(): void {
    $now = time();

    $monthly = $this->manager->calculateNextBillingDate('monthly');
    // Should be roughly 28-31 days from now.
    $this->assertGreaterThan($now + (27 * 86400), $monthly);
    $this->assertLessThan($now + (32 * 86400), $monthly);

    $quarterly = $this->manager->calculateNextBillingDate('quarterly');
    // Should be roughly 89-92 days from now.
    $this->assertGreaterThan($now + (88 * 86400), $quarterly);
    $this->assertLessThan($now + (93 * 86400), $quarterly);

    $yearly = $this->manager->calculateNextBillingDate('yearly');
    // Should be roughly 364-366 days from now.
    $this->assertGreaterThan($now + (363 * 86400), $yearly);
    $this->assertLessThan($now + (367 * 86400), $yearly);

    // Default (unknown period) should behave like monthly.
    $unknown = $this->manager->calculateNextBillingDate('weekly');
    $this->assertGreaterThan($now + (27 * 86400), $unknown);
  }

  /**
   * @covers ::hasActiveSubscription
   */
  public function testHasActiveSubscriptionTrue(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('loadByProperties')
      ->with([
        'uid' => 1,
        'tier_id' => 'hopeful_acorn',
        'subscription_status' => UserSubscriptionInterface::STATUS_ACTIVE,
      ])
      ->willReturn([1 => $this->createMock(UserSubscriptionInterface::class)]);

    $this->entityTypeManager->method('getStorage')
      ->with('user_subscription')
      ->willReturn($storage);

    $this->assertTrue($this->manager->hasActiveSubscription(1, 'hopeful_acorn'));
  }

  /**
   * @covers ::hasActiveSubscription
   */
  public function testHasActiveSubscriptionFalse(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('loadByProperties')->willReturn([]);

    $this->entityTypeManager->method('getStorage')
      ->with('user_subscription')
      ->willReturn($storage);

    $this->assertFalse($this->manager->hasActiveSubscription(1, 'hopeful_acorn'));
  }

  /**
   * @covers ::getUserSubscriptions
   */
  public function testGetUserSubscriptions(): void {
    $sub = $this->createMock(UserSubscriptionInterface::class);
    $sub->method('id')->willReturn(1);
    $sub->method('getTierId')->willReturn('hopeful_acorn');
    $sub->method('getGatewayId')->willReturn('manual');
    $sub->method('getExternalId')->willReturn('ext_1');
    $sub->method('getSubscriptionStatus')->willReturn('active');
    $sub->method('getPrice')->willReturn('5.00');
    $sub->method('getCurrency')->willReturn('EUR');
    $sub->method('getBillingPeriod')->willReturn('monthly');
    $sub->method('getNextBillingDate')->willReturn(1712000000);
    $sub->method('getCreatedTime')->willReturn(1709000000);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('loadByProperties')
      ->with(['uid' => 1])
      ->willReturn([1 => $sub]);

    $this->entityTypeManager->method('getStorage')
      ->with('user_subscription')
      ->willReturn($storage);

    $result = $this->manager->getUserSubscriptions(1);

    $this->assertCount(1, $result);
    $this->assertEquals('hopeful_acorn', $result[0]['tier_id']);
    $this->assertEquals('active', $result[0]['status']);
    $this->assertEquals('5.00', $result[0]['price']);
  }

  /**
   * @covers ::getUserSubscriptions
   */
  public function testGetUserSubscriptionsWithStatusFilter(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->expects($this->once())
      ->method('loadByProperties')
      ->with(['uid' => 1, 'subscription_status' => 'active'])
      ->willReturn([]);

    $this->entityTypeManager->method('getStorage')
      ->with('user_subscription')
      ->willReturn($storage);

    $result = $this->manager->getUserSubscriptions(1, 'active');
    $this->assertEmpty($result);
  }

  /**
   * @covers ::getAvailableGateways
   */
  public function testGetAvailableGateways(): void {
    $this->gatewayManager->method('getDefinitions')
      ->willReturn([
        'manual' => ['id' => 'manual', 'label' => 'Manual'],
        'stripe' => ['id' => 'stripe', 'label' => 'Stripe'],
      ]);

    $result = $this->manager->getAvailableGateways();

    $this->assertCount(2, $result);
    $this->assertArrayHasKey('manual', $result);
    $this->assertArrayHasKey('stripe', $result);
  }

  /**
   * @covers ::cancelSubscription
   * Tests that admin can cancel any user's subscription.
   */
  public function testAdminCanCancelAnySubscription(): void {
    $subscription = $this->createMock(UserSubscriptionInterface::class);
    $subscription->method('getOwnerId')->willReturn(99);
    $subscription->method('getSubscriptionStatus')->willReturn(UserSubscriptionInterface::STATUS_ACTIVE);
    $subscription->method('getExternalId')->willReturn('');
    $subscription->method('getGatewayId')->willReturn('manual');
    $subscription->expects($this->once())->method('setSubscriptionStatus')
      ->with(UserSubscriptionInterface::STATUS_CANCELLED);
    $subscription->expects($this->once())->method('save');

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with(1)->willReturn($subscription);
    $this->entityTypeManager->method('getStorage')
      ->with('user_subscription')
      ->willReturn($storage);

    // Admin user (different from owner).
    $this->currentUser->method('id')->willReturn(1);
    $this->currentUser->method('hasPermission')
      ->willReturnCallback(fn($perm) => $perm === 'administer subscriptions');

    $result = $this->manager->cancelSubscription(1);

    $this->assertTrue($result['success']);
  }

  /**
   * @covers ::getTiersByGroup
   */
  public function testGetTiersByGroup(): void {
    $tier1 = $this->createMock(SubscriptionTierInterface::class);
    $tier1->method('id')->willReturn('t1');
    $tier1->method('label')->willReturn('Tier 1');
    $tier1->method('getPrice')->willReturn('5.00');
    $tier1->method('getCurrency')->willReturn('EUR');
    $tier1->method('getBillingPeriod')->willReturn('monthly');
    $tier1->method('getDescription')->willReturn('desc');
    $tier1->method('getBenefits')->willReturn(['b']);
    $tier1->method('getBadgeLabel')->willReturn('T');
    $tier1->method('getWeight')->willReturn(1);

    $tier2 = $this->createMock(SubscriptionTierInterface::class);
    $tier2->method('id')->willReturn('t2');
    $tier2->method('label')->willReturn('Tier 2');
    $tier2->method('getPrice')->willReturn('10.00');
    $tier2->method('getCurrency')->willReturn('EUR');
    $tier2->method('getBillingPeriod')->willReturn('monthly');
    $tier2->method('getDescription')->willReturn('desc2');
    $tier2->method('getBenefits')->willReturn(['b2']);
    $tier2->method('getBadgeLabel')->willReturn('T2');
    $tier2->method('getWeight')->willReturn(0);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('loadByProperties')
      ->with(['group' => 'general_support', 'status' => TRUE])
      ->willReturn(['t1' => $tier1, 't2' => $tier2]);

    $this->entityTypeManager->method('getStorage')
      ->with('subscription_tier')
      ->willReturn($storage);

    $result = $this->manager->getTiersByGroup('general_support');

    $this->assertCount(2, $result);
    // Sorted by weight: t2 (0) before t1 (1).
    $this->assertEquals('t2', $result[0]['id']);
    $this->assertEquals('t1', $result[1]['id']);
  }

}
