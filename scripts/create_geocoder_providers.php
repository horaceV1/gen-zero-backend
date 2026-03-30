<?php

/**
 * @file
 * Create geocoder provider config entities for Nominatim.
 *
 * Run with: ddev drush php:script scripts/create_geocoder_providers.php
 */

use Drupal\geocoder\Entity\GeocoderProvider;

// Create Nominatim provider config entity.
$nominatim = GeocoderProvider::load('nominatim');
if ($nominatim) {
  echo "Nominatim provider already exists, updating...\n";
  $nominatim->set('configuration', [
    'rootUrl' => 'https://nominatim.openstreetmap.org',
    'userAgent' => 'GenZero-Drupal-Site',
    'referer' => '',
  ]);
  $nominatim->save();
}
else {
  $nominatim = GeocoderProvider::create([
    'id' => 'nominatim',
    'label' => 'Nominatim',
    'plugin' => 'nominatim',
    'configuration' => [
      'rootUrl' => 'https://nominatim.openstreetmap.org',
      'userAgent' => 'GenZero-Drupal-Site',
      'referer' => '',
    ],
  ]);
  $nominatim->save();
  echo "Created Nominatim provider config entity.\n";
}

// Also create OpenStreetMap provider as fallback.
$osm = GeocoderProvider::load('openstreetmap');
if (!$osm) {
  $osm = GeocoderProvider::create([
    'id' => 'openstreetmap',
    'label' => 'OpenStreetMap',
    'plugin' => 'openstreetmap',
    'configuration' => [
      'rootUrl' => 'https://nominatim.openstreetmap.org',
      'userAgent' => 'GenZero-Drupal-Site',
      'referer' => '',
    ],
  ]);
  $osm->save();
  echo "Created OpenStreetMap provider config entity.\n";
}
else {
  echo "OpenStreetMap provider already exists.\n";
}

// Test geocoding.
echo "\nTesting geocoding with Nominatim...\n";
$geocoder = \Drupal::service('geocoder');
$provider_entity = GeocoderProvider::load('nominatim');
$result = $geocoder->geocode('Lisboa, Portugal', [$provider_entity]);
if ($result) {
  foreach ($result as $r) {
    $lat = $r->getCoordinates()->getLatitude();
    $lon = $r->getCoordinates()->getLongitude();
    echo "SUCCESS: $lat, $lon\n";
    break;
  }
}
else {
  echo "FAILED: No results.\n";
}
