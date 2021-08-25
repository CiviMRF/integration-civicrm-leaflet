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

require_once LEAFLET_MAP__PLUGIN_DIR . 'shortcodes/class.shortcode.php';

/**
 * GeoJSON Shortcode Class
 */
class Leaflet_CiviCRM_Api_Shortcode extends Leaflet_Shortcode {

  /**
   * @var int
   */
  private static $_id = 0;

  /**
   * Generate HTML from the shortcode
   * Maybe won't always be required
   *
   * @param array $atts string
   * @param string $content Optional
   *
   * @return string (typically, return a script tag with Leaflet logic)
   * @since 2.8.2
   *
   */
  protected function getHTML($atts = '', $content = NULL) {
    // need to get the called class to extend above variables
    self::$_id ++;

    if ($atts) {
      extract($atts);
    }

    $cache = empty($cache) ? '1 minute' : $cache;
    $entity = empty($entity) ? '' : $entity;
    $action = empty($action) ? '' : $action;
    $profile = empty($profile) ? '' : $profile;
    $filter_header = empty($filter_header) ? '' : $filter_header;
    $filter_button_label = empty($filter_button_label) ? 'Filter' : $filter_button_label;
    $name = empty($name) ? 'source_'.self::$_id : $name;
    $name = sanitize_key($name);
    $icon = empty($icon) ? '' : json_decode(html_entity_decode($icon, ENT_COMPAT | ENT_HTML5), true);
    $icon_url = empty($icon_url) ? '' : $icon_url;
    if (empty($entity) || empty($action) || empty($profile)) {
      return "";
    }

    $lat_property = empty($lat_property) ? '' : $lat_property;
    $lng_property = empty($lng_property) ? '' : $lng_property;
    $addr_property = empty($addr_property) ? '' : $addr_property;
    if ((empty($lng_property) || empty($lat_property)) && empty($addr_property)) {
      return "";
    }

    $filters = [];
    $getFieldsParams['api_action'] = $action;
    $getFieldsOptions = [];
    $getFieldsOptions['cache'] = $cache;
    $fields = integration_civicrm_leaflet_api($entity, 'getfields', $getFieldsParams , $getFieldsOptions, $profile);
    if (isset($fields['values']) && is_array($fields['values'])) {
      foreach ($fields['values'] as $field) {
          if (isset($field['api.filter']) && $field['api.filter']) {
              $filter = $field;
              $filter['api_name'] = $filter['name'];
              $filter['name'] = $name . '_' . $filter['name'];
              $filter['input_callback'] = [$this, 'displayFilterField'];
              $filter['js_value_function'] = 'CiviCRMLeaflet.civicrm_leaflet_filter_value';
              if (isset($filter['options']) && is_array($filter['options'])) {
                $filter['input_callback'] = [$this, 'displayCheckboxFilterField'];
                $filter['js_value_function'] = 'CiviCRMLeaflet.civicrm_leaflet_filter_checkbox_value';
              } elseif ($filter['data_type'] == 'Date') {
                $filter['input_callback'] = [$this, 'displayDateFilterField'];
                $filter['js_value_function'] = 'CiviCRMLeaflet.civicrm_leaflet_filter_date_value';
              }
              $filters[$field['name']] = $filter;
          }
      }
    }
    $filters = apply_filters('integration_civicrm_leaflet_alter_filter_fields', $filters, $name);

    if ($content) {
      $content = str_replace(array("\r\n", "\n", "\r"), '<br>', $content);
      $content = htmlspecialchars($content);
    }
    // shortcode content becomes popup text
    $content_text = empty($content) ? '' : $content;
    // alternatively, the popup_text attribute works as popup text
    $popup_text = empty($popup_text) ? '' : $popup_text;
    // choose which one takes priority (content_text)
    $popup_text = empty($content_text) ? $popup_text : $content_text;
    $popup_text = trim($popup_text);
    $popup_property = empty($popup_property) ? '' : $popup_property;
    $tooltip_text = empty($tooltip_text) ? '' : $tooltip_text;
    $tooltip_text = trim($tooltip_text);
    $table_view = filter_var(empty($table_view) ? 0 : $table_view, FILTER_VALIDATE_INT);
    $tooltipCallback = empty($tooltip_callback) ? 'CiviCRMLeaflet.defaultTooltip': $tooltip_callback;
    if (empty($icon) && !empty($icon_url)) {
      $icon = ['iconUrl' => $icon_url];
    }
    if (!empty($icon)) {
      $marker_callback = 'customIcon';
    }
    $markerCallback = empty($marker_callback) ? 'CiviCRMLeaflet.defaultMarker': $marker_callback;
    if ($table_view) {
      $featureCallback = empty($popup_callback) ? 'CiviCRMLeaflet.tableFeature': $popup_callback;
    } else {
      $featureCallback = empty($popup_callback) ? 'CiviCRMLeaflet.defaultFeature': $popup_callback;
    }

    ob_start();
    ?>
    <script>
      window.WPLeafletMapPlugin = window.WPLeafletMapPlugin || [];

      function CiviCRMLeafletMapPlugin<?php echo esc_js($name); ?>(name) {
        var apiCall = {
          'api_entity': '<?php echo esc_js($entity); ?>',
          'api_action': '<?php echo esc_js($action); ?>',
          'api_profile_id': '<?php echo esc_js($profile); ?>',
          'cache': '<?php echo esc_js($cache); ?>',
          'lng_property': '<?php echo esc_js($lng_property); ?>',
          'lat_property': '<?php echo esc_js($lat_property); ?>',
          'addr_property': '<?php echo esc_js($addr_property); ?>',
          'name': '<?php echo esc_js($name); ?>'
        };

        var CiviCRMLeaflet = new IntegrationCiviCRMLeaflet(
            '<?php echo esc_js($tooltip_text); ?>',
            '<?php echo esc_js($popup_text); ?>',
            '<?php echo esc_js($popup_property); ?>',
            apiCall,
            '<?php echo esc_js(admin_url('admin-ajax.php')); ?>',
            name
        );

        return CiviCRMLeaflet;
      }

      function UpdateCiviCRMLeafletMapPlugin<?php echo esc_js($name); ?>(CiviCRMLeaflet) {
      function featureCallback(feature, layer) {
        <?php echo esc_js($featureCallback); ?>(feature, layer);
        <?php echo esc_js($tooltipCallback); ?>(feature, layer);
      }

      function apiParamCallback() {
        var filters = {
          <?php
          $i = 0;
          foreach ($filters as $filterName => $filter) {
            $suffix = ",";
            if ($i === count($filters)) {
              $suffix = "";
            }
            echo '"' . esc_js($filter['name']) . '": ' . esc_js($filter['js_value_function']) . esc_js($suffix);
            $i++;
          } ?>
        };

        var filterApiNames = {
          <?php
          $i = 0;
          foreach ($filters as $filterName => $filter) {
            $suffix = ",";
            if ($i === count($filters)) {
              $suffix = "";
            }
            echo '"' . esc_js($filter['name']) . '": "' . esc_js($filter['api_name']) . '"' . esc_js($suffix);
            $i++;
          } ?>
        };

        var api_params = {};
        for (var filterName in filters) {
          var jsValueFunction = filters[filterName];
          var filterValue = jsValueFunction(filterName);
          var filterApiName = filterApiNames[filterName];
          if (filterValue !== undefined) {
            api_params[filterApiName] = filterValue;
          }
        }

        return api_params;
      }

      function customIcon (feature, latlng) {
        <?php if (!empty($icon)) { ?>
            return L.marker(latlng, {"icon": L.icon(<?php echo json_encode($icon, JSON_PRETTY_PRINT); ?>)});
        <?php } else { ?>
            return L.marker(latlng);
        <?php } ?>
      }

      CiviCRMLeaflet.updateCiviCRMLayer(apiParamCallback, featureCallback, <?php echo esc_js($markerCallback); ?>);
    }

    var CiviCRMLeafletMap<?php echo esc_js($name); ?>;
    window.WPLeafletMapPlugin.push(function () {
      CiviCRMLeafletMap<?php echo esc_js($name); ?> = CiviCRMLeafletMapPlugin<?php echo esc_js($name); ?>('<?php echo esc_js($name); ?>');
      UpdateCiviCRMLeafletMapPlugin<?php echo esc_js($name); ?>(CiviCRMLeafletMap<?php echo esc_js($name); ?>);
    });

    jQuery(function($) {
      $('#civicrm_leaflet_map_filter_<?php echo esc_js($name); ?>').on('click dblclick', function () {
        UpdateCiviCRMLeafletMapPlugin<?php echo esc_js($name); ?>(CiviCRMLeafletMap<?php echo esc_js($name); ?>);
      });
    });
    </script>
    <?php
    $buffer = ob_get_clean();
    if (count($filters)) {
        $buffer .= '<div id="filter_' . esc_attr($name).'">';
        if ($filter_header) {
            $buffer .= $filter_header;
        }
        foreach($filters as $filter) {
            $buffer .= '<div class="civicrm_leaflet_filter" id="civicrm_leaflet_filter_wrapper_'.esc_attr($filter['name']).'">';
            $buffer .= call_user_func($filter['input_callback'], $filter);
            $buffer .= '</div>';
        }
        $buffer .= '<input type="submit" id="civicrm_leaflet_map_filter_' . esc_attr($name) . '" value="' . esc_attr($filter_button_label) . '" />';
        $buffer .= '</div>';

      Leaflet_CiviCRM_Api_Filters_Shortcode::addFilters($name);
    }
    return $buffer;
  }

  /**
   * Default display handler for a filter.
   *
   * @param $filter
   * @return false|string
   */
  protected function displayFilterField($filter) {
    $type = 'text';
    if ($filter['data_type'] == 'Int') {
        $type = 'number';
    }
    if (!isset($filter['default'])) {
        $filter['default'] = '';
    }
    ob_start();
    ?>
    <label class="" for="civicrm_filter_<?php echo esc_attr($filter['name']); ?>"><?php echo esc_html($filter['title']); ?></label>
    <input type="<?php echo esc_attr($type); ?>" id="civicrm_filter_<?php echo esc_attr($filter['name']); ?>" name="civicrm_filter_<?php echo esc_attr($filter['name']); ?>" value="<?php echo esc_attr($filter['default']); ?>">
    <?php
    return ob_get_clean();
  }

  /**
   * Display handler for checkbox filters.
   *
   * @param $filter
   * @return false|string
   */
  protected function displayCheckboxFilterField($filter) {
    ob_start();
    $i = 0;
    ?><label class=""><?php echo esc_html($filter['title']); ?></label><?php
    ?><span class="civicrm_leaflet_filter_checkbox"><?php
    foreach($filter['options'] as $value => $label) {
        $i ++;
        $class = "civicrm_leaflet_filter_checkbox_item";
        if ($i == 1) { $class .= " first"; }
        if ($i == count($filter['options'])) { $class .= " last"; }
        $checked = 'checked="checked"';
        ?><span class="<?php echo esc_attr($class); ?>">
        <label>
        <input type="checkbox" class="civicrm_filter_<?php echo esc_attr($filter['name']); ?>" <?php echo $checked; ?>" id="civicrm_filter_<?php echo esc_attr($filter['name']); ?>_<?php echo esc_attr($value); ?>" name="civicrm_filter_<?php echo esc_attr($filter['name']); ?>[]" value="<?php echo esc_attr($value); ?>">
        <span class="civicrm_leaflet_filter_checkbox_item_label"><?php echo esc_html($label); ?></span>
        </label>
        </span><?php
    }
    ?></span><?php
    return ob_get_clean();
  }

  /**
   * Display handler for a date filter.
   *
   * @param $filter
   * @return false|string
   */
  protected function displayDateFilterField($filter) {
    ob_start();
    ?>
      <label class=""><?php echo esc_html($filter['title']); ?></label>
      <input type="date" id="civicrm_filter_<?php echo esc_attr($filter['name']); ?>_min" name="civicrm_filter_<?php echo esc_attr($filter['name']); ?>_min">
      &nbsp;-&nbsp;
      <input type="date" id="civicrm_filter_<?php echo esc_attr($filter['name']); ?>_max" name="civicrm_filter_<?php echo esc_attr($filter['name']); ?>_max">
    <?php
    return ob_get_clean();
  }
}