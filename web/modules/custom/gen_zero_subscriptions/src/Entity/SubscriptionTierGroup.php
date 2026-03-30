<?php

namespace Drupal\gen_zero_subscriptions\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Subscription Tier Group config entity.
 *
 * @ConfigEntityType(
 *   id = "subscription_tier_group",
 *   label = @Translation("Subscription Tier Group"),
 *   label_collection = @Translation("Subscription Tier Groups"),
 *   label_singular = @Translation("subscription tier group"),
 *   label_plural = @Translation("subscription tier groups"),
 *   handlers = {
 *     "list_builder" = "Drupal\gen_zero_subscriptions\SubscriptionTierGroupListBuilder",
 *     "form" = {
 *       "add" = "Drupal\gen_zero_subscriptions\Form\SubscriptionTierGroupForm",
 *       "edit" = "Drupal\gen_zero_subscriptions\Form\SubscriptionTierGroupForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "tier_group",
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
 *     "description",
 *     "weight",
 *     "status",
 *   },
 *   links = {
 *     "add-form" = "/admin/commerce/subscription-tier-groups/add",
 *     "edit-form" = "/admin/commerce/subscription-tier-groups/{subscription_tier_group}/edit",
 *     "delete-form" = "/admin/commerce/subscription-tier-groups/{subscription_tier_group}/delete",
 *     "collection" = "/admin/commerce/subscription-tier-groups",
 *   },
 * )
 */
class SubscriptionTierGroup extends ConfigEntityBase implements SubscriptionTierGroupInterface {

  /**
   * The tier group machine name.
   */
  protected string $id;

  /**
   * The tier group label.
   */
  protected string $label;

  /**
   * The tier group description.
   */
  protected string $description = '';

  /**
   * The weight for sorting.
   */
  protected int $weight = 0;

  /**
   * {@inheritdoc}
   */
  public function getDescription(): string {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight(): int {
    return $this->weight;
  }

}
