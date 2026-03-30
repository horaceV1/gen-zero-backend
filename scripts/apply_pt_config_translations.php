<?php

/**
 * @file
 * Script to apply Portuguese config translation overrides for custom modules.
 *
 * Run with: ddev drush scr scripts/apply_pt_config_translations.php
 */

use Drupal\language\Config\LanguageConfigOverride;

$language_manager = \Drupal::languageManager();
$config_factory = \Drupal::configFactory();

// Get the pt-pt language config override storage.
$language = $language_manager->getLanguage('pt-pt');
if (!$language) {
  echo "ERROR: Portuguese (pt-pt) language not found.\n";
  return;
}

$overrides = [];

// =========================================================================
// gen_zero_subscriptions — Menu links
// =========================================================================
$overrides['gen_zero_subscriptions.links.menu'] = [
  'gen_zero_subscriptions.admin' => [
    'title' => 'Subscrições',
    'description' => 'Gerir níveis e grupos de níveis de subscrição.',
  ],
  'gen_zero_subscriptions.tier_groups' => [
    'title' => 'Grupos de Níveis',
    'description' => 'Gerir grupos de níveis de subscrição.',
  ],
  'gen_zero_subscriptions.tiers' => [
    'title' => 'Níveis',
    'description' => 'Gerir níveis de subscrição.',
  ],
  'gen_zero_subscriptions.user_subscriptions' => [
    'title' => 'Subscrições de Utilizadores',
    'description' => 'Ver e gerir subscrições ativas de utilizadores.',
  ],
];

// gen_zero_subscriptions — Task links
$overrides['gen_zero_subscriptions.links.task'] = [
  'gen_zero_subscriptions.tab.tier_groups' => [
    'title' => 'Grupos de Níveis',
  ],
  'gen_zero_subscriptions.tab.tiers' => [
    'title' => 'Níveis',
  ],
  'gen_zero_subscriptions.tab.user_subscriptions' => [
    'title' => 'Subscrições de Utilizadores',
  ],
];

// gen_zero_subscriptions — Action links
$overrides['gen_zero_subscriptions.links.action'] = [
  'entity.subscription_tier_group.add_form' => [
    'title' => 'Adicionar grupo de níveis',
  ],
  'entity.subscription_tier.add_form' => [
    'title' => 'Adicionar nível',
  ],
];

// gen_zero_subscriptions — Permissions
$overrides['gen_zero_subscriptions.permissions'] = [
  'administer subscriptions' => [
    'title' => 'Administrar subscrições',
    'description' => 'Gerir grupos de níveis de subscrição, níveis e definições.',
  ],
  'view subscription tiers' => [
    'title' => 'Ver níveis de subscrição',
    'description' => 'Ver níveis de subscrição disponíveis através da API REST.',
  ],
  'create subscriptions' => [
    'title' => 'Criar subscrições',
    'description' => 'Permite que os utilizadores criem subscrições através da API REST.',
  ],
  'manage own subscription' => [
    'title' => 'Gerir subscrição própria',
    'description' => 'Permite que os utilizadores vejam, pausem ou cancelem as suas subscrições.',
  ],
];

// =========================================================================
// gen_zero_donations — Permissions
// =========================================================================
$overrides['gen_zero_donations.permissions'] = [
  'create donations' => [
    'title' => 'Criar doações',
    'description' => 'Permite que os utilizadores criem encomendas de doação através da API REST.',
  ],
];

// =========================================================================
// Apply all overrides using the language.config_factory_override service
// =========================================================================
$config_override = \Drupal::service('language.config_factory_override');
$config_override->setLanguage($language);

$count = 0;
foreach ($overrides as $config_name => $values) {
  // Use language config override directly.
  $override = $language_manager->getLanguageConfigOverride('pt-pt', $config_name);

  foreach ($values as $key => $translations) {
    foreach ($translations as $property => $value) {
      $override->set("$key.$property", $value);
    }
  }

  $override->save();
  $count++;
  echo "  ✓ Config override saved: $config_name\n";
}

echo "\nDone! $count config translation overrides applied for pt-pt.\n";

// =========================================================================
// Now also translate the system.module info for custom modules
// =========================================================================
// These are typically in the config as system.module.* but Drupal stores
// module info in code, not config. The module names in admin UI come from
// the .info.yml files and are translated via the locale t() system.
// Since we already imported those strings via .po, they should work.

echo "\n--- Clearing caches ---\n";
drupal_flush_all_caches();
echo "All caches cleared.\n";
