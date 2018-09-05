"use strict"
/* Функции
getCookie(name)
doSavePosition() 	Сохранение положения

selectMap(node) 	Выбор карты из списка имеющихся
deSelectMap(node) 	Прекращение показа карты, и возврат её в список имеющихся.
displayMap(mapname) Создаёт leaflet lauer с именем, содержащемся в mapname, и заносит его на карту
removeMap(mapname)

selectTrack()
displayTrack()
deSelectTrack()
updateCurrTrack()

createDwnldJob()
*/
function getCookie(name) {
// возвращает cookie с именем name, если есть, если нет, то undefined
var matches = document.cookie.match(new RegExp(
	"(?:^|; )" + name.replace(/([\.$?*|{}\(\)\[\]\\\/\+^])/g, '\\$1') + "=([^;]*)"
	)
);
return matches ? decodeURIComponent(matches[1]) : null;
}

function doSavePosition(){
/* Сохранение положения
global map, mapDisplayed, document, currTrackSwitch
*/
var expires =  new Date();
var pos = JSON.stringify(map.getCenter());
var zoom = JSON.stringify(map.getZoom());
expires.setTime(expires.getTime() + (60*24*60*60*1000)); 	// протухнет через два месяца
document.cookie = "GaladrielMapPosition="+pos+"; expires="+expires+"; path=/";
document.cookie = "GaladrielMapZoom="+zoom+"; expires="+expires+"; path=/";
//alert('Сохранение параметров '+pos+zoom);
// Сохранение показываемых карт
var openedMapsNames = [];
for (var i = 0; i < mapDisplayed.children.length; i++) { 	// для каждого потомка списка mapDisplayed
	openedMapsNames[i] = mapDisplayed.children[i].innerHTML; 	// 
}
openedMapsNames = JSON.stringify(openedMapsNames);
document.cookie = "GaladrielMaps="+openedMapsNames+"; expires="+expires+"; path=/";
// Сохранение переключателей
document.cookie = "GaladrielcurrTrackSwitch="+Number(currTrackSwitch.checked)+"; expires="+expires+"; path=/"; 	// переключатель currTrackSwitch
}

// Функции выбора - удаления карт
function selectMap(node) { 	
// Выбор карты из списка имеющихся. Получим объект
//alert(node);
mapDisplayed.insertBefore(node,mapDisplayed.firstChild); 	// из списка доступных в список показываемых (объект, на котором событие, добавим в конец потомков mapDisplayed)
node.onclick = function(event){deSelectMap(event.currentTarget);};
displayMap(node.innerHTML);
}

function deSelectMap(node) {
// Прекращение показа карты, и возврат её в список имеющихся. Получим объект
//alert(node);
var li = null;
for (var i = 0; i < mapList.children.length; i++) { 	// для каждого потомка списка mapList
	li = mapList.children[i]; 	// взять этого потомка
	var childTitle = li.innerHTML;
	if (childTitle > node.innerHTML) { 	// если наименование потомка дальше по алфавиту, чем наименование того, на что кликнули
		//alert(childTitle+" "+node.innerHTML);
		break;
	}
	li = null;
}
mapList.insertBefore(node,li); 	// перенесём перед тем, на котором обломался цикл, или перед концом
node.onclick = function(event){selectMap(event.currentTarget);};
removeMap(node.innerHTML);
}

function displayMap(mapname) {
// Создаёт leaflet lauer с именем, содержащемся в mapname, и заносит его на карту
// Если для запросов тайлов нужно их расширение - делает запрос к askMapParm.php для получения
// Если в имени карты есть EPSG3395 - делает слой в проекции с пересчётом с помощью L.tileLayer.Mercator
// проекцию карты от askMapParm.php не получает!!! чтобы не делать лишних запросов
var tileCacheURIthis = tileCacheURI.replace('{map}',mapname); 	// глобальная переменная
//alert(tileCacheURIthis);
if(  tileCacheURIthis.indexOf('{ext}')!=-1) {	// если для запроса тайлов нужно их расширение
	var xhr = new XMLHttpRequest();
	xhr.open('GET', 'askMapParm.php?mapname='+mapname, false); 	// Подготовим синхронный запрос
	xhr.send();
	if (xhr.status == 200) { 	// Успешно
		var mapParm = JSON.parse(xhr.responseText); 	// параметры карты: первый - расширение, второй - проекция
		tileCacheURIthis = tileCacheURIthis.replace('{ext}',mapParm[0]);
		//alert('Получены параметры карты \n'+tileCacheURIthis);
	}
	else return;
}
//alert('mapname='+mapname+'\n'+window[mapname]);
if(  mapname.indexOf('EPSG3395')==-1) {
	if(!window[mapname])	window[mapname] = L.tileLayer(tileCacheURIthis, {
		});
}
else {
	if(!window[mapname])	window[mapname] = L.tileLayer.Mercator(tileCacheURIthis, {
	//if(!window[mapname])	window[mapname] = L.tileLayer(tileCacheURIthis, {
		});
}
//alert('После: mapname='+mapname+'\n'+window[mapname]);
window[mapname].addTo(map);
} // end function displayMap

function removeMap(mapname) {
window[mapname].remove();
}

// Функции выбора - удаления треков
function selectTrack(node) { 	
/* Выбор трека из списка имеющихся. Получим объект
global window, map, trackDisplayed,  currentTrackName
*/
//alert(node.innerHTML);
trackDisplayed.insertBefore(node,trackDisplayed.firstChild); 	// из списка доступных в список показываемых (объект, на котором событие, добавим в конец потомков mapDisplayed)
node.onclick = function(event){deSelectTrack(event.currentTarget);};
displayTrack(node.innerHTML); 	// создадим трек
} // end function selectTrack

function displayTrack(trackName) {
/* рисует трек с именем trackName 
global gpxDirURI, window, currentTrackName
*/
//alert(trackName);
if( window[trackName] && (trackName != currentTrackName)) window[trackName].addTo(map); 	// нарисуем его на карте. Текущий трек всегда перезагружаем
else {
	var xhr = new XMLHttpRequest();
	//alert(gpxDirURI+'/'+trackName+'.gpx');
	xhr.open('GET', encodeURI(gpxDirURI+'/'+trackName+'.gpx'), true); 	// Подготовим асинхронный запрос
	xhr.send();
	xhr.onreadystatechange = function() { // trackName - внешняя
		if (this.readyState != 4) return; 	// запрос ещё не завершился, покинем функцию
		if (this.status != 200) { 	// запрос завершлся, но неудачно
			alert('На запрос трека сервер ответил '+this.status);
			return; 	// что-то не то с сервером
		}
		//alert('|'+this.responseText.slice(-10)+'|');
		if(this.responseText.slice(-10).indexOf('</gpx>') == -1)	window[trackName] = omnivore.gpx.parse(this.responseText + '  </trkseg>\n </trk>\n</gpx>');
		else window[trackName] = omnivore.gpx.parse(this.responseText); 	// responseXML иногда почему-то кривой
		window[trackName].addTo(map); 	// нарисуем его на карте
	}
}
} // end function createTrack

function deSelectTrack(node) {
/* Прекращение показа трека, и возврат его в список имеющихся. Получим объект
global trackList
*/
//alert(node.innerHTML);
var li = null;
for (var i = 0; i < trackList.children.length; i++) { 	// для каждого потомка списка trackList
	li = trackList.children[i]; 	// взять этого потомка
	var childTitle = li.innerHTML;
	if (childTitle > node.innerHTML) { 	// если наименование потомка дальше по алфавиту, чем наименование того, на что кликнули
		//alert(childTitle+" "+node.innerHTML);
		break;
	}
	li = null;
}
trackList.insertBefore(node,li); 	// перенесём перед тем, на котором обломался цикл, или перед концом
node.onclick = function(event){selectTrack(event.currentTarget);};
removeMap(node.innerHTML);
}

function updateCurrTrack() {
/* Текущий трек дорсовывается по асинхронным запросам к серверу
global window currentTrackServerURI, currentTrackName
*/
var xhr = new XMLHttpRequest();
// Получим последнюю путевую точку или последний сегмент, или последний трек из текущего трека
xhr.open('GET', encodeURI(currentTrackServerURI+'?currTrackName='+currentTrackName), true); 	// Подготовим асинхронный запрос
xhr.send();
xhr.onreadystatechange = function() { // 
	if (this.readyState != 4) return; 	// запрос ещё не завершился, покинем функцию
	if (this.status != 200) { 	// запрос завершлся, но неудачно
		alert('Сервер ответил '+this.status+'\ncurrentTrackServerURI='+currentTrackServerURI+'\ncurrTrackName='+currentTrackName+'\n\n');
		return; 	// что-то не то с сервером
	}
	//alert(this.responseText);
	if(this.responseText) {
		var currentTrkPtGeoJSON = toGeoJSON.gpx(this.responseXML); 	// сделаем из полученного GeoJSON функцией из omnivore
		//alert(JSON.stringify(currentTrkPtGeoJSON));
		window[currentTrackName].addData(currentTrkPtGeoJSON); 	// добавим полученное к слою с текущим треком
	}
}
} // end function updateCurrTrack

// 
function createDwnldJob() {
/* Собирает задания на загрузку: для каждой карты кладёт на сервер csv с номерами тайлов текущего масштаба.
Считается, что номера тайлов указываются на сфере */
//alert('submit '+mapDisplayed.children.length+' maps');
var tileXs = dwnldJob.getElementsByClassName("tileX");
var tileYs = dwnldJob.getElementsByClassName("tileY");
var zoom = current_zoom.innerHTML;
var XYs = '', XYsE = '', xhr = [];
for (var i = 0; i < mapDisplayed.children.length; i++) { 	// для каждого потомка списка mapDisplayed
	var mapname = mapDisplayed.children[i].innerHTML; 	// 
	if(mapname.indexOf('EPSG3395')==-1) {	// карта - на сфере, пишем тайлы как есть
		if(!XYs.length) {
			for (var k = 0; k < tileXs.length; k++) {
				//alert('|'+tileXs[k].value+'|'+tileYs[k].value+'|');
				if(+tileXs[k].value && +tileYs[k].value) 	XYs += tileXs[k].value+','+tileYs[k].value+'\n';
			}
		}
		//alert(XYs);
		var uri = 'loaderJob.php?jobname='+mapname+'.'+zoom+'&xys='+XYs;
	}
	else {	// карта - на эллипсоиде, пишем тайлы на один ниже
		if(!XYsE.length) {
			var minY = 524288;	// max Y on zoom 19
			for (var k = 0; k < tileXs.length; k++) {
				if(+tileXs[k].value && +tileYs[k].value) {		
					XYsE += tileXs[k].value+','+String(+tileYs[k].value+1)+'\n'; 	// тайлы на 1 ниже,
					if(tileYs[k].value < minY) minY = tileYs[k].value;
				}
			}
			// а потом добавим верхний ряд
			for (var k = 0; k < tileXs.length; k++) {
				if(+tileXs[k].value && +tileYs[k].value) {		
					if(tileYs[k].value == minY) XYsE += tileXs[k].value+','+tileYs[k].value+'\n';
				}
			}
		}
		//alert(XYsE);
		var uri = 'loaderJob.php?jobname='+mapname+'.'+zoom+'&xys='+XYsE;
	}
	//alert(encodeURI(uri)+'\n\n');
	//return;
	xhr[i] = new XMLHttpRequest();
	xhr[i].open('GET', encodeURI(uri), true); 	// Подготовим асинхронный запрос
	xhr[i].send();
	xhr[i].onreadystatechange = function() { // 
		if (this.readyState != 4) return; 	// запрос ещё не завершился
		if (this.status != 200) return; 	// что-то не то с сервером
		dwnldJobList.innerHTML += this.responseText + '<br>\n';
	}
}
} 	// end function createDwnldJob


