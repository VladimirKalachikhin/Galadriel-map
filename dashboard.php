<?php
/*
NW |NNW|N|NNE|NE
WNW|   | |   |ENE
W  |   | |   |E
WSW|   | |   |ESE
SW |SSW|S|SSE|SE
*/
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

$rumbNames = array('N','NNE','NE','ENE','E','ESE','SE','SSE','S','SSW','SW','WSW','W','WNW','NW','NNW');
$rumbNum = round($vps['features'][1]['properties']['heading']/22.5);
if($rumbNum==16) $rumbNum = 0;
//echo "rumbNum=$rumbNum;<br>\n";
$currRumb = array();
$currRumb[$rumbNum] = $rumbNames[$rumbNum];
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
	.big_mid_symbol {
		font-size:60px;
	}
}
@media (min-height: 357px) and (max-height: 576px) {
	.big_symbol {
		font-size:250px;
	}
	.mid_symbol {
		font-size:40px;
	}
	.big_mid_symbol {
		font-size:80px;
	}
}
@media (min-height: 577px) and (max-height: 743px) {
	.big_symbol {
		font-size:320px;
	}
	.mid_symbol {
		font-size:45px;
	}
	.big_mid_symbol {
		font-size:90px;
	}
}
@media (min-height: 744px) and (max-height: 899px) {
	.big_symbol {
		font-size:400px;
	}
	.mid_symbol {
		font-size:50px;
	}
	.big_mid_symbol {
		font-size:100px;
	}
}
@media (min-height: 900px) {
	.big_symbol {
		font-size:530px;
	}
	.mid_symbol {
		font-size:65px;
	}
	.big_mid_symbol {
		font-size:130px;
	}
}
   </style>
</head>
<body style="margin:0; padding:0;">

<table style='
	border:1px solid; 
	position:fixed; 
	width:100%; 
	height:100%; 
	margin:0; padding:0;
	text-align:center;
	opacity: 0.25;
	z-index: -1;
'>
<tr>
<td style="width:20%;height:20%;"><span class='big_mid_symbol' style='background-color:black;color:white;'><?php echo $currRumb[14]; ?></span></td>
<td style="width:20%;height:20%;"><span class='big_mid_symbol' style='background-color:black;color:white;'><?php echo $currRumb[15]; ?></span></td>
<td style="width:20%;height:20%;"><span class='big_mid_symbol' style='background-color:black;color:white;'><?php echo $currRumb[0]; ?></span></td>
<td style="width:20%;height:20%;"><span class='big_mid_symbol' style='background-color:black;color:white;'><?php echo $currRumb[1]; ?></span></td>
<td style="width:20%;height:20%;"><span class='big_mid_symbol' style='background-color:black;color:white;'><?php echo $currRumb[2]; ?></span></td>
</tr>
<tr>
<td style="width:20%;height:20%;"><span class='big_mid_symbol' style='background-color:black;color:white;'><?php echo $currRumb[13]; ?></span></td>
<td rowspan="3" colspan="3"></td>
<td style="width:20%;height:20%;"><span class='big_mid_symbol' style='background-color:black;color:white;'><?php echo $currRumb[3]; ?></span></td>
</tr>
<tr>
<td style="width:20%;height:20%;"><span class='big_mid_symbol' style='background-color:black;color:white;'><?php echo $currRumb[12]; ?></span></td>
<td style="width:20%;height:20%;"><span class='big_mid_symbol' style='background-color:black;color:white;'><?php echo $currRumb[4]; ?></span></td>
</tr>
<tr>
<td style="width:20%;height:20%;"><span class='big_mid_symbol' style='background-color:black;color:white;'><?php echo $currRumb[11]; ?></span></td>
<td style="width:20%;height:20%;"><span class='big_mid_symbol' style='background-color:black;color:white;'><?php echo $currRumb[5]; ?></span></td>
</tr>
<tr>
<td style="width:20%;height:20%;"><span class='big_mid_symbol' style='background-color:black;color:white;'><?php echo $currRumb[10]; ?></span></td>
<td style="width:20%;height:20%;"><span class='big_mid_symbol' style='background-color:black;color:white;'><?php echo $currRumb[9]; ?></span></td>
<td style="width:20%;height:20%;"><span class='big_mid_symbol' style='background-color:black;color:white;'><?php echo $currRumb[8]; ?></span></td>
<td style="width:20%;height:20%;"><span class='big_mid_symbol' style='background-color:black;color:white;'><?php echo $currRumb[7]; ?></span></td>
<td style="width:20%;height:20%;"><span class='big_mid_symbol' style='background-color:black;color:white;'><?php echo $currRumb[6]; ?></span></td>
</tr>
</table>

<div style = '
	position:fixed;
	left: 50%;
	top: 50%;
	transform:translate(-50%, -50%);
	width:70%;
'>
	<div style='text-align:center;'>
		<span class='mid_symbol' style='vertical-align:middle; padding: 0; margin: 0;'>
			<?php echo $header;	?>
		</span>
	</div>
	<div id='dashboard' style='text-align:center; padding: 0; margin: 0;'>
		<span class='big_symbol' style='vertical-align:middle;'>
			<?php echo $symbol;	?>
		</span>
	</div>
	<div style='text-align:center; bottom:0; padding: 0; margin: 0;'>
		<a href="dashboard.php?mode=<?php echo $mode; ?>">
		<button style='width:90%;'>
		<span class='mid_symbol' style='vertical-align:middle;'>
			<?php echo $nextsymbol;	?>
		</span>
		</button>
		</a>
	</div>
</div>

</body>
</html>
