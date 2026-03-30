<?php

/**
 * @file
 * Seeds test data for orders, donations, and subscriptions.
 *
 * Run with: ddev drush scr scripts/seed_test_data.php
 */

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_price\Price;
use Drupal\user\Entity\User;

$entity_type_manager = \Drupal::entityTypeManager();

// ---------------------------------------------------------------------------
// 1. Create test users if needed
// ---------------------------------------------------------------------------
echo "=== Creating test users ===\n";

$test_users = [
  ['name' => 'maria_silva', 'mail' => 'maria.silva@example.com'],
  ['name' => 'joao_costa', 'mail' => 'joao.costa@example.com'],
  ['name' => 'ana_santos', 'mail' => 'ana.santos@example.com'],
  ['name' => 'pedro_oliveira', 'mail' => 'pedro.oliveira@example.com'],
  ['name' => 'sofia_pereira', 'mail' => 'sofia.pereira@example.com'],
  ['name' => 'carlos_ferreira', 'mail' => 'carlos.ferreira@example.com'],
  ['name' => 'lucia_rodrigues', 'mail' => 'lucia.rodrigues@example.com'],
  ['name' => 'miguel_almeida', 'mail' => 'miguel.almeida@example.com'],
];

$user_ids = [];

// Include existing real users.
$existing = $entity_type_manager->getStorage('user')->loadMultiple();
foreach ($existing as $u) {
  if ((int) $u->id() > 0) {
    $user_ids[] = (int) $u->id();
  }
}

foreach ($test_users as $tu) {
  $exists = $entity_type_manager->getStorage('user')
    ->loadByProperties(['name' => $tu['name']]);
  if ($exists) {
    $user_ids[] = (int) reset($exists)->id();
    echo "  User {$tu['name']} already exists.\n";
    continue;
  }
  $user = User::create([
    'name' => $tu['name'],
    'mail' => $tu['mail'],
    'status' => 1,
    'pass' => 'testpass123',
  ]);
  $user->save();
  $user_ids[] = (int) $user->id();
  echo "  Created user {$tu['name']} (uid: {$user->id()})\n";
}

$user_ids = array_unique($user_ids);
echo "Total users available: " . count($user_ids) . "\n\n";

// ---------------------------------------------------------------------------
// 2. Load projetos for donations
// ---------------------------------------------------------------------------
echo "=== Loading projetos ===\n";
$projetos = $entity_type_manager->getStorage('node')
  ->loadByProperties(['type' => 'projeto', 'status' => 1]);
$projeto_ids = array_keys($projetos);

if (empty($projeto_ids)) {
  echo "  WARNING: No published projetos found. Skipping donation creation.\n";
}
else {
  foreach ($projetos as $p) {
    echo "  Found projeto: {$p->label()} (nid: {$p->id()})\n";
  }
}
echo "\n";

// ---------------------------------------------------------------------------
// 3. Load store
// ---------------------------------------------------------------------------
$store = $entity_type_manager->getStorage('commerce_store')->loadDefault();
$store_id = $store ? $store->id() : 1;
echo "Using store ID: {$store_id}\n\n";

// ---------------------------------------------------------------------------
// 4. Load subscription tiers
// ---------------------------------------------------------------------------
echo "=== Loading subscription tiers ===\n";
$tiers = $entity_type_manager->getStorage('subscription_tier')->loadMultiple();
$tier_ids = [];
foreach ($tiers as $t) {
  if ($t->status() && $t->getPrice() > 0) {
    $tier_ids[] = $t->id();
    echo "  Tier: {$t->label()} ({$t->id()}) - {$t->getPrice()} {$t->getCurrency()}/{$t->getBillingPeriod()}\n";
  }
}
echo "\n";

// ---------------------------------------------------------------------------
// 5. Create donation orders (various states, amounts, users)
// ---------------------------------------------------------------------------
echo "=== Creating donation orders ===\n";

$donation_amounts = ['5.00', '10.00', '15.00', '25.00', '50.00', '75.00', '100.00', '150.00', '200.00', '500.00'];
$order_states = ['draft', 'completed', 'completed', 'completed']; // Bias toward completed
$designation_types = ['honor', 'memory'];
$gift_types = ['ecard', 'printcard'];
$honoree_first_names = ['Ana', 'João', 'Maria', 'Pedro', 'Sofia', 'Carlos', 'Lucia', 'Miguel', 'Isabel', 'Tomas'];
$honoree_last_names = ['Silva', 'Costa', 'Santos', 'Oliveira', 'Pereira', 'Ferreira', 'Rodrigues', 'Almeida', 'Gomes', 'Martins'];
$messages = [
  'In loving memory of our dear friend.',
  'Happy Birthday! Donating in your honor.',
  'Thank you for inspiring us to give.',
  'With love and gratitude.',
  'Supporting a cause close to our hearts.',
  'Wishing you all the best.',
  'For a better tomorrow.',
  'In celebration of your achievements.',
  '',
  '',
];

$donation_count = 0;

if (!empty($projeto_ids)) {
  // Create 30 donation orders spread across users, projetos, amounts, and states
  for ($i = 0; $i < 30; $i++) {
    $uid = $user_ids[array_rand($user_ids)];
    $projeto_id = $projeto_ids[array_rand($projeto_ids)];
    $amount = $donation_amounts[array_rand($donation_amounts)];
    $state = $order_states[array_rand($order_states)];
    $is_monthly = (rand(0, 3) === 0); // 25% chance
    $is_designated = (rand(0, 3) === 0); // 25% chance
    $is_notify = ($is_designated && rand(0, 1) === 0);

    // Load the user for email.
    $user = User::load($uid);
    $email = $user ? $user->getEmail() : "donor{$i}@example.com";

    $price = new Price($amount, 'EUR');

    // Build order item fields.
    $item_values = [
      'type' => 'donation',
      'title' => sprintf('Donation to %s', $projetos[$projeto_id]->label()),
      'unit_price' => $price,
      'quantity' => 1,
      'field_donation_amount' => $price,
      'field_monthly' => $is_monthly ? 1 : 0,
      'field_designated' => $is_designated ? 1 : 0,
      'field_designation_type' => $designation_types[array_rand($designation_types)],
      'field_gift_type' => $gift_types[array_rand($gift_types)],
    ];

    if ($is_designated) {
      $item_values['field_honoree_first'] = $honoree_first_names[array_rand($honoree_first_names)];
      $item_values['field_honoree_last'] = $honoree_last_names[array_rand($honoree_last_names)];
    }

    if ($is_notify) {
      $item_values['field_notify'] = 1;
      $item_values['field_recipient_first_name'] = $honoree_first_names[array_rand($honoree_first_names)];
      $item_values['field_recipient_last_name'] = $honoree_last_names[array_rand($honoree_last_names)];
      $item_values['field_card_email'] = 'notify' . $i . '@example.com';
      $item_values['field_message'] = $messages[array_rand($messages)];
    }

    if ($is_monthly) {
      // Set recurring start date somewhere in the past 6 months.
      $days_ago = rand(0, 180);
      $start_date = date('Y-m-d', strtotime("-{$days_ago} days"));
      $item_values['field_recurring_begins'] = $start_date;
    }

    $order_item = OrderItem::create($item_values);
    $order_item->save();

    // Create the order.
    // Vary the placed/completed timestamps for realistic historical data.
    $days_back = rand(0, 365);
    $created_time = strtotime("-{$days_back} days") + rand(0, 86400);

    $order = Order::create([
      'type' => 'donation',
      'store_id' => $store_id,
      'uid' => $uid,
      'mail' => $email,
      'order_items' => [$order_item],
      'field_projeto' => ['target_id' => $projeto_id],
      'state' => $state,
      'created' => $created_time,
      'changed' => $created_time + rand(0, 3600),
    ]);

    if ($state === 'completed') {
      $order->setCompletedTime($created_time + rand(60, 7200));
    }

    $order->save();
    $donation_count++;
  }
}

echo "  Created {$donation_count} donation orders.\n\n";

// ---------------------------------------------------------------------------
// 6. Create regular (default) commerce orders
// ---------------------------------------------------------------------------
echo "=== Creating default commerce orders ===\n";

$product_titles = [
  'Ocean Cleanup Kit',
  'Eco-Friendly Water Bottle',
  'Recycled Tote Bag',
  'Bamboo Utensil Set',
  'Solar Phone Charger',
  'Organic Cotton T-Shirt',
  'Reusable Beeswax Wraps',
  'Seed Paper Notebook',
  'Cork Yoga Mat',
  'Stainless Steel Straw Set',
  'Recycled Glass Vase',
  'Hemp Backpack',
  'Biodegradable Phone Case',
  'Fair Trade Coffee Bundle',
  'Plant-a-Tree Certificate',
];

$product_prices = ['9.99', '12.50', '15.00', '19.99', '24.50', '29.99', '34.99', '39.99', '49.99', '59.99', '79.99', '99.99'];
$default_order_states = ['draft', 'completed', 'completed', 'completed', 'completed'];
$default_order_count = 0;

for ($i = 0; $i < 25; $i++) {
  $uid = $user_ids[array_rand($user_ids)];
  $user = User::load($uid);
  $email = $user ? $user->getEmail() : "buyer{$i}@example.com";
  $state = $default_order_states[array_rand($default_order_states)];

  // Each order gets 1-3 items.
  $num_items = rand(1, 3);
  $order_items = [];

  for ($j = 0; $j < $num_items; $j++) {
    $item_price = $product_prices[array_rand($product_prices)];
    $title = $product_titles[array_rand($product_titles)];
    $qty = rand(1, 3);

    $order_item = OrderItem::create([
      'type' => 'default',
      'title' => $title,
      'unit_price' => new Price($item_price, 'EUR'),
      'quantity' => $qty,
    ]);
    $order_item->save();
    $order_items[] = $order_item;
  }

  $days_back = rand(0, 365);
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
  $default_order_count++;
}

echo "  Created {$default_order_count} default orders.\n\n";

// ---------------------------------------------------------------------------
// 7. Create user subscriptions (various tiers, statuses, billing periods)
// ---------------------------------------------------------------------------
echo "=== Creating user subscriptions ===\n";

$sub_storage = $entity_type_manager->getStorage('user_subscription');
$sub_statuses = ['active', 'active', 'active', 'paused', 'cancelled', 'pending']; // Bias toward active
$billing_periods = ['monthly', 'monthly', 'monthly', 'quarterly', 'yearly']; // Bias toward monthly
$sub_count = 0;

if (!empty($tier_ids)) {
  // Create subscriptions - aim for variety across users, tiers, statuses
  foreach ($user_ids as $uid) {
    if ($uid == 0) {
      continue; // Skip anonymous.
    }

    // Each user gets 1-3 subscriptions (some may have tried different tiers).
    $num_subs = rand(1, 3);
    $used_tiers = [];

    for ($s = 0; $s < $num_subs; $s++) {
      $tier_id = $tier_ids[array_rand($tier_ids)];

      // Avoid duplicate active subs to same tier for same user.
      if (in_array($tier_id, $used_tiers)) {
        continue;
      }
      $used_tiers[] = $tier_id;

      $tier = $tiers[$tier_id];
      $status = $sub_statuses[array_rand($sub_statuses)];
      $billing_period = $billing_periods[array_rand($billing_periods)];

      // Calculate dates.
      $days_back = rand(1, 300);
      $created_time = strtotime("-{$days_back} days");

      // Next billing date depends on status.
      if ($status === 'active') {
        $next_billing = strtotime("+{$days_back} days", match ($billing_period) {
          'monthly' => strtotime('+1 month', $created_time),
          'quarterly' => strtotime('+3 months', $created_time),
          'yearly' => strtotime('+1 year', $created_time),
          default => strtotime('+1 month', $created_time),
        });
        // Ensure some are upcoming, some overdue.
        if (rand(0, 1)) {
          $next_billing = strtotime('+' . rand(1, 30) . ' days');
        }
      }
      elseif ($status === 'paused' || $status === 'cancelled') {
        $next_billing = 0;
      }
      else {
        $next_billing = strtotime('+' . rand(1, 7) . ' days');
      }

      // Get user email.
      $user = User::load($uid);
      $email = $user ? $user->getEmail() : 'subscriber@example.com';

      // Adjust price for billing period.
      $base_price = (float) $tier->getPrice();
      $price = match ($billing_period) {
        'quarterly' => number_format($base_price * 3 * 0.9, 2, '.', ''), // 10% discount
        'yearly' => number_format($base_price * 12 * 0.8, 2, '.', ''), // 20% discount
        default => number_format($base_price, 2, '.', ''),
      };

      $subscription = $sub_storage->create([
        'tier_id' => $tier_id,
        'gateway_id' => 'manual',
        'external_id' => 'test_' . $uid . '_' . $tier_id . '_' . $s,
        'subscription_status' => $status,
        'price' => $price,
        'currency' => $tier->getCurrency(),
        'billing_period' => $billing_period,
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
else {
  echo "  WARNING: No active subscription tiers found. Skipping.\n";
}

echo "  Created {$sub_count} subscriptions.\n\n";

// ---------------------------------------------------------------------------
// Summary
// ---------------------------------------------------------------------------
echo "=== SEED COMPLETE ===\n";
echo "  Donation orders created: {$donation_count}\n";
echo "  Default orders created:  {$default_order_count}\n";
echo "  Subscriptions created:   {$sub_count}\n";
echo "  Test users created/used: " . count($user_ids) . "\n";
echo "\nTotal orders now: " . $entity_type_manager->getStorage('commerce_order')
  ->getQuery()->accessCheck(FALSE)->count()->execute() . "\n";
echo "Total subscriptions now: " . $entity_type_manager->getStorage('user_subscription')
  ->getQuery()->accessCheck(FALSE)->count()->execute() . "\n";
