<?php
/* POLL your gpsd
*/
ob_start(); 	// попробуем перехватить любой вывод скрипта
$gpsdHost = 'localhost';
$gpsdPort = 2947;
require_once('fGPSD.php'); // fGPSD.php
$LefletRealtime = posToLeafletRealtime($gpsdHost,$gpsdPort); 	// получим ВремяПозициюСкорость от gpsd
ob_end_clean(); 			// очистим, если что попало в буфер
header('Content-Type: application/json;charset=utf-8;');
echo "$LefletRealtime \n";
return;

function getPosAndInfo($host='localhost',$port=2947) { 
/* Собирает информацию с подключенных датчиков ГПС, AIS, etc. - что умеет gpsd
gpsd GET клиент
Использует POLL, поэтому сразу возвращает последние известные координаты, даже если ГПС сломано
Возвращает массив, соответствующий по структуре geoJSON
Первый объект geoJSON - сообщения
Второй - собственное местоположение
Третий - информация ais
*/
// возвращаемый массив, все значения - обязательные атрибуты в смысле geoJSON, 'id' => 'gps' - необязательно, но нужно для Leaflet RealTime
// так в соответствии со спецификацией
$geoJSON = array(
'type' => 'FeatureCollection',
'features' => array(
	array(
	'type' => 'Feature',
	'geometry' => null,
	'id' => 'status',
	'properties' => null
	),
	array(
	'type' => 'Feature',
	'geometry' => null,
	'id' => 'gps',
	'properties' => null
	),
	array(
	'type' => 'FeatureCollection',
	'features' => array(
		array(
		'type' => 'Feature',
		'geometry' => null,
		'id' => 'ais0',
		'properties' => null
		)
	)
	)
)
);

$gpsdData = askGPSD($host,$port,$GLOBALS['SEEN_GPS']|$GLOBALS['SEEN_AIS']);
if(is_string($gpsdData)) {
    $geoJSON['features'][0]['properties']= array('error' => $gpsdData); 	// корректный geoJSON
    return $geoJSON;
}

krsort($gpsdData); 	// отсортируем по времени к прошлому
$lat=0; $lon=0; $heading=0; $speed=0;
foreach($gpsdData as $device) {
	//echo "<br>device=<pre>"; print_r($device); echo "</pre>\n";
	if($device['mode'] < 2) continue; 	// координат нет или это не ГПС
	if($device['mode'] == 3) { 	// последний по времени 3D fix - больше ничего не надо
		$tpv = array(
			$device['lon'], 	// долгота
			$device['lat'], 	// широта
			$device['track'], 	// курс
			$device['speed'], 	// скорость
			$device['time'],
			$device['epx'], 	// метры ошибки по x
			$device['epy'], 	// метры ошибки по y
			$device['eps'] 	// метры/сек ошибки скорости
		);
		break;
	}
	elseif(!$tpv) { 	// возьмём самый последний 2D fix
		$tpv = array(
			$device['lon'], 	// долгота
			$device['lat'], 	// широта
			$device['track'], 	// курс
			$device['speed'], 	// скорость
			$device['time'],
			$device['epx'], 	// метры ошибки по x
			$device['epy'], 	// метры ошибки по y
			$device['eps'] 	// метры/сек ошибки скорости
		);
	} 	// а более ранние 2D fixses игнорируем
}
//echo "Получены данные\n";
//print_r($tpv);

if(!$tpv){ 	// координат нет, потому что не было ни одного готового устройства
    $geoJSON['features'][0]['properties'] = array('error' => 'no fix from any devices'); 	// ничего нет, облом
    return $geoJSON;
}

// Запишем координаты, скорость и направление себя
$geoJSON['features'][1]['geometry'] = array(
	'type' => 'Point',
	'coordinates' => array($tpv[0],$tpv[1]),
	
);
$geoJSON['features'][1]['properties'] = array(
	'heading' => $tpv[2],
	'velocity' => $tpv[3],
	'time' => $tpv[4],
	'errX' => $tpv[5],
	'errY' => $tpv[6],
	'errV' => $tpv[7],
);
// Запишем каждый объект AIS

return $geoJSON;
} // end function getPosAndInfo

function posToLeafletRealtime($host='localhost',$port=2947) {
// делает из нормального geoJSON горбатый для LeafletRealtime
$LefletRealtime = getPosAndInfo($host,$port); 	// получим ВремяПозициюСкорость от gpsd
$LefletRealtime['features'][0]['properties']['id'] = $LefletRealtime['features'][0]['id'];
//$LefletRealtime['features'][0]['geometry'] = $LefletRealtime['features'][1]['geometry'];
$LefletRealtime['features'][1]['properties']['id'] = $LefletRealtime['features'][1]['id'];;
// Для каждого объекта ais
//print_r($LefletRealtime);
return json_encode($LefletRealtime);
}; // end function posToLeafletRealtime


?>
