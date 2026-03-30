<?php

namespace Drupal\gen_zero_donations;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_price\Price;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\node\NodeInterface;

/**
 * Service to manage donation operations.
 */
class DonationManager {

  /**
   * Allowed fixed donation amounts.
   */
  public const FIXED_AMOUNTS = ['5', '10', '25', '50', '100'];

  /**
   * Minimum custom donation amount.
   */
  public const MIN_AMOUNT = 1.0;

  /**
   * Maximum custom donation amount.
   */
  public const MAX_AMOUNT = 10000.0;

  /**
   * Default currency code.
   */
  public const DEFAULT_CURRENCY = 'EUR';

  /**
   * The entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The current user.
   */
  protected AccountProxyInterface $currentUser;

  /**
   * The logger channel.
   */
  protected $logger;

  /**
   * Constructs a DonationManager object.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    AccountProxyInterface $current_user,
    LoggerChannelFactoryInterface $logger_factory,
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
    $this->logger = $logger_factory->get('gen_zero_donations');
  }

  /**
   * Validates a donation amount.
   *
   * @param mixed $amount
   *   The amount to validate.
   *
   * @return array
   *   An array with 'valid' (bool) and 'message' (string) keys.
   */
  public function validateAmount(mixed $amount): array {
    if (empty($amount) && $amount !== 0 && $amount !== '0') {
      return ['valid' => FALSE, 'message' => 'Amount is required.'];
    }

    if (!is_numeric($amount)) {
      return ['valid' => FALSE, 'message' => 'Amount must be a number.'];
    }

    $numeric = (float) $amount;

    if ($numeric < self::MIN_AMOUNT) {
      return [
        'valid' => FALSE,
        'message' => sprintf('Amount must be at least €%.2f.', self::MIN_AMOUNT),
      ];
    }

    if ($numeric > self::MAX_AMOUNT) {
      return [
        'valid' => FALSE,
        'message' => sprintf('Amount cannot exceed €%.2f.', self::MAX_AMOUNT),
      ];
    }

    // Round to 2 decimal places.
    $rounded = round($numeric, 2);
    if ($rounded != $numeric) {
      return [
        'valid' => FALSE,
        'message' => 'Amount can have at most 2 decimal places.',
      ];
    }

    return ['valid' => TRUE, 'message' => ''];
  }

  /**
   * Loads and validates a projeto node.
   *
   * @param int $projeto_id
   *   The node ID.
   *
   * @return array
   *   An array with 'valid' (bool), 'message' (string), and 'node' keys.
   */
  public function validateProjeto(int $projeto_id): array {
    $node = $this->entityTypeManager->getStorage('node')->load($projeto_id);

    if (!$node instanceof NodeInterface) {
      return [
        'valid' => FALSE,
        'message' => 'Projeto not found.',
        'node' => NULL,
      ];
    }

    if ($node->bundle() !== 'projeto') {
      return [
        'valid' => FALSE,
        'message' => 'The specified content is not a projeto.',
        'node' => NULL,
      ];
    }

    if (!$node->isPublished()) {
      return [
        'valid' => FALSE,
        'message' => 'This projeto is not currently accepting donations.',
        'node' => NULL,
      ];
    }

    return ['valid' => TRUE, 'message' => '', 'node' => $node];
  }

  /**
   * Creates a donation order.
   *
   * @param string $amount
   *   The donation amount.
   * @param \Drupal\node\NodeInterface $projeto
   *   The projeto node.
   * @param string $currency
   *   The currency code.
   * @param string|null $email
   *   Optional email for anonymous/override.
   *
   * @return \Drupal\commerce_order\Entity\Order
   *   The created order.
   */
  public function createDonation(string $amount, NodeInterface $projeto, string $currency = 'EUR', ?string $email = NULL): Order {
    $price = new Price($amount, $currency);

    // Create the order item.
    $order_item = OrderItem::create([
      'type' => 'donation',
      'title' => sprintf('Donation to %s', $projeto->label()),
      'unit_price' => $price,
      'quantity' => 1,
    ]);
    $order_item->save();

    // Determine email.
    $order_email = $email;
    if (empty($order_email) && !$this->currentUser->isAnonymous()) {
      $user = $this->entityTypeManager->getStorage('user')->load($this->currentUser->id());
      $order_email = $user?->getEmail();
    }

    // Get default store.
    $store = $this->entityTypeManager->getStorage('commerce_store')->loadDefault();

    // Create the donation order.
    $order = Order::create([
      'type' => 'donation',
      'store_id' => $store?->id() ?? 1,
      'uid' => $this->currentUser->id(),
      'mail' => $order_email ?? '',
      'order_items' => [$order_item],
      'field_projeto' => ['target_id' => $projeto->id()],
      'state' => 'draft',
    ]);
    $order->save();

    $this->logger->info('Donation of @amount @currency created for projeto @projeto (order @order).', [
      '@amount' => $amount,
      '@currency' => $currency,
      '@projeto' => $projeto->label(),
      '@order' => $order->id(),
    ]);

    return $order;
  }

  /**
   * Gets available fixed amounts for the donation widget.
   *
   * @return array
   *   Array of fixed amount options.
   */
  public function getFixedAmounts(): array {
    return array_map(function ($amount) {
      return [
        'value' => $amount,
        'label' => '€' . $amount,
      ];
    }, self::FIXED_AMOUNTS);
  }

  /**
   * Gets the donation configuration for the frontend.
   *
   * @return array
   *   Configuration array.
   */
  public function getConfig(): array {
    return [
      'fixed_amounts' => $this->getFixedAmounts(),
      'min_amount' => self::MIN_AMOUNT,
      'max_amount' => self::MAX_AMOUNT,
      'currency' => self::DEFAULT_CURRENCY,
      'currency_symbol' => '€',
    ];
  }

}
