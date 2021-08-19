<?php

$SEEN_GPS = 0x01; $SEEN_AIS = 0x08;

function askGPSD($host='localhost',$port=2947,$dataType=0x01) {
/*
$dataType - Bit vector of property flags. gpsd_json.5 ln 1355
*/
//echo "\n\nНачали. dataType=$dataType;host:$host:$port<br>\n";
$gpsd  = @stream_socket_client('tcp://'.$host.':'.$port,$errno,$errstr); // открыть сокет 
if(!$gpsd) return 'no GPSD';
//stream_set_blocking($gpsd,FALSE); 	// установим неблокирующий режим чтения Что-то с ним не так...
//echo "Socket opened, handshaking\n";
$controlClasses = array('VERSION','DEVICES','DEVICE','WATCH');
$WATCHsend = FALSE; $POLLsend = FALSE;
do { 	// при каскадном соединении нескольких gpsd заголовков может быть много
	$buf = fgets($gpsd); 
	//echo "<br>buf: ";echo "<pre>"; print_r($buf); echo "</pre>\n";
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
//echo "buf: ";echo "<pre>"; print_r($buf); echo "</pre>\n";

if(!$devicePresent) return 'no required devices present';
//echo "devicePresent: <pre>\n"; print_r($devicePresent); echo "</pre><br>\n";

@fwrite($gpsd, '?WATCH={"enable":false};'."\n"); 	// велим демону выключить устройства
fclose($gpsd);
//echo "Закрыт сокет\n";

if(!$buf['active']) return 'no any active devices';

$tpv = array();
foreach($buf['tpv'] as $device) {
	//echo "<br>device=<pre>"; print_r($device); echo "</pre>\n";
	//if(!in_array($device['device'],$devicePresent)) continue; 	// это не то устройство, которое потребовали. Однако, в случае gpsdPROXY или каскадного соединения gpsd здесь будет оригинальное устройство, сгенерировавшее данный, а в $devicePresent -- устройство, от которого данные получены. И будет неправильный облом.
	//print "Поток всякого из сети будем брать от специального демона, а не по POLL<br>\n";
	if(substr($device['device'],0,6) == 'tcp://') {
		//echo "<br>device=<pre>"; print_r($device); echo "</pre>\n";
		//if(!$_SESSION[$device['device']]) $_SESSION[$device['device']] = array();
		foreach($device as $type => $val) { 	// возможно, и не стоит? Данные могут быть очень неактуальны
			$_SESSION[$device['device']][$type] = $val;
		}
		$device = $_SESSION[$device['device']];
	}
	
	if($device['time'])	$tpv[$device['time']] = $device; 	// askGPSD, с ключём - время
	else {
		//$device['time'] = $buf['time']; 	// девайс не указал время -- используем время из сессии gpsd. А откуда gpsd его берёт?
		$tpv[] = $device; 	// с ключём  - целым.
	}
	//echo "<br>device=<pre>"; print_r($device); echo "</pre>\n";
}
//echo "Получены данные <pre>"; print_r($tpv); echo "</pre>\n";
return $tpv;
} // end function askGPSD


function getPosAndInfo($host='',$port=NULL) { 
/* Собирает информацию с подключенных датчиков ГПС, etc. - что умеет gpsd или SignalK
*/
if(is_array($host)) { 	// спрашивать у SignalK
	//error_log("fGPSD.php getPosAndInfo: will ask spatial info from SignalK");
	$TPV = getPosAndInfoFromSignalK($host);
}
elseif($host and $port) { 	// спрашивать у gpsd
	//error_log("fGPSD.php getPosAndInfo: will ask spatial info from gpsd");
	$TPV = getPosAndInfoFromGPSD($host,$port);
	if(isset($TPV['error'])) {
		$TPV = getPosAndInfoFromSignalK();
	}
}
else { 	// попробуем найти SignalK
	$TPV = getPosAndInfoFromSignalK();
}
return $TPV;
} // end function getPosAndInfo


function getPosAndInfoFromGPSD($host='localhost',$port=2947) { 
/* Пытается получить Направление, Местополжение и Скорость от gpsd 
Возвращает массив
При неудаче -- массив с ключём 'error'
*/
//error_log("fGPSD.php getPosAndInfoFromGPSD: asking spatial info from gpsd");
$gpsdData = askGPSD($host,$port,$GLOBALS['SEEN_GPS']);
if(is_string($gpsdData)) {
    $gpsdData = array('error' => $gpsdData); 	// 
    return $gpsdData;
}

krsort($gpsdData); 	// отсортируем по времени к прошлому
$lat=0; $lon=0; $heading=0; $speed=0;
$tpv = array();
$anyDepth = FALSE;
foreach($gpsdData as $device) {
	//echo "<br>device=<pre>"; print_r($device); echo "</pre>\n";
	if($device['mode'] < 2) { 	// координат нет или это не ГПС
		if($device['depth']) $anyDepth = $device['depth'];
		continue;
	}
	if($device['mode'] == 3) { 	// последний по времени 3D fix - больше ничего не надо
		$tpv = array(
			'lon' => $device['lon'], 	// долгота
			'lat' => $device['lat'], 	// широта
			'heading' => $device['track'], 	// курс
			'velocity' => $device['speed'], 	// скорость
			'time' => $device['time'],
			'errX' => $device['epx'], 	// метры ошибки по x
			'errY' => $device['epy'], 	// метры ошибки по y
			'errS' => $device['eps'], 	// метры/сек ошибки скорости
			'depth' => $device['depth'] 	// метры глубина
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
			'errS' => $device['eps'], 	// метры/сек ошибки скорости
			'depth' => $device['depth'] 	// метры глубина
		);
	} 	// а более ранние 2D fixses игнорируем
}
//echo "Получены данные\n";
//print_r($tpv);

if(!$tpv){ 	// координат нет, потому что не было ни одного готового устройства
    $gpsdData = array('error' => 'no fix from any devices'); 	// ничего нет, облом
    return $gpsdData;
}
if(!$tpv['depth'] and $anyDepth) $tpv['depth'] = $anyDepth;
return $tpv;
} // end function getPosAndInfoFromGPSD


function getPosAndInfoFromSignalK($server=array()) { 
/* Пытается получить Направление, Местополжение и Скорость от SignalK 
Возвращает массив
При неудаче -- массив с ключём 'error'

Серверы SignslK через какое-то время перестают быть видимыми через zeroconf, хотя, вроде, работают.
Поэтому обнаружение их никак не гарантируется.
*/

//error_log("fGPSD.php getPosAndInfoFromSignalK: asking spatial info from SignalK");
$serversDirName = sys_get_temp_dir().'/signalK';
$serversName = $serversDirName.'/signalKservers';
if(!file_exists($serversDirName)){ 	// один file_exists быстрей, чем два mkdir и chmod
	mkdir($serversDirName, 0777,true); 	// 
	chmod($serversDirName,0777); 	// права будут только на каталог netAIS. Если он вложенный, то на предыдущие, созданные по true в mkdir, прав не будет. Тогда надо использовать umask.
}
$findServers = unserialize(@file_get_contents($serversName));
$serversCount = @count($findServers);
//echo "Read findServers:"; print_r($findServers); echo "\n";

if(!$findServers) {
	if($server) {
		$self = json_decode(file_get_contents("http://{$server[0]}:{$server[1]}/signalk/v1/api/self"),TRUE);
		if(substr($self,0,8)=='vessels.') {
			$self = substr($self,9);
			$findServers = array();
			$findServers[$self] = array(	'host' => $server[0], 
									'port' => $server[1],
									'self' => $self
								);
		}
	}
	else {
		//error_log("fGPSD.php getPosAndInfoFromSignalK: search SignalK services");
		$ret = exec('avahi-browse --terminate --resolve --parsable --no-db-lookup _signalk-http._tcp',$signalkDiscovery);
		//error_log("fGPSD.php getPosAndInfoFromSignalK: search SignalK services result: $ret\n");
		if($ret) {
			$findServers = array();
			foreach($signalkDiscovery as $l){
				if($l[0] != '=') continue;
				$server = array();
				$signalkDiscovery = explode(';',$l);
				$server['host'] = $signalkDiscovery[7];
				$server['port'] = $signalkDiscovery[8];
				$self = explode(' ',$signalkDiscovery[9]);
				foreach($self as $l1){
					if(substr($l1,1,5) == 'self=') { 	// там кавычки
						$selfStr = substr(trim($l1,'"'),5);
						break;
					}
				}
				$server['self'] = $selfStr;
				$findServers[$selfStr] = $server;
			}
		}
		else {
			$self = json_decode(file_get_contents("http://localhost:3000/signalk/v1/api/self"),TRUE);
			if(substr($self,0,8)=='vessels.') {
				$self = substr($self,9);
				$findServers = array();
				$findServers[$self] = array(	'host' => $server[0], 
										'port' => $server[1],
										'self' => $self
									);
			}
		}
	}
}
if(!$findServers) {
	$TPV = array('error' => 'no any Signal K resources found'); 	// ничего нет, облом
	return $TPV;
}
//error_log("fGPSD.php getPosAndInfoFromSignalK: SignalK services found!");
//print_r($findServers);
// Серверы обнаружены
$spatialInfo = array();
foreach($findServers as $serverID => $server){
	$signalkDiscovery = json_decode(file_get_contents("http://{$server['host']}:{$server['port']}/signalk"),TRUE);
	if(! $signalkDiscovery) { 	// нет сервера, нет связи, и т.п.
		unset($findServers[$serverID]);
		continue;
	}
	//print_r($http_response_header);
	//print_r($signalkDiscovery);
	$APIurl = $signalkDiscovery['endpoints']['v1']['signalk-http'];
	$vessel = json_decode(file_get_contents($APIurl."vessels/{$server['self']}"),TRUE);
	$position = $vessel['navigation'];
	//print_r($position);
	if(! $position) { 	// нет такого ресурса
		unset($findServers[$serverID]);
		continue;
	}
	$timestamp = strtotime($position['position']['timestamp']);
	$TPV = array('time' => $timestamp);
	if($position['position']['value']['longitude'] and $position['position']['value']['latitude']) {
		$TPV['lon'] = $position['position']['value']['longitude']; 	// долгота
		$TPV['lat'] = $position['position']['value']['latitude']; 	// широта
		$TPV['heading'] = $position['courseOverGroundTrue']['value']*180/M_PI; 	// курс, исходно -- в радианах
		$TPV['velocity'] = $position['speedOverGround']['value']; 	// скорость m/sec
		//echo date(DATE_RFC2822,$timestamp).' '.$position['position']['timestamp'];
	}
	if($vessel['environment']['depth']['belowSurface']) $TPV['depth'] = $vessel['environment']['depth']['belowSurface']['value'];
	elseif($vessel['environment']['depth']['belowTransducer']) $TPV['depth'] = $vessel['environment']['depth']['belowTransducer']['value'];
	$spatialInfo[$timestamp] = $TPV;
}
krsort($spatialInfo); 	// отсортируем по времени к прошлому
//print_r(reset($spatialInfo));
//echo "Write findServers:"; print_r($findServers); echo "\n";
if($serversCount != count($findServers)){
	file_put_contents($serversName,serialize($findServers));
	@chmod($serversName,0666); 	// если файла не было
}
return reset($spatialInfo);
} // end function getPosAndInfoFromSignalK


?>
