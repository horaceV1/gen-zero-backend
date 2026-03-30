<?php

namespace Drupal\gen_zero_subscriptions\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\gen_zero_subscriptions\SubscriptionManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Subscription Tiers' block.
 *
 * @Block(
 *   id = "gen_zero_subscription_tiers",
 *   admin_label = @Translation("Subscription Tiers"),
 *   category = @Translation("Gen Zero"),
 * )
 */
class SubscriptionTiersBlock extends BlockBase implements ContainerFactoryPluginInterface {

  public function __construct(
    array $configuration,
    string $plugin_id,
    mixed $plugin_definition,
    protected SubscriptionManager $subscriptionManager,
    protected AccountProxyInterface $currentUser,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('gen_zero_subscriptions.subscription_manager'),
      $container->get('current_user'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'tier_group' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state): array {
    $form = parent::blockForm($form, $form_state);

    $group_options = ['' => $this->t('— All groups —')];
    $groups = $this->subscriptionManager->getGroupedTiers();
    foreach ($groups as $group) {
      $group_options[$group['id']] = $group['label'];
    }

    $form['tier_group'] = [
      '#type' => 'select',
      '#title' => $this->t('Tier group'),
      '#description' => $this->t('Select which subscription tier group to display, or leave empty to show all groups.'),
      '#options' => $group_options,
      '#default_value' => $this->configuration['tier_group'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state): void {
    parent::blockSubmit($form, $form_state);
    $this->configuration['tier_group'] = $form_state->getValue('tier_group');
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $group_id = $this->configuration['tier_group'] ?? '';

    if ($group_id) {
      $groups = [
        [
          'id' => $group_id,
          'label' => $group_id,
          'description' => '',
          'tiers' => $this->subscriptionManager->getTiersByGroup($group_id),
        ],
      ];
    }
    else {
      $groups = $this->subscriptionManager->getGroupedTiers();
    }

    // Enrich tiers with current user's subscription status.
    $uid = (int) $this->currentUser->id();
    if ($uid > 0) {
      $user_subscriptions = $this->subscriptionManager->getUserSubscriptions($uid, 'active');
      $subscribed_tiers = [];
      foreach ($user_subscriptions as $sub) {
        $subscribed_tiers[$sub['tier_id']] = $sub['id'];
      }
      foreach ($groups as &$group) {
        foreach ($group['tiers'] as &$tier) {
          $tier['is_subscribed'] = isset($subscribed_tiers[$tier['id']]);
          $tier['user_subscription_id'] = $subscribed_tiers[$tier['id']] ?? NULL;
        }
      }
      unset($group, $tier);
    }

    $build = [
      '#theme' => 'gen_zero_subscription_tiers_block',
      '#groups' => $groups,
      '#cancel_base_url' => '/api/subscriptions',
      '#cache' => [
        'tags' => ['config:gen_zero_subscriptions.tier_group', 'config:gen_zero_subscriptions.tier'],
        'contexts' => ['languages', 'user'],
      ],
      '#attached' => [
        'library' => ['gen_zero_subscriptions/subscription_tiers_block'],
      ],
    ];

    return $build;
  }

}
