<?php

namespace Drupal\gen_zero_subscriptions\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Interface for Subscription Tier config entities.
 */
interface SubscriptionTierInterface extends ConfigEntityInterface {

  /**
   * Gets the tier group ID.
   */
  public function getGroup(): string;

  /**
   * Gets the tier price.
   */
  public function getPrice(): string;

  /**
   * Gets the currency code.
   */
  public function getCurrency(): string;

  /**
   * Gets the billing period.
   */
  public function getBillingPeriod(): string;

  /**
   * Gets the tier description.
   */
  public function getDescription(): string;

  /**
   * Gets the tier benefits.
   *
   * @return string[]
   */
  public function getBenefits(): array;

  /**
   * Gets the badge label.
   */
  public function getBadgeLabel(): string;

  /**
   * Gets the weight.
   */
  public function getWeight(): int;

  /**
   * Gets the Commerce product variation ID for purchasing this tier.
   */
  public function getProductVariationId(): ?int;

}
