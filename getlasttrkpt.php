<?php session_start();
/* This is a current track server. Read gpx from gpxlogger directory. 
Work with any logger, not obligatory gpxlogger
Отдаёт последнюю структуру типа trkpt из незакрытого файла gpx.
Файл может писаться чем угодно.
Имя файла без расширения передаётся в первом параметре или в currTrackName
Файл ищется в $trackDir из params.php
Если файл закрыт, или какие-то проблемы с последней точкой - не отдаёт ничего

*/

ob_start(); 	// попробуем перехватить любой вывод скрипта
$path_parts = pathinfo($_SERVER['SCRIPT_FILENAME']); // определяем каталог скрипта
$selfPath = $path_parts['dirname'];
//chdir($path_parts['dirname']); // сменим каталог выполнение скрипта
require_once('fcommon.php');
require('params.php'); 	// пути и параметры

if($trackDir[0]!='/') $trackDir = "$selfPath/$trackDir";	// если путь относительный - будет абсолютный

$currTrackFileName = $_REQUEST['currTrackName'];
//$currTrackFileName = "2020-12-18_141744";
if(! $currTrackFileName) $currTrackFileName = $argv[1];
if(! $currTrackFileName) return;
$currTrackFileName = "$trackDir/$currTrackFileName.gpx";
//echo "currTrackFileName=$currTrackFileName; <br>\n";

// определимЮ записывается ли трек
$treckLogging = false; $lastTrkPtGPX = false;
$tailStrings = 5 * 20; 	// сколько строк заведомо включает последнюю trkpt. Спецификация говорит, что trkpt может иметь 20 строк
clearstatcache(TRUE,"$currTrackFileName");
$lastTrkPt = explode("\n",tailCustom($currTrackFileName,$tailStrings));
$lastTrkPt = array_filter( $lastTrkPt,'strlen'); 	// удалим пустые строки
//echo "lastTrkPt:<pre>"; print_r($lastTrkPt); echo "</pre>";
if($gpxlogger) { 	// params.php трек записывает gpxlogger
	if(gpxloggerRun()) $treckLogging = true;
}
else {
	if( trim(end($lastTrkPt)) != '</gpx>') $treckLogging = true; 	// если это завершённый GPX -- укажем, что трек не пишется
}
//echo "treckLogging=$treckLogging;<br>\n";

if($treckLogging) { 	// трек пишется - читаем трек
	$lastTrkPtStart = NULL; $lastTrkPtEnd = NULL;
	foreach($lastTrkPt as $i => $str) {
		if(substr(trim($str),0,6) == '<trkpt') 	$lastTrkPtStart = $i; 	// номер строки начала точки
		elseif(trim($str) == '</trkpt>') 	$lastTrkPtEnd = $i; 	// номер строки конца точки
	//	elseif(trim($str) == '</trkseg>') 	$lastTrkSegEnd = $i; 	// номер строки конца сегмента
	}
	//echo "lastTrkPtStart=$lastTrkPtStart; lastTrkPtEnd=$lastTrkPtEnd; lastTrkSegEnd=$lastTrkSegEnd; <br>\n";
	if(($lastTrkPtStart!==NULL) and ($lastTrkPtEnd!==NULL) and ($lastTrkPtStart < $lastTrkPtEnd)) { 	// точка есть
		$lastTrkPt = implode("\n",array_slice($lastTrkPt,$lastTrkPtStart,$lastTrkPtEnd-$lastTrkPtStart+1));
		//echo "lastTrkPt:<pre>"; print_r(htmlentities($lastTrkPt)); echo "</pre>"; 
		//echo htmlentities("<trkseg>\n".$_SESSION['lastTrkPt']."\n$lastTrkPt\n</trkseg>\n"); goto END;
		//echo "lastTrkPt:<pre>"; print_r($lastTrkPt); echo "</pre>"; 

		if($_SESSION['lastTrkPt'] <> $lastTrkPt) { 	// вернём, если точка изменилась
			//$lastTrkPtGPX = "<trkseg>\n".$_SESSION['lastTrkPt']."\n$lastTrkPt\n</trkseg>\n"; 	// всегда будем оформлять две последних путевых точек как отдельный сегмент
			$lastTrkPtGPX = gpx2geoJSONpoint(array($_SESSION['lastTrkPt'],$lastTrkPt)); 	// сделаем из двух последних точек GeoJSON LineString
			$_SESSION['lastTrkPt'] = $lastTrkPt;
		}
	}
}
$output = array('logging' => $treckLogging,'pt' => $lastTrkPtGPX);
ob_clean(); 	// очистим, если что попало в буфер
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Дата в прошлом
header('Content-Type: application/json;charset=utf-8;');
echo json_encode($output);
$content_lenght = ob_get_length();
header("Content-Length: $content_lenght");
ob_end_flush(); 	// отправляем и прекращаем буферизацию

END:
return;

function gpx2geoJSONpoint($gpxPts) {
/* Получает массив строк trkpt, rtept или wpt, разделённую \n , вовращает GeoJSON LineString */
$geoJSON = array(
'type' => 'FeatureCollection',
'features' => array(
	array(
	'type' => 'Feature',
	'geometry' => array(
		'type' => 'LineString',
		'coordinates' => array()
	),
	'id' => 'gps',
	'properties' => null
	)
)
);
foreach($gpxPts as $gpxPt) {
	$gpxPt = explode("\n",$gpxPt);
	if(!$gpxPt) continue;
	$type = strtolower(substr(trim($gpxPt[0]),1,3));
	if(($type<>'trk') AND ($type<>'wpt') AND ($type<>'rte')) continue; 	// это не точка
	//echo "type=$type;<br>\n";
	foreach($gpxPt as $str) {
		//echo "str=".htmlentities($str).";<br>";
		$coord = strpos($str,'lat="'); $strlen = strlen($str);
		if($coord !== FALSE) $lat = substr($str,$coord+5,strpos($str,'"',$coord+6)-$coord-5);
		//echo "coord=$coord; lat=$lat;<br>\n";
		$coord = strpos($str,'lon="');
		if($coord !== FALSE) $lon = substr($str,$coord+5,strpos($str,'"',$coord+6)-$coord-5);
		//echo "coord=$coord; lon=$lon;<br>\n";
		if($lat AND $lon) break;
	}
	if($lat AND $lon) {
		$geoJSON['features'][0]['geometry']['coordinates'][] = array($lon,$lat);
	}
	else continue;
}
//echo "geoJSON:<pre>"; print_r($geoJSON); echo "</pre>"; 

return $geoJSON;
} // end function gpx2geoJSONpoint
?>
