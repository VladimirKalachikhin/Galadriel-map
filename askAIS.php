<?php
/* 
*/
ob_start(); 	// попробуем перехватить любой вывод скрипта
$path_parts = pathinfo(__FILE__); // определяем каталог скрипта
chdir($path_parts['dirname']); // задаем директорию выполнение скрипта
require('params.php'); 	// пути и параметры

echo "aisJSONfileName=$aisJSONfileName; netAISJSONfileName=$netAISJSONfileName; <br><br>\n";

$AISdata = FALSE;
if($aisJSONfileName and $netAISJSONfileName) { 	// Объединим данные AIS и netAIS
	list($aisJSONfileName,$daemonRunningFlag) = getAISdFilesNames();
	$netAISJSONfileName = getNetAISdFilesNames();
	echo "aisJSONfileName=$aisJSONfileName; netAISJSONfileName=$netAISJSONfileName; <br><br>\n";
	// Запускаем gpsdAISd
	exec("$phpCLIexec $gpsdAISd -o$aisJSONfileName -h$gpsdHost -p$gpsdPort > /dev/null 2>&1 & echo $!"); 	// exec не будет ждать завершения: & - daemonise; echo $! - return daemon's PID
	echo "gpsdAISd daemon started as:<br>$phpCLIexec $gpsdAISd -o$aisJSONfileName -h$gpsdHost -p$gpsdPort<br><br>\n";
	@unlink($daemonRunningFlag); 	// Удалим флаг в знак того, что мы читаем данные
	clearstatcache(TRUE,$aisJSONfileName);
	$AISdata = json_decode(file_get_contents($aisJSONfileName),TRUE); 	// 
	if(! is_array($AISdata)) $AISdata=array();

	clearstatcache(TRUE,$netAISJSONfileName);
	$netAISdata = json_decode(file_get_contents($netAISJSONfileName),TRUE); 	// 
	if(! is_array($netAISdata)) $netAISdata = array();
	//echo "netAISdata <pre>"; print_r($netAISdata); echo "</pre><br>\n";
	
	foreach($netAISdata as $mmsi => $data) {
		foreach($data as $key => $val) {
			$AISdata[$mmsi][$key] = $val;
		}
	}
	
	//echo "AISdata <pre>"; print_r($AISdata); echo "</pre><br>\n";
	$AISdata = json_encode($AISdata);
}
elseif($netAISJSONfileName) $aisJSONfileName = getNetAISdFilesNames();
else {
	list($aisJSONfileName,$daemonRunningFlag) = getAISdFilesNames();
	clearstatcache(TRUE,$aisJSONfileName);
	if(file_exists($daemonRunningFlag)) unlink($daemonRunningFlag); 	// Удалим флаг в знак того, что мы читаем данные
	else {
		echo "aisJSONfileName=$aisJSONfileName; netAISJSONfileName=$netAISJSONfileName; <br><br>\n";
		// Запускаем gpsdAISd
		exec("$phpCLIexec $gpsdAISd -o$aisJSONfileName -h$gpsdHost -p$gpsdPort > /dev/null 2>&1 & echo $!"); 	// exec не будет ждать завершения: & - daemonise; echo $! - return daemon's PID
		echo "gpsdAISd daemon started as:<br>$phpCLIexec $gpsdAISd -o$aisJSONfileName -h$gpsdHost -p$gpsdPort<br><br>\n";
	}	
}
echo "aisJSONfileName=$aisJSONfileName;<br><br>\n";

clearstatcache(TRUE,$aisJSONfileName);
ob_end_clean(); 			// очистим, если что попало в буфер
header('Content-Type: application/json;charset=utf-8;');
if($AISdata === FALSE) echo file_get_contents($aisJSONfileName)."\n";
else echo "$AISdata \n"; 	// 
return;





function getAISdFilesNames() {
global $aisJSONfileName;
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

function getNetAISdFilesNames() {
global $netAISJSONfileName;
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
