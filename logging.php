<?php
/* Включает/выключает запись трека посредством gpxlogger */
ob_start(); 	// попробуем перехватить любой вывод скрипта
$path_parts = pathinfo(__FILE__); // определяем каталог скрипта
chdir($path_parts['dirname']); // задаем директорию выполнение скрипта
require('fcommon.php'); 	// 
require('params.php'); 	// пути и параметры

//$loggerNoFixTimeout = 30; 	// sec A new track is created if there's no fix written for an interval
//$loggerMinMovie = 5; 	// m Motions shorter than this will not be logged
// from params.php
$gpxlogger = "gpxlogger -e shm -r -i $loggerNoFixTimeout -m $loggerMinMovie"; 	// will listen to the local gpsd using shared memory, reconnect, interval, minmove. С $gpsdHost:$gpsdPort почему-то не работает в Ubuntu 20	
$outpuFileName = ''; 	

//$_REQUEST['startLogging'] = 1;
//$_REQUEST['stopLogging'] = 1;

if($status=(int)gpxloggerRun()) { 	// fcommon.php
	if($_REQUEST['stopLogging']) { 	
		exec("kill $status");
		$status=(int)gpxloggerRun(); 	// оно могло и не убиться
		if($status) echo "Unable to stop logging\n";
		else echo "Stoped logging\n";
	}
}
else {
	if($_REQUEST['startLogging']) { 	
		$outpuFileName = date('Y-m-d_His').'.gpx'; 	
		$fullOutpuFileName = $trackDir.'/'.$outpuFileName; 	
		$LoggerPid = exec("$gpxlogger -f $fullOutpuFileName > /dev/null 2>&1 & echo $!"); 	// exec не будет ждать завершения: & - daemonise; echo $! - return daemon's PID
		$status=(int)gpxloggerRun(); 	// оно могло и не запуститься
		if($status) echo "Started logging track to $outpuFile\n";
		else echo "Unable to start logging\n";
	}
}
ob_end_clean(); 			// очистим, если что попало в буфер
header('Content-Type: application/json;charset=utf-8;');
echo json_encode(array($status,$outpuFileName));
return;

?>
