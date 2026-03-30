<?php

namespace Drupal\gen_zero_subscriptions\Plugin\SubscriptionPaymentGateway;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\gen_zero_subscriptions\Entity\SubscriptionTierInterface;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Base class for subscription payment gateways.
 *
 * Provides HTTP client, config access, and logging to all gateway plugins.
 */
abstract class SubscriptionPaymentGatewayBase extends PluginBase implements SubscriptionPaymentGatewayInterface, ContainerFactoryPluginInterface {

  protected LoggerInterface $logger;

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected ClientInterface $httpClient,
    protected ConfigFactoryInterface $configFactory,
    LoggerChannelFactoryInterface $loggerFactory,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->logger = $loggerFactory->get('gen_zero_subscriptions');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('http_client'),
      $container->get('config.factory'),
      $container->get('logger.factory'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(): string {
    return (string) $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function requiresRedirect(): bool {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  abstract public function createSubscription(SubscriptionTierInterface $tier, array $payment_data, ?string $email = NULL): array;

  /**
   * {@inheritdoc}
   */
  public function cancelSubscription(string $subscription_id): array {
    return ['success' => FALSE, 'message' => 'Cancel not implemented for this gateway.'];
  }

  /**
   * {@inheritdoc}
   */
  public function getSubscriptionStatus(string $subscription_id): array {
    return ['status' => 'unknown', 'message' => 'Status check not implemented for this gateway.'];
  }

  /**
   * {@inheritdoc}
   */
  public function processWebhook(Request $request): array {
    return ['success' => FALSE, 'message' => 'Webhook not implemented for this gateway.'];
  }

  /**
   * Returns this gateway's config from gen_zero_subscriptions.gateway_settings.
   */
  protected function getGatewayConfig(): array {
    return $this->configFactory
      ->get('gen_zero_subscriptions.gateway_settings')
      ->get($this->getPluginId()) ?? [];
  }

  /**
   * Whether this gateway is in live/production mode.
   */
  protected function isLiveMode(): bool {
    $config = $this->getGatewayConfig();
    $mode = $config['mode'] ?? 'sandbox';
    return $mode === 'live';
  }

}
