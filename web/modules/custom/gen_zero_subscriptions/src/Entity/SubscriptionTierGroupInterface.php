<?php

namespace Drupal\gen_zero_subscriptions\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Interface for Subscription Tier Group config entities.
 */
interface SubscriptionTierGroupInterface extends ConfigEntityInterface {

  /**
   * Gets the group description.
   */
  public function getDescription(): string;

  /**
   * Gets the weight.
   */
  public function getWeight(): int;

}
