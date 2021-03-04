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

class CiviCRMLeafletApiWpcmrf {

  public static function profiles($profiles) {
    if (function_exists('wpcmrf_get_core')) {
      $core = wpcmrf_get_core();
      $wpcmrf_profiles = $core->getConnectionProfiles();
      foreach($wpcmrf_profiles as $profile) {
        $profile_name = 'wpcmrf_profile_'.$profile['id'];
        $profiles[$profile_name] = [
          'title' => $profile['label'],
          'function' => ['CiviCRMLeafletApiWpcmrf', 'api'],
          'profile_id' => $profile['id'],
        ];
      }
    }
    return $profiles;
  }

  public static function api($entity, $action, $params, $options, $profile) {
    $call = wpcmrf_api($entity, $action, $params, $options, $profile);
    if ($call->getStatus() == \CMRF\Core\Call::STATUS_FAILED) {
      return ['error' => 'Could not retrieve data', 'is_error' => '1'];
    }
    return $call->getReply();
  }

}