<?php
/* POLL your gpsd
*/
ob_start(); 	// попробуем перехватить любой вывод скрипта
include('params.php'); 	// пути и параметры
if(!$gpsdHost) $gpsdHost = 'localhost';
if(!$gpsdPort) $gpsdPort = 2947;
require_once('fGPSD.php'); // fGPSD.php

$LefletRealtime = json_encode(getPosAndInfo($gpsdHost,$gpsdPort)); 	// получим ВремяПозициюСкорость от gpsd

ob_end_clean(); 			// очистим, если что попало в буфер
header('Content-Type: application/json;charset=utf-8;');
echo "$LefletRealtime \n";
return;

