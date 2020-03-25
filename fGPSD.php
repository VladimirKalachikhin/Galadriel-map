<?php
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

echo "Начали\n";
$gpsd  = @stream_socket_client('tcp://'.$host.':'.$port); // открыть сокет 
if(!$gpsd){
    $geoJSON['features'][0]['properties']= array('error' => 'no GPSD'); 	// корректный geoJSON
    return $geoJSON;
}
echo "Открыт сокет\n";
$gpsdVersion = fgets($gpsd); 	// {"class":"VERSION","release":"3.15","rev":"3.15-2build1","proto_major":3,"proto_minor":11}
//echo "Получен VERSION\n";

fwrite($gpsd, '?WATCH={"enable":true};'); 	// велим демону включить устройства
echo "Отправлено ВКЛЮЧИТЬ\n";
$gpsdDevices = fgets($gpsd); 	// {"class":"DEVICES","devices":[{"class":"DEVICE","path":"/tmp/ttyS21","activated":"2017-09-20T20:13:02.636Z","native":0,"bps":38400,"parity":"N","stopbits":1,"cycle":1.00}]}
echo "Получен DEVICES\n<pre>"; print_r($gpsdDevices); echo "</pre><br>\n";
$gpsdWATCH = fgets($gpsd); 	// статус WATCH
echo "Получен WATCH\n<pre>"; print_r($gpsdWATCH); echo "</pre><br>\n";
$gpsdDevices = json_decode($gpsdDevices,TRUE);
$devicePresent = FALSE;
foreach($gpsdDevices["devices"] as $device) {
	if($device['flags']) $devicePresent = TRUE; 	// обнаружены и идентифицированы какие-то устройства
}
if(!$devicePresent){
    $geoJSON['features'][0]['properties'] = array('error' => 'no any devices present'); 	// ничего нет, облом
    return $geoJSON;
}

$lat=0; $lon=0; $heading=0; $speed=0;
$mode = 0;
//stream_set_blocking($gpsd,FALSE); 	// установим неблокирующий режим чтения
fwrite($gpsd, '?POLL;'); 	// запросим данные
echo "Отправлено ДАЙ!<br>\n";
$gpsdData = fgets($gpsd); 	// {"class":"POLL","time":"2017-09-20T20:17:49.515Z","active":1,"tpv":[{"class":"TPV","device":"/tmp/ttyS21","mode":3,"time":"2017-09-20T23:17:48.000Z","ept":0.005,"lat":37.859215000,"lon":23.873236667,"alt":256.900,"track":146.4000,"speed":3694.843,"climb":-141.300}],"gst":[{"class":"GST","device":"/tmp/ttyS21","time":"1970-01-01T00:00:00.000Z","rms":0.000,"major":0.000,"minor":0.000,"orient":0.000,"lat":0.000,"lon":0.000,"alt":0.000}],"sky":[{"class":"SKY","device":"/tmp/ttyS21","time":"1970-01-01T00:00:00.000Z"}]}
//echo "<pre>"; 
print_r($gpsdData); //echo "</pre>\n";
$gpsdData = json_decode($gpsdData,TRUE);

$tpv = array();
foreach($gpsdData['tpv'] as $device) {
	echo "device=<pre>"; print_r($device); echo "</pre>\n";
	if(($mode = $device['mode']) < 2) continue; 	// координат нет или это не ГПС
	$tpv[$device['time']] = array(
		$device['lon'], 	// долгота
		$device['lat'], 	// широта
		$device['track'], 	// курс
		$device['speed'], 	// скорость
		$device['time'],
		$device['epx'], 	// метры ошибки по x
		$device['epy'], 	// метры ошибки по y
		$device['eps'] 	// метры/сек ошибки скорости
	);
}
ksort($tpv); 	// отсортируем по времени получения координат	
$tpv = array_pop($tpv); 	// массив самых свежих координат
//echo "mode=$mode;\n";
//echo "Получены данные\n";
//print_r($tpv);

fclose($gpsd);
//echo "Закрыт сокет\n";
//print_r($gpsdData);
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
