<?php
/* 
*/
ob_start(); 	// попробуем перехватить любой вывод скрипта
chdir(__DIR__); // задаем директорию выполнение скрипта
require_once('fGPSD.php'); // fGPSD.php
require('params.php'); 	// пути и параметры

$SEEN_AIS = 0x08;

$AISdata = getPosAndInfo($gpsdHost,$gpsdPort,$SEEN_AIS); 	// исходные данные
//echo "Ответ:<pre>"; print_r($AISdata); echo "</pre>";
if(is_string($AISdata)) {
	$AISdata = array("error"=>$AISdata);
	goto DISPLAY;
}
if($netAISPath) { 	// Объединим данные AIS и netAIS
	$netAISJSONfilesDir = getAISdFilesNames($netAISJSONfilesDir); 	// определим имя и создадим каталог для данных netAIS
	//echo "netAISJSONfilesDir=$netAISJSONfilesDir;<br>\n";
	$netAISfileNames = preg_grep('~onion~', scandir($netAISJSONfilesDir)); 	// возьмём только файлы onion
	foreach($netAISfileNames as $netAISJSONfileName){
		$netAISJSONfileName = $netAISJSONfilesDir.$netAISJSONfileName;
		//echo "netAISJSONfileName=$netAISJSONfileName; <br><br>\n";
		clearstatcache(TRUE,$netAISJSONfileName);
		$netAISdata = json_decode(file_get_contents($netAISJSONfileName),TRUE); 	// 
		if(! is_array($netAISdata)) $netAISdata = array();
		//echo "netAISdata <pre>"; print_r($netAISdata); echo "</pre><br>\n";		
		foreach($netAISdata as $mmsi => $data) {
			$AISdata[$mmsi] = $data;
		}
	}
	//echo "AISdata <pre>"; print_r($AISdata); echo "</pre><br>\n";
}

DISPLAY:
ob_end_clean(); 			// очистим, если что попало в буфер
header('Content-Type: application/json;charset=utf-8;');
echo json_encode($AISdata)."\n"; 	// 

return;



function getAISdFilesNames($path) {
$path = rtrim($path,'/');
if(!$path) $path = 'data';
$dirName = pathinfo($path, PATHINFO_DIRNAME);
$fileName = pathinfo($path,PATHINFO_BASENAME);
if((!$dirName) OR ($dirName=='.')) {
	$dirName = sys_get_temp_dir()."/netAIS"; 	// права собственно на /tmp в системе могут быть замысловатыми
	@mkdir($dirName, 0777,true); 	// 
	@chmod($dirName,0777); 	// права будут только на каталог netAIS. Если он вложенный, то на предыдущие, созданные по true в mkdir, прав не будет. Тогда надо использовать umask.
	$path = $dirName."/".$fileName.'/';
}
else $path .= '/';
return $path;
} // end function getAISdFilesNames

?>
