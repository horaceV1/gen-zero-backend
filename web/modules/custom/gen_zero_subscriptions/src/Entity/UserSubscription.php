<?php

namespace Drupal\gen_zero_subscriptions\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerTrait;

/**
 * Defines the User Subscription content entity.
 *
 * Tracks individual user subscriptions linked to a tier and payment gateway.
 *
 * @ContentEntityType(
 *   id = "user_subscription",
 *   label = @Translation("User Subscription"),
 *   label_collection = @Translation("User Subscriptions"),
 *   label_singular = @Translation("user subscription"),
 *   label_plural = @Translation("user subscriptions"),
 *   handlers = {
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "list_builder" = "Drupal\gen_zero_subscriptions\UserSubscriptionListBuilder",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "user_subscription",
 *   admin_permission = "administer subscriptions",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "collection" = "/admin/commerce/user-subscriptions",
 *   },
 * )
 */
class UserSubscription extends ContentEntityBase implements UserSubscriptionInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public function getTierId(): string {
    return $this->get('tier_id')->value ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function getGatewayId(): string {
    return $this->get('gateway_id')->value ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function getExternalId(): string {
    return $this->get('external_id')->value ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function getSubscriptionStatus(): string {
    return $this->get('subscription_status')->value ?? self::STATUS_PENDING;
  }

  /**
   * {@inheritdoc}
   */
  public function setSubscriptionStatus(string $status): static {
    $this->set('subscription_status', $status);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPrice(): string {
    return $this->get('price')->value ?? '0.00';
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrency(): string {
    return $this->get('currency')->value ?? 'EUR';
  }

  /**
   * {@inheritdoc}
   */
  public function getBillingPeriod(): string {
    return $this->get('billing_period')->value ?? 'monthly';
  }

  /**
   * {@inheritdoc}
   */
  public function getNextBillingDate(): ?int {
    return $this->get('next_billing_date')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setNextBillingDate(int $timestamp): static {
    $this->set('next_billing_date', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime(): int {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    $fields['tier_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Tier ID'))
      ->setDescription(t('The subscription tier machine name.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 128);

    $fields['gateway_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Gateway ID'))
      ->setDescription(t('The payment gateway plugin ID.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 128);

    $fields['external_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('External ID'))
      ->setDescription(t('The subscription ID from the payment provider.'))
      ->setSetting('max_length', 255);

    $fields['subscription_status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Status'))
      ->setDescription(t('The subscription status.'))
      ->setRequired(TRUE)
      ->setDefaultValue(self::STATUS_PENDING)
      ->setSetting('allowed_values', [
        self::STATUS_PENDING => 'Pending',
        self::STATUS_ACTIVE => 'Active',
        self::STATUS_PAUSED => 'Paused',
        self::STATUS_CANCELLED => 'Cancelled',
        self::STATUS_EXPIRED => 'Expired',
      ]);

    $fields['price'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Price'))
      ->setDescription(t('The price at time of subscription.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 32);

    $fields['currency'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Currency'))
      ->setDescription(t('The currency code.'))
      ->setRequired(TRUE)
      ->setDefaultValue('EUR')
      ->setSetting('max_length', 3);

    $fields['billing_period'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Billing Period'))
      ->setDescription(t('The billing period (monthly, quarterly, yearly).'))
      ->setRequired(TRUE)
      ->setDefaultValue('monthly')
      ->setSetting('max_length', 32);

    $fields['next_billing_date'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Next Billing Date'))
      ->setDescription(t('Timestamp of the next billing cycle.'));

    $fields['email'] = BaseFieldDefinition::create('email')
      ->setLabel(t('Subscriber Email'))
      ->setDescription(t('The subscriber email address.'));

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('When the subscription was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('When the subscription was last updated.'));

    return $fields;
  }

}
