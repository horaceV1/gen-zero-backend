<?php

/**
 * @file
 * Update all content entity langcodes from 'en' to 'pt-pt'.
 *
 * Since the site was originally created with English as default language
 * and all content is actually Portuguese, this script updates all entity
 * langcodes to pt-pt so that JSON:API and path aliases work correctly.
 *
 * Run with: ddev drush scr scripts/fix_content_langcodes.php
 */

use Drupal\Core\Database\Database;

$connection = Database::getConnection();

echo "=== Updating content langcodes from 'en' to 'pt-pt' ===\n\n";

// 1. Update node tables.
$tables = [
  'node_field_data',
  'node_field_revision',
];
foreach ($tables as $table) {
  if ($connection->schema()->tableExists($table)) {
    $updated = $connection->update($table)
      ->fields(['langcode' => 'pt-pt'])
      ->condition('langcode', 'en')
      ->execute();
    echo "  $table: $updated rows updated\n";
  }
}

// Also update the base node table.
if ($connection->schema()->tableExists('node')) {
  $updated = $connection->update('node')
    ->fields(['langcode' => 'pt-pt'])
    ->condition('langcode', 'en')
    ->execute();
  echo "  node: $updated rows updated\n";
}

// 2. Update path_alias tables.
$tables = ['path_alias', 'path_alias_revision'];
foreach ($tables as $table) {
  if ($connection->schema()->tableExists($table)) {
    $updated = $connection->update($table)
      ->fields(['langcode' => 'pt-pt'])
      ->condition('langcode', 'en')
      ->execute();
    echo "  $table: $updated rows updated\n";
  }
}

// 3. Update taxonomy_term tables.
$tables = [
  'taxonomy_term_field_data',
  'taxonomy_term_field_revision',
  'taxonomy_term_data',
];
foreach ($tables as $table) {
  if ($connection->schema()->tableExists($table)) {
    $updated = $connection->update($table)
      ->fields(['langcode' => 'pt-pt'])
      ->condition('langcode', 'en')
      ->execute();
    echo "  $table: $updated rows updated\n";
  }
}

// 4. Update commerce entities (only tables with langcode column).
$commerce_tables = [
  'commerce_order_field_data',
  'commerce_order_field_revision',
  'commerce_order_item_field_data',
  'commerce_product_field_data',
  'commerce_product_field_revision',
  'commerce_product_variation_field_data',
  'commerce_product_variation_field_revision',
  'commerce_store_field_data',
  'commerce_store_field_revision',
];
foreach ($commerce_tables as $table) {
  if ($connection->schema()->tableExists($table)) {
    try {
      $updated = $connection->update($table)
        ->fields(['langcode' => 'pt-pt'])
        ->condition('langcode', 'en')
        ->execute();
      if ($updated > 0) {
        echo "  $table: $updated rows updated\n";
      }
    }
    catch (\Exception $e) {
      echo "  $table: skipped (no langcode column)\n";
    }
  }
}

// 5. Update block_content tables.
$block_tables = [
  'block_content_field_data',
  'block_content_field_revision',
];
foreach ($block_tables as $table) {
  if ($connection->schema()->tableExists($table)) {
    try {
      $updated = $connection->update($table)
        ->fields(['langcode' => 'pt-pt'])
        ->condition('langcode', 'en')
        ->execute();
      if ($updated > 0) {
        echo "  $table: $updated rows updated\n";
      }
    }
    catch (\Exception $e) {
      echo "  $table: skipped\n";
    }
  }
}

// 6. Update menu_link_content tables.
$menu_tables = [
  'menu_link_content_data',
  'menu_link_content_field_revision',
];
foreach ($menu_tables as $table) {
  if ($connection->schema()->tableExists($table)) {
    try {
      $updated = $connection->update($table)
        ->fields(['langcode' => 'pt-pt'])
        ->condition('langcode', 'en')
        ->execute();
      if ($updated > 0) {
        echo "  $table: $updated rows updated\n";
      }
    }
    catch (\Exception $e) {
      echo "  $table: skipped\n";
    }
  }
}

// 7. Update user_subscription table.
if ($connection->schema()->tableExists('user_subscription')) {
  try {
    $updated = $connection->update('user_subscription')
      ->fields(['langcode' => 'pt-pt'])
      ->condition('langcode', 'en')
      ->execute();
    if ($updated > 0) {
      echo "  user_subscription: $updated rows updated\n";
    }
  }
  catch (\Exception $e) {
    echo "  user_subscription: skipped\n";
  }
}

// 8. Update media tables.
$media_tables = [
  'media_field_data',
  'media_field_revision',
];
foreach ($media_tables as $table) {
  if ($connection->schema()->tableExists($table)) {
    try {
      $updated = $connection->update($table)
        ->fields(['langcode' => 'pt-pt'])
        ->condition('langcode', 'en')
        ->execute();
      if ($updated > 0) {
        echo "  $table: $updated rows updated\n";
      }
    }
    catch (\Exception $e) {
      echo "  $table: skipped\n";
    }
  }
}

// 9. Update paragraph tables.
$paragraph_tables = [
  'paragraphs_item_field_data',
  'paragraphs_item_revision_field_data',
];
foreach ($paragraph_tables as $table) {
  if ($connection->schema()->tableExists($table)) {
    try {
      $updated = $connection->update($table)
        ->fields(['langcode' => 'pt-pt'])
        ->condition('langcode', 'en')
        ->execute();
      if ($updated > 0) {
        echo "  $table: $updated rows updated\n";
      }
    }
    catch (\Exception $e) {
      echo "  $table: skipped\n";
    }
  }
}

// 10. Update webform submissions.
if ($connection->schema()->tableExists('webform_submission')) {
  try {
    $updated = $connection->update('webform_submission')
      ->fields(['langcode' => 'pt-pt'])
      ->condition('langcode', 'en')
      ->execute();
    if ($updated > 0) {
      echo "  webform_submission: $updated rows updated\n";
    }
  }
  catch (\Exception $e) {
    echo "  webform_submission: skipped\n";
  }
}

echo "\n--- Clearing caches ---\n";
drupal_flush_all_caches();
echo "All caches cleared.\n";

echo "\nDone! All content entity langcodes updated to pt-pt.\n";
