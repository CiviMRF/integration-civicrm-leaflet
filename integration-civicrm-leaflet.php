<?php
/*
Plugin Name: Integration between Leaflet Map and CiviCRM
Description: Integrates data from the CiviCRM api into a Leaflet Map. You can use this plugin with Connector to CiviCRM with CiviMcRestFace (https://wordpress.org/plugins/connector-civicrm-mcrestface/)
Version:     1.0.5
Author:      Jaap Jansma
License:     AGPL3
License URI: https://www.gnu.org/licenses/agpl-3.0.html
Text Domain: integration-civicrm-leaflet
*/

/**
 * Copyright (C) 2021  Jaap Jansma (jaap.jansma@civicoop.org)
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

defined('ABSPATH') or die("Cannot access pages directly.");
define('INTEGRATION_LEAFLET_CIVICRM_ROOT_PATH', plugin_dir_path(__FILE__));

add_action('init', function() {
  require_once INTEGRATION_LEAFLET_CIVICRM_ROOT_PATH . "CiviCRM/class.civicrm-api-local.php";
  require_once INTEGRATION_LEAFLET_CIVICRM_ROOT_PATH . "CiviCRM/class.civicrm-api-wpcmrf.php";
  require_once INTEGRATION_LEAFLET_CIVICRM_ROOT_PATH . "shortcodes/class.civicrm-api-filters-shortcode.php";
  require_once INTEGRATION_LEAFLET_CIVICRM_ROOT_PATH . "shortcodes/class.civicrm-api-shortcode.php";
  add_shortcode('leaflet-civicrm-api', array('Leaflet_CiviCRM_Api_Shortcode', 'shortcode'));
  add_shortcode('leaflet-civicrm-api-combined-filter-button', array('Leaflet_CiviCRM_Api_Filters_Shortcode', 'shortcode'));
  add_action( 'wp_ajax_integration_civicrm_leaflet_data', 'integration_civicrm_leaflet_data' );
  add_action( 'wp_ajax_nopriv_integration_civicrm_leaflet_data', 'integration_civicrm_leaflet_data' );
});

add_action( 'wp_enqueue_scripts', 'integration_civicrm_leaflet_enqueue_scripts');

function integration_civicrm_leaflet_enqueue_scripts() {
  // Add the JS and css for leaflet cluster markers.
  wp_enqueue_style('leaflet_clustermarker_stylesheet_default', plugin_dir_url( __FILE__ ).'packages/Leaflet.markercluster-1.4.1/dist/MarkerCluster.Default.css', Array('leaflet_stylesheet'), null, false);
  wp_enqueue_style('leaflet_clustermarker_stylesheet', plugin_dir_url( __FILE__ ).'packages/Leaflet.markercluster-1.4.1/dist/MarkerCluster.css', Array('leaflet_stylesheet'), null, false);
  wp_enqueue_style( 'integration_civicrm_leaflet_stylesheet', plugin_dir_url( __FILE__ ) . 'integration_civicrm_leaflet.css', array(), null, false );
  wp_enqueue_script('leaflet_clustermarker_js', plugin_dir_url( __FILE__ ).'packages/Leaflet.markercluster-1.4.1/dist/leaflet.markercluster.js', Array('leaflet_js'), null, true);
  wp_enqueue_script( 'integration_civicrm_leaflet_js', plugin_dir_url( __FILE__ ) . 'integration_civicrm_leaflet.js', array( 'jquery', 'leaflet_js' ), null, false );
}

function integration_civicrm_leaflet_data() {
  $lat_property = sanitize_text_field($_POST['lat_property']);
  $lng_property = sanitize_text_field($_POST['lng_property']);
  $addr_property = sanitize_text_field($_POST['addr_property']);
  $apiEntity = sanitize_text_field($_POST['api_entity']);
  $apiAction = sanitize_text_field($_POST['api_action']);
  $apiProfileId = sanitize_text_field($_POST['api_profile_id']);
  $apiOptions['limit'] = 0;
  $apiOptions['cache'] = sanitize_text_field($_POST['cache']);
  $apiParams = isset($_POST['api_params']) ? integration_civicrm_leaflet_recursive_sanitize_api_params($_POST['api_params']) : array();

  $reply = integration_civicrm_leaflet_api($apiEntity, $apiAction, $apiParams, $apiOptions, $apiProfileId);
  $features = [];
  if (isset($reply['values']) && is_array($reply['values'])) {
    foreach ($reply['values'] as $row) {
      if ($lng_property && !empty($row[$lng_property]) && $lat_property && !empty($row[$lat_property])) {
        $lng = $row[$lng_property];
        $lat = $row[$lat_property];
      } elseif ($addr_property && !empty($row[$addr_property])) {
        include_once LEAFLET_MAP__PLUGIN_DIR . 'class.geocoder.php';
        $location = new Leaflet_Geocoder($row[$addr_property]);
        $lat = $location->lat;
        $lng = $location->lng;
      }
      if (empty($lng) || empty($lat)) {
        continue;
      }
      $feature = [
        'type' => 'Feature',
        'properties' => $row,
        'geometry' => [
          'type' => 'Point',
          'coordinates' => [$lng, $lat]
        ]
      ];
      $features[] = $feature;
    }
  }
  $return['type'] = 'FeatureCollection';
  $return['features'] = $features;
  echo json_encode($return, JSON_PRETTY_PRINT);
  wp_die();
}

/**
 * Recursive sanitation for an array
 * Taken from: https://wordpress.stackexchange.com/a/255238
 *
 * @param $array
 * @return mixed
 */
function integration_civicrm_leaflet_recursive_sanitize_api_params($api_params) {
  foreach ( $api_params as $key => &$value ) {
    if ( is_array( $value ) ) {
      $value = integration_civicrm_leaflet_recursive_sanitize_api_params($value);
    }
    else {
      $value = sanitize_text_field( $value );
    }
  }

  return $api_params;
}

/**
 * Returns a list of possible connection profiles.
 * @return array
 */
function integration_civicrm_leaflet_get_profiles() {
  static $profiles = null;
  if (is_array($profiles)) {
    return $profiles;
  }

  $profiles = array();
  $profiles = CiviCRMLeafletApiLocal::profiles($profiles);
  $profiles = CiviCRMLeafletApiWpcmrf::profiles($profiles);

  $profiles = apply_filters('integration_civicrm_leaflet_get_profiles', $profiles);
  return $profiles;
}

function integration_civicrm_leaflet_api($entity, $action, $params, $options, $profile_id) {
  $profiles = integration_civicrm_leaflet_get_profiles();
  if (!isset($profiles[$profile_id])) {
    return ['error' => 'Invalid connection', 'is_error' => '1'];
  }
  $func = $profiles[$profile_id]['function'];
  return call_user_func($func, $entity, $action, $params, $options, $profiles[$profile_id]['profile_id']);
}