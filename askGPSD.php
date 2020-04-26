<?php
/* POLL your gpsd
*/
ob_start(); 	// попробуем перехватить любой вывод скрипта
$gpsdHost = 'localhost';
$gpsdPort = 2947;
require_once('fGPSD.php'); // fGPSD.php

$LefletRealtime = json_encode(getPosAndInfo($gpsdHost,$gpsdPort)); 	// получим ВремяПозициюСкорость от gpsd

ob_end_clean(); 			// очистим, если что попало в буфер
header('Content-Type: application/json;charset=utf-8;');
echo "$LefletRealtime \n";
return;

function getPosAndInfo($host='localhost',$port=2947) { 
/* Собирает информацию с подключенных датчиков ГПС, etc. - что умеет gpsd
Использует POLL, поэтому сразу возвращает последние известные координаты, даже если ГПС сломано
*/
$gpsdData = askGPSD($host,$port,$GLOBALS['SEEN_GPS']);
if(is_string($gpsdData)) {
    $gpsdData = array('error' => $gpsdData); 	// 
    return $gpsdData;
}

krsort($gpsdData); 	// отсортируем по времени к прошлому
$lat=0; $lon=0; $heading=0; $speed=0;
foreach($gpsdData as $device) {
	//echo "<br>device=<pre>"; print_r($device); echo "</pre>\n";
	if($device['mode'] < 2) continue; 	// координат нет или это не ГПС
	if($device['mode'] == 3) { 	// последний по времени 3D fix - больше ничего не надо
		$tpv = array(
			'lon' => $device['lon'], 	// долгота
			'lat' => $device['lat'], 	// широта
			'heading' => $device['track'], 	// курс
			'velocity' => $device['speed'], 	// скорость
			'time' => $device['time'],
			'errX' => $device['epx'], 	// метры ошибки по x
			'errY' => $device['epy'], 	// метры ошибки по y
			'errS' => $device['eps'] 	// метры/сек ошибки скорости
		);
		break;
	}
	elseif(!$tpv) { 	// возьмём самый последний 2D fix
		$tpv = array(
			'lon' => $device['lon'], 	// долгота
			'lat' => $device['lat'], 	// широта
			'heading' => $device['track'], 	// курс
			'velocity' => $device['speed'], 	// скорость
			'time' => $device['time'],
			'errX' => $device['epx'], 	// метры ошибки по x
			'errY' => $device['epy'], 	// метры ошибки по y
			'errS' => $device['eps'] 	// метры/сек ошибки скорости
		);
	} 	// а более ранние 2D fixses игнорируем
}
//echo "Получены данные\n";
//print_r($tpv);

if(!$tpv){ 	// координат нет, потому что не было ни одного готового устройства
    $gpsdData = array('error' => 'no fix from any devices'); 	// ничего нет, облом
    return $gpsdData;
}
return $tpv;
} // end function getPosAndInfo

?>
