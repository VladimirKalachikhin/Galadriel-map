<?php
ob_start(); 	// попробуем перехватить любой вывод скрипта
session_start();
/* This is a current track server. Read gpx from gpxlogger directory. 
Work with any logger, not obligatory gpxlogger

Отдаёт в виде GeoJSON LineString последние две trkpt из незавершённого файла gpx. Причём последнюю точку -- 
не только в смысле трека, но по расположению в файле.
Или последнюю отосланную и последнюю в треке.
Или линию от последней отосланной через сколько-то с тех пор.
Файл может писаться чем угодно. Правда, факт, что файл пишется gpxlogger определяется по факту того,
что он запущен. При этом gpxlogger может писать один файл, а сюда быть передано имя другого. В результате
будет возвращено, что файл пишется, даже если он завершён. Правда, если он завешён, новых точек возвращено не будет.
И оно и правильно, ибо может оно не gpxlogger'ом пишется.
Имя файла без расширения передаётся в первом параметре или в currTrackName
Файл ищется в $trackDir из params.php
Если файл завершён, или какие-то проблемы с последней точкой - отдаётся состояние записи и пустая LineString.

*/

require_once('fcommon.php');
require('params.php'); 	// пути и параметры

if($trackDir[0]!='/') $trackDir = __DIR__."/$trackDir";	// если путь относительный - будет абсолютный

$currTrackFileName = $_REQUEST['currTrackName'];
//$currTrackFileName = "2022-05-04_112407";
if(! $currTrackFileName) $currTrackFileName = $argv[1];
if(! $currTrackFileName) return;
$currTrackFileName = "$trackDir/$currTrackFileName.gpx";
//echo "currTrackFileName=$currTrackFileName; <br>\n";

// определим, записывается ли трек
$trackLogging = false; $lastTrkPtGPX = array();
// сколько строк включает последние с момента передачи trkpt. Спецификация говорит, что trkpt может иметь 20 строк
// если это независимо вызывается раз в 2 секунды, а приёмник ГПС отдаёт координаты 10 раз в секунду, и все они пишутся...
$tailStrings = 2 * 10 * 20;	// это примерно 10КБ. Норм?
clearstatcache(TRUE,"$currTrackFileName");
$lastTrkPts = explode("\n",tailCustom($currTrackFileName,$tailStrings));
$lastTrkPts = array_filter( $lastTrkPts,function ($str){return strlen(trim($str));}); 	// удалим пустые строки
//echo "lastTrkPts:<pre>"; print_r($lastTrkPts); echo "</pre>";
if($lastTrkPts){	// файл, например, грохнули, но клиент-то об этом не знает...
	if($gpxlogger) { 	// params.php трек записывает gpxlogger
		if(gpxloggerRun()) $trackLogging = true;
	}
	else {
		if( trim(end($lastTrkPts)) != '</gpx>') $trackLogging = true; 	// если это завершённый GPX -- укажем, что трек не пишется
	}
}
//echo "trackLogging=$trackLogging;<br>\n";

if($trackLogging) { 	// трек пишется - просмотрим трек
	// Для определения, какая последняя точка была отдана, найдём в ней строку с временем.
	$sendedTRPTtimeStr = '';
	foreach(explode("\n",$_SESSION['lastTrkPt']) as $str){
		if(substr($str=trim($str),0,6)=='<time>'){
			$sendedTRPTtimeStr = $str;
			break;
		}
	}
	$TRPTstart = count($lastTrkPts);
	foreach($lastTrkPts as $n => $str){
		if(substr(trim($str),0,6) == '<trkpt') 	$TRPTstart = $n; 	// номер строки начала точки
		elseif(trim($str)==$sendedTRPTtimeStr) break;	// Строка time последней отданной точки
	}
	//$debugMessage = "TRPTstart=$TRPTstart; sendedTRPTtimeStr=$sendedTRPTtimeStr;"; 
	//echo "TRPTstart=$TRPTstart; sendedTRPTtimeStr=$sendedTRPTtimeStr;<br>\n"; 
	// в считанном хвосте файла обнаружена последняя отправленная, или просто последняя точка, или ничего
	$lastTrkPts = array_slice($lastTrkPts,$TRPTstart);	// теперь массив начинается с первой строки последней отправленной точки или первой строки последней точки или пустой.
	//echo "lastTrkPts:$n<pre>"; print_r($lastTrkPts); echo "</pre>"; 

	$TRPTend = -1;
	foreach($lastTrkPts as $n => $str){
		if(substr(ltrim($str),0,8)=='</trkpt>') $TRPTend=$n;	// последняя строка последней полной точки
	}
	$lastTrkPts = array_slice($lastTrkPts,0,$TRPTend+1);	// теперь массив заканчивается последней строкой какой-то точки
	//echo "lastTrkPts:$n<pre>"; print_r($lastTrkPts); echo "</pre>"; 

	// Собираем точки в строку, а строки -- в массив.
	$TRPTfind = false; $TRPTstr = '';
	foreach($lastTrkPts as $str){
		if(substr(trim($str),0,6) == '<trkpt'){
			$TRPTstr = "$str\n";
			$TRPTfind = true;
		}
		elseif(substr(ltrim($str),0,8)=='</trkpt>'){
			$TRPTstr .= "$str\n";
			$lastTrkPtGPX[] = $TRPTstr;
			$TRPTfind = false;
		}
		elseif($TRPTfind) $TRPTstr .= "$str\n";
	}
	unset($lastTrkPts);
	//echo "lastTrkPtGPX:$n<pre>"; print_r($lastTrkPtGPX); echo "</pre>"; 

	// Теперь в $lastTrkPtGPX одна строка с последней ранее переданной точкой, или одна строка с 
	// последней точкой в файле, или более строк, или пусто
	if(count($lastTrkPtGPX)==1){
		if($lastTrkPtGPX[0]==$_SESSION['lastTrkPt']) $lastTrkPtGPX = [];	// не было новых точек
		elseif($_SESSION['lastTrkPt']) $lastTrkPtGPX = array($_SESSION['lastTrkPt'],$lastTrkPtGPX[0]);	// от последней сохранённой к последней в файле
		else {
			$_SESSION['lastTrkPt'] = $lastTrkPtGPX[0];
			$lastTrkPtGPX = [];
		}
	}

	if($lastTrkPtGPX){
		$_SESSION['lastTrkPt'] = end($lastTrkPtGPX);
		$lastTrkPtGPX = gpx2geoJSONpoint($lastTrkPtGPX); 	// сделаем GeoJSON LineString
	}
}

$output = array('logging' => $trackLogging,'pt' => $lastTrkPtGPX);
ob_clean(); 	// очистим, если что попало в буфер
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Дата в прошлом
header('Content-Type: application/json;charset=utf-8;');
echo json_encode($output);
$content_lenght = ob_get_length();
header("Content-Length: $content_lenght");
//header("X-debug: $debugMessage");
ob_end_flush(); 	// отправляем и прекращаем буферизацию

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
