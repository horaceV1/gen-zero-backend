<?php

/**
 * @file
 * Verification script — tests API data integrity.
 *
 * Run with: ddev drush scr scripts/verify_data.php
 */

use Drupal\user\Entity\User;

$etm = \Drupal::entityTypeManager();

echo "╔══════════════════════════════════════════════════╗\n";
echo "║     VERIFICAÇÃO DE DADOS — Gen Zero             ║\n";
echo "╚══════════════════════════════════════════════════╝\n\n";

// ====================================================================
// 1. Subscription Tiers API
// ====================================================================
echo "=== 1. API: Subscription Tiers ===\n";
$manager = \Drupal::service('gen_zero_subscriptions.subscription_manager');
$grouped = $manager->getGroupedTiers();

$errors = 0;
foreach ($grouped as $group) {
  echo "  Grupo: {$group['label']} ({$group['id']})\n";
  foreach ($group['tiers'] as $tier) {
    $has_price = !empty($tier['price']) && (float) $tier['price'] > 0;
    $has_label = !empty($tier['label']);
    $has_period = in_array($tier['billing_period'], ['monthly', 'quarterly', 'yearly']);

    $status = ($has_price && $has_label && $has_period) ? 'OK' : 'ERRO';
    if ($status === 'ERRO') {
      $errors++;
    }
    echo "    [{$status}] {$tier['label']}: €{$tier['price']}/{$tier['billing_period']}\n";
  }
}
echo "\n";

// ====================================================================
// 2. User Subscriptions — sample users
// ====================================================================
echo "=== 2. Subscrições por Utilizador ===\n";
$sample_uids = [1, 2, 5, 6, 7, 8, 9, 10, 11, 12];
foreach ($sample_uids as $uid) {
  $subs = $manager->getUserSubscriptions($uid);
  $user = User::load($uid);
  $name = $user ? $user->getAccountName() : 'desconhecido';
  echo "  {$name} (uid:{$uid}): " . count($subs) . " subscrição(ões)\n";
  foreach ($subs as $s) {
    // Validate logic.
    $tier_valid = !empty($s['tier_id']);
    $status_valid = in_array($s['status'], ['active', 'paused', 'cancelled', 'pending', 'expired']);
    $billing_valid = ($s['status'] !== 'active') || ($s['next_billing_date'] > 0);

    $checks = ($tier_valid && $status_valid) ? 'OK' : 'ERRO';
    if ($checks === 'ERRO') {
      $errors++;
    }
    echo "    [{$checks}] #{$s['id']} {$s['tier_id']} [{$s['status']}] €{$s['price']}/{$s['billing_period']}";
    if ($s['status'] === 'active' && $s['next_billing_date'] > 0) {
      echo " próx.fatura:" . date('Y-m-d', $s['next_billing_date']);
    }
    echo "\n";
  }
}
echo "\n";

// ====================================================================
// 3. hasActiveSubscription check
// ====================================================================
echo "=== 3. Verificação hasActiveSubscription ===\n";
$tier_ids = ['semente', 'arbusto', 'arvore', 'floresta'];
$check_uids = [1, 2, 6, 7, 8];
foreach ($check_uids as $uid) {
  $user = User::load($uid);
  $name = $user ? $user->getAccountName() : '?';
  $active_tiers = [];
  foreach ($tier_ids as $tid) {
    if ($manager->hasActiveSubscription($uid, $tid)) {
      $active_tiers[] = $tid;
    }
  }
  $active_str = empty($active_tiers) ? 'nenhuma' : implode(', ', $active_tiers);
  echo "  {$name}: subscrições ativas em: {$active_str}\n";
}
echo "\n";

// ====================================================================
// 4. Donation Config
// ====================================================================
echo "=== 4. Configuração de Doações ===\n";
$donManager = \Drupal::service('gen_zero_donations.donation_manager');
$config = $donManager->getConfig();
echo "  Moeda: {$config['currency']} ({$config['currency_symbol']})\n";
echo "  Mínimo: €{$config['min_amount']}\n";
echo "  Máximo: €{$config['max_amount']}\n";
echo "  Valores fixos: ";
foreach ($config['fixed_amounts'] as $fa) {
  echo $fa['label'] . ' ';
}
echo "\n\n";

// ====================================================================
// 5. Donation Amount Validation
// ====================================================================
echo "=== 5. Validação de Montantes de Doação ===\n";
$test_amounts = [0, -5, 0.99, 1.00, 5, 25, 100, 9999.99, 10000, 10001, 'abc', 15.555];
foreach ($test_amounts as $amount) {
  $result = $donManager->validateAmount($amount);
  $status = $result['valid'] ? 'VÁLIDO' : 'INVÁLIDO';
  $display = is_numeric($amount) ? number_format((float) $amount, 2) : (string) $amount;
  echo "  €{$display}: [{$status}]";
  if (!$result['valid']) {
    echo " — {$result['message']}";
  }
  echo "\n";
}
echo "\n";

// ====================================================================
// 6. Donation Orders — verify projeto references
// ====================================================================
echo "=== 6. Doações — Integridade das Referências ===\n";
$donation_orders = $etm->getStorage('commerce_order')->loadByProperties(['type' => 'donation']);
$valid_donations = 0;
$invalid_donations = 0;
$missing_projeto = 0;
$missing_items = 0;

foreach ($donation_orders as $order) {
  $has_items = count($order->getItems()) > 0;
  $projeto = $order->get('field_projeto')->entity;
  $has_projeto = ($projeto !== NULL);

  if (!$has_items) {
    $missing_items++;
  }
  if (!$has_projeto) {
    $missing_projeto++;
  }
  if ($has_items && $has_projeto) {
    $valid_donations++;
  }
  else {
    $invalid_donations++;
  }
}

echo "  Total doações: " . count($donation_orders) . "\n";
echo "  Válidas (com items + projeto): {$valid_donations}\n";
echo "  Sem projeto referenciado: {$missing_projeto}\n";
echo "  Sem items: {$missing_items}\n";
if ($invalid_donations > 0) {
  $errors += $invalid_donations;
  echo "  [AVISO] {$invalid_donations} doações com dados incompletos\n";
}
echo "\n";

// ====================================================================
// 7. Product Orders — verify items have prices
// ====================================================================
echo "=== 7. Encomendas de Produtos — Verificação ===\n";
$product_orders = $etm->getStorage('commerce_order')->loadByProperties(['type' => 'default']);
$valid_orders = 0;
$orders_with_total = 0;

foreach ($product_orders as $order) {
  $items = $order->getItems();
  $all_priced = TRUE;
  foreach ($items as $item) {
    if (!$item->getUnitPrice()) {
      $all_priced = FALSE;
    }
  }
  if ($all_priced && count($items) > 0) {
    $valid_orders++;
  }
  $total = $order->getTotalPrice();
  if ($total && (float) $total->getNumber() > 0) {
    $orders_with_total++;
  }
}

echo "  Total encomendas: " . count($product_orders) . "\n";
echo "  Com items+preços válidos: {$valid_orders}\n";
echo "  Com total > €0: {$orders_with_total}\n";
echo "\n";

// ====================================================================
// 8. Projetos — verify states and progress logic
// ====================================================================
echo "=== 8. Projetos — Lógica de Estado ===\n";
$projetos = $etm->getStorage('node')->loadByProperties(['type' => 'projeto']);

foreach ($projetos as $p) {
  $state = $p->get('project_state')->value ?? 'N/A';
  $goal = (float) ($p->get('project_goal')->value ?? 0);
  $progress = (float) ($p->get('project_current_progress')->value ?? 0);
  $pct = $goal > 0 ? round(($progress / $goal) * 100, 1) : 0;

  // Logic check: completed projects should have progress >= goal.
  $logic_ok = TRUE;
  $note = '';
  if ($state === 'concluido' && $progress < $goal) {
    $logic_ok = FALSE;
    $note = ' [AVISO: concluído mas progresso < objetivo]';
    $errors++;
  }

  $status = $logic_ok ? 'OK' : 'AVISO';
  echo "  [{$status}] {$p->label()} — {$state} — €{$progress}/€{$goal} ({$pct}%){$note}\n";

  // Check objectives.
  $objectives = $p->get('project_objectives')->referencedEntities();
  $total_obj = count($objectives);
  $done_obj = 0;
  foreach ($objectives as $obj) {
    if ($obj->get('alcancado')->value) {
      $done_obj++;
    }
  }
  if ($total_obj > 0) {
    echo "    Objetivos: {$done_obj}/{$total_obj} concluídos\n";
  }
}
echo "\n";

// ====================================================================
// 9. Subscription lifecycle methods
// ====================================================================
echo "=== 9. Teste de Ciclo de Vida de Subscrições ===\n";

// Find an active subscription to test pause/resume.
$active_subs = $etm->getStorage('user_subscription')->loadByProperties([
  'subscription_status' => 'active',
]);

if (!empty($active_subs)) {
  $test_sub = reset($active_subs);
  $test_id = (int) $test_sub->id();
  $test_uid = (int) $test_sub->getOwnerId();

  // Temporarily set current user for access check.
  $account_switcher = \Drupal::service('account_switcher');
  $test_user = User::load($test_uid);
  $account_switcher->switchTo($test_user);

  echo "  Testar subscrição #{$test_id} (uid:{$test_uid})\n";

  // Test pause.
  $pause_result = $manager->pauseSubscription($test_id);
  echo "  Pausar: " . ($pause_result['success'] ? 'OK' : 'FALHOU') . " — {$pause_result['message']}\n";

  // Test resume.
  $resume_result = $manager->resumeSubscription($test_id);
  echo "  Retomar: " . ($resume_result['success'] ? 'OK' : 'FALHOU') . " — {$resume_result['message']}\n";

  // Verify it's active again.
  $sub_data = $manager->getSubscription($test_id);
  echo "  Estado final: {$sub_data['status']}\n";

  $account_switcher->switchBack();
}
else {
  echo "  [AVISO] Nenhuma subscrição ativa para testar.\n";
}
echo "\n";

// ====================================================================
// SUMMARY
// ====================================================================
echo "╔══════════════════════════════════════════════════╗\n";
if ($errors === 0) {
  echo "║  ✓ TODAS AS VERIFICAÇÕES PASSARAM              ║\n";
}
else {
  echo "║  ✗ ERROS ENCONTRADOS: " . str_pad($errors, 26) . "║\n";
}
echo "╚══════════════════════════════════════════════════╝\n";
