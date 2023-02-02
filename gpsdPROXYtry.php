<?php
ob_start(); 	// попробуем перехватить любой вывод скрипта
require('params.php'); 	// пути и параметры
// start gpsdPROXY
exec("$phpCLIexec $gpsdPROXYpath/gpsdPROXY.php > /dev/null 2>&1 &");	// невозможно узнать, чем кончился запуск
ob_end_clean(); 			// очистим, если что попало в буфер
//header('Content-Type: application/json;charset=utf-8;');
//echo "$return \n";
?>
