<?php

declare(strict_types=1);

namespace Drupal\gen_zero_eupago\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * EuPago integration configuration form.
 */
final class EuPagoSettingsForm extends ConfigFormBase {

  protected function getEditableConfigNames(): array {
    return ['gen_zero_eupago.settings'];
  }

  public function getFormId(): string {
    return 'gen_zero_eupago_settings';
  }

  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('gen_zero_eupago.settings');

    $form['mode'] = [
      '#type' => 'select',
      '#title' => $this->t('Mode'),
      '#options' => [
        'sandbox' => $this->t('Sandbox'),
        'live' => $this->t('Live'),
      ],
      '#default_value' => $config->get('mode') ?: 'sandbox',
    ];

    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Key (chave)'),
      '#description' => $this->t('EuPago account API key. Provided by EuPago in the backoffice.'),
      '#default_value' => $config->get('api_key'),
      '#required' => TRUE,
      '#size' => 60,
    ];

    $form['channel'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Channel (canal)'),
      '#description' => $this->t('Optional channel/branding code provided by EuPago.'),
      '#default_value' => $config->get('channel'),
    ];

    $form['multibanco_deadline_days'] = [
      '#type' => 'number',
      '#title' => $this->t('Multibanco reference validity (days)'),
      '#min' => 0,
      '#default_value' => $config->get('multibanco_deadline_days') ?? 7,
    ];

    $form['allowed_methods'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Allowed payment methods'),
      '#options' => [
        'multibanco' => $this->t('Multibanco'),
        'mbway' => $this->t('MB WAY'),
        'cc' => $this->t('Credit Card'),
      ],
      '#default_value' => $config->get('allowed_methods') ?? ['multibanco', 'mbway', 'cc'],
    ];

    $form['return_url'] = [
      '#type' => 'url',
      '#title' => $this->t('Credit-card return URL'),
      '#description' => $this->t('URL on the frontend that EuPago redirects the customer to after credit-card payment.'),
      '#default_value' => $config->get('return_url'),
    ];

    $form['notify_url'] = [
      '#type' => 'url',
      '#title' => $this->t('Notify (webhook) URL override'),
      '#description' => $this->t('Public URL of /api/eupago/notify. Leave blank to use the request-derived URL.'),
      '#default_value' => $config->get('notify_url'),
    ];

    $form['payment_gateway_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Commerce payment gateway ID'),
      '#description' => $this->t('Machine name of the commerce_payment_gateway entity used to record successful EuPago payments.'),
      '#default_value' => $config->get('payment_gateway_id') ?: 'eupago',
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $allowed = array_values(array_filter((array) $form_state->getValue('allowed_methods')));
    $this->config('gen_zero_eupago.settings')
      ->set('mode', $form_state->getValue('mode'))
      ->set('api_key', trim((string) $form_state->getValue('api_key')))
      ->set('channel', trim((string) $form_state->getValue('channel')))
      ->set('multibanco_deadline_days', (int) $form_state->getValue('multibanco_deadline_days'))
      ->set('allowed_methods', $allowed)
      ->set('return_url', trim((string) $form_state->getValue('return_url')))
      ->set('notify_url', trim((string) $form_state->getValue('notify_url')))
      ->set('payment_gateway_id', trim((string) $form_state->getValue('payment_gateway_id')))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
