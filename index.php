<?php
require_once('fcommon.php');
require_once('params.php'); 	// пути и параметры
//url службы записи пути. Если не установлена -- обновления пишущегося пути не происходит
$currentTrackServerURI = 'getlasttrkpt.php'; 	// uri of the active track service, if present. If not -- no log update
// 	Динамическое обновление маршрутов  Route updater
// 		url службы динамического обновления маршрутов. При отсутствии -- маршруты можно обновить только перезагрузив страницу.
$updateRouteServerURI = 'checkRoutes.php'; 	// url to route updater service. If not present -- update server-located routes not work.

$versionTXT = '2.8.4';
/* 
2.8.0	distance circles
2.7.0	favorite maps
2.6.0	Human-readable maps names.
2.5.0	Shows the heading with the cursor, and the course with the velocity vector. Specially for gpsd 3.24.1
2.3.5	With depth coloring along gpx.
*/
// start gpsdPROXY
exec("$phpCLIexec $gpsdPROXYpath/gpsdPROXY.php > /dev/null 2>&1 &");

// Интернационализация
// требуется, чтобы языки были перечислены в порядке убывания предпочтения, так что берём первый
$appLocale = explode('-',explode(';',explode(',',$_SERVER['HTTP_ACCEPT_LANGUAGE'])[0])[0])[0];	
if(file_exists("internationalisation/$appLocale.php")) {
	require_once("internationalisation/$appLocale.php");
}
else {
	$appLocale = 'en';
	require_once('internationalisation/en.php');
}
//require_once('internationalisation/en.php');

$mapsInfo = array();
if( $tileCachePath) { 	// если мы знаем про GaladrielCache
// Получаем список имён карт
	if($mapSourcesDir[0]=='/') $fullMapSourcesDir = $mapSourcesDir;	// если путь абсолютный (и в unix, конечно) $mapSourcesDir - из конфига GaladrielCache
	else  $fullMapSourcesDir = "$tileCachePath/$mapSourcesDir"; 	// сделаем путь абсолютным
	foreach(glob("$fullMapSourcesDir/*.php") as $name) {
		$mapName=explode('.php',end(explode('/',$name)))[0]; 	// basename не работает с неанглийскими буквами!!!!
		$humanName = array();
		include($name);
		if($humanName){	// из описания источника
			$mapsInfo[$mapName] = $humanName[$appLocale];	// $appLocale - из internationalisation
		}
		if(!$mapsInfo[$mapName]) $mapsInfo[$mapName] = $mapName;
	}
	asort($mapsInfo,SORT_LOCALE_STRING);
}
//echo "mapsInfo:<pre>"; print_r($mapsInfo); echo "</pre>";
 
// Получаем список имён треков
$trackInfo = array(); $currentTrackName = '';
if($trackDir) {
	$trackInfo = glob("$trackDir/*.gpx"); 	// gpxDir - из файла params.php
	array_walk($trackInfo,function (&$name,$ind) { 	// удаление расширения из имени в списке. А оно нужно?
		//$name=basename($name,'.gpx'); 	// 
		$name=explode('.gpx',end(explode('/',$name)))[0]; 	// basename не работает с неанглийскими буквами!!!!
	}); 	// 
	//echo "trackInfo:<pre>"; print_r($trackInfo); echo "</pre>";
	// Текущий трек -- именно последний, есди он не завершён, а не последний не завершённый.
	$currentTrackName = getLastTrackName($trackNames);	// fcommon.php
	if($currentTrackName) {	// там может не быть ни одного трека
		if(trim(tailCustom("$trackDir/$currentTrackName")) == '</gpx>'){ 	// трек завершён fcommon.php
			$currentTrackName = '';
		}
		else $currentTrackName = pathinfo($currentTrackName)['filename'];
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

	<script src="polycolor/polycolorRenderer.js"></script>
	<script src="value2color/value2color.js"></script>

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
	
	<script src="long-press-event/dist/long-press-event.min.js"></script>
	
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
		<ul role="tablist" id="featuresList">
			<li id="homeTab" <?php if(!$tileCachePath) echo 'class="disabled"';?>><a href="#home" role="tab"><img src="img/maps.svg" alt="menu" width="70%"></a></li>
			<li id="dashboardTab"><a href="#dashboard" role="tab"><img src="img/speed1.svg" alt="dashboard" width="70%"></a></li>
			<li id="tracksTab" <?php if(!$trackDir) echo 'class="disabled"';?>><a href="#tracks" role="tab"><img src="img/track.svg" alt="tracks" width="70%"></a></li>
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
		<div class="leaflet-sidebar-pane" id="home" style="height:100%;">
			<h1 class="leaflet-sidebar-header leaflet-sidebar-close"> <?php echo $homeHeaderTXT;?> <span class="leaflet-sidebar-close-icn"><img src="img/Triangle-left.svg" alt="close" width="16px"></span></h1>
			<div style="min-height:92%;">
				<br>
				<ul id="mapDisplayed" class='commonList'>
				</ul>
				<ul id="mapList" class='commonList'>
<?php
foreach($mapsInfo as $mapName => $humanName) {
?>
						<li hidden id="<?php echo $mapName;?>" onClick="{selectMap(event.currentTarget)}"><?php echo "$humanName";?> </li>
<?php
}
?>
				</ul>
			</div>
			<button id="showMapsToggler" onClick='showMapsToggle();' style="width:90%;height:1.5rem;margin-bottom:1rem;"><?php echo trim(explode(',',$showMapsTogglerTXT)[1],"'"); // установим режим "все карты", не смотря на то, что сейчас не показывается ни одной. При старте клиента будет вызван showMapsToggle, который переключит режим и покажет избранные именно для этого клиента?></button>			
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
						<br><span style="font-size:50%;" id="dashboardCourseTXTlabel"><?php echo $dashboardCourseTXT;?></span>
						<span style="font-size:30%;"><br><span id="dashboardCourseAltTXTlabel"><?php echo $dashboardCourseAltTXT;?></span></span>
					</div>
					<div style="">
						<span id='courseDisplay'></span>
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
					<input type="checkbox" name="onoffswitch" class="onoffswitch-checkbox" id="loggingSwitch" onChange="loggingRun();" <?php //if($gpxloggerRun) echo "checked"; // а вдруг не этот экземпляр клиента потребовал включить запись трека? ?>>
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
foreach($trackInfo as $trackName) { 	
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
			<div id='routeControls' class="routeControls" style="width:95%; padding:1rem 0 2rem; text-align: center;">
				<input type="radio" name="routeControl" class='L' id="routeCreateButton"
					onChange="
						pointsControlsDisable();	// отключить кнопки точек
						if(!currentRoute) currentRoute = dravingLines; 	// 
						//console.log('[Кнопка Начать] currentRoute:',currentRoute._leaflet_id,'dravingLines:',dravingLines._leaflet_id);
						let layer = map.editTools.startPolyline(false,drivedPolyLineOptions.options);
						layer.options.color = '#FDFF00';
						layer.feature = drivedPolyLineOptions.feature;
						layer.on('editable:editing', function (event){event.target.updateMeasurements();});	// обновлять расстояния при редактировании
						//layer.on('click', L.DomEvent.stop).on('click', tooggleEditRoute);
						layer.on('click',tooggleEditRoute);
						layer.addTo(currentRoute);
						routeEraseButton.disabled=false;
						//if(!routeSaveName.value || Date.parse(routeSaveName.value)) routeSaveName.value = new Date().toJSON(); 	// запишем в поле ввода имени дату, если там ничего не было или была дата
						if(!routeSaveName.value) routeSaveName.value = new Date().toJSON(); 	// запишем в поле ввода имени дату, если там ничего не было
						//console.log('[Кнопка Начать] layer:',layer);
					"
				>
				<label for="routeCreateButton"><?php echo $routeControlsBeginTXT;?></label>
				<input type="radio" name="routeControl" class='R' id="routeContinueButton"
					onChange="
						// по нажатию кнопки создаётся однократно срабатываемый обработчик клика
						// на вершине объекта editable
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
				<div id='pointsButtons'>
					<br>
					<button id='ButtonSetpoint' onClick='createEditableMarker(pointIcon);' class='pointButton'><img src="leaflet-omnivorePATCHED/symbols/point.png" alt="<?php echo $okTXT;?>" width="100%"></button>
					<button id='ButtonSetanchor' onClick='createEditableMarker(anchorIcon);' class='pointButton'><img src="leaflet-omnivorePATCHED/symbols/anchor.png" alt="<?php echo $okTXT;?>" width="100%"></button>
					<button id='ButtonSetcaution' onClick='createEditableMarker(cautionIcon);' class='pointButton'><img src="leaflet-omnivorePATCHED/symbols/caution.png" alt="<?php echo $okTXT;?>" width="100%"></button><br>
					<br>
				</div>
				<input id = 'editableObjectName' type="text" title="<?php echo $routeSaveTXT;?>" placeholder='<?php echo $routeSaveTXT;?>' size='255' style='width:90%;font-size:150%;'><br>
				<textarea id = 'editableObjectDescr' title="<?php echo $editableObjectDescrTXT;?>" rows='3' cols='255' placeholder='<?php echo $editableObjectDescrTXT;?>' style='width:87%;padding: 0.5rem 3%;'></textarea><br>
				<br>
				<input type="radio" name="routeControl" id="routeEraseButton"
					onChange="
						delShapes(true);	// удалим все редактируемые объекты
						routeControlsDeSelect();	// сделаем невыбранными кнопки управления рисованием маршрута
						routeCreateButton.disabled=false; 	// - сделать доступной кнопку Начать
						pointsControlsEnable();	// включим кнопки точек
						this.disabled=true;
						routeContinueButton.disabled=true;
						// раз не осталось редактируемых объектов, редактирование завершено? Сохраним.
						if(currentRoute==dravingLines)	doSaveMeasuredPaths();
						//else saveGPX();	// ?но загруженный файл не будем сохранять, потому что он тогда перезагрузится, и перестанет быть текущим редактируемым
						//currentRoute = null;	// ?не будем считать, что редактирование завершено
					"
				>
				<label for="routeEraseButton"><?php echo $routeControlsClearTXT;?></label>
			</div>
			<?php // Поиск места ?>
			<div style="width:95%;">
				<div style="margin:0;padding:0;">
					<button onClick='goToPositionField.value += "°";goToPositionField.focus();' style="width:2rem;height:1rem;margin:0 0.7rem 0 0;"><span style="font-weight: bold; font-size:150%;">°</span></button>
					<button onClick='goToPositionField.value += "′";goToPositionField.focus();' style="width:2rem;height:1rem;margin:0 0.7rem 0 0;"><span style="font-weight: bold; font-size:150%;">′</span></button>
					<button onClick='goToPositionField.value += "″";goToPositionField.focus();' style="width:2rem;height:1rem;margin:0 0rem 0 0;"><span style="font-weight: bold; font-size:150%;">″</span></button><br>
				</div>
				<span style=""><?php echo $routePosTXT;?></span><br>
				<input id='goToPositionField' type="text" title="<?php echo $goToPositionTXT;?>" size='12' style='width:11rem;font-size:150%;'>			
				<button id='goToPositionButton' onClick='flyByString(this.value);' class='okButton' style="float:right;"><img src="img/ok.svg" alt="<?php echo $okTXT;?>" width="16px"></button><br>
			</div>
			<div  style='width:98%;height:12rem;overflow:auto;margin:0.3rem 0;'>
				<ul id='geocodedList' class='commonList'>
				</ul>
			</div>
			<?php // Сохранение маршрута ?>
			<div style="width:95%; padding: 1rem 0; text-align: center;">
				<h3><?php echo $routeSaveTitle;?></h3>
				<input id = 'routeSaveName' type="text" title="<?php echo $routeSaveTXT;?>" placeholder='<?php echo $routeSaveTXT;?>' size='255' style='width:90%;font-size:150%;'>
				<textarea id = 'routeSaveDescr' title="<?php echo $routeSaveDescrTXT;?>" rows='5' cols='255' placeholder='<?php echo $routeSaveDescrTXT;?>' style='width:87%;padding: 0.5rem 3%;'></textarea><br>
				<br>
				<button onClick="
					saveGPX();
					currentRoute = null;
					routeSaveName.value = '';
					routeSaveDescr.value = '';" 
					type='submit' class='okButton' style="float:right;"><img src="img/ok.svg" alt="<?php echo $okTXT;?>" width="16px"></button>
				<button onClick='routeSaveName.value=""; routeSaveDescr.value="";' type='reset' class='okButton' style="float:left;"><img src="img/no.svg" alt="<?php echo $clearTXT;?>" width="16px"></button>
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
foreach($routeInfo as $routeName) { 	// event -- предопределённый объект, который браузер передаёт в качестве первого аргумента в функцию-обработчик
?>
					<li onClick='selectTrack(event.currentTarget,routeList,routeDisplayed,displayRoute);'<?php echo " id='$routeName'>$routeName"; // однако, имена в track и route могут совпадать...?></li>
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
						<div style="font-size:50%;">
							<span style="font-size:50%;display:block;"><?php echo $bearingTXT; ?></span>
							<span style="font-size:40%;display:block;"><?php echo $altBearingTXT; ?></span>
							<span style="margin:0.5rem;display:block;" id='azimuthMOBdisplay'> </span>
						</div>
						<div style="font-size:75%;margin:1rem 0;">
							<span style="font-size:40%;display:block;"><?php echo $distanceTXT ?>, <?php echo $dashboardMeterMesTXT ?></span>
							<span style="font-size:30%;display:block;"><?php echo $altDistanceTXT ?></span>
							<span style="margin:0.5rem;display:block;" id='distanceMOBdisplay'> </span>
							<span style="font-size:75%;margin:0.5rem;display:block;" id='directionMOBdisplay'></span>
						</div>
						<div style="font-size:50%;" onClick="doCopyToClipboard(Math.round(currentMOBmarker.getLatLng().lat*10000)/10000+' '+Math.round(currentMOBmarker.getLatLng().lng*10000)/10000);" >
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
		<div class="leaflet-sidebar-pane" id="download" style="height:100%;">
			<h1 class="leaflet-sidebar-header leaflet-sidebar-close"><?php echo $downloadHeaderTXT;?> <span class="leaflet-sidebar-close-icn"><img src="img/Triangle-left.svg" alt="close" width="16px"></span></h1>
			<div style="margin: 1rem 0 0.5 0;height:5rem;">
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
			<form id="dwnldJob" style="font-size:120%;margin:0;height:50%;" onSubmit="createDwnldJob(); return false;" onreset="dwnldJobZoom.innerHTML=map.getZoom(); downJob=false; tileGrid.redraw();">
					<div>
						<span style="display:inline-block;width:50%;">X</span><span style="display:inline-block;">Y</span>
					</div>
						<div style="height:75%;display:grid;grid-template-columns:50% auto;grid-column-gap:3px;grid-auto-rows:min-content;overflow-y:auto;overflow-x:hidden;margin:0.5rem 0 0.5rem 0;">
							<div style='margin-bottom:0.5em;'>
								<input type="text" pattern="[0-9]*" title="<?php echo $integerTXT;?>" class="tileX" size='12' style='width:6rem;font-size:150%;'>
							</div>
							<div style='margin-bottom:0.5em;'>
								<input type="text" pattern="[0-9]*" title="<?php echo $integerTXT;?>" class="tileY" size='12' style='width:6rem;font-size:150%;' onChange="XYentryFields(this);">
							</div>
						</div>
				<div style="width:95%;">
					<button type='reset' style="margin:0 1.75rem 0 0;width:4rem;padding:0.2rem;"><img src="img/no.svg" alt="<?php echo $clearTXT;?>" width="16px" ></button>
					<button type='submit' style="margin:0 0 0 1.75rem;width:4rem;padding:0.2rem;float:right;"><img src="img/ok.svg" alt="<?php echo $okTXT;?>" width="16px"></button>
				</div>
			</form>
			<div style="font-size:120%;margin:1rem 0;">
				<h3>
					<span id="loaderIndicator" style="font-size:75%;vertical-align:top;color:gray;">&#x2B24; </span><?php echo $downloadJobListTXT;?>:
				</h3>
				<ul id="dwnldJobList" style="margin:0;">
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
					<input type="checkbox" name="onoffswitch" class="onoffswitch-checkbox" id="currTrackSwitch" onChange="loggingWait();" checked>
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
			<div style="margin: 1rem 1rem;"> <?php// Показывать окружности дистанции ?>
				<div class="onoffswitch" style="float:right;margin: 1rem auto;"> <!--  Переключатель https://proto.io/freebies/onoff/  -->
					<input type="checkbox" name="onoffswitch" class="onoffswitch-checkbox" id="distCirclesSwitch" onChange="distCirclesToggler();">
					<label class="onoffswitch-label" for="distCirclesSwitch">
						<span class="onoffswitch-inner"></span>
						<span class="onoffswitch-switch"></span>
					</label>
				</div>
				<span style="font-size:120%"><?php echo $settingsdistCirclesTXT;?></span>
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
// Глобальные переменные
var appLocale = '<?php echo $appLocale; ?>';
// для загрузки Mapbox GL при необходимости. Из-за чего-то надо так.
var mapboxGLscript = null;	// скрипт Mapbox GL, загружается при открытии соответствующей карты. Эти глобальные переменные ни нафиг не нужны, но если грузить скрипты Mapbox GL где-то в глубине -- при закрытии карты возникает мутная ошибка.
var mapboxLeafletscript = null;	// скрипт mapbox-gl-leaflet
// Карта
var defaultMap = 'OpenTopoMap'; 	// Карта, которая показывается, если нечего показывать. Народ интеллектуальный ценз ниасилил.
var showMapsTogglerTXT = [<?php echo $showMapsTogglerTXT; ?>];	// подписи на кнопке все/избранные карты
var showMapsList = JSON.parse(getCookie('GaladrielshowMapsList')) || [];	// массив названий избранных карт
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
var followToCursor = true; 	// карта следует за курсором Обеспечивает только паузу следования при перемещениях и масштабировании карты руками
var noFollowToCursor = false; 	// карта никогда не следует за курсором Глобальное отключение следования. Само не восстанавливается.
var CurrnoFollowToCursor = 1; 	// глобальная переменная для сохранения состояния
var followPause = 10 * 1000; 	// пауза следования карты за курсором, когда карту подвинули руками, микросекунд
var savePositionEvery = 15 * 1000; 	// будем сохранять положение каждые микросекунд локально в куку
var followPaused; 	// объект таймера, который восстанавливает следование курсору
var velocityVectorLengthInMn = <?php echo $velocityVectorLengthInMn;?>; 	// длинной в сколько минут пути рисуется линия скорости
// Окружности дистанции
if(getCookie('GaladrielMapdistCirclesSwitch') == undefined) distCirclesSwitch.checked = true; 	// показывать окружности дистанции
else distCirclesSwitch.checked = Boolean(+getCookie('GaladrielMapdistCirclesSwitch')); 	// getCookie from galadrielmap.js
// AIS
var vehicles = {}; 	// list of visible by AIS data vehicle objects 
var AISstatusTXT = {
<?php foreach($AISstatusTXT as $k => $v) echo "$k: '$v',\n"; 	// не используется??>
}
// Loader
var downJob = false; 	// флаг - не создаётся ли задание на скачивание
// Пути и маршруты
var editorEnabled = false;	// семафор, что можно использовать редактирования
// Путь
var currentTrackServerURI = '<?php echo $currentTrackServerURI;?>'; 	// адрес для подключения к сервису, отдающему сегменты текущего трека
var trackDirURI = '<?php echo $trackDir;?>'; 	// адрес каталога с треками
var routeDirURI = '<?php echo $routeDir;?>'; 	// адрес каталога с маршрутами
var currentTrackName = '<?php echo $currentTrackName;?>'; 	// имя текущего (пишущегося сейчас) трека
var updateRouteServerURI = '<?php echo $updateRouteServerURI;?>'; 	// url службы динамического обновления маршрутов
if(getCookie('GaladrielcurrTrackSwitch') == undefined) currTrackSwitch.checked = true; 	// показывать текущий трек вместе с курсором
else currTrackSwitch.checked = Boolean(+getCookie('GaladrielcurrTrackSwitch')); 	// getCookie from galadrielmap.js
if(getCookie('GaladrielSelectedRoutesSwitch') == undefined) SelectedRoutesSwitch.checked = false; 	// показывать выбранные маршруты
else SelectedRoutesSwitch.checked = Boolean(+getCookie('GaladrielSelectedRoutesSwitch')); 	// getCookie from galadrielmap.js
var globalCurrentColor = 0xFFFFFF; 	// цвет линий и  значков кластеров после первого набора
var depthInData = <?php echo $depthInData;?>;	// параметры показа глубины вдоль пути
// Маршрут
var drivedPolyLineOptions;
var currentRoute; 	// L.layerGroup, по объекту Editable которого щёлкнули. Типа, текущий.
{let weight;
if(L.Browser.mobile && L.Browser.touch) weight = 10; 	// мобильный браузер
else weight = 7; 	// стационарный браузер
drivedPolyLineOptions = { options: {
		showMeasurements: true,	// включить показ расстояний
		//color: '#FDFF00',
		weight: weight,
		opacity: 0.5,
	},
	feature: {type: 'Feature',
		properties: { 	// типа, оно будет JSONLayer
			isRoute: true 	// укажем, что это путь
		},
	},
};
}
var dravingLines = L.layerGroup();	// слои, в которых, собственно, рисуются маршруты и путевые точки
dravingLines.properties = {};
var goToPositionManualFlag = false; 	// флаг, что поле goToPositionField стали редактировать руками, и его не надо обновлять

// Dashboard
var lat; 	 	// широта
var lng; 	 	// долгота, округлённые до 4-х знаков
var copyToClipboardMessageOkTXT = '<?php echo $copyToClipboardMessageOkTXT;?>';
var copyToClipboardMessageBadTXT = '<?php echo $copyToClipboardMessageBadTXT;?>';
var dashboardDepthMesTXT = '<?php echo $dashboardDepthMesTXT;?>';
var dashboardMeterMesTXT = '<?php echo $dashboardMeterMesTXT;?>';
var dashboardKiloMeterMesTXT = '<?php echo $dashboardKiloMeterMesTXT;?>';
var dashboardCourseTXT = '<?php echo $dashboardCourseTXT;?>';
var dashboardCourseAltTXT = '<?php echo $dashboardCourseAltTXT;?>';
var dashboardHeadingTXT = '<?php echo $dashboardHeadingTXT;?>';
var dashboardHeadingAltTXT = '<?php echo $dashboardHeadingAltTXT;?>';
var dashboardMHeadingTXT = '<?php echo $dashboardMHeadingTXT;?>';
var dashboardMHeadingAltTXT = '<?php echo $dashboardMHeadingAltTXT;?>';
var latTXT = '<?php echo $latTXT;?>';
var longTXT = '<?php echo $longTXT;?>';	
// MOB
var currentMOBmarker;
var relBearingTXT = [<?php echo $relBearingTXT; // internationalisation ?>]
// main output data
var upData = {};
DisplayAISswitch.checked = true;	// Показывать цели AIS. Всегда?

// Подготовленные картинки для случая off-line
const mob_markerImg = '<?php echo $mob_markerImg; ?>';

// Инициализируем список карт
if(!showMapsList.length) showMapsToggle(true);	// покажем в списке карт все карты, если нет избранных
else showMapsToggle();	// покажем только избранные, поскольку изначально не показывается ничего

// чего не сделаешь, если двойное нажатие не работает нигде, а на длительное в Google Chrome
// и иже с ним навешана всякая фигня, и непросто навешана, а с запрещением всего остального
function longressListener(e){
e.preventDefault();
//console.log(e.target);
if(showMapsToggler.innerHTML == showMapsTogglerTXT[0]) return;	// текущий режим - "избранные карты", в нём не работаем
if(showMapsList.includes(e.target.id)){	// это избранная карта
	const n = showMapsList.indexOf(e.target.id);
	showMapsList.splice(n,1);	// вырежем имя из массива
	e.target.classList.remove("showedMapName");
}
else {
	showMapsList.push(e.target.id);
	e.target.classList.add("showedMapName");
}
event.stopImmediatePropagation();	// прекратим всплытие и обломим все имеющиеся обработчики. Вдруг фигня, навешенная скотским Google, перестанет работать.
//console.log('[longressListener] Список избранных карт:',showMapsList);
} // end function long-pressListener

let touchstartX, touchstartY;
function handleSwipe(event){
let touchendX=event.changedTouches[0].screenX; 
let touchendY=event.changedTouches[0].screenY; 
//alert(`handleSwipe touchstartY=${touchstartY}, touchendY=${touchendY}`);
if((touchendX > touchstartX+10) && (Math.abs(touchendY-touchstartY)<10)){	// вправо горизонтально
	//alert('handleSwipe горизонтальный жест');
	longressListener(event);
}
} // end function handleSwipe()

for(let mapLi of mapList.children){	// назначим обработчик длинного нажатия на каждое название карты, потому что его можно назначить только так
	mapLi.addEventListener('long-press', longressListener); 
	// а также обработчики свайпа, ибо в мобильных Chrome вообще всё через жопу
	mapLi.addEventListener('touchstart',function(e){touchstartX=e.changedTouches[0].screenX; touchstartY=e.changedTouches[0].screenY;});
	mapLi.addEventListener('touchend',handleSwipe);
}

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
	prefix: '<a href="https://youtu.be/kwMt4rjgsJs"  target=”_blank”><i>имевший цель, но чуждый смысла</i></a>'
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
sidebar.on("content", function(event){ 	// Событие открытия панели с информацией о вкладке. А такого же события закрытия нет.
	//console.log('sidebar.on "content"',event.id);
	switch(event.id){ 	// какую вкладку открыли
	case 'tracks':	// треки
		loggingCheck();
		break;
	case 'measure': 	// рисование маршрута
		centerMarkOn(); 	// включить крестик в середине
		if(CurrnoFollowToCursor === 1)CurrnoFollowToCursor = noFollowToCursor;  // запомним состояние глобального признака следования за курсором, если ещё не запоминали
		noFollowToCursor = true; 	// отключим следование за курсором
		editorEnabled = true;	// разрешим редактирования
		routeCreateButton.disabled=false; 	// - сделать доступной кнопку Начать
		pointsControlsEnable();	// включим кнопки точек
		break;
	case 'MOB': 	// человек за бортом
		if(!map.hasLayer(mobMarker)) MOBalarm();
		else if(!map.hasLayer(cursor)) centerMarkOn(); 	// включить крестик в середине
		break;
	case 'download':
		chkLoaderStatus();	// проверим загрузки
		tileGrid.addTo(map); 	// добавить на карту тайловую сетку
		if(CurrnoFollowToCursor === 1)CurrnoFollowToCursor = noFollowToCursor;  // запомним состояние глобального признака следования за курсором, если ещё не запоминали
		noFollowToCursor = true; 	// отключим следование за курсором
		break;
	}
});
sidebar.on("closing", function(){
	//console.log('sidebar closing',map.editTools.drawing(),currentRoute);
	tileGrid.remove(); 	// удалить с карты тайловую сетку
	if(CurrnoFollowToCursor !== 1) noFollowToCursor = CurrnoFollowToCursor; 	// восстановим признак следования за курсором
	CurrnoFollowToCursor = 1;
	centerMarkOff(); 	// выключить крестик в середине
	if(currentRoute && delShapes()) editorEnabled='maybe';	// есть редактируемые слои
	else {
		editorEnabled=false; 	// если нет редактируемых слоёв -- запретим включать редактирования
		currentRoute = null;
		routeSaveName.value = '';
		routeSaveDescr.value = '';
	}
});
// end controls
// Поведение карты
map.on('movestart zoomstart', function(event) { 	// карту начали двигать руками
	// функция отменяет следование карты за курсором, и устанавливает таймер, чтобы вернуть
	// пытается отделить собственные движения карты от юзерских, включая изменение масштаба
	if(userMoveMap) { 	// Убран флаг в куске, двигающем карту за курсором
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
	if(distCirclesSwitch.checked) distCirclesUpdate(distCircles);	// нарисуем круги дистанции
	if(map.hasLayer(centerMark)) centerMarkUpdate();	// нарисуем круги дистанции крестика в центре
});
<?php if($trackDir OR $routeDir) {?>
map.on('moveend', updateClasters); 	// кластеризация точек POI, показывает кластеры в области просмотра
<?php }?>    
map.on("layeradd", function(event) {
	//alert(tileGrid);
	if(tileGrid) tileGrid.bringToFront(); 	// выведем наверх слой с сеткой
});

// Восстановим слои
<?php if( $tileCachePath) { // если работаем через GaladrielCache?>
var layers = JSON.parse(getCookie('GaladrielMaps')); 	// getCookie from galadrielmap.js
// Занесём слои на карту
if(layers) layers.reverse().forEach(function(layerName){ 	// потому что они там были для красоты последним слоем вверх
		// если, скажем, поменялось имя источника карты, а она уже показывалась со старым именем,
		// то в куке будет старое имя, selectMap обломается и всё сломается.
		const node = document.getElementById(layerName);	
		if(node) selectMap(node);
	});
else selectMap(document.getElementById(defaultMap)); 	// покажкм defaultMap
coverage();	// Восстановим показ карты покрытия. Хотя состояние переключателя карты покрытия не сохраняется, firefox сохраняет состояние переключателя при простой перезагрузке страницы.
<?php }
else {?>
displayMap('default');
<?php }?>

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

// Восстановим показываемые из gpx пути
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

// Рисование маршрута
dravingLines.addTo(map);
doRestoreMeasuredPaths(); 	// восстановим из кук сохранённые на устройстве маршруты
routeControlsDeSelect(); 	// сделать кнопки рисования невыбранными

var pointIcon = L.icon({
	iconUrl: 'leaflet-omnivorePATCHED/symbols/point.png',
	iconSize: [32, 37],
	iconAnchor: [16, 37],
	tooltipAnchor: [16,-25],
	className: 'wpIcon',
});
var anchorIcon = L.icon({
	iconUrl: 'leaflet-omnivorePATCHED/symbols/anchor.png',
	iconSize: [32, 37],
	iconAnchor: [16, 37],
	tooltipAnchor: [16,-25],
	className: 'wpIcon'
});
var cautionIcon = L.icon({
	iconUrl: 'leaflet-omnivorePATCHED/symbols/caution.png',
	iconSize: [32, 37],
	iconAnchor: [16, 37],
	tooltipAnchor: [16,-25],
	className: 'wpIcon'
});

/*
map.on('editable:editing', // обязательный обработчик для editable для перересовывания расстояний при изменении пути
	function (e) {
		//console.log('обязательный обработчик для editable start by editable:editing',e);
		// А это норм, что оно глобально?
		if (e.layer instanceof L.Path) e.layer.updateMeasurements();
    }
);
*/
map.on('editable:drawing:end',	function(event) {
	 // выключать кнопку "Начать" при окончании рисования, сделать доступной "Продолжить"
	//console.log('map.on [editable:drawing:end] event.target:',event.target);
	/*
	if(event.layer instanceof L.Marker){
		console.log('[map.on editable:drawing:end] event.layer is a L.marker');
	}
	*/
	if(event.layer instanceof L.Path){
		//console.log('[map.on editable:drawing:end] event.layer is a L.Path');
		routeContinueButton.disabled=false;
	}
	routeCreateButton.checked=false;
});
map.on('editable:vertex:dragstart',	function(event) {
	window.navigator.vibrate(200); // Вибрировать 200ms
});

// Круги дистанции
var distCircles = [];
var centerMarkCircles = [];
for (let n=0; n<4; n++) {
	centerMarkCircles.push(	L.circle([], {
		color: '#FD00DB',
		weight: 1,
		opacity: 0.6,
		fill: false,
		pane: 'overlayPane',
		zIndexOffset: -503
	}));
	distCircles.push(	L.circle([], {
		color: '#FD00DB',
		weight: 1,
		opacity: 0.6,
		fill: false,
		pane: 'overlayPane',
		zIndexOffset: -503
	}));
};

// центр экрана
let centerMarkIcon = new L.divIcon({
	className: "centerMarkIcon"	// galadrielmap.css Установить прозрачность фона иначе, чем внешним стилем не удаётся.
});
var centerMarkMarker = L.marker(map.getBounds().getCenter(), {
	'icon': centerMarkIcon,
	pane: 'overlayPane',	// расположим маркер над тайлами, но ниже всего остального
	zIndexOffset: -1000
});
var centerMark = L.layerGroup([centerMarkMarker]);
centerMarkCircles.forEach(circle => circle.addTo(centerMark));

// Местоположение
// маркеры
var GpsCursor = L.icon({
	iconUrl: './img/gpscursor.png',
	iconSize:     [120, 120], // size of the icon
	iconAnchor:   [60, 60], // point of the icon which will correspond to marker's location
});

// курсор
var NoGpsCursor = L.icon({	// этот значёк может показываться и при пропаже связи с сервером, а в этом случае загрузить картинку не удастся. Но таскать с собой картинку заранее, как для MOB -- наверно, слишком накладно. Поэтому -- стилем.
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
	icon: GpsCursor,
	rotationAngle: 0, // начальный угол поворота маркера
	rotationOrigin: "50% 50%", 	// вертим маркер вокруг центра
	pane: 'overlayPane',	// расположим маркер над тайлами, но ниже всего остального
	zIndexOffset: -500
});
// указатель скорости
var velocityVector = L.marker(cursor.getLatLng(), {
	icon: velocityCursor,
	rotationAngle: 0, // начальный угол поворота маркера
	opacity: 0.1,
	pane: 'overlayPane',	// расположим маркер над тайлами, но ниже всего остального
	zIndexOffset: -501
});
velocityVectorLengthInMnDisplay.innerHTML = velocityVectorLengthInMn; 	// нарисуем цену вектора скорости на панели управления
// Точность ГПС
let GNSScircle = L.circle(cursor.getLatLng(), {
	radius: 10,
	color: '#000000',
	weight: 0,
	opacity: 0.1,
	fillOpacity: 0.1,
	pane: 'overlayPane',	// расположим маркер над тайлами, но ниже всего остального
	zIndexOffset: -502
});

// Курсор: объединение всех фигур
var positionCursor = L.layerGroup([GNSScircle,velocityVector,cursor]);
if(distCirclesSwitch.checked) distCircles.forEach(circle => circle.addTo(positionCursor));

// Для визуализации collisionDetector
var collisionIcon = L.icon({
	iconUrl: './img/redbulletdot.svg',
	iconSize:     [60, 60],
	iconAnchor:   [30, 30]
});
var collisionDirectionIcon = L.icon({
	iconUrl: './img/redArrow.svg',
	iconSize:     [20,24],
	iconAnchor:   [10,30]
});
var collisisonDetected = L.layerGroup([],{pane: 'overlayPane'}); 	// слой, на котором рисуются значки возможных столкновений collisionDetector
var collisionDirectionsCursor = L.layerGroup();	// слой с указателями направлений на опасности столкновений
if(DisplayAISswitch.checked) collisionDirectionsCursor.addTo(positionCursor);	// слой с указателями направлений на опасности столкновений

/*//////////////////////////// for collision test purpose //////////////////////////////////
var collisisonAreas = L.layerGroup(); 	// для тестовых целей collisionDetector
///////////////////////////// for collision test purpose /////////////////////////////////*/

// MOB marker
var mobIcon = L.icon({ 	// 
	// поскольку картинка скачивается браузером только в момент показа, то, если хотеть
	// работу при отсутствии сервера -- картинка должна быть заранее
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



// Realtime периодическое получение внешних данных
<?php
if($gpsdProxyHost=='localhost' or $gpsdProxyHost=='127.0.0.1' or $gpsdProxyHost=='0.0.0.0') $gpsdProxyHost = $_SERVER['HTTP_HOST'];
?>
let subscribe = ['TPV','AIS','ALARM'];

var spatialWebSocket; // будет глобальным сокетом
var lastDataUpdate=0;	// момент последнего получения данных
var PosFreshBeforeMultiplexor=30;	// через сколько интервалов PosFreshBefore убирать курсор совсем
var lastPositionUpdate=0;	// момент последнего обновления координат

function spatialWebSocketStart(){
/**/
let checkDataFreshInterval;	// объект периодического запуска проверки свежести данных
if(!DisplayAISswitch.checked) subscribe = subscribe.filter(i=>i!='AIS');

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
	//
}; // end spatialWebSocket.onopen

spatialWebSocket.onmessage = function(event) {
	//console.log(event);
	//console.log(`[message] Данные получены с сервера: ${event.data}`);
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
		spatialWebSocket.send('?WATCH={"enable":true,"json":true,"subscribe":"'+subscribe.join()+'","minPeriod":"'+minWATCHinterval+'"};');
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
		//console.log('spatialWebSocket: TPV recieved',data);
		realtimeTPVupdate(data);
		break;
	case 'AIS':
		realtimeAISupdate(data);
		break;
	case 'ALARM':
		//console.log('recieved ALARM data',data);
		for(const alarmType in data.alarms){
			switch(alarmType){
			case 'MOB':
				realtimeMOBupdate(data.alarms.MOB);
				break;
			case 'collisions':
				//console.log('recieved ALARM collisions data',data.alarms.collisions);
				if(!DisplayAISswitch.checked) break;	// если в параметрах не указано показывать AIS
				// Вообще-то, хотелось бы чтобы когда-нибудь указывалась опасность столкновения
				// не только с судами. Но пока у нас только цели AIS.
				realtimeCollisionsUpdate(data.alarms.collisions);
				//realtimeCollisionsUpdate(data.alarms.collisions,data.alarms.collisionSegments);	///////// for collision test purpose /////////
				break;
			}
		}
		break;
	}
}; // end spatialWebSocket.onmessage

spatialWebSocket.onclose = function(event) {
	console.log(`spatialWebSocket closed: connection broken with code ${event.code} by reason ${event.reason}`);
	window.setTimeout(spatialWebSocketStart, 3000); 	// перезапустим сокет через  секунд. В каком контексте здесь вызывается callback -- мне осталось непонятным, поэтому сокет ваще глобален
	//console.log('lastDataUpdate=',lastDataUpdate,'PosFreshBefore=',PosFreshBefore,Date.now()-lastDataUpdate);
	if((Date.now()-lastDataUpdate)>PosFreshBefore*PosFreshBeforeMultiplexor) {	// обычно PosFreshBefore -- 3-5 секунд
		positionCursor.remove(); 	// уберём курсор (layerGroup) с карты
		for(const vehicle in vehicles){	// уберём цели AIS с карты
			vehicles[vehicle].remove();
			vehicles[vehicle] = null;
			delete vehicles[vehicle];
		}
		collisisonDetected.clearLayers();	// очистим слой 
		collisisonDetected.remove();
		collisionDirectionsCursor.clearLayers();
		collisionDirectionsCursor.remove();
	}
	else cursor.setIcon(NoGpsCursor)	// заменим курсор (значёк) на серый
	velocityDial.innerHTML = '&nbsp;'; 	// обнулим панель приборов
	courseDisplay.innerHTML = '&nbsp;';
	locationDisplay.innerHTML = '&nbsp;';
	depthDial.innerHTML = '';
	//MOBtab.className='disabled'; 	// если нет курсора (координат) -- невозможно включить режим MOB. Это плохая идея.
	clearInterval(checkDataFreshInterval);	// остановить периодическую проверку свежести
}; // end spatialWebSocket.onclose

spatialWebSocket.onerror = function(error) {
	console.log(`[spatialWebSocket error] ${error.message}`);
	fetch('gpsdPROXYtry.php');	// если сервер перегрузился, там не запущен gpsdPROXY.
}; // end spatialWebSocket.onerror

//return spatialWebSocket;	
}; // end function spatialWebSocketStart

function spatialWebSocketStop(message=''){
	console.log('Stop recieve TPV',);
	spatialWebSocket.close(1000,message);
} // end function spatialWebSocketStop


function watchAISstart() {
let res = false;
if(spatialWebSocket.readyState == 1) {
	subscribe = subscribe.filter(i=>i!='AIS');
	subscribe.push('AIS');
	res = true;
	try {
		spatialWebSocket.send('?WATCH={"enable":true,"json":true,"subscribe":"'+subscribe.join()+'","minPeriod":"'+minWATCHinterval+'"};');
	}
	catch(err) {
		res = false;
	}
	collisionDirectionsCursor.addTo(positionCursor);	// слой с указателями направлений на опасности столкновений
}
return res;
} // end function watchAISstart

function watchAISstop() {
let res = false;
if(spatialWebSocket.readyState == 1) {
	subscribe = subscribe.filter(i=>i!='AIS');
	res = true;
	try {
		spatialWebSocket.send('?WATCH={"enable":true,"json":true,"subscribe":"'+subscribe.join()+'","minPeriod":"'+minWATCHinterval+'"};');
	}
	catch(err) {
		res = false;
	}
}
return res;
} // end function watchAISstop

function watchAISswitching(){
let res;
if(DisplayAISswitch.checked) {
	res = watchAISstart();
	//console.log('[watchAISswitching] START res=',res,'DisplayAISswitch.checked=',DisplayAISswitch.checked,);
	if(!res) DisplayAISswitch.checked = false;
}
else {
	res = watchAISstop('Dispalying AIS stopped');
	//console.log('[watchAISswitching] STOP res=',res,'DisplayAISswitch.checked=',DisplayAISswitch.checked,);
	// даже если неуспешно, всё равно
	for(const vehicle in vehicles){	// уберём цели AIS с карты
		vehicles[vehicle].remove();
		vehicles[vehicle] = null;
		delete vehicles[vehicle];
	};
	collisionDirectionsCursor.clearLayers();	// очистим слой указателей направлений на опасности столкновений на курсоре
	collisionDirectionsCursor.remove();
	collisisonDetected.clearLayers();	// очистим слой 
	collisisonDetected.remove();
	if(!res) DisplayAISswitch.checked = true;
}
}; // end function watchAISswitching

spatialWebSocketStart(); 	// запускам периодическую функцию получать TPV

// Обработчики сообщений
// Позиционирование
function realtimeTPVupdate(gpsdData) {
//console.log('Index gpsdData',gpsdData);
//console.log('Index gpsdData.MOB',gpsdData.MOB);
// Положение неизвестно
//console.log('Index gpsdData',gpsdData.lon,gpsdData.lat);
if(gpsdData.error || (gpsdData.lon == null)||(gpsdData.lat == null) || (gpsdData.lon == undefined)||(gpsdData.lat == undefined)) { 	// 
	console.log('No spatial info in GPSD data',gpsdData);
	//console.log('lastPositionUpdate=',lastPositionUpdate,'PosFreshBefore*PosFreshBeforeMultiplexor=',PosFreshBefore*PosFreshBeforeMultiplexor,Date.now()-lastPositionUpdate);
	if((Date.now()-lastPositionUpdate)>PosFreshBefore*PosFreshBeforeMultiplexor) {	// обычно PosFreshBefore -- 3-5 секунд
		positionCursor.remove(); 	// уберём курсор (layerGroup) с карты
		collisisonDetected.clearLayers();	// очистим слой 
		collisisonDetected.remove();
		collisionDirectionsCursor.clearLayers();
		collisionDirectionsCursor.remove();
	}
	else cursor.setIcon(NoGpsCursor)	// заменим курсор (значёк) на серый
	velocityDial.innerHTML = '&nbsp;'; 	// обнулим панель приборов
	courseDisplay.innerHTML = '&nbsp;';
	locationDisplay.innerHTML = '&nbsp;';
	depthDial.innerHTML = '';
	//MOBtab.className='disabled'; 	// если нет курсора (координат) -- невозможно включить режим MOB. Это плохая идея.
	return;
}
// Свежее ли положение известно
lastPositionUpdate = Date.now();
//MOBtab.className=''; 	// координаты появились -- можно включить режим MOB
positionCursor.invoke('setLatLng',[gpsdData.lat,gpsdData.lon]); // установим координаты всех маркеров
if(distCirclesSwitch.checked) distCirclesUpdate(distCircles);	// нарисуем круги дистанции
var positionTime = new Date(gpsdData.time);
var now = new Date();
//console.log('gpsdData.time:',gpsdData.time,'now',now,'now-positionTime',(now-positionTime)/1000);
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

// Направление
//console.log('Index gpsdData',gpsdData.track);
velocityVector.setLatLng( cursor.getLatLng() );// положение указателя скорости
if(gpsdData.track == null || gpsdData.track == undefined) {
	if(gpsdData.heading !== undefined) {	// зато есть курс
		positionCursor.invoke('setRotationAngle',gpsdData.heading); // повернём все маркеры
		courseDisplay.innerHTML = Math.round(gpsdData.heading); // покажем направление на приборной панели
		// Заменим подписи
		dashboardCourseTXTlabel.innerHTML = dashboardHeadingTXT;
		dashboardCourseAltTXTlabel.innerHTML = dashboardHeadingAltTXT
	}
	else if(gpsdData.mheading !== undefined){	// или магнитный курс
		if(gpsdData.magvar !== undefined) {		// если есть склонение -- он истинный курс
			let heading = gpsdData.mheading + gpsdData.magvar;
			positionCursor.invoke('setRotationAngle',heading); // повернём все маркеры
			courseDisplay.innerHTML = Math.round(heading); // покажем направление на приборной панели
			// Заменим подписи
			dashboardCourseTXTlabel.innerHTML = dashboardHeadingTXT
			dashboardCourseAltTXT.innerHTML = dashboardHeadingAltTXT
		}
		else {
			positionCursor.invoke('setRotationAngle',gpsdData.mheading); // повернём все маркеры
			courseDisplay.innerHTML = Math.round(gpsdData.mheading); // покажем направление на приборной панели
			// Заменим подписи
			dashboardCourseTXTlabel.innerHTML = dashboardMHeadingTXT
			dashboardCourseAltTXT.innerHTML = dashboardMHeadingAltTXT
		}
	}
	else {	// нет никакой информации о направлении
		courseDisplay.innerHTML = '&nbsp;';
		cursor.setRotationAngle(0); // повернём маркер
		velocityVector.setRotationAngle(0); // повернём указатель скорости
	}
}
else {
	velocityVector.setRotationAngle(gpsdData.track); // повернём указатель скорости
	// gpsdData.heading есть, только если данные от SignalK, а если от gpsd -- никогда нет
	if(gpsdData.heading !== undefined) cursor.setRotationAngle(gpsdData.heading);
	else if((gpsdData.mheading !== undefined) && (gpsdData.magvar !== undefined)) cursor.setRotationAngle(gpsdData.mheading + gpsdData.magvar);
	else cursor.setRotationAngle(gpsdData.track); // повернём маркер
	courseDisplay.innerHTML = Math.round(gpsdData.track); // покажем направление на приборной панели
	// Заменим подписи, вдруг до этого не было путевого угла
	dashboardCourseTXTlabel.innerHTML = dashboardCourseTXT
	dashboardCourseAltTXTlabel.innerHTML = dashboardCourseAltTXT
}
positionCursor.addTo(map); 	// добавить курсор на карту

// Окружность точност ГПС
var errGNSS = (+gpsdData.errX+gpsdData.errY)/2;
if(!errGNSS) errGNSS = 10; // метров
if(errGNSS/metresPerPixel > 15) GNSScircle.setRadius(errGNSS); 	// кружок точности больше кружка курсора
else GNSScircle.setRadius(0);
//GNSScircle.setLatLng(cursor.getLatLng());	// оно часть общего курсора, и его координаты и так выставляются?

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
	let heading = gpsdData.track || gpsdData.heading || gpsdData.mheading;	// для словесного определения направления - всё равно
	if(heading) {
		let relBearing = azimuth-heading+22.5;	// половина от 45 против часовой стрелке
		if(relBearing<0) relBearing = 360+relBearing;
		relBearing = Math.floor(relBearing/45); 	// курсовой угол (relative bearing) / 45 градусов -- номер сектора, против часовой стрелки
		if(relBearing>7) relBearing = 0;
		directionMOBdisplay.innerHTML = relBearingTXT[relBearing];
	}
}

//displayCollisionAreas(gpsdData.collisionArea);	///////// for collision test purpose /////////

}; // end function realtimeTPVupdate

// Данные AIS
function realtimeAISupdate(aisClass) {
// Показывает цели AIS, перечисленные в aisClass.ais
// те, которых там нет -- перестаёт показывать
//console.log(aisClass); 	// 
let aisData = aisClass.ais;
//console.log("[realtimeAISupdate] aisData:",aisData); 	// массив с данными целей
//console.log(DisplayAISswitch);
let vehiclesVisible = [];
for(let vehicle in aisData){	// vehicle == mmsi
	 vehicle = vehicle.toString();
	//console.log(vehicle,aisData[vehicle]);
	if(vehicle.toLowerCase() == 'error') break;
	//if(vehicle=='371255000') console.log('aisData[vehicle]:',JSON.stringify(aisData[vehicle]));
	//console.log(aisData[vehicle].lat);	console.log(aisData[vehicle].lon);
	if((aisData[vehicle].lat === null) || (aisData[vehicle].lon === null) || (aisData[vehicle].lat === undefined) || (aisData[vehicle].lon === undefined)) continue;	// не показываем цели без координат

	if(!vehicles[vehicle]) { 	// global var, массив layers с целями
		//console.log("[realtimeAISupdate] vehicle=",vehicle,"aisData[vehicle]:",aisData[vehicle]);
		//console.log('aisData[vehicle].collisionArea',aisData[vehicle].collisionArea);
		let defaultSymbol;
		let noHeadingSymbol;
		if(aisData[vehicle].netAIS) { 	// цель получена от netAIS
			defaultSymbol = [1*0.5,0, 0.25*0.5,0.25*0.5, 0,1*0.5, -0.25*0.5,0.5*0.5, -1*0.5,0.75*0.5, -1*0.5,-0.75*0.5, -0.25*0.5,-0.5*0.5, 0,-1*0.5, 0.25*0.5,-0.25*0.5]; 	// треугольник, расстояния от центра, через которые нарисуют polyline
			noHeadingSymbol = [1*0.35,0, 0.75*0.35,0.5*0.35, 1*0.35,1*0.35, 0.5*0.35,0.75*0.35, 0,1*0.35, -0.5*0.35,0.75*0.35, -1*0.35,1*0.35, -0.75*0.35,0.5*0.35, -1*0.35,0, -0.75*0.35,-0.5*0.35, -1*0.35,-1*0.35, -0.5*0.35,-0.75*0.35, 0,-1*0.35, 0.5*0.35,-0.75*0.35, 1*0.35,-1*0.35, 0.75*0.35,-0.5*0.35]; 	// ромбик: правый, верхний, левый, нижний ПРотив часовой от правого?
			//console.log(aisData[vehicle]);
		}
		else { 	// цель получена от локального приёмника AIS
			defaultSymbol = [0.8,0, -0.3,0.35, -0.3,-0.35]; 	// треугольник вправо, расстояния от центра, через которые нарисуют polyline
			noHeadingSymbol = [0.35,0, 0,0.35, -0.35,0, 0,-0.35]; 	// ромбик
		}
		vehicles[vehicle] = L.trackSymbol([aisData[vehicle].lat,aisData[vehicle].lon],{
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

	vehicles[vehicle].addData(aisData[vehicle]); 	// обновим данные
	//console.log(vehicles[vehicle].getLatLng());
	//console.log(vehicles[vehicle]);

	vehiclesVisible.push(vehicle); 	// запомним, какие есть
}

for(const vehicle in vehicles){
	if(vehiclesVisible.includes(vehicle) && DisplayAISswitch.checked) continue; 	// типа, синхронизация... clearInterval -- асинхронная функция, и может не успеть отключить опрос AIS до того, как цели будут убраны с экрана. Тогда они уберутся здесь.
	vehicles[vehicle].remove();
	vehicles[vehicle] = null;
	delete vehicles[vehicle];
}

//displayCollisionAreas();	///////// for collision test purpose /////////
} // end function realtimeAISupdate

// MOB
function realtimeMOBupdate(MOBdata) {
// pre MOB -- даже если у нас нет координат, полезно показать маркеры MOB
if(MOBdata.status === false) { 	// режим MOB надо выключить
	if(map.hasLayer(mobMarker)){ 	// если показывается мультислой с маркерами MOB
		MOBclose(); 	// пришло, что режима MOB нет -- завершим его
	}
}
else { 	//console.log('режим MOB есть, пришли новые данные');
	//console.log('Index data.MOB',data.MOB);
	// создадим GeoJSON
	let mobMarkerJSON = {"type":"FeatureCollection",
						"features":[]
						};
	for(const point of MOBdata.points){
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
	mobMarker = null; 	// ритуальное действие. Возможно, оно воздействует на сборщик мусора, и приведёт к быстрому реальному удалению объекта, но это ни откуда не следует.
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
} // end function realtimeMOBupdate

// Обнаружение столкновений
function realtimeCollisionsUpdate(collisions,collisionSegments=null){
/*
collisionSegments ///////// for collision test purpose /////////
*/
//console.log('[realtimeCollisionsUpdate] collisions',collisions);
collisisonDetected.clearLayers();	// очистим слой меток на судах
collisionDirectionsCursor.clearLayers();	// очистим слой меток на курсоре
if(!collisions || JSON.stringify(collisions)=='[]'){
	collisisonDetected.remove();
	return;
}
for(const vesselID in collisions){
		// Значки опасностей
		collisisonDetected.addLayer( L.marker(collisions[vesselID], {
			icon: collisionIcon,
			opacity: 0.5,
			pane: 'overlayPane',
			zIndexOffset: -1000
		}));
		// Указатели вокруг курсора
		const selflatLng = cursor.getLatLng();
		if(selflatLng){
			//console.log('Опасность с',bearing(selflatLng, collisions[vesselID]));
			collisionDirectionsCursor.addLayer( L.marker(selflatLng, {
				icon: collisionDirectionIcon,
				opacity: 0.75,
				rotationAngle: bearing(selflatLng, collisions[vesselID])
			}));
		}
};

/*//////// for collision test purpose /////////
//console.log(collisionSegments);						
// Общий объемлющий прямоугольник
collisionSegments.unitedSquareAreas.forEach(area => {
	//console.log('unitedSquareArea:',area);
	let polyline = [
		[area.topLeft.lat,area.topLeft.lon],
		[area.bottomRight.lat,area.topLeft.lon],
		[area.bottomRight.lat,area.bottomRight.lon],
		[area.topLeft.lat,area.bottomRight.lon],
		[area.topLeft.lat,area.topLeft.lon]
	];
	collisisonDetected.addLayer(L.polyline(polyline,{color: 'green',weight: 2,}));
});

// Пересекающиеся отрезки
if(collisionSegments.intersections){
	//console.log('collisionSegments.intersections:',collisionSegments.intersections);
	for(const vesselID in collisionSegments.intersections){
		collisionSegments.intersections[vesselID].forEach(segment => {
			//console.log('collisionSegment:\n',segment);
			let polyline = [
				[segment[0][0].lat,segment[0][0].lon],
				[segment[0][1].lat,segment[0][1].lon]
			];
			collisisonDetected.addLayer(L.polyline(polyline,{color: 'yellow',weight: 6,}));
			polyline = [
				[segment[1][0].lat,segment[1][0].lon],
				[segment[1][1].lat,segment[1][1].lon]
			];
			collisisonDetected.addLayer(L.polyline(polyline,{color: 'yellow',weight: 6,}));
		});
	};
}
///////// for collision test purpose ////////*/

collisisonDetected.addTo(map);	// а collisionDirectionsCursor часть positionCursor, и оно и так addTo(map)
collisisonDetected.setZIndex(-1000);
} // end function realtimeCollisionsUpdate



// 	Запуск всяких периодических функций	 realtime -- в galadrielmap.js, функция, асинхронно обращающаяся к uri
//setInterval(function(){realtime(gpsanddataServerURI,realtimeTPVupdate,lat);},1000); 	// данные позиционирования. Однако, function(){} компилячится каждый оборот, что как бы неправильно.
//setInterval(realtime,1000,gpsanddataServerURI,realtimeTPVupdate,upData); 	// данные позиционирования. Здесь компилячится при загрузке, и параметры передаются в realtime один раз. Что исключает динамические параметры. А как же передача по ссылке?

//var updateRoutesInterval = setInterval(function(){realtime(updateRouteServerURI,routeUpdate);},2000);
var updateRoutesInterval;
if(updateRouteServerURI) updateRoutesInterval = setInterval(realtime,3000,updateRouteServerURI,routeUpdate);

// Динамическое обновление показываемых маршрутов
function routeUpdate(changedRouteNames) {
/* Вызывается из-под realtime */
//console.log('changedRouteNames:',changedRouteNames);
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
if(changedRouteNames.error) return;
for(const name of changedRouteNames){
	node = document.getElementById(name); 	// однако, в trackDisplayed могут быть те же имена. Забить? в querySelector требуется экранирование пробелов и спец-символов. Это секс.
	//console.log('[routeUpdate] node:',name,node);
	if(node.parentNode != routeDisplayed) continue; 	// элемент, конечно, всегда есть, нужно, чтобы он показывался
	//console.log('[routeUpdate] replase node:',node);
	savedLayers[name].remove(); 	// удалим слой с карты
	savedLayers[name] = null; 	// обозначим, что слоя с таким именем у нас нет
	displayRoute(node); 	// перересуем маршрут
}
} // end  function routeUpdate

// Текущий трек
// Должен обновляться, даже если обновлялка не описана в конфиге, потому что трек может писать кто-то ещё. 
// Но как его обновлять, если нет обновлялки? Если только перегружать полностью.
// Но это очень затратно. Поэтому -- нет, не должен.
// Т.е. в худшем случае -- мы не знаем, обновляется ли currentTrack, или нет
// Ещё обновление трека можно повесить на обновление координат. Это концептуально правильно, но
// тогда при потере сервиса координат пропадёт и обновление трека (потому что функция обновления
// координат перестанет вызываться). А совсем везде независимое обновление трека будет работать, и
// покажет положение даже при отсутствии сервиса координат.

// Установим переключатель в сохранённое состояние
loggingSwitch.checked = Boolean(+getCookie('GaladrielloggingSwitch')); 	// getCookie from galadrielmap.js

var currentTrackShowedFlag = false; 	// флаг, не показывается ли текущий путь. Если об этом спрашивать у Leaflet, то пока загружается трек, можно запустить его загрузку ещё раз пять.
var currentWaitTrackUpdateProcess;	// процесс ослеживания наличия текущего (пишущегося) трека
var currentTrackUpdateProcess;	// процесс обновления текущего трека

if(currTrackSwitch.checked){	// Текущий трек всегда показывается
	if(currentTrackName && currentTrackServerURI){	// есть текущий трек и указано, откуда взять обновления
		currentTrackUpdateProcess = setInterval(currentTrackUpdate,3000);	// раз в 3 секунды
		console.log('Update track startet on startup with',currentTrackName,'track');
	}
	else{
		currentWaitTrackUpdateProcess = setInterval(loggingCheck,10000);	// раз в 10 секунд
		console.log('Logging check started on startup');
	}
}


function currentTrackUpdate(){
/*
Global: map, savedLayers, currentTrackName, currentTrackShowedFlag
DOM objects: loggingSwitch, trackDisplayed
*/
//console.log('[currentTrackUpdate] currentTrackName='+currentTrackName,'currentTrackShowedFlag=',currentTrackShowedFlag);
if(currentTrackShowedFlag !== false) { 	// Текущий трек некогда был загружен или сейчас загружается
	if(map.hasLayer(savedLayers[currentTrackName])) { 	// если он реально есть
		//console.log('[currentTrackUpdate] Текущий трек есть на карте','currentTrackName='+currentTrackName,'currentTrackShowedFlag=',currentTrackShowedFlag);
		updateCurrTrack(); 	//  - обновим,  galadrielmap.js
		currentTrackShowedFlag = true;
	}
	else { 
		if(currentTrackShowedFlag != 'loading') currentTrackShowedFlag = false;
	}
}
else { 	 //console.log("[currentTrackUpdate] текущий трек ещё не был загружен", currentTrackName);
	//console.log('[currentTrackUpdate] document.getElementById(currentTrackName):',document.getElementById(currentTrackName));
	//console.log(tracks.querySelector('li[title="Current Track"]'));
	currentTrackShowedFlag = 'loading'; 	// укажем, что трек сейчас загружается
	selectTrack(document.getElementById(currentTrackName),trackList,trackDisplayed,displayTrack); 	// загрузим трек асинхронно. galadrielmap.js
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
