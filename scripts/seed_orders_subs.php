<?php

/**
 * @file
 * Continuation seed: creates product orders + subscriptions.
 * Run after seed_comprehensive.php if it partially failed.
 *
 * Run with: ddev drush scr scripts/seed_orders_subs.php
 */

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_price\Price;
use Drupal\user\Entity\User;

$etm = \Drupal::entityTypeManager();
$store = $etm->getStorage('commerce_store')->loadDefault();
$store_id = $store ? $store->id() : 1;

// Gather all user IDs.
$all_users = $etm->getStorage('user')->loadMultiple();
$user_ids = [];
foreach ($all_users as $u) {
  if ((int) $u->id() > 0) {
    $user_ids[] = (int) $u->id();
  }
}

echo "=== ENCOMENDAS DE PRODUTOS ===\n";

// Load only EUR-priced physical variations.
$all_variation_ids = array_values(
  $etm->getStorage('commerce_product_variation')->getQuery()
    ->accessCheck(FALSE)
    ->condition('type', 'physical')
    ->execute()
);

// Pre-filter to only EUR variations.
$eur_variation_ids = [];
foreach ($all_variation_ids as $vid) {
  $v = $etm->getStorage('commerce_product_variation')->load($vid);
  if ($v && $v->getPrice() && $v->getPrice()->getCurrencyCode() === 'EUR') {
    $eur_variation_ids[] = (int) $vid;
  }
}
echo "  EUR variations available: " . count($eur_variation_ids) . "\n";

$default_states = ['draft', 'completed', 'completed', 'completed', 'completed'];
$order_count = 0;

for ($i = 0; $i < 40; $i++) {
  $uid = $user_ids[array_rand($user_ids)];
  $user = User::load($uid);
  $email = $user ? $user->getEmail() : "comprador{$i}@exemplo.com";
  $state = $default_states[array_rand($default_states)];

  $num_items = rand(1, 4);
  $order_items = [];

  for ($j = 0; $j < $num_items; $j++) {
    if (!empty($eur_variation_ids)) {
      $var_id = $eur_variation_ids[array_rand($eur_variation_ids)];
      $variation = $etm->getStorage('commerce_product_variation')->load($var_id);
      if ($variation && $variation->getPrice()) {
        $order_item = OrderItem::create([
          'type' => 'physical_product',
          'title' => $variation->getTitle() ?: 'Produto #' . $var_id,
          'purchased_entity' => $variation,
          'unit_price' => $variation->getPrice(),
          'quantity' => rand(1, 3),
        ]);
        $order_item->save();
        $order_items[] = $order_item;
      }
    }
  }

  if (empty($order_items)) {
    continue;
  }

  $days_back = rand(0, 540);
  $created_time = strtotime("-{$days_back} days") + rand(0, 86400);

  $order = Order::create([
    'type' => 'default',
    'store_id' => $store_id,
    'uid' => $uid,
    'mail' => $email,
    'order_items' => $order_items,
    'state' => $state,
    'created' => $created_time,
    'changed' => $created_time + rand(0, 3600),
  ]);

  if ($state === 'completed') {
    $order->setCompletedTime($created_time + rand(60, 7200));
  }

  $order->save();
  $order_count++;
}

echo "  Criadas {$order_count} encomendas de produtos.\n\n";

// ====================================================================
// SUBSCRIPTIONS
// ====================================================================
echo "=== SUBSCRIÇÕES ===\n";

$sub_storage = $etm->getStorage('user_subscription');
$tiers = $etm->getStorage('subscription_tier')->loadMultiple();
$active_tier_ids = [];
foreach ($tiers as $t) {
  if ($t->status() && (float) $t->getPrice() > 0) {
    $active_tier_ids[] = $t->id();
  }
}

$sub_count = 0;

if (!empty($active_tier_ids)) {
  foreach ($user_ids as $uid) {
    if ($uid == 0) {
      continue;
    }

    $user = User::load($uid);
    if (!$user) {
      continue;
    }
    $email = $user->getEmail();

    // Check if this user already has subscriptions.
    $existing_subs = $sub_storage->loadByProperties(['uid' => $uid]);
    if (!empty($existing_subs)) {
      continue; // Already has subs from previous seed.
    }

    // Assign a subscription story:
    // 40% → 1 active (loyal)
    // 20% → 1 active + 1 cancelled (upgraded)
    // 15% → 1 paused
    // 10% → 1 cancelled (churned)
    // 10% → 2 active (multi-tier)
    // 5% → none
    $roll = rand(1, 100);

    if ($roll <= 5) {
      continue;
    }

    $scenarios = [];
    if ($roll <= 45) {
      $tier_id = $active_tier_ids[array_rand($active_tier_ids)];
      $scenarios[] = ['tier' => $tier_id, 'status' => 'active', 'period' => 'monthly', 'days_ago' => rand(30, 300)];
    }
    elseif ($roll <= 65) {
      $tier_1 = $active_tier_ids[array_rand($active_tier_ids)];
      $tier_2 = $active_tier_ids[array_rand($active_tier_ids)];
      $scenarios[] = ['tier' => $tier_1, 'status' => 'cancelled', 'period' => 'monthly', 'days_ago' => rand(180, 500)];
      $scenarios[] = ['tier' => $tier_2, 'status' => 'active', 'period' => 'monthly', 'days_ago' => rand(10, 150)];
    }
    elseif ($roll <= 80) {
      $tier_id = $active_tier_ids[array_rand($active_tier_ids)];
      $scenarios[] = ['tier' => $tier_id, 'status' => 'paused', 'period' => 'monthly', 'days_ago' => rand(30, 200)];
    }
    elseif ($roll <= 90) {
      $tier_id = $active_tier_ids[array_rand($active_tier_ids)];
      $scenarios[] = ['tier' => $tier_id, 'status' => 'cancelled', 'period' => 'monthly', 'days_ago' => rand(60, 400)];
    }
    else {
      $shuffled = $active_tier_ids;
      shuffle($shuffled);
      $scenarios[] = ['tier' => $shuffled[0], 'status' => 'active', 'period' => 'monthly', 'days_ago' => rand(60, 300)];
      if (isset($shuffled[1])) {
        $periods = ['monthly', 'quarterly', 'yearly'];
        $scenarios[] = ['tier' => $shuffled[1], 'status' => 'active', 'period' => $periods[array_rand($periods)], 'days_ago' => rand(30, 200)];
      }
    }

    foreach ($scenarios as $sc) {
      $tier = $tiers[$sc['tier']];
      $created_time = strtotime("-{$sc['days_ago']} days");

      $base_price = (float) $tier->getPrice();
      $price = match ($sc['period']) {
        'quarterly' => number_format($base_price * 3 * 0.9, 2, '.', ''),
        'yearly' => number_format($base_price * 12 * 0.8, 2, '.', ''),
        default => number_format($base_price, 2, '.', ''),
      };

      if ($sc['status'] === 'active') {
        $next_billing = match ($sc['period']) {
          'monthly' => strtotime('+1 month'),
          'quarterly' => strtotime('+' . rand(1, 90) . ' days'),
          'yearly' => strtotime('+' . rand(1, 365) . ' days'),
          default => strtotime('+1 month'),
        };
      }
      else {
        $next_billing = 0;
      }

      $subscription = $sub_storage->create([
        'tier_id' => $sc['tier'],
        'gateway_id' => 'manual',
        'external_id' => 'seed_' . $uid . '_' . $sc['tier'] . '_' . rand(1000, 9999),
        'subscription_status' => $sc['status'],
        'price' => $price,
        'currency' => $tier->getCurrency(),
        'billing_period' => $sc['period'],
        'next_billing_date' => $next_billing,
        'email' => $email,
        'uid' => $uid,
        'created' => $created_time,
        'changed' => $created_time,
      ]);
      $subscription->save();
      $sub_count++;
    }
  }
}

echo "  Criadas {$sub_count} subscrições.\n\n";

// ====================================================================
// FINAL SUMMARY
// ====================================================================
echo "╔══════════════════════════════════════════════╗\n";
echo "║       RESUMO FINAL — TODA A BASE DE DADOS   ║\n";
echo "╠══════════════════════════════════════════════╣\n";

$counts = [
  'Utilizadores' => $etm->getStorage('user')->getQuery()->accessCheck(FALSE)->condition('uid', 0, '>')->count()->execute(),
  'Projetos (projeto)' => $etm->getStorage('node')->getQuery()->accessCheck(FALSE)->condition('type', 'projeto')->count()->execute(),
  'Produtos' => $etm->getStorage('commerce_product')->getQuery()->accessCheck(FALSE)->count()->execute(),
  'Encomendas (total)' => $etm->getStorage('commerce_order')->getQuery()->accessCheck(FALSE)->count()->execute(),
  'Subscrições' => $etm->getStorage('user_subscription')->getQuery()->accessCheck(FALSE)->count()->execute(),
];

foreach ($counts as $label => $count) {
  echo sprintf("║  %-30s %10s  ║\n", $label, $count);
}

echo "╠══════════════════════════════════════════════╣\n";

// Order breakdown.
$orders_all = $etm->getStorage('commerce_order')->loadMultiple();
$breakdown = [];
foreach ($orders_all as $o) {
  $key = $o->bundle() . '/' . $o->getState()->getId();
  $breakdown[$key] = ($breakdown[$key] ?? 0) + 1;
}
ksort($breakdown);
foreach ($breakdown as $k => $v) {
  echo sprintf("║  Encomendas %-20s %10s  ║\n", $k, $v);
}

echo "╠══════════════════════════════════════════════╣\n";

// Subscription breakdown.
$subs_all = $sub_storage->loadMultiple();
$sub_breakdown = [];
foreach ($subs_all as $s) {
  $key = $s->get('subscription_status')->value;
  $sub_breakdown[$key] = ($sub_breakdown[$key] ?? 0) + 1;
}
ksort($sub_breakdown);
foreach ($sub_breakdown as $k => $v) {
  echo sprintf("║  Subscrições %-19s %10s  ║\n", $k, $v);
}

echo "╚══════════════════════════════════════════════╝\n";
