<?php
require_once('fcommon.php');
require_once('params.php'); 	// пути и параметры
//url службы записи пути. Если не установлена -- записи пути не происходит
$currentTrackServerURI = 'getlasttrkpt.php'; 	// uri of the active track service, if present. If not -- not logging activity
// 	Динамическое обновление маршрутов  Route updater
// 		url службы динамического обновления маршрутов. При отсутствии -- маршруты можно обновить только перезагрузив страницу.
$updateRouteServerURI = 'checkRoutes.php'; 	// url to route updater service. If not present -- update server-located routes not work.

$versionTXT = '2.1.5';
/* 
*/
// start gpsdPROXY
exec("$phpCLIexec $gpsdPROXYpath/gpsdPROXY.php > /dev/null 2>&1 &");

// Интернационализация
if(strpos($_SERVER['HTTP_ACCEPT_LANGUAGE'],'ru')===FALSE) { 	// клиент - нерусский
	require_once('internationalisation/en.php');
}
else {
	require_once('internationalisation/ru.php');
}
//require_once('internationalisation/en.php');

if( $tileCachePath) { 	// если мы знаем про GaladrielCache
// Получаем список имён карт
	if($mapSourcesDir[0]=='/') $mapsInfo = $mapSourcesDir;	// если путь абсолютный (и в unix, конечно)
	else  $mapsInfo = "$tileCachePath/$mapSourcesDir"; 	// сделаем путь абсолютным
	$mapsInfo = glob("$mapsInfo/*.php");
	//echo ":<pre>"; print_r($mapsInfo); echo "</pre>";
	array_walk($mapsInfo,function (&$name,$ind) {
			//$name=basename($name,'.php'); 	//
			$name=explode('.php',end(explode('/',$name)))[0]; 	// basename не работает с неанглийскими буквами!!!!
		}); 	// 
	$vectorEnable = FALSE; 	// векторных карт у нас нет
	if($mapSourcesDir[0]!='/') $fullMapSourcesDir = "$tileCachePath/$mapSourcesDir";	// если путь абсолютный (и в unix, конечно)
	foreach($mapsInfo as $name) {
		//echo "$fullMapSourcesDir/$name.json <br>\n";
		if(file_exists("$fullMapSourcesDir/$name.json")) {
			$vectorEnable = TRUE; 	// векторные карты у нас есть
			break;
		}
	}
}
else {$mapsInfo = array(); $jobsInfo = array();}
 
// Получаем список имён треков
$trackInfo = array(); $currentTrackName = '';
if($trackDir) {
	$trackInfo = glob("$trackDir/*.gpx"); 	// gpxDir - из файла params.php
	array_walk($trackInfo,function (&$name,$ind) { 	// удаление расширения из имени в списке. А оно нужно?
		//$name=basename($name,'.gpx'); 	// 
		$name=explode('.gpx',end(explode('/',$name)))[0]; 	// basename не работает с неанглийскими буквами!!!!
	}); 	// 
	//echo "trackInfo:<pre>"; print_r($trackInfo); echo "</pre>";
	foreach($trackInfo as $trk){
		$lastStr = end(explode("\n",trim(tailCustom("$trackDir/$trk.gpx",5)))); 	// fcommon.php
		//echo "trk=$trk; lastStr=".htmlspecialchars($lastStr)."; <br>\n";
		if($lastStr <> '</gpx>') { 	// трек не завершён
			$currentTrackName = $trk;
			//echo "currentTrackName=$currentTrackName;<br>\n";
			if($currTrackFirst) break; 	// текущий трек - первый из незавершённых
		}
	}
}
// Получаем список имён маршрутов
$routeInfo = array();
if($routeDir) {
	$routeInfo = glob("$routeDir/*.gpx"); 	// routeDir - из файла params.php
	$routeInfo = array_merge($routeInfo,glob("$routeDir/*.kml"));
	$routeInfo = array_merge($routeInfo,glob("$routeDir/*.csv"));
	array_walk($routeInfo,function (&$name,$ind) {
			//$name=basename($name); 	// 
			$name=end(explode('/',$name)); 	// basename не работает с неанглийскими буквами!!!!
		}); 	// 
	sort($routeInfo);
}
// Обслужим файл целей netAIS
if(file_exists($netAISJSONfileName)) {
	$aisData = json_decode(file_get_contents($netAISJSONfileName),TRUE); 	// 
	// Почистим файл от старых целей. Нормально это делают и сервер и клиент, но клиент может быть не запущен
	$now = time();
	foreach($aisData as $veh => &$data) {
		if(($now-$data['timestamp'])>$noVehicleTimeout) unset($aisData[$veh]);
	}
	// зальём обратно
	file_put_contents($netAISJSONfileName,json_encode($aisData)); 	// 
}

// Проверим состояние записи трека
$gpxloggerRun = gpxloggerRun();

// Подготовим картинку для передачи её клиенту, чтобы тот мог видеть её и при потере связи с сервером
$imgFileName = 'img/mob_marker.png';
$mob_markerImg = base64_encode(file_get_contents($imgFileName));
$mob_markerImg = 'data: ' . mime_content_type($imgFileName) . ';base64,' . $mob_markerImg;
?>
<!DOCTYPE html >
<html lang="ru">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta http-equiv="Content-Script-Type" content="text/javascript">
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" > <!--  tell the mobile browser to disable unwanted scaling of the page and set it to its actual size -->

    <!-- Leaflet -->
	<link rel="stylesheet" href="leaflet/leaflet.css" type="text/css">
	<script src="leaflet/leaflet.js"></script>
<?php if($vectorEnable) { ?>
	<!-- Mapbox GL -->
	<link href="mapbox-gl-js/dist/mapbox-gl.css" rel='stylesheet' />
	<script src="mapbox-gl-js/dist/mapbox-gl.js"></script>
<?php }?>
    <!-- Leaflet sidebar -->
    <link rel="stylesheet" href="leaflet-sidebar-v2/css/leaflet-sidebar.min.css" />
	<script src="leaflet-sidebar-v2/js/leaflet-sidebar.min.js"></script>

    <script src="L.TileLayer.Mercator/src/L.TileLayer.Mercator.js"></script>

    <script src="Leaflet.RotatedMarker/leaflet.rotatedMarker.js"></script>
<?php if($trackDir OR $routeDir) {?>
	<script src='supercluster/supercluster.js'></script>
	<link rel="stylesheet" href="leaflet-omnivorePATCHED/leaflet-omnivore.css" />
	<script src="leaflet-omnivorePATCHED/leaflet-omnivore.js"></script>
<?php }?>    
	<script src="Leaflet.Editable/src/Leaflet.Editable.js"></script>
	<link rel="stylesheet" href="leaflet-measure-path/leaflet-measure-path.css" />
	<script src="leaflet-measure-path/leaflet-measure-path.js"></script>

	<script src="coordinate-parserPATCHED/coordinates.js">	</script>
	<script src="coordinate-parserPATCHED/validator.js"></script>
	<script src="coordinate-parserPATCHED/coordinate-number.js"></script> 

	<script src="leaflet-tracksymbolPATCHED/leaflet-tracksymbol.js"></script>
	
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
<?php if($vectorEnable) { ?>
<script src="mapbox-gl-leaflet/leaflet-mapbox-gl.js"></script>
<?php }?>
<div id="sidebar" class="leaflet-sidebar collapsed">
	<!-- Nav tabs -->
	<div class="leaflet-sidebar-tabs">
		<ul role="tablist" id="featuresList">
			<li id="homeTab" <?php if(!$tileCachePath) echo 'class="disabled"';?>><a href="#home" role="tab"><img src="img/maps.svg" alt="menu" width="70%"></a></li>
			<li id="dashboardTab"><a href="#dashboard" role="tab"><img src="img/speed1.svg" alt="dashboard" width="70%"></a></li>
			<li id="tracksTab" <?php if(!$trackDir) echo 'class="disabled"';?>><a href="#tracks" role="tab"><img src="img/track.svg" alt="tracks" width="70%" OnClick='loggingCheck();'></a></li>
			<li id="measureTab" ><a href="#measure" role="tab"><img src="img/route.svg" alt="Create route" width="70%"></a></li>
			<li id="routesTab" <?php if(!$routeDir) echo 'class="disabled"';?>><a href="#routes" role="tab"><img src="img/poi.svg" alt="Routes and POI" width="70%"></a></li>
		</ul>
		<ul role="tablist" id="settingsList">
			<li id="MOBtab"><a href="#MOB" role="tab"><img src="img/mob.svg" alt="activate MOB" width="70%"></a></li>
			<li <?php if(!$tileCachePath) echo 'class="disabled"';?>><a href="#download" role="tab"><img src="img/download1.svg" alt="download map" width="70%"></a></li>
			<li><a href="#settings" role="tab"><img src="img/settings1.svg" alt="settings" width="70%"></a></li>
		</ul>
	</div>
	<!-- Tab panes -->
	<div class="leaflet-sidebar-content" id='tabPanes'>
<?php /* ?>
<div id='infoBox' style='font-size: 90%; position: absolute;'>
</div>
<script>
//alert(window.outerWidth+' '+window.outerHeight);
infoBox.innerText='width: '+window.outerWidth+' height: '+window.outerHeight;
</script>
<?php */ ?>
		<!-- Карты -->
		<div class="leaflet-sidebar-pane" id="home">
			<h1 class="leaflet-sidebar-header leaflet-sidebar-close"> <?php echo $homeHeaderTXT;?> <span class="leaflet-sidebar-close-icn"><img src="img/Triangle-left.svg" alt="close" width="16px"></span></h1>
			<br>
			<ul id="mapDisplayed" class='commonList'>
			</ul>
			<ul id="mapList" class='commonList'>
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
		<div class="leaflet-sidebar-pane" id="dashboard" style="height:100%;">
			<h1 class="leaflet-sidebar-header leaflet-sidebar-close"> <?php echo $dashboardHeaderTXT;?> <span class="leaflet-sidebar-close-icn"><img src="img/Triangle-left.svg" alt="close" width="16px"></span></h1>
			<div class="big_symbol"> <?php // передвинуть карту на место курсора ?>
				<div>
					<div style="line-height:0.6;" onClick="map.setView(cursor.getLatLng());">				
						<div style="font-size:50%;"><?php echo $dashboardSpeedTXT;?></div><br>
						<div id='velocityDial'></div><br>
						<div style="font-size:50%;"><?php echo $dashboardSpeedMesTXT;?></div>
					</div>
					<div id='depthDial' style="line-height:0.4;" onClick="map.setView(cursor.getLatLng());">				
					</div>
					<div style="line-height:0.6;" onClick="map.setView(cursor.getLatLng());">
						<br><span style="font-size:50%;"><?php echo $dashboardHeadingTXT;?></span>
						<span style="font-size:30%; "><br><?php echo $dashboardHeadingAltTXT;?></span>
					</div>
					<div style="">
						<span id='headingDisplay'></span>
					</div>
					<div style="font-size:50%;line-height:0.6;" onClick="doCopyToClipboard(lat+' '+lng);" >
						<br><span style="font-size:50%;"><?php echo $dashboardPosTXT;?></span><br>
						<span style="font-size:30%; "><?php echo $dashboardPosAltTXT;?></span>
					</div>
					<div style="font-size:50%;" onClick="doCopyToClipboard(lat+' '+lng);">
						<span id='locationDisplay'></span>
					</div>
				</div>
			</div>
			<div class="scaledText" style="text-align:center; position: absolute; bottom: 0;">
				<?php echo $dashboardSpeedZoomTXT;?> <span id='velocityVectorLengthInMnDisplay'></span> <?php echo $dashboardSpeedZoomMesTXT;?>.
			</div>
		</div>
		<!-- Треки -->
		<div class="leaflet-sidebar-pane" id="tracks">
			<h1 class="leaflet-sidebar-header leaflet-sidebar-close"> <?php echo $tracksHeaderTXT;?> <span class="leaflet-sidebar-close-icn"><img src="img/Triangle-left.svg" alt="close" width="16px"></span></h1>
<?php if($gpxlogger){ // если запись пути осуществляется gpxlogger'ом ?>
			<div style="margin: 1rem;">
				<div class="onoffswitch" style="float:right;margin: 1rem auto;"> <!--  Переключатель https://proto.io/freebies/onoff/  -->
					<input type="checkbox" name="onoffswitch" class="onoffswitch-checkbox" id="loggingSwitch" onChange="loggingRun();" <?php if($gpxloggerRun) echo "checked"; ?>>
					<label class="onoffswitch-label" for="loggingSwitch">
						<span class="onoffswitch-inner"></span>
						<span class="onoffswitch-switch"></span>
					</label>
				</div>
				<div style="padding:1rem 0 0 0;font-size:120%">
					<span id="loggingIndicator" style="font-size:100%;<?php if($gpxloggerRun) echo"color:green;"; ?>"><?php if($gpxloggerRun) echo '&#x2B24;'; ?></span> <?php echo $loggingTXT;?>
				</div>
			</div>
<?php } ?>
			<ul id="trackDisplayed" class='commonList'>
			</ul>
			<ul id="trackList" class='commonList'>
<?php
foreach($trackInfo as $trackName) { 	// ниже создаётся анонимная функция, в которой вызывается функция, которой передаётся предопределённый в браузере объект event
?>
					<li onClick='{selectTrack(event.currentTarget,trackList,trackDisplayed,displayTrack)}' <?php echo " id='$trackName' "; if($trackName == $currentTrackName) echo "title='Current track' class='currentTrackName'"; echo ">$trackName";?></li>
<?php
}
?>
					<li hidden onClick='{selectTrack(event.currentTarget,trackList,trackDisplayed,displayTrack)}' id='trackLiTemplate' class='currentTrackName' title='Current track'></li>
			</ul>
		</div>
		<!-- Расстояния -->
		<div class="leaflet-sidebar-pane" id="measure">
			<h1 class="leaflet-sidebar-header leaflet-sidebar-close"> <?php echo $measureHeaderTXT;?> <span class="leaflet-sidebar-close-icn"><img src="img/Triangle-left.svg" alt="close" width="16px"></span></h1>
			<?php // Кнопки создания/редактирования маршрута ?>
			<div id='routeControls' class="routeControls" style="padding:1rem 0 2rem; text-align: center;">
				<input type="radio" name="routeControl" class='L' id="routeCreateButton"
					onChange="
						if(L.Browser.mobile && L.Browser.touch) var weight = 10; 	// мобильный браузер
						else var weight = 7; 	// стационарный браузер
						//window.LAYER = map.editTools.startPolyline(false,{showMeasurements: true,color: '#ccff00',weight: weight,opacity: 0.7});
						window.LAYER = map.editTools.startPolyline(false,{showMeasurements: true,color: '#FDFF00',weight: weight,opacity: 0.5});
                        //console.log(window.LAYER);
				        window.LAYER.on('click', L.DomEvent.stop).on('click', tooggleEditRoute);
						measuredPaths.push(window.LAYER);
						routeEraseButton.disabled=false;
						currentRoute = window.LAYER; 	// сделаем объект, по которому щёлкнули, текущим
						if(!routeSaveName.value || Date.parse(routeSaveName.value)) routeSaveName.value = new Date().toJSON(); 	// запишем в поле ввода имени дату, если там ничего не было или была дата
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
			<?php // Поиск места ?>
			<div style="width:95%;">
				<div style="margin:0;padding:0;">
					<button onClick='goToPositionField.value += "°";goToPositionField.focus();' style="width:2rem;height:1.5rem;margin:0 0.7rem 0 0;"><span style="font-weight: bold; font-size:150%;">°</span></button>
					<button onClick='goToPositionField.value += "′";goToPositionField.focus();' style="width:2rem;height:1.5rem;margin:0 0.7rem 0 0;"><span style="font-weight: bold; font-size:150%;">′</span></button>
					<button onClick='goToPositionField.value += "″";goToPositionField.focus();' style="width:2rem;height:1.5rem;margin:0 0rem 0 0;"><span style="font-weight: bold; font-size:150%;">″</span></button><br>
				</div>
				<span style=""><?php echo $dashboardPosAltTXT;?></span><br>
				<input id = 'goToPositionField' type="text" title="<?php echo $goToPositionTXT;?>" size='12' style='width:11rem;font-size:150%;'>			
				<button id = 'goToPositionButton' onClick='flyByString(this.value);' style="width:3rem;padding:0.2rem;float:right;"><img src="img/ok.svg" alt="<?php echo $okTXT;?>" width="16px"></button><br>
			</div>
			<div  style='width:98%;height:12rem;overflow:auto;margin:0.3rem 0;'>
				<ul id='geocodedList' class='commonList'>
				</ul>
			</div>
			<?php // Сохранение маршрута ?>
			<div style="width:95%; padding: 1rem 0; text-align: center;">
				<h3><?php echo $routeSaveTitle;?></h3>
				<input id = 'routeSaveName' type="text" title="<?php echo $routeSaveTXT;?>" placeholder='<?php echo $routeSaveTXT;?>' size='255' style='width:95%;font-size:150%;'>
				<textarea id = 'routeSaveDescr' title="<?php echo $routeSaveDescrTXT;?>" rows='5' cols='255' placeholder='<?php echo $routeSaveDescrTXT;?>' style='width:93%;padding: 0.5rem 3%;'></textarea>
				<button onClick='saveGPX();' type='submit' style="margin-top:5px;width:4rem;padding:0.2rem;float:right;"><img src="img/ok.svg" alt="<?php echo $okTXT;?>" width="16px"></button>
				<div id='routeSaveMessage' style='margin: 1rem;'></div>
			</div>			
		</div>
		<!-- Места и маршруты -->
		<div class="leaflet-sidebar-pane" id="routes">
			<h1 class="leaflet-sidebar-header leaflet-sidebar-close"> <?php echo $routesHeaderTXT;?> <span class="leaflet-sidebar-close-icn"><img src="img/Triangle-left.svg" alt="close" width="16px"></span></h1>
			<ul id="routeDisplayed" class='commonList'>
			</ul>
			<ul id="routeList" class='commonList'>
<?php
foreach($routeInfo as $routeName) { 	// ниже создаётся анонимная функция, в которой вызывается функция, которой передаётся предопределённый в браузере объект event
?>
					<li onClick='{selectTrack(event.currentTarget,routeList,routeDisplayed,displayRoute)}'<?php echo " id='$routeName'>$routeName"; // однако, имена в track и route могут совпадать...?></li>
<?php
}
?>
			</ul>
		</div>
		<!-- MOB -->
		<div class="leaflet-sidebar-pane" style="height:90%;" id="MOB">
			<h1 class="leaflet-sidebar-header leaflet-sidebar-close" style="background-color:red;"><?php echo $mobTXT; ?><span class="leaflet-sidebar-close-icn"><img src="img/Triangle-left.svg" alt="close" width="16px"></span></h1>
			<div style="margin: 1rem 1rem;width:90%;text-align: center;">
				<button onClick='MOBalarm();' style="width:75%;"><span style=""><?php echo $addMarkerTXT; ?></span></button>
			</div>
			<div class="big_symbol" style="line-height: normal;align-items: center;height:70%;" onClick="map.setView(currentMOBmarker.getLatLng());"> <?php //  передвинуть карту на место текущего маркера MOB ?>
				<div style=''><?php // объемлющий div необходим ?>
						<div style="font-size:40%;">
							<span style="font-size:50%;display:block;"><?php echo $bearingTXT; ?></span>
							<span style="font-size:40%;display:block;"><?php echo $altBearingTXT; ?></span>
							<span style="margin:0.5rem;display:block;" id='azimuthMOBdisplay'> </span>
						</div>
						<div style="font-size:65%;margin:1rem 0;">
							<span style="font-size:40%;display:block;"><?php echo $distanceTXT ?>, <?php echo $dashboardMeterMesTXT ?></span>
							<span style="font-size:30%;display:block;"><?php echo $altDistanceTXT ?></span>
							<span style="margin:0.5rem;display:block;" id='distanceMOBdisplay'> </span>
							<span style="font-size:75%;margin:0.5rem;display:block;" id='directionMOBdisplay'></span>
						</div>
						<div style="font-size:40%;" onClick="doCopyToClipboard(Math.round(currentMOBmarker.getLatLng().lat*10000)/10000+' '+Math.round(currentMOBmarker.getLatLng().lng*10000)/10000);" >
							<span style="font-size:50%;display:block;"><?php echo $dashboardPosTXT;?></span>
							<span style="font-size:40%;display:block;"><?php echo $dashboardPosAltTXT;?></span>
							<span style="margin:0.3rem;display:block;" id='locationMOBdisplay'></span>
						</div>
				</div>
			</div>
			<div style="position: absolute; bottom: 1rem;width:90%;text-align: center;"> <?php// Отбой ?>
				<button onClick='delMOBmarker();' id='delMOBmarkerButton' style="width:80%;margin:1rem 0;font-size:75%;" disabled ><span style=""><?php echo $removeMarkerTXT; ?></span></button>
				<div>
				<a style="position:relative;left:-1rem;font-size:100%;color:gray;" onClick='
					this.nextElementSibling.disabled=false;
					this.style.color="green";
				'>&#x2B24;</a>
				<button onClick='MOBclose();' style="width:75%;" disabled><span style=""><?php echo $cancelMOBTXT; ?></span></button>
				</div>
			</div>
		</div>
		<!-- Загрузчик -->
		<div class="leaflet-sidebar-pane" id="download">
			<h1 class="leaflet-sidebar-header leaflet-sidebar-close"><?php echo $downloadHeaderTXT;?> <span class="leaflet-sidebar-close-icn"><img src="img/Triangle-left.svg" alt="close" width="16px"></span></h1>
			<div style="margin: 1rem 0 3rem 0;padding:0 0.5rem 0 0;">
				<div style="margin:0 0 0.5rem 0">
					<div class="onoffswitch" style="float:right;margin: 0.3rem auto;"> <?php //  Переключатель https://proto.io/freebies/onoff/  ?>
						<input type="checkbox" name="onoffswitch" class="onoffswitch-checkbox" id="cowerSwitch" onChange="coverage();">
						<label class="onoffswitch-label" for="cowerSwitch">
							<span class="onoffswitch-inner"></span>
							<span class="onoffswitch-switch"></span>
						</label>
					</div>
					<div style="width:73%;">
						<span style='font-size:120%;'><?php echo $coverTXT;?></span> <span id='cover_zoom' style='font-size:150%;font-weight:bold;'></span>
					</div>
				</div>
				<span id='coverMap' style='font-size:150%;'></span> 
			</div>
			<h2 style=''><?php echo $downloadZoomTXT;?>: <span id='dwnldJobZoom'></span></h2>
			<div class="" style="font-size:120%;margin:0;">
				<form id="dwnldJob" onSubmit="createDwnldJob(); return false;" onreset="dwnldJobZoom.innerHTML=map.getZoom(); downJob=false; tileGrid.redraw();">
					<div style='display:grid;grid-template-columns:auto auto;'>
						<div>X</div><div>Y</div>
						<div style='height:28vh;overflow-y:auto;overflow-x:hidden;grid-column:1/3'>
							<div style='display:grid; grid-template-columns: auto auto; grid-column-gap: 3px;'>
								<div style='margin-bottom:10px;'>
									<input type="text" pattern="[0-9]*" title="<?php echo $integerTXT;?>" class="tileX" size='12' style='width:5rem;font-size:150%;'>
								</div>
								<div style='margin-bottom:10px;'>
									<input type="text" pattern="[0-9]*" title="<?php echo $integerTXT;?>" class="tileY" size='12' style='width:5rem;font-size:150%;' onChange="XYentryFields(this);">
								</div>
							</div>
						</div>
					</div>
					<div style="width:90%;">
						<button type='reset' style="margin-top:5px;width:4rem;padding:0.2rem;"><img src="img/no.svg" alt="<?php echo $clearTXT;?>" width="16px" ></button>
						<button type='submit' style="margin-top:5px;width:4rem;padding:0.2rem;float:right;"><img src="img/ok.svg" alt="<?php echo $okTXT;?>" width="16px"></button>
					</div>
				</form>
			</div>
			<div style="font-size:120%;margin:1rem 0;">
				<h3>
					<span id="loaderIndicator" style="font-size:75%;vertical-align:top;color:gray;">&#x2B24; </span><?php echo $downloadJobListTXT;?>:
				</h3>
				<ul id="dwnldJobList">
				</ul>
			</div>
		</div>
		<!-- Настройки -->
		<div class="leaflet-sidebar-pane" id="settings">
			<h1 class="leaflet-sidebar-header leaflet-sidebar-close"><?php echo $settingsHeaderTXT;?> <span class="leaflet-sidebar-close-icn"><img src="img/Triangle-left.svg" alt="close" width="16px"></span></h1>
			<div style="margin: 1rem 1rem;"> <?php// Следование за курсором ?>
				<div class="onoffswitch" style="float:right;margin: 1rem auto;"> <!--  Переключатель https://proto.io/freebies/onoff/  -->
					<input type="checkbox" name="onoffswitch" class="onoffswitch-checkbox" id="followSwitch" onChange="noFollowToCursor=!noFollowToCursor; CurrnoFollowToCursor=noFollowToCursor;" checked>
					<label class="onoffswitch-label" for="followSwitch">
						<span class="onoffswitch-inner"></span>
						<span class="onoffswitch-switch"></span>
					</label>
				</div>
				<span style="font-size:120%"><?php echo $settingsCursorTXT;?></span>
			</div>
			<div style="margin: 1rem 1rem;"> <?php// Текущий трек всегда показывается ?>
				<div class="onoffswitch" style="float:right;margin: 1rem auto;"> <!--  Переключатель https://proto.io/freebies/onoff/  -->
					<input type="checkbox" name="onoffswitch" class="onoffswitch-checkbox" id="currTrackSwitch" onChange="" checked>
					<label class="onoffswitch-label" for="currTrackSwitch">
						<span class="onoffswitch-inner"></span>
						<span class="onoffswitch-switch"></span>
					</label>
				</div>
				<span style="font-size:120%"><?php echo $settingsTrackTXT;?></span>
			</div>
			<div style="margin: 1rem 1rem;"> <?php// Выбранные маршруты всегда показываются ?>
				<div class="onoffswitch" style="float:right;margin: 1rem auto;"> <!--  Переключатель https://proto.io/freebies/onoff/  -->
					<input type="checkbox" name="onoffswitch" class="onoffswitch-checkbox" id="SelectedRoutesSwitch" onChange="">
					<label class="onoffswitch-label" for="SelectedRoutesSwitch">
						<span class="onoffswitch-inner"></span>
						<span class="onoffswitch-switch"></span>
					</label>
				</div>
				<span style="font-size:120%"><?php echo $settingsRoutesAlwaysTXT;?></span>
			</div>
			<br><br>
			<div style="margin: 1rem 1rem;"> <?php // Показ целей AIS ?>
				<div class="onoffswitch" style="float:right;margin: 0 auto;"> <!--  Переключатель https://proto.io/freebies/onoff/  -->
					<input type="checkbox" name="onoffswitch" class="onoffswitch-checkbox" id="DisplayAISswitch" onChange="watchAISswitching();">
					<label class="onoffswitch-label" for="DisplayAISswitch">
						<span class="onoffswitch-inner"></span>
						<span class="onoffswitch-switch"></span>
					</label>
				</div>
				<span style="font-size:120%;vertical-align:middle;"><?php echo $DisplayAIS_TXT;?></span>
			</div>
			<br><br>
			<div style="margin: 1rem 1rem;"> <?php // максимальная скорость обновления ?>
				<div style="float:right;margin: 1rem auto;">
					<input id='minWATCHintervalInput' type="text" pattern="[0-9]*" title="<?php echo $realTXT;?>" size='4' style='width:3rem;font-size:175%;'
					 onChange="minWATCHinterval=parseFloat(this.value);
					 if(isNaN(minWATCHinterval)) minWATCHinterval=0;
					 //console.log('Изменение, minWATCHinterval',minWATCHinterval);
					 spatialWebSocketStop('Close socket to change WATCH interval');
					 watchAISstop('Close socket to change WATCH interval');
					"
					>
				</div>
				<span style="font-size:120%;vertical-align:middle;"><?php echo $minWATCHintervalTXT;?></span>
			</div>
		</div>
	</div>
</div>
<div id="mapid" ></div>
<?php
if(!$velocityVectorLengthInMn) $velocityVectorLengthInMn = $collisionDistance;	// gpsdPROXY's params.php
if(!$velocityVectorLengthInMn) $velocityVectorLengthInMn = 10;
?>
<script> "use strict";

// Карта
var defaultMap = 'OpenTopoMap'; 	// Карта, которая показывается, если нечего показывать. Народ интеллектуальный ценз ниасилил.
var savedLayers = []; 	// массив для хранения объектов, когда они не на карте
var tileCacheURI = '<?php echo $tileCacheURI;?>'; 	// адрес источника карт, используется в displayMap
var additionalTileCachePath = ''; 	// дополнительный кусок пути к тайлам между именем карты и /z/x/y.png Используется в версионном кеше, например, в погоде. Без / в конце, но с / в начале, либо пусто. Присваивается в javascriptOpen в параметрах карты. Или ещё где-нибудь.
var startCenter = JSON.parse(getCookie('GaladrielMapPosition')); 	// getCookie from galadrielmap.js
if(! startCenter) startCenter = L.latLng([55.754,37.62]); 	// начальная точка
var startZoom = JSON.parse(getCookie('GaladrielMapZoom')); 	// getCookie from galadrielmap.js
if(! startZoom) startZoom = 12; 	// начальный масштаб
var userMoveMap = true; 	// флаг для отделения собственных движений карты от пользовательских. Считаем все пользовательскими, и только где надо - выставляем иначе
// ГПС
var minWATCHinterval=JSON.parse(getCookie('GaladrielminWATCHinterval'));	// Минимальный интервал, сек., с которым будут приходить данные от gpsdPROXY. Если 0 -- то по мере их получения от датчиков
if(!minWATCHinterval) minWATCHinterval = 0;
minWATCHintervalInput.value = minWATCHinterval;
var PosFreshBefore = <?php echo $PosFreshBefore * 1000;?>; 	// время в милисекундах, через которое положение считается протухшим
if(PosFreshBefore < (2*minWATCHinterval*1000+1000)) PosFreshBefore = 2*minWATCHinterval*1000+1000;
var heading = 0; 	// начальное направление
var followToCursor = true; 	// карта следует за курсором Обеспечивает только паузу следования при перемещениях и масштабировании карты руками
var noFollowToCursor = false; 	// карта никогда не следует за курсором Глобальное отключение следования. Само не восстанавливается.
var CurrnoFollowToCursor = 1; 	// глобальная переменная для сохранения состояния
var followPause = 10 * 1000; 	// пауза следования карты за курсором, когда карту подвинули руками, микросекунд
var savePositionEvery = 15 * 1000; 	// будем сохранять положение каждые микросекунд локально в куку
var followPaused; 	// объект таймера, который восстанавливает следование курсору
var velocityVectorLengthInMn = <?php echo $velocityVectorLengthInMn;?>; 	// длинной в сколько минут пути рисуется линия скорости
// AIS
var vehicles = []; 	// list of visible by AIS data vehicle objects 
var AISstatusTXT = {
<?php foreach($AISstatusTXT as $k => $v) echo "$k: '$v',\n";?>
}
// Loader
var downJob = false; 	// флаг - не создаётся ли задание на скачивание
// Пути и маршруты
var currentTrackServerURI = '<?php echo $currentTrackServerURI;?>'; 	// адрес для подключения к сервису, отдающему сегменты текущего трека
var trackDirURI = '<?php echo $trackDir;?>'; 	// адрес каталога с треками
var routeDirURI = '<?php echo $routeDir;?>'; 	// адрес каталога с маршрутами
var currentTrackName = '<?php echo $currentTrackName;?>'; 	// имя текущего (пишущегося сейчас) трека
var updateRouteServerURI = '<?php echo $updateRouteServerURI;?>'; 	// url службы динамического обновления маршрутов
if(getCookie('GaladrielcurrTrackSwitch') == undefined) currTrackSwitch.checked = true; 	// показывать текущий трек вместе с курсором
else currTrackSwitch.checked = Boolean(+getCookie('GaladrielcurrTrackSwitch')); 	// getCookie from galadrielmap.js
if(getCookie('GaladrielSelectedRoutesSwitch') == undefined) SelectedRoutesSwitch.checked = false; 	// показывать выбранные маршруты
else SelectedRoutesSwitch.checked = Boolean(+getCookie('GaladrielSelectedRoutesSwitch')); 	// getCookie from galadrielmap.js
var currentRoute; 	// объект Editable, по которому щёлкнули. Типа, текущий.
var globalCurrentColor = 0xFFFFFF; 	// цвет линий и  значков кластеров после первого набора
var currentTrackShowedFlag = false; 	// флаг, не показывается ли текущий путь. Если об этом спрашивать у Leaflet, то пока загружается трек, можно запустить его загрузку ещё раз пять.
// Dashboard
var lat; 	 	// широта
var lng; 	 	// долгота, округлённые до 4-х знаков
var copyToClipboardMessageOkTXT = '<?php echo $copyToClipboardMessageOkTXT;?>';
var copyToClipboardMessageBadTXT = '<?php echo $copyToClipboardMessageBadTXT;?>';
var dashboardDepthMesTXT = '<?php echo $dashboardDepthMesTXT;?>';
var dashboardMeterMesTXT = '<?php echo $dashboardMeterMesTXT;?>';
// Прокладка
var goToPositionManualFlag = false; 	// флаг, что поле goToPositionField стали редактировать руками, и его не надо обновлять
// MOB
var currentMOBmarker;
const mob_markerImg = '<?php echo $mob_markerImg; ?>';
<?php echo $relBearingTXT; // internationalisation ?>
// main output data
var upData = {};

// Определим карту
var map = L.map('mapid', {
	center: startCenter,
    zoom: startZoom,
    attributionControl: false,
    zoomControl: false,
    editable: true
	}
);

// Controls
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

// control для записывания в clipboard
var copyToClipboard = new L.Control.CopyToClipboard({ 	// класс определён в galadrielmap.js
	position: 'bottomright'
}); 	// на карту не добавляется


// Панель управления
var sidebar = L.control.sidebar('sidebar',{
	container: 'sidebar',
}).addTo(map);
sidebar.on("content", function(event){ 	// Событие открытия? панели 
	//alert(event.id);
	switch(event.id){ 	// какую вкладку открыли
	case 'download':
		chkLoaderStatus();	// проверим загрузки
		tileGrid.addTo(map); 	// добавить на карту тайловую сетку
		if(CurrnoFollowToCursor === 1)CurrnoFollowToCursor = noFollowToCursor;  // запомним состояние глобального признака следования за курсором, если ещё не запоминали
		noFollowToCursor = true; 	// отключим следование за курсором
		break;
	case 'measure': 	// рисование маршрута
		centerMarkOn(); 	// включить крестик в середине
		if(CurrnoFollowToCursor === 1)CurrnoFollowToCursor = noFollowToCursor;  // запомним состояние глобального признака следования за курсором, если ещё не запоминали
		noFollowToCursor = true; 	// отключим следование за курсором
		break;
	case 'MOB': 	// человек за бортом
		if(!map.hasLayer(mobMarker)) MOBalarm();
		break;
	}
});
sidebar.on("closing", function(){
	tileGrid.remove(); 	// удалить с карты тайловую сетку
	if(CurrnoFollowToCursor !== 1) noFollowToCursor = CurrnoFollowToCursor; 	// восстановим признак следования за курсором
	CurrnoFollowToCursor = 1;
	centerMarkOff(); 	// выключить крестик в середине
});
// end controls
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
	let zoom = event.target.getZoom();
	if(!downJob) dwnldJobZoom.innerHTML = zoom;
	cover_zoom.innerHTML = zoom+8;
	
});
<?php if($trackDir OR $routeDir) {?>
map.on('moveend', updateClasters); 	// кластеризация точек POI
<?php }?>    
map.on("layeradd", function(event) {
	//alert(tileGrid);
	if(tileGrid) tileGrid.bringToFront(); 	// выведем наверх слой с сеткой
});

// Восстановим слои
<?php if( $tileCachePath) { // если работаем через GaladrielCache?>
var layers = JSON.parse(getCookie('GaladrielMaps')); 	// getCookie from galadrielmap.js
// Занесём слои на карту
if(layers) layers.reverse().forEach(function(layerName){ 	// потому что они там были для красоты последним слоем вверъ
		for (var i = 0; i < mapList.children.length; i++) { 	// для каждого потомка списка mapList
			if (mapList.children[i].innerHTML==layerName) { 	// 
				selectMap(mapList.children[i]);
				break;
			}
		}
	});
else {
	for (var i = 0; i < mapList.children.length; i++) { 	// для каждого потомка списка mapList
		if (mapList.children[i].innerHTML==defaultMap) { 	// найдём, который из них defaultMap
			selectMap(mapList.children[i]); 	// и покажкм его
			break;
		}
	}
}
<?php }
else {?>
displayMap('default');
<?php }?>

// Восстановим показываемые маршруты
if(SelectedRoutesSwitch.checked) {
	let showRoutes = JSON.parse(getCookie('GaladrielRoutes')); 	// getCookie from galadrielmap.js
	if(showRoutes) {
		showRoutes.forEach(
			function(layerName){ 	// 
				for (let i = 0; i < routeList.children.length; i++) { 	// для каждого потомка списка routeList маршрутов
					if (routeList.children[i].innerHTML==layerName) { 	// 
						selectTrack(routeList.children[i],routeList,routeDisplayed,displayRoute)
						break;
					}
				}
			}
		);
	}
}

// Сетка
var tileGrid = new L.GridLayer();
tileGrid.on('tileload',chkColoreSelectedTile);	// подсветить тайлы, указанные в dwnldJob
tileGrid.createTile = function (coords) {
	var tile = document.createElement('div');
	tile.id = 'gridTile_'+coords.z+'_'+coords.x+'_'+coords.y
	tile.style.outline = '1px solid rgba(255,69,0,1)';
	tile.style.fontWeight = 'bold';
	tile.style.fontSize = 'xx-large';
	tile.style.color = 'rgba(255,69,0,0.75)';
	tile.innerHTML = '<div style="padding:1rem;pointer-events:auto;" onClick="loaderListPopulate(this)">'+coords.z+'<br>'+coords.x+' / '+coords.y+'</div>';	// pointer-events:auto потому, что для слоёв в leaflet указано pointer-events:none;, и они не принимают события указателя
	return tile;
}
if( !downJob) dwnldJobZoom.innerText = map.getZoom(); 	// текущий масштаб отобразим на панели скачивания
cover_zoom.innerText = map.getZoom()+8;

// Рисование маршрута
var measuredPaths = [];
doRestoreMeasuredPaths(); 	// восстановим из кук сохранённые на устройстве маршруты
routeControlsDeSelect(); 	// сделать кнопки рисования невыбранными
routeContinueButton.disabled=true; 	// сделать кнопку "Продолжить" неактивной.
routeEraseButton.disabled=true; 	// сделать кнопку "Стереть" неактивной.

map.on('editable:editing', // обязательный обработчик для editable для перересовывания расстояний при изменении пути
	function (e) {
		//console.log('обязательный обработчик для editable start by editable:editing');
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

// центр экрана
let markSize = Math.round(window.innerWidth/5);
//console.log(markSize);
var centerMark = L.marker(map.getBounds().getCenter(), {
	'icon': new L.icon({
		iconUrl: './img/Crosshair.svg',
		iconSize:     [markSize, markSize], // size of the icon
		iconAnchor:   [markSize/2, markSize/2], // point of the icon which will correspond to marker's location
		className: "centerMarkIcon"	// galadrielmap.css
	})
});

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

// курсор
var NoGpsCursor = L.icon({	// этот значёк может показываться и при пропаже связи с сервером, а в этом случае загрузить картинку не удастся. Попытка загрузить её заранее не получилась: Leaflet, видимо, убивает долго неиспользуемые объекты. Или сборщик мусора?
	iconUrl: './img/gpscursor.png',
	iconSize:     [120, 120], // size of the icon
	iconAnchor:   [60, 60], // point of the icon which will correspond to marker's location
	className: "NoGpsCursorIcon"	// galadrielmap.css
});
var velocityCursor = L.icon({
	iconUrl: './img/1x1.png',
	//iconUrl: './img/minLine.svg',
});
var NoCursor = L.icon({
	iconUrl: './img/1x1.png',
	iconSize: [0, 0], // size of the icon
});
var cursor = L.marker(startCenter, {
	'icon': GpsCursor,
	rotationAngle: heading, // начальный угол поворота маркера
	rotationOrigin: "50% 50%", 	// вертим маркер вокруг центра
});
// указатель скорости
var velocityVector = L.marker(cursor.getLatLng(), {
	'icon': velocityCursor,
	rotationAngle: heading, // начальный угол поворота маркера
	opacity: 0.1
});
velocityVectorLengthInMnDisplay.innerHTML = velocityVectorLengthInMn; 	// нарисуем цену вектора скорости на панели управления
// Точность ГПС
let GNSScircle = L.circle(cursor.getLatLng(), {
	'radius': 10,
	'color':'#000000',
	'weight':0,
	'opacity':0.1,
	'fillOpacity':0.1
});
var positionCursor = L.layerGroup([GNSScircle,velocityVector,cursor]);

// MOB marker
var mobIcon = L.icon({ 	// 
	iconUrl: mob_markerImg,
	//iconUrl: "img/mob.png",
	iconSize: [32, 37],
	//iconSize: [64, 74],
	iconAnchor: [16, 37],
	//iconAnchor: [32, 74],
	tooltipAnchor: [16,-25],
	className: 'mobIcon'	
});
// линия между положением и указанным маркером MOB
var toMOBline = L.polyline([], { 	
	color: 'red',
	weight: 10,
	opacity:0.3,
})

// восстановим маркеры
var mobMarker = getCookie('GaladrielMapMOB'); 	// getCookie from galadrielmap.js
if(mobMarker) {
	// Восстановим мультислой маркеров из GeoJSON, а потом каждому маркеру в мультислое присвоим иконку, которая в GeoJSON не сохраняется.
	mobMarker = L.geoJSON(JSON.parse(mobMarker));
	mobMarker.eachLayer(function (layer) {
		if(layer instanceof L.Marker)	{
			layer.setIcon(mobIcon);
			currentMOBmarker = layer; 	// последний станет текущим
		}
		else mobMarker.removeLayer(layer); 	// Считаем, что это toMOBline, и там больше ничего такого нет
	});
	mobMarker.addLayer(toMOBline);
	mobMarker.addTo(map);
	mobMarker.eachLayer(function (layer) { 	// сделаем каждый маркер draggable. 
		if(layer instanceof L.Marker)	{	
			layer.dragging.enable(); 	// переключение возможно, только если маркер на карте
			layer.on('dragend', function(event){
				//console.log("New MOB marker from server data dragged end, send to server new coordinates",currentMOBmarker);
				sendMOBtoServer(); 
			}); 	// отправим на сервер новые сведения, когда перемещение маркера закончилось. Если просто указать функцию -- в sendMOBtoServer передаётся event. Если в одну строку -- всё равно передаётся event. Что за???
		}
	});
}
else mobMarker = L.layerGroup().addLayer(toMOBline);

//var mobMarker = L.layerGroup().addLayer(toMOBline);

// Позиционирование
// Realtime периодическое обновление
<?php
if($gpsdProxyHost=='localhost' or $gpsdProxyHost=='127.0.0.1' or $gpsdProxyHost=='0.0.0.0') $gpsdProxyHost = $_SERVER['HTTP_HOST'];
?>
var spatialWebSocket; // будет глобальным сокетом
let lastDataUpdate;	// момент последнего обновления координат
function spatialWebSocketStart(){
	let checkDataFreshInterval;	// объект периодического запуска проверки свежести данных
	spatialWebSocket = new WebSocket("ws://<?php echo "$gpsdProxyHost:$gpsdProxyPort"?>"); 	// должен быть глобальным, ибо к нему отовсюду обращаются
	spatialWebSocket.onopen = function(e) {
		console.log("[spatialWebSocket open] Connection established");
		if(map.hasLayer(mobMarker)){ 	// если показывается мультислой с маркерами MOB
			sendMOBtoServer(); 	// отдадим данные MOB для передачи на сервер, на всякий случай -- вдруг там не знают
		}
		// Проверка актуальности координат если, скажем, нет связи с сервером.
		checkDataFreshInterval = setInterval(function (){
			if((Date.now()-lastDataUpdate)>PosFreshBefore){
				console.log('The latest TPV data was received too long ago, trying to reconnect for checking.');
				spatialWebSocket.close(1000,'The latest data was received too long ago');
			}
		},PosFreshBefore);
	}; // end spatialWebSocket.onopen

	spatialWebSocket.onmessage = function(event) {
		//console.log(event);
		//console.log(`[message] Данные TPV получены с сервера: ${event.data}`);
		let data;
		try{
			data = JSON.parse(event.data);
		}
		catch(error){
			console.log('spatialWebSocket: Parsing inbound data',error.message);
			return;
		}
		lastDataUpdate = Date.now();	// какое-то обновление данных пришло.
		switch(data.class){
		case 'VERSION':
			console.log('spatialWebSocket: Handshaiking with gpsd begin: VERSION recieved. Sending WATCH');
			spatialWebSocket.send('?WATCH={"enable":true,"json":true,"subscribe":"TPV","minPeriod":"'+minWATCHinterval+'"};');
			break;
		case 'DEVICES':
			console.log('spatialWebSocket: Handshaiking with gpsd proceed: DEVICES recieved');
			break;
		case 'WATCH':
			console.log('spatialWebSocket: Handshaiking with gpsd complit: WATCH recieved.');
			break;
		case 'POLL':
			break;
		case 'TPV':
			realtimeTPVupdate(data);
			break;
		case 'AIS':
			break;
		case 'MOB':
			//console.log('recieved MOB data',data);
			// pre MOB -- даже если у нас нет координат, полезно показать маркеры MOB
			if(data.status === false) { 	// режим MOB надо выключить
				if(map.hasLayer(mobMarker)){ 	// если показывается мультислой с маркерами MOB
					MOBclose(); 	// пришло, что режима MOB нет -- завершим его
				}
			}
			else { 	//console.log('режим MOB есть, пришли новые данные');
				//console.log('Index data',data);
				// создадим GeoJSON
				let mobMarkerJSON = {"type":"FeatureCollection",
									"features":[]
									};
				for(let point of data.points){
					let feature = 	{	
										"type":"Feature",
										"properties":{
											"current": point.current
										},
										"geometry":{
											"type":"Point",
											"coordinates": point.coordinates
										}
									};
					mobMarkerJSON.features.push(feature);
				}
				// Восстановим мультислой маркеров из GeoJSON, а потом каждому маркеру в мультислое присвоим иконку, которая в GeoJSON не сохраняется.
				mobMarker.remove(); 	// убрать мультислой-маркер с карты
				mobMarker = null; 	// реально удалим объект
				mobMarker = L.geoJSON(mobMarkerJSON); 	// создадим новый объект
				mobMarker.eachLayer(function (layer) {
					if(layer instanceof L.Marker)	{
						layer.setIcon(mobIcon);
						layer.on('click', function(ev){
							currentMOBmarker = ev.target;
							clearCurrentStatus(); 	// удалим признак current у всех маркеров
							currentMOBmarker.feature.properties.current = true;
							sendMOBtoServer(); 	// отдадим данные MOB для передачи на сервер
						}); 	// текущим будет маркер, по которому кликнули
						//console.log('Маркеры в полученной информации MOB ',layer);
						if(layer.feature.properties.current) currentMOBmarker = layer; 	// текущим станет указанный в переданных данных
					}
					else mobMarker.removeLayer(layer); 	// Считаем, что это toMOBline, и там больше ничего такого нет
				});
				mobMarker.addLayer(toMOBline);
				mobMarker.addTo(map); 	// покажем мультислой с маркерами MOB
				mobMarker.eachLayer(function (layer) { 	// сделаем каждый маркер draggable
					if(layer instanceof L.Marker)	{	
						layer.dragging.enable(); 	// переключение возможно, только если маркер на карте
						layer.on('dragend', function(event){
							//console.log("New MOB marker from server data dragged end, send to server new coordinates",currentMOBmarker);
							sendMOBtoServer(); 
						}); 	// отправим на сервер новые сведения, когда перемещение маркера закончилось. Если просто указать функцию -- в sendMOBtoServer передаётся event. Если в одну строку -- всё равно передаётся event. Что за???
					}
				});
			}
			//console.log(mobMarker);
			break;
		}
	}; // end spatialWebSocket.onmessage

	spatialWebSocket.onclose = function(event) {
		console.log(`spatialWebSocket closed: connection broken with code ${event.code} by reason ${event.reason}`);
		window.setTimeout(spatialWebSocketStart, 3000); 	// перезапустим сокет через  секунд. В каком контексте здесь вызывается callback -- мне осталось непонятным, поэтому сокет ваще глобален
		if((Date.now()-lastDataUpdate)>PosFreshBefore*30) positionCursor.remove(); 	// уберём курсор (layerGroup) с карты
		else cursor.setIcon(NoGpsCursor)	// заменим курсор (значёк) на серый
		velocityDial.innerHTML = '&nbsp;'; 	// обнулим панель приборов
		headingDisplay.innerHTML = '&nbsp;';
		locationDisplay.innerHTML = '&nbsp;';
		depthDial.innerHTML = '';
		//MOBtab.className='disabled'; 	// если нет курсора (координат) -- невозможно включить режим MOB. Это плохая идея.
		clearInterval(checkDataFreshInterval);	// остановить периодическую проверку свежести
	}; // end spatialWebSocket.onclose

	spatialWebSocket.onerror = function(error) {
	  console.log(`[spatialWebSocket error] ${error.message}`);
	}; // end spatialWebSocket.onerror

	function realtimeTPVupdate(gpsdData) {
		//console.log('Index gpsdData',gpsdData);
		//console.log('Index gpsdData.MOB',gpsdData.MOB);
		// Положение неизвестно
		//console.log('Index gpsdData',gpsdData.lon,gpsdData.lat);
		if(gpsdData.error || (gpsdData.lon == null)||(gpsdData.lat == null) || (gpsdData.lon == undefined)||(gpsdData.lat == undefined)) { 	// 
			console.log('No spatial info in GPSD data',gpsdData);
			positionCursor.remove(); 	// уберём курсор с карты
			velocityDial.innerHTML = '&nbsp;'; 	// обнулим панель приборов
			headingDisplay.innerHTML = '&nbsp;';
			locationDisplay.innerHTML = '&nbsp;';
			depthDial.innerHTML = '';
			//MOBtab.className='disabled'; 	// если нет курсора (координат) -- невозможно включить режим MOB. Это плохая идея.
			return;
		}
		// Свежее ли положение известно
		//MOBtab.className=''; 	// координаты появились -- можно включить режим MOB
		cursor.setLatLng(L.latLng(gpsdData.lat,gpsdData.lon));
		var positionTime = new Date(gpsdData.time);
		var now = new Date();
		//console.log('gpsdData.time:',gpsdData.time,'now',now,'now-positionTime',now-positionTime);
		if((now-positionTime) > PosFreshBefore) cursor.setIcon(NoGpsCursor); 	// свежее положение было определено раньше, чем PosFreshBefore милисекунд назад
		else cursor.setIcon(GpsCursor);
		
		// Показ скорости и прочего
		//console.log('Index gpsdData',gpsdData.speed);
		var metresPerPixel = (40075016.686 * Math.abs(Math.cos(cursor.getLatLng().lat*(Math.PI/180))))/Math.pow(2, map.getZoom()+8); 	// in WGS84
		if(gpsdData.speed==undefined || gpsdData.speed==null) {
			velocityDial.innerHTML = '&nbsp;';
			velocityVector.setIcon(NoCursor);
		}
		else {
			//var velocity = Math.round((gpsdData.speed*60*60/1000)*10)/10; 	// скорость от gpsd - в метрах в секунду
			var velocity = Math.round((gpsdData.speed*60*60/1000)*10)/10; 	// скорость от gpsd - в метрах в секунду

			velocityDial.innerHTML = velocity;
			// Установим длину указателя скорости за  минуты
			var velocityCursorLength = gpsdData.speed*60*velocityVectorLengthInMn; 	// метров  за  минуты
			velocityCursorLength = Math.round(velocityCursorLength/metresPerPixel);
			//console.log('map.getZoom='+map.getZoom()+'\nmetresPerPixel='+metresPerPixel+'\ngpsdData.speed='+gpsdData.speed+'\nvelocityCursorLength='+velocityCursorLength);
			velocityCursor.options.iconSize=[5,velocityCursorLength];
			velocityCursor.options.iconAnchor=[3,velocityCursorLength];
			velocityVector.setIcon(velocityCursor); 	// изменить иконку у маркера
		}
		if(gpsdData.depth) {
			//console.log('Index gpsdData',gpsdData.depth);
			depthDial.innerHTML = '<br><br><div style="font-size:50%;">'+dashboardDepthMesTXT+'</div><br><div>'+(Math.round(gpsdData.depth*100)/100)+'</div><br><div style="font-size:50%;">'+dashboardMeterMesTXT+'</div>';
		}
		else {
			depthDial.innerHTML = '';
		}
		
		// Направление с попыткой его запомнить при прекращении движения
		//console.log('Index gpsdData',gpsdData.track);
		velocityVector.setLatLng( cursor.getLatLng() );// положение указателя скорости
		if(gpsdData.track == null || gpsdData.track == undefined) {
			headingDisplay.innerHTML = '&nbsp;';
			cursor.setRotationAngle(0); // повернём маркер
			velocityVector.setRotationAngle(0); // повернём указатель скорости
		}
		else {
			heading = gpsdData.track; // если положение изменилось - возьмём новое направление, иначе - будет старое.
			cursor.setRotationAngle(heading); // повернём маркер
			velocityVector.setRotationAngle(heading); // повернём указатель скорости
			headingDisplay.innerHTML = Math.round(heading); // покажем направление на приборной панели
		}
		positionCursor.addTo(map); 	// добавить курсор на карту

		// Окружность точност ГПС
		var errGNSS = (+gpsdData.errX+gpsdData.errY)/2;
		if(!errGNSS) errGNSS = 10; // метров
		if(errGNSS/metresPerPixel > 15) GNSScircle.setRadius(errGNSS); 	// кружок точности больше кружка курсора
		else GNSScircle.setRadius(0);
		GNSScircle.setLatLng(cursor.getLatLng());

		// Карту в положение
		//console.log("followToCursor", followToCursor);
		if(followToCursor && (! noFollowToCursor)) { 	// если сказано следовать курсору, и это разрешено глобально
			userMoveMap = false;
			//map.fitBounds(realtime.getBounds(), {maxZoom: map.getZoom()});
			map.setView(cursor.getLatLng()); // подвинем карту на позицию маркера
			userMoveMap = true;
		}

		// координаты курсора с точностью знаков
		lat = Math.round(cursor.getLatLng().lat*10000)/10000; 	 	// широта
		lng = Math.round(cursor.getLatLng().lng*10000)/10000; 	 	// долгота
		//alert(cursor.getLatLng()+'\n'+lat+' '+lng);
		locationDisplay.innerHTML = '<?php echo $latTXT?> '+lat+'<br><?php echo $longTXT?> '+lng;	
		followSwitch.checked = !noFollowToCursor; 	// выставим переключатель на панели Настроек в текущее положение	
		
		// MOB
		if(map.hasLayer(mobMarker)){ 	// если показывается мультислой с маркерами MOB 
			//console.log(mobMarker.getLayers());
			let latlng1 = cursor.getLatLng();
			let latlng2 = currentMOBmarker.getLatLng();
			toMOBline.setLatLngs([latlng1,latlng2]); 	// обновим линию к текущему маркеру MOB
			// информация о MOB на панели
			const azimuth = bearing(latlng1, latlng2);
			azimuthMOBdisplay.innerHTML = Math.round(azimuth);
			distanceMOBdisplay.innerHTML = Math.round(latlng1.distanceTo(latlng2));
			locationMOBdisplay.innerHTML = '<?php echo $latTXT?> '+Math.round(currentMOBmarker.getLatLng().lat*10000)/10000+'<br><?php echo $longTXT?> '+Math.round(currentMOBmarker.getLatLng().lng*10000)/10000;	
			if(gpsdData.track !== null) { 	// если доступен истинный курс, heading есть всегда
				let relBearing = azimuth-heading+22.5;	// половина от 45 против часовой стрелки
				if(relBearing<0) relBearing = 360+relBearing;
				relBearing = Math.floor(relBearing/45); 	// курсовой угол (relative bearing) / 45 градусов -- номер сектора, против часовой стрелки
				if(relBearing>7) relBearing = 0;
				directionMOBdisplay.innerHTML = relBearingTXT[relBearing];
			}
		}
	}; // end function realtimeTPVupdate
//return spatialWebSocket;	
}; // end function spatialWebSocketStart

spatialWebSocketStart(); 	// запускам периодическую функцию получать TPV

function spatialWebSocketStop(message=''){
	console.log('Stop recieve TPV',);
	spatialWebSocket.close(1000,message);
} // end function spatialWebSocketStop


// Данные AIS
// 	Запуск периодических функций
var aisWebSocket;	// будет глобальный сокет для AIS
function watchAISstart() {
	//console.log('AIS switched ON');
	aisWebSocket = new WebSocket("ws://<?php echo "$gpsdProxyHost:$gpsdProxyPort"?>");	// этот сокет не глобальный!!!!
	aisWebSocket.onopen = function(e) {
		console.log("[aisWebSocket open] Connection established");
	}; // end aisWebSocket.onopen

	aisWebSocket.onmessage = function(event) {
		//console.log(`[aisWebSocket message] Данные AIS получены с сервера: ${event.data}`);
		let data;
		try{
			data = JSON.parse(event.data);
		}
		catch(error){
			console.log('aisWebSocket: Parsing inbound data',error.message);
			return;
		}
		switch(data.class){
		case 'VERSION':
			console.log('aisWebSocket: Handshaiking with gpsd begin: VERSION recieved. Sending WATCH');
			aisWebSocket.send('?WATCH={"enable":true,"json":true,"subscribe":"AIS","minPeriod":"'+minWATCHinterval+'"};');
			break;
		case 'DEVICES':
			console.log('aisWebSocket: Handshaiking with gpsd proceed: DEVICES recieved');
			break;
		case 'WATCH':
			console.log('aisWebSocket: Handshaiking with gpsd complit: WATCH recieved.');
			break;
		case 'POLL':
			break;
		case 'TPV':
			break;
		case 'AIS':
			realtimeAISupdate(data);
			break;
		}
	}; // end aisWebSocket.onmessage

	aisWebSocket.onclose = function(event) {
		console.log(`aisWebSocket closed: connection broken with code ${event.code} by reason ${event.reason}`);
		if(DisplayAISswitch.checked ) window.setTimeout(watchAISstart, 3000); 	// перезапустим сокет через  секунд, если в интерфейсе указано
		for(const vehicle in vehicles){
			vehicles[vehicle].remove();
			vehicles[vehicle] = null;
			delete vehicles[vehicle];
		}
	}; // end aisWebSocket.onclose

	aisWebSocket.onerror = function(error) {
	  console.log(`[aisWebSocket error] ${error.message}`);
	}; 	//end aisWebSocket.onerror

	function realtimeAISupdate(aisClass) {
	// Показывает цели AIS, перечисленные в aisClass.ais
	// те, которых там нет -- перестаёт показывать
	//console.log(aisClass); 	// 
	let aisData = aisClass.ais;
	//console.log(aisData); 	// массив с данными целей
	//console.log(DisplayAISswitch);
	let vehiclesVisible = [];
	for(const vehicle in aisData){
		//console.log(vehicle,aisData[vehicle]);
		if(vehicle.toLowerCase() == 'error') break;
		//console.log(aisData[vehicle].lat);	console.log(aisData[vehicle].lon);
		//console.log(typeof(vehicles[vehicle]));
		if((aisData[vehicle].lat === null) || (aisData[vehicle].lon === null) || (aisData[vehicle].lat === undefined) || (aisData[vehicle].lon === undefined)) continue;	// не показываем цели без координат
		if(!vehicles[vehicle]) { 	// global var, массив layers с целями
			//console.log(vehicle);
			//console.log(aisData[vehicle]);
			var defaultSymbol;
			var noHeadingSymbol;
			if(aisData[vehicle].netAIS) { 	// цель получена от netAIS
				defaultSymbol = [1*0.5,0, 0.25*0.5,0.25*0.5, 0,1*0.5, -0.25*0.5,0.5*0.5, -1*0.5,0.75*0.5, -1*0.5,-0.75*0.5, -0.25*0.5,-0.5*0.5, 0,-1*0.5, 0.25*0.5,-0.25*0.5]; 	// треугольник, расстояния от центра, через которые нарисуют polyline
				noHeadingSymbol = [1*0.35,0, 0.75*0.35,0.5*0.35, 1*0.35,1*0.35, 0.5*0.35,0.75*0.35, 0,1*0.35, -0.5*0.35,0.75*0.35, -1*0.35,1*0.35, -0.75*0.35,0.5*0.35, -1*0.35,0, -0.75*0.35,-0.5*0.35, -1*0.35,-1*0.35, -0.5*0.35,-0.75*0.35, 0,-1*0.35, 0.5*0.35,-0.75*0.35, 1*0.35,-1*0.35, 0.75*0.35,-0.5*0.35]; 	// ромбик: правый, верхний, левый, нижний ПРотив часовой от правого?
				//console.log(aisData[vehicle]);
			}
			else { 	// цель получена от локального приёмника AIS
				defaultSymbol = [0.8,0, -0.3,0.35, -0.3,-0.35]; 	// треугольник вправо, расстояния от центра, через которые нарисуют polyline
				noHeadingSymbol = [0.35,0, 0,0.35, -0.35,0, 0,-0.35]; 	// ромбик
			}
			vehicles[vehicle] = L.trackSymbol(L.latLng(0,0),{
				trackId: vehicle,
				leaderTime: velocityVectorLengthInMn*60,
				fill: true,
				fillOpacity: 1.0,
				stroke: true,
				opacity: 1.0,
				weight: 1.0,
				defaultSymbol: defaultSymbol,
				noHeadingSymbol: noHeadingSymbol 	// 
			}).addTo(map);
		}
		//console.log(vehicles[vehicle]);
		vehicles[vehicle].addData(aisData[vehicle]); 	// обновим данные
		
		vehiclesVisible.push(vehicle); 	// запомним, какие есть
	}
	for(const vehicle in vehicles){
		if(vehiclesVisible.includes(vehicle) && DisplayAISswitch.checked) continue; 	// типа, синхронизация... clearInterval -- асинхронная функция, и может не успеть отключить опрос AIS до того, как цели будут убраны с экрана. Тогда они уберутся здесь.
		vehicles[vehicle].remove();
		vehicles[vehicle] = null;
		delete vehicles[vehicle];
	}
	} // end function realtimeAISupdate

return aisWebSocket
} // end function watchAISstart

watchAISstart(); 	// запускам периодическую функцию смотреть AIS
DisplayAISswitch.checked = true;

function watchAISstop(message=''){
console.log('AIS switched OFF');
aisWebSocket.close(1000,message);
for(const vehicle in vehicles){
	vehicles[vehicle].remove();
	vehicles[vehicle] = null;
	delete vehicles[vehicle];
}
} // end function watchAISstop

function watchAISswitching(){
if(DisplayAISswitch.checked) watchAISstart();
else watchAISstop('Dispalying AIS stopped');
}; // end function watchAISswitching


// 	Запуск периодических функций	 realtime -- в galadrielmap.js, функция, асинхронно обращающаяся к uri
//setInterval(function(){realtime(gpsanddataServerURI,realtimeTPVupdate,lat);},1000); 	// данные позиционирования. Однако, function(){} компилячится каждый оборот, что как бы неправильно.
//setInterval(realtime,1000,gpsanddataServerURI,realtimeTPVupdate,upData); 	// данные позиционирования. Здесь компилячится при загрузке, и параметры передаются в realtime один раз. Что исключает динамические параметры. А как же передача по ссылке?

// 	Запуск периодических функций
//var updateRoutesInterval = setInterval(function(){realtime(updateRouteServerURI,routeUpdate);},2000);
var updateRoutesInterval = setInterval(realtime,2000,updateRouteServerURI,routeUpdate);

// Динамическое обновление показываемых маршрутов
function routeUpdate(changedRouteNames) {
/* Вызывается из-под realtime */
//console.log(changedRouteNames);
if(routeDisplayed.innerHTML.trim() == "") { 	// не показывается ни одного маршрута
	updateRoutesInterval = clearInterval(updateRoutesInterval); 	// прекратим следить за изменениями
	routeDisplayed.addEventListener("DOMNodeInserted", function (event) { 	// добавим обработчик события изменения DOM
		if(! updateRoutesInterval) { 	// никогда не должно быть здесь updateRoutesInterval, но оно может не успеть
			updateRoutesInterval = setInterval(function(){realtime(updateRouteServerURI,routeUpdate);},2000); 	// запустим слежение за изменением показываемых маршрутов
		}
		routeDisplayed.removeEventListener("DOMNodeInserted", this); 	// удаляем обработчик
	}
	, false);
	return;
}
/* в связи с возможностью наличия в trackDisplayed дублирующихся id --
может быть, вместо document.getElementById(name) сделать цикл по потомкам routeDisplayed? */
let node;
for(const name of changedRouteNames){
	node = document.getElementById(name); 	// однако, в trackDisplayed могут быть те же имена. Забить? в querySelector требуется экранирование пробелов и спец-символов. Это секс.
	if(node.parentNode != routeDisplayed) continue; 	// элемент, конечно, всегда есть, нужно, чтобы он показывался
	//console.log(node);
	savedLayers[name].remove(); 	// удалим слой с карты
	savedLayers[name] = null; 	// удалим сам слой
	displayRoute(node); 	// перересуем маршрут
}
} // end  function routeUpdate

// Текущий трек
// Должен обновляться, даже если обновлялка не описана в конфиге, потому что трек может писать кто-то ещё. 
// Т.е. в худшем случае -- мы не знаем, обновляется ли currentTrack, или нет
// Ещё обновление трека можно повесить на обновление координат. Это концептуально правильно, но
// тогда при потере сервиса координат пропадёт и обновление трека (потому что функция обновления
// координат перестанет вызываться). А совсем везде независимое обновление трека будет работать, и
// покажет положение даже при отсутствии сервиса координат.
var currentTrackUpdateProcess = setInterval(currentTrackUpdate,3000);
//console.log('Запущено слежение за логом, currentTrackUpdateProcess=', currentTrackUpdateProcess);
function currentTrackUpdate(){
// Global: map, savedLayers, currentTrackName, currentTrackShowedFlag
// DOM objects: currTrackSwitch, loggingSwitch, trackDisplayed
//console.log('currentTrackName='+currentTrackName,'currentTrackShowedFlag=',currentTrackShowedFlag);

// имеется имя текущего трека, и в интерфейсе указано показывать текущий трек, или текущий трек в списке показываемых
if((currentTrackName && currTrackSwitch.checked)||trackDisplayed.querySelector('li[title="Current track"]')) { 	// имеется имя текущего трека, и в интерфейсе указано показывать текущий трек, или текущий трек в списке показываемых
	if(currentTrackShowedFlag !== false) { 	// Текущий трек некогда был загружен или сейчас загружается
		if(map.hasLayer(savedLayers[currentTrackName])) { 	// если он реально есть
			//console.log('Текущий трек есть на карте','currentTrackName='+currentTrackName,'currentTrackShowedFlag=',currentTrackShowedFlag);
			if(typeof loggingSwitch === 'undefined'){ 	// обновлялка не сконфигурирована
				updateCurrTrack(); 	//  - обновим,  galadrielmap.js
			}
			else {
				if(loggingSwitch) updateCurrTrack(); 	//  - обновим  galadrielmap.js
			}
			currentTrackShowedFlag = true;
		}
		else { 
			if(currentTrackShowedFlag != 'loading') currentTrackShowedFlag = false;
		}
	}
	else { 	 //console.log("текущий трек ещё не был загружен", currentTrackName);
		//console.log(document.getElementById(currentTrackName));
		//console.log(tracks.querySelector('li[title="Current Track"]'));
		currentTrackShowedFlag = 'loading'; 	// укажем, что трек сейчас загружается
		selectTrack(document.getElementById(currentTrackName),trackList,trackDisplayed,displayTrack); 	// загрузим трек асинхронно. galadrielmap.js
	}
}
//console.log('Обновлён трек','currentTrackName='+currentTrackName,'currentTrackShowedFlag=',currentTrackShowedFlag);
} // end function currentTrackUpdate

// Сохранение переменных
var savePositionProcess = setInterval(doSavePosition,savePositionEvery); 	// велим сохранять всё каждые savePositionEvery
// Всегда после загрузки страницы "Следовать за курсором" включено
document.getElementById("followSwitch").checked = true; 	// выставим переключатель на панели Настроек в правильное положение
</script>
</body>
</html>
