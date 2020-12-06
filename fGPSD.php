<?php

$SEEN_GPS = 0x01; $SEEN_AIS = 0x08;

function askGPSD($host='localhost',$port=2947,$dataType=NULL) {
/*
$dataType - Bit vector of property flags. gpsd_json.5 ln 1355
*/
if($dataType===NULL) $dataType=0x01;
//echo "\n\nНачали. dataType=$dataType;host:$host:$port<br>\n";
$gpsd  = @stream_socket_client('tcp://'.$host.':'.$port,$errno,$errstr); // открыть сокет 
if(!$gpsd) return 'no GPSD';
//stream_set_blocking($gpsd,FALSE); 	// установим неблокирующий режим чтения Что-то с ним не так...
//echo "Socket opened, handshaking\n";
$controlClasses = array('VERSION','DEVICES','DEVICE','WATCH');
$WATCHsend = FALSE; $POLLsend = FALSE;
do { 	// при каскадном соединении нескольких gpsd заголовков может быть много
	$buf = fgets($gpsd); 
	if($buf === FALSE) { 	// gpsd умер
	    @socket_close($gpsd);
		$msg = "Failed to read data from gpsd: $errstr";
		echo "$msg<br>\n"; 
		return $msg;
	}
	if (!$buf = trim($buf)) {
		continue;
	}
	$buf = json_decode($buf,TRUE);
	switch($buf['class']){
	case 'VERSION': 	// можно получить от slave gpsd посде WATCH
		if(!$WATCHsend) { 	// команды WATCH ещё не посылали
			$res = fwrite($gpsd, '?WATCH={"enable":true};'."\n"); 	// велим демону включить устройства
			if($res === FALSE) { 	// gpsd умер
				socket_close($gpsd);
				$msg =  "Failed to send WATCH to gpsd: $errstr";
				echo "$msg<br>\n"; 
				return $msg;
			}
			$WATCHsend = TRUE;
			//echo "Sending TURN ON\n";
		}
		break;
	case 'DEVICES': 	// соберём подключенные устройства со всех gpsd, включая slave
		//echo "Received DEVICES\n"; //
		$devicePresent = array();
		foreach($buf["devices"] as $device) {
			if($device['flags']&$dataType) $devicePresent[] = $device['path']; 	// список требуемых среди обнаруженных и понятых устройств.
		}
		break;
	case 'DEVICE': 	// здесь информация о подключенных slave gpsd, т.е., общая часть path в имени устройства. Полезно для опроса конкретного устройства, но нам не надо. 
		//echo "Received about slave DEVICE<br>\n"; //
		break;
	case 'WATCH': 	// 
		//echo "Received WATCH<br>\n"; //
		//print_r($gpsdWATCH); //
		if(!$POLLsend) { 	// к slave gpsd POLL не отсылают? Тогда шлём POLL после первого WATCH
			//echo "<br>Отправлено ДАЙ!<br>\n";
			$res = fwrite($gpsd, '?POLL;'."\n"); 	// запросим данные
			if($res === FALSE) { 	// gpsd умер
				socket_close($gpsd);
				$msg =  "Failed to send POLL to gpsd: $errstr";
				echo "$msg<br>\n"; 
				return $msg;
			}
			$POLLsend = TRUE;
		}
		break;
	}
	
}while(in_array($buf['class'],$controlClasses));

if(!$devicePresent) return 'no required devices present';
//echo "<pre>\n"; print_r($devicePresent); echo "</pre><br>\n";

@fwrite($gpsd, '?WATCH={"enable":false};'."\n"); 	// велим демону выключить устройства
fclose($gpsd);
//echo "Закрыт сокет\n";

//echo "<br>JSON gpsdData: ";echo "<pre>"; print_r($gpsdData); echo "</pre>\n";
if(!$buf['active']) return 'no any active devices';

$tpv = array();
foreach($buf['tpv'] as $device) {
	//echo "<br>device=<pre>"; print_r($device); echo "</pre>\n";
	if(!in_array($device['device'],$devicePresent)) continue; 	// это не то устройство, которое потребовали
	if($device['time'])	$tpv[$device['time']] = $device; 	// askGPSD, с ключём - время
	else $tpv[] = $device; 	// с ключём  - целым.
}
//echo "Получены данные\n";
//echo "<br>device=<pre>"; print_r($tpv); echo "</pre>\n";
//print_r($buf);
return $tpv;
} // end function askGPSD


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
$tpv = array();
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
