<?php

namespace Drupal\gen_zero_subscriptions;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\gen_zero_subscriptions\Annotation\SubscriptionPaymentGateway;
use Drupal\gen_zero_subscriptions\Plugin\SubscriptionPaymentGateway\SubscriptionPaymentGatewayInterface;

/**
 * Plugin manager for subscription payment gateways.
 *
 * To add a new gateway, create a class in
 * Plugin/SubscriptionPaymentGateway/ that implements
 * SubscriptionPaymentGatewayInterface and uses the
 * @SubscriptionPaymentGateway annotation.
 */
class SubscriptionPaymentGatewayManager extends DefaultPluginManager {

  /**
   * {@inheritdoc}
   */
  public function __construct(
    \Traversable $namespaces,
    CacheBackendInterface $cache_backend,
    ModuleHandlerInterface $module_handler,
  ) {
    parent::__construct(
      'Plugin/SubscriptionPaymentGateway',
      $namespaces,
      $module_handler,
      SubscriptionPaymentGatewayInterface::class,
      SubscriptionPaymentGateway::class,
    );
    $this->alterInfo('subscription_payment_gateway_info');
    $this->setCacheBackend($cache_backend, 'subscription_payment_gateway_plugins');
  }

}
