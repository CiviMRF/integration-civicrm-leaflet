# Integration between Leaflet Map and CiviCRM

Provides an integration between CiviCRM api and the [leaflet map](https://wordpress.org/plugins/leaflet-map/). Meaning you can create maps from CiviCRM Data.
You can use this plugin with [Connector to CiviCRM with CiviMcRestFace plugin](https://wordpress.org/plugins/connector-civicrm-mcrestface/)
which gives you the ability to connect to an CiviCRM installation on a different server.
Funded by CiviCoop, civiservice.de, Bundesverband Soziokultur e.V.

## Usage
You can use the short code as follows:

```
[leaflet-civicrm-api entity=.. action=... lng_property='longitude' lat_property='latitude' profile=local tooltip_text='Name: {display_name}' ...]
<strong>{display_name}</strong>
[/leaflet-civicrm-api]
```

The following options are possible within the tag:

Option | Required | Default value | Description
------ | ------ | ------ | ------
entity | Yes | | The api entity in CiviCRM.
action | Yes | | The api action in CiviCRM.
profile | Yes | | If you have installed CiviCRM in the same wordpress site then the profile could be `local` otherwise use the name of the [CiviMcRestFace](https://wordpress.org/plugins/connector-civicrm-mcrestface/) connection.
lng_property | Yes | | Fill in the property which holds the longitude value. Not required when `addr_property` is provided.
lat_property | Yes | | Fill in the property which holds the longitude value. Not required when `addr_property` is provided.
addr_property | Only when lng_property and lat_property are not provided | | Fill in the property which holds the address value. This plugin will then convert that address to a point on the map.
tooltip_text | No | _empty_ | The text shown as a tooltip. You can use tokens to replace data in your text. For example "Name: {display_name}"
popup_text | No | _empty_ | The text shown as a popup. This text can also be put inside the shortcode tag. You can use tokens to replace data in your text.
popup_property | No | _empty_ | Property to show in the popup. For example `first_name` (when using the `Contact.get` api).
table_view | No | _empty_ | Add this if you want the popup to hold a table with all the data.
marker_callback | No | CiviCRMLeaflet.defaultMarker | Javascript function to replace the default marker.
tooltip_callback | No | CiviCRMLeaflet.defaultTooltip | Javascript function to replace a custom tooltip text.
popup_callback | No | CiviCRMLeaflet.defaultTooltip | Javascript function to replace a custom tooltip text.

**Tokens**

The tokens are the name of the fields in the api/data processor. So for example the `Contact.get` api returns a field `first_name` and the token `{first_name}` will then be replaced by the content of first name.

## Customizations

**Custom Markers**

You can add your own javascript callback function to replace the default marker. In this function you can generate a dynamic marker based on the data returned from CiviCRM.

Below is an example function.

```javascript

  function myCustomMarker (feature, latlng) {
    // See https://leafletjs.com/reference-1.7.1.html#marker
    // for more information on what kind of markers you can return.
    // For example with a custom logo when the data of contact_type is Individual is shown.
    if (feature.properties.contact_type == 'Individual') {
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
    }
    return L.marker(latlng);
  };

```

**Custom Popup**

You can add your own javascript callback function to replace the default popup function so that you can even more customize the data within the popup.

Below is an example function.

```javascript

  function myCustomPopup (feature, layer) {
    var text = 'Not an Individual';
    if (feature.properties.contact_type == 'Individual') {
      text = 'Individual'
    }
    if (text) {
      layer.bindPopup(text);
    }
  };

```

**Custom Tooltip**

You can add your own javascript callback function to replace the default tooltip function so that you can even more customize the text within the tooltip.

Below is an example function.

```javascript

  function myCustomTooltip (feature, layer) {
    var tooltip = 'Not an Individual';
    if (feature.properties.contact_type == 'Individual') {
      tooltip = 'Individual'
    }
    if (text) {
      layer.bindTooltip(tooltip);
    }
  };

```

**Available hooks**

* `integration_civicrm_leaflet_alter_filter_fields`: which allows you to alter the callback functions to display a filter field. See `integration_civicrm_leaflet.api.php` for an example implementation.


## Funded by

* [CiviCooP](https://www.civicoop.org)
* [Civiservice.de GmbH](https://civiservice.de/)
* [Bundesverband Soziokultur e.V.](https://www.soziokultur.de/)

# Reporting bugs

Bugs can be reported at [Github](https://github.com/CiviMRF/integration-civicrm-leaflet).

# Contributing

The code of this plugin is published and maintained at [Github](https://github.com/CiviMRF/integration-civicrm-leaflet).
The plugin is also published at [Wordpress.org](https://wordpress.org/plugins/integration-civicrm-leaflet)
and this requires that we submit each release to the [Wordpress SVN](https://plugins.svn.wordpress.org/integration-civicrm-leaflet)

**Workflow for development**

1. Fork the repository at Github
1. Create a new branch for the functionality you want to develop, or for the bug you want to fix.
1. Write your code and test it, once you are finished push it to your fork.
1. Create a Pull Request at Github to notify us to merge your changes.

**Workflow for creating a release**

Based on the instruction from [Learn with Daniel](https://learnwithdaniel.com/2019/09/publishing-your-first-wordpress-plugin-with-git-and-svn/)

1. Update `readme.txt` with the new version number (also update the Changelog section)
1. Update `integration-civicrm-leaflet.php` with the new version number
1. Create a new version at [Github](https://github.com/CiviMRF/integration-civicrm-leaflet).
1. To publish the release at Wordpress Plugin directory follow the following steps:
    1. Create a temp directory: `mkdir cintegration-civicrm-leaflet-tmp`
    1. Go into this directory: `cd cintegration-civicrm-leaflet-tmp`
    1. Do an SVN checkout into SVN directory: `svn checkout --depth immediates https://plugins.svn.wordpress.org/integration-civicrm-leaflet svn`
    1. Clone the Github repository into Github directory: `git clone https://github.com/CiviMRF/integration-civicrm-leaflet.git github`
    1. Go into the Github directory: `cd github`
    1. Checkout the created release (in our example 1.0.0): `git checkout 1.0.0`
    1. Go into the svn directory: `cd ../svn`
    1. Copie the files from github to SVN: `rsync -rc --exclude-from="../github/.distignore" "../github/" trunk/ --delete --delete-excluded`
    1. Add the files to SVN: `svn add . --force`
    1. Tag the release in SVN (in our example 1.0.0): `svn cp "trunk" "tags/1.0.0"`
    1. Now submit to the Wordpress SVN with a message: `svn ci -m 'Adding 1.0.0'`


# License

The plugin is licensed under [AGPL-3.0](LICENSE.txt).