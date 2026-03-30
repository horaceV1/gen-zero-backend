<?php

namespace Drupal\Tests\gen_zero_subscriptions\Unit;

use Drupal\gen_zero_subscriptions\Plugin\SubscriptionPaymentGateway\ManualGateway;
use Drupal\gen_zero_subscriptions\Entity\SubscriptionTierInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Unit tests for ManualGateway payment plugin.
 *
 * @group gen_zero_subscriptions
 * @coversDefaultClass \Drupal\gen_zero_subscriptions\Plugin\SubscriptionPaymentGateway\ManualGateway
 */
class ManualGatewayTest extends UnitTestCase {

  protected ManualGateway $gateway;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->gateway = new ManualGateway(
      [],
      'manual',
      [
        'id' => 'manual',
        'label' => 'Manual / Offline',
        'description' => 'Test',
      ]
    );
  }

  /**
   * @covers ::createSubscription
   */
  public function testCreateSubscriptionReturnsSuccess(): void {
    $tier = $this->createMock(SubscriptionTierInterface::class);
    $tier->method('id')->willReturn('hopeful_acorn');
    $tier->method('label')->willReturn('Hopeful Acorn');
    $tier->method('getPrice')->willReturn('5.00');
    $tier->method('getCurrency')->willReturn('EUR');
    $tier->method('getBillingPeriod')->willReturn('monthly');

    $result = $this->gateway->createSubscription($tier, [], 'test@example.com');

    $this->assertTrue($result['success']);
    $this->assertNotEmpty($result['subscription_id']);
    $this->assertStringStartsWith('manual_', $result['subscription_id']);
    $this->assertArrayHasKey('data', $result);
    $this->assertEquals('hopeful_acorn', $result['data']['tier_id']);
    $this->assertEquals('5.00', $result['data']['price']);
    $this->assertEquals('EUR', $result['data']['currency']);
    $this->assertEquals('manual', $result['data']['gateway']);
  }

  /**
   * @covers ::createSubscription
   */
  public function testCreateSubscriptionGeneratesUniqueIds(): void {
    $tier = $this->createMock(SubscriptionTierInterface::class);
    $tier->method('id')->willReturn('test');
    $tier->method('label')->willReturn('Test');
    $tier->method('getPrice')->willReturn('10.00');
    $tier->method('getCurrency')->willReturn('EUR');
    $tier->method('getBillingPeriod')->willReturn('monthly');

    $result1 = $this->gateway->createSubscription($tier, []);
    $result2 = $this->gateway->createSubscription($tier, []);

    $this->assertNotEquals($result1['subscription_id'], $result2['subscription_id']);
  }

  /**
   * @covers ::cancelSubscription
   */
  public function testCancelSubscription(): void {
    $result = $this->gateway->cancelSubscription('manual_abc123');

    $this->assertTrue($result['success']);
    $this->assertNotEmpty($result['message']);
  }

  /**
   * @covers ::getSubscriptionStatus
   */
  public function testGetSubscriptionStatus(): void {
    $result = $this->gateway->getSubscriptionStatus('manual_abc123');

    $this->assertEquals('active', $result['status']);
  }

  /**
   * @covers ::getLabel
   */
  public function testGetLabel(): void {
    $this->assertEquals('Manual / Offline', $this->gateway->getLabel());
  }

}
