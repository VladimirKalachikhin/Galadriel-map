<?php
/* Вызывается для создания задания на загрузку */
require('params.php'); 	// пути и параметры

if($mapSourcesDir[0]!='/') $mapSourcesDir = "$tileCachePath/$mapSourcesDir";	// если путь абсолютный (и в unix, конечно)
if($jobsDir[0]!='/') $jobsDir = "$tileCachePath/$jobsDir";	// если путь абсолютный (и в unix, конечно)
//echo "mapSourcesDir=$mapSourcesDir; tileCachePath=$tileCachePath;<br>\n";

$XYs = $_REQUEST['xys'];
$jobName = $_REQUEST['jobname'];
//echo "XYs=$XYs; jobName=$jobName; <br>\n";

$name_parts = pathinfo($jobName);
//echo "name_parts:<pre>"; print_r($name_parts); echo "</pre>";
if(!(is_numeric($name_parts['extension']) AND (intval($name_parts['extension']) <=20 AND intval($name_parts['extension']) >=0))) return; 	// расширение - не масштаб
if(!is_file("$mapSourcesDir/".$name_parts['filename'].'.php')) return; 	// нет такого источника
if(!$XYs) return; 	// нет собственно задания
// Создадим задание
file_put_contents("$jobsDir/$jobName",$XYs);
//file_put_contents("$jobName",$XYs);
//exit;
chmod("$jobsDir/$jobName",0777); 	// чтобы запуск от другого юзера
//echo "$tileCachePath<br>\n";
// Запустим планировщик
exec("$phpCLIexec $tileCachePath/loaderSched.php > /dev/null 2>&1 &"); 	// если запускать сам файл, ему нужны права
//exec("$tileCachePath/loaderSched.php"); 	
echo $jobName;
?>
