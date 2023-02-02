<?php /*
Для каждого файла gpx, кроме текущего (в соответствии с параметрами) 
проверяется размер, и если он меньше - файл удаляется: считается, что это незавершённый gpx с одной точкой, которые получаются при запуске-остановке записи чарез непродолжительное время
Если файл не оканчивается на </gpx> - он считается незавершённым.
Если он оканчивается на </trkpt> - в файл дописывается необходимое для получения корректного gpx
Если файл заканчивается на \n - считается, что файл завершён и корректен.

Текущим треком считается последний (первый) незавершённый трек. Он не завершается, как признак
того, что нужно запустить запись трека.
Если нужно завершить и текущий трек (при обработке треков после всего) - нужно вызвать 
с параметром all. Однако, если будет обнаружен работающий $gpxlogger, то файл, который он пишет,
завершён не будет.

cd /www-data/www/GaladrielMap/
/usr/bin/php-cli chkGPXfiles.php all

*/
chdir(__DIR__); // задаем каталог выполнение скрипта

require_once('fcommon.php');
require('params.php'); 	// пути и параметры
//echo "trackDir=$trackDir; \n";

if($trackDir[0]!='/') $trackDir = __DIR__."/$trackDir";	// если путь относительный - будет абсолютный
//$trackDir = '/home/storage/Общедоступные/Туризм/Suomi_2018/gpx_srv';

// Параметры
if($argv) { 	// cli
	if(($allMaps = @$argv[1])!='all') $allMaps=FALSE; 	// второй элемент - первый аргумент
}
else {	// http
	$allMaps = $_REQUEST['allMaps'];
}
// Определяем, работает ли сейчас запись трека
$recordedTrackName=gpxloggerRun(true);	// поскольку неизвестно, что содержится в строке global $gpxlogger, нужно определить PID именно этого процесса
$recordedTrackName = substr(end(explode('/',$recordedTrackName)),0,-4);	// может быть, пора уже исправить basename, а не вводить новые идиотские конструкции в язык?
//echo "\nchkGPXfiles.php start recordedTrackName=$recordedTrackName;\n";

// Получаем список имён треков
//echo "trackDir=$trackDir; \n";
$trackInfo = scandir($trackDir); 	// trackDir - из файла params.php
array_walk($trackInfo,function (&$name,$ind) {
		if(strpos($name,'~')!==FALSE) $name = NULL; 	// скрытые файлы
		else $name=strstr($name,'.gpx',TRUE); 	// строка до 
	}); 	// 
$trackInfo=array_unique($trackInfo);
sort($trackInfo,SORT_NATURAL | SORT_FLAG_CASE); 	// 
if(!$trackInfo[0]) unset($trackInfo[0]); 	// строка от файлов, которые не .gpx, например - каталогов
sort($trackInfo,SORT_NATURAL | SORT_FLAG_CASE); 	// 
if(!$allMaps) {
	if( $currTrackFirst ) unset($trackInfo[0]); // не рассматриваем текущий трек
	else unset($trackInfo[count( $trackInfo )-1]);
}
//echo "trackInfo:<pre>"; print_r($trackInfo); echo "</pre>\n";
$reportFileName = $trackDir."/chkGPXfiles_report";
file_put_contents($reportFileName,date('Y-m-d_His')." Now recorded track - $recordedTrackName; \n");
foreach($trackInfo as $trk){
	if($trk==$recordedTrackName) continue;	// ничего не делаем с пишущимся сейчас файлом
	echo "\n<br> Поехал файл $trk.gpx размером " . filesize( "$trackDir/$trk.gpx" ) . "\n";
	file_put_contents($reportFileName,"Prepared file $trk.gpx \n",FILE_APPEND);
	if( filesize( "$trackDir/$trk.gpx" ) <= 573 ) {
		if(unlink( "$trackDir/$trk.gpx" ) !== FALSE)	{	
			echo "удалён короткий файл $trackDir/$trk.gpx \n"; 	// незавершённый файл с одной точкой
			file_put_contents($reportFileName,"Deleted short file $trk.gpx \n",FILE_APPEND);
			continue;
		}
	}
	$trkFileSize = filesize("$trackDir/$trk.gpx"); 	// заранее, потому что кэш
	$lastStr = tailCustom("$trackDir/$trk.gpx"); 	// fcommon.php
	$lastStrLen = strlen($lastStr);
	$lastStr = trim($lastStr);
	//echo "lastStr=".htmlspecialchars($lastStr)."; <br>\n имеет длину $lastStrLen; <br>\n";
	while($lastStr <> '</gpx>') {
		if($lastStr == '</trkpt>') {  	echo "трек готов к завершению\n";
		    	if(file_put_contents( "$trackDir/$trk.gpx", "\n </trkseg>\n </trk>\n</gpx>", FILE_APPEND ) !== FALSE) {
		    		echo "завершён файл $trackDir/$trk.gpx \n";
					file_put_contents($reportFileName,"\tFinished file $trk.gpx \n",FILE_APPEND);
		    		break;
		    	}
		}
		else {  	echo "трек не завершён, обрезаем файл на $lastStrLen байт\n";
			file_put_contents($reportFileName,"The track is not completed, will trim the file by $lastStrLen bytes \n",FILE_APPEND);
			$h = fopen("$trackDir/$trk.gpx", 'r+');
			$trkFileSize -= $lastStrLen; 	// невозможно каждый раз определять размер файла, потому что кэш
			ftruncate($h, $trkFileSize);
			fclose($h);
			$lastStr = tailCustom("$trackDir/$trk.gpx"); 	// fcommon.php
			$lastStrLen = strlen($lastStr);
			$lastStr = trim($lastStr);
			//echo "Новая lastStr=".htmlspecialchars($lastStr)."; <br>\n имеет длину $lastStrLen; <br>\n";
		}
	}
}
?>
