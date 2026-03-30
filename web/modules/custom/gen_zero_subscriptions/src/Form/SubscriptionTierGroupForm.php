<?php

namespace Drupal\gen_zero_subscriptions\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form for adding/editing Subscription Tier Groups.
 */
class SubscriptionTierGroupForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);
    /** @var \Drupal\gen_zero_subscriptions\Entity\SubscriptionTierGroupInterface $entity */
    $entity = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#maxlength' => 255,
      '#default_value' => $entity->label(),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $entity->id(),
      '#machine_name' => [
        'exists' => '\Drupal\gen_zero_subscriptions\Entity\SubscriptionTierGroup::load',
      ],
      '#disabled' => !$entity->isNew(),
    ];

    $form['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
      '#default_value' => $entity->getDescription(),
    ];

    $form['weight'] = [
      '#type' => 'number',
      '#title' => $this->t('Weight'),
      '#default_value' => $entity->getWeight(),
    ];

    $form['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Active'),
      '#default_value' => $entity->status(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);

    $message_args = ['%label' => $this->entity->label()];
    if ($result === SAVED_NEW) {
      $this->messenger()->addStatus($this->t('Tier group %label has been created.', $message_args));
    }
    else {
      $this->messenger()->addStatus($this->t('Tier group %label has been updated.', $message_args));
    }

    $form_state->setRedirectUrl($this->entity->toUrl('collection'));
    return $result;
  }

}
