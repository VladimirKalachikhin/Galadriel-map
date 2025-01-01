<?php
/* Включает/выключает запись трека посредством команды в переменной $gpxlogger 
Кладёт себя в крон для запуска каждую минуту, в результате, если есть незавершённый gpx, а 
команда $gpxlogger не запущена -- запускает $gpxlogger с новым файлом лога

Предполагается, что команда в $gpxlogger обладает свойствами gpxlogger:
завершает трек по своему убийству (не обязательно)
не умеет дописывать существующий файл
*/
ob_start(); 	// попробуем перехватить любой вывод скрипта
chdir(__DIR__); // задаем каталог выполнение скрипта
require('fcommon.php'); 	// 
require('params.php'); 	// пути и параметры
echo "Поехали\n";

if(!$trackDir) return "{[0,'']}";	// нет каталога, куда писать

//$loggerNoFixTimeout = 30; 	// sec A new track is created if there's no fix written for an interval
//$loggerMinMovie = 5; 	// m Motions shorter than this will not be logged
$outpuFileName = ''; 	

//$_REQUEST['startLogging'] = 1;
//$_REQUEST['stopLogging'] = 1;

//file_put_contents($trackDir."/logging_started_by_cron","Запущено, \n_REQUEST[startLogging]={$_REQUEST['startLogging']}\n_REQUEST[stopLogging]={$_REQUEST['stopLogging']}");

$status=(int)gpxloggerRun();	// поскольку неизвестно, что содержится в строке global $gpxlogger, нужно определить PID именно этого процесса
//echo "\n[logging.php] start status=$status;\n";
//error_log("[logging.php] start status=$status;");
//error_log("[logging.php] start status=$status;");
if($status) { 	// fcommon.php $gpxlogger работает
	if($_REQUEST['stopLogging']) { 	
		exec("kill $status");
		$status=(int)gpxloggerRun(); 	// оно могло и не убиться
		if($status) error_log("[logging.php] Unable to stop logging");
		else error_log("[logging.php] Stoped logging ");
		exec("crontab -l | grep -v '".__FILE__."'  | crontab -"); 	// удалим себя из cron
	}
	else { 	// вернём имя последнего трека. Однако, запись трека может быть криво запущена
		// и последний трек не является записываемым
		$outpuFileName = getLastTrackName();
		//echo "[logging.php] outpuFileName=$outpuFileName;\n";
		if($outpuFileName) {
			$lastTrackName = $trackDir.'/'.$outpuFileName;	// этот файла есть
			if(substr(trim(tailCustom($lastTrackName)),-6)==='</gpx>') {	// неизвестно, куда оно пишет
				$outpuFileName = pathinfo(gpxloggerRun(true))['filename'];	// fCommon.php Попробуем у писателя и спросить
				if(!file_exists($trackDir.'/'.$outpuFileName.'.gpx')) $outpuFileName = '';	// пишется не в каталог $trackDir 
			}
			else {	// трек пишется в файл $outpuFileName
				$date = DateTime::createFromFormat('Y-m-d_His',pathinfo($outpuFileName)['filename']);	// pathinfo не работает с русскими буквами!
				if(date_diff(new DateTime("now"), $date)->days >= $newTrackEveryDays){
					exec("kill $status");
					$status=(int)gpxloggerRun(); 	// оно могло и не убиться
					if($status) error_log("[logging.php] Unable to stop logging");
					else {
						error_log("[logging.php] Restarting logging after $newTrackEveryDays days");
						list($status,$outpuFileName) = startGPXlogger(); 
						$outpuFileName = pathinfo($outpuFileName)['filename'];	
					}
				}
			}
		}
	}
}
else { 	// fcommon.php $gpxlogger не работает
	if($_REQUEST['startLogging']) { 	
		list($status,$outpuFileName) = startGPXlogger(); 	
		$outpuFileName = pathinfo($outpuFileName)['filename'];	
		error_log("[logging.php] startLogging status=$status; outpuFileName=$outpuFileName;");
	}
	elseif($_REQUEST['stopLogging']) { 	
		exec("crontab -l | grep -v '".__FILE__."'  | crontab -"); 	// удалим себя из cron
	}
	else { 
		// Проверим, нет ли необходимости запустить запись трека
		$lastTrackName = getLastTrackName();
		if($lastTrackName){
			$lastTrackName = $trackDir.'/'.$lastTrackName;
			$str = file_get_contents($lastTrackName,false,null,0,300);	// возьмём первые 300 то0ли байт, то-ли символов... Они всё сломали.
			if(strpos($str,'gpx') !== false){	// это, видимо, файл gpx
				echo "[logging.php] gpxlogger не запущен, последний трек: $lastTrackName\n";
				if(substr(trim(tailCustom($lastTrackName,10)),-6)!=='</gpx>'){
					echo "gpxlogger не запущен, а последний трек $lastTrackName не является завершённым, запускаем запись трека\n";
					list($status,$outpuFileName) = startGPXlogger(); 	
					$outpuFileName = pathinfo($outpuFileName)['filename'];	
				}
				else {
					exec("crontab -l | grep -v '".__FILE__."'  | crontab -"); 	// удалим себя из cron
				};
			}
			else {	// это кривой файл, стало быть, запись трека запускается криво, и не надо пытаться её запускать
				exec("crontab -l | grep -v '".__FILE__."'  | crontab -"); 	// удалим себя из cron
			};
		};
	};
};
ob_end_clean(); 			// очистим, если что попало в буфер
header('Content-Type: application/json;charset=utf-8;');
echo json_encode(array($status,$outpuFileName));
return;


function startGPXlogger(){
global $trackDir, $gpxlogger,$phpCLIexec;

$outpuFileName = date('Y-m-d_His').'.gpx'; 	
$fullOutpuFileName = $trackDir.'/'.$outpuFileName; 	
$gpxlgr = str_replace(array('&logfile','&host'),array($fullOutpuFileName,$_SERVER['HTTP_HOST']),$gpxlogger);
//echo "Logger start as:<br>$gpxlgr<br>\n";
$LoggerPid = exec("$gpxlgr > /dev/null 2>&1 & echo $!",$output,$result_code); 	// exec не будет ждать завершения: & - daemonise; echo $! - return daemon's PID
//error_log("[logging.php] [startGPXlogger] Logger start as: $gpxlgr  Result:".implode(' ',$output).", result_code=$result_code; LoggerPid=$LoggerPid;");
$status=(int)gpxloggerRun(); 	// оно могло и не запуститься
if($status) {
	//error_log("[logging.php] [startGPXlogger] Started logging track to $outpuFileName");
	// положим запуск в крон, чтобы возобновить запись лога после перезагрузки
	//echo '(crontab -l ; echo "* * * * * '.$phpCLIexec.' '.__FILE__."\n".'") | crontab -'."\n";
	exec("crontab -l | grep -v '".__FILE__."'  | crontab -"); 	// удалим себя из cron
	//exec('(crontab -l ; echo "* * * * * '.$phpCLIexec.' -q '.__FILE__."\n".'") | crontab - '); 	// каждую минуту, -q -- php не будет выдавать http заголовков. В википедии утверждается, что последняя пустая строка обязательна. Но чёта и так работает...
	$res=exec('(crontab -l ; echo "* * * * * '.$phpCLIexec.' -q '.__FILE__.'") | crontab - '); 	// каждую минуту, -q -- php не будет выдавать http заголовков
	//file_put_contents($trackDir."/logging_started_by_cron","запись в crontab завершилась с $res");
	return array($status,$outpuFileName);
}
else //error_log("[logging.php] [startGPXlogger] Unable to start logging");
return array(0,'');
}; // end function startGPXlogger()

?>
