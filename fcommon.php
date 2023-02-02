<?php
/*
tailCustom
gpxloggerRun
getLastTrackName()
*/

function tailCustom($filepath, $lines = 1, $adaptive = true) {
/**
Возвращает последние $lines файла с именем $filepath, пытаясь оптимизировать размер буфера ($adaptive = true)
под это дело. Если $adaptive = false - буфер 4096
* Slightly modified version of http://www.geekality.net/2011/05/28/php-tail-tackling-large-files/
* @author Torleif Berger, Lorenzo Stanco
* @link http://stackoverflow.com/a/15025877/995958
* @license http://creativecommons.org/licenses/by/3.0/
но вообще-то проще вызвать tail 
*/
// Open file
$f = @fopen($filepath, "rb");
if ($f === false) return false;
// Sets buffer size, according to the number of lines to retrieve.
// This gives a performance boost when reading a few lines from the file.
if (!$adaptive) $buffer = 4096;
else $buffer = ($lines < 2 ? 64 : ($lines < 10 ? 512 : 4096));
// Jump to last character
fseek($f, -1, SEEK_END);
// Read it and adjust line number if necessary
// (Otherwise the result would be wrong if file doesn't end with a blank line)
if (fread($f, 1) != "\n") $lines -= 1;

// Start reading
$output = '';
$chunk = '';
// While we would like more
while (ftell($f) > 0 && $lines >= 0) {
	// Figure out how far back we should jump
	$seek = min(ftell($f), $buffer);
	// Do the jump (backwards, relative to where we are)
	fseek($f, -$seek, SEEK_CUR);
	// Read a chunk and prepend it to our output
	$output = ($chunk = fread($f, $seek)) . $output;
	// Jump back to where we started reading
	fseek($f, -mb_strlen($chunk, '8bit'), SEEK_CUR);
	// Decrease our line counter
	$lines -= substr_count($chunk, "\n");
}
//echo "lines=$lines\n|$output|\n\n";
// While we have too many lines
// (Because of buffer size we might have read too many)
while ($lines++ < 0) {
	// Find first newline and remove all text before that
	$output = substr($output, strpos($output, "\n") + 1);
}
// Close file and return
fclose($f);
//return trim($output);
return $output;
} // end function tailCustom

function gpxloggerRun($retFileName=false){
/* Определяет, запущен ли gpxlogger, в смысле -- строка запуска, 
содержащаяся в глобальной переменной $gpxlogger
Возвращает его PID, или, если указано $retFileName=true, имя пишущегося файла
*/
global $gpxlogger, $busyboxPresent;
// Это не будет работать в системах, где вызывмется одно, а реально запускается другое.
// Например, вместо указанного php  запускается /usr/bin/php или даже /usr/bin/real_php
$gpxlg = substr($gpxlogger,0,strpos($gpxlogger,'&logfile'));
$name = trim(substr($gpxlg,0,strpos($gpxlg,'-')));	// будем считать именем запущенной программы всё до первого символа -
if(!$name) $name = trim(substr("$gpxlg ",0,strpos("$gpxlg ",' ')));	// если же в команде не было символов - то до первого пробела. Но это так себе подход.
//echo "gpxlg=$gpxlg; name=$name;\n";
if($busyboxPresent) exec("ps w | grep  '$gpxlg'",$psList); 	// for OpenWRT. For others -- let's hope so all run from one user
else {
	exec("ps -A w | grep  '$gpxlg'",$psList);
	if(!$psList) {
		exec("ps w | grep  '$gpxlg'",$psList); 	// for OpenWRT. For others -- let's hope so all run from one user
		$busyboxPresent=true;
	}
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
		case $name:
			//echo "name=$name; str="; print_r($str);echo "\n";
			if($retFileName){
				foreach(array_reverse($str) as $w) {	// обычно имя файла -- последний параметр, а array_reverse быстрее?
					if(strrpos($w,'.gpx')!==false) break;
					$w = '';
				}
				$run=$w;
			}
			else $run=$pid;
			break 3;
		}
	}
}
//echo "run=$run;\n";
return $run;
} // end function gpxloggerRun()

function getLastTrackName($trackNames=false){
/**/
global $trackDir, $currTrackFirst;	// params.php

if(!$trackNames) $trackNames = glob($trackDir.'/*gpx');
if($currTrackFirst) $outpuFileName = $trackNames[0]; 	// params.php
else $outpuFileName = $trackNames[count($trackNames)-1];
$outpuFileName = explode('/',$outpuFileName); 	// выделим имя файла, которое, в принципе, может быть кириллицей
$outpuFileName = $outpuFileName[count($outpuFileName)-1];

return $outpuFileName;
}; // end function getLastTrackName


?>
