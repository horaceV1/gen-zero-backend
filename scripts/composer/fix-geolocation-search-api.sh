#!/bin/bash
# Remove duplicate search_api_location_views classes from geolocation_search_api.
# These files declare classes in the Drupal\search_api_location_views namespace
# which conflicts with the actual search_api_location_views module.
# See: https://www.drupal.org/project/geolocation/issues/
DIR="web/modules/contrib/geolocation/modules/geolocation_search_api/src/Plugin/views/argument"
if [ -d "$DIR" ]; then
  rm -rf "$DIR"
  echo "Removed duplicate search_api_location_views classes from geolocation_search_api."
fi
