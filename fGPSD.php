<?php

$SEEN_GPS = 0x01; $SEEN_AIS = 0x08;

function askGPSD($host='localhost',$port=2947,$dataType=NULL) {
/*
$dataType - Bit vector of property flags. gpsd_json.5 ln 1355
*/
if(!$dataType) $dataType=$GLOBALS['SEEN_GPS']|$GLOBALS['SEEN_AIS']; 	// нельзя выражение в заголовке функции. А жаль
//echo "Начали. dataType=$dataType;<br>\n";
$gpsd  = @stream_socket_client('tcp://'.$host.':'.$port); // открыть сокет 
if(!$gpsd) return 'no GPSD';

//echo "Открыт сокет<br>\n";
$gpsdVersion = fgets($gpsd); 	// {"class":"VERSION","release":"3.15","rev":"3.15-2build1","proto_major":3,"proto_minor":11}
//echo "Получен VERSION\n";

fwrite($gpsd, '?WATCH={"enable":true};'); 	// велим демону включить устройства
//echo "Отправлено ВКЛЮЧИТЬ<br>\n";

$gpsdDevices = fgets($gpsd); 	// {"class":"DEVICES","devices":[{"class":"DEVICE","path":"/tmp/ttyS21","activated":"2017-09-20T20:13:02.636Z","native":0,"bps":38400,"parity":"N","stopbits":1,"cycle":1.00}]}
//echo "Получен DEVICES<br>\n"; //echo "<pre>"; 
//print_r($gpsdDevices); //echo "</pre><br>\n";
$gpsdDevices = json_decode($gpsdDevices,TRUE);
$devicePresent = array();
foreach($gpsdDevices["devices"] as $device) {
	if($device['flags']&$dataType) $devicePresent[] = $device['path']; 	// список требуемых среди обнаруженных и понятых устройств.
}
if(!$devicePresent) return 'no required devices present';

$gpsdWATCH = fgets($gpsd); 	// статус WATCH
//echo "Получен WATCH\n"; //echo "<pre>"; 
//print_r($gpsdWATCH); //echo "</pre><br>\n";

//stream_set_blocking($gpsd,FALSE); 	// установим неблокирующий режим чтения
fwrite($gpsd, '?POLL;'); 	// запросим данные
//echo "<br>Отправлено ДАЙ!<br>\n";
$gpsdData = fgets($gpsd); 	// {"class":"POLL","time":"2017-09-20T20:17:49.515Z","active":1,"tpv":[{"class":"TPV","device":"/tmp/ttyS21","mode":3,"time":"2017-09-20T23:17:48.000Z","ept":0.005,"lat":37.859215000,"lon":23.873236667,"alt":256.900,"track":146.4000,"speed":3694.843,"climb":-141.300}],"gst":[{"class":"GST","device":"/tmp/ttyS21","time":"1970-01-01T00:00:00.000Z","rms":0.000,"major":0.000,"minor":0.000,"orient":0.000,"lat":0.000,"lon":0.000,"alt":0.000}],"sky":[{"class":"SKY","device":"/tmp/ttyS21","time":"1970-01-01T00:00:00.000Z"}]}
//echo "gpsdData: ";//echo "<pre>"; 
//print_r($gpsdData); //echo "</pre>\n";
$gpsdData = json_decode($gpsdData,TRUE);
//echo "<br>JSON gpsdData: ";echo "<pre>"; print_r($gpsdData); echo "</pre>\n";
if(!$gpsdData['active']) return 'no any active devices';

$tpv = array();
foreach($gpsdData['tpv'] as $device) {
	//echo "<br>device=<pre>"; print_r($device); echo "</pre>\n";
	if(!in_array($device['device'],$devicePresent)) continue; 	// это не то устройство, которое потребовали
	$tpv[$device['time']] = $device;
}
//echo "Получены данные\n";
//print_r($tpv);

// ?WATCH={"enable":false}   ! Но это не обязательно, отключится по таймауту. В данном случае - не надо, иначе мы никогда не получим свежие координаты

fclose($gpsd);
//echo "Закрыт сокет\n";
//print_r($gpsdData);
return $tpv;
} // end function askGPSD
?>
