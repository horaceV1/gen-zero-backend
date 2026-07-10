<?php

declare(strict_types=1);

namespace Drupal\gen_zero_eupago;

use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;

/**
 * Thin wrapper around the EuPago REST API (ApiKey auth, v1.02).
 *
 * Endpoints used:
 *   - /api/v1.02/multibanco/create
 *   - /api/v1.02/mbway/create
 *   - /api/v1.02/creditcard/create
 *
 * Authentication: the EuPago channel API key is sent in the request header
 * as `Authorization: ApiKey xxxx-xxxx-xxxx-xxxx-xxxx` (NOT as a `chave` body
 * parameter — that is the deprecated body-auth API and rejects channel keys
 * with an "invalid API key" error).
 *
 * The API key is read from config `gen_zero_eupago.settings`.
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
   *
   * The ApiKey-auth Multibanco endpoint uses a FLAT payload (amount as a
   * top-level number), unlike MB WAY / Credit Card which nest under `payment`.
   */
  public function createMultibanco(string $internalId, float $amount): array {
    $settings = $this->getSettings();
    $payload = [
      'amount' => round($amount, 2),
      'identifier' => $internalId,
      // 0 = reference accepts a single payment only.
      'per_dup' => 0,
    ];
    if ($settings['multibanco_deadline_days'] > 0) {
      $payload['data_fim'] = date('Y-m-d', strtotime('+' . $settings['multibanco_deadline_days'] . ' days'));
    }
    return $this->post('/api/v1.02/multibanco/create', $payload);
  }

  /**
   * Creates an MB WAY payment request to the supplied phone alias.
   *
   * Phone may arrive as "351#912345678" or a bare local number.
   */
  public function createMbWay(string $internalId, float $amount, string $phone): array {
    $localPhone = $this->normalizePhone($phone);
    $payload = [
      'payment' => [
        'amount' => [
          'currency' => 'EUR',
          'value' => round($amount, 2),
        ],
        'identifier' => $internalId,
        // EuPago expects the ISO country code (e.g. 'PT'), not the dial code.
        'countryCode' => 'PT',
        'customerPhone' => $localPhone,
      ],
    ];
    return $this->post('/api/v1.02/mbway/create', $payload);
  }

  /**
   * Creates a credit-card payment URL (offsite redirect).
   */
  public function createCreditCard(string $internalId, float $amount, ?string $email, string $returnUrl): array {
    $payment = [
      'amount' => [
        'currency' => 'EUR',
        'value' => round($amount, 2),
      ],
      'identifier' => $internalId,
      'successUrl' => $returnUrl,
      'failUrl' => $returnUrl,
      'backUrl' => $returnUrl,
      'lang' => 'PT',
    ];
    $payload = ['payment' => $payment];
    if ($email) {
      $payload['customer'] = ['email' => $email, 'notify' => TRUE];
    }
    return $this->post('/api/v1.02/creditcard/create', $payload);
  }

  /**
   * Normalises a phone alias into a bare 9-digit local number.
   *
   * Accepts "351#912345678", "351912345678", "+351 912345678" or "912345678".
   */
  private function normalizePhone(string $phone): string {
    $phone = trim($phone);
    if (str_contains($phone, '#')) {
      [, $local] = explode('#', $phone, 2);
      $phone = $local;
    }
    $digits = preg_replace('/\D+/', '', $phone);
    // Strip a leading 351 country code if present.
    if (strlen($digits) > 9 && str_starts_with($digits, '351')) {
      $digits = substr($digits, 3);
    }
    return $digits;
  }

  /**
   * Looks up the status of a Multibanco reference.
   */
  public function lookupMultibanco(string $entity, string $reference, float $amount): array {
    $payload = [
      'reference' => [
        'entity' => $entity,
        'reference' => $reference,
        'amount' => ['currency' => 'EUR', 'value' => round($amount, 2)],
      ],
    ];
    return $this->post('/api/v1.02/reference/info', $payload);
  }

  /**
   * Internal POST helper.
   *
   * Sends the request with ApiKey header authentication and normalises the
   * EuPago v1.02 response into the legacy field names consumed by the
   * controller (`entidade`, `referencia`, `url`, `resposta`).
   */
  private function post(string $path, array $payload): array {
    $apiKey = trim((string) $this->getSettings()['api_key']);
    if ($apiKey === '') {
      return ['success' => FALSE, 'message' => 'EuPago API key is not configured.'];
    }

    $url = $this->getBaseUrl() . $path;
    try {
      $response = $this->httpClient->request('POST', $url, [
        'json' => $payload,
        'headers' => [
          'Accept' => 'application/json',
          'Content-Type' => 'application/json',
          'Authorization' => 'ApiKey ' . $apiKey,
        ],
        'timeout' => 20,
        // Do not throw on 4xx so we can surface EuPago's error message.
        'http_errors' => FALSE,
      ]);

      $statusCode = $response->getStatusCode();
      $body = (string) $response->getBody();
      $data = json_decode($body, TRUE);
      if (!is_array($data)) {
        $this->logger->error('EuPago: invalid JSON response (HTTP @code) from @url: @body', [
          '@code' => $statusCode, '@url' => $url, '@body' => mb_substr($body, 0, 500),
        ]);
        return ['success' => FALSE, 'message' => 'Invalid response from EuPago.'];
      }

      $transactionStatus = strtolower((string) ($data['transactionStatus'] ?? ''));
      $ok = $statusCode >= 200 && $statusCode < 300
        && (
          $transactionStatus === 'success'
          // Backwards-compatible with legacy body-auth responses.
          || ($data['sucesso'] ?? FALSE) === TRUE
          || (string) ($data['estado'] ?? '') === '0'
        );

      if (!$ok) {
        $msg = $data['text']
          ?? $data['reason']
          ?? $data['message']
          ?? $data['resposta']
          ?? 'EuPago returned an error.';
        $this->logger->warning('EuPago error on @path (HTTP @code): @msg | @raw', [
          '@path' => $path, '@code' => $statusCode, '@msg' => $msg, '@raw' => $body,
        ]);
        return ['success' => FALSE, 'message' => $msg, 'raw' => $data];
      }

      return ['success' => TRUE, 'raw' => $this->normalizeResponse($data)];
    }
    catch (GuzzleException $e) {
      $this->logger->error('EuPago request to @url failed: @msg', [
        '@url' => $url, '@msg' => $e->getMessage(),
      ]);
      return ['success' => FALSE, 'message' => 'Unable to reach EuPago: ' . $e->getMessage()];
    }
  }

  /**
   * Normalises a v1.02 response into the field names the controller expects.
   *
   * Live-verified responses:
   *   Multibanco: {transactionStatus, entity, reference, amount}
   *   MB WAY:     {transactionStatus, transactionID, reference}
   *   Credit Card:{transactionStatus, redirectUrl|url, transactionID}
   */
  private function normalizeResponse(array $data): array {
    // `reference` is a plain string on Multibanco; guard against array access.
    $referenceObj = is_array($data['reference'] ?? NULL) ? $data['reference'] : [];
    $referenceScalar = is_string($data['reference'] ?? NULL) ? $data['reference'] : NULL;
    return $data + [
      'entidade' => $data['entity'] ?? $referenceObj['entity'] ?? $data['entidade'] ?? NULL,
      'referencia' => $referenceScalar ?? $referenceObj['reference'] ?? $data['referencia'] ?? NULL,
      'url' => $data['redirectUrl'] ?? (is_string($data['url'] ?? NULL) ? $data['url'] : NULL),
      'per_dup' => $referenceObj['expirationDate'] ?? $data['expirationDate'] ?? $data['data_fim'] ?? NULL,
      'transaction_id' => $data['transactionID'] ?? NULL,
      'resposta' => $data['transactionStatus'] ?? $data['resposta'] ?? NULL,
    ];
  }

}
