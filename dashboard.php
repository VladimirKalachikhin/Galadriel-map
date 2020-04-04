<?php session_start();
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
	$dashboardHeadingTXT = 'Heading';
	$dashboardMagHeadingTXT = 'Magnetic heading';
	$dashboardMagVarTXT = 'Magnetic variation';
	$dashboardSpeedTXT = 'Velocity';
	$dashboardMinSpeedAlarmTXT = 'Speed too high';
	$dashboardMaxSpeedAlarmTXT = 'Speed too low';
	$dashboardSpeedMesTXT = 'km/h';
	$dashboardDepthTXT = 'Depth';
	$dashboardDepthAlarmTXT = 'Too shallow';
	$dashboardDepthMesTXT = 'm';
	$dashboardGNSSoldTXT = 'Instrument data old';
	$dashboardDepthMenuTXT = 'Shallow';
	$dashboardMinSpeedMenuTXT = 'Min speed';
	$dashboardMaxSpeedMenuTXT = 'Max speed';
}
else {
	$dashboardHeadingTXT = 'Истинный курс';
	$dashboardMagHeadingTXT = 'Магнитный курс';
	$dashboardMagVarTXT = 'Склонение';
	$dashboardSpeedTXT = 'Скорость';
	$dashboardMinSpeedAlarmTXT = 'Скорость меньше допустимой';
	$dashboardMaxSpeedAlarmTXT = 'Скорость больше допустимой';
	$dashboardSpeedMesTXT = 'км/ч';
	$dashboardDepthTXT = 'Глубина';
	$dashboardDepthAlarmTXT = 'Слишком мелко';
	$dashboardDepthMesTXT = 'м';
	$dashboardGNSSoldTXT = 'Данные с приборов устарели';
	$dashboardDepthMenuTXT = 'Опасная глубина';
	$dashboardMinSpeedMenuTXT = 'Минимальная скорость';
	$dashboardMaxSpeedMenuTXT = 'Максимальная скорость';
}
$gpsdHost = 'localhost'; $gpsdPort = 2947;
require_once('fGPSD.php'); // fGPSD.php 

$mode = $_REQUEST['mode'];
if($mode) $_SESSION['mode'] = $mode;
else $mode = $_SESSION['mode'];
$magnetic = $_REQUEST['magnetic'];
//echo "Is NULL ".is_null($_REQUEST['magnetic'])."<br>\n";
if($magnetic===NULL) $magnetic = $_SESSION['magnetic'];
else $_SESSION['magnetic'] = $magnetic;
if($magnetic) $magneticTurn = 0;
else $magneticTurn = 1;
$menu = $_REQUEST['menu'];
//echo "mode=$mode; menu=$menu; magnetic=$magnetic; magneticTurn=$magneticTurn;<br>\n";

if($_REQUEST['submit']) {
	$depthAlarm = $_REQUEST['depthAlarm'];
	$minDepthValue = $_REQUEST['minDepthValue'];
	if(!$minDepthValue) $depthAlarm = FALSE;
	$_SESSION['depthAlarm'] = $depthAlarm;
	$_SESSION['minDepthValue'] = $minDepthValue;

	$minSpeedAlarm = $_REQUEST['minSpeedAlarm'];
	$minSpeedValue = $_REQUEST['minSpeedValue'];
	if(!$minSpeedValue) $minSpeedAlarm = FALSE;
	$_SESSION['minSpeedAlarm'] = $minSpeedAlarm;
	$_SESSION['minSpeedValue'] = $minSpeedValue;

	$maxSpeedAlarm = $_REQUEST['maxSpeedAlarm'];
	$maxSpeedValue = $_REQUEST['maxSpeedValue'];
	if(!$maxSpeedValue) $maxSpeedAlarm = FALSE;
	$_SESSION['maxSpeedAlarm'] = $maxSpeedAlarm;
	$_SESSION['maxSpeedValue'] = $maxSpeedValue;
}
else {
	$minDepthValue = $_SESSION['minDepthValue'];
	$depthAlarm = $_SESSION['depthAlarm'];

	$minSpeedAlarm = $_SESSION['minSpeedAlarm'];
	$minSpeedValue = $_SESSION['minSpeedValue'];

	$maxSpeedAlarm = $_SESSION['maxSpeedAlarm'];
	$maxSpeedValue = $_SESSION['maxSpeedValue'];
}
//echo "depthAlarm=$depthAlarm; minDepthValue=$minDepthValue; minSpeedAlarm=$minSpeedAlarm; minSpeedValue=$minSpeedValue; maxSpeedAlarm=$maxSpeedAlarm; maxSpeedValue=$maxSpeedValue;<br>\n";

$tpv = getData($gpsdHost,$gpsdPort);
//echo "Ответ:<pre>"; print_r($tpv); echo "</pre>";
if(is_string($tpv)) {
	$symbol = $tpv;
	goto DISPLAY;
}
$gnssTime = new DateTime($tpv['time']); 	// 
$gnssTime = $gnssTime->getTimestamp();

if((time()-$gnssTime)>30) {
	$symbol = $dashboardGNSSoldTXT;	// данные ГПС устарели более, чем на 30 секунд 
	goto DISPLAY;
}

$header = '';
// Оповещения в порядке возрастания опасности, реально сработает последнее
$alarm = FALSE;
if($minSpeedAlarm AND $tpv['speed']) {
	if($tpv['speed']*60*60/1000 <= $minSpeedValue) {
		$mode = 'speed';
		$header = $dashboardMinSpeedAlarmTXT;
		$alarmJS = 'minSpeedAlarm();';
		$alarm = TRUE;
	}
}
if($maxSpeedAlarm AND $tpv['speed']) {
	if($tpv['speed']*60*60/1000 >= $maxSpeedValue) {
		$mode = 'speed';
		$header = $dashboardMaxSpeedAlarmTXT;
		$alarmJS = 'maxSpeedAlarm();';
		$alarm = TRUE;
	}
}
if($depthAlarm AND $tpv['depth']) {
	if($tpv['depth'] <= $minDepthValue) {
		$mode = 'depth';
		$header = $dashboardDepthAlarmTXT;
		$alarmJS = 'depthAlarm();';
		$alarm = TRUE;
	}
}

switch($mode) {
case 'heading':
	if($magnetic AND $tpv['magtrack']) {
		if(!$header) $header = $dashboardMagHeadingTXT;
		$symbol = round($tpv['magtrack']);
	}
	else {
		if(!$header) $header = $dashboardHeadingTXT;
		$symbol = round($tpv['track']); 	// 
	}
	$nextsymbol = "$dashboardSpeedTXT ".round($tpv['speed']*60*60/1000,1)." $dashboardSpeedMesTXT"; 	// скорость от gpsd - в метрах в секунду
	$mode = 'speed';
	break;
case 'depth':
	if(!$header) $header = "$dashboardDepthTXT, $dashboardDepthMesTXT";
	$symbol = round($tpv['depth'],1); 	// 
	if($magnetic AND $tpv['magtrack']) $nextsymbol = "$dashboardMagHeadingTXT ".round($tpv['track']); 	// 
	else $nextsymbol = "$dashboardHeadingTXT ".round($tpv['track']); 	// 
	$mode = 'heading';
	break;
default:
	if(!$header) $header = "$dashboardSpeedTXT, $dashboardSpeedMesTXT";
	$symbol = round($tpv['speed']*60*60/1000,1); 	// скорость от gpsd - в метрах в секунду
	if($tpv['depth']) {
		$nextsymbol = "$dashboardHeadingTXT ".round($tpv['track']); 	// 
		$nextsymbol = "$dashboardDepthTXT ".round($tpv['depth'],1)." $dashboardDepthMesTXT"; 	// скорость от gpsd - в метрах в секунду
		$mode = 'depth';
	}
	else {
		$nextsymbol = "$dashboardHeadingTXT ".round($tpv['track']); 	// 
		$mode = 'heading';
	}
}

$rumbNames = array(' N ','NNE',' NE ','ENE',' E ','ESE',' SE ','SSE',' S ','SSW',' SW ','WSW',' W ','WNW',' NW ','NNW');
if($magnetic AND $tpv['magtrack']) $rumbNum = round($tpv['track']/22.5);
else $rumbNum = round($tpv['track']/22.5);
if($rumbNum==16) $rumbNum = 0;
//echo "rumbNum=$rumbNum;<br>\n";
$currRumb = array();
$currRumb[$rumbNum] = $rumbNames[$rumbNum];

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
	<?php if(!$menu) echo "<meta http-equiv='refresh' content='2; url={$_SERVER['PHP_SELF']}'>";?>
	<script src="dashboard.js">	</script>
	<?php if($alarm) echo "<script>$alarmJS</script>";?>
	<link rel="stylesheet" href="dashboard.css" type="text/css"> 
   <title>Dashboard</title>
</head>
<body style="margin:0; padding:0;">
<script>
//alert(window.outerWidth+' '+window.outerHeight);
</script>

<?php if($menu) { ?>
<form action='<?php echo $_SERVER['PHP_SELF'];?>' style = '
	position:fixed;
	right: 5%;
	top: 5%;
	width:53%;
	background-color:lightgrey;
	padding: 1rem;
	font-size: xx-large;
	z-index: 10;
'>
	<table>
		<tr style='height:3rem;'>
			<td style='width:3rem;'><input type='checkbox' name='depthAlarm' value='1' <?php if($depthAlarm) echo 'checked';?>></td><td><?php echo $dashboardDepthMenuTXT?></td><td style='width:10%;'><input type='text' name=minDepthValue value='<?php echo $minDepthValue?>' style='width:95%;font-size:x-large;'></td>
		</tr><tr style='height:3rem;'>
			<td><input type='checkbox' name='minSpeedAlarm' value='1' <?php if($minSpeedAlarm) echo 'checked';?>></td><td><?php echo $dashboardMinSpeedMenuTXT?></td><td style='width:10%;'><input type='text' name=minSpeedValue value='<?php echo $minSpeedValue?>' style='width:95%;font-size:x-large;'></td>
		</tr><tr style='height:3rem;'>
			<td><input type='checkbox' name='maxSpeedAlarm' value='1' <?php if($maxSpeedAlarm) echo 'checked';?>></td><td><?php echo $dashboardMaxSpeedMenuTXT?></td><td style='width:10%;'><input type='text' name=maxSpeedValue value='<?php echo $maxSpeedValue?>' style='width:95%;font-size:x-large;'></td>
		</tr><tr>
			<td></td><td><a href='<?php echo $_SERVER['PHP_SELF'];?>' style='text-decoration:none;'><input type='button' value='&#x2718;' style='font-size:120%;'></a><input type='submit' name='submit' value='&#x2713;' style='font-size:120%;float:right;'></td><td></td>
		</tr>
	</table>
</form>
<?php } ?>

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
	<td style="width:20%;height:20%;"><span class='big_mid_symbol wb'><?php echo $currRumb[14]; ?></span></td>
	<td style="width:20%;height:20%;"><span class='big_mid_symbol wb'><?php echo $currRumb[15]; ?></span></td>
	<td style="width:20%;height:20%;"><span class='big_mid_symbol wb'><?php echo $currRumb[0]; ?></span></td>
	<td style="width:20%;height:20%;"><span class='big_mid_symbol wb'><?php echo $currRumb[1]; ?></span></td>
	<td style="width:20%;height:20%;"><span class='big_mid_symbol wb'><?php echo $currRumb[2]; ?></span></td>
</tr>
<tr>
	<td style="width:20%;height:20%;"><span class='big_mid_symbol wb'><?php echo $currRumb[13]; ?></span></td>
	<td rowspan="3" colspan="3"></td>
	<td style="width:20%;height:20%;"><span class='big_mid_symbol wb'><?php echo $currRumb[3]; ?></span></td>
</tr>
<tr>
	<td style="width:20%;height:20%;"><span class='big_mid_symbol wb'><?php echo $currRumb[12]; ?></span></td>
	<td style="width:20%;height:20%;"><span class='big_mid_symbol wb'><?php echo $currRumb[4]; ?></span></td>
</tr>
<tr>
	<td style="width:20%;height:20%;"><span class='big_mid_symbol wb'><?php echo $currRumb[11]; ?></span></td>
	<td style="width:20%;height:20%;"><span class='big_mid_symbol wb'><?php echo $currRumb[5]; ?></span></td>
</tr>
<tr>
	<td style="width:20%;height:20%;"><span class='big_mid_symbol wb'><?php echo $currRumb[10]; ?></span></td>
	<td style="width:20%;height:20%;"><span class='big_mid_symbol wb'><?php echo $currRumb[9]; ?></span></td>
	<td style="width:20%;height:20%;"><span class='big_mid_symbol wb'><?php echo $currRumb[8]; ?></span></td>
	<td style="width:20%;height:20%;"><span class='big_mid_symbol wb'><?php echo $currRumb[7]; ?></span></td>
	<td style="width:20%;height:20%;"><span class='big_mid_symbol wb'><?php echo $currRumb[6]; ?></span></td>
</tr>
</table>

<div style = '
	position:absolute;
	left: 0;
	right: 0;
	top: 5%;
	bottom: 0;
	margin: auto;
	width:70%;	
'>
	<div style='text-align:center;'>
		<span class='mid_symbol' style='vertical-align:middle; padding: 0; margin: 0;'>
			<?php echo $header;	?>
		</span>
	</div>
	<div id='dashboard' class='<?php if($alarm) echo "wb alarm";?>' style='text-align:center; padding: 0; margin: 0;'>
		<span class='big_symbol' style='vertical-align:middle;'>
			<?php echo $symbol;	?>
		</span>
	</div>
	<div style='text-align:center; bottom:0; padding: 0; margin: 0;'>
		<a href="<?php echo $_SERVER['PHP_SELF'];?>?magnetic=<?php echo $magneticTurn; ?>" style="text-decoration:none;">
			<button class='mid_symbol' style='width:14%;vertical-align:middle;' <?php if(empty($tpv['magtrack'])) echo 'disabled';?> >
				<div style="position:relative;<?php if(!$magnetic) echo "opacity:0.5;";?>">
				<?php if(!empty($tpv['magvar'])) echo "<div  class='small_symbol' style='position:absolute;text-align:center;'>$dashboardMagVarTXT</div><span style='font-size:75%;'>".round(@$tpv['magvar'])."</span>";	
					else echo "&#x1f9ed;";
				?>
				</div>
			</button>
		</a>
		<a href="<?php echo $_SERVER['PHP_SELF'];?>?mode=<?php echo $mode; ?>" style="text-decoration:none;">
			<button class='mid_symbol' style='width:70%;vertical-align:middle;'>
				<span style=''>
					<?php echo $nextsymbol;	?>
				</span>
			</button>
		</a>
		<a href="<?php echo $_SERVER['PHP_SELF'];?>?menu=<?php if(!$menu) echo '1';?>" style="text-decoration:none;">
			<button class='mid_symbol' style='width:14%;vertical-align:middle;'>
					&#9776;
			</button>
		</a>
	</div>
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
