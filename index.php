<?php
require_once('fcommon.php');
require_once('params.php'); 	// пути и параметры

$versionTXT = '1.7.2';
/* 
1.7.2 	auto-update edited routes
1.7.0 	geocoding feature
1.6		support of GaladrielCache cobering feature
1.5.0	with track logging control. Fixed crazy Firefox XMLHttpRequest mime-type defaults.
1.4.3	upd to stacked gpsd's
*/
// Интернационализация
require_once('internationalisation.php');

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
// Получаем список выполняющихся заданий на скачивание
	if($jobsDir[0]!='/') $jobsDir = "$tileCachePath/$jobsDir";	//  сделаем путь абсолютным, потому что jobsDir - из конфига GaladrielCache
	if($jobsInWorkDir[0]!='/') $jobsInWorkDir = "$tileCachePath/$jobsInWorkDir";	//  сделаем путь абсолютным
	$jobsInfo = preg_grep('~.[0-9]$~', scandir($jobsDir)); 	// возьмём только файлы с цифровым расшрением
	foreach($jobsInfo as $i => $jobName) {
		$jobSize = filesize("$jobsDir/$jobName");
		$jobComleteSize =  @filesize("$jobsInWorkDir/$jobName"); 	// файла в этот момент может уже и не оказаться
		//echo "jobSize=$jobSize; jobComleteSize=$jobComleteSize; <br>\n";
		$jobsInfo[$i] = array($jobName, round((($jobSize-$jobComleteSize)/$jobSize)*100)); 	// выполнено
	}
	//echo "jobsInfo:<pre>"; print_r($jobsInfo); echo "</pre>";
	$schedInfo = glob("$jobsDir/*.slock"); 	// имеющиеся PIDs запущенных планировщиков. Должен быть только один, но мало ли...
	//echo "schedInfo:<pre>"; print_r($schedInfo); echo "</pre>";
	$schedPID = FALSE;
	foreach($schedInfo as $schedPID) {
		$schedPID=explode('.slock',end(explode('/',$schedPID)))[0]; 	// basename не работает с неанглийскими буквами!!!!
		if(file_exists( "/proc/$schedPID")) break; 	// процесс с таким PID работает
		else {
			unlink("$jobsDir/$schedPID.slock"); 	// файл-флаг остался от чего-то, но процесс с таким PID не работает - удалим
			$schedPID = FALSE;
		}
	}
	//echo "schedPID=$schedPID; <br>\n";
}
else {$mapsInfo = array(); $jobsInfo = array();}
 
// Получаем список имён треков
$trackInfo = array();
if($trackDir) {
	$trackInfo = glob("$trackDir/*.gpx"); 	// gpxDir - из файла params.php
	array_walk($trackInfo,function (&$name,$ind) {
			//$name=basename($name,'.gpx'); 	// 
			$name=explode('.gpx',end(explode('/',$name)))[0]; 	// basename не работает с неанглийскими буквами!!!!
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

<?php if($gpsanddataServerURI) {?>
    <script src="Leaflet.RotatedMarker/leaflet.rotatedMarker.js"></script>
<?php }?>
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
	
<!--    <script src="JSON-js/cycle.js"></script>--> <!-- костыль для JSON.stringify , которая используется для отладки -->
<!--    <script src="fetch/fetch.js"></script>--> <!-- полифил для старых браузеров -->
<!--    <script src="promise-polyfill/promise.js"></script>--> <!-- полифил для старых браузеров -->

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
			<li id="dashboardTab" <?php if(!$gpsanddataServerURI) echo 'class="disabled"';?>><a href="#dashboard" role="tab"><img src="img/speed1.svg" alt="dashboard" width="70%"></a></li>
			<li id="tracksTab" <?php if(!$trackDir) echo 'class="disabled"';?>><a href="#tracks" role="tab"><img src="img/track.svg" alt="tracks" width="70%" OnClick='loggingCheck();'></a></li>
			<li id="measureTab" ><a href="#measure" role="tab"><img src="img/route.svg" alt="Create route" width="70%"></a></li>
			<li id="routesTab" <?php if(!$routeDir) echo 'class="disabled"';?>><a href="#routes" role="tab"><img src="img/poi.svg" alt="Routes and POI" width="70%"></a></li>
		</ul>
		<ul role="tablist" id="settingsList">
			<li id="download-tab" <?php if(!$tileCachePath) echo 'class="disabled"';?>><a href="#download" role="tab"><img src="img/download1.svg" alt="download map" width="70%"></a></li>
			<li><a href="#settings" role="tab"><img src="img/settings1.svg" alt="settings" width="70%"></a></li>
		</ul>
	</div>
	<!-- Tab panes -->
	<div class="leaflet-sidebar-content" id='tabPanes'>
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
		<div class="leaflet-sidebar-pane" id="dashboard">
			<h1 class="leaflet-sidebar-header leaflet-sidebar-close"> <?php echo $dashboardHeaderTXT;?> <span class="leaflet-sidebar-close-icn"><img src="img/Triangle-left.svg" alt="close" width="16px"></span></h1>
			<div class="big_symbol" onClick="if(! noFollowToCursor) map.setView(cursor.getLatLng());"> <!-- передвинуть карту на место курсора -->
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
					<div style="font-size:50%;line-height:0.5;" onClick="doCopyToClipboard(lat+' '+lng);" >
						<br><span style="font-size:50%;"><?php echo $dashboardPosTXT;?></span><br>
						<span style="font-size:30%; "><?php echo $dashboardPosAltTXT;?></span>
					</div>
					<div style="font-size:50%;">
						<span id='locationDisplay'></span>
					</div>
				</div>
			</div>
			<div style="text-align:center; position: absolute; bottom: 0;">
				<?php echo $dashboardSpeedZoomTXT;?> <span id='velocityVectorLengthInMnDisplay'></span> <?php echo $dashboardSpeedZoomMesTXT;?>.
			</div>
		</div>
		<!-- Треки -->
		<div class="leaflet-sidebar-pane" id="tracks">
			<h1 class="leaflet-sidebar-header leaflet-sidebar-close"> <?php echo $tracksHeaderTXT;?> <span class="leaflet-sidebar-close-icn"><img src="img/Triangle-left.svg" alt="close" width="16px"></span></h1>
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
			<ul id="trackDisplayed" class='commonList'>
			</ul>
			<ul id="trackList" class='commonList'>
<?php
foreach($trackInfo as $trackName) { 	// ниже создаётся анонимная функция, в которой вызывается функция, которой передаётся предопределённый в браузере объект event
?>
					<li onClick='{selectTrack(event.currentTarget,trackList,trackDisplayed,displayTrack)}' <?php echo " id='$trackName' "; if($trackName == $currentTrackName) echo "title='Current Track' class='currentTrackName' title='Current track'"; echo ">$trackName";?></li>
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
						if(L.Browser.mobile && L.Browser.touch) var weight = 15; 	// мобильный браузер
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
				<div style="width:10rem;margin:0;padding:0;">
					<button onClick='goToPositionField.value += "°";goToPositionField.focus();' style="width:2.1rem;height:1.5rem;margin:0 0.7rem 0 0;"><span style="font-weight: bold; font-size:150%;">°</span></button>
					<button onClick='goToPositionField.value += "′";goToPositionField.focus();' style="width:2.1rem;height:1.5rem;margin:0 0.7rem 0 0;"><span style="font-weight: bold; font-size:150%;">′</span></button>
					<button onClick='goToPositionField.value += "″";goToPositionField.focus();' style="width:2.1rem;height:1.5rem;margin:0 0rem 0 0;"><span style="font-weight: bold; font-size:150%;">″</span></button><br>
				</div>
				<span style=""><?php echo $dashboardPosAltTXT;?></span><br>
				<input id = 'goToPositionField' type="text" title="<?php echo $goToPositionTXT;?>" size='12' style='width:9rem;font-size:150%;'>			
				<button id = 'goToPositionButton' onClick='flyByString(this.value);' style="width:4rem;padding:0.2rem;float:right;"><img src="img/ok.svg" alt="<?php echo $okTXT;?>" width="16px"></button><br>
			</div>
			<div  style='width:98%;height:10rem;overflow:auto;margin:0.3rem 0;'>
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
		<!-- Загрузчик -->
		<div class="leaflet-sidebar-pane" id="download">
			<h1 class="leaflet-sidebar-header leaflet-sidebar-close"><?php echo $downloadHeaderTXT;?> <span class="leaflet-sidebar-close-icn"><img src="img/Triangle-left.svg" alt="close" width="16px"></span></h1>
			<div style="margin: 1rem 0 3rem 0;padding:0 0.5rem 0 0;">
				<div style="margin:0 0 0.5rem 0">
					<div class="onoffswitch" style="float:right;margin: 0.3rem auto;"> <!--  Переключатель https://proto.io/freebies/onoff/  -->
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
			<h2 style=''><?php echo $downloadZoomTXT;?>: <span id='current_zoom'></span></h2>
			<div class="" style="font-size:120%;margin:0;">
				<form id="dwnldJob" onSubmit="createDwnldJob(); downJob=false; return false;" onreset="current_zoom.innerHTML=map.getZoom(); downJob=false;//alert('reset');">
					<div style='display:grid;grid-template-columns:auto auto;'>
						<div>X</div><div>Y</div>
						<div style='height:28vh;overflow-y:auto;overflow-x:hidden;grid-column:1/3'>
							<div style='display:grid; grid-template-columns: auto auto; grid-column-gap: 3px;'>
								<div style='margin-bottom:10px;'>
									<input type="text" pattern="[0-9]*" title="<?php echo $integerTXT;?>" class="tileX" size='12' style='width:6rem;font-size:150%;'>
								</div>
								<div style='margin-bottom:10px;'>
									<input type="text" pattern="[0-9]*" title="<?php echo $integerTXT;?>" class="tileY" size='12' style='width:6rem;font-size:150%;' 
										onChange="
											//console.log(this.parentNode);
											downJob = map.getZoom(); 	// выставим флаг, что идёт подготовка задания на скачивание
											let newXinput = this.parentNode.previousElementSibling.cloneNode(true); 	// клонируем div с x
											newXinput.getElementsByTagName('input')[0].value = ''; 	// очистим поле ввода
											let newYinput = this.parentNode.cloneNode(true); 	// клонируем div с y
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
				<h3>
					<span id="loaderIndicator" style="font-size:100%;
<?php if($jobsInfo) { ?>
<?php 		if($schedPID) { ?>
					  color: green;" title="<?php echo $downloadLoaderIndicatorOnTXT;?>">&#9786;
<?php 		} else { ?>
					  color: red;" title="<?php echo $downloadLoaderIndicatorOffTXT;?>" onClick="restartLoader();">&#9785;
<?php 		} ?>
<?php } else {?>
					">
<?php 		} ?>
					</span><?php echo $downloadJobListTXT;?>:
				</h3>
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
		</div>
	</div>
</div>
<div id="mapid" ></div>
<?php
?>
<script> "use strict";

// Карта
var savedLayers = []; 	// массив для хранения объектов, когда они не на карте
var gpsanddataServerURI = '<?php echo $gpsanddataServerURI;?>'; 	// адрес для подключения к сервису координат и приборов
var aisServerURI = '<?php echo $aisServerURI;?>'; 	// адрес для подключения к сервису AIS
var tileCacheURI = '<?php echo $tileCacheURI;?>'; 	// адрес источника карт, используется в displayMap
var additionalTileCachePath = ''; 	// дополнительный кусок пути к тайлам между именем карты и /z/x/y.png Используется в версионном кеше, например, в погоде. Без / в конце, но с / в начале, либо пусто
var startCenter = JSON.parse(getCookie('GaladrielMapPosition'));
if(! startCenter) startCenter = L.latLng([55.754,37.62]); 	// начальная точка
var startZoom = JSON.parse(getCookie('GaladrielMapZoom'));
if(! startZoom) startZoom = 12; 	// начальный масштаб
var heading = 0; 	// начальное направление
var PosFreshBefore = <?php echo $PosFreshBefore * 1000;?>; 	// время в милисекундах, через которое положение считается протухшим
var followToCursor = true; 	// карта следует за курсором Обеспечивает только паузу следования при перемещениях и масштабировании карты руками
var noFollowToCursor = false; 	// карта никогда не следует за курсором Глобальное отключение следования. Само не восстанавливается.
var CurrnoFollowToCursor = 1; 	// глобальная переменная для сохранения состояния
var followPause = 10 * 1000; 	// пауза следования карты за курсором, когда карту подвинули руками, микросекунд
var savePositionEvery = 30 * 1000; 	// будем сохранять положение каждые микросекунд. В настоящее время только кладётся кука
var followPaused; 	// объект таймера, который восстанавливает следование курсору
var userMoveMap = true; 	// флаг для отделения собственных движений карты от пользовательских. Считаем все пользовательскими, и только где надо - выставляем иначе
var downJob = false; 	// флаг - не создаётся ли задание на скачивание
var velocityVectorLengthInMn = 10; 	// длинной в сколько минут пути рисуется линия скорости
var currentTrackServerURI = '<?php echo $currentTrackServerURI;?>'; 	// адрес для подключения к сервису, отдающему сегменты текущего трека
var trackDirURI = '<?php echo $trackDir;?>'; 	// адрес каталога с треками
var routeDirURI = '<?php echo $routeDir;?>'; 	// адрес каталога с маршрутами
var currentTrackName = '<?php echo $currentTrackName;?>'; 	// имя текущего (пишущегося сейчас) трека
var updateRouteServerURI = '<?php echo $updateRouteServerURI;?>'; 	// url службы динамического обновления маршрутов
if(getCookie('GaladrielcurrTrackSwitch') == undefined) currTrackSwitch.checked = true; 	// показывать текущий трек вместе с курсором
else currTrackSwitch.checked = Boolean(+getCookie('GaladrielcurrTrackSwitch'));
if(getCookie('GaladrielSelectedRoutesSwitch') == undefined) SelectedRoutesSwitch.checked = false; 	// показывать выбранные маршруты
else SelectedRoutesSwitch.checked = Boolean(+getCookie('GaladrielSelectedRoutesSwitch'));
var currentRoute; 	// объект Editable, по которому щёлкнули. Типа, текущий.
var globalCurrentColor = 0xFFFFFF; 	// цвет линий и  значков кластеров после первого набора
var currentTrackShowedFlag = false; 	// флаг, не показывается ли текущий путь. Если об этом спрашивать у Leaflet, то пока загружается трек, можно запустить его загрузку ещё раз пять.
var lat; 	 	// широта
var lng; 	 	// долгота, округлённые до 4-х знаков
var copyToClipboardMessageOkTXT = '<?php echo $copyToClipboardMessageOkTXT;?>';
var copyToClipboardMessageBadTXT = '<?php echo $copyToClipboardMessageBadTXT;?>';
var goToPositionManualFlag = false; 	// флаг, что поле goToPositionField стали редактировать руками, и его не надо обновлять
var vehicles = []; 	// list of visible by AIS data vehicle objects 
var AISstatusTXT = {
<?php foreach($AISstatusTXT as $k => $v) echo "$k: '$v',\n";?>
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
		tileGrid.addTo(map); 	// добавить на карту тайловую сетку
		if(CurrnoFollowToCursor === 1)CurrnoFollowToCursor = noFollowToCursor;  // запомним состояние глобального признака следования за курсором, если ещё не запоминали
		noFollowToCursor = true; 	// отключим следование за курсором
		break;
	case 'measure': 	// рисование маршрута
		centerMarkOn(); 	// включить крестик в середине
		if(CurrnoFollowToCursor === 1)CurrnoFollowToCursor = noFollowToCursor;  // запомним состояние глобального признака следования за курсором, если ещё не запоминали
		noFollowToCursor = true; 	// отключим следование за курсором
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
	//alert(zoom);
	if(!downJob) current_zoom.innerHTML = zoom;
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
var layers = JSON.parse(getCookie('GaladrielMaps'));
// Занесём слои на карту
if(layers) layers.reverse().forEach(function(layerName){ 	// потому что они там были для красоты последним слоем вверъ
		for (var i = 0; i < mapList.children.length; i++) { 	// для каждого потомка списка mapList
			if (mapList.children[i].innerHTML==layerName) { 	// 
				selectMap(mapList.children[i]);
				break;
			}
		}
	});
<?php }
else {?>
displayMap('default');
<?php }?>

// Восстановим показываемые треки
if(SelectedRoutesSwitch.checked) {
	let showRoutes = JSON.parse(getCookie('GaladrielRoutes'));
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
tileGrid.createTile = function (coords) {
	var tile = document.createElement('div');
	tile.style.outline = '1px solid rgba(255,69,0,1)';
	tile.style.fontWeight = 'bold';
	tile.style.fontSize = '23pt';
	tile.style.color = 'rgba(255,69,0,0.75)';
	tile.innerHTML = '<div style="padding:1rem;">'+coords.z+'<br>'+coords.x+' / '+coords.y+'</div>';
	return tile;
}
if( !downJob) current_zoom.innerHTML = map.getZoom(); 	// текущий масштаб отобразим на панели скачивания
cover_zoom.innerHTML = map.getZoom()+8;

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
	})
});

<?php if($gpsanddataServerURI) { // если нет источника текущих данных - не нужны и обработчики ?>
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
}).addTo(map);
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
// 	Запуск периодических функций
//setInterval(function(){realtime(gpsanddataServerURI,function(data){console.log(data);});},1000);
setInterval(function(){realtime(gpsanddataServerURI,realtimeTPVupdate);},1000); 	// данные позиционирования

// Realtime периодическое обновление
function realtimeTPVupdate(gpsdData) {
	//console.log(gpsdData);
	// Положение неизвестно
	if(gpsdData.error || (gpsdData.lon == null)||(gpsdData.lat == null)) { 	// 
		cursor.setIcon(NoCursor); 	// отключим курсоры
		velocityVector.setIcon(NoCursor);
		//velocityDial.innerHTML = ''; 	// может быть, следует знать, какой была скорость и координаты до пропадания приборов?
		GNSScircle.setRadius(0);
		//alert('Чёта с ГПС'); 
		return;
	}
	// Свежее ли положение известно
	cursor.setLatLng(L.latLng(gpsdData.lat,gpsdData.lon));
	var positionTime = new Date(gpsdData.time);
	var now = new Date();
	//alert("Время ГПС "+positionTime+'\n'+"Сейчас    "+now);
	if((now-positionTime) > PosFreshBefore) cursor.setIcon(NoGpsCursor); 	// свежее положение было определено раньше, чем PosFreshBefore милисекунд назад
	else	 		cursor.setIcon(GpsCursor);
	// Направление с попыткой его запомнить при прекращении движения
	velocityVector.setLatLng( cursor.getLatLng() );// положение указателя скорости
	if(gpsdData.heading === null) {
		headingDisplay.innerHTML = '&nbsp;';
		cursor.setRotationAngle(0); // повернём маркер
		velocityVector.setRotationAngle(0); // повернём указатель скорости
	}
	else {
		heading = gpsdData.heading; // если положение изменилось - возьмём новое направление, иначе - будет старое.
		cursor.setRotationAngle(heading); // повернём маркер
		velocityVector.setRotationAngle(heading); // повернём указатель скорости
		headingDisplay.innerHTML = Math.round(heading); // покажем направление на приборной панели
	}
	// Карту в положение
	//console.log("followToCursor", followToCursor);
	if(followToCursor && (! noFollowToCursor)) { 	// если сказано следовать курсору, и это разрешено глобально
		userMoveMap = false;
		//map.fitBounds(realtime.getBounds(), {maxZoom: map.getZoom()});
		map.setView(cursor.getLatLng()); // подвинем карту на позицию маркера
		userMoveMap = true;
	}
<?php 	if($currentTrackServerURI) { ?>
	// Текущий трек
	if(currentTrackName && currTrackSwitch.checked) { 	// имеется имя текущего трека, и в интерфейсе указано показывать текущий трек
		if(currentTrackShowedFlag !== false) { 	// Текущий трек некогда был загружен или сейчас загружается
			if(map.hasLayer(savedLayers[currentTrackName])) { 	// если он реально есть
				updateCurrTrack(); 	//  - обновим  galadrielmap.js
				currentTrackShowedFlag = true;
			}
			else { 
				if(currentTrackShowedFlag != 'loading') currentTrackShowedFlag = false;
			}
		}
		else { 	// текущий трек ещё не был загружен
			//console.log(document.getElementById(currentTrackName));
			//console.log(tracks.querySelector('li[title="Current Track"]'));
			selectTrack(document.getElementById(currentTrackName),trackList,trackDisplayed,displayTrack); 	// загрузим трек асинхронно. galadrielmap.js
			currentTrackShowedFlag = 'loading'; 	// укажем, что трек сейчас загружается
		}
	}
<?php 	} ?>
	// Показ скорости и прочего
	if(gpsdData.velocity===null) {
		velocityDial.innerHTML = '&nbsp;';
		velocityVector.setIcon(NoCursor);
	}
	else {
		var velocity = Math.round((gpsdData.velocity*60*60/1000)*10)/10; 	// скорость от gpsd - в метрах в секунду
		//alert("Скорость: "+velocity+"км/ч");
		velocityDial.innerHTML = velocity;
		// Установим длину указателя скорости за  минуты
		var metresPerPixel = (40075016.686 * Math.abs(Math.cos(cursor.getLatLng().lat*(Math.PI/180))))/Math.pow(2, map.getZoom()+8); 	// in WGS84
		var velocityCursorLength = gpsdData.velocity*60*velocityVectorLengthInMn; 	// метров  за  минуты
		velocityCursorLength = Math.round(velocityCursorLength/metresPerPixel);
		//console.log('map.getZoom='+map.getZoom()+'\nmetresPerPixel='+metresPerPixel+'\ngpsdData.velocity='+gpsdData.velocity+'\nvelocityCursorLength='+velocityCursorLength);
		//alert('metresPerPixel='+metresPerPixel+'\nvelocityCursorLength='+velocityCursorLength);
		velocityCursor.options.iconSize=[5,velocityCursorLength];
		velocityCursor.options.iconAnchor=[3,velocityCursorLength];
		velocityVector.setIcon(velocityCursor);
	}
	// координаты курсора с точностью знаков
	lat = Math.round(cursor.getLatLng().lat*10000)/10000; 	 	// широта
	lng = Math.round(cursor.getLatLng().lng*10000)/10000; 	 	// долгота
	//alert(cursor.getLatLng()+'\n'+lat+' '+lng);
	locationDisplay.innerHTML = '<?php echo $latTXT?> '+lat+'<br><?php echo $longTXT?> '+lng;	
	followSwitch.checked = !noFollowToCursor; 	// выставим переключатель на панели Настроек в текущее положение
	// Окружность точност ГПС
	var errGNSS = (+gpsdData.errX+gpsdData.errY)/2;
	if(!errGNSS) errGNSS = 10; // метров
	GNSScircle.setLatLng(cursor.getLatLng());
	GNSScircle.setRadius(errGNSS);
};

<?php 
} 
if($aisServerURI) { // если нет источника текущих данных - не нужны и обработчики 
?>


// Данные AIS
// 	Запуск периодических функций
//setInterval(function(){realtime(aisServerURI,function(data){console.log(data);});},1000);
setInterval(function(){realtime(aisServerURI,realtimeAISupdate);},5000);
//realtime(aisServerURI,realtimeAISupdate);

function realtimeAISupdate(aisData) {
//console.log(aisData); 	// массив с данными целей
let vehiclesVisible = [];
for(const vehicle in aisData){
	//console.log(aisData[vehicle]);
	//console.log(aisData[vehicle].lat);	console.log(aisData[vehicle].lon);
	//console.log(typeof(vehicles[vehicle]));
	if(!vehicles[vehicle]) { 	// global var, массив layers с целями
		//console.log(vehicle);
		//console.log(aisData[vehicle]);
		if(aisData[vehicle].netAIS) { 	// цель получена от netAIS
			var defaultSymbol = [1*0.5,0, 0.25*0.5,0.25*0.5, 0,1*0.5, -0.25*0.5,0.5*0.5, -1*0.5,0.75*0.5, -1*0.5,-0.75*0.5, -0.25*0.5,-0.5*0.5, 0,-1*0.5, 0.25*0.5,-0.25*0.5]; 	// треугольник, расстояния от центра, через которые нарисуют polyline
			var noHeadingSymbol = [1*0.35,0, 0.75*0.35,0.5*0.35, 1*0.35,1*0.35, 0.5*0.35,0.75*0.35, 0,1*0.35, -0.5*0.35,0.75*0.35, -1*0.35,1*0.35, -0.75*0.35,0.5*0.35, -1*0.35,0, -0.75*0.35,-0.5*0.35, -1*0.35,-1*0.35, -0.5*0.35,-0.75*0.35, 0,-1*0.35, 0.5*0.35,-0.75*0.35, 1*0.35,-1*0.35, 0.75*0.35,-0.5*0.35]; 	// ромбик: правый, верхний, левый, нижний ПРотив часовой от правого?
			//console.log(aisData[vehicle]);
		}
		else { 	// цель получена от локального приёмника AIS
			var defaultSymbol = [0.8,0, -0.3,0.35, -0.3,-0.35]; 	// треугольник вправо, расстояния от центра, через которые нарисуют polyline
			var noHeadingSymbol = [0.35,0, 0,0.35, -0.35,0, 0,-0.35]; 	// ромбик
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
	if(vehiclesVisible.includes(vehicle)) continue;
	vehicles[vehicle].remove();
	vehicles[vehicle] = null;
	delete vehicles[vehicle];
}
} // end function realtimeAISupdate

<?php
}

if($updateRouteServerURI) { // если нет сервиса обновления маршрута - не нужны и обработчики 
?>

// Динамическое обновление показываемых маршрутов
// 	Запуск периодических функций
var updateRoutesInterval = setInterval(function(){realtime(updateRouteServerURI,routeUpdate);},2000);

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

<?php
}
?>
var savePositionProcess = setInterval(doSavePosition,savePositionEvery); 	// велим сохранять позицию каждые savePositionEvery
document.getElementById("followSwitch").checked = true; 	// выставим переключатель на панели Настроек в правильное положение
</script>
</body>
</html>
