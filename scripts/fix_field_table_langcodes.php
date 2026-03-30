<?php

/**
 * @file
 * Fix langcode in ALL entity field data/revision tables from 'en' to 'pt-pt'.
 *
 * The previous script updated the main entity tables but missed the field
 * value tables (node__field_*, node_revision__field_*, commerce_*, etc.)
 * which also have a langcode column that must match the entity's langcode.
 *
 * Run with: ddev drush scr scripts/fix_field_table_langcodes.php
 */

use Drupal\Core\Database\Database;

$connection = Database::getConnection();

echo "=== Updating field table langcodes from 'en' to 'pt-pt' ===\n\n";

$total_updated = 0;

// Get all tables that have a 'langcode' column.
$all_tables = $connection->query("SHOW TABLES")->fetchCol();

foreach ($all_tables as $table) {
  // Check if the table has a langcode column.
  try {
    $columns = $connection->query("SHOW COLUMNS FROM `$table` LIKE 'langcode'")->fetchAll();
    if (empty($columns)) {
      continue;
    }
  }
  catch (\Exception $e) {
    continue;
  }

  // Count rows with 'en' langcode.
  try {
    $count = $connection->query("SELECT COUNT(*) FROM `$table` WHERE langcode = 'en'")->fetchField();
    if ($count > 0) {
      $updated = $connection->update($table)
        ->fields(['langcode' => 'pt-pt'])
        ->condition('langcode', 'en')
        ->execute();
      echo "  $table: $updated rows updated\n";
      $total_updated += $updated;
    }
  }
  catch (\Exception $e) {
    // Some tables may have issues, skip them.
    echo "  $table: SKIPPED ({$e->getMessage()})\n";
  }
}

echo "\n--- Total: $total_updated rows updated across all tables ---\n";
echo "\n--- Clearing caches ---\n";
drupal_flush_all_caches();
echo "All caches cleared.\n";
echo "\nDone!\n";
