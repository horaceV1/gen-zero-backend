<?php

/**
 * @file
 * Verify Portuguese translations for custom module strings.
 *
 * Run with: ddev drush scr scripts/verify_translations.php
 */

use Drupal\Core\StringTranslation\TranslatableMarkup;

$language_manager = \Drupal::languageManager();
$string_translation = \Drupal::service('string_translation');

// Switch to pt-pt language context.
$pt = $language_manager->getLanguage('pt-pt');
if (!$pt) {
  echo "ERROR: pt-pt language not found.\n";
  return;
}

echo "=== Verifying Portuguese Translations ===\n\n";
echo "Default language: " . $language_manager->getDefaultLanguage()->getId() . "\n\n";

// Test key strings.
$test_strings = [
  // Entity labels
  'User Subscription',
  'User Subscriptions',
  'Subscription Tier',
  'Subscription Tiers',
  'Subscription Tier Group',
  'Subscription Tier Groups',
  // Menu / UI strings
  'Subscriptions',
  'Tier Groups',
  'Tiers',
  'Add tier group',
  'Add tier',
  // Permissions
  'Administer subscriptions',
  'View subscription tiers',
  'Create subscriptions',
  'Manage own subscription',
  'Create donations',
  // Form labels
  'Name',
  'Description',
  'Status',
  'Active',
  'Disabled',
  'Group',
  'Price',
  'Billing Period',
  'Currency',
  'Monthly',
  'Quarterly',
  'Yearly',
  'Benefits',
  'Badge Label',
  'Weight',
  'Pricing',
  '- Select group -',
  'Tier Group',
  'Anonymous',
  'Gateway',
  'Created',
  // Messages
  'Tier group %label has been created.',
  'Tier %label has been created.',
  'Price must be a positive number.',
  'Add another benefit',
  'Manual / Offline',
];

$pass = 0;
$fail = 0;

foreach ($test_strings as $source) {
  $translated = new TranslatableMarkup($source, [], ['langcode' => 'pt-pt']);
  $result = (string) $translated;

  if ($result === $source) {
    echo "  ✗ NOT TRANSLATED: \"$source\"\n";
    $fail++;
  }
  else {
    echo "  ✓ \"$source\" → \"$result\"\n";
    $pass++;
  }
}

echo "\n=== Results: $pass translated, $fail not translated ===\n";

if ($fail > 0) {
  echo "\nNote: Some common strings (Name, Description, Status, etc.) may already\n";
  echo "have translations from Drupal core that match our custom translations.\n";
}
