<?php
/* POLL your gpsd
*/
$gpsdHost = 'localhost';
$gpsdPort = 2947;
require_once('fGPSD.php'); // fGPSD.php
$LefletRealtime = posToLeafletRealtime($gpsdHost,$gpsdPort); 	// получим ВремяПозициюСкорость от gpsd
header('Content-Type: application/json;charset=utf-8;');
echo "$LefletRealtime \n";
?>
