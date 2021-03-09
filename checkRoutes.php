<?php
//ob_start(); 	// попробуем перехватить любой вывод скрипта
session_start();
require('params.php'); 	// пути и параметры

$fresh = 60*60*24; 	//sec. The file was modified not later than this ago

$now=time(); $shanged = array();
$routesInfo = glob("$routeDir/*.gpx");
//echo ":<pre>"; print_r($routesInfo); echo "</pre>";
clearstatcache();
foreach($routesInfo as $fileName){
	$name=end(explode('/',$fileName)); 	// basename не работает с неанглийскими буквами!!!!
	$mTime=filemtime($fileName);
	//echo "$name ".($now-$mTime)." $fileName\n";
	if($now-$mTime > $fresh) continue; 	// изменён давно
	//echo "$name  $fileName\n _SESSION['shanged'][$name]['sended']=".$_SESSION['shanged'][$name]['sended'].";\n";
	if($_SESSION['shanged'][$name]['sended']==$mTime) continue; 	// это время изменения уже было сообщено
	$_SESSION['shanged'][$name]['sended']=$mTime;
	$shanged[]=$name;
}
session_write_close();
$shanged = json_encode($shanged);
//ob_end_clean(); 			// очистим, если что попало в буфер
header('Content-Type: application/json;charset=utf-8;');
echo "$shanged \n";
?>
