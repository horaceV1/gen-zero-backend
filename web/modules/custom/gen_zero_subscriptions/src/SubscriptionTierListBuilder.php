<?php

namespace Drupal\gen_zero_subscriptions;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * List builder for Subscription Tier entities.
 */
class SubscriptionTierListBuilder extends ConfigEntityListBuilder {

  /**
   * The tier group storage.
   */
  protected EntityStorageInterface $groupStorage;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type): static {
    $instance = parent::createInstance($container, $entity_type);
    $instance->groupStorage = $container->get('entity_type.manager')->getStorage('subscription_tier_group');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['label'] = $this->t('Name');
    $header['group'] = $this->t('Group');
    $header['price'] = $this->t('Price');
    $header['billing_period'] = $this->t('Billing Period');
    $header['status'] = $this->t('Status');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\gen_zero_subscriptions\Entity\SubscriptionTierInterface $entity */
    $group = $this->groupStorage->load($entity->getGroup());
    $row['label'] = $entity->label();
    $row['group'] = $group ? $group->label() : $entity->getGroup();
    $row['price'] = $entity->getPrice() . ' ' . $entity->getCurrency();
    $row['billing_period'] = ucfirst($entity->getBillingPeriod());
    $row['status'] = $entity->status() ? $this->t('Active') : $this->t('Disabled');
    return $row + parent::buildRow($entity);
  }

}
