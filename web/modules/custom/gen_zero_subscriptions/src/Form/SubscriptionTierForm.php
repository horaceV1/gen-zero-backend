<?php

namespace Drupal\gen_zero_subscriptions\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for adding/editing Subscription Tiers.
 */
class SubscriptionTierForm extends EntityForm {

  /**
   * The tier group storage.
   */
  protected $groupStorage;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->groupStorage = $entity_type_manager->getStorage('subscription_tier_group');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);
    /** @var \Drupal\gen_zero_subscriptions\Entity\SubscriptionTierInterface $entity */
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
        'exists' => '\Drupal\gen_zero_subscriptions\Entity\SubscriptionTier::load',
      ],
      '#disabled' => !$entity->isNew(),
    ];

    // Build group options from existing tier groups.
    $groups = $this->groupStorage->loadMultiple();
    $group_options = [];
    foreach ($groups as $group) {
      $group_options[$group->id()] = $group->label();
    }

    $form['group'] = [
      '#type' => 'select',
      '#title' => $this->t('Tier Group'),
      '#options' => $group_options,
      '#default_value' => $entity->getGroup(),
      '#required' => TRUE,
      '#empty_option' => $this->t('- Select group -'),
    ];

    $form['pricing'] = [
      '#type' => 'details',
      '#title' => $this->t('Pricing'),
      '#open' => TRUE,
    ];

    $form['pricing']['price'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Price'),
      '#default_value' => $entity->getPrice(),
      '#required' => TRUE,
      '#size' => 10,
    ];

    $form['pricing']['currency'] = [
      '#type' => 'select',
      '#title' => $this->t('Currency'),
      '#options' => [
        'EUR' => $this->t('EUR (€)'),
        'USD' => $this->t('USD ($)'),
        'GBP' => $this->t('GBP (£)'),
      ],
      '#default_value' => $entity->getCurrency(),
      '#required' => TRUE,
    ];

    $form['pricing']['billing_period'] = [
      '#type' => 'select',
      '#title' => $this->t('Billing Period'),
      '#options' => [
        'monthly' => $this->t('Monthly'),
        'quarterly' => $this->t('Quarterly'),
        'yearly' => $this->t('Yearly'),
      ],
      '#default_value' => $entity->getBillingPeriod(),
      '#required' => TRUE,
    ];

    $form['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
      '#default_value' => $entity->getDescription(),
      '#description' => $this->t('Displayed to subscribers. Supports plain text.'),
    ];

    $form['benefits_wrapper'] = [
      '#type' => 'details',
      '#title' => $this->t('Benefits'),
      '#open' => TRUE,
    ];

    $benefits = $entity->getBenefits();
    $num_benefits = $form_state->get('num_benefits');
    if ($num_benefits === NULL) {
      $num_benefits = max(count($benefits), 1);
      $form_state->set('num_benefits', $num_benefits);
    }

    $form['benefits_wrapper']['benefits'] = [
      '#type' => 'container',
      '#prefix' => '<div id="benefits-wrapper">',
      '#suffix' => '</div>',
      '#tree' => TRUE,
    ];

    for ($i = 0; $i < $num_benefits; $i++) {
      $form['benefits_wrapper']['benefits'][$i] = [
        '#type' => 'textfield',
        '#title' => $this->t('Benefit @num', ['@num' => $i + 1]),
        '#default_value' => $benefits[$i] ?? '',
      ];
    }

    $form['benefits_wrapper']['add_benefit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add another benefit'),
      '#submit' => ['::addBenefitCallback'],
      '#ajax' => [
        'callback' => '::benefitsAjaxCallback',
        'wrapper' => 'benefits-wrapper',
      ],
      '#limit_validation_errors' => [],
    ];

    $form['badge_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Badge Label'),
      '#default_value' => $entity->getBadgeLabel(),
      '#description' => $this->t('A short label for the tier badge (e.g., "Starter", "Champion").'),
      '#maxlength' => 64,
    ];

    $form['product_variation_id'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Commerce Product Variation'),
      '#target_type' => 'commerce_product_variation',
      '#default_value' => $entity->getProductVariationId()
        ? \Drupal::entityTypeManager()->getStorage('commerce_product_variation')->load($entity->getProductVariationId())
        : NULL,
      '#description' => $this->t('Link this tier to a Commerce product variation so users can purchase the subscription. The product should be unpublished to stay hidden from the store.'),
    ];

    $form['weight'] = [
      '#type' => 'number',
      '#title' => $this->t('Weight'),
      '#default_value' => $entity->getWeight(),
      '#description' => $this->t('Tiers with lower weight appear first.'),
    ];

    $form['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Active'),
      '#default_value' => $entity->status(),
    ];

    return $form;
  }

  /**
   * Submit callback for adding a benefit line.
   */
  public function addBenefitCallback(array &$form, FormStateInterface $form_state): void {
    $num_benefits = $form_state->get('num_benefits');
    $form_state->set('num_benefits', $num_benefits + 1);
    $form_state->setRebuild();
  }

  /**
   * AJAX callback for the benefits wrapper.
   */
  public function benefitsAjaxCallback(array &$form, FormStateInterface $form_state): array {
    return $form['benefits_wrapper']['benefits'];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    parent::validateForm($form, $form_state);

    $price = $form_state->getValue(['pricing', 'price']) ?? $form_state->getValue('price');
    if ($price !== NULL && (!is_numeric($price) || (float) $price < 0)) {
      $form_state->setErrorByName('price', $this->t('Price must be a positive number.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    // Flatten pricing values to top-level entity properties.
    $pricing = $form_state->getValue('pricing');
    if (is_array($pricing)) {
      foreach ($pricing as $key => $value) {
        $form_state->setValue($key, $value);
      }
      $form_state->unsetValue('pricing');
    }

    // Clean up benefits: remove empty entries.
    $benefits = array_values(array_filter(
      $form_state->getValue('benefits') ?? [],
      fn($b) => trim($b) !== ''
    ));
    $form_state->setValue('benefits', $benefits);

    // Remove wrapper keys that aren't entity properties.
    $form_state->unsetValue('benefits_wrapper');
    $form_state->unsetValue('add_benefit');

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);

    $message_args = ['%label' => $this->entity->label()];
    if ($result === SAVED_NEW) {
      $this->messenger()->addStatus($this->t('Tier %label has been created.', $message_args));
    }
    else {
      $this->messenger()->addStatus($this->t('Tier %label has been updated.', $message_args));
    }

    $form_state->setRedirectUrl($this->entity->toUrl('collection'));
    return $result;
  }

}
