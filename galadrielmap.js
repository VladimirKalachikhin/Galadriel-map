"use strict"
/* Функции
getCookie(name)
doSavePosition() 	Сохранение положения

selectMap(node) 	Выбор карты из списка имеющихся
deSelectMap(node) 	Прекращение показа карты, и возврат её в список имеющихся.
displayMap(mapname) Создаёт leaflet lauer с именем, содержащемся в mapname, и заносит его на карту
removeMap(mapname)

selectTrack()
deSelectTrack()
displayTrack()
updateCurrTrack()

createDwnldJob()

routeControlsDeSelect()
delShapes(realy)
tooggleEditRoute(e)
doSaveMeasuredPaths()
doRestoreMeasuredPaths()

saveGPX() 			Сохраняет на сервере маршрут из объекта currentRoute
toGPX(geoJSON,createTrk) Create gpx route or track (createTrk==true) from geoJSON object

String.prototype.encodeHTML = function ()
*/
function getCookie(name) {
// возвращает cookie с именем name, если есть, если нет, то undefined
name=name.trim();
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
mapname=mapname.trim();
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
mapname=mapname.trim();
window[mapname].remove();
}

// Функции выбора - удаления треков
function selectTrack(node,trackList,trackDisplayed,displayTrack) { 	
/* Выбор трека из списка имеющихся. 
node - объект li, элемент списка имеющихся, который выбрали
trackList - объект ul, список имеющихся
trackDisplayed - объект ul, список выбранных
displayTrack - функция показывания того, что соответствует выбранному элементу
global deSelectTrack()
*/
//alert(node.innerHTML);
//console.log(trackDisplayed.firstChild);
trackDisplayed.insertBefore(node,trackDisplayed.firstChild); 	// из списка доступных в список показываемых (объект, на котором событие, добавим в конец потомков mapDisplayed)
node.onclick = function(event){deSelectTrack(event.currentTarget,trackList,trackDisplayed,displayTrack);};
displayTrack(node.innerHTML); 	// создадим трек
} // end function selectTrack

function deSelectTrack(node,trackList,trackDisplayed,displayTrack) {
/* Прекращение показа трека, и возврат его в список имеющихся. Получим объект
node - объект li, элемент списка показываемых, который выбрали для непоказывания
trackList - объект ul, список имеющихся, куда надо вернуть node
global selectTrack()
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
node.onclick = function(event){selectTrack(event.currentTarget,trackList,trackDisplayed,displayTrack);};
removeMap(node.innerHTML);
}

function displayTrack(trackName) {
/* рисует трек с именем trackName 
global trackDirURI, window, currentTrackName
*/
//alert(trackName);
trackName=trackName.trim();
if( window[trackName] && (trackName != currentTrackName)) window[trackName].addTo(map); 	// нарисуем его на карте. Текущий трек всегда перезагружаем
else {
	var xhr = new XMLHttpRequest();
	//alert(trackDirURI+'/'+trackName+'.gpx');
	xhr.open('GET', encodeURI(trackDirURI+'/'+trackName+'.gpx'), true); 	// Подготовим асинхронный запрос
	xhr.send();
	xhr.onreadystatechange = function() { // trackName - внешняя
		if (this.readyState != 4) return; 	// запрос ещё не завершился, покинем функцию
		if (this.status != 200) { 	// запрос завершлся, но неудачно
			alert('На запрос трека сервер ответил '+this.status);
			return; 	// что-то не то с сервером
		}
		//alert('|'+this.responseText.slice(-10)+'|');
		if(this.responseText.slice(-10).indexOf('</gpx>') == -1)	window[trackName] = omnivore.gpx.parse(this.responseText + '  </trkseg>\n </trk>\n</gpx>'); // незавершённый gpx - дополним до конца. Поэтому скачиваем сами, а не omnivore
		else window[trackName] = omnivore.gpx.parse(this.responseText); 	// responseXML иногда почему-то кривой
		window[trackName].addTo(map); 	// нарисуем его на карте
	}
}
} // end function displayTrack

function displayRoute(routeName) {
/* рисует маршрут или места с именем routeName 
global routeDirURI map window
*/
if( window[routeName]) window[routeName].addTo(map); 	// нарисуем его на карте. 
else {
	var routeType =  routeName.slice((routeName.lastIndexOf(".") - 1 >>> 0) + 2).toLowerCase(); 	// https://www.jstips.co/en/javascript/get-file-extension/ потому что там нет естественного пути
	switch(routeType) {
	case 'gpx':
		window[routeName] = omnivore.gpx(routeDirURI+'/'+routeName);
		window[routeName].addTo(map);
		break;
	case 'kml':
		window[routeName] = omnivore.kml(routeDirURI+'/'+routeName);
		window[routeName].addTo(map);
		break;
	}
}
} // end function displayRoute

function updateCurrTrack() {
/* Текущий трек дорсовывается по асинхронным запросам к серверу
От сервера получается точка в формате gpx - структура типа trkpt
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
	//alert(encodeURI(uri)+'           \n\n');
	//continue;
	xhr[i] = new XMLHttpRequest();
	xhr[i].open('GET', encodeURI(uri), true); 	// Подготовим асинхронный запрос
	xhr[i].send();
	xhr[i].onreadystatechange = function() { // 
		if (this.readyState != 4) return; 	// запрос ещё не завершился
		if (this.status != 200) return; 	// что-то не то с сервером
		dwnldJobList.innerHTML += '<li>' + this.responseText + '</li>\n';
	}
}
} 	// end function createDwnldJob

// Функции рисования маршрутов
function routeControlsDeSelect() {
// сделаем невыбранными кнопки управления рисованием маршрута. Они должны быть и так не выбраны, но почему-то...
var elements = document.getElementsByName('routeControl');
for (var i = 0; i < elements.length; i++) {
	elements[i].checked=false;
}   
} // end function routeControlsDeSelect

function delShapes(realy) {
/* Удаляет полилинии в состоянии редактирования, если realy = true
возвращает число таких объектов
полилинии находятся в глобальном массиве measuredPaths, куда заносятся при создании
*/
//alert(measuredPaths);
var edEnShapesCntr=0;
if(realy) map.editTools.stopDrawing(); 	// нужно прекратить рисование перед удалением, иначе будут глюки
for(var i=0; i<measuredPaths.length; i++) {
	if(measuredPaths[i].editEnabled()) {
		edEnShapesCntr++;
		//console.log(measuredPaths[i]);
		//alert(measuredPaths[i].getLatLngs()[0]);
		if(realy) {
			measuredPaths[i].editor.deleteShapeAt(measuredPaths[i].getLatLngs()[0]);
			measuredPaths.splice(i,1);
		}
	}
};
//alert(measuredPaths);
return edEnShapesCntr;
}	// end function delShapes

function tooggleEditRoute(e) {
/* Переключает режим редактирования
Обычно обработчик клика по линии
*/
//console.log(e.target);

currentRoute = e.target; 	// сделаем объект, по которому щёлкнули, текущим
if(!routeSaveName.value || Date.parse(routeSaveName.value)) routeSaveName.value = new Date().toJSON(); 	// запишем в поле ввода имени дату, если там ничего не было или была дата

e.target.toggleEdit();
if(e.target.editEnabled()) { 	//  если включено редактирование
	routeEraseButton.disabled=false; 	// - сделать доступной кнопку Удалить
	routeContinueButton.disabled=false; 	// - сделать доступной кнопку Продолжить
}
else {
	if(delShapes(false))  routeEraseButton.disabled=false; 	// если есть редактируемые слои
	else {
		routeEraseButton.disabled=true; 	// - сделать доступной кнопку Удалить
		routeContinueButton.disabled=true; 	//  - сделать доступной кнопку Продолжить
	}
}
} // end function tooggleEditRoute

function doSaveMeasuredPaths() {
/* сохранение в cookie отображаемых на карте маршрутов
Сохраняются только маршруты, не находящиеся в состоянии редактирования.
Предполагается, что это для сохранения маршрутов/замеров расстояний на конкретном устройстве
*/
var toSave = [];
if(measuredPaths.length) { 	// если есть, что сохранять
	var expires =  new Date();
	expires.setTime(expires.getTime() + (60*24*60*60*1000)); 	// протухнет через два месяца
	for(var i=0; i<measuredPaths.length; i++) {	// в глобальном списке маргрутов
		if(!measuredPaths[i].editEnabled()) { 	// те, что не редактируются
			toSave.push(measuredPaths[i].getLatLngs()); 	// сохраним координаты вершин
		}
	}
}
//alert(toSave.length);
toSave = JSON.stringify(toSave);
//alert(toSave);
document.cookie = "GaladrielMapMeasuredPaths="+toSave+"; expires="+expires+"; path=/"; 	// если сечас и нет, чего сохранять - грохнем куки
} 	// end function doSaveMeasuredPaths

function doRestoreMeasuredPaths() {
//var RestoreMeasuredPaths = JSON.parse(JSON.retrocycle(getCookie('GaladrielMapMeasuredPaths')));
var RestoreMeasuredPaths = JSON.parse(getCookie('GaladrielMapMeasuredPaths'));
if(RestoreMeasuredPaths) {
	if(L.Browser.mobile && L.Browser.touch) var weight = 15; 	// мобильный браузер
	else var weight = 7; 	// стационарный браузер
	for(var i=0; i<RestoreMeasuredPaths.length; i++) {	// в списке маршрутов
		window.LAYER = L.polyline(RestoreMeasuredPaths[i],{showMeasurements: true,color: '#FDFF00',weight: weight,opacity: 0.5})
		.addTo(map);
		//window.LAYER.on('dblclick', L.DomEvent.stop).on('dblclick', window.LAYER.toggleEdit);
        window.LAYER.on('click', L.DomEvent.stop).on('click', tooggleEditRoute);
		measuredPaths.push(window.LAYER);
	}
}
}	// end function doRestoreMeasuredPaths

function saveGPX() {
/* Сохраняет на сервере маршрут из объекта currentRoute
*/
if(!currentRoute) { 	// глобальная переменная, должна содержать объект Editable, присваивается в tooggleEditRoute, типа - по щелчку на маршруте
	routeSaveMessage.innerHTML = 'Error - no route selected.'
	return;
}
//console.log(currentRoute.toGeoJSON().getBounds());
var route = currentRoute.toGeoJSON(); 	// сделаем из Editable объект geoJSON
if(route.geometry.type != 'LineString') { 	// это не линия
	routeSaveMessage.innerHTML = 'Error - omly line may be saved.'
	return;
}
if(!routeSaveName.value) routeSaveName.value = new Date().toJSON(); 	// это будет name
if(!route.properties.name) route.properties.name = routeSaveName.value;
var name = route.properties.name;
if(!route.properties.desc && routeSaveDescr.value) route.properties.desc = routeSaveDescr.value;
//console.log(route);
route = toGPX(route,currentRoute.getBounds()); 	// сделаем gpx маршрут
//console.log(route);

var xhr = new XMLHttpRequest();
xhr.open('POST', 'saveGPX.php', true); 	// Подготовим асинхронный запрос
xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
xhr.send('name=' + encodeURIComponent(name) + '&gpx=' + encodeURIComponent(route));
xhr.onreadystatechange = function() { // 
	if (this.readyState != 4) return; 	// запрос ещё не завершился
	if (this.status != 200) return; 	// что-то не то с сервером
	routeSaveMessage.innerHTML = this.responseText;
}
} // end function createGPX()

function toGPX(geoJSON,bounds,createTrk) {
/* Create gpx route or track (createTrk==true) from geoJSON object
geoJSON must have a needle gpx attributes
bounds - потому что geoJSON.getBounds() не работает
*/
//console.log(geoJSON);
var gpxtrack = `<?xml version="1.0" encoding="UTF-8" standalone="no" ?>
<gpx xmlns="http://www.topografix.com/GPX/1/1"  creator="GaladrielMap" version="1.1" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.topografix.com/GPX/1/1 http://www.topografix.com/GPX/1/1/gpx.xsd">
`;
gpxtrack += '<metadata>\n';
gpxtrack += '	<time>'+new Date().toISOString()+'</time>\n';
//var bounds = geoJSON.getBounds();
gpxtrack += '	<bounds minlat="'+bounds.getSouth().toFixed(4)+'" minlon="'+bounds.getWest().toFixed(4)+'" maxlat="'+bounds.getNorth().toFixed(4)+'" maxlon="'+bounds.getEast().toFixed(4)+'"  />\n';
gpxtrack += '</metadata>\n';
if(createTrk) gpxtrack += '	<trk>\n'; 	// рисуем трек
else gpxtrack += '	<rte>\n'; 	// рисуем маршрут

if(geoJSON.properties.name) gpxtrack += '		<name>' + geoJSON.properties.name.encodeHTML() + '</name>\n';
if(geoJSON.properties.cmt) gpxtrack += '		<cmt>' + geoJSON.properties.cmt.encodeHTML() + '</cmt>\n';
if(geoJSON.properties.desc) gpxtrack += '		<desc>' + geoJSON.properties.desc.encodeHTML() + '</desc>\n';
if(geoJSON.properties.src) gpxtrack += '		<src>' + geoJSON.properties.src + '</src>\n';
if(geoJSON.properties.link) gpxtrack += '		<link>' + geoJSON.properties.link + '</link>\n';
if(geoJSON.properties.number) gpxtrack += '		<number>' + geoJSON.properties.number + '</number>\n';
if(geoJSON.properties.type) gpxtrack += '		<type>' + geoJSON.properties.type + '</type>\n';
if(geoJSON.properties.extensions) gpxtrack += '		<extensions>' + geoJSON.properties.extensions + '</extensions>\n';

if(createTrk) gpxtrack += '		<trkseg>\n'; 	// рисуем трек
for (var i = 0; i < geoJSON.geometry.coordinates.length; i++) {
	if(createTrk) gpxtrack += '			<trkpt '; 	// рисуем трек
	else gpxtrack += '		<rtept '; 	// рисуем маршрут
	gpxtrack += 'lat="' + geoJSON.geometry.coordinates[i][1] + '" lon="' + geoJSON.geometry.coordinates[i][0] + '">';
	if(createTrk) gpxtrack += '</trkpt>\n'; 	// рисуем трек
	else gpxtrack += '</rtept>\n'; 	// рисуем маршрут
}
if(createTrk) gpxtrack += '		</trkseg>\n'; 	// рисуем трек

if(createTrk) gpxtrack += '	</trk>\n'; 	// рисуем трек
else gpxtrack += '	</rte>\n'; 	// рисуем маршрут
gpxtrack += '</gpx>';
return gpxtrack;
} // end function toGPX
    
String.prototype.encodeHTML = function () {
    return this.replace(/&/g, '&amp;')
               .replace(/</g, '&lt;')
               .replace(/>/g, '&gt;')
               .replace(/"/g, '&quot;')
               .replace(/'/g, '&apos;');
};
