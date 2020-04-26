var snd = new Audio("img/beep-02.wav");  
<<<<<<< HEAD
function depthAlarm() { 	// 
=======
function depthAlarm() {
>>>>>>> withAIS
	setInterval(function(){snd.play();},300)
}
function maxSpeedAlarm() {
	setInterval(function(){snd.play();},1000)
}
function minSpeedAlarm() {
	setInterval(function(){snd.play();},1500)
}
