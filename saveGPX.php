<?php
/* Вызывается для сохранения gpx */
require('params.php'); 	// пути и параметры

if(!$routeDir) {
	echo "Sorry, no way to save path.";
	return;
}

$gpx = trim(urldecode($_REQUEST['gpx']));
//$name = str_replace(' ','_',urldecode($_REQUEST['name']));
$name = trim(urldecode($_REQUEST['name']));
//error_log("Name: ".html_entity_decode($name));
//error_log($gpx);
file_put_contents("$routeDir/$name".'.gpx',$gpx); 	// 
echo $name.'.gpx saved!';
?>
