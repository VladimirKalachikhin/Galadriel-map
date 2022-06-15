<?php ob_start(); 	// попробуем перехватить любой вывод скрипта
/*
 Получаем список выполняющихся заданий на скачивание
$jobsDir -- tileproxy params.php
$jobsInWorkDir -- tileproxy params.php
*/
chdir(__DIR__); // задаем директорию выполнение скрипта
require_once('params.php'); 	// пути и параметры
if($jobsDir[0]!='/') $jobsDir = "$tileCachePath/$jobsDir";	//  сделаем путь абсолютным, потому что jobsDir - из конфига GaladrielCache
if($jobsInWorkDir[0]!='/') $jobsInWorkDir = "$tileCachePath/$jobsInWorkDir";	//  сделаем путь абсолютным
if($jobsDir[0]!='/') $jobsDir = "$tileCachePath/$jobsDir";

if($_REQUEST['restartLoader']) {
	exec("$phpCLIexec $tileCachePath/loaderSched.php > /dev/null 2>&1 &");
	sleep(1);
}

$jobsInfo = array();
foreach(preg_grep('~.[0-9]$~', scandir($jobsDir)) as $jobName) {	 	// возьмём только файлы с цифровым расшрением
	$jobSize = filesize("$jobsDir/$jobName");
	if(!$jobSize) continue;	// внезапно может оказаться файл нулевой длины
	$jobComleteSize =  @filesize("$jobsInWorkDir/$jobName"); 	// файла в этот момент может уже и не оказаться
	//echo "jobSize=$jobSize; jobComleteSize=$jobComleteSize; <br>\n";
	if($jobComleteSize==0) $jobComleteSize = $jobSize;
	$jobsInfo[$jobName] = round((1-$jobComleteSize/$jobSize)*100); 	// выполнено
}
//echo "jobsInfo:<pre>"; print_r($jobsInfo); echo "</pre>";
// Определим, запущен ли загрузчик
$schedInfo = glob("$jobsDir/*.slock"); 	// имеющиеся PIDs запущенных планировщиков. Должен быть только один, но мало ли...
//echo "schedInfo:<pre>"; print_r($schedInfo); echo "</pre>";
$schedPID = FALSE;
foreach($schedInfo as $schedPID) {
	$schedPID=explode('.slock',end(explode('/',$schedPID)))[0]; 	// basename не работает с неанглийскими буквами!!!!
	if(file_exists( "/proc/$schedPID")) break; 	// процесс с таким PID работает
	else {
		unlink("$jobsDir/$schedPID.slock"); 	// файл-флаг остался от чего-то, но процесс с таким PID не работает - удалим
		$schedPID = FALSE;
	}
}
//echo "schedPID=$schedPID; <br>\n";

ob_clean(); 	// очистим, если что попало в буфер
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header('Content-Type: application/json;charset=utf-8;');

echo json_encode(array("loaderRun"=>$schedPID,"jobsInfo"=>$jobsInfo));

$content_lenght = ob_get_length();
header("Content-Length: $content_lenght");
ob_end_flush(); 	// отправляем и прекращаем буферизацию
?>
