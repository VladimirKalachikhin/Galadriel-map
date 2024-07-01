<?php
chdir(__DIR__); // задаем директорию выполнение скрипта
require('params.php'); 	// 

$inetAISpath = '/GaladrielMap/digitraffic';

// Интернационализация
$appLocale = explode('-',explode(';',explode(',',$_SERVER['HTTP_ACCEPT_LANGUAGE'])[0])[0])[0];	
switch($appLocale){
case 'ru':
	$inetAISnotFoundTXT = 'Не найдено приложение inetAIS';
	$inetAISrunningTXT = 'inetAIS запущен как процесс №';
	$inetAISnotRunTXT = 'inetAIS не запущен';
	$inetAISrunTXT = 'запустить inetAIS';
	$inetAISstopTXT = 'остановить inetAIS';
	break;
default:
	$inetAISnotFoundTXT = 'inetAIS package not found';
	$inetAISrunnigTXT = 'inetAIS run as PID';
	$inetAISnotRunTXT = 'inetAIS not run';
	$inetAISrunTXT = 'run inetAIS';
	$inetAISstopTXT = 'stop inetAIS';
};

$msg = '';
if(!file_exists($inetAISpath)){
	$msg = $inetAISnotFoundTXT;
	goto DISPLAY;
};

BEGIN:
// Определим, запущен ли
// Это не будет работать в системах, где вызывмется одно, а реально запускается другое.
// Например, вместо указанного php  запускается /usr/bin/php или даже /usr/bin/real_php
@exec("ps -A w | grep 'inetAIS.php'",$psList);
if(!$psList) { 	// for OpenWRT. For others -- let's hope so all run from one user
	exec("ps w | grep 'inetAIS.php'",$psList);
	echo "BusyBox based system found\n";
}
//echo "<pre>"; print_r($psList);echo "</pre>";
$run = FALSE;
foreach($psList as $str) {
	//echo "str=$str;\n";
	$str = explode(' ',trim($str)); 	// массив слов
	$pid = $str[0];
	foreach($str as $w) {
		switch($w){
		case 'watch':
		case 'ps':
		case 'grep':
		case 'sh':
		case 'bash': 	// если встретилось это слово -- это не та строка
			break 2;
		case 'php':
			$run=$pid;
			break 3;
		}
	}
}
//echo "run=$run;\n";
if($run) {
	if($_REQUEST['action'] == 'stop'){
		$res = exec("kill $run");
		//echo "stop res=$res;";
		$_REQUEST['action'] = null;
		goto BEGIN;
	}
	else{
		$msg = $inetAISrunningTXT." $run";
		$buttonText = $inetAISstopTXT;
		$buttonValue = 'stop';
	};
}
else{ 
	if($_REQUEST['action'] == 'start'){
		exec("$inetAISpath/start -d");
		$_REQUEST['action'] = null;
		goto BEGIN;
	}
	else{
		$msg = $inetAISnotRunTXT;
		$buttonText = $inetAISrunTXT;
		$buttonValue = 'start';
	};
}

DISPLAY:
?>
<!DOCTYPE html >
<html lang="<?php echo $appLocale;?>">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<!--	<link rel="stylesheet" href="inetaispanel.css" type="text/css"> -->
   <title>inetAIS control panel</title>
</head>
<body style="text-align:center;font-size:150%;padding:50vh 0 50vh 0;">
<div>
	<?php echo $msg;?>
</div>
<br>
<form>
	<button type="submit" name="action" value="<?php echo $buttonValue;?>" style="width:30%;font-size:inherit;">
		<?php echo $buttonText;?>
	</button>
</form>
</body>
</html>
