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

class Leaflet_CiviCRM_Api_Filters_Shortcode {

  protected static $names = [];

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
    if ($atts) {
      extract($atts);
    }

    $filter_header = empty($filter_header) ? '' : $filter_header;
    $filter_button_label = empty($filter_button_label) ? 'Filter' : $filter_button_label;

    ob_start();
    ?>
    <script>
      jQuery(function($) {
        <?php foreach(self::$names as $name) { ?>
        $('#civicrm_leaflet_map_filter_<?php echo esc_js($name); ?>').hide()
        <?php } ?>

        $('#civicrm_leaflet_map_filter').on('click dblclick', function () {
          <?php foreach(self::$names as $name) { ?>
          UpdateCiviCRMLeafletMapPlugin<?php echo esc_js($name); ?>(CiviCRMLeafletMap<?php echo esc_js($name); ?>);
          <?php } ?>
        });
      });
    </script>
    <?php
    $buffer = ob_get_clean();
    if ($filter_header) {
      $buffer .= esc_html($filter_header);
    }
    $buffer .= '<input type="submit" id="civicrm_leaflet_map_filter" value="' . esc_attr($filter_button_label) . '" />';
    return $buffer;
  }

  public static function addFilters($name) {
    self::$names[] = $name;
  }

  /**
   * Instantiate class and get HTML for shortcode
   *
   * @param array|string|null $atts    string|array
   * @param string|null       $content Optional
   *
   * @return string HTML
   */
  public static function shortcode($atts = '', $content = null)
  {
    $instance = new Leaflet_CiviCRM_Api_Filters_Shortcode();

    // swap sequential array with associative array
    // this enables assumed-boolean attributes,
    // like: [leaflet-marker draggable svg]
    // meaning draggable=1 svg=1
    // and: [leaflet-marker !doubleClickZoom !boxZoom]
    // meaning doubleClickZoom=0 boxZoom=0
    if (!empty($atts)) {
      foreach($atts as $k => $v) {
        if (
          is_numeric($k) &&
          !key_exists($v, $atts) &&
          !!$v
        ) {
          // false if starts with !, else true
          if ($v[0] === '!') {
            $k = substr($v, 1);
            $v = 0;
          } else {
            $k = $v;
            $v = 1;
          }
          $atts[$k] = $v;
        }
        // change hyphens to underscores for `extract()`
        if (strpos($k, '-')) {
          $k = str_replace('-', '_', $k);
          $atts[$k] = $v;
        }
      }
    }

    return $instance->getHTML($atts, $content);
  }

}