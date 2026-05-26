<?php

declare(strict_types=1);

namespace Drupal\gen_zero_eupago;

use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;

/**
 * Thin wrapper around the EuPago REST API.
 *
 * Endpoints used (REST API v1.02):
 *   - /clientes/rest_api/multibanco/create
 *   - /clientes/rest_api/mbway/create
 *   - /clientes/rest_api/cc/create
 *   - /clientes/rest_api/multibanco/info  (status lookup)
 *
 * The EuPago key (`chave`) is read from config `gen_zero_eupago.settings`.
 */
final class EuPagoClient {

  public const SANDBOX_BASE_URL = 'https://sandbox.eupago.pt';
  public const LIVE_BASE_URL = 'https://clientes.eupago.pt';

  public function __construct(
    private readonly ClientInterface $httpClient,
    private readonly ConfigFactoryInterface $configFactory,
    private readonly LoggerInterface $logger,
  ) {}

  /**
   * Returns the integration settings.
   */
  public function getSettings(): array {
    $config = $this->configFactory->get('gen_zero_eupago.settings');
    return [
      'mode' => $config->get('mode') ?: 'sandbox',
      'api_key' => (string) $config->get('api_key'),
      'channel' => (string) ($config->get('channel') ?? ''),
      'multibanco_deadline_days' => (int) ($config->get('multibanco_deadline_days') ?? 0),
      'allowed_methods' => (array) ($config->get('allowed_methods') ?? ['multibanco', 'mbway', 'cc']),
      'notify_url' => (string) ($config->get('notify_url') ?? ''),
      'return_url' => (string) ($config->get('return_url') ?? ''),
      'payment_gateway_id' => (string) ($config->get('payment_gateway_id') ?? 'eupago'),
    ];
  }

  /**
   * Returns TRUE when the API key is configured.
   */
  public function isConfigured(): bool {
    $settings = $this->getSettings();
    return $settings['api_key'] !== '';
  }

  /**
   * Returns the API base URL according to the active mode.
   */
  public function getBaseUrl(): string {
    return ($this->getSettings()['mode'] === 'live')
      ? self::LIVE_BASE_URL
      : self::SANDBOX_BASE_URL;
  }

  /**
   * Creates a Multibanco reference (entidade + referencia).
   */
  public function createMultibanco(string $internalId, float $amount): array {
    $settings = $this->getSettings();
    $payload = [
      'chave' => $settings['api_key'],
      'valor' => number_format($amount, 2, '.', ''),
      'id' => $internalId,
    ];
    if ($settings['channel'] !== '') {
      $payload['canal'] = $settings['channel'];
    }
    if ($settings['multibanco_deadline_days'] > 0) {
      $payload['per_dup'] = date('Y-m-d', strtotime('+' . $settings['multibanco_deadline_days'] . ' days'));
    }
    return $this->post('/clientes/rest_api/multibanco/create', $payload);
  }

  /**
   * Creates an MB WAY payment request to the supplied phone alias.
   *
   * Phone format: "351#912345678".
   */
  public function createMbWay(string $internalId, float $amount, string $phone): array {
    $settings = $this->getSettings();
    $payload = [
      'chave' => $settings['api_key'],
      'valor' => number_format($amount, 2, '.', ''),
      'id' => $internalId,
      'alias' => $phone,
    ];
    if ($settings['channel'] !== '') {
      $payload['canal'] = $settings['channel'];
    }
    return $this->post('/clientes/rest_api/mbway/create', $payload);
  }

  /**
   * Creates a credit-card payment URL (offsite redirect).
   */
  public function createCreditCard(string $internalId, float $amount, ?string $email, string $returnUrl): array {
    $settings = $this->getSettings();
    $payload = [
      'chave' => $settings['api_key'],
      'valor' => number_format($amount, 2, '.', ''),
      'id' => $internalId,
      'url_retorno' => $returnUrl,
    ];
    if ($email) {
      $payload['email'] = $email;
    }
    if ($settings['channel'] !== '') {
      $payload['canal'] = $settings['channel'];
    }
    return $this->post('/clientes/rest_api/cc/create', $payload);
  }

  /**
   * Looks up the status of a Multibanco reference.
   */
  public function lookupMultibanco(string $entity, string $reference, float $amount): array {
    $settings = $this->getSettings();
    $payload = [
      'chave' => $settings['api_key'],
      'entidade' => $entity,
      'referencia' => $reference,
      'valor' => number_format($amount, 2, '.', ''),
    ];
    return $this->post('/clientes/rest_api/multibanco/info', $payload);
  }

  /**
   * Internal POST helper.
   */
  private function post(string $path, array $payload): array {
    if (empty($payload['chave'])) {
      return ['success' => FALSE, 'message' => 'EuPago API key is not configured.'];
    }

    $url = $this->getBaseUrl() . $path;
    try {
      $response = $this->httpClient->request('POST', $url, [
        'json' => $payload,
        'headers' => [
          'Accept' => 'application/json',
          'Content-Type' => 'application/json',
        ],
        'timeout' => 20,
      ]);

      $body = (string) $response->getBody();
      $data = json_decode($body, TRUE);
      if (!is_array($data)) {
        $this->logger->error('EuPago: invalid JSON response from @url: @body', [
          '@url' => $url, '@body' => mb_substr($body, 0, 500),
        ]);
        return ['success' => FALSE, 'message' => 'Invalid response from EuPago.'];
      }

      $ok = ($data['sucesso'] ?? FALSE) === TRUE || ($data['estado'] ?? '') === '0';
      if (!$ok) {
        $msg = $data['resposta'] ?? ($data['message'] ?? 'EuPago returned an error.');
        $this->logger->warning('EuPago error on @path: @msg | @raw', [
          '@path' => $path, '@msg' => $msg, '@raw' => $body,
        ]);
        return ['success' => FALSE, 'message' => $msg, 'raw' => $data];
      }

      return ['success' => TRUE, 'raw' => $data];
    }
    catch (GuzzleException $e) {
      $this->logger->error('EuPago request to @url failed: @msg', [
        '@url' => $url, '@msg' => $e->getMessage(),
      ]);
      return ['success' => FALSE, 'message' => 'Unable to reach EuPago: ' . $e->getMessage()];
    }
  }

}
