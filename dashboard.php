<?php session_start();
/*
NW  326.25|NNW 348.75|N 11.25 |NNE  33.75|NE 56.25
WNW 303.75|          |        |          |ENE 78.75
W   281.25|          |        |          |E 101.25
WSW 258.75|          |        |          |ESE 123.75
SW  236.25|SSW 213.75|S 191.25|SSE 168.75|SE 146.25
*/
$versionTXT = '1.4.0';
require_once('fGPSD.php'); // fGPSD.php 

include('params.php'); 	// пути и параметры
// Интернационализация
if(strpos($_SERVER['HTTP_ACCEPT_LANGUAGE'],'ru')===FALSE) { 	// клиент - нерусский
//if(TRUE) {
	$dashboardHeadingTXT = 'Course';
	$dashboardMagHeadingTXT = 'Magnetic course';
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
	$dashboardToHeadingAlarmTXT = 'The course is bad';
	$dashboardKeysMenuTXT = 'Use keys to switch the screen mode';
	$dashboardKeySetupTXT = 'Select purpose and press key for:';
	$dashboardKeyNextTXT = 'Next mode';
	$dashboardKeyPrevTXT = 'Previous mode';
	$dashboardKeyMenuTXT = 'Alarm menu';
	$dashboardKeyMagneticTXT = 'Magnetic course';
}
else {
	$dashboardHeadingTXT = 'Истинный курс'; 	//  хотя это "путевой угол", "путь"
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
	$dashboardToHeadingAlarmTXT = 'Отклонение от курса';
	$dashboardKeysMenuTXT = 'Используйте клавиши для смены режимов';
	$dashboardKeySetupTXT = 'Укажите назначение и нажмите клавишу для:';
	$dashboardKeyNextTXT = 'Следующий режим';
	$dashboardKeyPrevTXT = 'Предыдущий режим';
	$dashboardKeyMenuTXT = 'Меню оповещений';
	$dashboardKeyMagneticTXT = 'Магнитный курс';
}

// перечень типов данных из различных источников, которые требуется взять от gpsd
$dataTypes = array(  	// время в секундах после последнего обновления, после которого считается, что данные протухли. Поскольку мы спрашиваем gpsd POLL, легко не увидеть редко передаваемые данные
'track' => 15, 	// курс
'speed' => 10,	// скорость
'magtrack' => 15, 	// магнитный курс
'magvar' => 3600, 	// магнитное склонение
'depth' => 10 	// глубина
);
// типы данных, которые, собственно, будем показывать 
$displayData = array(  	// 
	'track' => array('variants' => [array('track',"$dashboardHeadingTXT"),array('magtrack',"$dashboardMagHeadingTXT")], 	// курс, магнитный курс
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
//$dataFullOld = 20; 	// период в секундах от даты данных, после которого считается, что данные протухли. Если источников данных много, то от даты самого свежего, так что некоторые данные могут быть очень старыми.
$dataFullOld = 0; 	// считаем, что данные всегда свежие

$mode = $_REQUEST['mode'];
if(!$mode) $mode = $_SESSION['mode'];
if(!$mode) $mode = 'track';
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
$tpv = askGPSD($gpsdHost,$gpsdPort); 	// исходные данные
//echo "Ответ:<pre>"; print_r($tpv); echo "</pre>";
if(is_string($tpv)) {
	$symbol = $tpv;
	goto DISPLAY;
}
$tpv = getData('tpv',$tpv,$dataTypes); 	// требуемые данные в плоском массиве
/////////////////////////////
//$tpv['track'] = 11;
/////////////////////////////
if($tpv['time']) { 	// иначе пусто преобразуется в очень давно
	$gnssTime = new DateTime($tpv['time'],new DateTimeZone('UTC')); 	// объект, время в указанной TZ, или по грнвичу, если не
	$gnssTime = $gnssTime->getTimestamp(); 	// число, unix timestamp - он вне часовых поясов

	if($dataFullOld and ((time()-$gnssTime)>$dataFullOld)) {
		$symbol = $dashboardGNSSoldTXT;	// данные устарели более, чем на секунд 
		goto DISPLAY;
	}
}
//else {
//	$symbol = $dashboardGNSSoldTXT;	// данные ГПС устарели
//	goto DISPLAY;
//}

$header = '';
// Оповещения в порядке возрастания опасности, реально сработает последнее
$alarm = FALSE;
if($minSpeedAlarm and ($tpv['speed']!==NULL)) {
	if($tpv['speed']*60*60/1000 <= $minSpeedValue) {
		$mode = 'speed';
		$header = $dashboardMinSpeedAlarmTXT;
		$alarmJS = 'minSpeedAlarm();';
		$alarm = TRUE;
	}
}
if($maxSpeedAlarm and ($tpv['speed']!==NULL)) {
	if($tpv['speed']*60*60/1000 >= $maxSpeedValue) {
		$mode = 'speed';
		$header = $dashboardMaxSpeedAlarmTXT;
		$alarmJS = 'maxSpeedAlarm();';
		$alarm = TRUE;
	}
}
if($toHeadingAlarm) {
	if($toHeadingMagnetic and isset($tpv['magtrack'])) $theHeading = $tpv['magtrack'];
	else $theHeading = $tpv['track']; 	// тревога прозвучит, даже если был указан магнитный курс, но его нет
	$minHeading = $toHeadingValue - $toHeadingPrecision;
	if($minHeading<0) $minHeading = $minHeading+360;
	$maxHeading = $toHeadingValue + $toHeadingPrecision;
	if($maxHeading>=360) $maxHeading = $maxHeading-360;
	//echo "$minHeading<$theHeading>$maxHeading";
	if($theHeading < $minHeading or $theHeading > $maxHeading) {
		$mode = 'track';
		$header = $dashboardToHeadingAlarmTXT;
		$alarmJS = 'toHeadingAlarm();';
		$alarm = true;
	}
}
if($depthAlarm and ($tpv['depth']!==NULL)) {
	if($tpv['depth'] <= $minDepthValue) {
		$mode = 'depth';
		$header = $dashboardDepthAlarmTXT;
		$alarmJS = 'depthAlarm();';
		$alarm = TRUE;
	}
}

// Что будем рисовать
//echo "mode=$mode; magnetic=$magnetic;<br>\n";
//echo "TPV:<pre>"; print_r($tpv); echo "</pre>";
//echo"tpv['speed']=".$tpv['speed']."<br>\n";
$cnt = count($displayData);
$enough = false; $prevMode = null; $nextMode = null;
for($i=0;$i<$cnt;++$i){
	if($i==0) {
		$parm = reset($displayData);
	}
	else {
		$parm = next($displayData);
	}
	$type = key($displayData);
	//echo "i=$i; type=$type; enough=$enough;<br>\n";
	//echo "parm:<pre>"; print_r($parm); echo "</pre>";
	//echo "displayData:<pre>"; print_r($displayData[$type]); echo "</pre>";
	if(!$mode) $mode = $type; 	// что-то не так с типом, сделаем текущий тип указанным
	if($enough) {
		$variant = 0;
		if($type == 'track' and $magnetic) $variant = 1;
		$variantType = $parm['variants'][$variant][0];
		if($tpv[$variantType] == NULL) { 	// но такого типа значения нет в полученных данных.
			if($i == $cnt-1) $i = -1; 	// цикл по кругу
			continue;
		}
		//$nextsymbol = "<span style='font-size:75%;'>".$parm['variants'][$variant][1]."</span> &nbsp; ".round($tpv[$variantType]*$parm['multiplicator'],$parm['precision']);
		$nextsymbol = $parm['variants'][$variant][1].":&nbsp; ".round($tpv[$variantType]*$parm['multiplicator'],$parm['precision']);
		$nextMode = $type;
		break;
	}
	if($type != $mode) {  	// это не указанный тип
		$prevMode = $type;
		continue;
	}
	$variant = 0;
	if($type == 'track' and $magnetic) $variant = 1;
	$variantType = $parm['variants'][$variant][0];
	if($tpv[$variantType] == NULL) { 	// но такого типа значения нет в полученных данных.
		$mode = null; 	// обозначим, что следующий тип должен стать указанным
		if($i == $cnt-1) $i = -1; 	// цикл по кругу
		continue;
	}
	$header = $parm['variants'][$variant][1];
	$symbol = round($tpv[$variantType]*$parm['multiplicator'],$parm['precision']);
	$enough = true;
}
if(!$prevMode){
	end($displayData);
	$prevMode = key($displayData);
}
$_SESSION['mode'] = $mode;
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

if($toHeadingAlarm) {

	//$toHeadingValue =30;
	// Метка указанного направления
	if(($toHeadingValue>315)and($toHeadingValue<360)){
		$percent = 100 - ($toHeadingValue - 315)*100/90;
		$currDirectMark = "<img src='img/markNNW.png' style='display:block;position:fixed;top:0;right:$percent%;'>";
	} 
	elseif($toHeadingValue == 0){
		$currDirectMark = "<img src='img/markN.png' style='display:block;position:fixed;top:0;left:49.5%;'>";
	}
	elseif(($toHeadingValue>0)and($toHeadingValue<45)){
		$percent = ($toHeadingValue+45)*100/90;
		$currDirectMark = "<img src='img/markNNE.png' style='display: block;position: fixed;top:0;left:$percent%;'>";
	}
	elseif($toHeadingValue == 45){
		$currDirectMark = "<img src='img/markNE.png' style='display: block;position: fixed;top:0;right:0;'>";
	}
	elseif(($toHeadingValue > 45) and ($toHeadingValue < 90)){
		$percent = 100 - ($toHeadingValue-45)*100/90;
		$currDirectMark = "<img src='img/markENE.png' style='display: block;position: fixed;right:0;bottom:$percent%;'>";
	}
	elseif($toHeadingValue == 90){
		$currDirectMark = "<img src='img/markE.png' style='display: block;position: fixed;right:0;top:49%;'>";
	}
	elseif(($toHeadingValue > 90) and ($toHeadingValue < 135)){
		$percent = ($toHeadingValue-45)*100/90;
		$currDirectMark = "<img src='img/markESE.png' style='display: block;position: fixed;right:0;top:$percent%;'>";
	}
	elseif($toHeadingValue == 135){
		$currDirectMark = "<img src='img/markSE.png' style='display: block;position: fixed;bottom:0;right:0;'>";
	}
	elseif(($toHeadingValue>135)and($toHeadingValue<180)){
		$percent = 100 - ($toHeadingValue-135)*100/90;
		$currDirectMark = "<img src='img/markSSE.png' style='display: block;position: fixed;bottom:0;left:$percent%;'>";
	}
	elseif($toHeadingValue == 180){
		$currDirectMark = "<img src='img/markS.png' style='display: block;position: fixed;bottom:0;left:49.5%;'>";
	}
	elseif(($toHeadingValue>180)and($toHeadingValue<225)){
		$percent = ($toHeadingValue-135)*100/90;
		$currDirectMark = "<img src='img/markSSW.png' style='display: block;position: fixed;bottom:0;right:$percent%;'>";
	}
	elseif($toHeadingValue==225){
		$currDirectMark = "<img src='img/markSW.png' style='display: block;position: fixed;bottom:0;left:0;'>";
	}
	elseif(($toHeadingValue>225)and($toHeadingValue<270)){
		$percent = 100 - ($toHeadingValue-225)*100/90;
		$currDirectMark = "<img src='img/markWSW.png' style='display:block;position:fixed;left:0;top:$percent%;'>";
	}
	elseif($toHeadingValue == 270){
		$currDirectMark = "<img src='img/markW.png' style='display: block;position: fixed;left:0;top:49%;'>";
	}
	elseif(($toHeadingValue>270)and($toHeadingValue<315)){
		$percent = ($toHeadingValue-225)*100/90;
		$currDirectMark = "<img src='img/markWNW.png' style='display:block;position:fixed;left:0;bottom:$percent%;'>";
	}
	elseif($toHeadingValue==315){
		$currDirectMark = "<img src='img/markNW.png' style='display: block;position: absolute;top:0;left:0;'>";
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
	width:65%;
	background-color:lightgrey;
	padding: 1rem;
	font-size: xx-large;
	z-index: 10;
'>
	<table>
		<tr style='height:3rem;'>
			<td style='width:3rem;'><input type='checkbox' name='depthAlarm' value='1' <?php if($depthAlarm) echo 'checked';?>></td>
			<td><?php echo "$dashboardDepthMenuTXT, $dashboardDepthMesTXT"?></td>
			<td style='width:15%;'><input type='text' name=minDepthValue value='<?php echo $minDepthValue?>' style='width:95%;font-size:xx-large;'></td>
		</tr><tr style='height:3rem;'>
			<td><input type='checkbox' name='minSpeedAlarm' value='1' <?php if($minSpeedAlarm) echo 'checked';?>></td>
			<td><?php echo "$dashboardMinSpeedMenuTXT, $dashboardSpeedMesTXT"?></td>
			<td style='width:15%;'><input type='text' name=minSpeedValue value='<?php echo $minSpeedValue?>' style='width:95%;font-size:xx-large;'></td>
		</tr><tr style='height:3rem;'>
			<td><input type='checkbox' name='maxSpeedAlarm' value='1' <?php if($maxSpeedAlarm) echo 'checked';?>></td>
			<td><?php echo "$dashboardMaxSpeedMenuTXT, $dashboardSpeedMesTXT"?></td>
			<td style='width:15%;'><input type='text' name=maxSpeedValue value='<?php echo $maxSpeedValue?>' style='width:95%;font-size:xx-large;'></td>
		</tr><tr style='height:3rem;'>
			<td><input type='checkbox' name='toHeadingAlarm' value='1' <?php if($toHeadingAlarm) echo 'checked';?>></td>
			<td><?php if($magnetic) {
						if($toHeadingAlarm) {
							if($toHeadingMagnetic) echo $dashboardMagHeadingTXT;
							else echo $dashboardHeadingTXT;
						}
						else echo $dashboardMagHeadingTXT; 
					}
					else {
						if($toHeadingAlarm) {
							if($toHeadingMagnetic) echo $dashboardMagHeadingTXT;
							else echo $dashboardHeadingTXT;
						}
						else echo $dashboardHeadingTXT;
					}?><br> &nbsp; 
			<input type='radio' name='toHeadingPrecision' value='10' <?php if($toHeadingPrecision == 10) echo 'checked';?>> &plusmn; 10&deg; &nbsp; 
			<input type='radio' name='toHeadingPrecision' value='20' <?php if($toHeadingPrecision == 20) echo 'checked';?>> &plusmn; 20&deg;
			<td style='width:15%;'><input type='text' name=toHeadingValue value='<?php if($magnetic){ 
																							if($toHeadingAlarm) echo $toHeadingValue; 
																							else echo round($tpv['magtrack']);
																						}
																						else { 
																							if($toHeadingAlarm) echo $toHeadingValue;
																							else echo round($tpv['track']);
																						}?>' style='width:95%;font-size:xx-large;'></td>
		</tr><tr>
			<td></td><td style='padding-top:2rem;'><a href='<?php echo $_SERVER['PHP_SELF'];?>' style='text-decoration:none;'><input type='button' value='&#x2718;' style='font-size:120%;'></a><input type='submit' name='submit' value='&#x2713;' style='font-size:120%;float:right;'></td><td></td>
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
padding: 1rem;
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

function getData($dataName,$gpsdData,$dataTypes) {
/* Аккумулирующий фильтр данных $gpsdData, полученных от функции askGPSD
Данные аккумулируются в сессии с именем $dataName
Какие данные нужно взять из $gpsdData и сколько хранить - указано в массиве $dataTypes
Возвращает массив данных

В настоящее время предполагается, что используется gpsdPROXY, и смысла в аккумулировании нет:
оно выполняется gpsdPROXY. Однако $dataTypes содержит список типов данных, которые надо извлечь
и которые dashboard может показать.
Но, однако, источником может быть не только gpsdPROXY, поэтому попытка аккумуляции оставлена

В $gpsdData данные по устройствам, в результирующем массиве - без конкретного устройства
*/
//echo "Получено от gpsd:<pre>"; print_r($gpsdData); echo "</pre>";

$tpv = $_SESSION[$dataName]; $tpvTime = $_SESSION[$dataName.'tpvTime']; $currTime = time();
krsort($gpsdData); 	// отсортируем устройства по времени к прошлому
foreach($gpsdData as $device) {
	$tpv['time'] = $device['time'];
	foreach($dataTypes as $data => $timeout) {
		// а вдруг у нас не gpadPROXY, а что-то, что не контролирует время жизни?
		if(($currTime-$tpvTime[$data])>$timeout) $tpv[$data] = NULL; 	// обнулим, если эти данные появлялись давно. Вне зависимости от возраста самих данных, которого может и не быть
		if($device[$data]!==NULL) {
			$tpv[$data] = (float)$device[$data];
			$tpvTime[$data] = $currTime;
		}
	}
	if($device['mode'] == 3) { 	// последний по времени 3D fix 
		// считаем, что это более достоверно
		$tpv['track'] = $device['track']; 	// курс, без явного преобразования типов, чтобы остался NULL
		$tpv['speed'] = $device['speed']; 	// скорость
	}

	$enough = TRUE;
	foreach($dataTypes as $data) {
		if(!($enough = ($enough AND $tpv[$data]))) break;
	}
	if($enough) break; 	// прекратим просмотр устройств, если собрали все данные
}
$_SESSION[$dataName] = $tpv; 	// собираем не только последние значения, но аккумулируем все. Позволяет собрать из нескольких источников, но какие-то величины могут быть сильно старыми.
$_SESSION[$dataName.'tpvTime'] = $tpvTime;

//echo "Собрано:<pre>"; print_r($tpv); echo "</pre>";
return $tpv;
} // end function getData

?>
