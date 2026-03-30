<?php

namespace Drupal\Tests\gen_zero_subscriptions\Unit;

use Drupal\gen_zero_subscriptions\Entity\UserSubscriptionInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Unit tests for UserSubscription status constants.
 *
 * @group gen_zero_subscriptions
 */
class UserSubscriptionStatusTest extends UnitTestCase {

  /**
   * Verify all expected statuses are defined.
   */
  public function testStatusConstantsExist(): void {
    $this->assertEquals('active', UserSubscriptionInterface::STATUS_ACTIVE);
    $this->assertEquals('paused', UserSubscriptionInterface::STATUS_PAUSED);
    $this->assertEquals('cancelled', UserSubscriptionInterface::STATUS_CANCELLED);
    $this->assertEquals('expired', UserSubscriptionInterface::STATUS_EXPIRED);
    $this->assertEquals('pending', UserSubscriptionInterface::STATUS_PENDING);
  }

  /**
   * Verify all statuses are unique.
   */
  public function testStatusConstantsAreUnique(): void {
    $statuses = [
      UserSubscriptionInterface::STATUS_ACTIVE,
      UserSubscriptionInterface::STATUS_PAUSED,
      UserSubscriptionInterface::STATUS_CANCELLED,
      UserSubscriptionInterface::STATUS_EXPIRED,
      UserSubscriptionInterface::STATUS_PENDING,
    ];

    $this->assertCount(5, array_unique($statuses));
  }

}
