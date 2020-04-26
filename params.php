<?php
/* Options, paths and services
*/
$currTrackFirst = FALSE; 	// In list of a tracks current track is a first (TRUE), or a last (FALSE). Depending on a your tracking app.

// paths
$tileCachePath = '/home/www-data/tileproxy'; 	// path to GaladrielCache tile cache/proxy app location, if present, in filesystem. Comment this if no GaladrielCache 
$trackDir = 'track'; 	// track files directory, if present, in filesystem
$routeDir = 'route'; 	// route & POI files directory, if present, in filesystem

// Services
$tileCacheURI = '/tileproxy/tiles.php?z={z}&x={x}&y={y}&r={map}'; 	// uri of the map service, for example Galadriel tile cache/proxy service. In case GaladrielCache {map} is a map name in GaladrielCache app.
//$tileCacheURI = 'http://a.tile.opentopomap.org/{z}/{x}/{y}.png'; 	//  uri of the map service - if no use GaladrielCache. Comment the $tileCachePath on this case.

$gpsanddataServerURI = 'askGPSD.php'; 	// uri of the active data service, if present. Commonly spatial and vehicle data.
$currentTrackServerURI = 'getlasttrkpt.php'; 	// uri of the active track service, if present
$aisServerURI = 'askAIS.php'; 	// uri of the AIS data service, if present.
// Settings of a tile cache/proxy app
if( $tileCachePath) require("$tileCachePath/params.php"); 	// 
?>
