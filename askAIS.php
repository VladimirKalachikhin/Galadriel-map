<?php
/* 
*/
ob_start(); 	// попробуем перехватить любой вывод скрипта
$path_parts = pathinfo(__FILE__); // определяем каталог скрипта
chdir($path_parts['dirname']); // задаем директорию выполнение скрипта
require_once('fGPSD.php'); // fGPSD.php
require('params.php'); 	// пути и параметры

$SEEN_AIS = 0x08;
//echo "netAISJSONfileName=$netAISJSONfileName; <br><br>\n";

$AISdata = askGPSD($gpsdHost,$gpsdPort,$SEEN_AIS); 	// исходные данные
//echo "Ответ:<pre>"; print_r($AISdata); echo "</pre>";
if(is_string($AISdata)) {
	$AISdata = '{"error":"'.$AISdata.'"}';
	goto DISPLAY;
}
if( $netAISJSONfileName) { 	// Объединим данные AIS и netAIS
	$netAISJSONfileName = getNetAISdFilesNames($netAISJSONfileName);
	clearstatcache(TRUE,$netAISJSONfileName);
	$netAISdata = json_decode(@file_get_contents($netAISJSONfileName),TRUE); 	// 
	if(! is_array($netAISdata)) $netAISdata = array();
	//echo "netAISdata <pre>"; print_r($netAISdata); echo "</pre><br>\n";
	
	foreach($netAISdata as $mmsi => $data) {
		$AISdata[$mmsi] = $data;
	}
	//echo "AISdata <pre>"; print_r($AISdata); echo "</pre><br>\n";
}

DISPLAY:
ob_end_clean(); 			// очистим, если что попало в буфер
header('Content-Type: application/json;charset=utf-8;');
echo json_encode($AISdata)."\n"; 	// 

return;



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
