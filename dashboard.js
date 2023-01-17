var snd = new Audio("img/beep-02.wav");  

function depthAlarmSound() { 	// 
	setInterval(function(){snd.play();},300)
}

function maxSpeedAlarmSound() {
	setInterval(function(){snd.play();},1000)
}

function minSpeedAlarmSound() {
	setInterval(function(){snd.play();},1500)
}

function toHeadingAlarmSound() {
	let timer = setInterval(function(){snd.play();},250)
	//setTimeout(function(){clearInterval(timer)},1000);
}


