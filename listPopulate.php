<?php
/* Получает dirname=route или dirname=track 
и отдаёт список файлов {gpx,kml,csv}
в каталогах $trackDir или $routeDir из params.php
*/
ini_set('error_reporting', E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);
//ini_set('error_reporting', E_ALL & ~E_STRICT & ~E_DEPRECATED);
//ob_start(); 	// попробуем перехватить любой вывод скрипта
require('fcommon.php');
require('params.php'); 	// пути и параметры

$dirName = end(explode('/',$_REQUEST['dirname'])); 	// basename не работает с неанглийскими буквами!!!!
if($dirName == end(explode('/',$trackDir))) $dirName = $trackDir;
elseif($dirName == end(explode('/',$routeDir))) $dirName = $routeDir;
else {
	//ob_end_clean(); 			// очистим, если что попало в буфер
	http_response_code(400);	// Bad request
	return;
};

$dirInfo = glob("$dirName/*{gpx,kml,csv}", GLOB_BRACE);
//echo ":<pre>"; print_r($dirInfo); echo "</pre>";
array_walk($dirInfo,function (&$name,$ind) {
		//$name=basename($name); 	// 
		$name=end(explode('/',$name)); 	// basename не работает с неанглийскими буквами!!!!
	}); 	// 
sort($dirInfo);
//echo ":<pre>"; print_r($dirInfo); echo "</pre>";
$currentTrackName = getLastTrackName();	// fcommon.php 
//echo "currentTrackName=$currentTrackName;<br>\n";
if($currentTrackName) {	// там может не быть ни одного трека
	if(trim(tailCustom("$trackDir/$currentTrackName")) == '</gpx>'){ 	// echo "трек завершён<br>\n"; // fcommon.php
		$currentTrackName = '';
	}
	else $currentTrackName = end(explode('/',$currentTrackName));
}

$dirInfo = json_encode(array('currentTrackName'=>$currentTrackName,'filelist'=>$dirInfo));
//ob_end_clean(); 			// очистим, если что попало в буфер
header('Content-Type: application/json;charset=utf-8;');
echo "$dirInfo \n";
?>
