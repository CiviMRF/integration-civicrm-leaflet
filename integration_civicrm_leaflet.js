var integrationCiviCRMLeafletClusterLayer;

function IntegrationCiviCRMLeaflet (tooltip_text, popup_text, popup_property, apiSettings, ajaxUrl, name) {

  var config = {
    tooltip_text: window.WPLeafletMapPlugin.unescape(tooltip_text),
    popup_text: window.WPLeafletMapPlugin.unescape(popup_text),
    popup_property: popup_property,
    api: apiSettings,
    ajaxUrl: ajaxUrl,
    name: name
  };

  var map = window.WPLeafletMapPlugin.getCurrentMap();
  if (!integrationCiviCRMLeafletClusterLayer) {
    integrationCiviCRMLeafletClusterLayer = new L.markerClusterGroup();
    map.addLayer(integrationCiviCRMLeafletClusterLayer);
  }

  /**
   * Updates the CiviCRM layer with data from CiviCRM.
   *
   * @param featureCallback
   * @param apiParamCallbak
   */
  this.updateCiviCRMLayer = function(apiParamCallbak, featureCallback, markerCallback) {
    var ajaxData = config.api;
    ajaxData.action = 'integration_civicrm_leaflet_data';
    ajaxData.api_params = apiParamCallbak();
    integrationCiviCRMLeafletClusterLayer.clearLayers();
    window.jQuery.post(config.ajaxUrl, ajaxData, function(response) {
      var geoJsonData = JSON.parse(response);
      var layer = new L.GeoJSON(geoJsonData, {
        "onEachFeature": featureCallback,
        "pointToLayer": markerCallback
      });
      integrationCiviCRMLeafletClusterLayer.addLayer(layer);
    });
  };

  /**
   * Returns the API value for a text field.
   *
   * @param filter
   * @returns {{length}|*|jQuery|string}
   */
   this.civicrm_leaflet_filter_value = function (filter) {
    var val = window.jQuery('#civicrm_filter_'+filter).val();
    if (val.length) {
      return val;
    }
  };

  /**
   * Returns the API filter parameter for checkbox filters.
   * In CiviCRM this should be somethingling [IN => [=1,2,3]]
   *
   * @param filter
   * @returns {{IN: ({length}|*|jQuery)}}
   */
  this.civicrm_leaflet_filter_checkbox_value = function (filter) {
    var selectedValues = window.jQuery("input.civicrm_filter_" + filter + ":checkbox:checked").map(function () {
      return window.jQuery(this).val();
    }).get();
    if (selectedValues.length) {
      return {"IN": selectedValues};
    }
  };

  this.civicrm_leaflet_filter_date_value = function(filter) {

    var val_min = window.jQuery('#civicrm_filter_'+filter+'_min').val();
    var val_max = window.jQuery('#civicrm_filter_'+filter+'_max').val();
    if (val_min.length && val_max.length) {
      return {
        "BETWEEN": [formatDateForCiviCRM(val_min), formatDateForCiviCRM(val_max)]
      };
    } else if (val_min.length) {
      return {
        ">=": formatDateForCiviCRM(val_min)
      };
    } else if (val_max.length) {
      return {
        "<=": formatDateForCiviCRM(val_max)
      };
    }
  };

  /**
   * Display function of feature.
   *
   * @param feature
   * @param layer
   */
  this.defaultFeature = function (feature, layer) {
    var props = feature.properties || {};
    var text = this.template(config.popup_text, feature.properties);
    if (config.popup_property) {
      text = props[config.popup_property];
    }
    if (text) {
      layer.bindPopup(text);
    }
  };

  /**
   * Display function for table feature.
   *
   * @param feature
   * @param layer
   */
  this.tableFeature = function  (feature, layer) {
    var props = feature.properties || {};
    var text= window.WPLeafletMapPlugin.propsToTable(props);
    if (text) {
      layer.bindPopup(text);
    }
  };

  /**
   * Display function for table feature.
   *
   * @param feature
   * @param layer
   */
  this.defaultTooltip = function  (feature, layer) {
    if (config.tooltip_text) {
      var tooltip = this.template(config.tooltip_text, feature.properties);
      layer.bindTooltip(tooltip);
    }
  };

  /**
   * Returns the default marker for the map.
   *
   * @param feature
   * @param latlng
   * @returns {*}
   */
  this.defaultMarker = function (feature, latlng) {
    // See https://leafletjs.com/reference-1.7.1.html#marker
    // for more information on what kind of markers you can return.
    // For example with a custom logo when the data of contact_type is Individual is shown.
    /*if (feature.properties.contact_type == 'Individual') {
      var customIcon = L.icon({
        iconUrl: 'https://leafletjs.com/examples/custom-icons/leaf-orange.png',
        shadowUrl: 'https://leafletjs.com/examples/custom-icons/leaf-shadow.png',
        iconSize:     [38, 95],
        shadowSize:   [50, 64],
        iconAnchor:   [22, 94],
        shadowAnchor: [4, 62],
        popupAnchor:  [-3, -76]
      });
      return L.marker(latlng, {icon: customIcon});
    }*/
    return L.marker(latlng);
  };

  /**
   * Function to convert a date value to CiviCRM's format (yyyymmdd)
   *
   * @param dateValue
   * @returns {string}
   */
  var formatDateForCiviCRM = function (dateValue) {
    var dateObject = new Date(dateValue);
    var strDate = (dateObject.getDate()).toString().padStart(2, '0');
    var strMonth = (dateObject.getMonth() +1).toString().padStart(2, '0');
    var strYear = (dateObject.getFullYear()).toString().padStart(4, '0');
    return strYear+strMonth+strDate;
  };

  var templateRe = /\{ *(.*?) *\}/g;

  /**
   * It interpolates variables in curly brackets (regex above)
   *
   * ex: "Property Value: {property_key}"
   *
   * @param {string} str
   * @param {object} data e.g. feature.properties
   */
  this.template = function (str, data) {
    if (data == null) {
      return str;
    }

    return str.replace(templateRe, function (match, key) {
      var obj = this.liquid(key);
      var value = this.parseKey(data, obj.key);
      if (value === undefined && obj.default) {
        return obj.default;
      } else if (value === undefined && data.hasOwnProperty(key)) {
        return '';
      } else if (value === undefined) {
        return match;
      }
      return value;
    }.bind(this));
  };

  /**
   * parses liquid tags from a string
   *
   * @param {string} str
   */
  this.liquid = function (str) {
    var tags = str.split(' | ');
    var obj = {};

    // removes initial variable from array
    var key = tags.shift();

    for (var i = 0, len = tags.length; i < len; i++) {
      var tag = tags[i].split(': ');
      var tagName = tag.shift();
      var tagValue = tag.join(': ') || true;

      obj[tagName] = tagValue;
    }

    // always preserve the original string
    obj.key = key;

    return obj;
  };

  /**
   * It uses strToPath to access a possibly nested path value
   *
   * @param {object} obj
   * @param {string} key
   */
  this.parseKey = function (obj, key) {
    var arr = this.strToPath(unescape(key));
    var value = obj;

    for (var i = 0, len = arr.length; i < len; i++) {
      value = value[arr[i]];
      if (!value) {
        return undefined;
      }
    }

    return value;
  };

  /**
   * Converts nested object keys to array
   *
   * ex: `this.that['and'].theOther[4]` ->
   *     ['this', 'that', 'and', 'theOther', '4']
   * @param {string} key
   */
  this.strToPath = function (key) {
    if (key == null) {
      return [];
    }
    /** used in strToPath */
    var strToPathRe = /[.‘’'“”"\[\]]+/g;
    var input = key.split(strToPathRe);
    var output = [];

    // failsafe for all empty strings;
    // mostly catches brackets at the end of a string
    for (var i = 0, len = input.length; i < len; i++) {
      if (input[i] !== '') {
        output.push(input[i]);
      }
    }

    return output;
  };
}