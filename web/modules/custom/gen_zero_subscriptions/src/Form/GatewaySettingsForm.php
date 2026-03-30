<?php

namespace Drupal\gen_zero_subscriptions\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\gen_zero_subscriptions\SubscriptionPaymentGatewayManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configuration form for payment gateway settings.
 */
class GatewaySettingsForm extends ConfigFormBase {

  public function __construct(
    protected SubscriptionPaymentGatewayManager $gatewayManager,
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $instance = new static(
      $container->get('plugin.manager.subscription_payment_gateway'),
      $container->get('entity_type.manager'),
    );
    $instance->setConfigFactory($container->get('config.factory'));
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'gen_zero_subscriptions_gateway_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['gen_zero_subscriptions.gateway_settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('gen_zero_subscriptions.gateway_settings');

    // Load tiers for plan mapping selects.
    $tiers = $this->entityTypeManager->getStorage('subscription_tier')->loadMultiple();
    $tierOptions = [];
    foreach ($tiers as $tier) {
      $tierOptions[$tier->id()] = $tier->label();
    }

    // --- PayPal ---
    $form['paypal'] = [
      '#type' => 'details',
      '#title' => $this->t('PayPal'),
      '#open' => TRUE,
    ];

    $form['paypal']['paypal_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('Mode'),
      '#options' => [
        'sandbox' => $this->t('Sandbox (test)'),
        'live' => $this->t('Live (production)'),
      ],
      '#default_value' => $config->get('paypal.mode') ?? 'sandbox',
    ];

    $form['paypal']['paypal_client_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client ID'),
      '#default_value' => $config->get('paypal.client_id') ?? '',
    ];

    $form['paypal']['paypal_client_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client Secret'),
      '#default_value' => $config->get('paypal.client_secret') ?? '',
    ];

    $form['paypal']['paypal_webhook_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Webhook ID'),
      '#description' => $this->t('PayPal webhook ID for signature verification. Leave empty to skip verification (not recommended for production).'),
      '#default_value' => $config->get('paypal.webhook_id') ?? '',
    ];

    $form['paypal']['paypal_return_url'] = [
      '#type' => 'url',
      '#title' => $this->t('Return URL'),
      '#description' => $this->t('Frontend URL to redirect after successful PayPal payment.'),
      '#default_value' => $config->get('paypal.return_url') ?? '',
    ];

    $form['paypal']['paypal_cancel_url'] = [
      '#type' => 'url',
      '#title' => $this->t('Cancel URL'),
      '#description' => $this->t('Frontend URL to redirect if user cancels on PayPal.'),
      '#default_value' => $config->get('paypal.cancel_url') ?? '',
    ];

    // PayPal plan mapping per tier.
    $form['paypal']['plan_mapping'] = [
      '#type' => 'details',
      '#title' => $this->t('Plan ID Mapping'),
      '#description' => $this->t('Map each subscription tier to a PayPal Plan ID. Create plans in your PayPal Dashboard > Subscriptions.'),
      '#open' => TRUE,
    ];

    $planMapping = $config->get('paypal.plan_mapping') ?? [];
    foreach ($tierOptions as $tierId => $tierLabel) {
      $form['paypal']['plan_mapping']['paypal_plan_' . $tierId] = [
        '#type' => 'textfield',
        '#title' => $tierLabel,
        '#default_value' => $planMapping[$tierId] ?? '',
        '#placeholder' => 'P-xxxxxxxxxxxxx',
      ];
    }

    // --- EuPago ---
    $form['eupago'] = [
      '#type' => 'details',
      '#title' => $this->t('EuPago'),
      '#open' => TRUE,
    ];

    $form['eupago']['eupago_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('Mode'),
      '#options' => [
        'demo' => $this->t('Demo (sandbox)'),
        'live' => $this->t('Live (production)'),
      ],
      '#default_value' => $config->get('eupago.mode') ?? 'demo',
    ];

    $form['eupago']['eupago_api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Key'),
      '#default_value' => $config->get('eupago.api_key') ?? '',
    ];

    $form['eupago']['eupago_channel'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Channel'),
      '#description' => $this->t('EuPago channel identifier (optional).'),
      '#default_value' => $config->get('eupago.channel') ?? '',
    ];

    $form['eupago']['eupago_return_url'] = [
      '#type' => 'url',
      '#title' => $this->t('Return URL (Credit Card)'),
      '#description' => $this->t('Frontend URL for credit card payment redirect return.'),
      '#default_value' => $config->get('eupago.return_url') ?? '',
    ];

    $form['eupago']['eupago_multibanco_deadline_days'] = [
      '#type' => 'number',
      '#title' => $this->t('Multibanco deadline (days)'),
      '#description' => $this->t('Number of days the Multibanco reference is valid. Leave empty for no deadline.'),
      '#default_value' => $config->get('eupago.multibanco_deadline_days') ?? '',
      '#min' => 1,
      '#max' => 90,
    ];

    $form['eupago']['eupago_allowed_methods'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Allowed payment methods'),
      '#options' => [
        'multibanco' => $this->t('Multibanco (ATM reference)'),
        'mbway' => $this->t('MB WAY (phone payment)'),
        'cc' => $this->t('Credit Card (redirect)'),
      ],
      '#default_value' => $config->get('eupago.allowed_methods') ?? ['multibanco', 'mbway'],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $config = $this->config('gen_zero_subscriptions.gateway_settings');

    // Build PayPal plan mapping from form values.
    $tiers = $this->entityTypeManager->getStorage('subscription_tier')->loadMultiple();
    $planMapping = [];
    foreach ($tiers as $tier) {
      $value = $form_state->getValue('paypal_plan_' . $tier->id());
      if (!empty($value)) {
        $planMapping[$tier->id()] = $value;
      }
    }

    $config
      ->set('paypal.mode', $form_state->getValue('paypal_mode'))
      ->set('paypal.client_id', $form_state->getValue('paypal_client_id'))
      ->set('paypal.client_secret', $form_state->getValue('paypal_client_secret'))
      ->set('paypal.webhook_id', $form_state->getValue('paypal_webhook_id'))
      ->set('paypal.return_url', $form_state->getValue('paypal_return_url'))
      ->set('paypal.cancel_url', $form_state->getValue('paypal_cancel_url'))
      ->set('paypal.plan_mapping', $planMapping)
      ->set('eupago.mode', $form_state->getValue('eupago_mode'))
      ->set('eupago.api_key', $form_state->getValue('eupago_api_key'))
      ->set('eupago.channel', $form_state->getValue('eupago_channel'))
      ->set('eupago.return_url', $form_state->getValue('eupago_return_url'))
      ->set('eupago.multibanco_deadline_days', $form_state->getValue('eupago_multibanco_deadline_days'))
      ->set('eupago.allowed_methods', array_values(array_filter($form_state->getValue('eupago_allowed_methods'))))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
