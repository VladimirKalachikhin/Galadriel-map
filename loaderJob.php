<?php
ob_start(); 	// попробуем перехватить любой вывод скрипта
session_start();
/* Вызывается для создания задания на загрузку */
chdir(__DIR__); // задаем директорию выполнение скрипта
require('params.php'); 	// пути и параметры

if($mapSourcesDir[0]!='/') $mapSourcesDir = "$tileCachePath/$mapSourcesDir";	// если путь абсолютный (и в unix, конечно)
if($jobsDir[0]!='/') $jobsDir = "$tileCachePath/$jobsDir";	// если путь абсолютный (и в unix, конечно)
//echo "mapSourcesDir=$mapSourcesDir; tileCachePath=$tileCachePath;<br>\n";

$XYs = $_REQUEST['xys'];
$jobName = $_REQUEST['jobname'];
//echo "XYs=$XYs; jobName=$jobName; <br>\n";
//$jobName='OpenTopoMap.11';
//$XYs="1189,569\n1190,569\n1191,569";
if($jobName != 'restart') {
	$name_parts = pathinfo($jobName);
	//echo "name_parts:<pre>"; print_r($name_parts); echo "</pre>";
	if(!(is_numeric($name_parts['extension']) AND (intval($name_parts['extension']) <=20 AND intval($name_parts['extension']) >=0))) return; 	// расширение - не масштаб
	if(!is_file("$mapSourcesDir/".$name_parts['filename'].'.php')) return; 	// нет такого источника
	if(!$XYs) return; 	// нет собственно задания
	// Создадим задание
	file_put_contents("$jobsDir/$jobName",$XYs,FILE_APPEND); 	// возможно, такое задание уже есть. Тогда, скорее всего, тайлы указанного масштаба не будут загружены, а будут загружены эти тайлы следующего масштаба. Не страшно.
	// Сохраним задание на всякий случай
	file_put_contents("$jobsDir/oldJobs/$jobName".'_'.gmdate("Y-m-d_Gis", time()),$XYs);
	//file_put_contents("$jobName",$XYs);
	chmod("$jobsDir/$jobName",0666); 	// чтобы запуск от другого юзера
}

// Запустим планировщик
// Если эта штука вызывается для нескольких карт подряд, то просто при запуске планировщика
// каждый его экземпляр видит, что запущены другие, и завершается. В результате не запускается ни один.
// Поэтому будем запускать планировщик не чаще чем раз в секунд.
$status = 0;
//echo (time()-$_SESSION['loaderJobStartLoader'])." ";
if((time()-$_SESSION['loaderJobStartLoader'])>3) {
	exec("$phpCLIexec $tileCachePath/loaderSched.php > /dev/null 2>&1 &",$ret,$status); 	// если запускать сам файл, ему нужны права
	//exec("$phpCLIexec $tileCachePath/loaderSched.php > log_$jobName.txt 2>&1 &",$ret,$status); 	// если запускать сам файл, ему нужны права
	if($status==0)$_SESSION['loaderJobStartLoader'] = time();	// при успешном запуске
}

ob_clean(); 	// очистим, если что попало в буфер
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header('Content-Type: text/html; charset=utf-8;');

echo "$status;$jobName"; 	// вернём что-нибудь. Например, $status запущенного exec. Правда, $status всегда будет 0, потому что оболочка запустится, а про остальное мы не узнаем. $ret -- пустой массив

$content_lenght = ob_get_length();
header("Content-Length: $content_lenght");
//header("X-debug: $debugMessage");
ob_end_flush(); 	// отправляем и прекращаем буферизацию
?>
