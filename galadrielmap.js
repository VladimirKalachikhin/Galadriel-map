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
MOBtabHighLight(on=false)
setMOBpopup(layer)
createMOBpointMarker(mobMarkerJSON)
clearCurrentStatus()	удаляет признак "текущий маркер" у всех маркеров мультислоя mobMarker
MOBclose()
realMOBclose()
delMOBmarker()
makeMOBmarkerCurrent(LMarker)
is_currentMOBmarkerSelf()
mobMarkerDragendFunction(event)
mobMarkerClickFunction(event)
sendMOBtoServer()
MOBtoGeoJSON(MOBdata)
GeoJSONtoMOB(mobMarkerJSON)

distCirclesUpdate()	Устанавливает диаметр и подписи кругов дистанции
distCirclesToggler() включает/выключает показ окружностей дистанции по переключателю в интерфейсе

windSwitchToggler()
windSymbolUpdate(TPVdata)
realWindSymbolUpdate(direction=0,speed=0)
restoreDisplayedRoutes()

loadScriptSync(scriptURL)	Синхронная загрузка javascriptbearing(latlng1, latlng2)

bearing(latlng1, latlng2)
tileNum2degree(zoom,xtile,ytile) Tile numbers to lon./lat. left top corner

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
/*/
// определение имени файла этого скрипта, например, чтобы знать пути на сервере
const i = document.getElementsByTagName('script').length - 1; 	// это так, потому что эта часть сработает при загрузке скрипта, и он в этот момент - последний http://feather.elektrum.org/book/src.html
var galadrielmapScript = scripts[i];
console.log(i,galadrielmapScript);
alert(galadrielmapScript.src);
/*/
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
}; // end function listPopulate

function getCookie(name) {
// возвращает cookie с именем name, если есть, если нет, то undefined
name=name.trim();
var matches = document.cookie.match(new RegExp(
	"(?:^|; )" + name.replace(/([\.$?*|{}\(\)\[\]\\\/\+^])/g, '\\$1') + "=([^;]*)"
	)
);
return matches ? decodeURIComponent(matches[1]) : null;
};

function doSavePosition(){
/* Сохранение положения
global map, mapDisplayed, document, currTrackSwitch
*/
let toSave = {'startCenter':map.getCenter()};
toSave['startZoom'] = map.getZoom();
// Сохранение показываемых карт
let openedNames = [];
for (let i = 0; i < mapDisplayed.children.length; i++) { 	// для каждого потомка списка mapDisplayed
	//console.log('mapDisplayed li',mapDisplayed.children[i]);
	openedNames[i] = mapDisplayed.children[i].id; 	// 
}
toSave['layers'] = openedNames;
// Сохранение показываемых маршрутов
openedNames = [];
for (let i = 0; i < routeDisplayed.children.length; i++) { 	// для каждого потомка списка mapDisplayed
	openedNames[i] = routeDisplayed.children[i].innerHTML; 	// 
}
toSave['showRoutes'] = openedNames;
// Сохранение переключателей и параметров
toSave['currTrackSwitch'] = currTrackSwitch.checked;
if(typeof loggingSwitch !== 'undefined') toSave['loggingSwitch'] = loggingSwitch.checked;
toSave['SelectedRoutesSwitch'] = SelectedRoutesSwitch.checked;
toSave['minWATCHinterval'] = minWATCHinterval;
toSave['showMapsList'] = showMapsList;
storageHandler.save(toSave);
//console.log('Position, layers and options saved');
}; // end function doSavePosition

// Функции выбора - удаления карт
async function selectMap(node) { 	
// Выбор карты из списка имеющихся. Получим объект
mapDisplayed.insertBefore(node,mapDisplayed.firstChild); 	// из списка доступных в список показываемых (объект, на котором событие, добавим в конец потомков mapDisplayed)
node.classList.remove("showedMapName");
node.hidden = false;
node.onclick = function(event){deSelectMap(event.currentTarget);};
displayMap(node.id);
}; // end function selectMap

async function deSelectMap(node) {
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
}; // end function deSelectMap

function displayMap(mapname) {
/* Создаёт leaflet lauer с именем, содержащемся в mapname, и заносит его на карту
 Делает запрос к tileproxy/cacheControl.php для получения параметров карты
 Если в параметрах карты есть проекция, и она EPSG3395, 
 или в имени карты есть EPSG3395 - делает слой в проекции с пересчётом с помощью L.tileLayer.Mercator
*/
mapname=mapname.trim(mapname);
// Всегда будем спрашивать параметры карты
let mapParm = new Array(); 	// переменная для параметров карты
const xhr = new XMLHttpRequest();
xhr.open('GET', tileCacheControlURI+'?getMapInfo='+mapname, false); 	// Подготовим синхронный запрос
xhr.send();
if (xhr.status == 200) { 	// Успешно
	try {
		//console.log('[displayMap] xhr.responseText:',xhr.responseText);
		mapParm = JSON.parse(xhr.responseText); 	// параметры карты
	}
	catch(err) { 	// 
		return;
	};
}
else return;
// javascript в загружаемом источнике на открытие карты
//console.log('[displayMap] mapParm:',mapParm);
if(mapParm['data'] && mapParm['data']['javascriptOpen']) eval(mapParm['data']['javascriptOpen']);
let minNativeZoom,maxNativeZoom;
if(mapParm.minZoom>3){
	minNativeZoom = mapParm.minZoom;
	mapParm.minZoom = 3;
};
if(mapParm.maxZoom<16){
	maxNativeZoom = mapParm.maxZoom;
	mapParm.maxZoom += 1;
};
if(mapParm['bounds']) {
	mapParm['bounds'] = [mapParm['bounds']['leftTop'],mapParm['bounds']['rightBottom']];
	//console.log('[displayMap] mapParm:',mapParm);
	if(mapParm['bounds'][0]['lng']>0 && mapParm['bounds'][1]['lng']<=0){	// граница переходит антимередиан
		// Caution: if the area crosses the antimeridian (often confused with the International Date Line), you must specify corners outside the [-180, 180] degrees longitude range.
		mapParm['bounds'][0]['lng'] -= 360;
		mapParm['bounds'][1]['lng'] += 360;
	};
};
//console.log('[displayMap] mapParm[bounds]:',mapParm['bounds']);
let layerParm = {
	"minZoom":mapParm.minZoom,
	"maxZoom":mapParm.maxZoom,
	"minNativeZoom":minNativeZoom,
	"maxNativeZoom":maxNativeZoom,
	//"bounds": mapParm['bounds']
};
let vectorLayerParm = {
	"style": mapParm['mapboxStyle'],
	"minZoom":mapParm.minZoom,
	"bounds": mapParm['bounds']
};
//console.log('[displayMap] layerParm:',layerParm);
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
	// Потому что в javascript при закрытии карты могут указать aditionalTileCachePath = []; (как, собственно, и было в Weather) и тогда после закрытия такой карты открыть другую не получится
	if(!additionalTileCachePath.length) additionalTileCachePath.push('');
	for(let addPath of additionalTileCachePath) {
		let mapnameThis = mapname+addPath; 	// 
		let tileCacheURIthis = tileCacheURI.replace('{map}',mapnameThis); 	// глобальная переменная
		if(mapParm['ext'])	tileCacheURIthis = tileCacheURIthis.replace('{ext}',mapParm['ext']); 	// при таком подходе можно сделать несколько слоёв с одним запросом параметров
		//console.log(tileCacheURIthis);
		//console.log('mapname=',mapname,savedLayers[mapname]);
		if((mapParm['epsg']&&String(mapParm['epsg']).indexOf('3395')!=-1)||(mapname.indexOf('EPSG3395')!=-1)) {
			savedLayers[mapname].addLayer(L.tileLayer.Mercator(tileCacheURIthis, layerParm));
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
			savedLayers[mapname].addLayer(L.mapboxGL(vectorLayerParm));
		}
		else {
			savedLayers[mapname].addLayer(L.tileLayer(tileCacheURIthis, layerParm));
		}
	}
}
else {
	let mapnameThis = additionalTileCachePath ? mapname+additionalTileCachePath : mapname;
	let tileCacheURIthis = tileCacheURI.replace('{map}',mapnameThis); 	// глобальная переменная
	if(mapParm['ext'])	tileCacheURIthis = tileCacheURIthis.replace('{ext}',mapParm['ext']); 	// при таком подходе можно сделать несколько слоёв с одним запросом параметров
	//console.log(tileCacheURIthis);
	if((mapParm['epsg']&&String(mapParm['epsg']).indexOf('3395')!=-1)||(mapname.indexOf('EPSG3395')!=-1)) {
		if(!savedLayers[mapname])	savedLayers[mapname] = L.tileLayer.Mercator(tileCacheURIthis, layerParm);
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
		if(!savedLayers[mapname])	savedLayers[mapname] = L.mapboxGL(vectorLayerParm);
	}
	else {
		if(!savedLayers[mapname])	savedLayers[mapname] = L.tileLayer(tileCacheURIthis, layerParm);
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
}; // end function displayMap

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
}; // end function removeMap

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
}; // end function showMapsToggle


// Функции выбора - удаления треков
async function selectTrack(node,trackList,trackDisplayed,displayTrack) { 	
/* Выбор трека из списка имеющихся. 
node - объект li, элемент списка имеющихся, который выбрали
trackList - объект ul, список имеющихся
trackDisplayed - объект ul, список выбранных
displayTrack - функция показывания того, что соответствует выбранному элементу
global deSelectTrack() currentTrackShowedFlag
*/
console.log('[selectTrack] trackDisplayed.firstChild:',trackDisplayed.firstChild);
trackDisplayed.insertBefore(node,trackDisplayed.firstChild); 	// из списка доступных в список показываемых (объект, на котором событие, добавим в конец потомков mapDisplayed)
node.onclick = function(event){deSelectTrack(event.currentTarget,trackList,trackDisplayed,displayTrack);};
if(node.title.toLowerCase().indexOf("current")!= -1) {	// текущий трек
	currentTrackShowedFlag = 'loading'; 	// укажем, что трек сейчас загружается
	startCurrentTrackUpdateProcess();	// запустим обновление трека
}
//console.log('[selectTrack] node.title=',node.title,currentTrackShowedFlag);
displayTrack(node); 	// создадим трек
}; // end function selectTrack

async function deSelectTrack(node,trackList,trackDisplayed,displayTrack) {
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
}; // end function deSelectTrack

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
	xhr.open('GET', trackDirURI+encodeURI('/'+trackName+'.gpx'), true); 	// В этом грёбаном языке специальная грёбаная функция для получения правильного url портит [], которые являются частью ipv6
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
		};
		//console.log(savedLayers[trackName]);
		savedLayers[trackName].addTo(map); 	// нарисуем его на карте
	};
};
}; // end function displayTrack

async function displayRoute(routeNameNode) {
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
		if(! updateRoutesInterval) {
			// Запуск динамического обновления показываемых маршрутов,
			// если ещё не запущен и есть адрес обновлялки
			if(updateRouteServerURI) updateRoutesInterval = setInterval(realtime,3000,updateRouteServerURI,routeUpdate);
		};
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
}; // end function displayRoute

async function updateCurrTrack() {
// Получим GeoJSON - ломаную из скольких-то последних путевых точек, или false, если с последнего
// обращения нет новых точек
// в формате GeoJSON
//console.log('[updateCurrTrack]',currentTrackServerURI,currentTrackName);
var xhr = new XMLHttpRequest();
xhr.open('GET', currentTrackServerURI+encodeURI('?currTrackName='+currentTrackName), true); 	// Подготовим асинхронный запрос
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
			if((typeof loggingSwitch !== 'undefined') && loggingSwitch.checked){ 	// этот клиент сказал писать трек, состояние loggingSwitch восстанавливается из куки в index.php
				loggingIndicator.style.color='red';
				loggingIndicator.innerText='\u2B24';
				loggingRun();	// попытаемся запустить запись трека
			}
			else {
				loggingIndicator.style.color='';
				loggingIndicator.innerText='';
				loggingIndicator.style.color=null;
				if(currentWaitTrackUpdateProcess){
					clearInterval(currentWaitTrackUpdateProcess);	
					console.log('[updateCurrTrack] Не должно быть currentWaitTrackUpdateProcess, но он был. Убили, запускаем.');
				};
				if(currTrackSwitch.checked) startCurrentWaitTrackUpdateProcess();	// Текущий трек всегда показывается
			};
		};
	};
};
}; // end function updateCurrTrack

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
}; // end function XYentryFields

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
};
}; // end function loaderListPopulate

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
}; // end function coloreSelectedTiles

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
}; // end function chkColoreSelectedTile

function createDwnldJob() {
/* Собирает задания на загрузку: для каждой карты кладёт на сервер csv с номерами тайлов текущего масштаба.
Считается, что номера тайлов указываются на сфере */
//alert('submit '+mapDisplayed.children.length+' maps');
let tileXs = dwnldJob.getElementsByClassName("tileX");
let tileYs = dwnldJob.getElementsByClassName("tileY");
let zoom = dwnldJobZoom.innerText;
let XYs = '', XYsE = '', xhr = [];
for (let i = 0; i < mapDisplayed.children.length; i++) { 	// для каждого потомка списка mapDisplayed
	let mapname = mapDisplayed.children[i].id; 	// 
	let uri;
	if(mapname.indexOf('EPSG3395')==-1) {	// карта - на сфере, пишем тайлы как есть
		if(!XYs.length) {
			for (var k = 0; k < tileXs.length; k++) {
				if(tileXs[k].value && tileYs[k].value) 	XYs += tileXs[k].value+','+tileYs[k].value+'\n';
			}
		}
		//console.log(XYs);
		uri = '?loaderJob='+mapname+'.'+zoom+'&xys='+XYs+'&infinitely';
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
		uri = '?loaderJob='+mapname+'.'+zoom+'&xys='+XYsE+'&infinitely';
	}
	//console.log('[createDwnldJob] uri=',uri);
	//continue;
	xhr[i] = new XMLHttpRequest();
	xhr[i].open('GET', tileCacheControlURI+encodeURI(uri), true); 	// Подготовим асинхронный запрос
	xhr[i].send();
	xhr[i].onreadystatechange = function() { // 
		if (this.readyState != 4) return; 	// запрос ещё не завершился
		if (this.status != 200) return; 	// что-то не то с сервером
		let response = JSON.parse(this.response);
		if(response && response['status'] == '0') { 	// первым должен идти код возврата eval запуска загрузчика
			loaderIndicator.style.color='green';
			loaderIndicator.style.cursor='pointer';
			//loaderIndicator.innerText='\u263A';
			loaderIndicator.title=downloadLoaderIndicatorOnTXT;
			loaderIndicator.onclick=function (){chkLoaderStatus('stop');};
		}
		else {
			loaderIndicator.style.color='red';
			loaderIndicator.style.cursor='pointer';
			//loaderIndicator.innerText='\u2639';
			loaderIndicator.title=downloadLoaderIndicatorOffTXT;
			loaderIndicator.onclick=function (){chkLoaderStatus('start');};
		}
		if(response && response['jobName']) dwnldJobList.innerHTML += '<li>' + response['jobName'] + '</li>\n';
	};
};
}; 	// end function createDwnldJob

function chkLoaderStatus(restartLoader=false) {
/*  */
let xhr = new XMLHttpRequest();
let url = '?loaderStatus';
dwnldJobList.innerHTML = '';
if(restartLoader=='start') {
	url += '&restartLoader&infinitely';
	dwnldJobList.innerHTML = 'Send (re)start loader<br>';
}
else if(restartLoader=='stop') {
	url += '&stopLoader';
	dwnldJobList.innerHTML = 'Send stop loader<br>';
};
xhr.open('GET', tileCacheControlURI+encodeURI(url), true); 	// Подготовим асинхронный запрос
xhr.send();
xhr.onreadystatechange = function() { // 
	if (this.readyState != 4) return; 	// запрос ещё не завершился
	if (this.status != 200) return; 	// что-то не то с сервером
	//console.log('[chkLoaderStatus] this.response=',this.response);
	let {loaderRun,jobsInfo} = JSON.parse(this.response);
	//console.log('[chkLoaderStatus]',loaderRun,jobsInfo,JSON.stringify(jobsInfo));
	
	//loaderIndicator.innerText='\u2B24 ';
	if((JSON.stringify(jobsInfo)!=='[]') && !loaderRun){	// есть задания, но загрузчик не запущен. Менее через жопу выяснить, не пуст ли объект в этом кривом языке нельзя. При том, что в PHP оно всегда array.
	//if(jobsInfo.length && !loaderRun){	// есть задания, но загрузчик не запущен
		loaderIndicator.style.color='red';
		loaderIndicator.style.cursor='pointer';
		//loaderIndicator.innerText='\u2639';
		loaderIndicator.title=downloadLoaderIndicatorOffTXT;
		loaderIndicator.onclick=function (){chkLoaderStatus('start');};
		let liS = '';
		for(let jobName in jobsInfo){
			liS += `<li  ><span>${jobName} </span><span style='font-size:75%;'>${jobsInfo[jobName]}%</span></li>`;
		}
		dwnldJobList.innerHTML += liS;
	}
	else if(loaderRun){	// загрузчик запущен
		loaderIndicator.style.color='green';
		loaderIndicator.style.cursor='pointer';
		//loaderIndicator.innerText='\u263A';
		loaderIndicator.title=downloadLoaderIndicatorOnTXT;
		loaderIndicator.onclick=function (){chkLoaderStatus('stop');};

		let liS = '';
		for(let jobName in jobsInfo){
			liS += `<li  ><span>${jobName} &nbsp; </span><span style='font-size:115%;'>${jobsInfo[jobName]}%</span></li>`;
		}
		dwnldJobList.innerHTML += liS;
	}
	else {	// загрузчик не запущен и нет заданий - загрузчик не должен быть запущен
		loaderIndicator.style.color='gray';
		loaderIndicator.style.cursor='default';
		loaderIndicator.onclick=null;
		loaderIndicator.title='';
		//loaderIndicator.innerText=' ';
	}
}; // end function onreadystatechange
}; // end function chkLoaderStatus


// Функции рисования маршрутов
function routeControlsDeSelect() {
// сделаем невыбранными кнопки управления рисованием маршрута. Они должны быть и так не выбраны, но почему-то...
for(let element of document.getElementsByName('routeControl')){
	element.checked=false;
	element.disabled=true;
}   
}; // end function routeControlsDeSelect

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
}; // end function getGPXicon

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
};	// end function delShapes


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
}; // end function tooggleEditRoute

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
}; // end function createEditableMarker

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
storageHandler.save('RestoreMeasuredPaths',toSave);
} 	// end function doSaveMeasuredPaths

function doRestoreMeasuredPaths() {
/*Global drivedPolyLineOptions*/
let RestoreMeasuredPaths = storageHandler.restore('RestoreMeasuredPaths'); 	// storageHandler from galadrielmap.js
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
//console.log('[saveGPX] route:',route);
if(route.features.length){	// какие-то объекты есть
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
}
else {	// никаких объектов нет
	route = '';
	currentRoute = null;
};

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
//console.log('[flyByString] stringPos=',stringPos);
let error;
try {
    var position = new Coordinates(stringPos); 	// Разберём строку как координаты https://github.com/otto-dev/coordinate-parser
	//console.log('[flyByString] position:',position);
	const lat=position.getLatitude();
	const lon=position.getLongitude();
	map.setView(L.latLng([lat,lon])); 	// подвинем карту в указанное место
	let xhr = new XMLHttpRequest();	// Запросим сервис геокодирования на предмет - что у нас в этом месте
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
} catch (error) { 	// coordinate-parser обломался, строка - не координаты.
	//console.log('[flyByString] stringPos=',stringPos,error);
	// А не номер тайла ли там?
	const digits = stringPos.trim().match(new RegExp("/(?<!-|\.)\d+(?!\.)/g"));	// вытащим неотрицательные целые числа из строки. Выражение неправильное, оно возвращает вторые и далеее цифры после запятой. Но так бывает редко ;-)
	//console.log('[flyByString] digits:',digits);
	let x,y,z;
	if (digits && digits.length == 3 && ((digits.join().length+11) > stringPos.trim().length)) {	// если чисел три, и добавление разумного количества разделителей и пробелов делает строку результата больше исходной. А иначе - это адрес с цифрами.
		digits.map(Number);	// преобразуем строки в числа (оно и так, но так модно и прилично. Так ChatGPT говорит.)
		[z,x,y] = digits;
		let maxTileNum = Math.pow(2, z) - 1;
		if (x > maxTileNum || y > maxTileNum) {
			[x,y,z] = digits;
			maxTileNum = Math.pow(2, z) - 1;
			if (x > maxTileNum || y > maxTileNum) [z,x,y] = [null,null,null];
		};
	};
	if(z && x && y){	// это похоже на номер тайла, переместимся к этому тайлу
		console.log('[flyByString] z,x,y:',z,x,y);
		map.panTo(tileNum2degree(z,x,y));
		map.setZoom(z);
	}
	else {	// это просто строка, возможно, с адресом, сделаем запрос к геосервису
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
		};
	};
};
}; // end function flyByString

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

function loggingRun(run='') {
/* запускает/останавливает запись трека по кнопке в интерфейсе 
или просто вызовом
*/
if(typeof loggingIndicator === 'undefined') return	// loggingRun может быть запущен откуда-нибудь
//console.log('[loggingRun] run=',run);
let logging = 'logging.php';
switch(run){
case 'start':
	if(!loggingIndicator.innerText || loggingIndicator.style.color != 'green'){	// запись не ведётся, или была включена, но не включилась
		logging += '?startLogging=1';
	};
	break;
case 'stop':
	if(loggingIndicator.innerText){	// запись была включена
		logging += '?stopLogging=1';
		if(currentTrackName) doNotCurrentTrackName(currentTrackName);
		console.log('[loggingRun]  Update track stopped because no logging now');
		if(currentWaitTrackUpdateProcess){
			clearInterval(currentWaitTrackUpdateProcess);	
			console.log('[loggingRun] Не должно быть currentWaitTrackUpdateProcess, но он был.');
		}
		if(currTrackSwitch.checked) startCurrentWaitTrackUpdateProcess();	// Текущий трек всегда показывается
	};
	break;
default:
	if(typeof loggingSwitch !== 'undefined'){	// для пользователя со всеми правами
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
				//console.log('[loggingRun] Не должно быть currentWaitTrackUpdateProcess, но он был. Убили, запускаем.');
			}
			if(currTrackSwitch.checked) startCurrentWaitTrackUpdateProcess();	// Текущий трек всегда показывается
		};
	};
};
//console.log('[loggingRun] logging=',logging);
loggingCheck(logging);
} // end function loggingRun

function loggingCheck(logging='logging.php') {
/* включает и выключает запись трека, а также проверяет, ведётся ли запись 
путём запроса logging.
Запрос должен вернуть JSON массив из двух значенией: ведётся ли запись bool и имя пишущегося файла
*/
//console.log('[loggingCheck] started');
let xhr = new XMLHttpRequest();
xhr.open('GET', logging, true); 	// Подготовим асинхронный запрос
xhr.send();
xhr.onreadystatechange = function() { // 
	if (this.readyState != 4) return; 	// запрос ещё не завершился
	if (this.status != 200) return; 	// что-то не то с сервером
	//console.log('[loggingCheck] this.response=',this.response);
	let status = JSON.parse(this.response);
	if(status[0]) { 	// состояние gpxlogger после выполнения logging.php, 1 или 0 - запущен успешно
		// Новый текущий трек
		const newTrackName = status[1].substring(0, status[1].lastIndexOf('.')) || status[1]; 	// имя нового текущего (пишущийся сейчас) трека -- имя файла без расширения		
		//console.log(status,'[loggingCheck] Новый текущий трек newTrackName=',newTrackName);
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
		};
		if(typeof loggingIndicator !== 'undefined'){	// если запись пути осуществляется gpxlogger'ом 
			loggingIndicator.style.color='green';
			loggingIndicator.innerText='\u2B24';
			if(typeof loggingSwitch !== 'undefined'){	// для пользователя со всеми правами
				if(!newTrackName) {	// не было возвращено имени, хотя запись трека работае. Возможно, кто-то запустил запись в какой-то не наш каталог.
					loggingSwitch.disabled = true;	// отключим переключатель, и не будем нигде включать - пусть жмут Shift-Reload
					return; 
				};
			};
		};
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
		if(typeof loggingIndicator !== 'undefined'){
			if((typeof loggingSwitch !== 'undefined') && loggingSwitch.checked){
				loggingIndicator.style.color='red';
				loggingIndicator.innerText='\u2B24';
			}
			else {
				loggingIndicator.innerText='';
			};
		};
	}
return;
}; // end xhr.onreadystatechange
}; // end function loggingCheck


async function coverage(){
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


function MOBalarm(latlng=null,MOBmarkerInfo={}) {
/* Добавляет новую точку MOB в mobMarker (который всегда есть), или обновляет имеющуюся, 
и делает mobMarker видимым на карте, если ещё не.
Точка будет в текущих координатах, или указанных.
MOBmarkerInfo - это MOB в формате gpsdPROXY

Концептуально делать это всё путем преобразования mobMarker в mobMarkerJSON, изменением json,
а потом преобразованием json обратно в mobMarker с помощью createMOBpointMarker(mobMarkerJSON).
Тогда вся сопутствующая пурга типа отметок времени и изменений интерфейса выполнится сама.
Но исторически сложилось, что здесь делается не концептуально, а естественным образом, изменением
непосредственно mobMarker.
*/
// Global: map, cursor, currentMOBmarker, centerMark
//console.log('[MOBalarm] MOBmarkerInfo:',JSON.stringify(MOBmarkerInfo));
if(!latlng){
	if(map.hasLayer(cursor)) latlng = cursor.getLatLng(); 	// координаты известны и показываются, хотя, возможно, устаревшие
	else {
		// если даже нет координат -- дадим возможность ставить маркер в центре карты
		centerMarkOn(); 	// включить крестик в середине
		latlng = centerMarkMarker.getLatLng();
		locationMOBdisplay.innerHTML = latTXT+' '+Math.round(latlng.lat*10000)/10000+'<br>'+longTXT+' '+Math.round(latlng.lng*10000)/10000;	
	};
};
const selfmmsi = vesselSelf ? vesselSelf.split(':').pop() : '';
if(!MOBmarkerInfo.mmsi) MOBmarkerInfo.mmsi = selfmmsi;
//const sart = MOBmarkerInfo.mmsi.startsWith('972') || MOBmarkerInfo.mmsi.startsWith('974');	// это точка AIS SART
const sart = MOBmarkerInfo.mmsi != selfmmsi;	// это точка MOB, поставленная не нами
let thisMarkerIs;
// для всяких SART будем обновлять точку, когда как для руками поставленных в этом экземпляре - добавлять
// Точки от AIS SART - единственные в mobMarker с данныи mmsi, когда как точек со своим mmsi
// может быть много. Также может быть много точек, полученных от netAIS MOB.
if(sart){	
	for(const layer of mobMarker.getLayers()){
		if(layer instanceof L.Marker && (MOBmarkerInfo.mmsi == layer.feature.properties.mmsi)){	// пришло обновление именно этого маркера
			thisMarkerIs = layer;
			break;
		};
	};
};
// маркер для этой точки
if(thisMarkerIs) {
	thisMarkerIs.setLatLng(latlng);
	thisMarkerIs.feature.properties.safety_related_text = MOBmarkerInfo.safety_related_text ? MOBmarkerInfo.safety_related_text : ''
	mobMarker.feature.properties.timestamp = MOBmarkerInfo.timestamp ? MOBmarkerInfo.timestamp : Math.round(Date.now()/1000);
}
else {
	thisMarkerIs = L.marker(latlng, {
		"icon": mobIcon, 
		"draggable": !sart,
	});
	thisMarkerIs.feature = {
		"type": "Feature",
		"properties": {
			"mmsi": MOBmarkerInfo.mmsi,
			"safety_related_text": MOBmarkerInfo.safety_related_text ? MOBmarkerInfo.safety_related_text : '',
		},
	};
	thisMarkerIs.on('click', mobMarkerClickFunction);
	if(sart) setMOBpopup(thisMarkerIs);
	else thisMarkerIs.on('dragend', mobMarkerDragendFunction);
	
	mobMarker.addLayer(thisMarkerIs);
	mobMarker.feature.properties.timestamp = Math.round(Date.now()/1000);
};
// Если currentMOBmarker уже есть - не следует его переназначать на маркер SART, потому что
// он может быть вручную указанным маркером, который реально идут спасать
if(!currentMOBmarker || !map.hasLayer(mobMarker) || !sart){
	makeMOBmarkerCurrent(thisMarkerIs);
};
if(!map.hasLayer(mobMarker)) mobMarker.addTo(map); 	// выставим маркер

if(!sart && (loggingIndicator !== undefined && !loggingSwitch.checked)) {	// включим запись трека, но только если это свой MOB
	loggingSwitch.checked = true;
	loggingRun(); 	// хотя в loggingSwitch стоит onChange="loggingRun();" изменение loggingSwitch.checked = true; не приводит к срабатыванию обработчика
}

sendMOBtoServer(); 	// отдадим данные MOB для передачи на сервер
return true;
} // end function MOBalarm


function MOBtabHighLight(on=false){
/* Посветка кнопки MOB */
//const highligting = '<img src="img/redbulletdot.svg" style="position:absolute;left:0;top:1em;width:100%;">';
//const img = MOBtab.querySelector('img');
//console.log('[MOBtabHighLight]',MOBtab);
//let str = MOBtab.innerHTML;
//str = str.replace(highligting,'');
//str = str.replace('</a>','');
//if(on) MOBtab.innerHTML = str+highligting+'</a>';	// Это ломает leaflet-sidebar-v2, о чём там есть комментарий.
//else MOBtab.innerHTML = str+'</a>';
if(on) MOBtab.style.backgroundColor = 'red';
else MOBtab.style.backgroundColor = 'inherit';
}; // end function MOBtabHighLight


function setMOBpopup(layer){
let dataStamp = '';
if(mobMarker.feature.properties.timestamp){
	const d = new Date(mobMarker.feature.properties.timestamp*1000);
	dataStamp = d.getHours()+':'+(d.getMinutes()<10?'0'+d.getMinutes():d.getMinutes());
	//dataStamp = d.getHours()+':'+d.getMinutes();
}
let PopupContent = `
<div>
	<div style='width:100%;'>
		${layer.feature.properties.mmsi||''} 
		<img  width="24px" style="margin:0.1rem;vertical-align:middle;" src="${mob_markerImg}">
	</div>
	<div style='width:100%;background-color:lavender;'>
		<span style='font-size:110%;'>${layer.feature.properties.safety_related_text||''}</span><br>
	</div>
	<span>${dataStamp}</span>
</div>
`;
layer.bindPopup(PopupContent,{});	// таким образом, Popup лепится только к маркерам MOB, пришедшим извне.
}; // end function setMOBpopup

function createMOBpointMarker(mobMarkerJSON){
/*
Создадим mobMarker - мультислой маркеров из переданного GeoJSON,
а потом каждому маркеру в мультислое присвоим иконку, которая в GeoJSON не сохраняется.
Возможно, нарисуем линию от текущего маркера к месторасположению, но саму линию в мультислой добавим обязательно.
Покажем мультислой на карте.

Global: mobMarker, он создаётся заново.
*/
mobMarker = L.geoJSON(mobMarkerJSON);
if(!mobMarker.feature){
	mobMarker.feature = {
		properties: {}
	};
};
// Почему-то в mobMarker timestamp - это mobMarker.feature.properties.timestamp,
// а в mobMarkerJSON = mobMarkerJSON.properties.timestamp
// Вроде бы, это от GeoJSON
if(mobMarkerJSON.properties && mobMarkerJSON.properties.timestamp){	// штатно не, но могут быть куки от предыдущих версий
	mobMarker.feature.properties.timestamp = mobMarkerJSON.properties.timestamp;
};
const selfmmsi = vesselSelf ? vesselSelf.split(':').pop() : '';
let layerID;
mobMarker.eachLayer(function (layer) {
	if(layer instanceof L.Marker)	{
		//const sart = layer.feature.properties.mmsi && (layer.feature.properties.mmsi.startsWith('972') || layer.feature.properties.mmsi.startsWith('974'));	// это точка AIS SART
		const sart = layer.feature.properties.mmsi && layer.feature.properties.mmsi != selfmmsi;	// это точка MOB, поставленная не нами
		layerID = mobMarker.getLayerId(layer);
		layer.setIcon(mobIcon);
		if(!layer.feature.properties) layer.feature.properties = {};
		layer.on('click', mobMarkerClickFunction); 	// текущим будет маркер, по которому кликнули
		if(!layer.getLatLng() || (layer.getLatLng().lat == undefined) || (layer.getLatLng().lng == undefined)){	// У этой точки нет координат. Например, это AIS MOB.
			if(map.hasLayer(cursor)) layer.setLatLng(cursor.getLatLng()); 	// координаты известны и показываются, хотя, возможно, устаревшие - назначим точке свои координаты
			else layer.setLatLng(map.getCenter());
		};
		//console.log('Маркеры в полученной информации MOB ',layer);
		// если вообще не был назначен currentMOBmarker, или мультислой mobMarker не показывается
		//if(!currentMOBmarker || !map.hasLayer(mobMarker)){	// Это вообще не надо, потому что мы создаём весь мультислой заново, и никакого текущего маркера вне создаваемых здесь быть не может.
			if(layer.feature.properties.current) { 	// текущим станет указанный в переданных данных
				//console.log('[createMOBpointMarker] Делаем текущим маркер с координатами:',layer.getLatLng());
				makeMOBmarkerCurrent(layer);
			}
		//};
		if(sart) setMOBpopup(layer);
	}
	// А назачем удалять линию? Затем, что она уже неактуальна. Если надо - тут же будет нарисована новая.
	// Ещё потому, что иначе она будет просто линия, а не toMOBline, и с ней никто ничего не сможет сделать.
	else mobMarker.removeLayer(layer); 	// Считаем, что это toMOBline, и там больше ничего такого нет
});
toMOBline.setLatLngs([]); 	// очистим линию к текущему маркеру MOB
mobMarker.addLayer(toMOBline);	// добавим в мультислой линию, её там нет.
// Возможно, не нужно принудительно устанавливать текущий маркер?
// Последний маркер станет текущим, если текущего вообще не назначали.
// При таком условии если есть MOB SART, то, когда этот маркер снова будет показан после прекращения
// режима MOB, линия к нему не будет проведена. Фича? Ага, но только в том случае, если 
// в MOBclose не currentMOBmarker = null;
if(layerID && !currentMOBmarker){
	//console.log('[createMOBpointMarker] Назначаем текущим последний маркер с координатами:',mobMarker.getLayer(layerID).getLatLng());
	makeMOBmarkerCurrent(mobMarker.getLayer(layerID));	// назначим текущим последний маркер
};
//
//console.log('[createMOBpointMarker] mobMarker:',mobMarker);
/*/ Перерисуем линию, если есть текущий маркер. А надо?
if(currentMOBmarker){
	let latlng1 = cursor.getLatLng();	// cursor-то есть всегда, но какие у него координаты, когда его нет?
	let latlng2 = currentMOBmarker.getLatLng();
	toMOBline.setLatLngs([latlng1,latlng2]); 	// обновим линию к текущему маркеру MOB
};
/*/
mobMarker.addTo(map); 	// покажем мультислой с маркерами MOB

mobMarker.eachLayer(function (layer) { 	// сделаем каждый маркер draggable, кроме чужих маркеров
	if(layer instanceof L.Marker && is_currentMOBmarkerSelf(layer)){	
		layer.dragging.enable(); 	// переключение возможно, только если маркер на карте
		layer.on('dragend', mobMarkerDragendFunction); 	// отправим на сервер новые сведения, когда перемещение маркера закончилось. Если просто указать функцию -- в sendMOBtoServer передаётся event. Если в одну строку -- всё равно передаётся event. Что за???
	}
});
}; // end function createMOBpointMarker


function MOBclose() {
mobMarker.remove(); 	// убрать мультислой-маркер с карты
//currentMOBmarker = null;
mobMarker.clearLayers(); 	// очистить мультислой от маркеров
toMOBline.setLatLngs([]);	// сделаем линию никакой
mobMarker.addLayer(toMOBline); 	// вернём туда линию
//console.log("[MOBclose] mobMarker:",mobMarker);
storageHandler.del('mobMarker');
azimuthMOBdisplay.innerHTML = '&nbsp;';
distanceMOBdisplay.innerHTML = '&nbsp;';
directionMOBdisplay.innerHTML = '&nbsp;';
locationMOBdisplay.innerHTML = '&nbsp;';
delMOBmarkerButton.disabled = true;
//centerMarkOff(); 	// выключить крестик в середине -- не надо, ибо при закрытии панели оно уже вызывается
MOBtabHighLight(false);	// убрать подсветку кнопки
sidebar.close();	// закрыть панель
} // end function MOBclose

function realMOBclose(){
mobMarker.feature.properties.timestamp = Math.round(Date.now()/1000);
sendMOBtoServer(false); 	// передадим на сервер, что режим MOB прекращён
MOBclose();
}; // end function realMOBclose


function delMOBmarker(){
/* Удаляет текущий маркер MOB
mobMarker это LayerGroup 
Вызывается юзером
*/
//console.log('[delMOBmarker] currentMOBmarker before del ',currentMOBmarker);
if(!is_currentMOBmarkerSelf() || !checkSelfMOBmarkerScount()) return;	// нельзя убрать чужой или последний свой маркер
mobMarker.removeLayer(currentMOBmarker);
currentMOBmarker = null;
// Сделаем текущим первый попавшийся свой маркер, или никакого?
let layerID;
for(const layer of mobMarker.getLayers()){
	if(!(layer instanceof L.Marker)) continue;
	if(is_currentMOBmarkerSelf(layer)) {
		makeMOBmarkerCurrent(layer);
		break;
	}
	layerID = mobMarker.getLayerId(layer);
};
//if(!currentMOBmarker) makeMOBmarkerCurrent(mobMarker.getLayer(layerID));	// сделаем текущим последний маркер
sendMOBtoServer(); 	// отдадим новые данные MOB для передачи на сервер
} // end function delMOBmarker


function makeMOBmarkerCurrent(LMarker){
/* Global currentMOBmarker */
if(!(LMarker instanceof L.Marker)) return;
// Забавно, что в javascript нижеследующие операторы могут быть выполнены в любой последовательности
// с одинаковым результатом. Но всё же расположим их в разумной.
currentMOBmarker = LMarker;
clearCurrentStatus(); 	// удалим признак current у всех маркеров
currentMOBmarker.feature.properties.current = true;
if(is_currentMOBmarkerSelf() && checkSelfMOBmarkerScount()) {
	//console.log('Это наш маркер');
	delMOBmarkerButton.disabled = false; // включим/выключим кнопку удаления маркера MOB
}
else {
	//console.log('Это чужой или единственный маркер',checkSelfMOBmarkerScount());
	delMOBmarkerButton.disabled = true;	// выключим кнопку удаления маркера
};

if(!mobMarker.hasLayer(currentMOBmarker)) mobMarker.addLayer(currentMOBmarker);	// можно добавить сколько угодно одних и тех же слоёв
mobMarker.feature.properties.timestamp = Math.round(Date.now()/1000);
}; // end function makeMOBmarkerCurrent

function clearCurrentStatus() {
/* удаляет признак "текущий маркер" у всех маркеров мультислоя mobMarker */
mobMarker.eachLayer(function (layer) { 	// удалим признак current у какого-то маркера
	if((layer instanceof L.Marker) && (layer.feature.properties.current == true))	{
		layer.feature.properties.current = false;
	}
});
} // end function clearCurrentStatus


function is_currentMOBmarkerSelf(marker=null){
/* Возвращает true, если текущая или указанная точка MOB поставлена нашим судном. С любого клиента. 
В случае GaladrielMap SignalK ed. есть что-то типа своего mmsi, и идентифицируется по нему.
В случае просто GaladrielMap mmsi есть только у gpsdPROXY, поэтому считаем, что если mmsi нет - то это мы.
*/
if(!marker) marker = currentMOBmarker;
let ret;
const selfmmsi = vesselSelf ? vesselSelf.split(':').pop() : '';
//console.log('[is_currentMOBmarkerSelf] selfmmsi=',selfmmsi,'marker:',marker);
if(marker.feature){	// L.Marker
	if(marker.feature.properties.mmsi && (marker.feature.properties.mmsi !== selfmmsi)){
		ret = false;
	}
	else ret = true;
}
else if(marker.properties){	// mobMarkerJSON
	if(marker.properties.mmsi && (marker.properties.mmsi !== selfmmsi)){
		ret = false;
	}
	else ret = true;
};
//console.log('[is_currentMOBmarkerSelf]  selfmmsi=',selfmmsi,'return=',ret);
return ret;
}; // end function is_currentMOBmarkerSelf


function checkSelfMOBmarkerScount(){
/* Считает, имеется ли больше одного своего маркеров MOB */
let n=0;
for(const layer of mobMarker.getLayers()){
	if(!(layer instanceof L.Marker)) continue;
	if(is_currentMOBmarkerSelf(layer)) n++;
	if(n > 1) return true;
};
return false;
}; // end function checkSelfMOBmarkerScount


function mobMarkerDragendFunction(event){
//console.log("MOB dragged end, send to server new coordinates",mobMarker);
mobMarker.feature.properties.timestamp = Math.round(Date.now()/1000);
if(event.target.feature.properties.current == true){
	let latlng1 = cursor.getLatLng();	// cursor-то есть всегда, но какие у него координаты, когда его нет?
	let latlng2 = event.target.getLatLng();
	toMOBline.setLatLngs([latlng1,latlng2]); 	// обновим линию к текущему маркеру MOB
};
sendMOBtoServer(); 
}; // end function mobMarkerDragendFunction


function mobMarkerClickFunction(event){
//console.log("MOB click",event.target);
if(event.target.feature.properties.current == true) return;
makeMOBmarkerCurrent(event.target)
//console.log("MOB click, send to server new coordinates",mobMarker);
sendMOBtoServer(); 
}; // end function mobMarkerClickFunction


function sendMOBtoServer(status=true){
/* Кладёт данные MOB в массив, который передаётся на сервер 
mobMarker -- это Leaflet LayerGroup, т.е. там исчерпывающая информация
*/
//console.log("sendMOBtoServer status=",status,mobMarker);
let mobMarkerJSON = null;
mobMarkerJSON = mobMarker.toGeoJSON(); 	//
if(!mobMarkerJSON.properties){	// вообще-то, toGeoJSON должна сохранять левые поля, но она делает это как-то иногда...
	mobMarkerJSON.properties = {"timestamp": Math.round(new Date().getTime()/1000)};
};
if(mobMarker.feature.properties.timestamp) mobMarkerJSON.properties.timestamp = mobMarker.feature.properties.timestamp;
if(status){
	//console.log('[sendMOBtoServer] Посадим куку MOB');
	storageHandler.save('mobMarker',mobMarkerJSON);
}
else {
	storageHandler.del('mobMarker');
};
//console.log('Sending to server mobMarkerJSON',JSON.stringify(mobMarkerJSON));
upData.MOB = GeoJSONtoMOB(mobMarkerJSON,status);	// приведение к формату gpsdPROXY
//console.log('[sendMOBtoServer] Sending to server upData.MOB:',upData.MOB);
//console.log('[sendMOBtoServer] upData=',JSON.stringify(upData.MOB));
//console.log('[sendMOBtoServer] spatialWebSocket.readyState:',spatialWebSocket.readyState);

// отдадим данные MOB для передачи на сервер через глобальный сокет для передачи координат.
// Он есть, иначе -- нет координат и нет проблем.
// Его может не быть, а MOB можно поставить и без координат.
if(spatialWebSocket && spatialWebSocket.readyState == 1) {	// при этом в index.php в spatialWebSocket.onopen эта функция вызывается сразу по открытию соединения с gpsdPROXY, так что если есть gpsdPROXY - данные станут общими.
	spatialWebSocket.send('?UPDATE={"updates":['+JSON.stringify(upData.MOB)+']};'); 	
};
}; // end function sendMOBtoServer

function MOBtoGeoJSON(MOBdata){
/* Переделывает объект MOB из формата gpsdPROXY в mobMarkerJSON: Leaflet GeoJSON для GaladrielMap */
//console.log('[MOBtoGeoJSON] MOBdata:',MOBdata);
let mobMarkerJSON = {
	"type":"FeatureCollection",
	"features":[],
	"properties": {
		"timestamp": MOBdata.timestamp
	}
};
for(const point of MOBdata.points){
	if(point.coordinates.length === 0 || point.coordinates[0]==null || point.coordinates[1]==null) continue;	// Точки без координат просто не показываем. А что делать?
	const feature = {	
		"type":"Feature",
		"properties":{
			"current": Boolean(point.current),
			"mmsi": String(point.mmsi),
			"safety_related_text": String(point.safety_related_text)
		},
		"geometry":{
			"type":"Point",
			"coordinates": point.coordinates
		}
	};
	mobMarkerJSON.features.push(feature);
};
//console.log('[MOBtoGeoJSON] mobMarkerJSON:',mobMarkerJSON);
return mobMarkerJSON;
}; // end function MOBtoGeoJSON

function GeoJSONtoMOB(mobMarkerJSON,status){
/* Переделывает Leaflet GeoJSON мультислоя mobMarker в объект MOB формата gpsdPROXY */
//console.log('[GeoJSONtoMOB] mobMarkerJSON:',mobMarkerJSON);
let MOB={
	"class": 'MOB',
	"status": status,
	"points": [],
	"timestamp": status ? mobMarkerJSON.properties.timestamp : Math.round(new Date().getTime()/1000),
	"source": '972'+vesselSelf.substring(3)
};
for(let feature of mobMarkerJSON.features){
	switch(feature.geometry.type){
	case "Point":
		MOB.points.push({
			'coordinates':feature.geometry.coordinates,
			'current':feature.properties.current,
			'mmsi':feature.properties.mmsi,
			'safety_related_text':feature.properties.safety_related_text
		});
		break;
	case "LineString":
		break;
	};
};
return MOB;
}; // end function GeoJSONtoMOB


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
	storageHandler.save('distCirclesSwitch',true);
}
else {
	distCircles.forEach(circle => circle.removeFrom(positionCursor));
	storageHandler.save('distCirclesSwitch',false);
};
}; // end function distCirclesToggler


function windSwitchToggler() {
/* включает/выключает показ символа ветра по переключателю в интерфейсе */
if(windSwitch.checked) {
	windSymbolMarker.setLatLng(cursor.getLatLng());	// хотя его может и не быть. Но если не установить координаты, может происходить ошибка в leaflet, если символ показывается до очередного обновления координат, когда они устанавливаюся для всех слоёв, включая символ ветра.
	if(!positionCursor.hasLayer(windSymbolMarker)) windSymbolMarker.addTo(positionCursor);
	storageHandler.save('WindSwitch',true);
}
else {
	windSymbolMarker.removeFrom(positionCursor);
	storageHandler.save('WindSwitch',false);
};
}; // end function windSwitchToggler

function windSymbolUpdate(data){
/* Ветер определяется естественно - относительно носа судна 
Global: track,heading
*/
//console.log('[windSymbolUpdate] useTrueWind=',useTrueWind);
if(useTrueWind){	// options.js указано использовать истинный ветер
	//console.log('[windSymbolUpdate] wspeedt=',data.wspeedt,'wanglet=',data.wanglet,'track=',track);
	if(data.wspeedt != null && data.wanglet != null && track != null){
		let dir = data.wanglet + track - 90;	// картинка-то у нас горизонтальна
		if(dir >= 360) dir -= 360;
		realWindSymbolUpdate(dir,data.wspeedt);
	}
	else realWindSymbolUpdate();
}
else {	// указано использовать вымпельный ветер
	//console.log('[windSymbolUpdate] heading=',data.heading,'wind dir=',data.wangler+data.heading,'wspeedr=',data.wspeedr);
	/*/ Это работает только в новых браузерах, но думаю, что слишком новых
	if(data.wspeedr && data.wangler && (heading ?? track)){	//
		let dir = data.wangler + (heading ?? track) - 90;	// картинка-то у нас горизонтальна
		if(dir >= 360) dir -= 360;
		realWindSymbolUpdate(dir,data.wspeedr);
	}
	else realWindSymbolUpdate();
	/*/
	// А это - работает во всех.
	let hdng = heading || track || mheading;
	if(data.wspeedr != null && data.wangler != null && hdng != null){	//
		let dir = data.wangler + hdng - 90;	// картинка-то у нас горизонтальна
		if(dir >= 360) dir -= 360;
		realWindSymbolUpdate(dir,data.wspeedr);
	}
	else realWindSymbolUpdate();
};
}; // end function windSymbolUpdate

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

function restoreDisplayedRoutes(){
// Восстановим показываемые маршруты и заодно согласуем списки routeList и routeDisplayed
if(SelectedRoutesSwitch.checked) {
	let showRoutes = storageHandler.restore('showRoutes'); 	// storageHandler from galadrielmap.js
	if(showRoutes) {
		showRoutes.forEach(
			function(layerName){ 	// 
				// однако же, возможно повторение id, раз он имя файла? Получение всех li, содержащих строку.
				// [... выражение] просто делает из результатов выражения массив
				// но результат querySelectorAll и так массив (в отличии от)? Авотхрен, оно тоже NodeList, хоть и статический, и методы массива там ёк
				//const routeListLi = [... document.querySelectorAll('#routeList > li')].filter(el => el.textContent.includes(layerName));
				//const routeListLi = [... routeList.querySelectorAll('li')].filter(el => el.textContent.includes(layerName));
				const routeListLi = [... routeList.querySelectorAll('li')].filter(el => el.textContent == layerName);
				if(routeListLi.length) { 	// объект с этим именем есть в списке routeList
					const routeDisplayedLi = [... routeDisplayed.querySelectorAll('li')].filter(el => el.textContent.includes(layerName));
					if(routeDisplayedLi.length) { 	// этот объект уже есть в списке routeDisplayed, т.е., маршрут показывается
						routeListLi[0].remove();	// удалим его из routeList
						routeListLi[0] = null;
					}
					else {	// иначе выберем объект, т.е., покажем маршрут
						selectTrack(routeListLi[0],routeList,routeDisplayed,displayRoute)
					};
				};	// 	если нет -- и ладно
			}	// end function(layerName)
		);
	};
};
}; // end function restoreDisplayedRoutes


function hideControlsControl(hideControlPosition){
/* Создаёт невидимый псевдо-control, по тапу по которому все указанные в controlsList control делаются невидимыми на экране
На самом деле, тут просто style.display = 'none', потому что штатный control совсем неудобен
для этой цели, и штатные функции leaflet неудобны в силу общего кретинизма javascript, поэтому всё примитивно.
*/
let doit=true;
switch(hideControlPosition){
case 'topleft':
	hideControl.style.top = '0';
	hideControl.style.bottom = null;
	hideControl.style.right = null;
	hideControl.style.left = '0';
	break;
case 'topmiddle':
	hideControl.style.top = '0';
	hideControl.style.bottom = null;
	hideControl.style.right = null;
	hideControl.style.left = 'calc(50vw - var(--control-size)/2)';
	break;
case 'topright':
	hideControl.style.top = '0';
	hideControl.style.bottom = null;
	hideControl.style.right = '0';
	hideControl.style.left = null;
	break;
case 'rightmiddle':
	hideControl.style.top = 'calc(50vh - var(--control-size)/2)';
	hideControl.style.bottom = null;
	hideControl.style.right = '0';
	hideControl.style.left = null;
	break;
case 'bottomright':
	hideControl.style.top = null;
	hideControl.style.bottom = '0';
	hideControl.style.right = '0';
	hideControl.style.left = null;
	break;
case 'bottommiddle':
	hideControl.style.top = null;
	hideControl.style.bottom = '0';
	hideControl.style.right = null;
	hideControl.style.left = 'calc(50vw - var(--control-size)/2)';
	break;
case 'bottomleft':
	hideControl.style.top = null;
	hideControl.style.bottom = '0';
	hideControl.style.right = null;
	hideControl.style.left = '0';
	break;
case 'leftmiddle':
	hideControl.style.top = null;
	hideControl.style.top = 'calc(50vh - var(--control-size)/2)';
	hideControl.style.right = null;
	hideControl.style.left = '0';
	break;
default:
	doit=false;
};
//console.log('[hideControlsControl] hideControlPosition=',hideControlPosition,'doit=',doit);
if(doit){
	hideControl.style.display = 'unset';
	// Тут нужна именованная функция, тогда не будет повторной её установки в качестве обработчика.
	// А анонимная функция установится повторно, ибо все анонимные функции разные.
	// Авотхрен! И именованная функция тоже устанавливается многократно, несмотря на https://developer.mozilla.org/ru/docs/Web/API/EventTarget/addEventListener
	// Опять авотхрен! Именованная функция, определённая сдесь же - да, устанавливается многократно,
	// ибо да - она определяется снова, и тогда - другая. Т.е., должна быть именованная функция из
	// внешней области видимости. В данном случае - блин, глобальная.
	//hideControl.removeEventListener('click', hideControlEventListener);
	hideControl.addEventListener('click', hideControlEventListener);	
};
}; // end function hideControlsControl

function hideControlEventListener(event){
	//console.log('hideControl click:',event);
	for(let control of controlsList){
		//console.log('hideControl click control:',control.getContainer().style.display);
		//if(control._map) control.remove();	// так оно удаляется из DOM, а у нас везде используются значения полей и переключателей.
		//else control.addTo(map);
		if(control.getContainer().style.display == 'none') control.getContainer().style.display = 'unset';
		else control.getContainer().style.display = 'none';
	};
}; // end function hideControlEventListener

function hideControlsToggler(target){
/*  */
//console.log('[hideControlsToggler] target:',target);
if(target.value == 'onoffswitch') {
	if(target.checked){	// возможность сокрытия включили
		for(let radio of settings.querySelectorAll('input[type="radio"][name="hideControlPosition"]')){
			//console.log('[hideControlsToggler] radio:',radio);
			radio.disabled = false;
			if(radio.checked) hideControlsControl(radio.value);
		};
	}
	else{	// возможность сокрытия выключили
		for(let radio of settings.querySelectorAll('input[type="radio"][name="hideControlPosition"]')){
			//console.log('[hideControlsToggler] radio:',radio);
			radio.disabled = true;
		};
		hideControl.removeEventListener('click', hideControlEventListener);
		hideControl.style.display = 'none';
	};
	storageHandler.save('hideControlsSwitch',hideControlsSwitch.checked);
}
else {	// изменили расположения переключателя сокрытия
	hideControlsControl(target.value);	// если возможность сокрытия выключена - то и сменить ничего нельзя.
	storageHandler.save('hideControlPosition',target.value);
};
}; // end function hideControlsToggler


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

function tileNum2degree(zoom,xtile,ytile) {
/* Tile numbers to lon./lat. left top corner
// http://wiki.openstreetmap.org/wiki/Slippy_map_tilenames
*/
const n = Math.pow(2, zoom)
const lon_deg = xtile / n * 360.0 - 180.0;
const nn = Math.PI - (2 * Math.PI * ytile) / Math.pow(2, zoom);
const lat_deg = (180 / Math.PI) * Math.atan(0.5 * (Math.exp(nn) - Math.exp(-nn)));
return {'lat': lat_deg,'lng': lon_deg};
}

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
//console.log('[realtime] dataUrl=',dataUrl);
//console.log('[realtime] upData:',upData);
if(upData) {
	if(dataUrl.includes('?')) dataUrl += '&upData=';
	else dataUrl += '?upData=';
	dataUrl += encodeURIComponent(JSON.stringify(upData));
}
fetch(dataUrl)
.then((response) => {
    return response.text();
})
.then(data => { 		// The Body mixin of the Fetch API represents the body of the response/request, allowing you to declare what its content type is and how it should be handled.
	try {
		//console.log('[realtime] data=', data);
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
});

}; 	// end function realtime



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

const storageHandler = {
	_storageName : 'GaladrielMapOptions',
	_store: {'empty':true},	// типа, флаг, что ещё не считывали из хранилища. Так проще и быстрей в этом кривом языке.
	storage: false,	// теоретически, можно указать, куда именно записывать? Но только мимо проверки доступности.
	//storage: 'cookie',
	//storage: 'storage',
	save: function(key,value=null){
		/* сохраняет key->value, но можно передать список пар одним параметром 
		или просто строку с именем переменной */
		let values = {};
		if(arguments.length == 2){	// два аргумента - это key->value
			values[key] = value;
		}
		else if(typeof key == 'object') {	// один, но список key->value
			values = key;
		}
		else {	// один, тогда это строка - имя переменной
			//values[key] = window[key];	// это обломается, если key - не глобальная переменная, объявленная через var
			// поэтому нижесказанное - единственный способ получить значение объекта по его имени.
			// Он сработает и с локальным объектом, и с объектами, объявленными через let и const
			values[key] = eval(key);
			//console.log('[storageHandler] save key=',key,window[key]);
		};
		//console.log('[storageHandler] save',values,'to storage:',this.storage,'store:',this._store);
		for(let key in values){
			this._store[key] = values[key];
		};
		this._store.empty = false;
		this._saveStore();
	},
	restore: function(key){
		//alert('[storageHandler] restore '+key);
		if(this._store.empty){
			this._restoreStore();
			this._store.empty = false;
		};
		return this._store[key.trim()];
	},
	restoreAll: function(){
		if(this._store.empty){
			this._restoreStore();
			this._store.empty = false;
		};
		delete this._store.empty;
		for(let varName in this._store){
			window[varName] = this._store[varName];	// window[varName] - создаётся глобальная переменная с именем, являющимся значением varName
		};
		this._store.empty = false;
	},
	del: function(key){
		if(this._store.empty){
			this._restoreStore();
			this._store.empty = false;
		};
		delete this._store[key.trim()];
		this._saveStore();
	},
	_findStorage: function(){
		try {
			window.localStorage.setItem("__storage_test__", "__storage_test__");
			window.localStorage.removeItem("__storage_test__");
			this.storage='storage';
		}
		catch (err) {
			this.storage='cookie';	// куки-то всегда можно, да?
		};
	},
	_saveStore: function(){
		if(!this.storage) this._findStorage();
		switch(this.storage){
		case 'storage':
			//console.log('_saveStore:',JSON.stringify(this._store));
			window.localStorage.setItem(this._storageName, JSON.stringify(this._store));
			break;
		case 'cookie':
			let expires = new Date(Date.now() + (60*24*60*60*1000));	// протухнет через два месяца
			expires = expires.toUTCString();
			document.cookie = this._storageName+"="+JSON.stringify(this._store)+"; expires="+expires+"; path=/; SameSite=Lax;";
			break;
		default:
			console.log('storageHandler: the parameters are not saved, there is nowhere');
		};
	},
	_restoreStore: function(){
		if(!this.storage) this._findStorage();
		switch(this.storage){
		case 'storage':
			this._store = JSON.parse(window.localStorage.getItem(this._storageName));
			//console.log('_restoreStore:',JSON.stringify(this._store));
			if(!this._store) this._store = {'empty':true};
			break;
		case 'cookie':
			this._store = JSON.parse(document.cookie.match(new RegExp(
				"(?:^|; )" + this._storageName.replace(/([\.$?*|{}\(\)\[\]\\\/\+^])/g, '\\$1') + "=([^;]*)"
			))[1]);
			if(!this._store) this._store = {'empty':true};
			break;
		default:
			console.log('storageHandler: no saved parameters, there is nowhere');
		};
	}
}; // end storageHandler

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

