<?php

/**
 * @file
 * Update leaflet widget geocoder to use Nominatim config entity.
 *
 * Run with: ddev drush php:script scripts/update_leaflet_geocoder.php
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

// Enable geocoder with Nominatim provider entity reference.
$component['settings']['geocoder'] = [
  'control' => TRUE,
  'settings' => [
    'set_marker' => TRUE,
    'popup' => FALSE,
    'autocomplete' => [
      'placeholder' => 'Pesquisar endereço...',
      'title' => 'Pesquise um endereço no mapa',
    ],
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

// Enable locate control.
$component['settings']['locate'] = [
  'control' => TRUE,
  'options' => '{"position":"topright","setView":"untilPanOrZoom","returnToPrevBounds":true,"keepCurrentZoomLevel":true,"strings":{"title":"Localizar-me"}}',
  'automatic' => FALSE,
];

// Enable fullscreen control.
$component['settings']['fullscreen'] = [
  'control' => TRUE,
  'options' => '{"position":"topleft","pseudoFullscreen":false}',
];

// Center map on Portugal by default.
$component['settings']['map']['map_position']['center']['lat'] = 39.5;
$component['settings']['map']['map_position']['center']['lon'] = -8.0;
$component['settings']['map']['map_position']['zoom'] = 7;

$form_display->setComponent('leaflet_location', $component);
$form_display->save();

echo "Leaflet widget updated with geocoder (Nominatim), locate, fullscreen.\n";
echo "Map centered on Portugal.\n";

// Verify
$updated = \Drupal::entityTypeManager()
  ->getStorage('entity_form_display')
  ->load('node.information.default');
$comp = $updated->getComponent('leaflet_location');
echo "\nGeocoder settings:\n";
print_r($comp['settings']['geocoder']);
