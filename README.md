# GaladrielMap [![License: CC BY-SA 4.0](https://img.shields.io/badge/License-CC%20BY--SA%204.0-lightgrey.svg)](https://creativecommons.org/licenses/by-sa/4.0/)
This is a web application -- the tiles map viewer. The application can be placed on a weak server such as RaspberryPi or NAS and used on full clients such as tablets and smartphones. Only browser need.  
It is assumed that the application is used in the onboard local area network of the boat or camper. The author uses it from the [wi-fi router/GSM modem under OpenWRT](https://github.com/VladimirKalachikhin/MT7620_openwrt_firmware) as a server on his sailboat "Galadriel".  
The GaladrielMap designed for use mainly with [GaladrielCache](https://github.com/VladimirKalachikhin/Galadriel-cache) but may be used with any tile OSM-like map sources or file tile cache for exploring the map.  
The GaladrielMap created with use a lot of famous projects, so don't forget to install [dependenses](#dependences-and-thanks).  
The author is not responsible for the consequences of using the GaladrielMap as a navigator!

## v. 1.4
 ![screen](screenshots/s10.png) 

## Features:
1. Both raster and vector tiles support.
2. English or Russian interface, dependent of browser language settings
3. View one OSM- or mapbox-like on-line map or  
4. with [GaladrielCache](https://github.com/VladimirKalachikhin/Galadriel-cache) some a stacked maps  
[Open Sea Map](http://www.openseamap.org/)  
[Open Topo Map](https://opentopomap.org/about)  
 or any number of other maps:  
 ![stacked maps](screenshots/s1.png)<br><br>
 
5. Positioning via [gpsd](https://gpsd.io/) and display current (writing now) track file in gpx format:  
 ![Positioning](screenshots/s2.png)<br><br>
 
6. Display routes and POIs files in gpx, kml and csv format:  
 ![Display routes and POIs](screenshots/s5.png)<br><br>
 
7. Creating a route localy and save it to the server in gpx format (description below):  
 ![Creating a route](screenshots/s3.png)<br><br>
 
8. Exchange coordinates via clipboard (see screenshot above and description below)  

9.  Weather forecast from [Thomas Krüger Weather Service](http://weather.openportguide.de/index.php/en/) (with GaladrielCache v.1.3 or above)  
 ![Weather forecast](screenshots/s8.png)<br><br>
 
10. Display AIS info:  
 ![AIS info](screenshots/s9-1.png)<br>
Displaying AIS data is disabled by default, so you must enable it by uncomment string with $aisServerURI variable in _params.php_. 
 <br>
 
11. Control the GaladrielCache Loader:   
 ![Control Loader](screenshots/s4.png)<br><br>
 
12. Dashboard.
 _dashboard.php_ - the separate app to display some instruments if it is in your board network, on weak (and/or old) devices, such as E-ink readers, for example. Displayed velocity, depth and true and magnetic heading.   
 ![Dashboard velocity](screenshots/s6.jpg)<br>
 ![Dashboard heading](screenshots/s7.jpg)<br>
 ![Dashboard depth](screenshots/s11.jpg)<br>
 The Dashboard allows you to set a signal for dangerous events, such as shallow or speed. Set up your browser to allow sound signal.  
 ![Dashboard alarm](screenshots/s12.jpg)<br>
 No fanciful javascript, no fanciful css.  

## Compatibility
Linux. Modern browsers include mobile.

## Demo
No live demo, but ready to use [virtual machine image](https://github.com/VladimirKalachikhin/GaladrielMap-Demo-image) exists.

## Install&configure:
You must have a web server under Linux with php support. Just copy app, dependences and set paths.  
Paths and other are set and describe in _params.php_  
Additionally, you may need to set parameters in files  _askGPSD.php_ and _askAIS.php_ .

## Dependences and thanks
* [Leaflet](https://leafletjs.com/) in _leaflet/_ directory
* [Leaflet.RotatedMarker](https://github.com/bbecquet/Leaflet.RotatedMarker) as _Leaflet.RotatedMarker/leaflet.rotatedMarker.js_
* [L.TileLayer.Mercator](https://github.com/ScanEx/L.TileLayer.Mercator) as _L.TileLayer.Mercator/src/L.TileLayer.Mercator.js_
* [leaflet-sidebar-v2](https://github.com/nickpeihl/leaflet-sidebar-v2) in _leaflet-sidebar-v2/_ directory
* [Leaflet.Editable](https://github.com/Leaflet/Leaflet.Editable) in _Leaflet.Editable/_ directory
* [Leaflet Measure Path](https://github.com/ProminentEdge/leaflet-measure-path) in _leaflet-measure-path/_ directory
* [supercluster](https://github.com/mapbox/supercluster) as _supercluster/supercluster.js_
* [Coordinate Parser](https://github.com/servant-of-god/coordinate-parser) in _coordinate-parser/_ directory
* [gpsdAISd](https://github.com/VladimirKalachikhin/gpsdAISd) in _gpsdAISd/_ directory
* [mapbox-gl-js](https://github.com/mapbox/mapbox-gl-js) in _mapbox-gl-js/dist/_ directory
* [mapbox-gl-leaflet](https://github.com/mapbox/mapbox-gl-leaflet) as _mapbox-gl-leaflet/leaflet-mapbox-gl.js_

Create a local copy of dependences and/or edit _index.php_

## Emergency kit
All you need to install, including dependences, are in _emergencykit/_.  
You may download full pack -- more 4MB, or without vector tiles support pack -- less them 1MB.

## More thanks
* [leaflet-omnivore](https://github.com/mapbox/leaflet-omnivore) for leaflet-omnivore. This patched to show markers and non well-formed gpx files.
* [Metrize Icons by Alessio Atzeni](https://icon-icons.com/pack/Metrize-Icons/1130) for icons.
* [Typicons by Stephen Hutchings](https://icon-icons.com/pack/Typicons/1144) for icons.
* [Map Icons Collection](https://mapicons.mapsmarker.com/) for icons.
* [On/Off FlipSwitch](https://proto.io/freebies/onoff/)
* [leaflet-tracksymbol](https://github.com/lethexa/leaflet-tracksymbol) which became the basis for display AIS data
* [openmaptiles](https://github.com/openmaptiles/fonts) for Open Font Glyphs for GL Styles
* [GitHub MAPBOX project](https://github.com/mapbox) for navigation ui resources
* [OpenMapTiles](https://github.com/openmaptiles) for Mapbox GL basemap style
* [leaflet-ais-tracksymbol](https://github.com/PowerPan/leaflet-ais-tracksymbol) for ideas

## gpsd
GaladrielMap gets realtime info, such as spatial data, AIS data, instruments from  [gpsd](https://gpsd.io/) via _askGPSD.php_ and _askAIS.php_ services. You may configure these services to you gpsd host and port. Defaults are localhost and 2947 port (default for gpsd). In addition, you must specify the php cli filename on _askAIS.php_ to start [gpsdAISd](https://github.com/VladimirKalachikhin/gpsdAISd).  
How to install and configure gpsd see [gpsd pages](https://gpsd.io/).

## Tracks
You may use `gpxlogger` app from gpsd-clients packet to logging track on your server. GaladrielMap displaying current track as a not well-formed gpx file. Other tracks may be displayed simultaneously.  
Run _chkGPXfiles.php_ in cli to repair non-well-formed gpx files for other applications.

## CSV
Comma-Separated Values text file - the simplest way of cooking personal POI for your trip. Only text editor needed. But, to avoid mistakes, any spreadsheet recommended.  
The first line in the csv file must be field names. Good choice is a `"number","name","description","type","link","latitude","longitude"`  
Latitude and longitude may be in degrees, minutes and seconds, 61°04.7'N for example, or in decimal degrees.
A real example of using csv to store information about ports and piers on Lake Saimaa in Finland - [SaimaaPOI](https://github.com/VladimirKalachikhin/Saimaa-POI). File with geospatial photolinks on csv format - is a good example too.

## Exchange coordinates
To get current position to clipboard to share it via other apps - tap on Position on <img src="img/speed1.svg" alt="Dashboard" width="24px"> tab.  
Also, tap on POI name on point's popup to get a position of this point.  
To get coordinates of any point - open <img src="img/route.svg" alt="Handle route" width="24px"> tab. Coordinates of crosshair will be in text field.  
To fly map by coordinates type they of any format to this field and press button.

## Create and edit route
Open <img src="img/route.svg" alt="Handle route" width="24px"> tab to navigational plotting feature.  
You can create and edit the route on the local device, and/or save it to server.  
 This feature includes a base gpx route editing tool. You can edit any gpx route from the server in the same way as local route, and save it under the same or new name.  
 Good case for use - navigational plotting by the navigator in a dry and warm booth for the steersman on the rain and wind.  
 But it is only a base gpx route editor. Gpx &lt;metadata&gt; not supported, and point &lt;extensions&gt; (GARMIN like it) not supported too. Be careful to edit and save exists gpx.

## Mapbox-style vector tiles maps
GaladrielMap has limited support the Mapbox-style vector tiles maps. [Mapbox style file](https://docs.mapbox.com/mapbox-gl-js/style-spec/) must be placed on `$mapSourcesDir` directory of the GaladrielCache with **{mapname}.json** name. Sprites and glyphs you can find in _styles/_ directory.

## Support
You can get support for GaladrielMap and GaladrielCahe for a beer [via PayPal](https://paypal.me/VladimirKalachikhin) or [YandexMoney](https://yasobe.ru/na/galadrielmap) at [galadrielmap@gmail.com](mailto:galadrielmap@gmail.com)  