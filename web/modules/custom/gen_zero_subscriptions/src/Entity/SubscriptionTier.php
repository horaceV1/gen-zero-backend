<?php

namespace Drupal\gen_zero_subscriptions\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Subscription Tier config entity.
 *
 * @ConfigEntityType(
 *   id = "subscription_tier",
 *   label = @Translation("Subscription Tier"),
 *   label_collection = @Translation("Subscription Tiers"),
 *   label_singular = @Translation("subscription tier"),
 *   label_plural = @Translation("subscription tiers"),
 *   handlers = {
 *     "list_builder" = "Drupal\gen_zero_subscriptions\SubscriptionTierListBuilder",
 *     "form" = {
 *       "add" = "Drupal\gen_zero_subscriptions\Form\SubscriptionTierForm",
 *       "edit" = "Drupal\gen_zero_subscriptions\Form\SubscriptionTierForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "tier",
 *   admin_permission = "administer subscriptions",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "weight" = "weight",
 *     "status" = "status",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "group",
 *     "price",
 *     "currency",
 *     "billing_period",
 *     "description",
 *     "benefits",
 *     "badge_label",
 *     "product_variation_id",
 *     "weight",
 *     "status",
 *   },
 *   links = {
 *     "add-form" = "/admin/commerce/subscription-tiers/add",
 *     "edit-form" = "/admin/commerce/subscription-tiers/{subscription_tier}/edit",
 *     "delete-form" = "/admin/commerce/subscription-tiers/{subscription_tier}/delete",
 *     "collection" = "/admin/commerce/subscription-tiers",
 *   },
 * )
 */
class SubscriptionTier extends ConfigEntityBase implements SubscriptionTierInterface {

  /**
   * The tier machine name.
   */
  protected string $id;

  /**
   * The tier label.
   */
  protected string $label;

  /**
   * The tier group ID.
   */
  protected string $group = '';

  /**
   * The price amount.
   */
  protected string $price = '0.00';

  /**
   * The currency code.
   */
  protected string $currency = 'EUR';

  /**
   * The billing period (monthly, yearly, etc.).
   */
  protected string $billing_period = 'monthly';

  /**
   * The tier description.
   */
  protected string $description = '';

  /**
   * The list of benefits.
   *
   * @var string[]
   */
  protected array $benefits = [];

  /**
   * The badge label.
   */
  protected string $badge_label = '';

  /**
   * The Commerce product variation ID for purchasing this tier.
   */
  protected ?int $product_variation_id = NULL;

  /**
   * The weight for sorting.
   */
  protected int $weight = 0;

  /**
   * {@inheritdoc}
   */
  public function getGroup(): string {
    return $this->group;
  }

  /**
   * {@inheritdoc}
   */
  public function getPrice(): string {
    return $this->price;
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrency(): string {
    return $this->currency;
  }

  /**
   * {@inheritdoc}
   */
  public function getBillingPeriod(): string {
    return $this->billing_period;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): string {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function getBenefits(): array {
    return $this->benefits;
  }

  /**
   * {@inheritdoc}
   */
  public function getBadgeLabel(): string {
    return $this->badge_label;
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight(): int {
    return $this->weight;
  }

  /**
   * {@inheritdoc}
   */
  public function getProductVariationId(): ?int {
    return $this->product_variation_id;
  }

}
