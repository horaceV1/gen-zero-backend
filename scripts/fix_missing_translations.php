<?php

/**
 * @file
 * Fix remaining untranslated strings.
 *
 * Run with: ddev drush scr scripts/fix_missing_translations.php
 */

$storage = \Drupal::service('locale.storage');

$strings = [
  'Gateway' => 'Gateway de Pagamento',
  'Manual / Offline' => 'Manual / Offline',
];

foreach ($strings as $source => $translation) {
  $result = $storage->findString(['source' => $source, 'context' => '']);
  if (!$result) {
    $result = $storage->createString(['source' => $source, 'context' => '']);
    $result->save();
  }

  // Check if translation already exists.
  $existing = $storage->findTranslation(['lid' => $result->lid, 'language' => 'pt-pt']);
  if ($existing) {
    $existing->setString($translation);
    $existing->customized = 1;
    $existing->save();
    echo "Updated: $source => $translation\n";
  }
  else {
    $storage->createTranslation([
      'lid' => $result->lid,
      'language' => 'pt-pt',
      'translation' => $translation,
      'customized' => 1,
    ])->save();
    echo "Created: $source => $translation\n";
  }
}

echo "\nDone!\n";
