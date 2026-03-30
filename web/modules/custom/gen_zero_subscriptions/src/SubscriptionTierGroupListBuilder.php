<?php

namespace Drupal\gen_zero_subscriptions;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * List builder for Subscription Tier Group entities.
 */
class SubscriptionTierGroupListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['label'] = $this->t('Name');
    $header['description'] = $this->t('Description');
    $header['status'] = $this->t('Status');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\gen_zero_subscriptions\Entity\SubscriptionTierGroupInterface $entity */
    $row['label'] = $entity->label();
    $row['description'] = $entity->getDescription();
    $row['status'] = $entity->status() ? $this->t('Active') : $this->t('Disabled');
    return $row + parent::buildRow($entity);
  }

}
