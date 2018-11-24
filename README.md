# GaladrielMap
This is a web tile map viewer to serve in a weak computers such as RaspberryPi or NAS and use on a thick clients such as tablets and smartphones. Author use it from the wi-fi router/GSM modem under OpenWRT on his sailboat Galadriel.<br>
GaladrielMap designed for use mainly with [GaladrielCache](https://github.com/VladimirKalachikhin/Galadriel-cache), but may be used with any tile OSM-like map sources or file tile cache for explore map. The author is not responsible for the consequences of using the GaladrielMap as navigator!

## v. 0.1

## Features:
1. Stacked maps (with GaladrielCache)
2. Positioning via gpsd
3. Display current (writing now) track file in gpx format
4. Display track/route files in gpx format
5. Control of a GaladrielCache loader
6. Creating a route

## Compatibility
Modern browsers, include mobile.

## Screenshots
 ![s1](screenshots/s1.png) ![s2](screenshots/s2.png) ![s3](screenshots/s3.png) ![s4](screenshots/s4.png)

## Install&configure:
You must have a web server with php support. Just copy.<br>
Paths and other are set and describe in _params.php_

### Dependences
* [Leaflet](https://leafletjs.com/) in _leaflet/_ directory
* [leaflet-realtime](https://github.com/perliedman/leaflet-realtime) as _leaflet-realtime/dist/leaflet-realtime.js_
* [Leaflet.RotatedMarker](https://github.com/bbecquet/Leaflet.RotatedMarker) as _Leaflet.RotatedMarker/leaflet.rotatedMarker.js_
* [L.TileLayer.Mercator](https://github.com/ScanEx/L.TileLayer.Mercator) as _L.TileLayer.Mercator/src/L.TileLayer.Mercator.js_
* [leaflet-sidebar-v2](https://github.com/nickpeihl/leaflet-sidebar-v2) in _leaflet-sidebar-v2/_ directory
<br>For more compability:
* [fetch polyfill](https://github.com/github/fetch/) as _fetch/fetch.js_
* [promise-polyfill](https://github.com/taylorhakes/promise-polyfill) as _promise-polyfill/promise.js_

Create local copy of dependences and/or edit _index.php_

### Thanks
* [leaflet-omnivore](https://github.com/mapbox/leaflet-omnivore) for leaflet-omnivore. This patched to non well-formed gpx files.
* [Metrize Icons by Alessio Atzeni](https://icon-icons.com/pack/Metrize-Icons/1130) for icons.
* [Typicons by Stephen Hutchings](https://icon-icons.com/pack/Typicons/1144) for icons.
* [On/Off FlipSwitch](https://proto.io/freebies/onoff/)

### gpsd
GaladrielMap get position and velosity from gpsd via _askGPSD.php_ service. You may configure _askGPSD.php_ to you gpsd host and port. Default are localhost and 2947 port (default for gpsd).<br>
How to install and configure gpsd see [gpsd pages](http://www.catb.org/gpsd/).

### tracks
You may use gpxlogger app from gpsd-clients packet to logging on your server. GaladrielMap displaying current not well-formed gpx file. Other tracks may be diplayed simultaneously.<br>
Run _chkGPXfiles.php_ in cli to repair non well-formed gpx files.



