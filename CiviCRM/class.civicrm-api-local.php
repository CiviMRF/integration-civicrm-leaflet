<?php
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

class CiviCRMLeafletApiLocal {

  public static function profiles($profiles) {
    if (function_exists('civi_wp')) {
      $profiles['local'] = [
        'title' => __('Local CiviCRM'),
        'function' => ['CiviCRMLeafletApiLocal', 'api'],
        'profile_id' => '',
      ];
    }
    return $profiles;
  }

  public static function api($entity, $action, $params, $options, $profile) {
    if (empty($entity) || empty($action) || !is_array($params)) {
      throw new Exception('One of given parameters is empty.');
    }

    if (!civi_wp()->initialize()) {
      return ['error' => 'CiviCRM not Initialized', 'is_error' => '1'];
    }

    /*
     * Copied from CiviCRM invoke function as there is a problem with timezones
     * when the local connection is used.
     *
     * CRM-12523
     * WordPress has it's own timezone calculations
     * CiviCRM relies on the php default timezone which WP
     * overrides with UTC in wp-settings.php
     */
    $wpBaseTimezone = date_default_timezone_get();
    $wpUserTimezone = get_option('timezone_string');
    if ($wpUserTimezone) {
      date_default_timezone_set($wpUserTimezone);
      \CRM_Core_Config::singleton()->userSystem->setMySQLTimeZone();
    }

    try {
      if (!empty($options)) {
        $params['options'] = $options;
      }
      $result = civicrm_api3($entity, $action, $params);
    } catch (CiviCRM_API3_Exception $e) {
      $error = $e->getMessage() . '<br><br><pre>' . $e->getTraceAsString() . '</pre>';
      $result = ['error' => $error, 'is_error' => '1'];
    }

    /*
     * Reset the timezone back the original setting.
     */
    if ($wpBaseTimezone) {
      date_default_timezone_set($wpBaseTimezone);
    }

    return $result;
  }

}