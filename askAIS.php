<?php
/* 
*/
session_start();
ob_start(); 	// попробуем перехватить любой вывод скрипта
$path_parts = pathinfo(__FILE__); // определяем каталог скрипта
chdir($path_parts['dirname']); // задаем директорию выполнение скрипта
require('params.php'); 	// пути и параметры

$daemonRunningFTimeOut = 60; 	// сек. Если gpsdAISd не обновлял флаг-файл столько -- он умер

//echo "aisJSONfileName=$aisJSONfileName; netAISJSONfileName=$netAISJSONfileName; <br><br>\n";
list($aisJSONfileName,$daemonRunningFlag) = getAISdFilesNames($aisJSONfileName);
//echo "aisJSONfileName=$aisJSONfileName; daemonRunningFlag=$daemonRunningFlag;<br>\n";
clearstatcache(TRUE,$daemonRunningFlag);
if(file_exists($daemonRunningFlag)) {
	echo "daemonRunningFlag exists.<br>\n";
	unlink($daemonRunningFlag); 	// Удалим флаг в знак того, что мы читаем данные
	clearstatcache(TRUE,$daemonRunningFlag);
}
else {
	// Запускаем gpsdAISd
	exec("$phpCLIexec $gpsdAISdPath/$gpsdAISd -o$aisJSONfileName -h$host -p$port > /dev/null 2>&1 & echo $!"); 	// exec не будет ждать завершения: & - daemonise; echo $! - return daemon's PID
	echo "gpsdAISd daemon started as:<br>$phpCLIexec $gpsdAISdPath/$gpsdAISd -o$aisJSONfileName -h$host -p$port<br><br>\n";
}	

$AISdata = json_decode(file_get_contents($aisJSONfileName),TRUE);
if(!$AISdata) $AISdata = array();
if( $netAISJSONfileName) { 	// Объединим данные AIS и netAIS
	$netAISJSONfileName = getNetAISdFilesNames($netAISJSONfileName);
	clearstatcache(TRUE,$netAISJSONfileName);
	$netAISdata = json_decode(@file_get_contents($netAISJSONfileName),TRUE); 	// 
	if(! is_array($netAISdata)) $netAISdata = array();
	//echo "netAISdata <pre>"; print_r($netAISdata); echo "</pre><br>\n";
	
	foreach($netAISdata as $mmsi => $data) {
		foreach($data as $key => $val) {
			$AISdata['AIS'][$mmsi][$key] = $val;
		}
	}
	
	//echo "AISdata <pre>"; print_r($AISdata); echo "</pre><br>\n";
}

ob_end_clean(); 			// очистим, если что попало в буфер
header('Content-Type: application/json;charset=utf-8;');
echo json_encode($AISdata['AIS'])."\n"; 	// 

return;





function getAISdFilesNames($aisJSONfileName) {
$dirName = pathinfo($aisJSONfileName, PATHINFO_DIRNAME);
$fileName = pathinfo($aisJSONfileName,PATHINFO_BASENAME);
if((!$dirName) OR ($dirName=='.')) {
	$dirName = sys_get_temp_dir()."/gpsdAISd"; 	// права собственно на /tmp в системе могут быть замысловатыми
	@mkdir($dirName, 0777,true); 	// 
	@chmod($dirName,0777); 	// права будут только на каталог gpsdAISd. Если он вложенный, то на предыдущие, созданные по true в mkdir, прав не будет. Тогда надо использовать umask.
	$aisJSONfileName = $dirName."/".$fileName;
}
$daemonRunningFlag = $aisJSONfileName.'Flag';
// создадим сам файл, если надо
clearstatcache(TRUE,$aisJSONfileName);
if(!file_exists($aisJSONfileName)) {
	file_put_contents($aisJSONfileName,json_encode(array()));
	@chmod($aisJSONfileName,0666); 	// 
}
return [$aisJSONfileName,$daemonRunningFlag];
}

function getNetAISdFilesNames($netAISJSONfileName) {
$dirName = pathinfo($netAISJSONfileName, PATHINFO_DIRNAME);
$fileName = pathinfo($netAISJSONfileName,PATHINFO_BASENAME);
if((!$dirName) OR ($dirName=='.')) {
	$dirName = sys_get_temp_dir()."/netAIS"; 	// права собственно на /tmp в системе могут быть замысловатыми
	@mkdir($dirName, 0777,true); 	// 
	@chmod($dirName,0777); 	// права будут только на каталог netAIS. Если он вложенный, то на предыдущие, созданные по true в mkdir, прав не будет. Тогда надо использовать umask.
	$netAISJSONfileName = $dirName."/".$fileName;
}
return $netAISJSONfileName;
}

?>
