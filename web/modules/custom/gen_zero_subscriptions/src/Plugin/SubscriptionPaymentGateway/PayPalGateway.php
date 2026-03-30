<?php

namespace Drupal\gen_zero_subscriptions\Plugin\SubscriptionPaymentGateway;

use Drupal\gen_zero_subscriptions\Entity\SubscriptionTierInterface;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\HttpFoundation\Request;

/**
 * PayPal Subscriptions API gateway.
 *
 * Uses PayPal's Subscriptions API v1 for recurring payments.
 * Requires a PayPal plan ID mapped to each tier in gateway settings.
 *
 * Admin setup:
 *  1. Create Products & Plans in PayPal Dashboard (or via API).
 *  2. Enter client_id, client_secret, and plan mappings at
 *     /admin/commerce/subscriptions/gateway-settings.
 *
 * @SubscriptionPaymentGateway(
 *   id = "paypal",
 *   label = @Translation("PayPal"),
 *   description = @Translation("Process recurring subscriptions via PayPal Subscriptions API."),
 * )
 */
class PayPalGateway extends SubscriptionPaymentGatewayBase {

  /**
   * {@inheritdoc}
   */
  public function requiresRedirect(): bool {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function createSubscription(SubscriptionTierInterface $tier, array $payment_data, ?string $email = NULL): array {
    $config = $this->getGatewayConfig();

    if (empty($config['client_id']) || empty($config['client_secret'])) {
      return ['success' => FALSE, 'message' => 'PayPal gateway is not configured.'];
    }

    $planId = $config['plan_mapping'][$tier->id()] ?? NULL;
    if (empty($planId)) {
      $this->logger->error('PayPal: No plan ID mapped for tier @tier', ['@tier' => $tier->id()]);
      return ['success' => FALSE, 'message' => 'PayPal plan not configured for this tier.'];
    }

    try {
      $accessToken = $this->getAccessToken($config);
    }
    catch (\Exception $e) {
      $this->logger->error('PayPal auth failed: @msg', ['@msg' => $e->getMessage()]);
      return ['success' => FALSE, 'message' => 'Payment service temporarily unavailable.'];
    }

    $returnUrl = $config['return_url'] ?? '';
    $cancelUrl = $config['cancel_url'] ?? '';

    $body = [
      'plan_id' => $planId,
      'application_context' => [
        'brand_name' => 'GenZero',
        'locale' => 'pt-PT',
        'user_action' => 'SUBSCRIBE_NOW',
        'return_url' => $returnUrl,
        'cancel_url' => $cancelUrl,
      ],
    ];

    if ($email) {
      $body['subscriber'] = ['email_address' => $email];
    }

    try {
      $response = $this->httpClient->request('POST', $this->getApiBaseUrl($config) . '/v1/billing/subscriptions', [
        'headers' => [
          'Authorization' => 'Bearer ' . $accessToken,
          'Content-Type' => 'application/json',
          'Accept' => 'application/json',
        ],
        'json' => $body,
      ]);

      $data = json_decode((string) $response->getBody(), TRUE);
      $subscriptionId = $data['id'] ?? '';

      // Find the approval URL from HATEOAS links.
      $approvalUrl = '';
      foreach ($data['links'] ?? [] as $link) {
        if ($link['rel'] === 'approve') {
          $approvalUrl = $link['href'];
          break;
        }
      }

      if (empty($approvalUrl)) {
        $this->logger->error('PayPal: No approval URL in response for tier @tier', ['@tier' => $tier->id()]);
        return ['success' => FALSE, 'message' => 'Could not initiate PayPal payment.'];
      }

      return [
        'success' => TRUE,
        'subscription_id' => $subscriptionId,
        'message' => 'Redirect to PayPal to complete subscription.',
        'initial_status' => 'pending',
        'redirect_url' => $approvalUrl,
        'data' => [
          'paypal_subscription_id' => $subscriptionId,
          'plan_id' => $planId,
        ],
      ];
    }
    catch (GuzzleException $e) {
      $this->logger->error('PayPal subscription creation failed: @msg', ['@msg' => $e->getMessage()]);
      return ['success' => FALSE, 'message' => 'Failed to create PayPal subscription.'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function cancelSubscription(string $subscription_id): array {
    $config = $this->getGatewayConfig();

    try {
      $accessToken = $this->getAccessToken($config);
      $this->httpClient->request('POST', $this->getApiBaseUrl($config) . '/v1/billing/subscriptions/' . $subscription_id . '/cancel', [
        'headers' => [
          'Authorization' => 'Bearer ' . $accessToken,
          'Content-Type' => 'application/json',
        ],
        'json' => ['reason' => 'User requested cancellation.'],
      ]);

      return ['success' => TRUE, 'message' => 'PayPal subscription cancelled.'];
    }
    catch (GuzzleException $e) {
      $this->logger->error('PayPal cancel failed: @msg', ['@msg' => $e->getMessage()]);
      return ['success' => FALSE, 'message' => 'Failed to cancel PayPal subscription.'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getSubscriptionStatus(string $subscription_id): array {
    $config = $this->getGatewayConfig();

    try {
      $accessToken = $this->getAccessToken($config);
      $response = $this->httpClient->request('GET', $this->getApiBaseUrl($config) . '/v1/billing/subscriptions/' . $subscription_id, [
        'headers' => [
          'Authorization' => 'Bearer ' . $accessToken,
          'Accept' => 'application/json',
        ],
      ]);

      $data = json_decode((string) $response->getBody(), TRUE);
      $status = strtolower($data['status'] ?? 'unknown');

      // Map PayPal statuses → our statuses.
      $statusMap = [
        'active' => 'active',
        'suspended' => 'paused',
        'cancelled' => 'cancelled',
        'expired' => 'expired',
        'approval_pending' => 'pending',
      ];

      return [
        'status' => $statusMap[$status] ?? 'unknown',
        'next_billing_date' => $data['billing_info']['next_billing_time'] ?? NULL,
      ];
    }
    catch (GuzzleException $e) {
      $this->logger->error('PayPal status check failed: @msg', ['@msg' => $e->getMessage()]);
      return ['status' => 'unknown', 'message' => 'Could not check PayPal status.'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function processWebhook(Request $request): array {
    $config = $this->getGatewayConfig();
    $body = json_decode($request->getContent(), TRUE);

    if (empty($body['event_type']) || empty($body['resource'])) {
      return ['success' => FALSE, 'message' => 'Invalid webhook payload.'];
    }

    // Verify webhook signature if webhook_id is configured.
    if (!empty($config['webhook_id'])) {
      if (!$this->verifyWebhookSignature($request, $config)) {
        $this->logger->warning('PayPal: Webhook signature verification failed.');
        return ['success' => FALSE, 'message' => 'Invalid webhook signature.'];
      }
    }

    $eventType = $body['event_type'];
    $resource = $body['resource'];
    $externalId = $resource['id'] ?? '';

    $this->logger->info('PayPal webhook: @event for @id', [
      '@event' => $eventType,
      '@id' => $externalId,
    ]);

    switch ($eventType) {
      case 'BILLING.SUBSCRIPTION.ACTIVATED':
        return [
          'success' => TRUE,
          'external_id' => $externalId,
          'new_status' => 'active',
          'message' => 'Subscription activated.',
        ];

      case 'BILLING.SUBSCRIPTION.CANCELLED':
        return [
          'success' => TRUE,
          'external_id' => $externalId,
          'new_status' => 'cancelled',
          'message' => 'Subscription cancelled.',
        ];

      case 'BILLING.SUBSCRIPTION.SUSPENDED':
        return [
          'success' => TRUE,
          'external_id' => $externalId,
          'new_status' => 'paused',
          'message' => 'Subscription suspended.',
        ];

      case 'BILLING.SUBSCRIPTION.EXPIRED':
        return [
          'success' => TRUE,
          'external_id' => $externalId,
          'new_status' => 'expired',
          'message' => 'Subscription expired.',
        ];

      case 'BILLING.SUBSCRIPTION.PAYMENT.FAILED':
        $this->logger->warning('PayPal: Payment failed for subscription @id', ['@id' => $externalId]);
        return [
          'success' => TRUE,
          'external_id' => $externalId,
          'new_status' => 'paused',
          'message' => 'Payment failed, subscription paused.',
        ];

      default:
        $this->logger->info('PayPal: Ignoring event @event', ['@event' => $eventType]);
        return ['success' => FALSE, 'message' => 'Unhandled event type.'];
    }
  }

  /**
   * Gets a PayPal OAuth2 access token.
   */
  protected function getAccessToken(array $config): string {
    $response = $this->httpClient->request('POST', $this->getApiBaseUrl($config) . '/v1/oauth2/token', [
      'auth' => [$config['client_id'], $config['client_secret']],
      'form_params' => ['grant_type' => 'client_credentials'],
      'headers' => ['Accept' => 'application/json'],
    ]);

    $data = json_decode((string) $response->getBody(), TRUE);

    if (empty($data['access_token'])) {
      throw new \RuntimeException('PayPal: No access_token in OAuth response.');
    }

    return $data['access_token'];
  }

  /**
   * Returns the PayPal API base URL based on mode.
   */
  protected function getApiBaseUrl(array $config): string {
    $mode = $config['mode'] ?? 'sandbox';
    return $mode === 'live'
      ? 'https://api-m.paypal.com'
      : 'https://api-m.sandbox.paypal.com';
  }

  /**
   * Verifies a PayPal webhook signature.
   */
  protected function verifyWebhookSignature(Request $request, array $config): bool {
    try {
      $accessToken = $this->getAccessToken($config);

      $verifyBody = [
        'auth_algo' => $request->headers->get('PAYPAL-AUTH-ALGO', ''),
        'cert_url' => $request->headers->get('PAYPAL-CERT-URL', ''),
        'transmission_id' => $request->headers->get('PAYPAL-TRANSMISSION-ID', ''),
        'transmission_sig' => $request->headers->get('PAYPAL-TRANSMISSION-SIG', ''),
        'transmission_time' => $request->headers->get('PAYPAL-TRANSMISSION-TIME', ''),
        'webhook_id' => $config['webhook_id'],
        'webhook_event' => json_decode($request->getContent(), TRUE),
      ];

      $response = $this->httpClient->request('POST', $this->getApiBaseUrl($config) . '/v1/notifications/verify-webhook-signature', [
        'headers' => [
          'Authorization' => 'Bearer ' . $accessToken,
          'Content-Type' => 'application/json',
        ],
        'json' => $verifyBody,
      ]);

      $result = json_decode((string) $response->getBody(), TRUE);
      return ($result['verification_status'] ?? '') === 'SUCCESS';
    }
    catch (\Exception $e) {
      $this->logger->error('PayPal signature verification error: @msg', ['@msg' => $e->getMessage()]);
      return FALSE;
    }
  }

}
