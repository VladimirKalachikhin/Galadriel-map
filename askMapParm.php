<?php
require('params.php'); 	// пути и параметры

// Получаем список имён карт
if($mapSourcesDir[0]!='/') $mapSourcesDir = "$tileCachePath/$mapSourcesDir";	// если путь абсолютный (и в unix, конечно)

$mapName = $_REQUEST['mapname'];
//echo "mapSourcesDir=$mapSourcesDir; mapName=$mapName; <br>\n";
include("$mapSourcesDir/$mapName.php");
$mapInfo = array(
	'ext'=>$ext,
	'epsg'=>$EPSG, 
	'minZoom'=>$minZoom,
	'maxZoom'=>$maxZoom
);
$mapInfo = json_encode($mapInfo);
header('Content-Type: application/json;charset=utf-8;');
echo "$mapInfo \n";
?>
