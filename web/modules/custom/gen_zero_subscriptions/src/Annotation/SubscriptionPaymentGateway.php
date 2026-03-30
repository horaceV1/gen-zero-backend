<?php

namespace Drupal\gen_zero_subscriptions\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a SubscriptionPaymentGateway plugin annotation.
 *
 * Plugin Namespace: Plugin\SubscriptionPaymentGateway
 *
 * @Annotation
 */
class SubscriptionPaymentGateway extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public string $id;

  /**
   * The human-readable label.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * A brief description of the gateway.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description = '';

}
