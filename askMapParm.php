<?php
ob_start(); 	// попробуем перехватить любой вывод скрипта
require('params.php'); 	// пути и параметры

// Получаем список имён карт
if($mapSourcesDir[0]!='/') $fullMapSourcesDir = "$tileCachePath/$mapSourcesDir";	// если путь абсолютный (и в unix, конечно)

$mapName = $_REQUEST['mapname'];
//error_log("askMapParm.php: fullMapSourcesDir=$fullMapSourcesDir; mapName=$mapName;");
if(strpos($mapName,'_COVER')) { 	// нужно показать покрытие, а не саму карту
	include("$fullMapSourcesDir/common_COVER"); 	// файл, описывающий источник тайлов покрытия, используемые ниже переменные - оттуда.
}
else include("$fullMapSourcesDir/$mapName.php"); 	//  файл, описывающий источник, используемые ниже переменные - оттуда.
$mapInfo = array(
	'ext'=>$ext,
	'ContentType'=>$ContentType,
	'epsg'=>$EPSG, 
	'minZoom'=>$minZoom,
	'maxZoom'=>$maxZoom,
	'data'=>$data
);
if(($ext=='pbf')and(file_exists("$fullMapSourcesDir/$mapName.json"))) $mapInfo['mapboxStyle'] = "$tileCacheServerPath/$mapSourcesDir/$mapName.json"; 	// путь в смысле web
$mapInfo = json_encode($mapInfo);
ob_end_clean(); 			// очистим, если что попало в буфер
header('Content-Type: application/json;charset=utf-8;');
echo "$mapInfo \n";
?>
