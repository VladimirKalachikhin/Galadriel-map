<?php
/* Options, paths and services
*/
// Параметры Options
//	Длина вектора скорости, собственного и целей AIS. Минут движения.
// если не указано -- то дистанция определения возможности столкновения 
// (величина $collisionDistance в файле params.php gpsdPROXY)
// Velocity vector length, own and AIS targets. Minutes of movement.
// if not set - the $collisionDistance in gpsdPROXY's params.php
//$velocityVectorLengthInMn = 10;	
// Пути paths
// путь в файловой системе к демону, собирающему информацию от gpsd. Демон имеет собственные настройки и зависимости!
// The Data collection daemon. Daemon has its own config file!
$gpsdPROXYpath = 'gpsdPROXY';	// file system path
// Адрес gpsdPROXY, если не указан $gpsdPROXYpath. gpsdPROXY url, if no $gpsdPROXYpath present.
// Если не указано, то не будет координат и другой динамической информации.
//$gpsdProxyHost = ;	// If undefined - no spatial and other data presents. If $gpsdPROXYpath is set, then get it from gpsdPROXY's params.php
//$gpsdProxyPort = ;	// Required, if $gpsdProxyHost is defined
// путь в файловой системе к папке с записью пути (треку), от расположения GaladrielMap или абсолютный
$trackDir = 'track'; 	// track files directory, if present, in filesystem
// путь в файловой системе к папке с проложенными маршрутами и навигационными точками. Или абсолютный.
$routeDir = 'route'; 	// route & POI files directory, if present, in filesystem

// Список адресов, обращение с которых позволяет иметь полные (белый список, white list)
// или ограниченные (чёрный список, black list) возможности.
// Отсутствие этой переменной означает полные возможности у всех.
// Формат списка: 
// перечень ip, шаблон адреса (1.2.3.*), подсеть (1.2.3/24 или 1.2.3.4/255.255.255.0), диапазон адресов (1.2.3.0-1.2.3.255)
// Возможны как ipn4, так и ipv6 адреса, но для ipv6 можно указать только подсеть.
// A list of addresses that can be accessed with full (whitelist)
// or limited (blacklist) capabilities.
// The absence of this variable means full capabilities for everyone.
// List format:
// list of ip, wildcard (1.2.3.*), subnet (1.2.3/24 or 1.2.3.4/255.255.255.0), span of ip (1.2.3.0-1.2.3.255)
// Both ipn4 and ipv6 addresses are possible, but for ipv6, you can only specify a subnet.
/*
$grantsAddrList = array(
'whitelist',
array(
	'127.0.0.0','192.168.10.2-192.168.10.100','192.168.10.102'
)
);
*/
// Службы Services
// 	Источник карты Map source
// url источника карты: в интернет (только растровые тайлы) или GaladrielCache
$tileCacheURI = "/tileproxy/tiles.php?z={z}&x={x}&y={y}&r={map}"; 	// uri of the map service, for example Galadriel tile cache/proxy service. In case GaladrielCache {map} is a map name in GaladrielCache app.
//$tileCacheURI = "/tileproxy/tiles/{map}/{z}/{x}/{y}.{ext}"; 	//
//$tileCacheURI = "http://mt2.google.com/vt/lyrs=s,m&hl=ru&x={x}&y={y}&z={z}"; 	//
//$tileCacheURI = 'http://a.tile.opentopomap.org/{z}/{x}/{y}.png'; 	//
//	Управление GaladrielCache, если используется GaladrielCache.
$tileCacheControlURI = "/tileproxy/cacheControl.php";	// uri of GaladrielCache control interface
// Карта, которая показывается, если нечего показывать.
$defaultMap = 'OpenTopoMap';	// GaladrielCache map to display then no map selected
// Начальная точка, если никакой точки не указано, строка json
$defaultCenter = '{"lat": 55.754, "lng": 37.62}';	// map center when no coordinates sets, json string

// если время последнего определения положения отличается от текущего на столько секунд -- положение показывается как устаревшее (серый курсор)
$PosFreshBefore = 5; 	// seconds. The position is considered correct no longer than this time. If the position older - cursor is grey.

// 	Запись пути Logging
//		установите gpsd-utils, в состав которых входит gpxlogger  install gpsd-utils for gpxlogger
// 		при потере позиции на столько секунд будет создан новый путь
$loggerNoFixTimeout = 30; 	// sec A new track is created if there's no fix written for an interval
// 		новые координаты записываются каждые столько секунд
$loggerMinMovie = 5; 	// m Motions shorter than this will not be logged 
//		файл, куда записывается путь, имеет такое имя, что более поздние файлы находятся в начале списка (TRUE), или в конце (FALSE). Зависит от программы записи пути.
$currTrackFirst = FALSE; 	// In list of a tracks current track is a first (TRUE), or a last (FALSE). Depending on a your tracking app.
//		Через сколько дней начинать новый файл.
$newTrackEveryDays = 1;	// After how many days to start a new file.
//		запуск gpxlogger. &logfile заменяется на имя файла лога, &host -- именем хоста, на котором занущена программа
//		если эта переменная не установлена -- считается, что запись пути осуществляется чем-то другим
//		_Обязательно_ указывать полные пути, если вы хотите, чтобы запись пути возобновилась после
//		случайного выключения сервера. Узнать полный путь к команде можно с помощью заклинания which.
//		It is mandatory to specify full paths if you want the path recording to resume after
//		accidentally shutting down the server. You can find out the full path to the command 
//		using a spell "which"		
//$gpxlogger = "/usr/local/bin/gpxlogger -e shm -r -i $loggerNoFixTimeout -m $loggerMinMovie -f &logfile"; 	// will listen to the local gpsd using shared memory, reconnect, interval, minmove. &logfile replaced by log filename
//$gpxlogger = "gpxlogger -e shm -r --garmin -i $loggerNoFixTimeout -m $loggerMinMovie -f &logfile"; 	// sins 3.24.1 - logging depth as Garmin extension. will listen to the local gpsd using shared memory, reconnect, interval, minmove. &logfile replaced by log filename
//$gpxlogger = "gpxlogger -e shm -r -i $loggerNoFixTimeout -m $loggerMinMovie -f &logfile &host:2947"; 	// will listen to the local gpsd using shared memory, reconnect, interval, minmove. &logfile replaced by log filename, &host replaced by host name 
//$gpxlogger = "gpxlogger -e sockets -r -i $loggerNoFixTimeout -m $loggerMinMovie -f &logfile"; 	// 
$gpxlogger = "gpxlogger -e dbus -r -i $loggerNoFixTimeout -m $loggerMinMovie -f &logfile"; 	// 

// Показ глубины вдоль gpx. Display depth along the gpx.
// display	boolean	Показывать ли глубину вдоль линии пути из файлов gpx, если она там есть.
//					Показ глубины примерно утраивает затраты памяти клиента на показ файла gpx.
//					Whether to show the depth along the track line from the gpx files, if it is there.
//					Showing the depth approximately triples the client's memory consumption for showing the gpx file.
// minvalue	float	Минимально - допустимая глубина. Рекомендуется установить >= осадке судна.
//					Minimum permissible depth. It is recommended to set >= draught of the vessel.
// maxvalue	float	Максимально - интересная глубина. Глубина свыше будет обозначаться одним цветом.
//					Maximum is an interesting depth. Depths over will be indicated by one colour.
// minColor	array or string	Цвет для minvalue. Массив r,g,b или строка с html обозначением цвета.
//							Color for minvalue. Array of r,g,b or html color string.
// maxColor	array or string	Цвет для maxvalue.
//							Color for maxvalue.
// underMinColor	string	Цвет для глубины, меньшей, чем minvalue. Только! строка с html обозначением цвета.
//							Color for a depth less than minvalue. Html color string only.
// upperMaxColor	string	Цвет для глубины, большей чем maxvalue. Только! строка с html обозначением цвета.
//							Colour for depth, more than maxvalue. Html color string only.

$depthInData = '{"display":true,
"minvalue": 2,
"maxvalue": 10,
"minColor": [255,0,0],
"maxColor": [0,255,0],
"underMinColor": "rgb(155,0,0)",
"upperMaxColor": "rgb(200,250,240)"
}';

// Показ символа ветра Display wind symbol
// Использовать истинный ветер, а не вымпельный
$useTrueWind = false;	// Use true wind instead relative

// Системные параметры System
// строка запуска консольного интерпретатора php
//$phpCLIexec = '/usr/bin/php-cli'; 	// php-cli executed name on your OS
//$phpCLIexec = '/usr/bin/php'; 	// php-cli executed name on your OS
$phpCLIexec = 'php'; 	// php-cli executed name on your OS

//  поскольку params.php загружается отнюдь не только в index, все параметры должны быть здесь
if($gpsdPROXYpath) require_once($gpsdPROXYpath.'/params.php'); 	// 
?>
