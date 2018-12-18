<?php
require_once('fcommon.php');
require('params.php'); 	// пути и параметры

$versionTXT = '0.1.0';
// Интернационализация
if(strpos($_SERVER['HTTP_ACCEPT_LANGUAGE'],'ru')===FALSE) { 	// клиент - нерусский
//if(TRUE) { 	// клиент - нерусский
	$homeHeaderTXT = 'Maps';
	$dashboardHeaderTXT = 'Velocity&heading';
	$dashboardSpeedMesTXT = 'km/h';
	$dashboardHeadingTXT = 'Heading';
	$dashboardHeadingAltTXT = 'Истинный курс';
	$dashboardPosTXT = 'Position';
	$dashboardPosAltTXT = 'Широта / Долгота';
	$dashboardSpeedZoomTXT = 'Velocity vector - distance for';
	$dashboardSpeedZoomMesTXT = 'minutes';

	$tracksHeaderTXT = 'Create track';

	$measureHeaderTXT = 'Route';
	$routeControlsBeginTXT = 'Begin';
	$routeControlsContinueTXT = 'Continue';
	$routeControlsClearTXT = 'Erase';
	
	$routesHeaderTXT = 'Tracks and POI';
	
	$downloadHeaderTXT = 'Download';
	$downloadZoomTXT = 'Zoom';
	$downloadJobListTXT = 'Started downloading';
	
	$settingsHeaderTXT = 'Settings';
	$settingsCursorTXT = 'Follow <br>to cursor';
	$settingsTrackTXT = 'Current track<br>always visible';
	$integerTXT = 'Integer';
	$clearTXT = 'Clear';
	$okTXT = 'Create!';
	$latTXT = 'Lat';
	$longTXT = 'Lng';
	$completeTXT = 'complete';
}
else {
	$homeHeaderTXT = 'Карты';
	$dashboardHeaderTXT = 'Скорость и направление';
	$dashboardSpeedMesTXT = 'км/ч';
	$dashboardHeadingTXT = 'Истинный курс';
	$dashboardHeadingAltTXT = 'Heading';
	$dashboardPosTXT = 'Местоположение';
	$dashboardPosAltTXT = 'Latitude / Longitude';
	$dashboardSpeedZoomTXT = 'Вектор скорости - расстояние за';
	$dashboardSpeedZoomMesTXT = 'минут';

	$tracksHeaderTXT = 'Треки';

	$measureHeaderTXT = 'Маршрут';
	$routeControlsBeginTXT = 'Начать';
	$routeControlsContinueTXT = 'Продолжить';
	$routeControlsClearTXT = 'Стереть';
	
	$routesHeaderTXT = 'Места и маршруты';
	
	$downloadHeaderTXT = 'Загрузки';
	$downloadZoomTXT = 'Масштаб';
	$downloadJobListTXT = 'Поставлены загрузки';
	
	$settingsHeaderTXT = 'Параметры';
	$settingsCursorTXT = 'Следование <br>за курсором';
	$settingsTrackTXT = 'Текущй трек <br>всегда показывается';
	$integerTXT = 'Целое число';
	$clearTXT = 'Очистить';
	$okTXT = 'Создать!';
	$latTXT = 'Ш';
	$longTXT = 'Д';
	$completeTXT = 'выполнено';
}

if( $tileCachePath) { 	// если мы знаем про GaladrielCache
// Получаем список имён карт
	if($mapSourcesDir[0]=='/') $mapsInfo = $mapSourcesDir;	// если путь абсолютный (и в unix, конечно)
	else  $mapsInfo = "$tileCachePath/$mapSourcesDir"; 	// сделаем путь абсолютным
	$mapsInfo = glob("$mapsInfo/*.php");
	//echo ":<pre>"; print_r($mapsInfo); echo "</pre>";
	array_walk($mapsInfo,function (&$name,$ind) {
			$name=basename($name,'.php'); 	//
		}); 	// 
// Получаем список выполняющихся заданий на скачивание
	if($jobsDir[0]!='/') $jobsDir = "$tileCachePath/$jobsDir";	//  сделаем путь абсолютным, потому что jobsDir - из конфига GaladrielCache
	if($jobsInWorkDir[0]!='/') $jobsInWorkDir = "$tileCachePath/$jobsInWorkDir";	//  сделаем путь абсолютным
	$jobsInfo = preg_grep('~.[0-9]$~', scandir($jobsDir)); 	// возьмём только файлы с цифровым расшрением
	foreach($jobsInfo as $i => $jobName) {
		$jobSize = filesize("$jobsDir/$jobName");
		$jobComleteSize =  @filesize("$jobsInWorkDir/$jobName"); 	// файла в этот момент может уже и не оказаться
		//echo "jobSize=$jobSize; jobComleteSize=$jobComleteSize; <br>\n";
		$jobsInfo[$i] = array($jobName, (100 - round(($jobComleteSize/$jobSize)*100)));
	}
	//echo "jobsInfo:<pre>"; print_r($jobsInfo); echo "</pre>";
}
else {$mapsInfo = array(); $jobsInfo = array();}
 
// Получаем список имён треков
$trackInfo = array();
if($trackDir) {
	$trackInfo = glob("$trackDir/*.gpx"); 	// gpxDir - из файла params.php
	array_walk($trackInfo,function (&$name,$ind) {
			$name=basename($name,'.gpx'); 	// 
		}); 	// 
	//echo "trackInfo:<pre>"; print_r($trackInfo); echo "</pre>";
	foreach($trackInfo as $trk){
		$lastStr = tailCustom("$trackDir/$trk.gpx"); 	// fcommon.php
		//echo "lastStr=".htmlspecialchars($lastStr)."; <br>\n";
		if($lastStr AND ($lastStr <> '</gpx>')) { 	// трек не завершён
			$currentTrackName = $trk;
			if($currTrackFirst) break; 	// текущий трек - первый из незавершённых
		}
	}
}
// Получаем список имён маршрутов
$routeInfo = array();
if($routeDir) {
	$routeInfo = glob("$routeDir/*.gpx"); 	// routeDir - из файла params.php
	$routeInfo = array_merge($routeInfo,glob("$routeDir/*.kml"));
	array_walk($routeInfo,function (&$name,$ind) {
			$name=basename($name); 	// 
		}); 	// 
	sort($routeInfo);
}

?>
<!DOCTYPE html >
<html lang="ru">
<head>
<!--   <LINK href="common.css" rel="stylesheet" type="text/css"> -->
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta http-equiv="Content-Script-Type" content="text/javascript">
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" > <!--  tell the mobile browser to disable unwanted scaling of the page and set it to its actual size -->

	<link rel="stylesheet" href="leaflet/leaflet.css" type="text/css">
	<script src="leaflet/leaflet-src.js"></script>
    <link rel="stylesheet" href="leaflet-sidebar-v2/css/leaflet-sidebar.css" />
	<script src="leaflet-sidebar-v2/js/leaflet-sidebar.js"></script>
    <script src="L.TileLayer.Mercator/src/L.TileLayer.Mercator.js"></script>

<?php if($gpsanddataServerURI) {?>
    <script src="leaflet-realtime/dist/leaflet-realtime.js"></script>
    <script src="Leaflet.RotatedMarker/leaflet.rotatedMarker.js"></script>
<?php }?>
<?php if($trackDir OR $routeDir) {?>
	<link rel="stylesheet" href="leaflet-omnivorePATCHED/leaflet-omnivore.css" />
	<script src="leaflet-omnivorePATCHED/leaflet-omnivore.js"></script>
<?php }?>    

	<script src="Leaflet.Editable/src/Leaflet.Editable.js"></script>
	<link rel="stylesheet" href="leaflet-measure-path/leaflet-measure-path.css" />
	<script src="leaflet-measure-path/leaflet-measure-path.js"></script>

<!--    <script src="JSON-js/cycle.js"></script>--> <!-- костыль для JSON.stringify , которая используется для отладки -->
    <script src="fetch/fetch.js"></script> <!-- полифил для старых браузеров -->
    <script src="promise-polyfill/promise.js"></script> <!-- полифил для старых браузеров -->

	<link rel="stylesheet" href="galadrielmap.css" type="text/css"> <!-- замена стилей -->
	<script src="galadrielmap.js"></script>
   <title>GaladrielMap <?php echo $versionTXT;?></title>
   <!-- карта на весь экран -->
   <style>
body {
    padding: 0;
    margin: 0;
}
html, body, #mapid {
    height: 100%;
    width: 100vw;
}
   </style>
</head>
<body>
<div id="sidebar" class="leaflet-sidebar collapsed">
	<!-- Nav tabs -->
	<div class="leaflet-sidebar-tabs">
		<ul role="tablist">
			<li id="home-tab" <?php if(!$tileCachePath) echo 'class="disabled"';?>><a href="#home" role="tab"><img src="img/maps.svg" alt="menu" width="70%"></a></li>
			<li id="dashboard-tab" <?php if(!$gpsanddataServerURI) echo 'class="disabled"';?>><a href="#dashboard" role="tab"><img src="img/speed1.svg" alt="dashboard" width="70%"></a></li>
			<li id="tracks-tab" <?php if(!$trackDir) echo 'class="disabled"';?>><a href="#tracks" role="tab"><img src="img/track.svg" alt="tracks" width="70%"></a></li>
			<li id="measure-tab" ><a href="#measure" role="tab"><img src="img/route.svg" alt="Create route" width="70%"></a></li>
			<li id="routes-tab" <?php if(!$routeDir) echo 'class="disabled"';?>><a href="#routes" role="tab"><img src="img/poi.svg" alt="Routes and POI" width="70%"></a></li>
		</ul>
		<ul role="tablist">
			<li id="download-tab" <?php if(!$tileCachePath) echo 'class="disabled"';?>><a href="#download" role="tab"><img src="img/download1.svg" alt="download map" width="70%"></a></li>
			<li><a href="#settings" role="tab"><img src="img/settings1.svg" alt="settings" width="70%"></a></li>
		</ul>
	</div>
	<!-- Tab panes -->
	<div class="leaflet-sidebar-content">
		<!-- Карты -->
		<div class="leaflet-sidebar-pane" id="home">
			<h1 class="leaflet-sidebar-header leaflet-sidebar-close"> <?php echo $homeHeaderTXT;?> <span class="leaflet-sidebar-close-icn"><img src="img/Triangle-left.svg" alt="close" width="16px"></span></h1>
			<br>
			<ul id="mapDisplayed">
			</ul>
			<ul id="mapList">
<?php
foreach($mapsInfo as $mapName) { 	// ниже создаётся анонимная функция, в которой вызывается функция, которой передаётся предопределённый в браузере объект event
?>
					<li onClick="{selectMap(event.currentTarget)}"><?php echo "$mapName";?></li>
<?php
}
?>
			</ul>
		</div>
		<!-- Приборы -->
		<div class="leaflet-sidebar-pane" id="dashboard">
			<h1 class="leaflet-sidebar-header leaflet-sidebar-close"> <?php echo $dashboardHeaderTXT;?> <span class="leaflet-sidebar-close-icn"><img src="img/Triangle-left.svg" alt="close" width="16px"></span></h1>
			<div class="big_symbol" onClick="map.setView(cursor.getLatLng());"> <!-- передвинуть карту на место курсора -->
				<div>
					<div style="line-height:0.5;margin-top:2em;">				
						<span id='velocityDial'></span><br><span style="font-size:50%;"><?php echo $dashboardSpeedMesTXT;?></span>
					</div>
					<div style="font-size:50%;line-height:0.5;">
						<br><br><span style="font-size:50%;"><?php echo $dashboardHeadingTXT;?></span><br>
						<span style="font-size:30%; "><?php echo $dashboardHeadingAltTXT;?></span>
					</div>
					<div style="font-size:50%;">
						<span id='headingDisplay'></span>
					</div>
					<div style="font-size:50%;line-height:0.5;">
						<br><span style="font-size:50%;"><?php echo $dashboardPosTXT;?></span><br>
						<span style="font-size:30%; "><?php echo $dashboardPosAltTXT;?></span>
					</div>
					<div style="font-size:50%;">
						<span id='locationDisplay'></span>
					</div>
				</div>
			</div>
			<div style="text-align:center; position: absolute; bottom: 0; width: 100%;">
				<?php echo $dashboardSpeedZoomTXT;?> <span id='velocityVectorLengthInMnDisplay'></span> <?php echo $dashboardSpeedZoomMesTXT;?>.
			</div>
		</div>
		<!-- Треки -->
		<div class="leaflet-sidebar-pane" id="tracks">
			<h1 class="leaflet-sidebar-header leaflet-sidebar-close"> <?php echo $tracksHeaderTXT;?> <span class="leaflet-sidebar-close-icn"><img src="img/Triangle-left.svg" alt="close" width="16px"></span></h1>
			<br>
			<ul id="trackDisplayed">
			</ul>
			<ul id="trackList">
<?php
foreach($trackInfo as $trackName) { 	// ниже создаётся анонимная функция, в которой вызывается функция, которой передаётся предопределённый в браузере объект event
?>
					<li onClick='{selectTrack(event.currentTarget,trackList,trackDisplayed,displayTrack)}' <?php if($trackName == $currentTrackName) echo "id='currentTrackLi' class='currentTrackName' title='Current track'"; echo ">$trackName";?></li>
<?php
}
?>
			</ul>
		</div>
		<!-- Расстояния -->
		<div class="leaflet-sidebar-pane" id="measure">
			<h1 class="leaflet-sidebar-header leaflet-sidebar-close"> <?php echo $measureHeaderTXT;?> <span class="leaflet-sidebar-close-icn"><img src="img/Triangle-left.svg" alt="close" width="16px"></span></h1>
			<div id='routeControls' class="routeControls" style="padding: 1rem 0; text-align: center;">
				<input type="radio" name="routeControl" class='L' id="routeCreateButton"
					onChange="
						if(L.Browser.mobile && L.Browser.touch) var weight = 15; 	// мобильный браузер
						else var weight = 7; 	// стационарный браузер
						//window.LAYER = map.editTools.startPolyline(false,{showMeasurements: true,color: '#ccff00',weight: weight,opacity: 0.7});
						window.LAYER = map.editTools.startPolyline(false,{showMeasurements: true,color: '#FDFF00',weight: weight,opacity: 0.5});
                        //console.log(window.LAYER);
				        window.LAYER.on('click', L.DomEvent.stop).on('click', tooggleEditRoute);
						measuredPaths.push(window.LAYER);
						routeEraseButton.disabled=false;
					"
				>
				<label for="routeCreateButton"><?php echo $routeControlsBeginTXT;?></label>
				<input type="radio" name="routeControl" class='R' id="routeContinueButton"
					onChange="
						map.once('editable:vertex:click', function f(e) { // это CancelableVertexEvent
	                        //console.log(e);
	                        //console.log(e.vertex);
	                        e.cancel(); 	// прекратить дальнейшую обработку
	                        //e.vertex.split();
							e.vertex.continue();
							routeCreateButton.checked=true;
						});
					"
				>
				<label for="routeContinueButton"><?php echo $routeControlsContinueTXT;?></label><br>
				<br>
				<input type="radio" name="routeControl" id="routeEraseButton"
					onChange="
						delShapes(true);
						routeControlsDeSelect();
						this.disabled=true;
						routeContinueButton.disabled=true;
					"
				>
				<label for="routeEraseButton"><?php echo $routeControlsClearTXT;?></label>
			</div>
		</div>
		<!-- Места и маршруты -->
		<div class="leaflet-sidebar-pane" id="routes">
			<h1 class="leaflet-sidebar-header leaflet-sidebar-close"> <?php echo $routesHeaderTXT;?> <span class="leaflet-sidebar-close-icn"><img src="img/Triangle-left.svg" alt="close" width="16px"></span></h1>
			<br>
			<ul id="routeDisplayed">
			</ul>
			<ul id="routeList">
<?php
foreach($routeInfo as $routeName) { 	// ниже создаётся анонимная функция, в которой вызывается функция, которой передаётся предопределённый в браузере объект event
?>
					<li onClick='{selectTrack(event.currentTarget,routeList,routeDisplayed,displayRoute)}' <?php echo ">$routeName";?></li>
<?php
}
?>
			</ul>
		</div>
		<!-- Загрузчик -->
		<div class="leaflet-sidebar-pane" id="download">
			<h1 class="leaflet-sidebar-header leaflet-sidebar-close"><?php echo $downloadHeaderTXT;?> <span class="leaflet-sidebar-close-icn"><img src="img/Triangle-left.svg" alt="close" width="16px"></span></h1>
			<h2 style=''><?php echo $downloadZoomTXT;?>: <span id='current_zoom'></span></h2>
			<div class="" style="font-size:120%;margin:0;">
				<form id="dwnldJob" onSubmit="createDwnldJob();return false;" onreset="current_zoom.innerHTML=map.getZoom(); downJob=false;//alert('reset');">
					<div style='display:grid;grid-template-columns:auto auto;'>
						<div>X</div><div>Y</div>
						<div style='height:25vh;overflow-y:auto;overflow-x:hidden;grid-column:1/3'> 
							<div style='display:grid; grid-template-columns: auto auto; grid-column-gap: 3px;'>
								<div style='margin-bottom:10px;'>
									<input type="text" pattern="[0-9]*" title="<?php echo $integerTXT;?>" class="tileX" size='12' style='width:7rem;font-size:150%;'>
								</div>
								<div style='margin-bottom:10px;'>
									<input type="text" pattern="[0-9]*" title="<?php echo $integerTXT;?>" class="tileY" size='12' style='width:7rem;font-size:150%;' 
										onChange="
											//console.log(this.parentNode);
											downJob = map.getZoom(); 	// выставим флаг, что идёт подготовка задания на скачивание
											var newXinput = this.parentNode.previousElementSibling.cloneNode(true); 	// клонируем div с x
											newXinput.getElementsByTagName('input')[0].value = ''; 	// очистим поле ввода
											var newYinput = this.parentNode.cloneNode(true); 	// клонируем div с y
											newYinput.getElementsByTagName('input')[0].value = ''; 	// очистим поле ввода
											this.onchange = null; 	// удалим обработчик с этого элемента
											this.parentNode.parentNode.insertBefore(newXinput,this.parentNode.nextElementSibling); 	// вставляем после последнего. Да, вот так через задницу, потому что это javascript
											this.parentNode.parentNode.insertBefore(newYinput,newXinput.nextElementSibling);
											newXinput.getElementsByTagName('input')[0].focus(); 	// установим курсор ввода
										"
									>
								</div>
							</div>
						</div>
					</div>
					<div style="width:85%;margin: 0 auto;">
						<button type='reset' style="margin-top:5px;width:4rem;padding:0.2rem;"><img src="img/no.svg" alt="<?php echo $clearTXT;?>" width="16px" ></button>
						<button type='submit' style="margin-top:5px;width:4rem;padding:0.2rem;float:right;"><img src="img/ok.svg" alt="<?php echo $okTXT;?>" width="16px"></button>
					</div>
				</form>
			</div>
			<div style="font-size:120%;margin:1rem 0;">
				<h3><?php echo $downloadJobListTXT;?>:</h3>
				<ul id="dwnldJobList">
<?php
foreach($jobsInfo as $jobName) { 	// 
	list($jobName,$jobPercent) = $jobName;
	echo "						<li  ><span>$jobName </span><span style='font-size:75%;'>$jobPercent% $completeTXT</span></li>";
}
?>
				</ul>
			</div>
		</div>
		<!-- Настройки -->
		<div class="leaflet-sidebar-pane" id="settings">
			<h1 class="leaflet-sidebar-header leaflet-sidebar-close"><?php echo $settingsHeaderTXT;?> <span class="leaflet-sidebar-close-icn"><img src="img/Triangle-left.svg" alt="close" width="16px"></span></h1>
			<div style="margin: 1rem 1rem;">
					<div class="onoffswitch" style="float:right;margin: 1rem auto;"> <!--  Переключатель https://proto.io/freebies/onoff/  -->
						<input type="checkbox" name="onoffswitch" class="onoffswitch-checkbox" id="followSwitch" onChange="noFollowToCursor=!noFollowToCursor; CurrnoFollowToCursor=noFollowToCursor;" checked>
						<label class="onoffswitch-label" for="followSwitch">
							<span class="onoffswitch-inner"></span>
							<span class="onoffswitch-switch"></span>
						</label>
					</div>
					<span style="font-size:120%"><?php echo $settingsCursorTXT;?></span>
			</div>
			<div style="margin: 1rem 1rem;">
					<div class="onoffswitch" style="float:right;margin: 1rem auto;"> <!--  Переключатель https://proto.io/freebies/onoff/  -->
						<input type="checkbox" name="onoffswitch" class="onoffswitch-checkbox" id="currTrackSwitch" onChange="" checked>
						<label class="onoffswitch-label" for="currTrackSwitch">
							<span class="onoffswitch-inner"></span>
							<span class="onoffswitch-switch"></span>
						</label>
					</div>
				<span style="font-size:120%"><?php echo $settingsTrackTXT;?></span>
			</div>
		</div>
	</div>
</div>
<div id="mapid" ></div>
<?php
?>
<script> "use strict";
// Карта
var gpsanddataServerURI = '<?php echo $gpsanddataServerURI;?>'; 	// адрес для подключения к сервису координат и приборов
var tileCacheURI = '<?php echo $tileCacheURI;?>'; 	// адрес источника карт, используется в displayMap
var startCenter = JSON.parse(getCookie('GaladrielMapPosition'));
if(! startCenter) startCenter = L.latLng([55.754,37.62]); 	// начальная точка
var startZoom = JSON.parse(getCookie('GaladrielMapZoom'));
if(! startZoom) startZoom = 12; 	// начальный масштаб
var heading = 0; 	// начальное направление
var PosFreshBefore = 30 * 1000; 	// время в микросекундах, через которое положение считается протухшим
var followToCursor = true; 	// карта следует за курсором Обеспечивает только паузу следования при перемещениях и масштабировании карты руками
var noFollowToCursor = false; 	// карта никогда не следует за курсором Глобальное отключение следования. Само не восстанавливается.
var CurrnoFollowToCursor = 1; 	// глобальная переменная для сохранения состояния
var followPause = 10 * 1000; 	// пауза следования карты за курсором, когда карту подвинули руками, микросекунд
var savePositionEvery = 30 * 1000; 	// будем сохранять положение каждые микросекунд. В настоящее время только кладётся кука
var followPaused = null; 	// объект таймера, который восстанавливает следование курсору
var userMoveMap = true; 	// флаг для отделения собственных движений карты от пользовательских. Считаем все пользовательскими, и только где надо - выставляем иначе
var downJob = false; 	// флаг - не создаётся ли задание на скачивание
var velocityVectorLengthInMn = 10; 	// длинной в сколько минут пути рисуется линия скорости
var currentTrackServerURI = '<?php echo $currentTrackServerURI;?>'; 	// адрес для подключения к сервису, отдающему сегменты текущего трека
var trackDirURI = '<?php echo $trackDir;?>'; 	// адрес каталога с треками
var routeDirURI = '<?php echo $routeDir;?>'; 	// адрес каталога с маршрутами
var currentTrackName = '<?php echo $currentTrackName;?>'; 	// имя текущего (пишущегося сейчас) трека
if(getCookie('GaladrielcurrTrackSwitch') == undefined) currTrackSwitch.checked = true; 	// показывать текущий трек вместе с курсором
else currTrackSwitch.checked = Boolean(+getCookie('GaladrielcurrTrackSwitch'));
// Определим карту
var map = L.map('mapid', {
	center: startCenter,
    zoom: startZoom,
    attributionControl: false,
    zoomControl: false,
    editable: true
	}
);

// Zoom в правом верхнем углу
L.control.zoom({
     position:'topright'
}).addTo(map);

// Версия и пр. в правом нижнем углу
var info = L.control.attribution({
	prefix: 'GaladrielMap <?php echo $versionTXT;?> by Leaflet'
}
).addTo(map);

// Шкала масштаба
L.control.scale({
	position: 'bottomleft',
	maxWidth: 200,
	imperial: false
}
).addTo(map);

// Панель управления
var sidebar = L.control.sidebar('sidebar',{
	container: 'sidebar',
}).addTo(map);
sidebar.on("content", function(event){ 	// Событие открытия? панели 
	//alert(event.id);
	switch(event.id){ 	// какую вкладку открыли
	case 'download':
		tileGrid.addTo(map); 	// добавить на карту тайловую сетку
		if(CurrnoFollowToCursor === 1)CurrnoFollowToCursor = noFollowToCursor;  // запомним состояние глобального признака следования за курсором, если ещё не запоминали
		noFollowToCursor = true; 	// отключим следование за курсором
		break;
	}
});
sidebar.on("closing", function(){
	tileGrid.remove(); 	// удалить с карты тайловую сетку
	if(CurrnoFollowToCursor !== 1) noFollowToCursor = CurrnoFollowToCursor; 	// восстановим признак следования за курсором
	CurrnoFollowToCursor = 1;
});

// Поведение карты
map.on('movestart zoomstart', function(event) { 	// карту начали двигать руками
	// функция отменяет следование карты за курсором, и устанавливает таймер, чтобы вернуть
	// пытается отделить собственные движения карты от юзерских, включая изменение масштаба
	if(userMoveMap) { 	// Убран флаг в куске, двигающем карту за курсором
		//alert('Карту сдвинули событием '+event.type);
		if(event.type == 'zoomstart') userMoveMap = 2; // юзер нажал zoom
		else {
			if(userMoveMap == 2) userMoveMap = true; 	// на это дело сработало movestart - игнорируем
			else {
				followToCursor=false; 	// запретим следование за курсором
				clearTimeout(followPaused); 	// отменим то, что есть
				followPaused = setTimeout('followToCursor=true;',followPause); 	// через время followPause разрешим обратно
			}
		}
	}
});
map.on('zoomend', function(event) {
	var zoom = event.target.getZoom();
	//alert(zoom);
	if(!downJob) current_zoom.innerHTML = zoom;
	
});
map.on("layeradd ", function(event) {
	//alert(tileGrid);
	if(tileGrid) tileGrid.bringToFront(); 	// выведем наверх слой с сеткой
});

// Восстановим слои
<?php if( $tileCachePath) { // если работаем через GaladrielCache?>
var lauers = JSON.parse(getCookie('GaladrielMaps'));
// Занесём слои на карту
if(lauers) lauers.reverse().forEach(function(lauerName){ 	// потому что они там были для красоты последним слоем вверъ
		for (var i = 0; i < mapList.children.length; i++) { 	// для каждого потомка списка mapList
			if (mapList.children[i].innerHTML==lauerName) { 	// 
				selectMap(mapList.children[i]);
				break;
			}
		}
	});
<?php }
else {?>
displayMap('default');
<?php }?>

// Сетка
var tileGrid = new L.GridLayer();
tileGrid.createTile = function (coords) {
	var tile = document.createElement('div');
	tile.style.outline = '1px solid rgba(255,69,0,1)';
	tile.style.fontWeight = 'bold';
	tile.style.fontSize = '23pt';
	tile.style.color = 'rgba(255,69,0,0.75)';
	tile.innerHTML = '<div style="padding:1rem;">'+coords.z+'<br>'+coords.x+' / '+coords.y+'</div>';
	return tile;
}
if( !downJob) current_zoom.innerHTML = map.getZoom(); 	// текущий масштаб отобразим а панели скачивания

// Рисование маршрута
var measuredPaths = [];
doRestoreMeasuredPaths(); 	// восстановим из кук сохранённые на устройстве маршруты
routeControlsDeSelect(); 	// сделать кнопки рисования невыбранными
routeContinueButton.disabled=true; 	// сделать кнопку "Продолжить" неактивной.
routeEraseButton.disabled=true; 	// сделать кнопку "Стереть" неактивной.

map.on('editable:editing', // обязательный обработчик для editable для перересовывания расстояний при изменении пути
	function (e) {
		//console.log(e);
		//console.log(e.layer);
		if (e.layer instanceof L.Path) e.layer.updateMeasurements();
    }
);
map.on('editable:drawing:end', // выключать кнопку "Начать" при окончании рисования, сделать доступной "Продолжить"
	function () {
		//alert('Stop create'); 
		routeCreateButton.checked=false;
		routeContinueButton.disabled=false;
	}
);
map.on('editable:vertex:dragstart', 
	function (e) {
		window.navigator.vibrate(200); // Вибрировать 200ms
	}
)
var doSaveMeasuredPathsProcess = setInterval(doSaveMeasuredPaths,savePositionEvery); 	// велим сохранять позицию каждые savePositionEvery

<?php if(!$gpsanddataServerURI) goto noRealTime; // если нет источника текущих данных - не нужны и обработчики ?>
// Местоположение
// маркеры
var GpsCursor = L.icon({
	iconUrl: './img/gpscursor.png',
	//shadowUrl: '//leafletjs.com/docs/images/leaf-shadow.png',
	iconSize:     [120, 120], // size of the icon
	//shadowSize:   [50, 64], // size of the shadow
	iconAnchor:   [60, 60], // point of the icon which will correspond to marker's location
	//shadowAnchor: [4, 62],  // the same for the shadow
	//popupAnchor:  [-3, -76] // point from which the popup should open relative to the iconAnchor
});

var NoGpsCursor = L.icon({
	iconUrl: './img/nogpscursor.png',
	iconSize:     [120, 120], // size of the icon
	iconAnchor:   [60, 60], // point of the icon which will correspond to marker's location
});
var velocityCursor = L.icon({
	iconUrl: './img/1x1.png',
	//iconUrl: './img/minLine.svg',
	iconSize:     [5, 1], // size of the icon
	iconAnchor:   [3, 1], // point of the icon which will correspond to marker's location
});

var NoCursor = L.icon({
	iconUrl: './img/1x1.png',
	iconSize:     [0, 0], // size of the icon
	iconAnchor:   [0, 0], // point of the icon which will correspond to marker's location
});
// курсор
var cursor = L.marker(startCenter, {
	'icon': GpsCursor,
	rotationAngle: heading, // начальный угол поворота маркера
	rotationOrigin: "50% 50%" 	// вертим маркер вокруг центра
});
// указатель скорости
var velocityVector = L.marker(cursor.getLatLng(), {
	'icon': velocityCursor,
	rotationAngle: heading, // начальный угол поворота маркера
	rotationOrigin: "100% 100%", 	// вертим вокруг дальнего конца
	opacity: 0.1
}).addTo(map);
velocityVectorLengthInMnDisplay.innerHTML = velocityVectorLengthInMn; 	// нарисуем цену вектора скорости на панели управления

// Точность ГПС
var GNSScircle = L.circle(cursor.getLatLng(), {
	'radius': 10,
	'color':'#000000',
	'weight':1,
	'opacity':0.1
}).addTo(map);

// Позиционирование
var realtime = L.realtime(gpsanddataServerURI, {
	interval: 1 * 1000,
	pointToLayer: function (feature, latlng) {
		return cursor.setLatLng(latlng);
	}        
}).addTo(map);

// Realtime периодическое обновление
realtime.on('update', function(onUpdate) {
	//alert(JSON.stringify(JSON.decycle(onUpdate.features)));
	//alert(JSON.stringify(JSON.decycle(onUpdate.update)));
	//alert(cursor.getLatLng());
	// .gps. - это geoJSON id, который отдаёт testGPSD.php
	// Положение неизвестно
	if(onUpdate.features.gps === undefined) { 	// баг в leaflet-realtime : если geometry":null, то вся onUpdate.features.gps неопределена, и прочитать сообщение об ошибке невозможно
		cursor.setIcon(NoCursor); 	// отключим курсоры
		velocityVector.setIcon(NoCursor);
		//velocityDial.innerHTML = ''; 	// может быть, следует знать, какой была скорость и координаты до пропадания приборов?
		GNSScircle.setRadius(0);
		//alert('Чёта с ГПС'); 
		return;
	}
	// Свежее ли положение известно
	var positionTime = new Date(onUpdate.features.gps.properties.time);
	var now = new Date();
	//alert("Время ГПС "+positionTime+'\n'+"Сейчас    "+now);
	if((now-positionTime) > PosFreshBefore) cursor.setIcon(NoGpsCursor); 	// свежее положение было определено раньше, чем PosFreshBefore милисекунд назад
	else
	 		cursor.setIcon(GpsCursor);
	// Направление с попыткой его запомнить при прекращении движения
	if((onUpdate.features.gps.properties.heading !== null) && Math.round( onUpdate.features.gps.properties.velocity ) != 0) {heading = onUpdate.features.gps.properties.heading;} // если положение изменилось - возьмём новое направление, иначе - будет старое.
	//alert("Направление: "+JSON.stringify(onUpdate.features.gps.properties.heading));
	velocityVector.setLatLng( cursor.getLatLng() );// положение указателя скорости
	cursor.setRotationAngle(heading); // повернём маркер
	velocityVector.setRotationAngle(heading); // повернём указатель скорости
	headingDisplay.innerHTML = Math.round(heading); // покажем направление на приборной панели
	// Карту в положение
	//console.log("followToCursor", followToCursor);
	if(followToCursor && (! noFollowToCursor)) { 	// если сказано следовать курсору, и это разрешено глобально
		userMoveMap = false;
		map.fitBounds(realtime.getBounds(), {maxZoom: map.getZoom()}); // подвинем карту на позицию маркера
		map.setView(cursor.getLatLng());
		userMoveMap = true;
	}
<?php if($currentTrackServerURI) { ?>
	// Текущий трек
	if(currentTrackName) {
		if(map.hasLayer(window[currentTrackName])) { 	// Текущий трек показывается
			//alert('Текущий трек показывается');
			updateCurrTrack();
		}
		else {
			if(currTrackSwitch.checked) selectTrack(currentTrackLi); 	// требуется показывать текущй трек
		}
	}
<?php } ?>
	// Показ скорости и прочего
	//var velocity = Math.round(((onUpdate.features.gps.properties.velocity/1000)*60*60)*10)/10; 	// скорость от gpsd - в метрах в секунду
	var velocity = Math.round((onUpdate.features.gps.properties.velocity*60*60/1000)*10)/10; 	// скорость от gpsd - в метрах в секунду
	//alert("Скорость: "+velocity+"км/ч");
	velocityDial.innerHTML = velocity;
	// координаты курсора с точностью знаков
	var lat = Math.round(cursor.getLatLng().lat*10000)/10000; 	 	// широта
	var lng = Math.round(cursor.getLatLng().lng*10000)/10000; 	 	// долгота
	//alert(cursor.getLatLng()+'\n'+lat+' '+lng);
	locationDisplay.innerHTML = '<?php echo $latTXT?> '+lat+'<br><?php echo $longTXT?> '+lng;	
	followSwitch.checked = !noFollowToCursor; 	// выставим переключатель на панели Настроек в текущее положение
	// Установим длину указателя скорости за  минуты
	var metresPerPixel = (40075016.686 * Math.abs(Math.cos(cursor.getLatLng().lat*(Math.PI/180))))/Math.pow(2, map.getZoom()+8); 	// in WGS84
	var velocityCursorLength = onUpdate.features.gps.properties.velocity*60*velocityVectorLengthInMn; 	// метров  за  минуты
	velocityCursorLength = Math.round(velocityCursorLength/metresPerPixel);
	//console.log('map.getZoom='+map.getZoom()+'\nmetresPerPixel='+metresPerPixel+'\nonUpdate.features.gps.properties.velocity='+onUpdate.features.gps.properties.velocity+'\nvelocityCursorLength='+velocityCursorLength);
	//alert('metresPerPixel='+metresPerPixel+'\nvelocityCursorLength='+velocityCursorLength);
	velocityCursor.options.iconSize=[5,velocityCursorLength];
	velocityCursor.options.iconAnchor=[3,velocityCursorLength];
	velocityVector.setIcon(velocityCursor);
	// Окружность точност ГПС
	var errGNSS = (+onUpdate.features.gps.properties.errX+onUpdate.features.gps.properties.errY)/2;
	if(!errGNSS) errGNSS = 10; // метров
	GNSScircle.setLatLng(cursor.getLatLng());
	GNSScircle.setRadius(errGNSS);
});
<?php 
noRealTime: 
?>

var savePositionProcess = setInterval(doSavePosition,savePositionEvery); 	// велим сохранять позицию каждые savePositionEvery
document.getElementById("followSwitch").checked = true; 	// выставим переключатель на панели Настроек в правильное положение
</script>
</body>
</html>
