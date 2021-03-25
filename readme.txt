=== Integration between Leaflet Map and CiviCRM ===
Contributors: jaapjansma
Donate link: https://github.com/CiviMRF/integration-civicrm-leaflet
Tags: leaflet, CiviCRM, map, leaflet map, api, connector, rest
Requires at least: 5.2
Tested up to: 5.6
Requires PHP: 7.2
Stable tag: 1.0.4
License: AGPL-3.0

Provides an integration between CiviCRM api and the [leaflet map](https://wordpress.org/plugins/leaflet-map/). Meaning you can create maps from CiviCRM Data.
You can use this plugin with [Connector to CiviCRM with CiviMcRestFace plugin](https://wordpress.org/plugins/connector-civicrm-mcrestface/)
which gives you the ability to connect to an CiviCRM installation on a different server.
Funded by CiviCoop, civiservice.de, Bundesverband Soziokultur e.V.

== Description ==

Provides an integration between CiviCRM api and the [leaflet map](https://wordpress.org/plugins/leaflet-map/). Meaning you can create maps from CiviCRM Data.
You can use this plugin with [Connector to CiviCRM with CiviMcRestFace plugin](https://wordpress.org/plugins/connector-civicrm-mcrestface/)
which gives you the ability to connect to an CiviCRM installation on a different server.

You can use the short code as follows:

  [leaflet-civicrm-api entity=.. action=... lng_property='longitude' lat_property='latitude' profile=local tooltip_text='Name: {display_name}' ...]
    <strong>{display_name}</strong>
  [/leaflet-civicrm-api]

Add the short code `[leaflet-map]` to show the map.
See https://wordpress.org/plugins/leaflet-map/ on how you can configure the `[leaflet-map]` short code.

For more documentation see: [README.md](https://github.com/CiviMRF/integration-civicrm-leaflet/blob/main/README.md)

**Funded by**

* [CiviCooP](https://www.civicoop.org)
* [Civiservice.de GmbH](https://civiservice.de/)
* [Bundesverband Soziokultur e.V.](https://www.soziokultur.de/)

== Changelog ==

1.0.4: Fixed issue with CiviMcRestFace.
1.0.3: Fixed issue with jQuery.
1.0.2: Fixed issue with jQuery.
1.0.1: Check checkbox filters by default.
1.0.0: First version.