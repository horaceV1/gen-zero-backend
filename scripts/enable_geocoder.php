<?php

/**
 * @file
 * Enable geocoder search on the information content type's leaflet widget.
 *
 * Run with: ddev drush php:script scripts/enable_geocoder.php
 */

// Get the form display for node.information.default.
$form_display = \Drupal::entityTypeManager()
  ->getStorage('entity_form_display')
  ->load('node.information.default');

if (!$form_display) {
  echo "ERROR: Form display node.information.default not found.\n";
  exit(1);
}

$component = $form_display->getComponent('leaflet_location');
if (!$component) {
  echo "ERROR: leaflet_location component not found in form display.\n";
  exit(1);
}

echo "Current geocoder settings:\n";
print_r($component['settings']['geocoder']);
echo "\n";

// Enable geocoder control with Nominatim provider.
$component['settings']['geocoder'] = [
  'control' => TRUE,
  'settings' => [
    'popup' => FALSE,
    'position' => 'topright',
    'input_size' => 25,
    'providers' => [
      'nominatim' => [
        'checked' => TRUE,
        'weight' => 0,
      ],
    ],
    'min_terms' => 3,
    'delay' => 800,
    'zoom' => 16,
    'options' => '',
  ],
];

// Enable locate control (GPS).
$component['settings']['locate'] = [
  'control' => TRUE,
  'options' => '{"position":"topright","setView":"untilPanOrZoom","returnToPrevBounds":true,"keepCurrentZoomLevel":true,"strings":{"title":"Locate my position"}}',
  'automatic' => FALSE,
];

$form_display->setComponent('leaflet_location', $component);
$form_display->save();

echo "Geocoder enabled on leaflet_location widget with Nominatim provider.\n";
echo "Locate (GPS) control also enabled.\n";

// Verify
$updated = \Drupal::entityTypeManager()
  ->getStorage('entity_form_display')
  ->load('node.information.default');
$comp = $updated->getComponent('leaflet_location');
echo "\nUpdated geocoder settings:\n";
print_r($comp['settings']['geocoder']);
