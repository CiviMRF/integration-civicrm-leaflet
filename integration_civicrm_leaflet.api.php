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

/** Hooks provided by this plugin */

add_filter('integration_civicrm_leaflet_alter_filter_fields', function($filters, $name) {
  // Add your own custom filter handlers.
  // Each filter has a input_callback which is responsible for rendering the form elements
  // And each filter has a js_value_function which is responsible for returning the
  // parameters for the api call.
  foreach($filters as $filer_name => $filter) {
    if (isset($filter['options']) && is_array($filter['options'])) {
      $filters[$filer_name]['input_callback'] = 'example_display_option_field_callback';
      $filters[$filer_name]['js_value_function'] = 'example_option_field_js_value_function_'.$filter['name'];
    }
  }
}, 10, 2);

/**
 * @param $filter
 * @return string
 */
function example_display_option_field_callback($filter) {
  ob_start();
  $i = 0;
  ?><span class="civicrm_leaflet_filter_checkbox"><?php
  foreach($filter['options'] as $value => $label) {
    $i ++;
    $class = "civicrm_leaflet_filter_checkbox_item";
    if ($i == 1) { $class .= " first"; }
    if ($i == count($filter['options'])) { $class .= " last"; }
    ?><span class="<?php echo esc_attr($class); ?>">
    <label>
        <input type="checkbox" id="civicrm_filter_<?php echo esc_attr($filter['name']); ?>_<?php echo esc_attr($value); ?>" name="civicrm_filter_<?php echo esc_attr($filter['name']); ?>[]" value="<?php echo esc_attr($value); ?>">
        <span class="civicrm_leaflet_filter_checkbox_item_label"><?php echo esc_html($label); ?></span>
    </label>
    </span><?php
  }
  ?>
  </span>
  <script type="text/javascript">
      /*
       * Return the selected value as an json object or undefined when filter is not set.
       *
       * @param String the name of the filter
       * @return undefined|mixed
       *   Return undefined when filter is not set
       *   Otherwise return the value for the api filter.
       */
      function example_option_field_js_value_function_<?php echo esc_js($filter['name']); ?>(filter) {
        var selectedValues = $("#input.civicrm_filter_<?php echo esc_attr($filter['name']); ?>:checkbox:checked").map(function(){
          return $(this).val();
        }).get();
        if (selectedValues.length) {
          return {"IN": selectedValues};
        }
      }
  </script>
  <?php
  return ob_get_clean();
}