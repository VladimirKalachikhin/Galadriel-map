<?php
/* Включает/выключает запись трека посредством gpxlogger */
ob_start(); 	// попробуем перехватить любой вывод скрипта
$path_parts = pathinfo(__FILE__); // определяем каталог скрипта
chdir(__DIR__); // задаем директорию выполнение скрипта
require('fcommon.php'); 	// 
require('params.php'); 	// пути и параметры

//$loggerNoFixTimeout = 30; 	// sec A new track is created if there's no fix written for an interval
//$loggerMinMovie = 5; 	// m Motions shorter than this will not be logged
$outpuFileName = ''; 	

//$_REQUEST['startLogging'] = 1;
//$_REQUEST['stopLogging'] = 1;

$status=(int)gpxloggerRun();
echo "status=$status;<br>\n";
if($status) { 	// fcommon.php
	if($_REQUEST['stopLogging']) { 	
		exec("kill $status");
		$status=(int)gpxloggerRun(); 	// оно могло и не убиться
		if($status) echo "Unable to stop logging\n";
		else echo "Stoped logging\n";
	}
	else { 	// вернём имя последнего трека
		$trackNames = glob($trackDir.'/*gpx');
		if($currTrackFirst) $outpuFileName = $trackNames[0]; 	// params.php
		else $outpuFileName = $trackNames[count($trackNames)-1];
		$outpuFileName = explode('/',$outpuFileName); 	// выделим имя файла, которое, в принципе, может быть кириллицей
		$outpuFileName = $outpuFileName[count($outpuFileName)-1];
	}
}
else {
	if($_REQUEST['startLogging']) { 	
		$outpuFileName = date('Y-m-d_His').'.gpx'; 	
		$fullOutpuFileName = $trackDir.'/'.$outpuFileName; 	
		$gpxlogger = str_replace(array('&logfile','&host'),array($fullOutpuFileName,$_SERVER['HTTP_HOST']),$gpxlogger);
		//echo "Logger start as:<br>$gpxlogger<br>\n";
		$LoggerPid = exec("$gpxlogger > /dev/null 2>&1 & echo $!"); 	// exec не будет ждать завершения: & - daemonise; echo $! - return daemon's PID
		$status=(int)gpxloggerRun(); 	// оно могло и не запуститься
		if($status) echo "Started logging track to $outpuFileName\n";
		else echo "Unable to start logging\n";
	}
}
ob_end_clean(); 			// очистим, если что попало в буфер
header('Content-Type: application/json;charset=utf-8;');
echo json_encode(array($status,$outpuFileName));
return;

?>
