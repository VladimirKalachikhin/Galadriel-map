<?php session_start();
/* Отдаёт последнюю структуру типа trkpt из незакрытого файла gpx
Имя файла без расширения передаётся в первом параметре или в currTrackName
Файл ищется в $gpxDir из params.php
Если файл закрыт, или какие-то проблемы с последней точкой - не отдаёт ничего
*/

ob_start(); 	// попробуем перехватить любой вывод скрипта
$path_parts = pathinfo($_SERVER['SCRIPT_FILENAME']); // определяем каталог скрипта
$selfPath = $path_parts['dirname'];
//chdir($path_parts['dirname']); // сменим каталог выполнение скрипта
require_once('fcommon.php');
require('params.php'); 	// пути и параметры

if($gpxDir[0]!='/') $gpxDir = "$selfPath/$gpxDir";	// если путь относительный - будет абсолютный
$currTrackFileName = $_REQUEST['currTrackName'];
if(! $currTrackFileName) $currTrackFileName = $argv[1];
if(! $currTrackFileName) goto END;
$currTrackFileName = "$gpxDir/$currTrackFileName.gpx";
//$currTrackFileName = "$gpxDir/07-05-2018_003913.gpx";
//echo "currTrackFileName=$currTrackFileName; <br>\n";

$tailStrings = 5 * 20; 	// сколько строк заведомо включает последнюю trkpt. Спецификация говорит, что trkpt может иметь 20 строк

clearstatcache(TRUE,"$currTrackFileName");
$lastTrkPt = explode("\n",tailCustom($currTrackFileName,$tailStrings));
$lastTrkPt = array_filter( $lastTrkPt, 'strlen' ); 	// удалим пустые строки
//print_r($lastTrkPt);
if( trim(end($lastTrkPt)) == '</gpx>') goto END; 	// если это завершённый GPX
foreach($lastTrkPt as $i => $str) {
	if(substr(trim($str),0,6) == '<trkpt') 	$lastTrkPtStart = $i; 	// номер строки начала точки
	elseif(trim($str) == '</trkpt>') 	$lastTrkPtEnd = $i; 	// номер строки конца точки
//	elseif(trim($str) == '</trkseg>') 	$lastTrkSegEnd = $i; 	// номер строки конца сегмента
}
//echo "lastTrkPtStart=$lastTrkPtStart; lastTrkPtEnd=$lastTrkPtEnd; lastTrkSegEnd=$lastTrkSegEnd; <br>\n";
if($lastTrkPtStart > $lastTrkPtEnd) goto END; 	// что-то пошло не так
$lastTrkPt = implode("\n",array_slice($lastTrkPt,$lastTrkPtStart,$lastTrkPtEnd-$lastTrkPtStart+1));
//echo htmlentities("<trkseg>\n".$_SESSION['lastTrkPt']."\n$lastTrkPt\n</trkseg>\n");
ob_clean(); 	// очистим, если что попало в буфер, но заголовки выше должны отправиться
if($_SESSION['lastTrkPt'] <> $lastTrkPt) {
	$lastTrkPtGPX = "<trkseg>\n".$_SESSION['lastTrkPt']."\n$lastTrkPt\n</trkseg>\n"; 	// всегда будем оформлять путевую точку как отдельный сегмент
	$_SESSION['lastTrkPt'] = $lastTrkPt;
	header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
	header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Дата в прошлом
	header ("Content-Type: text/XML");
	echo("$lastTrkPtGPX\n");
	$content_lenght = ob_get_length();
	header("Content-Length: $content_lenght");
	ob_end_flush(); 	// отправляем и прекращаем буферизацию
}

END:
?>
