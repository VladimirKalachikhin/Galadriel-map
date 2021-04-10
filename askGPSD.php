<?php
/* POLL your gpsd
Оповещает клиента о MOB
*/
session_start();
ob_start(); 	// попробуем перехватить любой вывод скрипта
chdir(pathinfo(__FILE__, PATHINFO_DIRNAME)); // задаем директорию выполнение скрипта
include('params.php'); 	// пути и параметры
if(!$gpsdHost) $gpsdHost = 'localhost';
if(!$gpsdPort) $gpsdPort = 2947;
//echo "$gpsdHost,$gpsdPort\n";
require_once('fGPSD.php'); // fGPSD.php
$MOBdataFileName = 'MOB.json';
$MOBdataFilePath = 'MOB/';
$outData = array();

// Примем данные
$upData = $_REQUEST['upData'];
echo "upData=$upData;<br>\n";
$upData = json_decode($upData, true);
echo "File ".pathinfo(__FILE__, PATHINFO_DIRNAME)."<br>\nВходящие:<pre>";print_r($upData);echo "</pre>";

// Обработаем данные
if(isset($upData['MOB'])) { 	echo "режим MOB должен быть включён<br>\n";
	echo "upData['MOB']={$upData['MOB']};<br>\n";
 	if($upData['MOB'] === 'close'){ 	// === для отключения преобразования типов, иначе строка приводится к числу, а обычно там 0	 
 		echo "режим MOB должен быть выключен<br>\n";
		rename( $MOBdataFilePath.$MOBdataFileName, $MOBdataFilePath.date('Y-m-d_His').'_'.$MOBdataFileName); 	
 	}
 	elseif($upData['MOB']) { echo "Пришли новые данные MOB<br>\n";
 		file_put_contents($MOBdataFilePath.$MOBdataFileName,json_encode($upData['MOB']));
 		$outData['MOB'] = 0; 	// отметим, что данные приняли
 		$_SESSION['MOBsending'] = time(); 	// отметим, что данные этому клиенту отослали -- раз только что приняли
 	}
 	else { 	echo "клиент снова хочет данные MOB<br>\n";
		$_SESSION['MOBsending'] = 0;
 	}
}

// Соберём данные
// MOB
$MOBtime = @filectime($MOBdataFilePath.$MOBdataFileName);
echo "MOBtime=$MOBtime;<br>\n";
if($MOBtime) { 	// режим MOB
	if($MOBtime > $_SESSION['MOBsending']) { 	// этому клиенту данные ещё не сообщали
		$outData['MOB'] = json_decode(file_get_contents($MOBdataFilePath.$MOBdataFileName), true);
		$_SESSION['MOBsending'] = time(); 	// отметим, что данные этому клиенту отослали -- раз только что приняли
	}
	else $outData['MOB'] = true; 	// сообщим о режиме MOB
}
session_write_close();
echo "<pre>";print_r($outData);echo "</pre>";

// Отправим данные
$outData = json_encode(array_merge($outData,getPosAndInfo($gpsdHost,$gpsdPort))); 	// получим ВремяПозициюСкорость от gpsd

ob_end_clean(); 			// очистим, если что попало в буфер
header('Content-Type: application/json;charset=utf-8;');
echo "$outData \n";
return;

