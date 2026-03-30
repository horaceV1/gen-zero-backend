<?php

/**
 * @file
 * Fix data integrity issues found during verification.
 *
 * Run with: ddev drush scr scripts/fix_data.php
 */

$etm = \Drupal::entityTypeManager();

// Fix 1: Assign projeto to orphaned donation orders.
echo "=== Fix: Orphaned donation orders ===\n";
$donation_orders = $etm->getStorage('commerce_order')->loadByProperties(['type' => 'donation']);
$projetos = $etm->getStorage('node')->loadByProperties(['type' => 'projeto', 'status' => 1]);
$projeto_ids = array_keys($projetos);

$fixed = 0;
foreach ($donation_orders as $order) {
  $projeto = $order->get('field_projeto')->entity;
  if ($projeto === NULL && !empty($projeto_ids)) {
    $order->set('field_projeto', ['target_id' => $projeto_ids[array_rand($projeto_ids)]]);
    $order->save();
    $fixed++;
  }
}
echo "  Fixed {$fixed} orphaned donation orders.\n";

// Fix 2: Fix 'CleanAngra 2' progress to match goal (it's marked concluido).
echo "=== Fix: CleanAngra 2 progress ===\n";
$ca2 = $etm->getStorage('node')->loadByProperties(['title' => 'CleanAngra 2', 'type' => 'projeto']);
if ($ca2) {
  $node = reset($ca2);
  $goal = $node->get('project_goal')->value;
  $node->set('project_current_progress', $goal);
  $node->save();
  echo "  Fixed CleanAngra 2 progress to €{$goal}.\n";
}

echo "\nDone.\n";
