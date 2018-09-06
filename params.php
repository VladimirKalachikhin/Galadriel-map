<?php
/* Options, paths and services
*/
$currTrackFirst = FALSE; 	// In list of a tracks current track is a first (TRUE), or a last (FALSE). Depending on a your tracking app.

// paths
$tileCachePath = '/home/www-data/tileproxy'; 	// path to GaladrielCache tile cache/proxy app location, if present, in filesystem.
//$gpxDir = 'gpx'; 	// track files directory, if present, in filesystem

// Services
$tileCacheURI = '/tileproxy/tiles.php?z={z}&x={x}&y={y}&r={map}'; 	// uri of a map service, for example Galadriel tile cache/proxy service. In case GaladrielCache {map} is a map name in GaladrielCache app.
//$tileCacheURI = '/tileproxy/tiles/{map}/{z}/{x}/{y}.{ext}'; 	//  uri of a map service - if used a local nginx or file system directly. If variable {ext} present in uri, extension of a tile file gets from GaladrielCache parms. So you must set $tileCachePath or define {ext} explicitly.

//$gpsanddataServerURI = 'askGPSD.php'; 	// uri of a active data service, if present. Commonly GNSS/AIS.
$currentTrackServerURI = 'getlasttrkpt.php'; 	// uri of a active track service, if present

?>
