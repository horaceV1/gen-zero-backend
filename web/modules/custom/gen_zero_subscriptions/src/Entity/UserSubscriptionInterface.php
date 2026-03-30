<?php

namespace Drupal\gen_zero_subscriptions\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Interface for User Subscription content entities.
 */
interface UserSubscriptionInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  const STATUS_ACTIVE = 'active';
  const STATUS_PAUSED = 'paused';
  const STATUS_CANCELLED = 'cancelled';
  const STATUS_EXPIRED = 'expired';
  const STATUS_PENDING = 'pending';

  /**
   * Gets the subscription tier ID.
   */
  public function getTierId(): string;

  /**
   * Gets the payment gateway plugin ID.
   */
  public function getGatewayId(): string;

  /**
   * Gets the external subscription ID from the payment provider.
   */
  public function getExternalId(): string;

  /**
   * Gets the subscription status.
   */
  public function getSubscriptionStatus(): string;

  /**
   * Sets the subscription status.
   */
  public function setSubscriptionStatus(string $status): static;

  /**
   * Gets the price at time of subscription.
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
   * Gets the next billing date.
   */
  public function getNextBillingDate(): ?int;

  /**
   * Sets the next billing date.
   */
  public function setNextBillingDate(int $timestamp): static;

  /**
   * Gets the creation timestamp.
   */
  public function getCreatedTime(): int;

}
