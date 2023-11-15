"use strict"
/* Функции
listPopulate(listObject,dirURI,chkCurrent=false,withExt=true,onComplete=undefined)
getCookie(name)		возвращает cookie с именем name, если есть, если нет, то undefined
doSavePosition() 	Сохранение положения, списка показываемых карт, маршрутов и используемых параметров

selectMap(node) 	Выбор карты из списка имеющихся
deSelectMap(node) 	Прекращение показа карты, и возврат её в список имеющихся.
displayMap(mapname) Создаёт leaflet lauer с именем, содержащемся в mapname, и заносит его на карту
removeMap(mapname)
showMapsToggle()	переключает показ всех или выбранных карт в списке карт

selectTrack()		Выбор трека из списка имеющихся. 
deSelectTrack()		Прекращение показа трека, и возврат его в список имеющихся.
displayTrack()		рисует трек с именем в trackNameNode
displayRoute(routeNameNode)	рисует маршрут или места с именем routeName 
updateCurrTrack()

XYentryFields(element)	Генерация полей ввода списка тайлов для загрузки
loaderListPopulate(element)	Заполнение списка тайлов для загрузки путём клика по тайлу 
coloreSelectedTiles()
chkColoreSelectedTile(tileEvent)
createDwnldJob() 	создаёт файлы заданий и запускает загрузчик
chkLoaderStatus() 	запускает загрузчик

routeControlsDeSelect()
pointsControlsDisable()
pointsControlsEnable()
getGPXicon(gpxtype)
delShapes(realy,inLayer=null)	Удаляет полилинии в состоянии редактирования, если realy = true
tooggleEditRoute(e)
createEditableMarker(Icon)
doSaveMeasuredPaths()
doRestoreMeasuredPaths()
bindPopUptoEditable(layer)

saveGPX() 			Сохраняет на сервере маршрут из объекта currentRoute
toGPX(geoJSON,createTrk) Create gpx route or track (createTrk==true) from geoJSON object

String.prototype.encodeHTML = function ()

createSuperclaster(geoJSONpoints)
removeFromSuperclaster(superclasterLayer,point)
updateClasters()
updClaster(e)
realUpdClaster(layer)

nextColor(color,step)

centerMarkPosition() // Показ координат центра и переход по введённым
centerMarkUpdate()
centerMarkOn
centerMarkOff

flyByString(stringPos) Получает строку предположительно с координатами, и перемещает туда центр карты
updGeocodeList(nominatim)
doCopyToClipboard() Копирование в буфер обмена

doCurrentTrackName(liID)
doNotCurrentTrackName(liID)

loggingWait()		запускает/останавливает слежение за наличием пишущегося трека
loggingRun() 		запускает/останавливает запись трека
loggingCheck(logging='logging.php')	включает и выключает запись трека, а также проверяет, ведётся ли запись 

coverage()

MOBalarm()
clearCurrentStatus()	удаляет признак "текущий маркер" у всех маркеров мультислоя mobMarker
MOBclose()
delMOBmarker()
sendMOBtoServer()

distCirclesUpdate()	Устанавливает диаметр и подписи кругов дистанции
distCirclesToggler() включает/выключает показ окружностей дистанции по переключателю в интерфейсе

windSwitchToggler()
windSymbolUpdate(TPVdata)
realWindSymbolUpdate(direction=0,speed=0)

loadScriptSync(scriptURL)	Синхронная загрузка javascriptbearing(latlng1, latlng2)

bearing(latlng1, latlng2) {

atou(b64)		ASCII to Unicode (decode Base64 to original data)
utoa(data)		Unicode to ASCII (encode data to Base64)

realtime(dataUrl,fUpdate)

Классы
L.Control.CopyToClipboard
hasLayerRecursive(what)
eachLayerRecursive()

///////// for collision test purpose /////////
displayCollisionAreas(selfArea=null)
///////// for collision test purpose /////////
*/
/*
// определение имени файла этого скрипта, например, чтобы знать пути на сервере
const index = document.getElementsByTagName('script').length - 1; 	// это так, потому что эта часть сработает при загрузке скрипта, и он в этот момент - последний http://feather.elektrum.org/book/src.html
var galadrielmapScript = scripts[index];
//console.log(galadrielmapScript);
*/

function listPopulate(listObject,dirURI,chkCurrent=false,withExt=true,onComplete=undefined){
/*
*/
fetch(`listPopulate.php?dirname=${dirURI}`)	// запросим список файлов 
.then((response) => {
    return response.json();
})
.then(data => {
	//console.log('[listPopulate] data:',data);
	if(data){
		if(chkCurrent) currentTrackName = data.currentTrackName.substring(0, data.currentTrackName.lastIndexOf('.')) || data.currentTrackName;	// глобальная переменная
		if(data.filelist.length){
			const templateLi = listObject.querySelector('li[class="template"]');	// почему-то 'li[hidden]' не работает.
			listObject.querySelectorAll('li').forEach(li => {	// удалим из списка что там есть. delete использовать нельзя, потому что delete не уничтожает объекты, вопреки своему названию.
				if(li!=templateLi) {
					//console.log(li);
					li.remove();
					li = null;
				}
			});
			data.filelist.forEach(fileName => {
				if(!withExt) fileName = fileName.substring(0, fileName.lastIndexOf('.')) || fileName;
				let newLI = templateLi.cloneNode(true);
				newLI.classList.remove("template");
				newLI.id = fileName;
				newLI.innerText = fileName;
				newLI.hidden=false;
				listObject.append(newLI);
				if(chkCurrent && fileName == currentTrackName) {
					// Сделаем текущим и запустим слежение
					doCurrentTrackName(fileName);	// обязательно после append, ибо вне дерева элементы не ищутся. JavaScript -- коллекция нелепиц.
				}
			});
		};
	};
	//console.log('[listPopulate] listObject:',listObject,'onComplete:',onComplete);
	if(onComplete) onComplete();	// здесь надо }).then(что?=>{if(onComplete) onComplete();}) ?
})
.catch( (err) => {	// сюда придёт любая ошибка после fetch
	console.log(`Error get ${dirURI} files list:`,err.message);
});
} // end function listPopulate

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
document.cookie = "GaladrielMapPosition="+pos+"; expires="+expires+"; path=/; samesite=Lax";
document.cookie = "GaladrielMapZoom="+zoom+"; expires="+expires+"; path=/; samesite=Lax";
//alert('Сохранение параметров '+pos+zoom);
// Сохранение показываемых карт
let openedNames = [];
for (let i = 0; i < mapDisplayed.children.length; i++) { 	// для каждого потомка списка mapDisplayed
	openedNames[i] = mapDisplayed.children[i].id; 	// 
}
openedNames = JSON.stringify(openedNames);
document.cookie = "GaladrielMaps="+openedNames+"; expires="+expires+"; path=/; samesite=Lax";
// Сохранение показываемых маршрутов
openedNames = [];
for (let i = 0; i < routeDisplayed.children.length; i++) { 	// для каждого потомка списка mapDisplayed
	openedNames[i] = routeDisplayed.children[i].innerHTML; 	// 
}
openedNames = JSON.stringify(openedNames);
document.cookie = "GaladrielRoutes="+openedNames+"; expires="+expires+"; path=/; samesite=Lax";
// Сохранение переключателей и параметров
document.cookie = "GaladrielcurrTrackSwitch="+Number(currTrackSwitch.checked)+"; expires="+expires+"; path=/; samesite=Lax"; 	// переключатель currTrackSwitch
document.cookie = "GaladrielloggingSwitch="+Number(loggingSwitch.checked)+"; expires="+expires+"; path=/; samesite=Lax"; 	// переключатель loggingSwitch, включение записи трека. Сохраняется, чтобы знать, что именно этот клиент включил запись.
document.cookie = "GaladrielSelectedRoutesSwitch="+Number(SelectedRoutesSwitch.checked)+"; expires="+expires+"; path=/; samesite=Lax"; 	// переключатель SelectedRoutesSwitch
document.cookie = "GaladrielminWATCHinterval="+minWATCHinterval+"; expires="+expires+"; path=/; samesite=Lax"; 	// 
document.cookie = "GaladrielshowMapsList="+JSON.stringify(showMapsList)+"; expires="+expires+"; path=/; samesite=Lax"; 	// 
//console.log('Position, layers and options saved');
} // end function doSavePosition

// Функции выбора - удаления карт
function selectMap(node) { 	
// Выбор карты из списка имеющихся. Получим объект
node.classList.remove("showedMapName");
node.hidden = false;
mapDisplayed.insertBefore(node,mapDisplayed.firstChild); 	// из списка доступных в список показываемых (объект, на котором событие, добавим в конец потомков mapDisplayed)
node.onclick = function(event){deSelectMap(event.currentTarget);};
displayMap(node.id);
}

function deSelectMap(node) {
// Прекращение показа карты, и возврат её в список имеющихся. Получим объект
//alert(node);
let li = null;
for (let i = 0; i < mapList.children.length; i++) { 	// для каждого потомка списка mapList
	li = mapList.children[i]; 	// взять этого потомка
	let childTitle = li.innerHTML;
	//console.log('[deSelectMap] childTitle=',childTitle);
	if (childTitle > node.innerHTML) { 	// если наименование потомка дальше по алфавиту, чем наименование того, на что кликнули
		break;
	}
	li = null;
}
mapList.insertBefore(node,li); 	// перенесём перед тем, на котором обломался цикл, или перед концом
node.onclick = function(event){selectMap(event.currentTarget);};
if(showMapsToggler.innerHTML == showMapsTogglerTXT[0]){	// текущий режим - "только избранные"
	if(!showMapsList.includes(node.id)) node.hidden = true;
}
else {	// текущий режим - "все карты"
	if(showMapsList.includes(node.id)) node.classList.add("showedMapName");
}
removeMap(node.id);
}

function displayMap(mapname) {
/* Создаёт leaflet lauer с именем, содержащемся в mapname, и заносит его на карту
 Делает запрос к askMapParm.php для получения параметров карты
 Если в параметрах карты есть проекция, и она EPSG3395, 
 или в имени карты есть EPSG3395 - делает слой в проекции с пересчётом с помощью L.tileLayer.Mercator
*/
mapname=mapname.trim(mapname);
// Всегда будем спрашивать параметры карты
let mapParm = new Array(); 	// переменная для параметров карты
const xhr = new XMLHttpRequest();
xhr.open('GET', 'askMapParm.php?mapname='+mapname, false); 	// Подготовим синхронный запрос
xhr.send();
if (xhr.status == 200) { 	// Успешно
	try {
		mapParm = JSON.parse(xhr.responseText); 	// параметры карты
		//alert('Получены параметры карты \n'+tileCacheURIthis);
	}
	catch(err) { 	// у карты не было параметров. Например, мы не используем GaladrielCache.
		return;
	}
}
else return;
// javascript в загружаемом источнике на открытие карты
//console.log('[displayMap] mapParm:',mapParm);
if(mapParm['data'] && mapParm['data']['javascriptOpen']) eval(mapParm['data']['javascriptOpen']);
// Загружаемая карта - многослойная?
if(Array.isArray(additionalTileCachePath)) { 	// глобальная переменная - дополнительный кусок пути к талам между именем карты и /z/x/y.png Используется в версионном кеше, например, в погоде. Без / в конце, но с / в начале, либо пусто. Например, Weather.php
	let currZoom; 
	//console.log('[displayMap] mapname=',mapname,'savedLayers[mapname]',savedLayers[mapname]);
	if(savedLayers[mapname]) {
		if(savedLayers[mapname].options.zoom) currZoom = savedLayers[mapname].options.zoom;
		savedLayers[mapname].remove();
	}
	savedLayers[mapname]=L.layerGroup();
	if(currZoom) savedLayers[mapname].options.zoom = currZoom;
	// Потому что в javascript при закрытии карты могут указать dditionalTileCachePath = []; (как, собственно, и было в Weather) и тогда после закрытия такой карты открыть другую не получится
	if(!additionalTileCachePath.length) additionalTileCachePath.push('');
	for(let addPath of additionalTileCachePath) {
		let mapnameThis = mapname+addPath; 	// 
		let tileCacheURIthis = tileCacheURI.replace('{map}',mapnameThis); 	// глобальная переменная
		if(mapParm['ext'])	tileCacheURIthis = tileCacheURIthis.replace('{ext}',mapParm['ext']); 	// при таком подходе можно сделать несколько слоёв с одним запросом параметров
		//console.log(tileCacheURIthis);
		//console.log('mapname=',mapname,savedLayers[mapname]);
		if((mapParm['epsg']&&String(mapParm['epsg']).indexOf('3395')!=-1)||(mapname.indexOf('EPSG3395')!=-1)) {
			//alert('on Ellipsoide')
			savedLayers[mapname].addLayer(L.tileLayer.Mercator(tileCacheURIthis, {minZoom:mapParm.minZoom,maxZoom:mapParm.maxZoom}));
		}
		else if(mapParm['mapboxStyle']) { 	// векторные тайлы, mapboxStyle добавляется в askMapParm.php, и содержит uri стиля
			if(typeof L.mapboxGL !== 'undefined'){
				let link = document.createElement('link');
				link.type = 'text/css';
				link.href = 'style.css';
				link.rel = 'mapbox-gl-js/dist/mapbox-gl.css';
				document.head.appendChild(link);
				if(!loadScriptSync("mapbox-gl-js/dist/mapbox-gl.js")) return;
				if(!loadScriptSync("mapbox-gl-leaflet/leaflet-mapbox-gl.js")) return;
			}
			savedLayers[mapname].addLayer(L.mapboxGL({style: mapParm['mapboxStyle'],minZoom:mapParm.minZoom}));
		}
		else {
			savedLayers[mapname].addLayer(L.tileLayer(tileCacheURIthis, {minZoom:mapParm.minZoom,maxZoom:mapParm.maxZoom}));
		}
	}
}
else {
	let mapnameThis = additionalTileCachePath ? mapname+additionalTileCachePath : mapname;
	let tileCacheURIthis = tileCacheURI.replace('{map}',mapnameThis); 	// глобальная переменная
	if(mapParm['ext'])	tileCacheURIthis = tileCacheURIthis.replace('{ext}',mapParm['ext']); 	// при таком подходе можно сделать несколько слоёв с одним запросом параметров
	//console.log(tileCacheURIthis);
	if((mapParm['epsg']&&String(mapParm['epsg']).indexOf('3395')!=-1)||(mapname.indexOf('EPSG3395')!=-1)) {
		if(!savedLayers[mapname])	savedLayers[mapname] = L.tileLayer.Mercator(tileCacheURIthis, {minZoom:mapParm.minZoom,maxZoom:mapParm.maxZoom});
	}
	else if(mapParm['mapboxStyle']) { 	// векторные тайлы, mapboxStyle добавляется в askMapParm.php, и содержит uri стиля
		//console.log("[displayMap] typeof L.mapboxGL=",typeof L.mapboxGL,L.mapboxGL);
		if(typeof L.mapboxGL === 'undefined'){
			let link = document.createElement('link');
			link.type = 'text/css';
			link.href = 'style.css';
			link.rel = 'mapbox-gl-js/dist/mapbox-gl.css';
			document.head.appendChild(link);
			if(!(mapboxGLscript=loadScriptSync("mapbox-gl-js/dist/mapbox-gl.js"))) return;	// Нахрена присваивать глобальной переменной, которая нигде не используется -- неясно, но без этого возникает ошибка при закрытии карты.
			if(!(mapboxLeafletscript=loadScriptSync("mapbox-gl-leaflet/leaflet-mapbox-gl.js"))) return;
			//console.log("[displayMap] функции загружены");
		}
		if(!savedLayers[mapname])	savedLayers[mapname] = L.mapboxGL({style:mapParm['mapboxStyle'],minZoom:mapParm.minZoom});
	}
	else {
		if(!savedLayers[mapname])	savedLayers[mapname] = L.tileLayer(tileCacheURIthis, {minZoom:mapParm.minZoom,maxZoom:mapParm.maxZoom});
	}
}
//console.log(savedLayers[mapname]);
// установим текущий масштаб в пределах возможного для загружаемой карты
if(! savedLayers[mapname].options.zoom) {
	let currZoom = map.getZoom();
	if(mapParm['maxZoom'] < currZoom) {
		map.setZoom(mapParm['maxZoom']);
		savedLayers[mapname].options.zoom = currZoom;
	}
	else if(mapParm['minZoom'] > currZoom) { 
		map.setZoom(mapParm['minZoom']);
		savedLayers[mapname].options.zoom = currZoom;
	}
	else savedLayers[mapname].options.zoom = false;
}
// javascript в загружаемом источнике на закрытие карты
if(mapParm['data'] && mapParm['data']['javascriptClose']) savedLayers[mapname].options.javascriptClose = mapParm['data']['javascriptClose'];
// Наконец, покажем
savedLayers[mapname].addTo(map);
} // end function displayMap

function removeMap(mapname) {
mapname=mapname.trim();
if(!savedLayers[mapname]) return;	// например, в списке есть трек, но gpx был кривой, и слой не был создан
if(savedLayers[mapname].options.javascriptClose) eval(savedLayers[mapname].options.javascriptClose);
if(savedLayers[mapname].options.zoom) { 
	map.setZoom(savedLayers[mapname].options.zoom); 	// вернём масштаб как было
	savedLayers[mapname].options.zoom = false;
}
savedLayers[mapname].remove(); 	// удалим слой с карты
//savedLayers[mapname] = null; 	// удалим сам слой. Но это не надо, ибо включение/выключение отображения слоёв должно быть быстро, и обычно их не надо повторно получать с сервера
} // end function removeMap

function showMapsToggle(all=false){
/*	переключает показ всех или выбранных карт в списке карт */
//console.log('[showMapsToggle] showMapsList:',showMapsList);
if(all || showMapsToggler.innerHTML == showMapsTogglerTXT[0]){	// текущий режим - "избранные карты" (на кнопке надпись: "все карты")
	for(let mapLi of mapList.children){
		//console.log('покажем все карты',mapLi.id);
		mapLi.hidden = false;	// покажем все карты
		if(showMapsList.includes(mapLi.id)){	// избранная карта
			mapLi.classList.add("showedMapName");
		}
	}
	showMapsToggler.innerHTML = showMapsTogglerTXT[1];	// сменим режим на "все карты"
}
else {	// текущий режим - "все карты" - покажем только избранные
	for(let mapLi of mapList.children){
		//console.log('покажем только избранные',mapLi.id);
		mapLi.hidden = false;	// при старте они все скрытые
		if(!showMapsList.includes(mapLi.id)){	// карта не в списке избранных
			mapLi.hidden = true;	// не покажем карту
		}
		mapLi.classList.remove("showedMapName");
	}
	showMapsToggler.innerHTML = showMapsTogglerTXT[0];	// сменим режим на "избранные карты"
}
} // end function showMapsToggle


// Функции выбора - удаления треков
function selectTrack(node,trackList,trackDisplayed,displayTrack) { 	
/* Выбор трека из списка имеющихся. 
node - объект li, элемент списка имеющихся, который выбрали
trackList - объект ul, список имеющихся
trackDisplayed - объект ul, список выбранных
displayTrack - функция показывания того, что соответствует выбранному элементу
global deSelectTrack() currentTrackShowedFlag
*/
//console.log(trackDisplayed.firstChild);
trackDisplayed.insertBefore(node,trackDisplayed.firstChild); 	// из списка доступных в список показываемых (объект, на котором событие, добавим в конец потомков mapDisplayed)
node.onclick = function(event){deSelectTrack(event.currentTarget,trackList,trackDisplayed,displayTrack);};
if(node.title.toLowerCase().indexOf("current")!= -1) {	// текущий трек
	currentTrackShowedFlag = 'loading'; 	// укажем, что трек сейчас загружается
	startCurrentTrackUpdateProcess();	// запустим обновление трека
}
//console.log('[selectTrack] node.title=',node.title,currentTrackShowedFlag);
displayTrack(node); 	// создадим трек
} // end function selectTrack

function deSelectTrack(node,trackList,trackDisplayed,displayTrack) {
/* Прекращение показа трека, и возврат его в список имеющихся. Получим объект
node - объект li, элемент списка показываемых, который выбрали для непоказывания
trackList - объект ul, список имеющихся, куда надо вернуть node
global selectTrack()
*/
if(node.title.toLowerCase().indexOf("current")!= -1) {	// текущий трек
	if(!currTrackSwitch.checked){	// Текущий трек не всегда показывается
		if(currentTrackUpdateProcess) {
			clearInterval(currentTrackUpdateProcess);	
			currentTrackUpdateProcess = null;
		}
		if(currentWaitTrackUpdateProcess) {	// хотя его и не должно быть
			clearInterval(currentWaitTrackUpdateProcess);	// 
			currentWaitTrackUpdateProcess = null;
		}
	}
};

var li = null;
for (var i = 0; i < trackList.children.length; i++) { 	// для каждого потомка списка trackList
	li = trackList.children[i]; 	// взять этого потомка
	var childTitle = li.innerHTML;
	if (childTitle > node.innerHTML) { 	// если наименование потомка дальше по алфавиту, чем наименование того, на что кликнули
		break;
	}
	li = null;
}
trackList.insertBefore(node,li); 	// перенесём перед тем, на котором обломался цикл, или перед концом
//console.log(node);
node.onclick = function(event){selectTrack(event.currentTarget,trackList,trackDisplayed,displayTrack);};
removeMap(node.innerHTML);
}

function displayTrack(trackNameNode) {
/* рисует трек с именем в trackNameNode
global trackDirURI, window, currentTrackName
*/
var trackName = trackNameNode.innerText.trim();
//console.log('[displayTrack] trackName=',trackName,'currentTrackName=',currentTrackName,'savedLayers[trackName]',savedLayers[trackName]);
if( savedLayers[trackName]) {
	//console.log('[displayTrack] Рисуем на карте из кеша trackName=',trackName);
	savedLayers[trackName].addTo(map); 	// нарисуем его на карте. Текущий трек всегда перезагружаем в updateCurrTrack
}
else {
	// просто спрашиваем у сервера файл, там не ответчик
	var options = {featureNameNode : trackNameNode};
	var xhr = new XMLHttpRequest();
	//console.log('[displayTrack] Загружаем новый файл trackName=',trackDirURI+'/'+trackName+'.gpx');
	xhr.open('GET', encodeURI(trackDirURI+'/'+trackName+'.gpx'), true); 	// Подготовим асинхронный запрос
	xhr.overrideMimeType( "application/gpx+xml; charset=UTF-8" ); 	// тупые уроды из Mozilla считают, что если не указан mime type ответа -- то он text/xml. Файлы они, очевидно, не скачивают.
	xhr.send();
	xhr.onreadystatechange = function() { // trackName - внешняя
		if (this.readyState != 4) return; 	// запрос ещё не завершился, покинем функцию
		if (this.status != 200) { 	// запрос завершлся, но неудачно
			console.log('[displayTrack] To request file '+trackDirURI+'/'+trackName+' server return '+this.status);
			if(trackNameNode.title.toLowerCase().indexOf("current")!= -1) {	// текущий трек
				currentTrackShowedFlag = 'error'; 	// укажем, что с треком что-то не то
			}
			return; 	// что-то не то с сервером
		}
		// В злопаршивом Javascript символ /00 пробельным не является
		//console.log('|'+this.responseText.slice(-10)+'|');
		let str = this.responseText.replace(/\0+|\0+/g, '').trim().slice(-12);
		//console.log('[displayTrack] |'+str+'|');
		if(!str) return;
		if(str.indexOf('</gpx>') == -1) {	// может получиться кривой gpx -- по разным причинам
			//console.log('кривой gpx',str);
			// незавершённый gpx - дополним до конца. Поэтому скачиваем сами, а не omnivore
			let responseText = this.responseText.replace(/\0+|\0+/g, '').trim();	// потому что this.responseText не строка, а getter-only property, хрен его знает, что это значит и зачем.
			if(str.endsWith('</trkpt>')) responseText += '\n  </trkseg>\n </trk>\n</gpx>';	// точку оно всегда? успевает записать
			else if(str.endsWith('</trkseg>')) responseText += '\n </trk>\n</gpx>';
			else responseText += '\n</gpx>';	// на самом деле, здесь </metadata>, т.е., gpxlogger запустился, но ничего не пишет: нет gpsd, нет спутников, нет связи...
			savedLayers[trackName] = omnivore.gpx.parse(responseText,options);
		}
		else {
			savedLayers[trackName] = omnivore.gpx.parse(this.responseText,options); 	// responseXML иногда почему-то кривой
		}
		//console.log(savedLayers[trackName]);
		savedLayers[trackName].addTo(map); 	// нарисуем его на карте
	}
}
} // end function displayTrack

function displayRoute(routeNameNode) {
/* рисует маршрут или места с именем routeName 
global routeDirURI map window
*/
var routeName = routeNameNode.innerText.trim();
var options = {featureNameNode : routeNameNode};
if( savedLayers[routeName]) {
	savedLayers[routeName].addTo(map); 	// нарисуем его на карте. 
}
else {
	var routeType =  routeName.slice((routeName.lastIndexOf(".") - 1 >>> 0) + 2).toLowerCase(); 	// https://www.jstips.co/en/javascript/get-file-extension/ потому что там нет естественного пути
	//console.log('[displayRoute] routeName=',routeName,'routeType=',routeType);
	switch(routeType) {
	case 'gpx':
		savedLayers[routeName] = omnivore.gpx(routeDirURI+'/'+routeName,options);
		break;
	case 'kml':
		savedLayers[routeName] = omnivore.kml(routeDirURI+'/'+routeName,options);
		break;
	case 'csv':
		savedLayers[routeName] = omnivore.csv(routeDirURI+'/'+routeName,options);
		break;
	}
	//console.log('[displayRoute] routeName=',routeName,'savedLayers[routeName]:',savedLayers[routeName]);
	if( savedLayers[routeName]) {
		if(!('properties' in savedLayers[routeName])) savedLayers[routeName].properties = {};
		savedLayers[routeName].properties.fileName = routeName;	// имя файла. А нафига? А чтобы потом понять, что объект загружен из файла
		savedLayers[routeName].addTo(map);
	}
}
} // end function displayRoute

function updateCurrTrack() {
// Получим GeoJSON - ломаную из скольких-то последних путевых точек, или false, если с последнего
// обращения нет новых точек
// в формате GeoJSON
//console.log('[updateCurrTrack]',currentTrackServerURI,currentTrackName);
var xhr = new XMLHttpRequest();
xhr.open('GET', encodeURI(currentTrackServerURI+'?currTrackName='+currentTrackName), true); 	// Подготовим асинхронный запрос
xhr.send();
xhr.onreadystatechange = function() { // 
	if (this.readyState != 4) return; 	// запрос ещё не завершился, покинем функцию
	if (this.status != 200) { 	// запрос завершлся, но неудачно
		//console.log('Server return '+this.status+'\ncurrentTrackServerURI='+currentTrackServerURI+'\ncurrTrackName='+currentTrackName+'\n\n');
		console.log('To [updateCurrTrack] server return '+this.status+' instead '+currentTrackName+' last segment.');
		if(typeof loggingIndicator != 'undefined'){ 	// лампочка в интерфейсе
			loggingIndicator.style.color='red';
			loggingIndicator.innerText='\u2B24';
		}
		return; 	// что-то не то с сервером
	}
	//console.log(this.responseText);
	let resp = {};
	try {
		resp = JSON.parse(this.responseText);
	}
	catch(err) {
		if(this.responseText.trim()) console.log('Bad data to update current track:'+this.responseText+';',err.message)
	}
	//console.log('[updateCurrTrack]',resp);
	if(resp.logging){ 	// лог пишется
		if(typeof loggingIndicator != 'undefined'){ 	// лампочка в интерфейсе
			loggingIndicator.style.color='green';
			loggingIndicator.innerText='\u2B24';
		}
		if(resp.pt) { 	// есть данные
			if(savedLayers[currentTrackName]) {	// может не быть, если, например, показ треков выключили, но выполнение currentTrackUpdate уже запланировано. Вообще-то, так быть не может, но сообщение об отсутствии иногда наблюдается. А иногда -- нет.
				//if(typeof savedLayers[currentTrackName].getLayers  == 'function') { 	// это layerGroup
				if(savedLayers[currentTrackName] instanceof L.LayerGroup) { 	// это layerGroup
					savedLayers[currentTrackName].getLayers()[0].addData(resp.pt); 	// добавим полученное к слою с текущим треком
					//console.log(savedLayers[currentTrackName].getLayers()[0]);
				}
				else savedLayers[currentTrackName].addData(resp.pt); 	// добавим полученное к слою с текущим треком
			}
		}
	}
	else { 	// лог не пишется
		if(typeof loggingIndicator != 'undefined'){
			// лампочка и переключатель в интерфейсе
			if(loggingSwitch.checked){ 	// этот клиент сказал писать трек, состояние loggingSwitch восстанавливается из куки в index.php
				loggingIndicator.style.color='red';
				loggingIndicator.innerText='\u2B24';
				loggingRun();	// попытаемся запустить запись трека
			}
			else {
				loggingIndicator.style.color='';
				loggingIndicator.innerText='';
				if(currentWaitTrackUpdateProcess){
					clearInterval(currentWaitTrackUpdateProcess);	
					console.log('[updateCurrTrack] Не должно быть currentWaitTrackUpdateProcess, но он был. Убили, запускаем.');
				}
				if(currTrackSwitch.checked) startCurrentWaitTrackUpdateProcess();	// Текущий трек всегда показывается
			}
		}
	}
}
} // end function updateCurrTrack

// Загрузчик и подготовка задания
function XYentryFields(element){
/* Генерация полей ввода списка тайлов для загрузки
element - второе поле input номера тайла
*/
//console.log(element.parentNode);
const xElement = element.parentNode.previousElementSibling.getElementsByTagName('input')[0];
const x = xElement.value;
const y = element.value;
if(x && y){
	const tileId = 'gridTile_'+parseInt(dwnldJobZoom.innerText)+'_'+x+'_'+y;
	const tile = document.getElementById(tileId);
	if(tile) tile.classList.add('selectedTile');	// выделим тайл, что он указан

	let newXinput = element.parentNode.previousElementSibling.cloneNode(true); 	// клонируем div с x
	newXinput.getElementsByTagName('input')[0].value = ''; 	// очистим поле ввода
	let newYinput = element.parentNode.cloneNode(true); 	// клонируем div с y
	newYinput.getElementsByTagName('input')[0].value = ''; 	// очистим поле ввода
	element.parentNode.parentNode.insertBefore(newXinput,element.parentNode.nextElementSibling); 	// вставляем после последнего. Да, вот так через задницу, потому что это javascript
	element.parentNode.parentNode.insertBefore(newYinput,newXinput.nextElementSibling);
	newXinput.getElementsByTagName('input')[0].focus(); 	// установим курсор ввода
}
else {
	xElement.value = '';
	element.value = '';
	xElement.focus();
	tileGrid.redraw();
}
// Узнаем, есть ли так или иначе указанные тайлы
const tileXs = dwnldJob.getElementsByClassName("tileX");
const tileYs = dwnldJob.getElementsByClassName("tileY");
downJob = false;
for (var k = 0; k < tileXs.length; k++) {
	if(tileXs[k].value && tileYs[k].value){
		downJob = true; 	// выставим флаг, что идёт подготовка задания на скачивание
		break;
	}
}
if( !downJob) dwnldJobZoom.innerHTML = map.getZoom(); 	// текущий масштаб отобразим на панели скачивания
} // end function XYentryFields

function loaderListPopulate(element){
/* Заполнение списка тайлов для загрузки путём клика по тайлу 
element - это div с подписью номера тайла, по нему кликают
*/
//console.log(element);
if(parseInt(dwnldJobZoom.innerText) != map.getZoom()) return;	// текущий масштаб -- не тот, для которого начали создавать задание
let e,x,y,z;
//[x,y] = element.innerText.split("\n")[1].split('/').map(item=>parseInt(item));
[e,z,x,y] = element.parentElement.id.split("_");	// gridTile_12_2474_1288, квадрат сетки
//console.log('z=',z,'x=',x,'y=',y);
const tileXs = dwnldJob.getElementsByClassName("tileX");
const tileYs = dwnldJob.getElementsByClassName("tileY");
if(element.parentElement.classList.contains('selectedTile')){	// квадрат сетки выделен, кликнутый тайл должен быть в списке
	element.parentElement.classList.remove('selectedTile');	// снимем выделение
	for (var k = 0; k < tileXs.length; k++) {	// проверим весь список, ибо номер мог быть внесён несколько раз руками
		if((tileXs[k].value==x) && (tileYs[k].value==y)){	// найдём номер тайла в списке тайлов
			tileXs[k].value = '';	// удалим этот номер из списка
			tileYs[k].value = '';
		}
	}
}
else {	// кликнутого тайла, вероятно, нет в списке
	const lastX = tileXs[tileXs.length-1];	// заполним последние поля списка номером кликнутого тайла
	const lastY = tileYs[tileYs.length-1];
	lastX.value = x;
	lastY.value = y;
	XYentryFields(lastY);
}
} // end function loaderListPopulate

function coloreSelectedTiles(){
const zoom = parseInt(dwnldJobZoom.innerText);
if(zoom != map.getZoom()) return;	// текущий масштаб -- не тот, для которого создано задание
const tileXs = dwnldJob.getElementsByClassName("tileX");
const tileYs = dwnldJob.getElementsByClassName("tileY");
for (var k = 0; k < tileXs.length; k++) {
	if(tileXs[k].value && tileYs[k].value){
		const tileId = 'gridTile_'+zoom+'_'+tileXs[k].value+'_'+tileYs[k].value;
		const tile = document.getElementById(tileId);
		if(tile) tile.classList.add('selectedTile');
	}
}
} // end function coloreSelectedTiles
function chkColoreSelectedTile(tileEvent){
const zoom = parseInt(dwnldJobZoom.innerText);
//console.log('zoom=',zoom,'z=',tileEvent.coords.z);
if(zoom != tileEvent.coords.z) return;	// текущий масштаб -- не тот, для которого создано задание
//console.log('zoom=',zoom,'z=',tileEvent.coords.z,tileEvent.tile, tileEvent.coords);
const tileXs = dwnldJob.getElementsByClassName("tileX");
const tileYs = dwnldJob.getElementsByClassName("tileY");
downJob = false;
for (var k = 0; k < tileXs.length; k++) {
	if(tileXs[k].value && tileYs[k].value){
		const tileId = 'gridTile_'+zoom+'_'+tileXs[k].value+'_'+tileYs[k].value;
		const tile = document.getElementById(tileId);
		if(tile) tile.classList.add('selectedTile');
		downJob = true; 	// выставим флаг, что идёт подготовка задания на скачивание
	}
}
if( !downJob) dwnldJobZoom.innerHTML = map.getZoom(); 	// текущий масштаб отобразим на панели скачивания
} // end function chkColoreSelectedTile

function createDwnldJob() {
/* Собирает задания на загрузку: для каждой карты кладёт на сервер csv с номерами тайлов текущего масштаба.
Считается, что номера тайлов указываются на сфере */
//alert('submit '+mapDisplayed.children.length+' maps');
var tileXs = dwnldJob.getElementsByClassName("tileX");
var tileYs = dwnldJob.getElementsByClassName("tileY");
var zoom = dwnldJobZoom.innerText;
var XYs = '', XYsE = '', xhr = [];
for (var i = 0; i < mapDisplayed.children.length; i++) { 	// для каждого потомка списка mapDisplayed
	var mapname = mapDisplayed.children[i].id; 	// 
	if(mapname.indexOf('EPSG3395')==-1) {	// карта - на сфере, пишем тайлы как есть
		if(!XYs.length) {
			for (var k = 0; k < tileXs.length; k++) {
				if(tileXs[k].value && tileYs[k].value) 	XYs += tileXs[k].value+','+tileYs[k].value+'\n';
			}
		}
		//console.log(XYs);
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
		//console.log(XYsE);
		var uri = 'loaderJob.php?jobname='+mapname+'.'+zoom+'&xys='+XYsE;
	}
	//console.log(uri);
	//continue;
	xhr[i] = new XMLHttpRequest();
	xhr[i].open('GET', encodeURI(uri), true); 	// Подготовим асинхронный запрос
	xhr[i].send();
	xhr[i].onreadystatechange = function() { // 
		if (this.readyState != 4) return; 	// запрос ещё не завершился
		if (this.status != 200) return; 	// что-то не то с сервером
		let responseText = this.responseText.split(';');
		//console.log('[createDwnldJob] responseText:',this.responseText);
		if(responseText[0] == '0') { 	// первым должен идти код возврата eval запуска загрузчика
			loaderIndicator.style.color='green';
			//loaderIndicator.innerText='\u263A';
		}
		else {
			loaderIndicator.style.color='red';
			//loaderIndicator.innerText='\u2639';
		}
		dwnldJobList.innerHTML += '<li>' + responseText[1] + '</li>\n';
	}
}
} 	// end function createDwnldJob

function chkLoaderStatus(restartLoader=0) {
/*  */
let xhr = new XMLHttpRequest();
xhr.open('GET', encodeURI('chkLoaderStatus.php?restartLoader='+restartLoader), true); 	// Подготовим асинхронный запрос
xhr.send();
xhr.onreadystatechange = function() { // 
	if (this.readyState != 4) return; 	// запрос ещё не завершился
	if (this.status != 200) return; 	// что-то не то с сервером
	//console.log('[chkLoaderStatus] this.response=',this.response);
	let {loaderRun,jobsInfo} = JSON.parse(this.response);
	//console.log('[chkLoaderStatus]',loaderRun,jobsInfo,JSON.stringify(jobsInfo));
	
	dwnldJobList.innerHTML = '';
	//loaderIndicator.innerText='\u2B24 ';
	if((JSON.stringify(jobsInfo)!=='[]') && !loaderRun){	// есть задания, но загрузчик не запущен. Менее через жопу выяснить, не пуст ли объект в этом кривом языке нельзя. При том, что в PHP оно всегда array.
	//if(jobsInfo.length && !loaderRun){	// есть задания, но загрузчик не запущен
		loaderIndicator.style.color='red';
		//loaderIndicator.innerText='\u2639';
		loaderIndicator.onclick=chkLoaderStatus(true);

		let liS = '';
		for(let jobName in jobsInfo){
			liS += `<li  ><span>${jobName} </span><span style='font-size:75%;'>${jobsInfo[jobName]}%</span></li>`;
		}
		dwnldJobList.innerHTML = liS;
	}
	else if(loaderRun){	// загрузчик запущен
		loaderIndicator.style.color='green';
		//loaderIndicator.innerText='\u263A';

		let liS = '';
		for(let jobName in jobsInfo){
			liS += `<li  ><span>${jobName} &nbsp; </span><span style='font-size:115%;'>${jobsInfo[jobName]}%</span></li>`;
		}
		dwnldJobList.innerHTML = liS;
	}
	else {	// загрузчик не должен быть запущен
		loaderIndicator.style.color='gray';
		//loaderIndicator.innerText=' ';
	}
}
} // end function chkLoaderStatus


// Функции рисования маршрутов
function routeControlsDeSelect() {
// сделаем невыбранными кнопки управления рисованием маршрута. Они должны быть и так не выбраны, но почему-то...
for(let element of document.getElementsByName('routeControl')){
	element.checked=false;
	element.disabled=true;
}   
} // end function routeControlsDeSelect

function pointsControlsDisable(){
for(let button of pointsButtons.querySelectorAll('button')){	// кнопки установки маркеров
	button.disabled = true;
};
}; // end function pointsControlsDisable

function pointsControlsEnable(){
for(let button of pointsButtons.querySelectorAll('button')){	// кнопки установки маркеров
	let gpxtype = button.id.substring(9);	// id начинаются с "ButtonSet", а дальше, например, point: ButtonSetpoint
	//console.log('[pointsControlsEnable] button',gpxtype,button);
	button.onclick = function (event) {createEditableMarker(getGPXicon(gpxtype));};
	button.disabled = false;
};
}; // end function pointsControlsEnable

function getGPXicon(gpxtype){
/* вообще-то, здесь должно быть обращение к iconServer из leaflet-omnivore, но пока так*/
let iconName = gpxtype+'Icon';
return window[iconName];
} // end function getGPXicon

function delShapes(realy,inLayer=null) {
/* Удаляет полилинии в состоянии редактирования, если realy = true
возвращает число таких объектов.
Полилинии находятся в L.LayerGroup currentRoute. Мы не знаем, что такое currentRoute, и это
может быть как dravingLines (L.LayerGroup с нарисованными локально объектами), так и 
ранее загруженный svg. При этом, как минимум в случае svg, эта L.LayerGroup сама состоит 
(только) из L.LayerGroup, в которых, в свою очередь, находится искомое.
*/
if(!inLayer) inLayer = currentRoute;
//console.log('[delShapes] inLayer:',inLayer);
let edEnShapesCntr=0;
let needUpdateSuperclaster = false;
for(let layer of inLayer.getLayers()){
	if(layer instanceof L.LayerGroup) { 	// это layerGroup
	//if("getLayers" in layer) { 	// это layerGroup
		edEnShapesCntr += delShapes(realy,layer);
	}
	else {	// это что-то ещё
		if(typeof layer.editEnabled === 'function' && layer.editEnabled()){	// оно редактируется сейчас
			edEnShapesCntr++;
			//console.log('[delShapes] editabled layer',layer);
			if(realy) {
				//if('getLatLngs' in layer) layer.editor.deleteShapeAt(layer.getLatLngs()[0]);	// Мутный способ убрать слой с экрана, но я не вижу, как иначе.
				if(layer instanceof L.Path) {
					layer.editor.deleteShapeAt(layer.getLatLngs()[0]);	// Мутный способ убрать слой с экрана, но я не вижу, как иначе.
				}
				else {
					needUpdateSuperclaster = needUpdateSuperclaster || removeFromSuperclaster(inLayer,layer);	// могут быть кластеризованные точки, а так -- достаточно removeLayer
				}
				inLayer.removeLayer(layer);	// удалим слой из LayerGroup
				//console.log('[delShapes] из inLayer ',inLayer._leaflet_id,inLayer,'удалён объект',layer._leaflet_id,layer);
				layer = null;	// это приведёт к быстрому удалению объекта сборщиком мусора? Обычно оно не успевает...
			}
		}
	}
}
//console.log('[delShapes] needUpdateSuperclaster:',needUpdateSuperclaster);
if(needUpdateSuperclaster) updClaster(inLayer);	// обновим один раз за все удаления
return edEnShapesCntr;
}	// end function delShapes


function tooggleEditRoute(e) {
/* Переключает режим редактирования
Обычно обработчик клика по линии
*/
//console.log('tooggleEditRoute start by anymore',e);
// Щёлкнуть могли либо по нарисованному локально объекту (в том числе -- и по восстановленному из куки)
// либо по загруженному gpx
if(editorEnabled===false) {
	//console.log('[tooggleEditRoute] Редактирование запрещено');
	return;
}
let target;
if(e.target) target = e.target;	// вызвали как обработчик события. В этом языке this почему-то currentTarget (текущий обработчик события в процессе всплытия), а не current (тот, кто инициировал событие). Поэтому лучше явно.
else target = e;	// вызвали просто как функцию
let layerName = '';
currentRoute = null;
//console.log('[tooggleEditRoute] target',target);
//console.log('[tooggleEditRoute] savedLayers:',savedLayers);
if(dravingLines.hasLayerRecursive(target)){	// Щёлкнули по одному из нарисованных объектов. hasLayerRecursive потому что omnivore импортирует gpx как L.LayerGroup с двумя слоями: точки и всё остальное
	//console.log('[tooggleEditRoute] Щёлкнули на объекте',target._leaflet_id,target,'в dravingLines',dravingLines._leaflet_id,dravingLines);
	currentRoute = dravingLines;
	layerName = new Date().toJSON(); 	// запишем в поле ввода имени дату
}
else {
	for (layerName in savedLayers) {	// нет способа определить, в какой layerGroup находится layer, но у нас все показываемые слои хранятся в массиве savedLayers
		//console.log('[tooggleEditRoute] layerName=',layerName);
		if((savedLayers[layerName] instanceof L.LayerGroup) && savedLayers[layerName].hasLayerRecursive(target)){
			//console.log('[tooggleEditRoute] Щёлкнули на объекте',target._leaflet_id,target,'в',savedLayers[layerName]._leaflet_id,layerName,savedLayers[layerName]);
			currentRoute = savedLayers[layerName];
			routeSaveName.value = layerName; 	// запишем в поле ввода имени имя загруженного файла
			break;
		}
	}
}
if(!currentRoute) {
	console.log('[tooggleEditRoute] Не удалось определить currentRoute, облом.');
	return;
}

//console.log('[tooggleEditRoute] target:',target,'currentRoute:',currentRoute,'dravingLines:',dravingLines)
target.toggleEdit();	// оно Leaflet.Editable
if(target.editEnabled()) { 	//  если включено редактирование
	//console.log('[tooggleEditRoute] Редактирование включили');
	routeEraseButton.disabled=false; 	// - сделать доступной кнопку Удалить
	if(!routeSaveName.value) routeSaveName.value = layerName;	// имя файла для сохранения
	// здесь устанавливается выключение режима редактирования по изменению и покиданию
	// поля "описание объекта" в редакторе маршрутов
	// Не знаю, хорошая ли это идея, но я со временем забыл, что для сохранения названия и описания объекта
	/*/ нужно завершить редактирование этого объекта.
	editableObjectDescr.onchange = function (){
		tooggleEditRoute(target);
		//console.log("Выключено редактирование объекта",target)
	};*/
	if((!routeSaveDescr.value) && currentRoute.properties && currentRoute.properties.desc) routeSaveDescr.value = currentRoute.properties.desc;
	if(target.feature && target.feature.properties && target.feature.properties.name) editableObjectName.value = target.feature.properties.name;
	if(target.feature && target.feature.properties && target.feature.properties.desc) editableObjectDescr.value = target.feature.properties.desc;
	if(target instanceof L.Marker){
		//console.log('[tooggleEditRoute] target is instanceof L.Marker',target);
		routeCreateButton.disabled=true; 	// - сделать недоступной кнопку Начать
		pointsControlsEnable();	// включим кнопки точек
		target.setOpacity(0.4);
		target.options.draggable = true;	// сделаем маркер перемещаемым
		const gpxtype = target.feature.properties.type;
		//console.log('[tooggleEditRoute] gpxtype=',gpxtype,pointsButtons.querySelectorAll('button'));
		for(let button of pointsButtons.querySelectorAll('button')){
			if(button.id != 'ButtonSet'+gpxtype) {
				button.disabled = true;
			}
			else {
				button.onclick = function (event) {
					tooggleEditRoute(target);
					button.onclick = function (event) {createEditableMarker(target.getIcon());};
				};
			}
		}
	}
	else {
		pointsControlsDisable();	// отключить кнопки точек
		routeContinueButton.disabled=false; 	// - сделать доступной кнопку Продолжить
	}
}
else {
	//console.log('[tooggleEditRoute] Редактирование выключили');
	editableObjectDescr.onchange = null;
	if(delShapes(false))  routeEraseButton.disabled=false; 	// если есть редактируемые слои в currentRoute
	else {	// 
		//console.log('[tooggleEditRoute] нет редактируемых слоёв: как бы завершаем редактирование currentRoute с именем',layerName,currentRoute);
		if(!target.feature) target.feature = {};
		if(!target.feature.properties) target.feature.properties = {};
		target.feature.properties.name = editableObjectName.value;
		target.feature.properties.desc = editableObjectDescr.value;
		bindPopUptoEditable(target);

		// Автоматическое сохранение ранее загруженного gpx по прекращению редактирования.
		// в результате поведение редактирования файла с сервера такое же, как и редактирование локального.
		// Раз уж они выглядят одинаково.
		// А хорошая ли это идея?
		if(currentRoute.properties && (routeSaveName.value == currentRoute.properties.fileName)){	// мы редактировали ранее загруженный файл
			//console.log('[tooggleEditRoute] Сохраняется файл',currentRoute.properties.fileName);
			//saveGPX();
		}
		else {
			//console.log('[tooggleEditRoute] Сохраняется кука');
			doSaveMeasuredPaths();
		};

		routeCreateButton.disabled=false; 	// - сделать доступной кнопку Начать
		routeEraseButton.disabled=true; 	// - сделать недоступной кнопку Удалить
		routeContinueButton.disabled=true; 	//  - сделать недоступной кнопку Продолжить
		if(editorEnabled==='maybe') editorEnabled=false;	// панель закрыли во время редактирования, потом редактирование завершили
		//currentRoute = null;	// иначе saveGPX не сработает
		//routeSaveName.value = '';	// если нет автоматического сохранения gpx, то надо оставить
		//routeSaveDescr.value = '';
		editableObjectName.value = '';
		editableObjectDescr.value = '';
	}
	if(target instanceof L.Marker){
		//console.log('[tooggleEditRoute] target is instanceof L.Marker');
		target.setOpacity(0.7);
		target.options.draggable = false;	// сделаем маркер не перемещаемым
		const gpxtype = target.feature.properties.type;
		for(let button of pointsButtons.querySelectorAll('button')){	// кнопки установки маркеров
			button.disabled = false;
			if(button.id == 'ButtonSet'+gpxtype) {	// кнопка, по которой был создан этот маркер
				button.onclick = function (event) {createEditableMarker(target.getIcon());};	// вернём стандартное действие -- создание маркера
			}
		};
	}
	else pointsControlsEnable();
}
} // end function tooggleEditRoute

function createEditableMarker(Icon){
if(!currentRoute) currentRoute = dravingLines; 	// 
let gpxtype = Icon.options.iconUrl.substring(Icon.options.iconUrl.lastIndexOf('/')+1,Icon.options.iconUrl.lastIndexOf('.png'));
let layer = map.editTools.startMarker(centerMarkMarker.getLatLng(),{
	icon: Icon,
	opacity: 0.5
}).addTo(currentRoute);
layer.feature = {type: 'Feature',
	properties: { 	// типа, оно будет JSONLayer
		type: gpxtype,
	},
};

layer.on('click',tooggleEditRoute);
//layer.on('editable:drawing:end',	function(event) {
//	console.log('layer.on [editable:drawing:end] event.layer:',event.layer);
//});
//layer.on('editable:enable',function(event){
//});
//layer.on('editable:disable',function(event){
//})
// прикалывает маркер в указанных координатах. Если не прикалывать -- в мобильных браузерах
// значёк сдвигается вместе со шторкой инструментальной панели и прикалывается там.
// с другой стороны, в старых браузерах он в этот момент не двигается по тапу, т.е., фактически
// приколот, хотя действия не было.
layer.editor.tools.stopDrawing();	
//console.log('createEditableMarker',layer);

for(let button of pointsButtons.querySelectorAll('button')){
	//console.log('[createEditableMarker] button.id=',button.id,'ButtonSet+gpxtype=','ButtonSet'+gpxtype);
	if(button.id != 'ButtonSet'+gpxtype) {
		button.disabled = true;
	}
	else {
		button.onclick = function (event) {
			//console.log('[button on click] layer:',layer);
			tooggleEditRoute(layer);
			button.onclick = function (event) {createEditableMarker(Icon);};
		};
	}
}
routeControlsDeSelect();	// отключим все кнопки рисования линии
routeEraseButton.disabled=false;	// включим кнопку Стереть
if(!routeSaveName.value) routeSaveName.value = new Date().toJSON(); 	// запишем в поле ввода имени дату, если там ничего не было
} // end function createEditableMarker

function doSaveMeasuredPaths() {
/* сохранение в cookie отображаемых на карте маршрутов
Сохраняются только маршруты, не находящиеся в состоянии редактирования.
Предполагается, что это для сохранения маршрутов/замеров расстояний на конкретном устройстве
*/
let expires =  new Date();
let toSave = L.geoJSON();
function findEditDisabled(layer){
	//console.log('[doSaveMeasuredPaths][findEditDisabled] layer:',layer,layer instanceof L.LayerGroup,'eachLayer' in layer);
	if(layer instanceof L.LayerGroup){
		layer.eachLayer(findEditDisabled);
	}
	else {
		if(('editEnabled' in layer) && !layer.editEnabled()){	// режим редактирования этого слоя выключен или отсутствует
			//console.log('[doSaveMeasuredPaths][findEditDisabled] layer:',layer,layer.toGeoJSON());
			let gj = layer.toGeoJSON();
			if(!gj.type){
				console.log('[doSaveMeasuredPaths][findEditDisabled] метод toGeoJSON() не добавляет в создаваемый GeoJSON свойство type = "Feature", если преобразуется объект типа L.Marker',gj);
				gj.type = 'Feature';
			}
			toSave.addData(gj);
			expires.setTime(expires.getTime() + (60*24*60*60*1000)); 	// протухнет через два месяца
		}
	}
} // end function findEditDisabled
//console.log('[doSaveMeasuredPaths] toSave original:',toSave);
dravingLines.eachLayer(findEditDisabled);
toSave = toSave.toGeoJSON();	// здесь я реально не понял. А оно не geoJSON? Оно не GeoJSON. Оно LayerGroup
toSave.properties = dravingLines.properties;	// на самом деле -- чисто чтобы там было properties, оно нигде не используется
//console.log('[doSaveMeasuredPaths] toSave:',toSave);

toSave = toGPX(toSave); 	// сделаем gpx 
//console.log('[doSaveMeasuredPaths] Save to cookie GaladrielMapMeasuredPaths',toSave,expires.getTime()-Date.now());
toSave = utoa(toSave);	// кодируем в Base64, потому что xml нельза сохранить в куке

// если expires осталась сейчас -- кука удалится, иначе -- поставится.
document.cookie = "GaladrielMapMeasuredPaths="+toSave+"; expires="+expires+"; path=/; samesite=Lax"; 	// если сечас и нет, чего сохранять - грохнем куки
//console.log('[doSaveMeasuredPaths] document.cookie:',document.cookie);
} 	// end function doSaveMeasuredPaths

function doRestoreMeasuredPaths() {
/**/
let RestoreMeasuredPaths = getCookie('GaladrielMapMeasuredPaths');
//console.log('[doRestoreMeasuredPaths] RestoreMeasuredPaths=',RestoreMeasuredPaths);
if(RestoreMeasuredPaths) {
	try {	// в принципе, там может быть фигня, но главное -- та же кука от старой версии приведёт к облому
		RestoreMeasuredPaths = atou(RestoreMeasuredPaths);	// восстановим из base64
	}
	catch {
		return;
	}
	//console.log('[doRestoreMeasuredPaths] Restore from cookie',RestoreMeasuredPaths);
	
	dravingLines.clearLayers();
	dravingLines = omnivore.gpx.parse(RestoreMeasuredPaths);	// leaflet-omnivore.js
	//console.log('[doRestoreMeasuredPaths] dravingLines',dravingLines);
	dravingLines.eachLayerRecursive(function (layer){
		//console.log('[doRestoreMeasuredPaths] layer',layer);
		if(layer.feature && (layer.feature.geometry.type == 'LineString' || layer.feature.geometry.type == 'Line')){
			layer.options.color = '#FDFF00';
		}
	});
	dravingLines.addTo(map);
}
}	// end function doRestoreMeasuredPaths

function bindPopUptoEditable(layer){
// Подпись - Tooltip
let tooltip = layer.getTooltip();
if(tooltip){
	if(layer.feature.properties.name) {
		//console.log('[bindPopUptoEditable] изменение tooltip',tooltip);
		layer.setTooltipContent(layer.feature.properties.name);
	}
	else layer.unbindTooltip();
}
else {
	if(layer.feature.properties.name) {
		layer.unbindTooltip();
		layer.bindTooltip(layer.feature.properties.name,{ 	
			permanent: true,  	// всегда показывать
			direction: 'auto', 
			//direction: 'left', 
			//offset: [-16,-25],
			//offset: [-32,0],
			className: 'wpTooltip', 	// css class
			opacity: 0.75
		});
	}
}

// popUp
let popUpHTML = '';
if(layer.feature.properties.number) popUpHTML = " <span style='font-size:120%;'>"+layer.feature.properties.number+"</span> "+popUpHTML;
if(layer.feature.properties.name) popUpHTML = "<b>"+layer.feature.properties.name+"</b> "+popUpHTML;
if(layer instanceof L.Marker) {
	let lat = Math.round(layer.getLatLng().lat*10000)/10000; 	 	// широта
	let lng = Math.round(layer.getLatLng().lng*10000)/10000; 	 	// долгота
	if(!popUpHTML) popUpHTML = lat+" "+lng;
	popUpHTML = "<span style='font-size:120%'; onClick='doCopyToClipboard(\""+lat+" "+lng+"\");'>" +popUpHTML+ "</span><br>";
}
if(layer.feature.properties.cmt) popUpHTML += "<p>"+layer.feature.properties.cmt+"</p>";
if(layer.feature.properties.desc) popUpHTML += "<p>"+layer.feature.properties.desc.replace(/\n/g, '<br>')+"</p>"; 	// gpx description
if(layer.feature.properties.ele) popUpHTML += "<p>Alt: "+layer.feature.properties.ele+"</p>"; 	// gpx elevation
//popUpHTML += getLinksHTML(feature); 	// приклеим ссылки Пока не реализовано
layer.unbindPopup();	// если, допустим, описание было, а потом не стало
if(popUpHTML) {
	//console.log('[bindPopUptoEditable] binding popup',popUpHTML);
	layer.bindPopup(popUpHTML+'<br>');
}
} // end function bindPopUptoEditable


function saveGPX() {
/* Сохраняет на сервере маршрут из объекта currentRoute. currentRoute -- это или нарисованный
локально объект, или отредактированный gpx
*/
if(!currentRoute) { 	// глобальная переменная, присваивается в tooggleEditRoute, типа - по щелчку на маршруте
	routeSaveMessage.innerHTML = 'Error - no route selected.'
	return;
}
//console.log('[saveGPX] currentRoute:',currentRoute);
//console.log('[saveGPX] Сохраняется файл',currentRoute.properties.fileName);
	function collectSuperclasterPoints(layerGroup){
	//console.log('[collectSuperclasterPoints] layerGroup:',layerGroup);
	let pointsFeatureCollection = []; 	// 
	for(const layer of layerGroup.getLayers()){
		if('supercluster' in layer) { 	// это superclaster'изованный слой, с точками, надо полагать, ранее положенными в свойство layer.supercluster
			//console.log('[collectSuperclasterPoints] layer.supercluster.points:',layer.supercluster.points);
			pointsFeatureCollection = pointsFeatureCollection.concat(layer.supercluster.points);
		}
		if(layer instanceof L.LayerGroup) {	// это LayerGroup
			pointsFeatureCollection = pointsFeatureCollection.concat(collectSuperclasterPoints(layer));
		}
	}
	//console.log('[collectSuperclasterPoints] pointsFeatureCollection:',pointsFeatureCollection);
	return pointsFeatureCollection;
	} // end function collectSuperclasterPoints


let fileName = routeSaveName.value; 	// имя файла для сохранения, поле в интерфейсе
if(! fileName) { 	// внезапно имени нет, хотя в index поле заполняется
	fileName = new Date().toJSON();
	routeSaveName.value = fileName;
}

if(!(currentRoute  instanceof L.LayerGroup)) currentRoute = new L.LayerGroup([currentRoute]); 	// попробуем сменть тип на layerGroup, но это обычно боком выходит, потому что всё же layergroup не layer. Да, впрочем, нормально?

// Теперь делаем JSON, из которого сделаем gpx
// Сначала соберём в pointsFeatureCollection реальные точки из данных superclaster
// поскольку мы хотим toGeoJSON() все имеющиеся точки, а слой может быть superclaster, то будем доставать точки из supercluster'а
let pointsFeatureCollection = collectSuperclasterPoints(currentRoute); 	// 
//console.log('[saveGPX] pointsFeatureCollection:',pointsFeatureCollection);

let route = currentRoute.toGeoJSON(); 	// сделаем объект geoJSON. Очевидно, это новый объект?
if(!('properties' in route)) route.properties = {};
//route.properties.fileName = fileName;	// имя файла. А нафига?
if(routeSaveDescr.value.trim()) route.properties.desc = routeSaveDescr.value;	// общий комментарий
route.properties.time = new Date().toISOString();
route.properties.xmlns = "http://www.topografix.com/GPX/1/1";
route.properties['xmlns:gpxx'] = "http://www8.garmin.com/xmlschemas/GpxExtensions/v3";
route.properties['xmlns:xsi'] = "http://www.w3.org/2001/XMLSchema-instance";
route.properties['xsi:schemaLocation'] = "http://www.topografix.com/GPX/1/1 http://www.topografix.com/GPX/1/1/gpx.xsd https://www8.garmin.com/xmlschemas/GpxExtensions/v3 https://www8.garmin.com/xmlschemas/GpxExtensions/v3/GpxExtensionsv3.xsd";
for(let key in currentRoute.properties) {	//
	if(typeof route.properties[key] === 'undefined') route.properties[key] = currentRoute.properties[key];
}
//console.log('[saveGPX] currentRoute:',currentRoute);
//console.log('[saveGPX] route as geoJSON:',route);

// теперь выкинем точки, которые есть в supercluster, а потом добавим все точки из supercluster
// потому что при текущем масштабе некоторые точки из supercluster могли отображаться как точки,
// а не как значки supercluster
if(pointsFeatureCollection.length) { 	// это был supercluster, поэтому в geoJSON неизвестно, сколько оригинальных точек, а не все. Но у нас с собой было...
	// выкинем все точки, присутствующие в pointsFeatureCollection
	let pointsFeatureCollectionStrings = pointsFeatureCollection.map(function (point){
																		// а вот тут убъём все сохранённые маркеры
																		// из-за того, что JSON.stringify нельзя
																		// заставить что-то сделать с циклической структурой
																		point.properties.marker = undefined;
																		return JSON.stringify(point);
																	});
	route.features = route.features.filter(function(feature){	
		// не сами кластеры, не точки, и точки, не входящие в pointsFeatureCollection
		return (!feature.properties.cluster) && ((feature.geometry.type !== 'Point') || (! pointsFeatureCollectionStrings.includes(JSON.stringify(feature))));
	});
	//console.log('[saveGPX] JSON.stringify(route.features)',JSON.stringify(route.features));
	// нифига не понятно, почему layer.supercluster.points -- это geoJSON? Видимо, потому, что
	// в supercluster исходно загружаются не объекты leaflet, а GeoJSON Feature objects. 
	route.features = route.features.concat(pointsFeatureCollection); 	// теперь положим туда точки, ранее взятые в superclaster'е
}
//console.log('[saveGPX] route as geoJSON after:',route);

route = toGPX(route); 	// сделаем gpx 
//console.log('[saveGPX] route as gpx:',route);

var xhr = new XMLHttpRequest();
xhr.open('POST', 'saveGPX.php', true); 	// Подготовим асинхронный запрос
xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
xhr.send('name=' + encodeURIComponent(fileName) + '&gpx=' + encodeURIComponent(route));
xhr.onreadystatechange = function() { // 
	if (this.readyState != 4) return; 	// запрос ещё не завершился
	if (this.status != 200) return; 	// что-то не то с сервером
	routeSaveMessage.innerHTML = this.responseText;
}

} // end function saveGPX()


function toGPX(geoJSON) {
/* Create gpx route or track (createTrk==true) from geoJSON object вместо этого LineString
должна иметь свойство properties.isRoute == true, тогда рисуется маршрут, иначе -- путь (track)
geoJSON must have a needle gpx attributes
bounds - потому что geoJSON.getBounds() не работает
*/
//console.log('[toGPX] geoJSON:',geoJSON);
var gpxtrack = `<?xml version="1.0" encoding="UTF-8" standalone="no" ?>
<gpx creator="GaladrielMap" version="1.1"
`;
for(let key in geoJSON.properties) {	//
	if(!key.startsWith('xml') && !key.startsWith('xsi')) continue;
	gpxtrack += `\t${key}="${geoJSON.properties[key]}"\n`;
};
gpxtrack += '>\n';
gpxtrack += '<metadata>\n';
var date = geoJSON.properties.date;
if(!date) date = new Date().toISOString();
gpxtrack += '	<time>'+ date +'</time>\n';
// Хитрый способ получить границы всех объектов в geoJSON
const geojsongroup = L.geoJSON(geoJSON);
let bounds = geojsongroup.getBounds();
//console.log('[toGPX] bounds:',bounds);
if(Object.entries(bounds).length) gpxtrack += '	<bounds minlat="'+bounds.getSouth().toFixed(4)+'" minlon="'+bounds.getWest().toFixed(4)+'" maxlat="'+bounds.getNorth().toFixed(4)+'" maxlon="'+bounds.getEast().toFixed(4)+'"  />\n';
if(geoJSON.properties) doDescriptions(geoJSON.properties) 	// запишем разные описательные поля
gpxtrack += '</metadata>\n';

for(let i=0; i<geoJSON.features.length;i++) {
	//console.log('[toGPX] geoJSON.features[i]:',geoJSON.features[i]);
	switch(geoJSON.features[i].geometry.type) {
	case 'MultiLineString': 	// это обязательно путь
		gpxtrack += '	<trk>\n'; 	// рисуем трек
		doDescriptions(geoJSON.features[i].properties) 	// запишем разные описательные поля
		for(let k = 0; k < geoJSON.features[i].geometry.coordinates.length; k++) {
			gpxtrack += '		<trkseg>\n'; 	// рисуем трек
			for (let j = 0; j < geoJSON.features[i].geometry.coordinates[k].length; j++) {
				gpxtrack += '			<trkpt '; 	// рисуем трек
				gpxtrack += 'lat="' + geoJSON.features[i].geometry.coordinates[k][j][1] + '" lon="' + geoJSON.features[i].geometry.coordinates[k][j][0] + '">';
				gpxtrack += '</trkpt>\n'; 	// рисуем трек
			}
			gpxtrack += '		</trkseg>\n'; 	// рисуем трек
		}
		gpxtrack += '	</trk>\n'; 	// рисуем трек
		break;
	case 'LineString': 	// это может быть как маршрут, так и путь
		if(geoJSON.features[i].properties.isRoute) gpxtrack += '	<rte>\n'; 	// рисуем маршрут
		else gpxtrack += '	<trk>\n'; 	// рисуем трек
		doDescriptions(geoJSON.features[i].properties) 	// запишем разные описательные поля
		if(!geoJSON.features[i].properties.isRoute) gpxtrack += '		<trkseg>\n'; 	// рисуем трек
		for (let j = 0; j < geoJSON.features[i].geometry.coordinates.length; j++) {
			if(!geoJSON.features[i].properties.isRoute) gpxtrack += '			<trkpt '; 	// рисуем трек
			else gpxtrack += '		<rtept '; 	// рисуем маршрут
			gpxtrack += 'lat="' + geoJSON.features[i].geometry.coordinates[j][1] + '" lon="' + geoJSON.features[i].geometry.coordinates[j][0] + '">';
			if(!geoJSON.features[i].properties.isRoute) gpxtrack += '</trkpt>\n'; 	// рисуем трек
			else gpxtrack += '</rtept>\n'; 	// рисуем маршрут
		}
		if(!geoJSON.features[i].properties.isRoute) gpxtrack += '		</trkseg>\n'; 	// рисуем трек
		if(!geoJSON.features[i].properties.isRoute) gpxtrack += '	</trk>\n'; 	// рисуем трек
		else gpxtrack += '	</rte>\n'; 	// рисуем маршрут
		break;
	case 'Point':
		gpxtrack += '	<wpt '; 	// рисуем точку
		gpxtrack += 'lat="' + geoJSON.features[i].geometry.coordinates[1] + '" lon="' + geoJSON.features[i].geometry.coordinates[0] + '">\n';
		doDescriptions(geoJSON.features[i].properties) 	// запишем разные описательные поля
		gpxtrack += '	</wpt>\n'; 	// 
	}
}
gpxtrack += '</gpx>';
//console.log('[toGPX] resulting gpxtrack',gpxtrack);
return gpxtrack;

	function doDescriptions(properties) {
		//console.log('[toGPX][doDescriptions] properties:',properties,properties.desc);
		if(properties.name) gpxtrack += '		<name>' + properties.name.encodeHTML() + '</name>\n';
		if(properties.cmt) gpxtrack += '		<cmt>' + properties.cmt.encodeHTML() + '</cmt>\n';
		if(properties.desc) gpxtrack += '		<desc>' + properties.desc.encodeHTML() + '</desc>\n';
		if(properties.src) gpxtrack += '		<src>' + properties.src + '</src>\n';
		if(properties.link) {
			for ( let ii = 0; ii < properties.link.length; ii++) { 	// ссылок может быть много
				//console.log(properties.link[ii]);
				gpxtrack += properties.link[ii];
			}
			//console.log(gpxtrack);
		}
		if(properties.number) gpxtrack += '		<number>' + properties.number + '</number>\n';
		if(properties.type) gpxtrack += '		<type>' + properties.type + '</type>\n';
		if(properties.extensions) { 	// там просто уж оформленная строка
			for ( let ii = 0; ii < properties.extensions.length; ii++) {
				gpxtrack += properties.extensions[ii];
			};
		}
	}
} // end function toGPX


String.prototype.encodeHTML = function () {
    return this.replace(/&/g, '&amp;')
               .replace(/</g, '&lt;')
               .replace(/>/g, '&gt;')
               .replace(/"/g, '&quot;')
               .replace(/'/g, '&apos;');
};


// Кластеризация точек
function createSuperclaster(geoJSONpoints){
/* geoJSONpoints - array of GeoJSON points, as it described in Superclaster doc */
const index = new Supercluster({
	log: false, 	// вывод лога в консоль
	radius: superclusterRadius,
	extent: 256,
	maxZoom: 14,
	minPoints: 3	// при умолчальных 2 невозможно разделить дублирующиеся точки
}).load(geoJSONpoints); 
return index;
} // end function createSuperclaster


function removeFromSuperclaster(superclasterLayer,point){
let ret = false;
if(!superclasterLayer.supercluster) return ret;
if(!(point instanceof L.Marker)) return ret;
let pointStr = point.toGeoJSON();
// Убъём сохранённый маркер, потому что там хранится тот же GeoJSON, и в результате
// JSON.stringify обламывается, что структура циклическая.
// Пляски вокруг параметров JSON.stringify не помогли: ничего не работает, или я ниасилил доку.
// Потом supercluster создаст новый маркер, ничего страшного.
if(pointStr.properties.marker) pointStr.properties.marker = undefined;	
//console.log('[removeFromSuperclaster] point:',pointStr,JSON.stringify(pointStr));
pointStr = JSON.stringify(pointStr);
for(let i = 0; i < superclasterLayer.supercluster.points.length; i++){
	// а вот здесь не будем просто убивать маркер, ибо убъются все
	// переприсвоим, потом убъём, потом присвоим обратно.
	// В конце-концов, это просто пляски со ссылками. Или убить?
	let savedMarker;
	if(superclasterLayer.supercluster.points[i].properties.marker) {
		savedMarker = superclasterLayer.supercluster.points[i].properties.marker;
		superclasterLayer.supercluster.points[i].properties.marker = undefined;
	}
	let superStr = JSON.stringify(superclasterLayer.supercluster.points[i]); 
	if(savedMarker) superclasterLayer.supercluster.points[i].properties.marker = savedMarker;
	if(pointStr===superStr){
		superclasterLayer.supercluster.points.splice(i,1);
		superclasterLayer.supercluster = createSuperclaster(superclasterLayer.supercluster.points); 	// создание нового и загрузка в суперкластер точек 		
		ret = true;
		//console.log('[removeFromSuperclaster] точка найлена ret=',ret);
		break;
	}
}
return ret;
} // end function removeFromSuperclaster

function updateClasters() {
/* Обновляет показываемые кластеры точек
В savedLayers вообще все показываемые слои: карты, сетки, файлы. Но не окружности дистанции.
Чтобы не разбираться - будем выбирать оттуда заведомо только файлы.
*/
//console.log('galadrielmap.js: updateClasters start by anymore');
for (var i = 0; i < routeDisplayed.children.length; i++) { 	// для каждого потомка списка routeDisplayed
	const trackName = routeDisplayed.children[i].innerHTML; 	// наименование показывающегося слоя, возможн, с точками
	updClaster(savedLayers[trackName]);
}
for (var i = 0; i < trackDisplayed.children.length; i++) { 	// для каждого потомка списка trackDisplayed
	const trackName = trackDisplayed.children[i].innerHTML; 	// наименование показывающегося слоя, возможн, с точками
	updClaster(savedLayers[trackName]);
}
} // end function updateClasters

async function updClaster(e) {
// обновляет кластер
if(!e) return;
let layer;
if(e.target) layer = e.target; 	// e - event
else layer = e;	// e - layer
//console.log('[updClaster] layer:',layer._leaflet_id,layer,layer instanceof L.LayerGroup);
realUpdClaster(layer);
layer.eachLayer(realUpdClaster);

function realUpdClaster(layer) {
	if(!layer.supercluster) return;
	//console.log('[realUpdClaster] Обновляется кластер',layer._leaflet_id,layer);
	const bounds = map.getBounds();
	const mapBox = {
		bbox: [bounds.getWest(), bounds.getSouth(), bounds.getEast(), bounds.getNorth()],
		zoom: map.getZoom()
	}
	// Оно может быть вызвано во время изменения масштаба, и тогда map.getZoom() вернёт дробный масштаб
	// от этого у supercluster съезжает крыша, и оно падает с весёлыми глюками.
	// При этом бесполезно снова спрашивать здесь map.getZoom() -- возвращаемое значение не меняется
	// хотя изменение масштаба давно закончилось.
	// Поэтому просто не будем обновлять кластер, если масштаб дробный.
	// Авотхрен: значение map.getZoom() не меняется (и остаётся дробным) до следующего изменения масштаба.
	// Опять автохрен: оказывается, дробные значения масштаба -- нормально. Видимо, оно не не меняется, а правда такое -- дробное.
	// Получается, авторы supercluster этого не знали, и заложились на целое?
	// Таким образом, нужно изменить масштаб карты с дробного к ближайшему целому, и вызывать supercluster
	// А можно забить, и вызывать supercluster с округлённым до целого масштабом -- это не концептуально,
	// но на практике -- без разницы.
	/*
	if(!Number.isInteger(mapBox.zoom)){
		console.log('[realUpdClaster] mapBox.zoom=',mapBox.zoom);
		//return;
	}
	*/
	// Точки и кластеры показываются на слое layer только в пределах bbox.
	//все другие точки тихо лежат в кеше
	mapBox.zoom = Math.round(mapBox.zoom);
	//console.log('[realUpdClaster] mapBox.bbox:',mapBox.bbox,'mapBox.zoom=',mapBox.zoom);
	//console.log('[realUpdClaster] layer.supercluster.getClusters:',layer.supercluster.getClusters(mapBox.bbox, mapBox.zoom));
	let newGeoJSONpoints=[], pointsExistsIDs=[];
	for(const point of layer.supercluster.getClusters(mapBox.bbox, mapBox.zoom)){
		// возвращает новые точки: у которых нет сохранённого маркера, или этот маркер не показывается сейчас
		if(!point.properties.marker) newGeoJSONpoints.push(point);
		else {
			if(!(point.properties.marker._leaflet_id in layer._layers)) newGeoJSONpoints.push(point);
			else pointsExistsIDs.push(point.properties.marker._leaflet_id);	// это id тех, кто есть, и кто должно быть в слое
		}
	};
	//console.log('[realUpdClaster] newGeoJSONpoints:',newGeoJSONpoints,'pointsExistsIDs:',pointsExistsIDs);
	for(let id in layer._layers){	// удаляем точки, которых быть не должно
		id = parseInt(id);
		//console.log('[realUpdClaster] id=',id,pointsExistsIDs.includes(id));
		if(!pointsExistsIDs.includes(id)) {
			// Собственно, вся эта лабуда с новыми и старыми точками выше сделана исключительно
			// ради того, чтобы определить точки, уходящие из поля зрения, но не в результате зуммирования
			// и удаления из их GeoJSON сохранённого маркера -- в целях сбережения памяти.
			// Т.е., память никогда не кончится при любом количестве точек, как оно и задумано в supercluster.
			// За исключением случая, когда сначала зум (тогда маркер не удаляется, даже если точка уходит
			// из поля зрения), а потом сдвиг, и точка уходит. Тогда маркер остаётся, но это не страшно?
			//console.log('[realUpdClaster] lastSuperClusterUpdatePosition:',lastSuperClusterUpdatePosition,map.getZoom());
			if(lastSuperClusterUpdatePosition[1]==map.getZoom()) {
				//console.log('[realUpdClaster] Удаляется сохранённый маркер из',layer._layers[id]);
				// сохранённый маркер есть, раз эта точка показывалась, но эта точка может быть кластером
				// Чёта фигня какая-то. У кластера есть сохранённый маркер? А кто?
				// Ха! Оказывается, сохранять маркер -- это не я придумал, такая фича есть в supercluster
				if(layer._layers[id].feature.properties.marker) layer._layers[id].feature.properties.marker = undefined;
			}
			layer.removeLayer(id);
		}
	}
	//layer.clearLayers();
	layer.addData(newGeoJSONpoints); 	// добавляем новые точки
} // end function realUpdClaster
} // end function updClaster



function nextColor(color,step) {
/* step - by color chanel 
step не может быть константой, если color - число, если мы хотим получать чистые цвета

Тривиальный код даёт тот же результат?:
function random(number) {
  return Math.floor(Math.random() * (number+1));
}
const rndCol = 'rgb(' + random(255) + ',' + random(255) + ',' + random(255) + ')';
*/
if(!step) step = 0x80;
const colorStr = ('000000' + color.toString(16)).slice(-6);
var r = parseInt(colorStr.slice(0,2),16);
var g = parseInt(colorStr.slice(2,4),16);
var b = parseInt(colorStr.slice(4),16);
b-=step;
if(b<0) {
	b=0xFF+b;
	g-=step;
	if(g<0) {
		g=0xFF+g;
		r-=step;
		if(r<0) {
			r=0xFF+r;
			g=0xFF-g;
			b=0xFF-b;
		}
	}
}
return parseInt(('00'+r.toString(16)).slice(-2)+('00'+g.toString(16)).slice(-2)+('00'+b.toString(16)).slice(-2),16);
} // end function nextColor


// Показ координат центра и переход по введённым
function centerMarkPosition() {
/* global goToPositionField */
centerMark.invoke('setLatLng',map.getCenter()); // установим координаты всех маркеров
if(goToPositionManualFlag === false) { 	// если поле не юзают руками
	const lat = Math.round(centerMarkMarker.getLatLng().lat*10000)/10000; 	 	// широта с четыремя знаками после запятой - 10см
	const lng = Math.round(((centerMarkMarker.getLatLng().lng%360+540)%360-180)*10000)/10000; 	 	// долгота
	goToPositionField.value = lat + ' ' + lng;
} 	// а когда руками, т.е., фокус в поле -- координаты перестают изменяться. Карта же может двигаться за курсором
}; // end function centerMarkPosition

function centerMarkUpdate(){
//let markSize = Math.round(distCirclesUpdate(centerMarkCircles))*2; 	// нарисуем круги и заодно получим размер крестика
distCirclesUpdate(centerMarkCircles);
let markSize = Math.round((window.innerWidth+window.innerHeight)/10);
//console.log("[centerMarkOn] markSize=",markSize);
let centerMark_markerImg = `<svg width="${markSize}" height="${markSize}" viewBox="0 0 100% 100%" xmlns="http://www.w3.org/2000/svg">
	<line x1="50%" y1="0" x2="50%" y2="100%" stroke="rgb(253,0,219)" />
	<line x1="0" y1="50%" x2="100%" y2="50%" stroke="rgb(253,0,219)" />
	<line x1="0" y1="50%" x2="25%" y2="50%" stroke="rgba(253,0,219,0.3)" stroke-width="3px" />
	<line x1="75%" y1="50%" x2="100%" y2="50%" stroke="rgba(253,0,219,0.3)" stroke-width="3px" />
	<line x1="50%" y1="0%" x2="50%" y2="25%" stroke="rgba(253,0,219,0.3)" stroke-width="3px" />
	<line x1="50%" y1="75%" x2="50%" y2="100%" stroke="rgba(253,0,219,0.3)" stroke-width="3px" />
</svg>`;
centerMarkIcon.options.html = centerMark_markerImg;
centerMarkIcon.options.iconAnchor = [markSize/2, markSize/2];
//console.log(centerMarkIcon);
} // end function centerMarkUpdate

function centerMarkOn() {
/**/
centerMarkPosition();
centerMarkUpdate();	// обязательно после centerMarkPosition, ибо там географическое положение используется для вычисления положения в пикселях
centerMark.addTo(map);
map.on('move', centerMarkPosition);
goToPositionField.addEventListener('focus', function(e){goToPositionManualFlag=true;}); 	// при получении фокуса - прекратить обновление
goToPositionField.addEventListener('blur', function(e){ 	// когда теряет фокус. В результате, даже если карта движется, с полем можно работать
			goToPositionButton.value = goToPositionField.value; 	// разбор введённого как координат происходит потом, когда координаты действительно нужны - для скорости
			goToPositionManualFlag=false;
		}
	); 	// при потере - возобновить
}; // end function centerMarkOn

function centerMarkOff() {
centerMark.remove();
map.off('move', centerMarkPosition);
}; // end function centerMarkOff


function flyByString(stringPos){
/* Получает строку предположительно с координатами, и перемещает туда центр карты */
//console.log('goToPositionButton',goToPositionButton.value,'goToPositionField',goToPositionField.value);
if(!stringPos) stringPos = map.getCenter().lat+' '+map.getCenter().lng; 	// map -- глобально определённая карта
console.log('[flyByString] stringPos=',stringPos);
let error;
try {
    var position = new Coordinates(stringPos); 	// https://github.com/otto-dev/coordinate-parser
	//console.log('[flyByString] position:',position);
	const lat=position.getLatitude();
	const lon=position.getLongitude();
	map.setView(L.latLng([lat,lon])); 	// подвинем карту в указанное место
	let xhr = new XMLHttpRequest();
	const url = encodeURI('https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat='+lat+'&lon='+lon);
	xhr.open('GET', url, true); 	// Подготовим асинхронный запрос
	xhr.setRequestHeader('Referer',url); 	// nominatim.org требует?
	xhr.send();
	xhr.onreadystatechange = function() { // 
		if (this.readyState != 4) return; 	// запрос ещё не завершился
		if (this.status != 200) return; 	// что-то не то с сервером
		const nominatim = JSON.parse(this.response);
		//console.log(nominatim);
		updGeocodeList(nominatim);
	}	
} catch (error) { 	// строка - не координаты
	//console.log('[flyByString] stringPos=',stringPos,error);
	let xhr = new XMLHttpRequest();
	//const url = encodeURI('https://nominatim.openstreetmap.org/search/'+stringPos+'?format=jsonv2'); 	// прямое геокодирование
	const url = encodeURI('https://nominatim.openstreetmap.org/search?q='+stringPos+'&format=jsonv2'); 	// прямое геокодирование
	xhr.open('GET', url, true); 	// Подготовим асинхронный запрос
	xhr.setRequestHeader('Referer',url); 	// nominatim.org требует? Теперь, .... требует.
	xhr.send();
	xhr.onreadystatechange = function() { // 
		if (this.readyState != 4) return; 	// запрос ещё не завершился
		if (this.status != 200) return; 	// что-то не то с сервером
		const nominatim = JSON.parse(this.response);
		//console.log(nominatim);
		updGeocodeList(nominatim);
	}
}
} // end function flyByString

function updGeocodeList(nominatim){
if(!Array.isArray(nominatim)) nominatim = [nominatim];
geocodedList.innerHTML = ""; 	// очистим список
for(const geoObj of nominatim){
	//console.log(geoObj);
	let optNode = document.createElement('li');
	optNode.innerText = geoObj.display_name;
	optNode.onclick = function(e) {
		//console.log(e); 
		for(let liNode of geocodedList.children){
			liNode.style.backgroundColor='inherit';
		}
		e.target.style.backgroundColor='#d5d5d5';
		map.setView(L.latLng([geoObj.lat,geoObj.lon]))
	};
	geocodedList.append(optNode);
}
} // end function updGeocodeList


// Копирование в буфер обмена
function doCopyToClipboard(text) {
/* создаёт control с полем, откуда можно скопировать text в буфер обмена, 
при этом пытается это сделать сама.
Через некоторое время поле исчезает

global copyToClipboard
*/
if(typeof(text) === 'string') {
	if(!copyToClipboard._map) { 	// кривой метод
		//alert('not on map!');
		copyToClipboard.addTo(map);
	}
	copyToClipboardField.value = text;
	copyToClipboardField.focus();
	copyToClipboardField.select(); // 
	let successful = document.execCommand('copy');
	if(successful) {
		copyToClipboardMessage.innerText = copyToClipboardMessageOkTXT;
	}
	else {
		copyToClipboardMessage.style.color='red';
		copyToClipboardMessage.innerText = copyToClipboardMessageBadTXT;
	}
	//console.log('PosFreshBefore',PosFreshBefore);
	setTimeout(doCopyToClipboard,PosFreshBefore); 	// удалим поле через PosFreshBefore, определённый в index
}
else {
	if(typeof copyToClipboard !== 'undefined') copyToClipboard.remove();
}
} // end function doCopyToClipboard


function doCurrentTrackName(liID){
let liObj = document.getElementById(liID);
//console.log('doCurrentTrackName',liID,liObj);
liObj.classList.add("currentTrackName");
liObj.title='Current track';
currentTrackName = liID;
currentTrackShowedFlag = false; 	// флаг, что у нас новый текущий трек. Обрабатывается в currentTrackUpdate index.php
} // end function doCurrentTrackName

function doNotCurrentTrackName(liID){
let liObj = document.getElementById(liID);
liObj.classList.remove("currentTrackName");
liObj.title='';
currentTrackName = '';
} // end function doNotCurrentTrackName

function loggingWait() {
/* запускает/останавливает слежение за наличием пишущегося трека по кнопке в интерфейсе */
if(currTrackSwitch.checked){	// Текущий трек всегда показывается
	startCurrentWaitTrackUpdateProcess();	
	console.log('[loggingWait]  Logging check started by user');
}
else {
	if(currentWaitTrackUpdateProcess){
		clearInterval(currentWaitTrackUpdateProcess);
		currentWaitTrackUpdateProcess = null;
	}	
	console.log('[loggingWait]  Logging check stopped by user');
}
} // end function loggingWait

function loggingRun() {
/* запускает/останавливает запись трека по кнопке в интерфейсе */
let logging = 'logging.php';
if(loggingSwitch.checked) {	// Запись пути
	logging += '?startLogging=1';
}
else {
	logging += '?stopLogging=1';
	if(currentTrackName) doNotCurrentTrackName(currentTrackName);
	console.log('[loggingRun] Logging stop by user');
	console.log('[loggingRun]  Update track stopped because no logging now');
	if(currentWaitTrackUpdateProcess){
		clearInterval(currentWaitTrackUpdateProcess);	
		console.log('[loggingRun] Не должно быть currentWaitTrackUpdateProcess, но он был. Убили, запускаем.');
	}
	if(currTrackSwitch.checked) startCurrentWaitTrackUpdateProcess();	// Текущий трек всегда показывается
}
loggingCheck(logging);
} // end function loggingRun

function loggingCheck(logging='logging.php') {
/* включает и выключает запись трека, а также проверяет, ведётся ли запись 
путём запроса logging.
Запрос должен вернуть JSON массив из двух значенией: ведётся ли запись bool и имя пишущегося файла
*/
//console.log('[loggingCheck] started');
let xhr = new XMLHttpRequest();
xhr.open('GET', encodeURI(logging), true); 	// Подготовим асинхронный запрос
xhr.send();
xhr.onreadystatechange = function() { // 
	if (this.readyState != 4) return; 	// запрос ещё не завершился
	if (this.status != 200) return; 	// что-то не то с сервером
	//console.log('[loggingCheck] this.response=',this.response);
	let status = JSON.parse(this.response);
	if(status[0]) { 	// состояние gpxlogger после выполнения logging.php, 1 или 0 - запущен успешно
		loggingIndicator.style.color='green';
		loggingIndicator.innerText='\u2B24';
		// Новый текущий трек
		const newTrackName = status[1].substring(0, status[1].lastIndexOf('.')) || status[1]; 	// имя нового текущего (пишущийся сейчас) трека -- имя файла без расширения		
		//console.log(status,'[loggingCheck] Новый текущий трек newTrackName=',newTrackName);
		if(!newTrackName) {	// не было возвращено имени, хотя запись трека работае. Возможно, кто-то запустил запись в какой-то не наш каталог.
			loggingSwitch.disabled = true;	// отключим переключатель, и не будем нигде включать - пусть жмут Shift-Reload
			return; 
		}
		let newTrackLI = document.getElementById(newTrackName); 	// его всегда нет? Нет, он вполне может быть, если, например, запись запустил не этот клиент
		//console.log(newTrackLI);
		if(!newTrackLI) {
			//console.log(tracks.querySelector('li[title="Current Track"]'));
			//tracks.querySelector('li[title="Current Track"]').classList.remove("currentTrackName");
			if(currentTrackName) doNotCurrentTrackName(currentTrackName);
			newTrackLI = trackLiTemplate.cloneNode(true);
			newTrackLI.id = newTrackName;
			newTrackLI.innerText = newTrackName;
			newTrackLI.hidden=false;
			newTrackLI.classList.remove("template");
			//console.log(newTrackName,newTrackLI);
			trackList.append(newTrackLI);
			doCurrentTrackName(newTrackName);	// обязательно после append, ибо вне дерева элементы не ищутся. JavaScript -- коллекция нелепиц.
		}
		else { 	// иначе он и так текущий. Авотхрен.
			if(newTrackName !== currentTrackName) doCurrentTrackName(newTrackName);	// 
		}
		// запустим слежение за логом, если ещё не
		// Но слежение за логом должно быть запущено, только если трек показывается
		// но startCurrentTrackUpdateProcess периодически запускает currentTrackUpdate
		// который при первом запуске... Но процесс запускается, а он не нужен.
		// Поэтому запускаем процесс только если указано "Текущий трек всегда показывается."
		if(currTrackSwitch.checked) {
			startCurrentTrackUpdateProcess();	// в index.php
		}
	}
	else {
		if(loggingSwitch.checked){
			loggingIndicator.style.color='red';
			loggingIndicator.innerText='\u2B24';
		}
		else {
			loggingIndicator.innerText='';
		}
	}
return;
}; // end xhr.onreadystatechange
}; // end function loggingCheck

function coverage(){
//console.log(cowerSwitch);
//console.log(mapDisplayed.firstElementChild);
if(cowerSwitch.checked){ 	// переключатель в интерфейсе загрузчика
	if(mapDisplayed.firstElementChild){ 	// список показываемых карт не пуст
		const mapname = mapDisplayed.firstChild.id;
		displayMap(mapname+'_COVER');
		coverMap.innerHTML = mapname;
	}
	else cowerSwitch.checked = false;
}
else {
	//console.log(savedLayers);
	for (let mapname in savedLayers) {
		if(mapname.indexOf('_COVER')!=-1) {
			//console.log(mapname);
			savedLayers[mapname].remove();
			//break; 	// почему-то иногда не удаляется предыдущий... Восстанавливается из куки?
		}
	}
	coverMap.innerHTML = '';
}
return;
} // end function coverage


function MOBalarm() {
//
// Global: map, cursor, currentMOBmarker, centerMark
let latlng;
if(map.hasLayer(cursor)) latlng = cursor.getLatLng(); 	// координаты известны и показываются, хотя, возможно, устаревшие
else {
	// если даже нет координат -- дадим возможность ставить маркер в центре карты
	centerMarkOn(); 	// включить крестик в середине
	latlng = centerMarkMarker.getLatLng();
	locationMOBdisplay.innerHTML = latTXT+' '+Math.round(latlng.lat*10000)/10000+'<br>'+longTXT+' '+Math.round(latlng.lng*10000)/10000;	
	//return false;	
}

currentMOBmarker = L.marker(latlng, { 	// маркер для этой точки
	icon: mobIcon,
	draggable: true,
});
currentMOBmarker.on('click', function(ev){
	currentMOBmarker = ev.target;
	clearCurrentStatus(); 	// удалим признак current у всех маркеров
	currentMOBmarker.feature.properties.current = true;
	sendMOBtoServer(); 	// отдадим данные MOB для передачи на сервер
}); 	// текущим будет маркер, по которому кликнули
currentMOBmarker.on('dragend', function(event){
	//console.log("MOB marker dragged end, send to server new coordinates",currentMOBmarker);
	sendMOBtoServer(); 
}); 	// отправим на сервер новые сведения, когда перемещение маркера закончилось. Если просто указать функцию -- в sendMOBtoServer передаётся event. Если в одну строку -- всё равно передаётся event. Что за???
clearCurrentStatus(); 	// удалим признак current у всех маркеров
currentMOBmarker.feature = { 	// укажем признак "текущий маркер" как GeoJson свойство
	type: 'Feature',
	properties: {current: true},
};
//console.log('[MOBalarm] currentMOBmarker:',currentMOBmarker);
mobMarker.addLayer(currentMOBmarker);
if(!map.hasLayer(mobMarker)) mobMarker.addTo(map); 	// выставим маркер

if(loggingIndicator !== undefined && !loggingSwitch.checked) {
	loggingSwitch.checked = true;
	loggingRun(); 	// хотя в loggingSwitch стоит onChange="loggingRun();" изменение loggingSwitch.checked = true; не приводит к срабатыванию обработчика
}
if(mobMarker.getLayers().length > 2) delMOBmarkerButton.disabled = false;

sendMOBtoServer(); 	// отдадим данные MOB для передачи на сервер

return true;
} // end function MOBalarm


function clearCurrentStatus() {
/* удаляет признак "текущий маркер" у всех маркеров мультислоя mobMarker */
mobMarker.eachLayer(function (layer) { 	// удалим признак current у какого-то маркера
	if((layer instanceof L.Marker) && (layer.feature.properties.current == true))	{
		layer.feature.properties.current = false;
	}
});
} // end function clearCurrentStatus


function MOBclose() {
mobMarker.remove(); 	// убрать мультислой-маркер с карты
mobMarker.clearLayers(); 	// очистить мультислой от маркеров
mobMarker.addLayer(toMOBline); 	// вернём туда линию
sendMOBtoServer(false); 	// передадим на сервер, что режим MOB прекращён
document.cookie = "GaladrielMapMOB=; expires=0; path=/; samesite=Lax"; 	// удалим куку
azimuthMOBdisplay.innerHTML = '&nbsp;';
distanceMOBdisplay.innerHTML = '&nbsp;';
directionMOBdisplay.innerHTML = '&nbsp;';
locationMOBdisplay.innerHTML = '&nbsp;';
delMOBmarkerButton.disabled = true;
//centerMarkOff(); 	// выключить крестик в середине -- не надо, ибо при закрытии панели оно уже вызывается
sidebar.close();	// закрыть панель
} // end function MOBclose


function delMOBmarker(){
/* Удаляет текущий маркер MOB
mobMarker это LayerGroup 
*/
let layers = mobMarker.getLayers();
if(layers.length < 3) return; // т.е., там линия и один маркер
mobMarker.removeLayer(currentMOBmarker);
layers = mobMarker.getLayers(); 	// мы не знаем, какой именно маркер был удалён -- текущий мог быть любым
//console.log(layers);
for(let i=layers.length-1; i>=0; i--){ 	// мы не знаем, где там линия
	if (layers[i] instanceof L.Marker) { 	// почему это здесь не работает? Может быть, потому что L.Marker? И правда...
	//if (layers[i].options.icon) {
		currentMOBmarker = layers[i]; 	// последний маркер в mobMarker
		currentMOBmarker.feature.properties.current = true;
		//console.log('New currentMOBmarker after del ',currentMOBmarker);
		break;
	}
}
//currentMOBmarker = layers[layers.length-1]; 	// последний маркер в mobMarker, но в layers их же прежнее число
if(layers.length < 3) delMOBmarkerButton.disabled = true; // т.е., там линия и один маркер
sendMOBtoServer(); 	// отдадим данные MOB для передачи на сервер
} // end function delMOBmarker


function sendMOBtoServer(status=true){
/* Кладёт данные MOB в массив, который передаётся на сервер 
mobMarker -- это Leaflet LayerGroup, т.е. там исчерпывающая информация
*/
//console.log("sendMOBtoServer status=",status);
upData.MOB = {};
upData.MOB.class = 'MOB';
upData.MOB.status = status; 	// 
upData.MOB.points = [];
//upData.MOB.LineString = {};
let mobMarkerJSON = mobMarker.toGeoJSON(); 	//
for(let feature of mobMarkerJSON.features){
	switch(feature.geometry.type){
	case "Point":
		upData.MOB.points.push({'coordinates':feature.geometry.coordinates,'current':feature.properties.current});
		break;
	case "LineString":
		//upData.MOB.LineString.coordinates = feature.geometry.coordinates;	// линия только одна
		break;
	}
}
//console.log('[sendMOBtoServer] Sending to server upData.MOB:',upData.MOB);
//console.log('[sendMOBtoServer] upData=',JSON.stringify(upData.MOB));
//console.log('[sendMOBtoServer] spatialWebSocket.readyState:',spatialWebSocket.readyState);
if(spatialWebSocket.readyState == 1) {
	spatialWebSocket.send('?UPDATE={"updates":['+JSON.stringify(upData.MOB)+']};'); 	// отдадим данные MOB для передачи на сервер через глобальный сокет для передачи координат. Он есть, иначе -- нет координат и нет проблем.
}
// Посадим куку
mobMarkerJSON = JSON.stringify(mobMarkerJSON);
const expires =  new Date();
expires.setTime(expires.getTime() + (30*24*60*60*1000)); 	// протухнет через месяц
document.cookie = "GaladrielMapMOB="+mobMarkerJSON+"; expires="+expires+"; path=/; samesite=Lax"; 	// 
} // end function sendMOBtoServer

// Круги дистанции
function distCirclesUpdate(distCircles){
/* Устанавливает диаметр и подписи кругов дистанции 
в зависимости от координат и масштаба.
*/
if(!distCircles[0] || !distCircles[0].getLatLng()) return;
let distCirclesRadius;
const zoom = Math.round(map.getZoom());	// масштаб может быть дробным во время собственно масштабирования
const metresPerPixel = (40075016.686 * Math.abs(Math.cos(distCircles[0].getLatLng().lat*(Math.PI/180))))/Math.pow(2, map.getZoom()+8); 	// in WGS84
//console.log("[distCirclesUpdate] zoom",zoom,"метров в пикселе:",metresPerPixel);
switch(zoom){
case 0:
case 1:
case 2:
case 3:
case 4:
	distCirclesRadius = [200000,500000,1000000,2000000];
	break
case 5:
case 6:
	distCirclesRadius = [50000,100000,150000,300000];
	break
case 7:
case 8:
	distCirclesRadius = [10000,20000,50000,100000];
	break
case 9:
case 10:
	distCirclesRadius = [5000,10000,20000,30000];
	break
case 11:
case 12:
	distCirclesRadius = [1000,2000,5000,10000];
	break
case 13:
case 14:
	distCirclesRadius = [200,500,1000,2000];
	break
case 15:
	distCirclesRadius = [100,200,300,500];
	break
case 16:
default:
	distCirclesRadius = [50,100,200,300];
}
let label;
for (let i=0; i<4; i++)	{
	distCircles[i].setRadius(distCirclesRadius[i]);
	distCircles[i].unbindTooltip();
	if(distCirclesRadius[0]>=1000) label = (distCirclesRadius[i]/1000).toString()+' '+dashboardKiloMeterMesTXT
	else label = distCirclesRadius[i].toString();
	distCircles[i].bindTooltip(label,{permanent:true,direction:'center',className:'distCirclesRadiusTooltip',offset:[0,-distCirclesRadius[i]/metresPerPixel]});	
}
//return distCirclesRadius[2]/metresPerPixel;	
} // end function distCirclesUpdate

function distCirclesToggler() {
/* включает/выключает показ окружностей дистанции по переключателю в интерфейсе */
if(distCirclesSwitch.checked) {
	distCircles.forEach(circle => circle.addTo(positionCursor));
	// Посадим куку
	const expires =  new Date();
	expires.setTime(expires.getTime() + (30*24*60*60*1000)); 	// протухнет через месяц
	document.cookie = 'GaladrielMapdistCirclesSwitch=1; expires='+expires+"; path=/; samesite=Lax"; 	// 
}
else {
	distCircles.forEach(circle => circle.removeFrom(positionCursor));
	// Посадим куку
	const expires =  new Date();
	expires.setTime(expires.getTime() + (30*24*60*60*1000)); 	// протухнет через месяц
	document.cookie = 'GaladrielMapdistCirclesSwitch=0; expires='+expires+"; path=/; samesite=Lax"; 	// 
}
} // end function distCirclesToggler


function windSwitchToggler() {
/* включает/выключает показ символа ветра по переключателю в интерфейсе */
if(windSwitch.checked) {
	windSymbolMarker.addTo(positionCursor);
	// Посадим куку
	const expires =  new Date();
	expires.setTime(expires.getTime() + (30*24*60*60*1000)); 	// протухнет через месяц
	document.cookie = "GaladrielWindSwitch=1; expires="+expires+"; path=/; SameSite=Lax;";
}
else {
	windSymbolMarker.remove();
	// Посадим куку
	const expires =  new Date();
	expires.setTime(expires.getTime() + (30*24*60*60*1000)); 	// протухнет через месяц
	document.cookie = 'GaladrielWindSwitch=0; expires='+expires+"; path=/; samesite=Lax"; 	// 
}
} // end function windSwitchToggler

function windSymbolUpdate(TPVdata){
/**/
//console.log('[windSymbolUpdate] useTrueWind=',useTrueWind);
if(useTrueWind){	// options.js указано использовать истинный ветер
	//console.log('[windSymbolUpdate] wspeedt=',TPVdata.wspeedt,'wanglet=',TPVdata.wanglet,'track=',TPVdata.track);
	if(TPVdata.wspeedt && TPVdata.wanglet && TPVdata.track){
		let dir = TPVdata.wanglet + TPVdata.track - 90;	// картинка-то у нас горизонтальна
		if(dir >= 360) dir -= 360;
		realWindSymbolUpdate(dir,TPVdata.wspeedt);
	}
	else realWindSymbolUpdate();
}
else {	// указано использовать вымпельный ветер
	//console.log('[windSymbolUpdate] heading=',TPVdata.heading,'wind dir=',TPVdata.wangler+TPVdata.heading,'wspeedr=',TPVdata.wspeedr);
	if(TPVdata.wspeedr && TPVdata.wangler){
		let dir = TPVdata.wangler + (TPVdata.heading || TPVdata.track) - 90;	// картинка-то у нас горизонтальна
		if(dir >= 360) dir -= 360;
		realWindSymbolUpdate(dir,TPVdata.wspeedr);
	}
	else realWindSymbolUpdate();
}
} // end function windSymbolUpdate

function realWindSymbolUpdate(direction=0,speed=0){
/**/
// Символ
let windSVG = document.getElementById('wSVGimage');
if(!windSVG) return;	// картинка там как-то не сразу появляется
let windMark = windSVG.getElementById('wMark');

while (windMark.firstChild) {	// удалим все символы из значка
	windMark.removeChild(windMark.firstChild);
}
let pos = 0, w25=0;
if(speed){	// стрелка
	windMark.appendChild(document.createElementNS('http://www.w3.org/2000/svg', 'use'));
	windMark.lastChild.setAttribute('x',String(pos));
	windMark.lastChild.setAttribute('y','0');
	windMark.lastChild.setAttributeNS('http://www.w3.org/1999/xlink','xlink:href','#bLine');
	pos += 70;
}
if(Math.floor(speed/25)){	// перо 25 м/сек
	speed -= 25;
	w25=1;
}
for(let i=Math.floor(speed/5); i>0; i--){	// перья 5 м/сек
	speed -= 5;
	//console.log('pos',pos);
	windMark.appendChild(document.createElementNS('http://www.w3.org/2000/svg', 'use'));
	windMark.lastChild.setAttribute('x',String(pos));
	windMark.lastChild.setAttribute('y',0);
	windMark.lastChild.setAttributeNS('http://www.w3.org/1999/xlink', 'xlink:href','#w5');
	pos += 10;
}
if(w25){
	windMark.appendChild(document.createElementNS('http://www.w3.org/2000/svg', 'use'));
	windMark.lastChild.setAttribute('x',String(pos));
	windMark.lastChild.setAttribute('y',0);
	windMark.lastChild.setAttributeNS('http://www.w3.org/1999/xlink', 'xlink:href','#w25');
}
if(Math.floor((speed*10)/25)) {	// половинное перо
	windMark.appendChild(document.createElementNS('http://www.w3.org/2000/svg', 'use'));
	if(pos==70) windMark.lastChild.setAttribute('x',String(70-20));
	else windMark.lastChild.setAttribute('x',String(70-2.5));
	windMark.lastChild.setAttribute('y',0);
	windMark.lastChild.setAttributeNS('http://www.w3.org/1999/xlink', 'xlink:href','#w2.5');
}

// напрвление
windSymbolMarker.setRotationAngle(direction);
} // end function realWindSymbolUpdate




// Общие функции

function loadScriptSync(scriptURL){
/* Синхронная загрузка javascript 
Вопреки распространённому мнению, script.async = false не приводит к асинхронной загрузке.
Это свойство в случае загрузки скрипта из скрипта вообще ничего не делает, и имеет смысл
только при загрзке <script src=""><script>, где указывает, что надо сохранить порядок загрузки
*/
const xhr = new XMLHttpRequest();
xhr.open('GET', scriptURL, false); 	// Подготовим синхронный запрос
xhr.send();
if (xhr.status == 200) { 	// Успешно
	let script = document.createElement("script");
	script.textContent = xhr.responseText;
	document.head.appendChild(script);
	//console.log("[loadScriptSync] Загружен скрипт",scriptURL,script);
	return script;
}
return false;
} // end function loadScriptSync


function bearing(latlng1, latlng2) {
/**/
//console.log(latlng1,latlng2)
const rad = Math.PI/180;
let lat1,lat2,lon1,lon2;
if(latlng1.lat) lat1 = latlng1.lat * rad;
else lat1 = latlng1.latitude * rad;
if(latlng2.lat) lat2 = latlng2.lat * rad;
else lat2 = latlng2.latitude * rad;
if(latlng1.lng) lon1 = latlng1.lng * rad;
else if(latlng1.lon) lon1 = latlng1.lon * rad;
else lon1 = latlng1.longitude * rad;
if(latlng2.lng) lon2 = latlng2.lng * rad;
else if(latlng2.lon) lon2 = latlng2.lon * rad;
else lon2 = latlng2.longitude * rad;
//console.log('lat1=',lat1,'lat2=',lat2,'lon1=',lon1,'lon2=',lon2)

let y = Math.sin(lon2 - lon1) * Math.cos(lat2);
let x = Math.cos(lat1) * Math.sin(lat2) - Math.sin(lat1) * Math.cos(lat2) * Math.cos(lon2 - lon1);
//console.log('x',x,'y',y)

let bearing = ((Math.atan2(y, x) * 180 / Math.PI) + 360) % 360;
if(bearing >= 360) bearing = bearing-360;

return bearing;
} // end function bearing


/**
Эти казлы так и ниасилили юникод в JavaScript. Багу более 15 лет.
 * ASCII to Unicode (decode Base64 to original data)
 * @param {string} b64
 * @return {string}
 */
function atou(b64) {
  return decodeURIComponent(escape(atob(b64)));
}

/**
 * Unicode to ASCII (encode data to Base64)
 * @param {string} data
 * @return {string}
 */
function utoa(data) {
  return btoa(unescape(encodeURIComponent(data)));
}


function realtime(dataUrl,fUpdate,upData) {
/*
fUpdate - функция обновления. Все должно делаться в ней. Получает json object
upData - данные для отправки
*/
//console.log(dataUrl);
//console.log('RealTime upData',upData);
if(upData) {
	if(dataUrl.includes('?')) dataUrl += '&upData=';
	else dataUrl += '?upData=';
	dataUrl += encodeURI(JSON.stringify(upData));
}
fetch(dataUrl)
.then((response) => {
    return response.text();
})
.then(data => { 		// The Body mixin of the Fetch API represents the body of the response/request, allowing you to declare what its content type is and how it should be handled.
	try {
		//console.log(data);
		return JSON.parse(data);
	}
	catch(err) {
		// error handling
		//console.log(err);
		throw Error(err); 	// просто сбросим ошибку ближайшему catch
	}
})
.then(data => {
	//console.log('RealTime inbound data',data);
	for (let prop in upData) {  	// очистим передаваемые данные, раз сеанс связи состоялся
		delete upData[prop];
	}
	fUpdate(data);
})
.catch( (err) => {
	fUpdate({'error':err.message});
})

} 	// end function realtime



/* Определения классов */
// control для копирования в клипбоард
L.Control.CopyToClipboard = L.Control.extend({
	onAdd: function(map) {
			var div = L.DomUtil.create('div','CopyToClipboardClass');
			div.innerHTML = `
				<span id='copyToClipboardMessage' onClick='doCopyToClipboard()'></span>
				<input id='copyToClipboardField' type='text'  size='12' >
			`;
			return div;
		},
	onRemove: function(map) {
		// Nothing to do here
		}
});

L.LayerGroup.include({	
	// рекурсивный eachLayer. Их просили это сделать с 2016 года, но они только плачь по Украине осилили. 
	// https://github.com/Leaflet/Leaflet/issues/4461
	eachLayerRecursive: function(method, context) {
		this.eachLayer(function(layer) {
			if (layer._layers) layer.eachLayerRecursive(method, context);	// а почему они не применяют instanceof? Медленно? 
			else method.call(context, layer);
		});
	}
});

L.LayerGroup.include({	
	// рекурсивный hasLayer
	hasLayerRecursive: function(what) {
		let res = false;
		if(this.hasLayer(what)) return true;
		for(const layer of this.getLayers()){	// нужно прекратить обход, eachLayer не подойдёт
			if(!(layer instanceof L.LayerGroup)) continue;	// это не LayerGroup
			if(layer.hasLayer(what)) return true;
			else res = layer.hasLayerRecursive(what);
		}
		return res;
	}
});

/*//////// for collision test purpose /////////
// Функции для отладки предупреждения о столкновениях
function displayCollisionAreas(selfArea=null){
//
function mkPolyline(area){
	let polyline = [];
	area.forEach(point => {polyline.push([point.lat,point.lon]);});
	polyline.push([area[0].lat,area[0].lon]);
	return polyline;
};
//collisisonAreas.remove();	// так гораздо медленней
collisisonAreas.clearLayers();	// очистим слой 
if(selfArea){
	//console.log('selfArea:',selfArea);
	let polyline = mkPolyline(selfArea);
	collisisonAreas.addLayer(L.polyline(polyline,{color: 'red',weight: 2,}));
}
for(let vessel in vehicles){
	//console.log(vessel,vehicles[vessel]);
	if(!vehicles[vessel].options.collisionArea) continue;	// 
	//console.log(vessel,vehicles[vessel].options.collisionArea);
	let polyline = mkPolyline(vehicles[vessel].options.collisionArea);
	//console.log('vessel',vessel,'course=',vehicles[vessel].options.course,'polyline:',polyline.length);
	collisisonAreas.addLayer(L.polyline(polyline,{color: 'red',weight: 2,}));
};
collisisonAreas.addTo(map);
} // end function displayCollisionAreas

/*//////// for collision test purpose /////////

