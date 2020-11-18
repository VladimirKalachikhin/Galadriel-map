<?php
/* Options, paths and services
*/
$currTrackFirst = FALSE; 	// In list of a tracks current track is a first (TRUE), or a last (FALSE). Depending on a your tracking app.

// paths
$tileCachePath = '/home/www-data/tileproxy'; 	// path to GaladrielCache tile cache/proxy app location, if present, in filesystem. Comment this if no GaladrielCache. 
$trackDir = 'track'; 	// track files directory, if present, in filesystem
$routeDir = 'route'; 	// route & POI files directory, if present, in filesystem

// Services
$tileCacheServerPath = '/tileproxy';
$tileCacheURI = "$tileCacheServerPath/tiles.php?z={z}&x={x}&y={y}&r={map}"; 	// uri of the map service, for example Galadriel tile cache/proxy service. In case GaladrielCache {map} is a map name in GaladrielCache app.
//$tileCacheURI = "http://mt2.google.com/vt/lyrs=s,m&hl=ru&x={x}&y={y}&z={z}"; 	//   uri of the map service - if no use GaladrielCache. Comment the $tileCachePath on this case.
//$tileCacheURI = 'http://a.tile.opentopomap.org/{z}/{x}/{y}.png'; 	//  uri of the map service - if no use GaladrielCache. Comment the $tileCachePath on this case.
$gpsanddataServerURI = 'askGPSD.php'; 	// uri of the active data service, if present. Commonly spatial and vehicle data.
$currentTrackServerURI = 'getlasttrkpt.php'; 	// uri of the active track service, if present

// Positioning support
$gpsdHost = 'localhost';
$gpsdPort = 2947;

// AIS & netAIS support
//$aisServerURI = 'askAIS.php'; 	// uri of the AIS data service, if present. Comment it if no need any AIS support.
// AIS
$aisJSONfileName = 'aisJSONdata';	//  Comment this if no need AIS support. To collect AIS data file. Without path - in /tmp, but has troubles on this case.
//$aisJSONfileName = '/home/www-data/gpsdAISd/aisJSONdata'; 	// Comment this if no need AIS support
$gpsdAISd = 'gpsdAISd/gpsdAISd.php'; 	// Daemon to collect local AIS data. Require if $aisJSONfileName, system path
// netAIS NOT AVAILABLE
//$netAISJSONfileName = 'netaisJSONdata'; 	//  Comment this if no need netAIS support. файл данных AIS, такой же, как у gpsdAISd. Туда добавляются цели от netAIS
//$netAISJSONfileName = '/home/www-data/gpsdAISd/netAISJSONdata'; 	//  Comment this if no need netAIS support. файл данных AIS, такой же, как у gpsdAISd. Туда добавляются цели от netAIS
// Run netAIS daemon netAIS/netAISclient.php periodicaly via cron.

// System
//$phpCLIexec = '/usr/bin/php-cli'; 	// php-cli executed name on your OS
//$phpCLIexec = '/usr/bin/php'; 	// php-cli executed name on your OS
$phpCLIexec = 'php'; 	// php-cli executed name on your OS

// Settings of a tile cache/proxy app
if( $tileCachePath) require("$tileCachePath/params.php"); 	// 
?>
