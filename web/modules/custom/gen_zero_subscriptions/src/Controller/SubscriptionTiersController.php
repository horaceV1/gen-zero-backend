<?php

namespace Drupal\gen_zero_subscriptions\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\gen_zero_subscriptions\SubscriptionManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for the subscription tiers API endpoint.
 */
class SubscriptionTiersController extends ControllerBase {

  public function __construct(
    protected SubscriptionManager $subscriptionManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('gen_zero_subscriptions.subscription_manager'),
    );
  }

  /**
   * Returns all active tier groups with their tiers.
   *
   * GET /api/subscriptions/tiers
   * Optional query: ?group=general_support
   *
   * Response includes per-tier:
   *   - product_variation_id: Commerce variation to POST to /cart/add
   *   - user_subscription: null or {id, status, next_billing_date} if user
   *     has an existing subscription for that tier.
   */
  public function getTiers(Request $request): JsonResponse {
    $group = $request->query->get('group');

    if ($group) {
      $tiers = $this->subscriptionManager->getTiersByGroup($group);
      $tiers = $this->enrichTiersWithUserData($tiers);
      return new JsonResponse([
        'data' => $tiers,
        'available_gateways' => $this->subscriptionManager->getAvailableGatewaysForApi(),
        'user_authenticated' => $this->currentUser()->isAuthenticated(),
      ]);
    }

    $grouped = $this->subscriptionManager->getGroupedTiers();
    // Enrich each group's tiers.
    foreach ($grouped as &$groupData) {
      $groupData['tiers'] = $this->enrichTiersWithUserData($groupData['tiers']);
    }
    unset($groupData);

    return new JsonResponse([
      'data' => $grouped,
      'available_gateways' => $this->subscriptionManager->getAvailableGatewaysForApi(),
      'user_authenticated' => $this->currentUser()->isAuthenticated(),
    ]);
  }

  /**
   * Adds user subscription data to each tier.
   *
   * @param array $tiers
   *   Formatted tier data from SubscriptionManager.
   *
   * @return array
   *   Tiers enriched with user_subscription key.
   */
  protected function enrichTiersWithUserData(array $tiers): array {
    $uid = (int) $this->currentUser()->id();

    // Build a lookup of active/paused subscriptions by tier_id.
    $userSubsByTier = [];
    if ($uid > 0) {
      $allSubs = $this->subscriptionManager->getUserSubscriptions($uid);
      foreach ($allSubs as $sub) {
        $status = $sub['status'];
        // Only include active or paused — cancelled/expired are not relevant.
        if (in_array($status, ['active', 'paused'], TRUE)) {
          $userSubsByTier[$sub['tier_id']] = $sub;
        }
      }
    }

    foreach ($tiers as &$tier) {
      $tier['user_subscription'] = $userSubsByTier[$tier['id']] ?? NULL;
    }
    unset($tier);

    return $tiers;
  }

}
