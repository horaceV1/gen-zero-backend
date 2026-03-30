<?php

namespace Drupal\Tests\gen_zero_subscriptions\Unit;

use Drupal\gen_zero_subscriptions\Plugin\rest\resource\SubscriptionResource;
use Drupal\gen_zero_subscriptions\SubscriptionManager;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Unit tests for SubscriptionResource REST plugin.
 *
 * @group gen_zero_subscriptions
 * @coversDefaultClass \Drupal\gen_zero_subscriptions\Plugin\rest\resource\SubscriptionResource
 */
class SubscriptionResourceTest extends UnitTestCase {

  protected SubscriptionManager $subscriptionManager;
  protected SubscriptionResource $resource;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->subscriptionManager = $this->createMock(SubscriptionManager::class);
    $logger = $this->createMock(LoggerInterface::class);

    $serializer_formats = ['json'];
    $plugin_definition = [
      'id' => 'subscription_resource',
      'plugin_id' => 'subscription_resource',
    ];

    $this->resource = new SubscriptionResource(
      [],
      'subscription_resource',
      $plugin_definition,
      $serializer_formats,
      $logger,
    );

    // Inject the subscription manager via reflection since we can't use
    // the container in unit tests.
    $ref = new \ReflectionProperty($this->resource, 'subscriptionManager');
    $ref->setAccessible(TRUE);
    $ref->setValue($this->resource, $this->subscriptionManager);
  }

  /**
   * @covers ::post
   */
  public function testPostMissingTierId(): void {
    $request = Request::create('/api/subscriptions', 'POST', [], [], [], [], json_encode([]));

    $response = $this->resource->post($request);

    $this->assertEquals(400, $response->getStatusCode());
  }

  /**
   * @covers ::post
   */
  public function testPostInvalidEmail(): void {
    $request = Request::create('/api/subscriptions', 'POST', [], [], [], [], json_encode([
      'tier_id' => 'hopeful_acorn',
      'email' => 'not-an-email',
    ]));

    $response = $this->resource->post($request);

    $this->assertEquals(400, $response->getStatusCode());
  }

  /**
   * @covers ::post
   */
  public function testPostSubscriptionFailure(): void {
    $this->subscriptionManager->method('createSubscription')
      ->willReturn(['success' => FALSE, 'message' => 'Tier not found']);

    $request = Request::create('/api/subscriptions', 'POST', [], [], [], [], json_encode([
      'tier_id' => 'nonexistent',
      'gateway' => 'manual',
    ]));

    $response = $this->resource->post($request);

    $this->assertEquals(422, $response->getStatusCode());
  }

  /**
   * @covers ::post
   */
  public function testPostSubscriptionSuccess(): void {
    $this->subscriptionManager->method('createSubscription')
      ->with('hopeful_acorn', 'manual', [], 'test@example.com')
      ->willReturn([
        'success' => TRUE,
        'subscription_id' => 1,
        'external_id' => 'ext_123',
      ]);

    $request = Request::create('/api/subscriptions', 'POST', [], [], [], [], json_encode([
      'tier_id' => 'hopeful_acorn',
      'gateway' => 'manual',
      'email' => 'test@example.com',
    ]));

    $response = $this->resource->post($request);

    $this->assertEquals(201, $response->getStatusCode());
  }

  /**
   * @covers ::post
   */
  public function testPostDefaultsToManualGateway(): void {
    $this->subscriptionManager->expects($this->once())
      ->method('createSubscription')
      ->with('hopeful_acorn', 'manual', [], NULL)
      ->willReturn(['success' => TRUE, 'subscription_id' => 1]);

    $request = Request::create('/api/subscriptions', 'POST', [], [], [], [], json_encode([
      'tier_id' => 'hopeful_acorn',
    ]));

    $this->resource->post($request);
  }

  /**
   * @covers ::post
   */
  public function testPostWithPaymentData(): void {
    $paymentData = ['token' => 'tok_test123'];

    $this->subscriptionManager->expects($this->once())
      ->method('createSubscription')
      ->with('hopeful_acorn', 'stripe', $paymentData, NULL)
      ->willReturn(['success' => TRUE, 'subscription_id' => 1]);

    $request = Request::create('/api/subscriptions', 'POST', [], [], [], [], json_encode([
      'tier_id' => 'hopeful_acorn',
      'gateway' => 'stripe',
      'payment_data' => $paymentData,
    ]));

    $this->resource->post($request);
  }

}
