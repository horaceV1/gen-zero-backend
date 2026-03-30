<?php

namespace Drupal\gen_zero_subscriptions;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * List builder for User Subscription entities.
 */
class UserSubscriptionListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['id'] = $this->t('ID');
    $header['owner'] = $this->t('User');
    $header['tier'] = $this->t('Tier');
    $header['status'] = $this->t('Status');
    $header['gateway'] = $this->t('Gateway');
    $header['price'] = $this->t('Price');
    $header['created'] = $this->t('Created');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\gen_zero_subscriptions\Entity\UserSubscriptionInterface $entity */
    $row['id'] = $entity->id();
    $row['owner'] = $entity->getOwner() ? $entity->getOwner()->getDisplayName() : $this->t('Anonymous');
    $row['tier'] = $entity->getTierId();
    $row['status'] = ucfirst($entity->getSubscriptionStatus());
    $row['gateway'] = $entity->getGatewayId();
    $row['price'] = $entity->getPrice() . ' ' . $entity->getCurrency();
    $row['created'] = \Drupal::service('date.formatter')->format($entity->getCreatedTime(), 'short');
    return $row + parent::buildRow($entity);
  }

}
