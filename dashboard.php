<?php session_start();
/*
NW  326.25|NNW 348.75|N 11.25 |NNE  33.75|NE 56.25
WNW 303.75|          |        |          |ENE 78.75
W   281.25|          |        |          |E 101.25
WSW 258.75|          |        |          |ESE 123.75
SW  236.25|SSW 213.75|S 191.25|SSE 168.75|SE 146.25
*/
$versionTXT = '2.1.0';
/*
2.0.2 -- MOB info support
*/

require('params.php'); 	// пути и параметры
// Интернационализация
if(strpos($_SERVER['HTTP_ACCEPT_LANGUAGE'],'ru')===FALSE) { 	// клиент - нерусский
//if(TRUE) {
	$dashboardCourseTXT = 'Course';
	$dashboardHeadingTXT = 'Heading';
	$dashboardMagCourseTXT = 'Magnetic course';
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
	$dashboardToCourseAlarmTXT = 'The course is bad';
	$dashboardToHeadingAlarmTXT = 'The heading is bad';
	$dashboardKeysMenuTXT = 'Use keys to switch the screen mode';
	$dashboardKeySetupTXT = 'Select purpose and press key for:';
	$dashboardKeyNextTXT = 'Next mode';
	$dashboardKeyPrevTXT = 'Previous mode';
	$dashboardKeyMenuTXT = 'Alarm menu';
	$dashboardKeyMagneticTXT = 'Magnetic course';
	$dashboardMOBTXT = 'A man overboard!';
	$relBearingTXT = array(
'straight ahead',
'right ahead',
'to starboard',	
'right rear',
'directly astern',
'left rear',
'to port',	
'left ahead',
	);

}
else {
	$dashboardCourseTXT = 'Истинный путь';
	$dashboardHeadingTXT = 'Истинный курс';
	$dashboardMagCourseTXT = 'Магнитный путь';
	$dashboardMagHeadingTXT = 'Магнитный курс';
	$dashboardMagVarTXT = 'Склонение';
	$dashboardSpeedTXT = 'Скорость';
	$dashboardMinSpeedAlarmTXT = 'Скорость меньше допустимой';
	$dashboardMaxSpeedAlarmTXT = 'Скорость больше допустимой';
	$dashboardSpeedMesTXT = 'км/ч';
	$dashboardDepthTXT = 'Глубина';
	$dashboardDepthAlarmTXT = 'Слишком мелко';
	$dashboardDepthMesTXT = 'м';
	$dashboardGNSSoldTXT = 'Данные от приборов устарели';
	$dashboardDepthMenuTXT = 'Опасная глубина';
	$dashboardMinSpeedMenuTXT = 'Минимальная скорость';
	$dashboardMaxSpeedMenuTXT = 'Максимальная скорость';
	$dashboardToCourseAlarmTXT = 'Отклонение от пути';
	$dashboardToHeadingAlarmTXT = 'Отклонение от курса';
	$dashboardKeysMenuTXT = 'Используйте клавиши для смены режимов';
	$dashboardKeySetupTXT = 'Укажите назначение и нажмите клавишу для:';
	$dashboardKeyNextTXT = 'Следующий режим';
	$dashboardKeyPrevTXT = 'Предыдущий режим';
	$dashboardKeyMenuTXT = 'Меню оповещений';
	$dashboardKeyMagneticTXT = 'Магнитный путь';
	$dashboardMOBTXT = 'Человек за бортом!';
	$relBearingTXT = array(
'прямо по курсу',
'справа впереди',
'справа по борту',	
'справа сзади',
'сзади по корме',
'слева сзади',
'слева по борту',	
'слева впереди',
	);

}

// перечень типов данных из различных источников, которые требуется взять от gpsd
$dataTypes = array(  	//
'track', 	// путевой угол
'heading', 	// курс
'speed',	// скорость
'magtrack', 	// магнитный путевой угол
'mheading', 	// магнитный курс
'magvar', 	// магнитное склонение
'depth' 	// глубина
);
// типы данных, которые, собственно, будем показывать 
$displayData = array(  	// 
	'track' => array('variants' => [array('track',"$dashboardCourseTXT"),array('magtrack',"$dashboardMagCourseTXT")], 	// путь, магнитный путь
		'precision' => 0,
		'multiplicator' => 1
	),
	'heading' => array('variants' => [array('heading',"$dashboardHeadingTXT"),array('mheading',"$dashboardMagHeadingTXT")], 	// путь, магнитный путь
		'precision' => 0,
		'multiplicator' => 1
	),
	'speed' => array('variants' => [array('speed',"$dashboardSpeedTXT, $dashboardSpeedMesTXT")],	// скорость
		'precision' => 1,
		'multiplicator' => 60*60/1000
	),
	'depth' => array('variants' => [array('depth',"$dashboardDepthTXT, $dashboardDepthMesTXT")], 	// глубина
		'precision' => 1,
		'multiplicator' => 1
	)
);

$mode = $_REQUEST['mode'];
if(!$mode) $mode = $_SESSION['mode'];
if(!$mode) $mode = 'track';
$_SESSION['mode'] = $mode;	// перепишем теневое значение mode актуальным, раз такова воля юзера
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

	$toHeadingAlarm = $_REQUEST['toHeadingAlarm'];
	$toHeadingValue = $_REQUEST['toHeadingValue'];
	$toHeadingPrecision = $_REQUEST['toHeadingPrecision'];
	$toHeadingMagnetic = $magnetic;
	if(!$toHeadingValue) $toHeadingAlarm = FALSE;
	$_SESSION['toHeadingAlarm'] = $toHeadingAlarm;
	$_SESSION['toHeadingValue'] = $toHeadingValue;
	$_SESSION['toHeadingPrecision'] = $toHeadingPrecision;
	$_SESSION['toHeadingMagnetic'] = $toHeadingMagnetic;
}
else {
	$minDepthValue = $_SESSION['minDepthValue'];
	$depthAlarm = $_SESSION['depthAlarm'];

	$minSpeedAlarm = $_SESSION['minSpeedAlarm'];
	$minSpeedValue = $_SESSION['minSpeedValue'];

	$maxSpeedAlarm = $_SESSION['maxSpeedAlarm'];
	$maxSpeedValue = $_SESSION['maxSpeedValue'];

	$toHeadingAlarm = $_SESSION['toHeadingAlarm'];
	$toHeadingValue = $_SESSION['toHeadingValue'];
	$toHeadingPrecision = $_SESSION['toHeadingPrecision'];
	if(!$toHeadingPrecision) $toHeadingPrecision = 10;
	$toHeadingMagnetic = $_SESSION['toHeadingMagnetic'];
}
//echo "depthAlarm=$depthAlarm; minDepthValue=$minDepthValue; minSpeedAlarm=$minSpeedAlarm; minSpeedValue=$minSpeedValue; maxSpeedAlarm=$maxSpeedAlarm; maxSpeedValue=$maxSpeedValue;<br>\n";
//echo "toHeadingMagnetic=$toHeadingMagnetic;<br>\n";

if($gpsdProxyHost=='localhost' or $gpsdProxyHost=='127.0.0.1' or $gpsdProxyHost=='0.0.0.0') $gpsdProxyHost = $_SERVER['HTTP_HOST'];
//echo "$gpsdProxyHost:$gpsdProxyPort<br>\n";
list($tpv,$mob) = askGPSDproxy($gpsdProxyHost,$gpsdProxyPort); 	// требуемые данные в плоском массиве, MOB - своё положение, точка MOB; 
//echo "Ответ:<pre>"; print_r($tpv); print_r($mob); echo "</pre>";

if(is_string($tpv)) {
	$symbol = $tpv;
	goto DISPLAY;
}

$header = '';
// Оповещения в порядке возрастания опасности, реально сработает последнее
$alarm = FALSE;
if($minSpeedAlarm and ($tpv['speed']!==NULL)) {
	if($tpv['speed']*60*60/1000 <= $minSpeedValue) {
		$mode = 'speed';
		$header = $dashboardMinSpeedAlarmTXT;
		$alarmJS = 'minSpeedAlarmSound();';
		$alarm = TRUE;
	}
}
if($maxSpeedAlarm and ($tpv['speed']!==NULL)) {
	if($tpv['speed']*60*60/1000 >= $maxSpeedValue) {
		$mode = 'speed';
		$header = $dashboardMaxSpeedAlarmTXT;
		$alarmJS = 'maxSpeedAlarmSound();';
		$alarm = TRUE;
	}
}
if($toHeadingAlarm and !$mob) {
	if($toHeadingMagnetic and isset($tpv['magtrack'])) $theHeading = $tpv['magtrack'];
	else $theHeading = $tpv['track']; 	// тревога прозвучит, даже если был указан магнитный курс, но его нет
	$minHeading = $toHeadingValue - $toHeadingPrecision;
	if($minHeading<0) $minHeading = $minHeading+360;
	$maxHeading = $toHeadingValue + $toHeadingPrecision;
	if($maxHeading>=360) $maxHeading = $maxHeading-360;
	//echo "$minHeading<$theHeading>$maxHeading";
	if($theHeading < $minHeading or $theHeading > $maxHeading) {
		switch($mode){
		case 'track';
			$header = $dashboardToCourseAlarmTXT;
			break;
		case 'heading';
			$header = $dashboardToHeadingAlarmTXT;
			break;
		default:
			$mode = 'track';
			$header = $dashboardToCourseAlarmTXT;
		}
		$alarmJS = 'toHeadingAlarmSound();';
		$alarm = true;
	}
}
if($depthAlarm and ($tpv['depth']!==NULL)) {
	if($tpv['depth'] <= $minDepthValue) {
		$mode = 'depth';
		$header = $dashboardDepthAlarmTXT;
		$alarmJS = 'depthAlarmSound();';
		$alarm = TRUE;
	}
}

// Что будем рисовать
//echo "mode=$mode; magnetic=$magnetic;<br>\n";
//echo "TPV:<pre>"; print_r($tpv); echo "</pre>";
//echo"tpv['speed']=".$tpv['speed']."<br>\n";
$parms = array_keys($displayData);
$cnt = count($parms);
$cycle = null; $enough = false; $nextMode = null;
$prevMode = null; 

for($i=0;$i<$cnt;++$i){
	$type = $parms[$i];	// что показывать
	$parm = $displayData[$type];	// как показывать
	if(!$mode) $mode = $type; 	// что-то не так с типом, сделаем текущий тип указанным
	//echo "i=$i; type=$type; mode=$mode; enough=$enough;tpv[{$variantType}]={$tpv[$variantType]}<br>\n";
	//echo "parm:<pre>"; print_r($parm); echo "</pre>";
	//echo "displayData:<pre>"; print_r($displayData[$type]); echo "</pre>";
	if($enough) {
		$variant = 0;
		if($type == 'track' and $magnetic) $variant = 1;
		$variantType = $parm['variants'][$variant][0];
		if(! isset($tpv[$variantType])) { 	// но такого типа значения нет в полученных данных.
			if($i == $cnt-1) $i = -1; 	// цикл по кругу
			continue;
		}
		if($cycle === $variantType){ 	// прокрутили до ранее выбранного типа, но нечего показывать
			$nextsymbol = '';
			break;
		}
		//$nextsymbol = "<span style='font-size:75%;'>".$parm['variants'][$variant][1]."</span> &nbsp; ".round($tpv[$variantType]*$parm['multiplicator'],$parm['precision']);
		$nextsymbol = $parm['variants'][$variant][1].":&nbsp; ".round($tpv[$variantType]*$parm['multiplicator'],$parm['precision']);
		$nextMode = $type;
		break;
	}
	if($type != $mode) {  	// это не указанный тип 	текущее что показывать не то, что показывается на главном экране
		$prevMode = $type;	// запомним это как предыдущее что показывать, для управления клавишами
		continue;
	}
	// этот тип тот же, что и на экране, его надо показать
	$variant = 0;
	if($type == 'track' and $magnetic) $variant = 1;
	$variantType = $parm['variants'][$variant][0];
	if(! isset($tpv[$variantType])) { 	// но такого типа значения нет в полученных данных.
		$mode = null; 	// обозначим, что следующий тип должен стать указанным
		if($cycle === $variantType){ 	// прокрутили все типы, но нечего показывать
			$symbol = 'No data';	
			break;
		}
		if(!$cycle) $cycle = $variantType;	// запомним этот тип того, что нужно показывать для проверки зацикливания, если ничего не осталось показывать
		if($i == $cnt-1) $i = -1; 	// цикл по кругу
		continue;
	}
	if(!$header) $header = $parm['variants'][$variant][1];
	$symbol = round($tpv[$variantType]*$parm['multiplicator'],$parm['precision']);
	$enough = true;
	$cycle = $variantType;	// сдедующий тип будем искать по кругу до выбранного
	if($i == $cnt-1) $i = -1; 	// цикл по кругу
}
if(!$prevMode){
	$prevMode = $parms[$cnt-1];
}
//$_SESSION['mode'] = $mode;
//print "prevMode=$prevMode; nextMode=$nextMode;<br>\n";

$rumbNames = array('&nbsp;&nbsp;&nbsp;N&nbsp;&nbsp;&nbsp;','NNE','&nbsp;NE&nbsp;','ENE','&nbsp;&nbsp;E&nbsp;&nbsp;','ESE','&nbsp;SE&nbsp;','SSE','&nbsp;&nbsp;&nbsp;S&nbsp;&nbsp;&nbsp;','SSW','&nbsp;SW&nbsp;','WSW','&nbsp;&nbsp;W&nbsp;&nbsp;','WNW','&nbsp;NW&nbsp;','NNW');
if($toHeadingMagnetic and isset($tpv['magtrack'])) $theHeading = $tpv['magtrack'];
elseif(isset($tpv['track'])) $theHeading = $tpv['track']; 	// тревога прозвучит, даже если был указан магнитный курс, но его нет
else $theHeading = NULL;
if($theHeading !== NULL){
	$rumbNum = $theHeading;
	$rumbNum = round($rumbNum/22.5);
	if($rumbNum==16) $rumbNum = 0;
}
else $rumbNum = NULL;
//echo "{$tpv['track']};rumbNum=$rumbNum;{$rumbNames[$rumbNum]}<br>\n";
$currRumb = array();
$currRumb[$rumbNum] = $rumbNames[$rumbNum];

$MOBtxt = '';
if($mob) {
	$toHeadingAlarm = TRUE;
	$toHeadingValue = bearing($mob);
	//echo "Азимут на MOB $toHeadingValue, курс $theHeading<br>\n";
		
	$mobRumb = $toHeadingValue-$theHeading+22.5;
	if($mobRumb<0) $mobRumb = 360 + $mobRumb;
	$mobRumb = floor($mobRumb/45);
	if($mobRumb>7) $mobRumb = 0;
	$mobRumb = $relBearingTXT[$mobRumb];
	//echo "$mobRumb<br>\n";

	$MOBtxt = '<div style="position:absolute;left:1%;right:auto;top:15%;opacity: 0.3;"  class="big_mid_symbol wb"><span style="">'.$dashboardMOBTXT.'</span><br><span style="font-size:50%">'.$mobRumb.'</span></div>';
}

if($toHeadingAlarm) {

	//$toHeadingValue =30;
	// Метка указанного направления
	if(($toHeadingValue>315)and($toHeadingValue<360)){
		$percent = 100 - ($toHeadingValue - 313)*100/90;
		$currDirectMark = "<div style='display:block;position:fixed;top:0;right:$percent%;'><img src='img/markNNW.png' class='markVert'></div>";
	} 
	elseif($toHeadingValue == 0){
		$currDirectMark = "<div style='display:block;position:fixed;top:0;left:49.5%;'><img src='img/markN.png' class='markVert'></div>";
	}
	elseif(($toHeadingValue>0)and($toHeadingValue<45)){
		$percent = ($toHeadingValue+43)*100/90;
		$currDirectMark = "<div style='display: block;position: fixed;top:0;left:$percent%;width:3rem;height:3rem'><img src='img/markNNE.png' class='markVert'></div>";
	}
	elseif($toHeadingValue == 45){
		$currDirectMark = "<div style='display: block;position: fixed;top:0;right:0;'><img src='img/markNE.png' class='markVert'></div>";
	}
	elseif(($toHeadingValue > 45) and ($toHeadingValue < 90)){
		$percent = 100 - ($toHeadingValue-43)*100/90;
		$currDirectMark = "<div style='display: block;position: fixed;right:0;bottom:$percent%;'><img src='img/markENE.png' class='markHor'></div>";
	}
	elseif($toHeadingValue == 90){
		$currDirectMark = "<div style='display: block;position: fixed;right:0;top:49%;'><img src='img/markE.png' class='markHor'></div>";
	}
	elseif(($toHeadingValue > 90) and ($toHeadingValue < 135)){
		$percent = ($toHeadingValue-47)*100/90;
		$currDirectMark = "<div style='display: block;position: fixed;right:0;top:$percent%;'><img src='img/markESE.png' class='markHor'></div>";
	}
	elseif($toHeadingValue == 135){
		$currDirectMark = "<div style='display: block;position: fixed;bottom:0;right:0;'><img src='img/markSE.png' class='markHor'></div>";
	}
	elseif(($toHeadingValue>135)and($toHeadingValue<180)){
		$percent = 100 - ($toHeadingValue-133)*100/90;
		$currDirectMark = "<div style='display: block;position: fixed;bottom:0;left:$percent%;'><img src='img/markSSE.png' class='markVert'></div>";
	}
	elseif($toHeadingValue == 180){
		$currDirectMark = "<div style='display: block;position: fixed;bottom:0;left:49.5%;'><img src='img/markS.png' class='markVert'></div>";
	}
	elseif(($toHeadingValue>180)and($toHeadingValue<225)){
		$percent = ($toHeadingValue-137)*100/90;
		$currDirectMark = "<div style='display: block;position: fixed;bottom:0;right:$percent%;'><img src='img/markSSW.png' class='markVert'></div>";
	}
	elseif($toHeadingValue==225){
		$currDirectMark = "<div style='display: block;position: fixed;bottom:0;left:0;'><img src='img/markSW.png' class='markHor'></div>";
	}
	elseif(($toHeadingValue>225)and($toHeadingValue<270)){
		$percent = 100 - ($toHeadingValue-223)*100/90;
		$currDirectMark = "<div style='display:block;position:fixed;left:0;top:$percent%;'><img src='img/markWSW.png' class='markHor'></div>";
	}
	elseif($toHeadingValue == 270){
		$currDirectMark = "<div style='display: block;position: fixed;left:0;top:49%;'><img src='img/markW.png' class='markHor'></div>";
	}
	elseif(($toHeadingValue>270)and($toHeadingValue<315)){
		$percent = ($toHeadingValue-227)*100/90;
		$currDirectMark = "<div style='display:block;position:fixed;left:0;bottom:$percent%;'><img src='img/markWNW.png' class='markHor'></div>";
	}
	elseif($toHeadingValue==315){
		$currDirectMark = "<div style='display: block;position: absolute;top:0;left:0;'><img src='img/markNW.png' class='markHor'></div>";
	}
	// Метка текущего направления 	$theHeading уже есть
	if(($theHeading>315)and($theHeading<=360)){
		$percent = 100 - ($theHeading - 315)*100/90;
		$currTrackMark = "<img src='img/markCurrN.png' style='display:block;position:fixed;top:0;right:$percent%;' class='vert'>";
	} 
	elseif(($theHeading>=0)and($theHeading<45)){
		$percent = ($theHeading+45)*100/90;
		$currTrackMark = "<img src='img/markCurrN.png' style='display: block;position: fixed;top:0;left:$percent%;' class='vert'>";
	}
	elseif($theHeading == 45){
		$currTrackMark = "<img src='img/markCurrSE.png' style='display: block;position: fixed;top:0;right:0;' class='vert'>";
	}
	elseif(($theHeading > 45) and ($theHeading < 135)){
		$percent = 100 - ($theHeading-45)*100/90;
		$currTrackMark = "<img src='img/markCurrE.png' style='display: block;position: fixed;right:0;bottom:$percent%;' class='hor'>";
	}
	elseif($theHeading == 135){
		$currTrackMark = "<img src='img/markCurrNE.png' style='display: block;position: fixed;bottom:0;right:0;' class='vert'>";
	}
	elseif(($theHeading>135)and($theHeading<225)){
		$percent = 100 - ($theHeading-135)*100/90;
		$currTrackMark = "<img src='img/markCurrN.png' style='display: block;position: fixed;bottom:0;left:$percent%;' class='vert'>";
	}
	elseif($theHeading==225){
		$currTrackMark = "<img src='img/markCurrNE.png' style='display: block;position: fixed;bottom:0;left:0;' class='vert'>";
	}
	elseif(($theHeading>225)and($theHeading<315)){
		$percent = 100 - ($theHeading-225)*100/90;
		$currTrackMark = "<img src='img/markCurrE.png' style='display:block;position:fixed;left:0;top:$percent%;' class='hor'>";
	}
	elseif($theHeading==315){
		$currTrackMark = "<img src='img/markCurrNE.png' style='display: block;position: absolute;top:0;left:0;' class='vert'>";
	}
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
	<?php if(!$menu) echo "<meta http-equiv='refresh' content='2; url={$_SERVER['PHP_SELF']}'>\n";?>
	<script src="dashboard.js">	</script>
	<?php if($alarm) echo "<script>$alarmJS</script>";?>
	<link rel="stylesheet" href="dashboard.css" type="text/css"> 
   <title>Dashboard <?php echo $versionTXT;?></title>
</head>
<body style="margin:0; padding:0;">
<?php /* ?>
<div id='infoBox' style='font-size: 90%; position: absolute;'>
</div>
<script>
//alert(window.outerWidth+' '+window.outerHeight);
infoBox.innerText='width: '+window.outerWidth+' height: '+window.outerHeight;
</script>
<?php */ ?>
<?php echo "$currTrackMark $currDirectMark"; // указателb заданного и текущего курса ?>

<script>
var controlKeys = getCookie('GaladrielMapDashboardControlKeys');
if(controlKeys) {
	controlKeys = JSON.parse(controlKeys);
}
else {
	controlKeys = {
		'upKey': ['ArrowUp',38],
		'downKey': ['ArrowDown',40],
		'menuKey': ['AltRight',18,2],
		'magneticKey': ['KeyM',77]
	}
}
//console.log('controlKeys before',controlKeys);

window.addEventListener("keydown", keySu, true);  

function keySu(event) {
if (event.defaultPrevented) {
	return; // Should do nothing if the default action has been cancelled
}

var handled = false;
if (event.code !== undefined) {
	if(controlKeys.upKey.indexOf(event.code) != -1) handled = 'up';
	else if(controlKeys.downKey.indexOf(event.code) != -1) handled = 'down';
	else if(controlKeys.menuKey.indexOf(event.code) != -1) handled = 'menu';
	else if(controlKeys.magneticKey.indexOf(event.code) != -1) handled = 'magnetic';
}
else if (event.keyCode !== undefined) { // Handle the event with KeyboardEvent.keyCode and set handled true.
	if(controlKeys.upKey.indexOf(event.keyCode) != -1) handled = 'up';
	else if(controlKeys.downKey.indexOf(event.keyCode) != -1) handled = 'down';
	else if(controlKeys.menuKey.indexOf(event.keyCode) != -1) handled = 'menu';
	else if(controlKeys.magneticKey.indexOf(event.keyCode) != -1) handled = 'magnetic';
}
else if (event.location != 0) { // 
	if(controlKeys.upKey.indexOf(event.location) != -1) handled = 'up';
	else if(controlKeys.downKey.indexOf(event.location) != -1) handled = 'down';
	else if(controlKeys.menuKey.indexOf(event.location) != -1) handled = 'menu';
	else if(controlKeys.magneticKey.indexOf(event.location) != -1) handled = 'magnetic';
}

if (handled) {
	event.preventDefault(); // Suppress "double action" if event handled
	switch(handled){
	case 'down':
		//alert(handled);
		window.location.href = '<?php echo $_SERVER['PHP_SELF'];?>?mode=<?php echo $nextMode; ?>';
		break;
	case 'up':
		//alert(handled);
		window.location.href = '<?php echo $_SERVER['PHP_SELF'];?>?mode=<?php echo $prevMode; ?>';
		break;
	case 'menu':
		//alert(handled);
		window.location.href = '<?php echo $_SERVER['PHP_SELF'];?>?menu=<?php if(!$menu) echo '1';?>';
		break;
	case 'magnetic':
		//alert(handled);
		window.location.href = '<?php echo $_SERVER['PHP_SELF'];?>?magnetic=<?php echo $magneticTurn;?>';
		break;
	}
}
} // end function keySu

function getCookie(name) {
// возвращает cookie с именем name, если есть, если нет, то undefined
name=name.trim();
var matches = document.cookie.match(new RegExp(
	"(?:^|; )" + name.replace(/([\.$?*|{}\(\)\[\]\\\/\+^])/g, '\\$1') + "=([^;]*)"
	)
);
//console.log('matches',matches);
return matches ? decodeURIComponent(matches[1]) : undefined;
}

</script>

<?php if($menu) { ?>
<form action='<?php echo $_SERVER['PHP_SELF'];?>' style = '
	position:fixed;
	right: 5%;
	top: 5%;
	width:75%;
	background-color:lightgrey;
	padding: 1em;
	font-size: xx-large;
	z-index: 10;
'>
	<table>
		<tr style='height:2em;'>
			<td ><input type='checkbox' name='depthAlarm' value='1' <?php if($depthAlarm) echo 'checked';?> style='height:3em;width:3em;'></td>
			<td><?php echo "$dashboardDepthMenuTXT, $dashboardDepthMesTXT"?></td>
			<td style='width:15%;'><input type='text' name=minDepthValue value='<?php echo $minDepthValue?>' style='width:95%;font-size:inherit;'></td>
		</tr><tr style='height:2em;'>
			<td><input type='checkbox' name='minSpeedAlarm' value='1' <?php if($minSpeedAlarm) echo 'checked';?> style='height:3em;width:3em;'></td>
			<td><?php echo "$dashboardMinSpeedMenuTXT, $dashboardSpeedMesTXT"?></td>
			<td style='width:15%;'><input type='text' name=minSpeedValue value='<?php echo $minSpeedValue?>' style='width:95%;font-size:inherit;'></td>
		</tr><tr style='height:2em;'>
			<td><input type='checkbox' name='maxSpeedAlarm' value='1' <?php if($maxSpeedAlarm) echo 'checked';?> style='height:3em;width:3em;'></td>
			<td><?php echo "$dashboardMaxSpeedMenuTXT, $dashboardSpeedMesTXT"?></td>
			<td style='width:15%;'><input type='text' name=maxSpeedValue value='<?php echo $maxSpeedValue?>' style='width:95%;font-size:inherit;'></td>
		</tr><tr style='height:2em;'>
			<td><input type='checkbox' name='toHeadingAlarm' value='1' <?php if($toHeadingAlarm) echo 'checked';?> style='height:3em;width:3em;'></td>
			<td><?php
					//echo "<br> mode=$mode; displayData[mode]['variants']:<pre>"; print_r($displayData[$mode]['variants'][1]); echo "</pre>";
					if($magnetic) {
						if($toHeadingAlarm) {	// имеется режим опасности
							if($toHeadingMagnetic) {	// ранее был установлен
								switch($mode){
								case 'track':
								case 'heading':
									echo $displayData[$mode]['variants'][1][1] ;
									break;
								default:
									echo $displayData['track']['variants'][1][1] ;
								}
							}
							else {
								switch($mode){
								case 'track':
								case 'heading':
									echo $displayData[$mode]['variants'][0][1] ;
									break;
								default:
									echo $displayData['track']['variants'][0][1] ;
								}
							}
						}
						else {
							switch($mode){
							case 'track':
							case 'heading':
								echo $displayData[$mode]['variants'][1][1] ;
								break;
							default:
								echo $displayData['track']['variants'][1][1] ;
							}
						}
					}
					else {
						if($toHeadingAlarm) {
							if($toHeadingMagnetic) {
								switch($mode){
								case 'track':
								case 'heading':
									echo $displayData[$mode]['variants'][1][1] ;
									break;
								default:
									echo $displayData['track']['variants'][1][1] ;
								}
							}
							else {
								switch($mode){
								case 'track':
								case 'heading':
									echo $displayData[$mode]['variants'][0][1] ;
									break;
								default:
									echo $displayData['track']['variants'][0][1] ;
								}
							}
						}
						else {
							switch($mode){
							case 'track':
							case 'heading':
								echo $displayData[$mode]['variants'][0][1] ;
								break;
							default:
								echo $displayData['track']['variants'][0][1] ;
							}
						}
					}
					?><br> &nbsp; 
			<input type='radio' name='toHeadingPrecision' value='10' <?php if($toHeadingPrecision == 10) echo 'checked';?> style='height:2em;width:2em;'> &plusmn; 10&deg; &nbsp; 
			<input type='radio' name='toHeadingPrecision' value='20' <?php if($toHeadingPrecision == 20) echo 'checked';?> style='height:2em;width:2em;'> &plusmn; 20&deg;
			<td style='width:15%;'><input type='text' name=toHeadingValue value='<?php if($magnetic){ 
																							if($toHeadingAlarm) echo $toHeadingValue; 
																							else echo round($tpv['magtrack']);
																						}
																						else { 
																							if($toHeadingAlarm) echo $toHeadingValue;
																							else echo round($tpv['track']);
																						}?>' style='width:95%;font-size:inherit;'></td>
		</tr><tr>
			<td></td><td style='padding-top:1em;'><a href='<?php echo $_SERVER['PHP_SELF'];?>' style='text-decoration:none;'><input type='button' value='&nbsp;&nbsp;&#x2718;&nbsp;&nbsp;' style='font-size:130%;'></a><input type='submit' name='submit' value='&nbsp;&nbsp;&#x2713;&nbsp;&nbsp;' style='font-size:130%;float:right;'></td><td></td>
		</tr>
	</table>
	<div id='jsKeys'>
	</div>
</form>
<?php } ?>
<table  style='
	width:100%; 
	height:100%; 
	position:fixed; 
	margin:0; padding:0;
	text-align:center;
	z-index: -1;
'>
<tr>
	<td style="width:20%;height:20%;"><span class='big_mid_symbol wb' style="opacity: 0.3;"><?php echo $currRumb[14]; ?></span></td>
	<td style="width:20%;height:20%;"><span class='big_mid_symbol wb' style="opacity: 0.3;"><?php echo $currRumb[15]; ?></span></td>
	<td style="width:20%;height:20%;"><span class='big_mid_symbol wb' style="opacity: 0.3;"><?php echo $currRumb[0]; ?></span></td>
	<td style="width:20%;height:20%;"><span class='big_mid_symbol wb' style="opacity: 0.3;"><?php echo $currRumb[1]; ?></span></td>
	<td style="width:20%;height:20%;"><span class='big_mid_symbol wb' style="opacity: 0.3;"><?php echo $currRumb[2]; ?></span></td>
</tr>
<tr>
	<td style="width:20%;height:20%;"><span class='big_mid_symbol wb' style="opacity: 0.3;"><?php echo $currRumb[13]; ?></span></td>
	<td rowspan="3" colspan="3"></td>
	<td style="width:20%;height:20%;"><span class='big_mid_symbol wb' style="opacity: 0.3;"><?php echo $currRumb[3]; ?></span></td>
</tr>
<tr>
	<td style="width:20%;height:20%;"><span class='big_mid_symbol wb' style="opacity: 0.3;"><?php echo $currRumb[12]; ?></span></td>
	<td style="width:20%;height:20%;"><span class='big_mid_symbol wb' style="opacity: 0.3;"><?php echo $currRumb[4]; ?></span></td>
</tr>
<tr>
	<td style="width:20%;height:20%;"><span class='big_mid_symbol wb' style="opacity: 0.3;"><?php echo $currRumb[11]; ?></span></td>
	<td style="width:20%;height:20%;"><span class='big_mid_symbol wb' style="opacity: 0.3;"><?php echo $currRumb[5]; ?></span></td>
</tr>
<tr>
	<td style="width:20%;height:20%;"><span class='big_mid_symbol wb' style="opacity: 0.3;"><?php echo $currRumb[10]; ?></span></td>
	<td style="width:20%;height:20%;"><span class='big_mid_symbol wb' style="opacity: 0.3;"><?php echo $currRumb[9]; ?></span></td>
	<td style="width:20%;height:20%;"><span class='big_mid_symbol wb' style="opacity: 0.3;"><?php echo $currRumb[8]; ?></span></td>
	<td style="width:20%;height:20%;"><span class='big_mid_symbol wb' style="opacity: 0.3;"><?php echo $currRumb[7]; ?></span></td>
	<td style="width:20%;height:20%;"><span class='big_mid_symbol wb' style="opacity: 0.3;"><?php echo $currRumb[6]; ?></span></td>
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
		<?php echo $MOBtxt; ?>
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
				<?php if(!empty($tpv['magvar'])) echo "\t<div  class='small_symbol' style='position:absolute;text-align:center;'>\n\t\t\t\t\t\t$dashboardMagVarTXT\n\t\t\t\t\t</div>\n\t\t\t\t\t<span style='font-size:75%;'>".round(@$tpv['magvar'])."</span>\n";	
					else echo "<img src='img/compass.png' alt='magnetic course'>";
				?>
				</div>
			</button>
		</a>
		<a href="<?php echo $_SERVER['PHP_SELF'];?>?mode=<?php echo $nextMode; ?>" style="text-decoration:none;">
			<button class='mid_symbol' style='width:70%;vertical-align:middle;'>
					<?php echo "$nextsymbol\n";	?>
			</button>
		</a>
		<a href="<?php echo $_SERVER['PHP_SELF'];?>?menu=<?php if(!$menu) echo '1';?>" style="text-decoration:none;">
			<button class='mid_symbol' style='width:14%;vertical-align:middle;'>
					<img src='img/menu.png' alt='menu'>
			</button>
		</a>
	</div>
</div>
<?php if($menu) { ?>
<div id='setKeysWin' style="
display:none;
position:fixed;
right: 20%;
top: 20%;
width:55%;
background-color:grey;
padding: 1em;
font-size: xx-large;
z-index: 20;
margin-left: auto;
margin-right: auto;
font-size:x-large;
">
<?php echo $dashboardKeySetupTXT;?><br>
<div  style="width:90%;margin:0 auto 0 auto;">
	<table>
	<tr>
	<td style="width:60%;"><?php echo $dashboardKeyNextTXT;?></td>
	<td><input type="radio" name="setKeysSelect" id="downKeyField" onClick="this.value='';downKeyFieldDisplay.innerHTML='';keyCodes.downKey=[];"></td>
	<td style="width:40%;font-size:120%;background-color:white"><span id='downKeyFieldDisplay'></span></td>
	</tr><tr>
	<td style="width:60%;"><?php echo $dashboardKeyPrevTXT;?></td>
	<td><input type="radio" name="setKeysSelect" id="upKeyField" onClick="this.value='';upKeyFieldDisplay.innerHTML='';keyCodes.upKey=[];" ></td>
	<td style="width:40%;font-size:120%;background-color:white"><span id='upKeyFieldDisplay'></span></td>
	</tr><tr>
	<td style="width:60%;"><?php echo $dashboardKeyMenuTXT;?></td>
	<td><input type="radio" name="setKeysSelect" id="menuKeyField" onClick="this.value='';menuKeyFieldDisplay.innerHTML='';keyCodes.menuKey=[];""></td>
	<td style="width:40%;font-size:120%;background-color:white"><span id='menuKeyFieldDisplay'></span></td>
	</tr><tr>
	<td style="width:60%;"><?php echo $dashboardKeyMagneticTXT;?></td>
	<td><input type="radio" name="setKeysSelect" id="magneticKeyField" onClick="this.value='';magneticKeyFieldDisplay.innerHTML='';keyCodes.magneticKey=[];""></td>
	<td style="width:40%;font-size:120%;background-color:white"><span id='magneticKeyFieldDisplay'></span></td>
	</tr>
	</table>
</div>
<div style="width:70%;margin:1em auto 1em auto;">
	<input type='button' value='&#x2718;' style='font-size:120%;' onClick="openSetKeysWin();" ><input type='submit' name='submit' value='&#x2713;' onClick="saveKeys();" style='font-size:120%;float:right;'>
</div>
</div>
<script>
var keyCodes = {};
function jsTest() {
var html = '<div style="width:100%;text-align:right;">';
html += '<span style="font-size:50%;"><?php echo $dashboardKeysMenuTXT;?> </span>';
html += ' &nbsp; <a href="#" onClick="openSetKeysWin();" ><img src="img/settings.png" alt="define keys" class="small"></a></div>';
jsKeys.innerHTML = html;
} // end function jsTest

function openSetKeysWin() {
/**/
//console.log(controlKeys);
if(setKeysWin.style.display == 'none'){
	window.removeEventListener("keydown", keySu, true);  
	if(controlKeys.upKey) {
		if(controlKeys.upKey.length) {
			upKeyField.value = controlKeys.upKey[0];
			upKeyFieldDisplay.innerHTML = controlKeys.upKey[0]?controlKeys.upKey[0]:'some key';
		}
		else {
			upKeyField.value = null;
			upKeyFieldDisplay.innerHTML = '';
		}
	}
	if(controlKeys.downKey){
		if(controlKeys.downKey.length) {
			downKeyField.value = controlKeys.downKey[0];
			downKeyFieldDisplay.innerHTML = controlKeys.downKey[0]?controlKeys.downKey[0]:'some key';
		}
		else {
			downKeyField.value = null;
			downKeyFieldDisplay.innerHTML = '';
		}
	}
	if(controlKeys.menuKey){
		if(controlKeys.menuKey.length) {
			menuKeyField.value = controlKeys.menuKey[0];
			menuKeyFieldDisplay.innerHTML = controlKeys.menuKey[0]?controlKeys.menuKey[0]:'some key';
		}
		else {
			menuKeyField.value = null;
			menuKeyFieldDisplay.innerHTML = '';
		}
	}
	if(controlKeys.magneticKey){
		if(controlKeys.magneticKey.length) {
			magneticKeyField.value = controlKeys.magneticKey[0];
			magneticKeyFieldDisplay.innerHTML = controlKeys.magneticKey[0]?controlKeys.magneticKey[0]:'some key';
		}
		else {
			magneticKeyField.value = null;
			magneticKeyFieldDisplay.innerHTML = '';
		}
	}
	window.addEventListener("keydown", setKeys, true);  // В читалке Sony можно назначить listener только на window 
	setKeysWin.style.display = 'initial';
}
else {
	setKeysWin.style.display = 'none';
	window.addEventListener("keydown", keySu, true);  
}
} // end function openSetKeysWin()

function setKeys(event) {
/*  */
//console.log(event);
if(event.code == 'Tab' || event.code == 'Esc' || event.code == 'Home') return;
//alert(event.code+','+event.keyCode+','+event.key+','+event.charCode+','+event.location)
event.preventDefault();
//alert(event.code+','+event.keyCode+','+event.key+','+event.charCode+','+event.location);
var keyCode;
if(event.code) keyCode = event.code;
else keyCode = 'some key';
//alert(typeof event.target.id);
if(event.target.id == 'upKeyField') {
	keyCodes['upKey'] = [event.code,event.keyCode,event.key,event.charCode,event.location]
	upKeyFieldDisplay.innerHTML = keyCode;
}
else if(event.target.id == 'downKeyField') {
	keyCodes['downKey'] = [event.code,event.keyCode,event.key,event.charCode,event.location]
	downKeyFieldDisplay.innerHTML = keyCode;
}
else if(event.target.id == 'menuKeyField') {
	keyCodes['menuKey'] = [event.code,event.keyCode,event.key,event.charCode,event.location]
	menuKeyFieldDisplay.innerHTML = keyCode;
}
else if(event.target.id == 'magneticKeyField') {
	keyCodes['magneticKey'] = [event.code,event.keyCode,event.key,event.charCode,event.location]
	magneticKeyFieldDisplay.innerHTML = keyCode;
}
else if(event.target.id == '') {
	keyCodes['downKey'] = [event.code,event.keyCode,event.key,event.charCode,event.location]
	downKeyFieldDisplay.innerHTML = keyCode;
}
//console.log('keyCodes',keyCodes);
} // end function setKeys()

function saveKeys(){
for(var type in keyCodes){
	controlKeys[type] = keyCodes[type];
}
//console.log(controlKeys);
keyCodes = JSON.stringify(controlKeys);
var date = new Date(new Date().getTime()+1000*60*60*24*365).toGMTString();
//alert(keyCodes);
//document.cookie = 'GaladrielMapDashboardControlKeys='+encodeURIComponent(keyCodes)+'; expires='+date+';';
document.cookie = 'GaladrielMapDashboardControlKeys='+keyCodes+'; expires='+date+';';
setKeysWin.style.display = 'none';
} // end function saveKeys

jsTest();
</script>
<?php }; ?>
</body>
</html>
<?php

function askGPSDproxy($host='localhost',$port=3838){
/*
В $gpsdData данные по устройствам, в результирующем массиве - без конкретного устройства
*/
global $dataTypes;

$gpsd  = @stream_socket_client('tcp://'.$host.':'.$port,$errno,$errstr); // открыть сокет 
$res = @fwrite($gpsd, "\n\n"); 	// gpsdPROXY не пришлёт VERSION при открытии соединения
//echo "res=$res; ";var_dump($gpsd);echo "<br>\n";
if(($res === FALSE) or !$gpsd) return "no GPSD: $errstr";
//echo "Socket to gpsd opened, handshaking<br>\n";
$controlClasses = array('VERSION','DEVICES','DEVICE','WATCH');
do { 	//
	$buf = fgets($gpsd); 
	//echo "<br>buf:<br>|".strtr($buf,"\r\n",'?!')."|<br>\n";
	if($buf === FALSE) { 	// gpsd умер
		@socket_close($gpsd);
		$msg = "Failed to read data from gpsdPROXY";
		echo "$msg<br>\n"; 
		return $msg;
	}
	if (!$buf = trim($buf)) {	// пусто -- это второй \r\n в конце строки. Но пустая строка -- как бы принятое в http завершение сообщения?
		continue;
	}
	$buf = json_decode($buf,TRUE);
	if($buf === null) { 	// прислали странное, это не gpsd?
		@socket_close($gpsd);
		$msg = "Recieved not JSON. Is this cgpsdPROXY?";
		echo "$msg<br>\n"; 
		return $msg;
	}
	//echo "<br>buf: ";echo "<pre>"; print_r($buf); echo "</pre>\n";
	switch($buf['class']){
	case 'VERSION': 	// 
		$res = fwrite($gpsd, '?WATCH={"enable":true};'."\n\n"); 	// велим демону включить устройства
		if($res === FALSE) { 	// gpsd умер
			socket_close($gpsd);
			$msg =  "Failed to send WATCH to gpsdPROXY: $errstr";
			echo "$msg<br>\n"; 
			return $msg;
		}
		//echo "Send TURN ON<br>\n";
		break;
	case 'DEVICES': 	// соберём подключенные устройства со всех gpsd, включая slave
		//echo "Received DEVICES<br>\n"; //
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
		//echo "Sending POLL<br>\n";
		$res = fwrite($gpsd, '?POLL={"subscribe":""TPV""};'."\n\n"); 	// запросим данные
		if($res === FALSE) { 	// gpsd умер
			socket_close($gpsd);
			$msg =  "Failed to send POLL to gpsdPROXY: $errstr";
			echo "$msg<br>\n"; 
			return $msg;
		}
		break;
	}
}while(!$buf or in_array($buf['class'],$controlClasses));
@fwrite($gpsd, '?WATCH={"enable":false};'."\n\n"); 	// велим демону выключить устройства
fclose($gpsd);
//echo "Закрыт сокет\n";
//echo "Все полученные от gpsdPROXY данные:<pre>"; print_r($buf); echo "</pre>";
$gpsdData = array();
foreach($buf['tpv'] as $device) {
	//echo "<br>device=<pre>"; print_r($device); echo "</pre>\n";
	if($device['time'])	$gpsdData[$device['time']] = $device; 	// с ключём - время
	else {
		$gpsdData[] = $device; 	// с ключём  - целым.
	}
	//echo "<br>device=<pre>"; print_r($device); echo "</pre>\n";
}
//echo "Данные askGPSD <pre>"; print_r($gpsdData); echo "</pre>\n";

$tpv = array();
$selfLonLat = array();	// будет использоваться для MOB и на всякий случай
krsort($gpsdData); 	// отсортируем устройства по времени к прошлому
foreach($gpsdData as $device) {
	//echo "device=<pre>"; print_r($device); echo "</pre>\n";
	if(is_numeric($device['lon']) and is_numeric($device['lat']))	$selfLonLat = array($device['lon'],$device['lat']);
	foreach($dataTypes as $data) {	// выберем то, что указано в $dataTypes
		if($device[$data]!==NULL) $tpv[$data] = (float)$device[$data];
	}
	//echo "<br>tpv=<pre>"; print_r($tpv); echo "</pre>\n";
	if($device['mode'] == 3) { 	// последний по времени 3D fix 
		// считаем, что это более достоверно
		if(array_key_exists('track',$device)) $tpv['track'] = $device['track']; 	// путь, без явного преобразования типов, чтобы остался NULL
		if(array_key_exists('magtrack',$device)) $tpv['magtrack'] = $device['magtrack']; 	// магнитный путь
		if(array_key_exists('heading',$device)) $tpv['heading'] = $device['heading']; 	// курс
		if(array_key_exists('mheading',$device)) $tpv['mheading'] = $device['mheading']; 	// магнитный курс
		if(array_key_exists('speed',$device)) $tpv['speed'] = $device['speed']; 	// скорость
	}
	//echo "device['mode']={$device['mode']}<br>tpv=<pre>"; print_r($tpv); echo "</pre>\n";
	$enough = TRUE;
	foreach($dataTypes as $data) {	// проверяем, всё ли типы данных, что указаны в $dataTypes, есть в $tpv
		if(!($enough = ($enough and $tpv[$data]))) break;	// если все $dataTypes есть, цикл прокрутится, и $enough останется TRUE. Иначе цикл обломится с $enough FALSE, и устройства будут просматириваться дальше
	}
	if($enough) break; 	// прекратим просмотр устройств, если собрали все данные
}

// MOB
$mob = array();
if($buf['mob']['status']){
	foreach($buf['mob']['points'] as $point){
		//echo "<pre>"; print_r($point); echo "</pre>";
		if($point['current']){
			$mob = array($selfLonLat,$point['coordinates']);	// своё положение, точка MOB; долгота широта, lon lat
			break;
		}
	}
}

unset($gpsdData);
return array($tpv,$mob);
} // end function askGPSDproxy

function bearing($pair) {
/* Азимут между точками
$pair = array(array($lon,$lat),array($lon,$lat))
*/
//echo "<pre>"; print_r($pair); echo "</pre>";
$lat1 = deg2rad($pair[0][1]);
$lat2 = deg2rad($pair[1][1]);
$lon1 = deg2rad($pair[0][0]);
$lon2 = deg2rad($pair[1][0]);
//echo "lat1=$lat1; lat2=$lat2; lon1=$lon1; lon2=$lon2;<br>\n";

$y = sin($lon2 - $lon1) * cos($lat2);
$x = cos($lat1) * sin($lat2) - sin($lat1) * cos($lat2) * cos($lon2 - $lon1);
//echo "x=$x; y=$y;<br>\n";

$bearing = rad2deg(atan2($y, $x));
//echo "$bearing<br>";
if($bearing >= 360) $bearing = $bearing-360;
elseif($bearing < 0) $bearing = $bearing+360;

return $bearing;
} // end function bearing

?>
