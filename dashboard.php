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
//$gpsdHost = 'localhost';
//$gpsdPort = 2947;
require_once('fGPSD.php'); // fGPSD.php

$mode = $_REQUEST['mode'];

$vps = getPosAndInfo(); 	// получим ВремяПозициюСкорость от gpsd
//echo "Ответ:<pre>"; print_r($vps); echo "</pre>";
if($vps['features'][0]['properties']['error']) {
	$symbol = $vps['features'][0]['properties']['error'];
	goto DISPLAY;
}
$gnssTime = new DateTime($vps['features'][1]['properties']['time']); 	// 
$gnssTime = $gnssTime->getTimestamp();

if((time()-$gnssTime)>30) {
	$symbol = $dashboardGNSSoldTXT;	// данные ГПС устарели более, чем на 30 секунд
	goto DISPLAY;
}

switch($mode) {
case 'heading':
	$header = $dashboardHeadingTXT;
	$symbol = round($vps['features'][1]['properties']['heading']); 	// 
	$nextsymbol = "$dashboardSpeedTXT ".round($vps['features'][1]['properties']['velocity']*60*60/1000,1)." $dashboardSpeedMesTXT"; 	// скорость от gpsd - в метрах в секунду
	$mode = '';
	break;
default:
	$header = "$dashboardSpeedTXT, $dashboardSpeedMesTXT";
	$symbol = round($vps['features'][1]['properties']['velocity']*60*60/1000,1); 	// скорость от gpsd - в метрах в секунду
	$nextsymbol = "$dashboardHeadingTXT ".round($vps['features'][1]['properties']['heading']); 	// 
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
	<!--<meta http-equiv="refresh" content="2">-->
	<meta http-equiv="refresh" content="2">
   <title>Dashboard</title>
   <style>
@media (min-width: 768px) and (max-width: 991px) {
	.big_symbol {
		font-size:250px;
	}
	.mid_symbol {
		font-size:45px;
	}
}
@media (min-width: 992px) and (max-width: 1199px) {
	.big_symbol {
		font-size:350px;
	}
	.mid_symbol {
		font-size:50px;
	}
}
@media (min-width: 1200px) {
	.big_symbol {
		font-size:400px;
	}
	.mid_symbol {
		font-size:60px;
	}
}
a {
	text-decoration: none;
}
a:link {
	color: #000000; /* Цвет ссылок */
}
a:visited {
	color: #000000; /* Цвет посещенных ссылок */
}
a:hover, a:focus {
	color: blue; /* Цвет посещенных ссылок */
}
   </style>
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
	<button style='width:90%;'>
	<a href="dashboard.php?mode=<?php echo $mode; ?>">
	<span class='mid_symbol' style='vertical-align:middle;'>
	<?php
	echo $nextsymbol;
	?>
	</span>
	</a>
	</button>
</div>
</body>
</html>
