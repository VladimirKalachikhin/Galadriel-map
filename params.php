<?php
/* Options, paths and services
*/
$currTrackFirst = FALSE; 	// In list of a tracks current track is a first (TRUE), or a last (FALSE). Depending on a your tracking app.

// Пути paths
// путь в файловой системе к программе кеширования тайлов GaladrielCache
$tileCachePath = '/home/www-data/tileproxy'; 	// path to GaladrielCache tile cache/proxy app location, if present, in filesystem. Comment this if no GaladrielCache. 
// путь в файловой системе к папке с записью пути (треку), от расположения GaladrielMap или абсолютный
$trackDir = 'track'; 	// track files directory, if present, in filesystem
// путь в файловой системе к папке с проложенными маршрутами и навигационными точками. Или абсолютный.
$routeDir = 'route'; 	// route & POI files directory, if present, in filesystem

// Службы Services
// 	Источник карты Map source
$tileCacheServerPath = '/tileproxy'; 	// вообще, здесь этой переменной не место, и она (пере) определяется в конфиге GaladrielCache
// url источника карты: в интернет или GaladrielCache
$tileCacheURI = "$tileCacheServerPath/tiles.php?z={z}&x={x}&y={y}&r={map}"; 	// uri of the map service, for example Galadriel tile cache/proxy service. In case GaladrielCache {map} is a map name in GaladrielCache app.
//$tileCacheURI = "http://mt2.google.com/vt/lyrs=s,m&hl=ru&x={x}&y={y}&z={z}"; 	//   uri of the map service - if no use GaladrielCache. Comment the $tileCachePath on this case.
//$tileCacheURI = 'http://a.tile.opentopomap.org/{z}/{x}/{y}.png'; 	//  uri of the map service - if no use GaladrielCache. Comment the $tileCachePath on this case.

// 	Позиционирование Positioning support    
// url службы, отдающей координаты. При отсутствии -- позиционировании карты не будет
$gpsanddataServerURI = 'askGPSD.php'; 	// uri of the active data service, if present. Commonly spatial and vehicle data.

// url демона gpsd, к которому должна обращаться служба позиционирования
$gpsdHost = 'localhost'; 	// gpsd host
//$gpsdHost = '192.168.10.10';
// порт демона gpsd
//$gpsdPort = 2947; 	// gpsd port
$gpsdPort = 3838; 	// gpsdPROXY

// если используется gpsdPROXY, и он нигде не запускается отдельно, укажите здесь полное имя для его запуска:
// If you use gpsdPROXY, and no start it separately, place full filename here to start it:
$gpsdPROXYname = 'gpsdPROXY/gpsdPROXY.php';

// Signal K
//$signalKhost = array(['localhost',3000]);
// если время последнего определения положения отличается от текущего на столько секунд -- положение показывается как устаревшее (серый курсор)
$PosFreshBefore = 5; 	// seconds. The position is considered correct no longer than this time. If the position older - cursor is grey.

// 	Запись пути Logging
//		установите gpsd-utils, в состав которых входит gpxlogger  install gpsd-utils for gpxlogger
//		если эта переменная не установлена -- считается, что запись пути осуществляется чем-то другим
//		запуск gpxlogger. $gpsdHost:$gpsdPort подставляются всегда, в конце строки запуска.
$gpxlogger = "gpxlogger -e shm -r -i $loggerNoFixTimeout -m $loggerMinMovie"; 	// will listen to the local gpsd using shared memory, reconnect, interval, minmove. С $gpsdHost:$gpsdPort почему-то не работает в Ubuntu 20	
//$gpxlogger = "gpxlogger -e sockets -r -i $loggerNoFixTimeout -m $loggerMinMovie"; 	// will listen to the local gpsd using shared memory, reconnect, interval, minmove. $gpsdHost:$gpsdPort always added to launch line end. If not set -- logging is not done by gpxlogger
// 		url службы записи пути. Если не установлена -- записи пути не происходит
$currentTrackServerURI = 'getlasttrkpt.php'; 	// uri of the active track service, if present. If not -- not logging activity
// 		при потере позиции на столько секунд будет создан новый путь
$loggerNoFixTimeout = 30; 	// sec A new track is created if there's no fix written for an interval
// 		новые координаты записываются каждые столько секунд
$loggerMinMovie = 5; 	// m Motions shorter than this will not be logged

// 	Поддержка Системы Автоматической Идентификации (AIS) и средства обмена положением через Интернет (netAIS)  AIS & netAIS support
// url службы AIS. При отсутствии -- отображения информации AIS и netAIS не будет. Если не используется -- рекомендуется закомментировать эту строку для экономии ресурсов. AIS -- это очень ресурсоёмко.
$aisServerURI = 'askAIS.php'; 	// uri of the AIS data service, if present. Comment it if no need any AIS support.
// время в секундах, в течении которого цель AIS отображается после получения от неё последней информации
$noVehicleTimeout = 600; 	// seconds, time of continuous absence of the vessel in AIS, when reached - is deleted from the data. "when a ship is moored or at anchor, the position message is only broadcast every 180 seconds;"
// 		netAIS
// путь в файловой системе к программе поддержки обмена положением через Интернет (netAIS)
$netAISPath = '/home/www-data/netAIS'; 	//  Comment this if no need netAIS support.

// 	Динамическое обновление маршрутов  Route updater
// 		url службы динамического обновления маршрутов. При отсутствии -- маршруты можно обновить только перезагрузив страницу.
$updateRouteServerURI = 'checkRoutes.php'; 	// url to route updater service. If not present -- update server-located routes not work.

// Системные параметры System
// строка запуска консольного интерпретатора php
//$phpCLIexec = '/usr/bin/php-cli'; 	// php-cli executed name on your OS
//$phpCLIexec = '/usr/bin/php'; 	// php-cli executed name on your OS
$phpCLIexec = 'php'; 	// php-cli executed name on your OS

// Параметры тайлового кеша Settings of a tile cache/proxy app
if( $tileCachePath) require("$tileCachePath/params.php"); 	//  
// Параметры netAIS  Settings of a netAIS app
if( $netAISPath) require("$netAISPath/params.php"); 	// 
?>
