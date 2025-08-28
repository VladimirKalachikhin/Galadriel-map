<?php
/* Вызывается для сохранения gpx */
ini_set('error_reporting', E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);
//ini_set('error_reporting', E_ALL & ~E_STRICT & ~E_DEPRECATED);
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
if(pathinfo($name,PATHINFO_EXTENSION) != 'gpx') $name = "$name.gpx";
if($gpx){
	$res = file_put_contents("$routeDir/$name",$gpx); 	// 
	if($res === false) echo "$name not saved!";
	else echo "$name saved!";
}
else {
	unlink("$routeDir/$name");
	echo "$name deleted!";
};
?>
