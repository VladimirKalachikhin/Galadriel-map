<?php
/**/
// Интернационализация
if(strpos($_SERVER['HTTP_ACCEPT_LANGUAGE'],'ru')===FALSE) { 	// клиент - нерусский
//if(TRUE) {
	$dashboardSpeedTXT = 'Velocity';
	$dashboardSpeedMesTXT = 'km/h';
	$dashboardHeadingTXT = 'Heading';
	$dashboardGNSSoldTXT = 'GNSS data old';
}
else {
	$dashboardHeadingTXT = 'Истинный курс';
	$dashboardSpeedTXT = 'Скорость';
	$dashboardSpeedMesTXT = 'км/ч';
	$dashboardGNSSoldTXT = 'Данные геопозиционирования устарели';
}
$gpsdHost = 'localhost'; $gpsdPort = 2947;
require_once('fGPSD.php'); // fGPSD.php 

$mode = $_REQUEST['mode'];

$tpv = getData($gpsdHost,$gpsdPort);
echo "Ответ:<pre>"; print_r($tpv); echo "</pre>";
if(is_string($tpv)) {
	$symbol = $tpv;
	goto DISPLAY;
}
$gnssTime = new DateTime($tpv['time']); 	// 
$gnssTime = $gnssTime->getTimestamp();
/*
if((time()-$gnssTime)>30) {
	$symbol = $dashboardGNSSoldTXT;	// данные ГПС устарели более, чем на 30 секунд 
	goto DISPLAY;
}
*/

switch($mode) {
case 'heading':
	$header = $dashboardHeadingTXT;
	$symbol = round($tpv['track']); 	// 
	$nextsymbol = "$dashboardSpeedTXT ".round($tpv['speed']*60*60/1000,1)." $dashboardSpeedMesTXT"; 	// скорость от gpsd - в метрах в секунду
	$mode = '';
	break;
default:
	$header = "$dashboardSpeedTXT, $dashboardSpeedMesTXT";
	$symbol = round($tpv['speed']*60*60/1000,1); 	// скорость от gpsd - в метрах в секунду
	$nextsymbol = "$dashboardHeadingTXT ".round($tpv['track']); 	// 
	$mode = 'heading';
}
DISPLAY:
$fontZ = intdiv(mb_strlen($symbol),3); 	// считая, что штатный размер шрифта позволяет разместить 4 символа на экране
if($fontZ>1) {
	$fontZ = round((1/$fontZ)*100);
	$symbol = "<span style='font-size:$fontZ%;'>$symbol</span>";
}

?>
<!DOCTYPE html >
<html lang="ru">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
	<meta http-equiv="Pragma" content="no-cache" />
	<meta http-equiv="Expires" content="0" />
	<!--<meta http-equiv="refresh" content="2">-->
	<meta http-equiv="refresh" content="2">
   <title>Dashboard</title>
   <style>
@media (max-height: 356px) {
	.big_symbol {
		font-size:220px;
	}
	.mid_symbol {
		font-size:30px;
	}
}
@media (min-height: 357px) and (max-height: 576px) {
	.big_symbol {
		font-size:250px;
	}
	.mid_symbol {
		font-size:40px;
	}
}
@media (min-height: 577px) and (max-height: 743px) {
	.big_symbol {
		font-size:320px;
	}
	.mid_symbol {
		font-size:45px;
	}
}
@media (min-height: 744px) and (max-height: 899px) {
	.big_symbol {
		font-size:400px;
	}
	.mid_symbol {
		font-size:50px;
	}
}
@media (min-height: 900px) {
	.big_symbol {
		font-size:530px;
	}
	.mid_symbol {
		font-size:65px;
	}
}
   </style>
   <script>
function beep() {
    var snd = new Audio("data:audio/wav;base64,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qJrx4KOQjahZxWbcZgztj2c49nKmkId44S71j0c8eV9yDK6uPRzx5X18eDvjvQ6yKo9ZSS6l//8elePK/Lf//IInrOF/FvDoADYAGBMGb7FtErm5MXMlmPAJQVgWta7Zx2go+8xJ0UiCb8LHHdftWyLJE0QIAIsI+UbXu67dZMjmgDGCGl1H+vpF4NSDckSIkk7Vd+sxEhBQMRU8j/12UIRhzSaUdQ+rQU5kGeFxm+hb1oh6pWWmv3uvmReDl0UnvtapVaIzo1jZbf/pD6ElLqSX+rUmOQNpJFa/r+sa4e/pBlAABoAAAAA3CUgShLdGIxsY7AUABPRrgCABdDuQ5GC7DqPQCgbbJUAoRSUj+NIEig0YfyWUho1VBBBA//uQZB4ABZx5zfMakeAAAAmwAAAAF5F3P0w9GtAAACfAAAAAwLhMDmAYWMgVEG1U0FIGCBgXBXAtfMH10000EEEEEECUBYln03TTTdNBDZopopYvrTTdNa325mImNg3TTPV9q3pmY0xoO6bv3r00y+IDGid/9aaaZTGMuj9mpu9Mpio1dXrr5HERTZSmqU36A3CumzN/9Robv/Xx4v9ijkSRSNLQhAWumap82WRSBUqXStV/YcS+XVLnSS+WLDroqArFkMEsAS+eWmrUzrO0oEmE40RlMZ5+ODIkAyKAGUwZ3mVKmcamcJnMW26MRPgUw6j+LkhyHGVGYjSUUKNpuJUQoOIAyDvEyG8S5yfK6dhZc0Tx1KI/gviKL6qvvFs1+bWtaz58uUNnryq6kt5RzOCkPWlVqVX2a/EEBUdU1KrXLf40GoiiFXK///qpoiDXrOgqDR38JB0bw7SoL+ZB9o1RCkQjQ2CBYZKd/+VJxZRRZlqSkKiws0WFxUyCwsKiMy7hUVFhIaCrNQsKkTIsLivwKKigsj8XYlwt/WKi2N4d//uQRCSAAjURNIHpMZBGYiaQPSYyAAABLAAAAAAAACWAAAAApUF/Mg+0aohSIRobBAsMlO//Kk4soosy1JSFRYWaLC4qZBYWFRGZdwqKiwkNBVmoWFSJkWFxX4FFRQWR+LsS4W/rFRb/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////VEFHAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAU291bmRib3kuZGUAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAMjAwNGh0dHA6Ly93d3cuc291bmRib3kuZGUAAAAAAAAAACU=");  
    snd.play();
}
//beep();
   </script>
</head>
<body>
<div style='text-align:center; top:0;'>
	<span class='mid_symbol' style='vertical-align:middle; padding: 0; margin: 0;'>
	<?php
	//echo "fontZ=$fontZ;<br>\n";
	echo $header;
	?>
	</span>
</div>
<div style='padding:2% 0; margin:0;'>
	<div id='dashboard' style='text-align:center; padding: 0; margin: 0;'>
	<span class='big_symbol' style='vertical-align:middle;'>
	<?php
	echo $symbol;
	?>
	</span>
	</div>
</div>
<div style='text-align:center; bottom:0; padding: 0; margin: 0;'>
	<a href="dashboard.php?mode=<?php echo $mode; ?>">
	<button style='width:90%;'>
	<span class='mid_symbol' style='vertical-align:middle;'>
	<?php
	echo $nextsymbol;
	?>
	</span>
	</button>
	</a>
</div>
</body>
</html>
<?php

function getData($gpsdHost='localhost',$gpsdPort=2947) {
/**/
$gpsdData = askGPSD($gpsdHost,$gpsdPort,$SEEN_GPS); 	// 
//echo "Получено от gpsd:<pre>"; print_r($gpsdData); echo "</pre>";
if(is_string($gpsdData)) return $gpsdData;

krsort($gpsdData); 	// отсортируем по времени к прошлому
foreach($gpsdData as $device) {
	if($device['mode'] == 3) { 	// последний по времени 3D fix 
		$tpv = array(
			'track' => $device['track'], 	// курс
			'speed' => $device['speed'] 	// скорость
		);
		// считаем, что это более достоверно
		if($device['magtrack']) $tpv['magtrack'] = $device['magtrack']; 	// магнитный курс
		if($device['magvar']) $tpv['magvar'] = $device['magvar']; 	// магнитное склонение
		if($device['depth']) $tpv['depth'] = $device['depth']; 	// глубина
	}
	if(!$tpv['track']) $tpv['track'] = $device['track']; 	// курс
	if(!$tpv['speed']) $tpv['speed'] = $device['speed']; 	// скорость
	$tpv['time'] = $device['time'];
	if(!$tpv['magtrack']) $tpv['magtrack'] = $device['magtrack']; 	// магнитный курс
	if(!$tpv['magvar']) $tpv['magvar'] = $device['magvar']; 	// магнитное склонение
	if(!$tpv['depth']) $tpv['depth'] = $device['depth']; 	// глубина

	if($tpv['track'] AND $tpv['speed'] AND $tpv['magtrack'] AND $tpv['magvar'] AND $tpv['depth'])	break;
}

return $tpv;
} // end function getData
?>
