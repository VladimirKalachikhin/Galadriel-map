(function(f){if(typeof exports==="object"&&typeof module!=="undefined"){module.exports=f()}else if(typeof define==="function"&&define.amd){define([],f)}else{var g;if(typeof window!=="undefined"){g=window}else if(typeof global!=="undefined"){g=global}else if(typeof self!=="undefined"){g=self}else{g=this}g.omnivore = f()}})(function(){var define,module,exports;return (function(){function r(e,n,t){function o(i,f){if(!n[i]){if(!e[i]){var c="function"==typeof require&&require;if(!f&&c)return c(i,!0);if(u)return u(i,!0);var a=new Error("Cannot find module '"+i+"'");throw a.code="MODULE_NOT_FOUND",a}var p=n[i]={exports:{}};e[i][0].call(p.exports,function(r){var n=e[i][1][r];return o(n||r)},p,p.exports,r,e,n,t)}return n[i].exports}for(var u="function"==typeof require&&require,i=0;i<t.length;i++)o(t[i]);return o}return r})()({1:[function(require,module,exports){
"use strict"

var xhr = require('corslite'),
    csv2geojson = require('csv2geojson'),
    wellknown = require('wellknown'),
    polyline = require('polyline'),
    topojson = require('topojson'),
    toGeoJSON = require('togeojson');

module.exports.polyline = polylineLoad;
module.exports.polyline.parse = polylineParse;

module.exports.geojson = geojsonLoad;

module.exports.topojson = topojsonLoad;
module.exports.topojson.parse = topojsonParse;

module.exports.csv = csvLoad;
module.exports.csv.parse = csvParse;

module.exports.gpx = gpxLoad;
module.exports.gpx.parse = gpxParse;

module.exports.kml = kmlLoad;
module.exports.kml.parse = kmlParse;

module.exports.wkt = wktLoad;
module.exports.wkt.parse = wktParse;

//module.exports.toGeoJSON = toGeoJSON;

function addData(l, d) { 	// layer geojson
/* Загружает geojson в layer 
*/
if ('setGeoJSON' in l) {
	l.setGeoJSON(d);
} else if ('addData' in l) {
	l.addData(d);
}
} // end function addData

/**
 * Load a [GeoJSON](http://geojson.org/) document into a layer and return the layer.
 *
 * @param {string} url
 * @param {object} options
 * @param {object} customLayer
 * @returns {object}
 */
function geojsonLoad(url, options, customLayer) {
    var layer = customLayer || L.geoJson();
    xhr(url, function(err, response) {
        if (err) return layer.fire('error', { error: err });
        addData(layer, JSON.parse(response.responseText));
        layer.fire('ready');
    });
    return layer;
}

/**
 * Load a [TopoJSON](https://github.com/mbostock/topojson) document into a layer and return the layer.
 *
 * @param {string} url
 * @param {object} options
 * @param {object} customLayer
 * @returns {object}
 */
function topojsonLoad(url, options, customLayer) {
    var layer = customLayer || L.geoJson();
    xhr(url, onload);
    function onload(err, response) {
        if (err) return layer.fire('error', { error: err });
        topojsonParse(response.responseText, options, layer);
        layer.fire('ready');
    }
    return layer;
}

/**
 * Load a CSV document into a layer and return the layer.
 *
 * @param {string} url
 * @param {object} options
 * @param {object} customLayer
 * @returns {object}
 */
function csvLoad(url, options, customLayer) {
if(customLayer) var layer = L.layerGroup([customLayer]);
else var layer = L.layerGroup();
xhr(url, onload);
function onload(err, response) {
    var error;
    if (err) return layer.fire('error', { error: err });
    function avoidReady() {
        error = true;
    }
    layer.on('error', avoidReady);
    csvParse(response.responseText, options, layer);
    layer.off('error', avoidReady);
    if (!error) layer.fire('ready');
}
return layer;
}

/**
 * Load a GPX document into a layer and return the layer.
 *
 * @param {string} url
 * @param {object} options
 * @param {object} customLayer
 * @returns {object}
 */
function gpxLoad(url, options, customLayer) {
if(customLayer) var layer = L.layerGroup([customLayer]);
else var layer = L.layerGroup();
//console.log(url);
xhr(url, onload);
function onload(err, response) {
    var error;
    if (err) return layer.fire('error', { error: err });
    function avoidReady() {
        error = true;
    }
    layer.on('error', avoidReady);
    //console.log('leaflet-omnivore [gpxLoad] onload response:',response.responseText);
    gpxParse(response.responseXML || response.responseText, options, layer);
//    gpxParse(response.responseText, options, layer);
    layer.off('error', avoidReady);
    if (!error) layer.fire('ready');
}
return layer;
}

/**
 * Load a [KML](https://developers.google.com/kml/documentation/) document into a layer and return the layer.
 *
 * @param {string} url
 * @param {object} options
 * @param {object} customLayer
 * @returns {object}
 */
function kmlLoad(url, options, customLayer) {
if(customLayer) var layer = L.layerGroup([customLayer]);
else var layer = L.layerGroup();
xhr(url, onload);
function onload(err, response) {
    var error;
    if (err) return layer.fire('error', { error: err });
    function avoidReady() {
        error = true;
    }
    layer.on('error', avoidReady);
    kmlParse(response.responseXML || response.responseText, options, layer);
    layer.off('error', avoidReady);
    if (!error) layer.fire('ready');
}
return layer;
}

/**
 * Load a WKT (Well Known Text) string into a layer and return the layer
 *
 * @param {string} url
 * @param {object} options
 * @param {object} customLayer
 * @returns {object}
 */
function wktLoad(url, options, customLayer) {
    var layer = customLayer || L.geoJson();
    xhr(url, onload);
    function onload(err, response) {
        if (err) return layer.fire('error', { error: err });
        wktParse(response.responseText, options, layer);
        layer.fire('ready');
    }
    return layer;
}

/**
 * Load a polyline string into a layer and return the layer
 *
 * @param {string} url
 * @param {object} options
 * @param {object} customLayer
 * @returns {object}
 */
function polylineLoad(url, options, customLayer) {
    var layer = customLayer || L.geoJson();
    xhr(url, onload);
    function onload(err, response) {
        if (err) return layer.fire('error', { error: err });
        polylineParse(response.responseText, options, layer);
        layer.fire('ready');
    }
    return layer;
}

function topojsonParse(data, options, layer) {
    var o = typeof data === 'string' ?
        JSON.parse(data) : data;
    layer = layer || L.geoJson();
    for (var i in o.objects) {
        var ft = topojson.feature(o, o.objects[i]);
        if (ft.features) addData(layer, ft.features);
        else addData(layer, ft);
    }
    return layer;
}

function csvParse(csv, options, layer) {
/**/
if(layer) {
	if("getLayers" in layer) { 	// это layerGroup
		var featuresLayer = layer.getLayers()[0] || L.geoJson();
	}
	else {	// это одиночный Layer
		var featuresLayer = layer;
		layer = new L.layerGroup([featuresLayer]); 	// попробуем сменть тип на layerGroup, но это обычно боком выходит
	}
}
else {
	var featuresLayer = L.geoJson();
	var layer = new L.layerGroup([featuresLayer]);
}
var color = globalCurrentColor;
globalCurrentColor = nextColor(globalCurrentColor); 	// сменим текущий цвет, from galadrielmap.js
if(color == 0xFFFFFF) featuresLayer.options.color = 0x3388FF; 	//  умолчальный цвет линий
else featuresLayer.options.color = color; 	//  цвет линий
if(options.featureNameNode) { 	// li с именем файла, из которого делаем layer
	options.featureNameNode.style.backgroundColor = '#'+('000000' + color.toString(16)).slice(-6);
}
featuresLayer.options.onEachFeature = getPopUpToLine; 	// функция, вызываемая для каждой feature при её создании
featuresLayer.options.style = function(geoJsonFeature){return{color: '#'+('000000' + featuresLayer.options.color.toString(16)).slice(-6)};}; 	// A Function defining the Path options for styling GeoJSON lines and polygons, called internally when data is added. 
if(! layer.hasLayer(featuresLayer)) layer.addLayer(featuresLayer);

var pointsLayer = L.geoJson();
pointsLayer.options.color = color; 	//  цвет значков
pointsLayer.options.pointToLayer = function (geoJsonPoint, latlng) { 	// функция, вызываемая для каждой точки при её создании
	var parameters = {color: pointsLayer.options.color}; 	// таким образом мы забросим цвет в создание маркера
	var marker = getMarkerToPoint(geoJsonPoint, latlng, parameters);
	return marker;
};
layer.addLayer(pointsLayer);

options = options || {};
csv2geojson.csv2geojson(csv, options, onparse);

function onparse(err, geojson) {
    if (err) return layer.fire('error', { error: err });
	var Points=[];
	var Features=[];
	//console.log(layer.options.markerColor);
	for(var i=0; i<geojson.features.length;i++) {
		if(geojson.features[i].geometry.type=='Point') {
			geojson.features[i].properties.color = layer.options.markerColor;
			Points.push(geojson.features[i]);
		}
		else Features.push(geojson.features[i]);
	}
	addData(featuresLayer, Features); 	// добавим и покажем всё остальное
	if(Points.length) {
		doClastering(pointsLayer, Points); 	// закластеризуем точки
		updClaster(pointsLayer);	// galadrielmap.js  и покажем
	}
} 	// end function onparse
return layer;
} // end function csvParse

function gpxParse(gpx, options, layer) {
/* 
Создаёт layerGroup из двух слоёв, в одном - линии, в другом - точки.

*/
//console.log('leaflet-omnivore [gpxParse] gpx:',gpx);
var xml = parseXML(gpx);	// делает DOM XML, если gpx -- строка, иначе не делает ничего
if (!xml) return windows.fire('error', {
    error: 'Could not parse GPX'
});
//console.log('leaflet-omnivore [gpxParse] xml:',xml);
if(layer) {
	//console.log('leaflet-omnivore [gpxParse] layer:',layer,layer instanceof L.layerGroup,"getLayers" in layer);
	if("getLayers" in layer) { 	// это layerGroup
		var featuresLayer = layer.getLayers()[0] || L.geoJson();
	}
	else {	// это одиночный Layer
		var featuresLayer = layer;
		layer = L.layerGroup([featuresLayer]); 	// попробуем сменть тип на layerGroup, но это обычно боком выходит. Но, вообще-то, layer создаётся как layerGroup.
	}
}
else {
	var featuresLayer = L.geoJson();
	var layer = L.layerGroup([featuresLayer]);
}

var geojson = toGeoJSON.gpx(xml);
//console.log('leaflet-omnivore [gpxParse] geojson:',geojson);

if(layer.properties) Object.assign(layer.properties,geojson.properties);
else layer.properties = geojson.properties;

var Points=[];
var Features=[];
for(let i=0; i<geojson.features.length;i++) {
	if(geojson.features[i].geometry.type=='Point') Points.push(geojson.features[i]);
	else {
		//console.log(geojson.features[i]);
		//if(geojson.features[i].properties.isRoute) geojson.features[i].properties.fileName = options.featureNameNode.innerText.trim();	// оно не надо
		Features.push(geojson.features[i]);
	}
}
//console.log('leaflet-omnivore [gpxParse] options:',options);
//console.log('leaflet-omnivore [gpxParse] Points:',Points);
//console.log('leaflet-omnivore [gpxParse] Features:',Features);
var color = globalCurrentColor;
globalCurrentColor = nextColor(globalCurrentColor); 	// сменим текущий цвет, from galadrielmap.js
if(color == 0xFFFFFF) featuresLayer.options.color = 0x3388FF; 	//  умолчальный цвет линий
else featuresLayer.options.color = color; 	//  цвет линий
if(options && options.featureNameNode) { 	// li с именем файла, из которого делаем layer
	options.featureNameNode.style.backgroundColor = '#'+('000000' + color.toString(16)).slice(-6);
}
featuresLayer.options.onEachFeature = getPopUpToLine; 	// функция, вызываемая для каждой feature при её создании
featuresLayer.options.style = function(geoJsonFeature){return{color: '#'+('000000' + featuresLayer.options.color.toString(16)).slice(-6)};}; 	// A Function defining the Path options for styling GeoJSON lines and polygons, called internally when data is added. 
// Добавим в слой объекты
featuresLayer.addData(Features); 	// добавим и покажем всё остальное
if(! layer.hasLayer(featuresLayer)) layer.addLayer(featuresLayer);
//console.log(featuresLayer);
// Теперь добавим точки
if(Points.length) {
	var pointsLayer = L.geoJson();
	pointsLayer.options.color = color; 	//  цвет значков
	pointsLayer.options.pointToLayer = function (geoJsonPoint, latlng) { 	// функция, вызываемая для каждой точки при её создании
		var parameters = {color: pointsLayer.options.color}; 	// таким образом мы забросим цвет в создание маркера
		var marker = getMarkerToPoint(geoJsonPoint, latlng, parameters);
		//marker.on('dblclick', L.DomEvent.stop).on('dblclick', tooggleEditRoute);
		//marker.on('click', L.DomEvent.stop).on('click', tooggleEditRoute); 	// galadrielmap.js чёта stop не работает?
		marker.on('click', tooggleEditRoute); 	// galadrielmap.js
		marker.on('editable:dragstart', function(event){
			// Нужно будет перестроить superclaster с точкой с новыми координатами
			removeFromSuperclaster(pointsLayer,event.target); 	// galadrielmap.js
		});
		marker.on('editable:dragend', function(event){
			// Нужно перестроить superclaster с точкой с новыми координатами
			//console.log('leaflet-omnivore.js [marker.on editable:dragend] pointsLayer:',pointsLayer);
			pointsLayer.supercluster.points.push(event.target.toGeoJSON());
			pointsLayer.supercluster = createSuperclaster(pointsLayer.supercluster.points); 	// galadrielmap.js создание нового и загрузка в суперкластер точек 		
		});
		return marker;
	};
	doClastering(pointsLayer, Points); 	// закластеризуем точки
	updClaster(pointsLayer);	// galadrielmap.js  и покажем
	layer.addLayer(pointsLayer);
}
//layer.options.fileName = options.featureNameNode.innerText.trim(); // Оно не надо?
//console.log(layer);
//console.log(layer.getLayers());
return layer;
}

function doClastering(layer, geojson) {
/* Кластеризует wpt в layer, если они там есть 
Требует наличия supercluster.js
*/
/*
const index = new Supercluster({
    log: false, 	// вывод лога в консоль
    radius: 40,
    extent: 256,
    maxZoom: 15,
}).load(geojson); 	// собственно, загрузка в суперкластер точек index
*/
layer.supercluster = createSuperclaster(geojson);	// galadrielmap.js
layer.on('click', (e) => { 	// клик по любому значку (вообще по любому месту?) :-( потому что нам нужен layer
	//console.log('leaflet-omnivore.js : doClastering start by click');
	//console.log(e);
	if (e.layer.feature.properties.cluster_id) { 	// кликнутый значёк - кластер
		const expansionZoom = e.target.supercluster.getClusterExpansionZoom(e.layer.feature.properties.cluster_id); 	// 	получим масштаб, при котором этот кластер разделится	
	    map.flyTo(e.latlng,expansionZoom);
	}
});
return layer;
} // end function doClastering

function getMarkerToPoint(geoJsonPoint, latlng, parameters) { 	//  https://leafletjs.com/reference-1.3.4.html#geojson 
// Функция, которая в latlng рисует маркер по сведениям из geoJsonPoint
// обычно вызывается как свойство layer.options.pointToLayer
// В geoJsonPoint.properties собираются:
//'ele' 'name', 'cmt', 'desc', 'src', 'number', 'author', 'copyright', 'sym', 'type', 'time', 'keywords' в function getProperties(node) для gpx
// 'name' 'icon' 'description' в function getPlacemark(root) для kml
// для csv просто берутся имеющиеся имена атрибутов, поэтому будем парсить имена отсюда: https://www.gpsbabel.org/htmldoc-1.5.4/fmt_unicsv.html
//console.log(parameters);

// Сам маркер - Marker
if(!parameters) parameters = {};
var marker = L.marker(latlng, { 	// маркер для этой точки
	riseOnHover: true
});
if(geoJsonPoint.properties.cluster) { 	// это кластер
	//console.log(geoJsonPoint);
	if(!parameters.color) parameters.color = 0xFFFFFF;
    const icon  = L.divIcon({
        html: `<div style="background-color: #${('000000' + parameters.color.toString(16)).slice(-6)};"><span>${  geoJsonPoint.properties.point_count_abbreviated  }</span></div>`,
        className: `marker-cluster`,
        iconSize: L.point(25, 25),
    });
	marker.setIcon(icon);
}
else { 	// это индивидуальная точка
	//console.log('marker for point');
	// Значёк - Icon
	//alert('icon' in marker.options);
	var iconNames = []; 	// возможные имена значков
	if(geoJsonPoint.properties.sym) iconNames.push(geoJsonPoint.properties.sym.trim().replace(/ /g, '_').replace(/,/g, '').toLowerCase()); 	// gpx sym (symbol name) attribyte
	if(geoJsonPoint.properties.symbol) iconNames.push(geoJsonPoint.properties.symbol.trim().replace(/ /g, '_').replace(/,/g, '').toLowerCase()); 	// csv symbol name attribyte
	if(geoJsonPoint.properties.symb) iconNames.push(geoJsonPoint.properties.symb.trim().replace(/ /g, '_').replace(/,/g, '').toLowerCase()); 	// csv symbol name attribyte
	if(geoJsonPoint.properties.type) iconNames.push(geoJsonPoint.properties.type.trim().replace(/ /g, '_').replace(/,/g, '').toLowerCase()); 	// gpx type (classification) attribyte
	if(geoJsonPoint.properties.icon) { 	// kml Icon
		//console.log('"'+geoJsonPoint.properties.icon.textContent.trim()+'"');
		var iNm = geoJsonPoint.properties.icon.textContent.trim();
		iNm = iNm.substring(iNm.lastIndexOf('/')+1);
		var iNmExt = iNm.slice((iNm.lastIndexOf(".") - 1 >>> 0) + 2); 	// icon filename ext https://www.jstips.co/en/javascript/get-file-extension/ потому что там нет естественного пути
		if(iNmExt.length) iNm = iNm.slice(0,-(iNmExt.length+1));
		//console.log(iNm);
		if(iNm.length) iconNames.push(iNm.replace(/ /g, '_').replace(/,/g, '').toLowerCase()); 	// kml icon name in <Style><IconStyle><Icon> attribyte
	}
	iconNames = [...new Set(iconNames)];	// только уникальные значения. Сначала из неуникального массива делается Set, потом из Set -- массив.
	iconServer.setIconCustomIcon(marker,iconNames); 	// заменить в marker icon на нужный асинхронно
	//console.log(iconServer.iconsByType);
	//console.log(marker);

	// Подпись - Tooltip
	if(geoJsonPoint.properties.name) {
		marker.bindTooltip(geoJsonPoint.properties.name,{ 	
			permanent: true,  	// всегда показывать
			//direction: 'auto', 
			//direction: 'left', 
			direction: 'top', 
			offset: [-16,0],
			className: 'wpTooltip', 	// css class
			opacity: 0.75
		});
		//}).openTooltip(); 	// и перерисуем подпись под умолчальный маркер. Под другие маркеры перерисуем потом. Но это бессмысленно - она не перерисовывается
	}

	// Информация о - PopUp
	//console.log(geoJsonPoint.properties.link);
	var popUpHTML = '';
	if(geoJsonPoint.properties.number) popUpHTML = geoJsonPoint.properties.number;
	if(geoJsonPoint.properties.name) popUpHTML = "<b>"+geoJsonPoint.properties.name+"</b> "+popUpHTML;
	if(!popUpHTML) popUpHTML = latlng.lat+" "+latlng.lng;
	//console.log('leaflet-omnivore.js [getMarkerToPoint] latlng:',latlng);
	popUpHTML = "<span style='font-size:120%'; onClick='doCopyToClipboard(\""+latlng.lat+" "+latlng.lng+"\")'>" + popUpHTML + "</span><br>";
	//popUpHTML = "<span style='font-size:120%';'>" + popUpHTML + "</span><br>";

	if(geoJsonPoint.properties.cmt) popUpHTML = popUpHTML+"<p>"+geoJsonPoint.properties.cmt.replace(/\n/g, '<br>')+"</p>"; 	// gpx description;
	if(geoJsonPoint.properties.desc) popUpHTML = popUpHTML+"<p>"+geoJsonPoint.properties.desc.replace(/\n/g, '<br>')+"</p>"; 	// gpx description
	if(geoJsonPoint.properties.notes) popUpHTML = popUpHTML+"<p>"+geoJsonPoint.properties.notes.replace(/\n/g, '<br>')+"</p>"; 	// csv description
	if(geoJsonPoint.properties.description) popUpHTML = popUpHTML+"<p>"+geoJsonPoint.properties.description.replace(/\n/g, '<br>')+"</p>"; 	// kml description
	if(geoJsonPoint.properties.comment) popUpHTML = popUpHTML+"<p>"+geoJsonPoint.properties.comment.replace(/\n/g, '<br>')+"</p>"; 	// csv description
	if(geoJsonPoint.properties.ele) popUpHTML = popUpHTML+"<p>Alt: "+geoJsonPoint.properties.ele+"</p>"; 	// gpx elevation
	if(geoJsonPoint.properties.alt) popUpHTML = popUpHTML+"<p>Alt: "+geoJsonPoint.properties.alt+"</p>"; 	// csv elevation
	if(geoJsonPoint.properties.height) popUpHTML = popUpHTML+"<p>Alt: "+geoJsonPoint.properties.height+"</p>"; 	// csv elevation
	if(geoJsonPoint.properties.depth) popUpHTML = popUpHTML+"<p>Alt: "+geoJsonPoint.properties.depth+"</p>"; 	// csv depth

	popUpHTML += getLinksHTML(geoJsonPoint); 	// приклеим ссылки

	marker.bindPopup(popUpHTML+'<br>'); 	// создадим PopUp, popUpHTML всегда не пуст
}
return marker;
} // end function getMarkerToPoint

function getLinksHTML(feature) {
/* Возвращает строку,которую можно было бы показать в PopUp, 
из атрибутов link в feature. Оформляет ссылки как может.
Пытается обнаружить ссылки на картинки и показывает для них фотоаппаратик.
*/
var camImgPath = leafletOmnivoreScript.src.substr(0, leafletOmnivoreScript.src.lastIndexOf("/"))+"/icons/cam.svg";
var popUpHTML = '';
var links = [];
if(feature.properties.link) links.push(feature.properties.link);
if(feature.properties.url) links.push(feature.properties.url);
if(!links.length) return popUpHTML;
// имеются ссылки
//console.log('имеются ссылки',links);
for (var i=0; i<links.length; i++) {
	var linkHTML = '';
	switch(typeof(links[i])) {
	case "string":
		linkHTML = '<a href="'+links[i]+'" target="_blank" >';
		if((links[i].slice(-5).toLowerCase()=='.jpeg') || (links[i].slice(-4).toLowerCase()=='.jpg') || (links[i].slice(-4).toLowerCase()=='.png') || (links[i].slice(-4).toLowerCase()=='.svg') || (links[i].slice(-4).toLowerCase()=='.tif') || (links[i].slice(-5).toLowerCase()=='.tiff')) {
			linkHTML = linkHTML + '<img src="'+camImgPath+'" width="12%" style="vertical-align: middle; margin:auto 1rem;"></a>';
		}
		else { 	// непонятная ссылка
			linkHTML = linkHTML + 'External link' + '</a><br>';
		}
		break;
	case "object":
		for(var j=0; j<links[i].length; j++) { 	// для каждой ссылки
			//console.log(links[i][j]);
			let link;
			if(links[i][j].attributes.length){
				if(links[i][j].attributes.http)	link = links[i][j].attributes.http.value.trim(); 	// зачем это было? Правильно же attributes.href?
				else if(links[i][j].attributes.href) link = links[i][j].attributes.href.value.trim();
			}
			else 	link = links[i][j].innerHTML.trim();
			linkHTML += '<a href="'+link+'" target=”_blank” >';
			var text = ' ', textAttr;
			if( textAttr = links[i][j].getElementsByTagName('text')[0]) text = textAttr.textContent+'<br>'; 	// есть атрибут text
			if(links[i][j].getElementsByTagName('type')[0]) { 	// есть атрибут type
				if( links[i][j].getElementsByTagName('type')[0].textContent.indexOf("image") != -1) { 	// если картинка
					linkHTML += '<img src="'+camImgPath+'" width="12%" style="vertical-align: middle; margin:auto 1rem;"></a>'+text;
				}
				else { 	// неизвестный тип
					if(!text) text = 'External link';
					linkHTML += text + '</a><br>';
				}
			}
			else { 	// нет атрибута type
				if((link.slice(-5).toLowerCase()=='.jpeg') || (link.slice(-4).toLowerCase()=='.jpg') || (link.slice(-4).toLowerCase()=='.png') || (link.slice(-4).toLowerCase()=='.svg') || (link.slice(-4).toLowerCase()=='.tif') || (link.slice(-5).toLowerCase()=='.tiff')) {
					linkHTML += '<img src="'+camImgPath+'" width="12%" style="vertical-align: middle; margin:auto 1rem;"></a>'+text;
				}
				else { 	// непонятная ссылка
					if(!text) text = 'External link';
					linkHTML += text + '</a><br>';
				}
			}
		}
		break;
	}
	popUpHTML = popUpHTML+linkHTML;
}
if(popUpHTML) popUpHTML = '<br>'+popUpHTML;
return popUpHTML;
}; 	// end function getLinksHTML

function getPopUpToLine(feature, layer) {
/* A Function that will be called once for each created Feature
*/
//console.log('leaflet-omnivore [getPopUpToLine] feature:',feature,'layer:',layer);
if(feature.properties && feature.properties.isRoute) { 	// это маршрут.
	//console.log('leaflet-omnivore [getPopUpToLine] drivedPolyLineOptions:',drivedPolyLineOptions,globalCurrentColor);
	Object.assign(layer.options,drivedPolyLineOptions.options);	// drivedPolyLineOptions из index.php
	Object.assign(layer.feature.properties,drivedPolyLineOptions.feature.properties);	// drivedPolyLineOptions из index.php
	layer.on('editable:editing', function (event){event.target.updateMeasurements();});	// обновлять расстояния при редактировании
	//layer.on('dblclick', L.DomEvent.stop).on('dblclick', tooggleEditRoute);
	//layer.on('click', L.DomEvent.stop).on('click', tooggleEditRoute); 	// galadrielmap.js чёта stop не работает?
	layer.on('click', tooggleEditRoute); 	// galadrielmap.js
}
if(feature.properties) {
	// Подпись - Tooltip
	if(feature.properties.name) {
		layer.bindTooltip(feature.properties.name,{ 	
			permanent: true,  	// всегда показывать
			direction: 'auto', 
			//direction: 'center', 
			//offset: [0,-15],
			className: 'wpTooltip', 	// css class
			opacity: 0.75
		});
	}
	// PopUp
	var popUpHTML = '';
	if(feature.properties.number) popUpHTML = " <span style='font-size:120%;'>"+feature.properties.number+"</span> "+popUpHTML;
	if(feature.properties.name) popUpHTML = "<b>"+feature.properties.name+"</b> "+popUpHTML;
	if(feature.properties.cmt) popUpHTML += "<p>"+feature.properties.cmt+"</p>";
	if(feature.properties.desc) popUpHTML += "<p>"+feature.properties.desc.replace(/\n/g, '<br>')+"</p>"; 	// gpx description
	if(feature.properties.description) popUpHTML += "<p>"+feature.properties.description.replace(/\n/g, '<br>')+"</p>"; 	// kml description
	popUpHTML += getLinksHTML(feature); 	// приклеим ссылки
	//if(feature.properties.name) popUpHTML = "<b>"+feature.properties.name+"</b> "+popUpHTML;
	if(popUpHTML) {
		layer.bindPopup(popUpHTML+'<br>');
	}
}
//console.log('leaflet-omnivore [getPopUpToLine] layer:',layer);
} // end function getPopUpToLine

// определение имени файла этого скрипта
var scripts = document.getElementsByTagName('script');
var index = scripts.length - 1; 	// это так, потому что эта часть сработает при загрузке скрипта, и он в этот момент - последний http://feather.elektrum.org/book/src.html
var leafletOmnivoreScript = scripts[index];
//console.log(leafletOmnivoreScript);

var iconServer = { 	
// типа, объект, централизованно раздающий L.icon, в которых уже есть сама картинка как base64
// объект скачивает требуемые файлы картинок и хранит. Когда надо -- указывает в объекте L.icon как iconUrl.
// Неиспользуемые картинки не удаляются, так что при удаче можно закачать в память все 400 картинок.
// Но это меньше 600Kb.
// Основная цель предварительной закачки картинок -- определить наличие файла с таким именем.
// Список возможных имён iconNames получен из разных мест показываемого (gpx, kml, csv) файла. Там,
// в принципе, могут быть разные слова, которые могут быть поняты как тип объекта, и которые
// могут стать именем файла значка для объекта. Но значка с таким именем в коллекции может не быть.
// Чтобы это понять, и установить умолчальный значёк -- и используется предварительная загрузка файла.
iconsByType: {}, 	// сюда будем складывать L.icon каждого типа
setIconCustomIcon: function (marker,iconNames) {
/* пытается создать L.icon с iconUrl из iconNames, где они без пути и расширения
при наличии такого файла - создаёт, устанавливает эту L.icon в marker
и складывает в iconsByType
*/
let iconName = iconNames.shift();
//console.log(iconName);
if(!iconName) return;
//console.log(this.iconsByType);
if(this.iconsByType[iconName]) {
	if(typeof this.iconsByType[iconName] === 'object') { 	// такая icon уже получена
		marker.setIcon(this.iconsByType[iconName]).openTooltip(); 	// если  icon с таким именем уже создавали - посадить значёк и перерисовать подпись
		//console.log('icon '+iconName+' из хранилища');
	}
	else { 	// такую icon кто-то сейчас получает
		// ждать
		let vait = setInterval(function(){ 	// запустим асинхронное ожидание. В результате сначала присвоится умолчальный значёк, а потом - нужный
			//console.log('Ждём icon '+iconName);
			if(iconServer.iconsByType[iconName] && typeof iconServer.iconsByType[iconName] === 'object') { 	// такая icon уже получена
				marker.setIcon(iconServer.iconsByType[iconName]).openTooltip(); 	// если  icon с таким именем уже создавали  посадить значёк и перерисовать подпись
				//console.log('Дождались icon '+iconName);
				clearInterval(vait); 	// прекратим ждать
			}
			else {
				if(iconServer.iconsByType[iconName] === false) {
					//console.log('Не дождались icon '+iconName);
					clearInterval(vait); 	// оно обломалось, прекратим ждать
				}
			}
		},100); 	// таймер на  милисекунд
	}
}
else if(this.iconsByType[iconName] === false) { 	// такой icon вообще нет, её кто-то пытался получить, но безуспешно
	//console.log('Уже был облом с icon '+iconName);
    iconServer.setIconCustomIcon(marker,iconNames); 	// вызовем себя для следующего имени
	// ничего не делать - поставится умолчальная
}
else { 	// такая icon ещё не получена
	this.iconsByType[iconName] = true; 	// укажем, что понеслось получать
	// получить асинхронно
	// все требуемые картинки значков скачиваются и хранятся в памяти
	// а нафига? А так мы узнаем, какой картинки нет.
	//console.log(leafletOmnivoreScript.src.substr(0, leafletOmnivoreScript.src.lastIndexOf("/")));
	fetch(leafletOmnivoreScript.src.substr(0, leafletOmnivoreScript.src.lastIndexOf("/"))+"/symbols/"+iconName+".png")
	.then(function(response) {
		//console.log(response);
		if(response.ok)	return response.blob(); 	// руками обработаем ошибки сервера
		else throw new Error('Network response was not ok for icon '+iconName); 	// Перейдём сразу к .catch
	})
	.then(function(blob){
		let iconURL = URL.createObjectURL(blob);	// здесь получается blob -- такой хитрый Data URL. В результате его понимает L.icon как ссылку, но файл уже загружен. Вопрос выгрузки остаётся открытым: ведь оно нужновсё время после загрузки, и загружается только один раз. https://developer.mozilla.org/ru/docs/Web/API/URL/createObjectURL
		//console.log(iconURL);
		let icon = L.icon({
			iconUrl: iconURL,
			iconSize: [32, 37],
			iconAnchor: [16, 37],
			tooltipAnchor: [16,-25],
			className: 'wpIcon'
		});
		iconServer.iconsByType[iconName] = icon;	// сохраним полученный значёк в кеше
		marker.setIcon(icon).openTooltip(); 	// посадить значёк и перерисовать подпись
		//console.log('Create and Set icon '+iconName);
		//console.log(marker);
	})
	.catch(function(error) {	// - не работает в случае 404!, поэтому выше throw new Error
		iconServer.iconsByType[iconName] = false; 	// укажем, что со значком облом
		console.log('iconServer setIconCustomIcon fetch error: ' + error.message);
	    iconServer.setIconCustomIcon(marker,iconNames); 	// вызовем себя для следующего имени
	});
}
}, // end function setIconCustomIcon, список атрибутов объекта продолжается
} // end object iconServer

function kmlParse(gpx, options, layer) {
/**/
var xml = parseXML(gpx);	// делает DOM XML, если gpx -- строка, иначе не делает ничего
if (!xml) return layer.fire('error', {
    error: 'Could not parse KML'
});
var geojson = toGeoJSON.kml(xml);
var Points=[];
var Features=[];
for(var i=0; i<geojson.features.length;i++) {
	if(geojson.features[i].geometry.type=='Point') Points.push(geojson.features[i]);
	else Features.push(geojson.features[i]);
}
if(layer) {
	if("getLayers" in layer) { 	// это layerGroup
		var featuresLayer = layer.getLayers()[0] || L.geoJson();
	}
	else {	// это одиночный Layer
		var featuresLayer = layer;
		layer = new L.layerGroup([featuresLayer]); 	// попробуем сменть тип на layerGroup, но это обычно боком выходит
	}
}
else {
	var featuresLayer = L.geoJson();
	var layer = new L.layerGroup([featuresLayer]);
}
var color = globalCurrentColor;
globalCurrentColor = nextColor(globalCurrentColor); 	// сменим текущий цвет, from galadrielmap.js
if(color == 0xFFFFFF) featuresLayer.options.color = 0x3388FF; 	//  умолчальный цвет линий
else featuresLayer.options.color = color; 	//  цвет линий
if(options.featureNameNode) { 	// li с именем файла, из которого делаем layer
	options.featureNameNode.style.backgroundColor = '#'+('000000' + color.toString(16)).slice(-6);
}
featuresLayer.options.onEachFeature = getPopUpToLine; 	// функция, вызываемая для каждой feature при её создании
featuresLayer.options.style = function(geoJsonFeature){return{color: '#'+('000000' + featuresLayer.options.color.toString(16)).slice(-6)};}; 	// A Function defining the Path options for styling GeoJSON lines and polygons, called internally when data is added. 
addData(featuresLayer, Features); 	// добавим и покажем всё остальное
if(! layer.hasLayer(featuresLayer)) layer.addLayer(featuresLayer);
if(Points.length) {
	var pointsLayer = L.geoJson();
	pointsLayer.options.color = color; 	//  цвет значков
	pointsLayer.options.pointToLayer = function (geoJsonPoint, latlng) { 	// функция, вызываемая для каждой точки при её создании
		var parameters = {color: pointsLayer.options.color}; 	// таким образом мы забросим цвет в создание маркера
		var marker = getMarkerToPoint(geoJsonPoint, latlng, parameters);
		return marker;
	};
	doClastering(pointsLayer, Points); 	// закластеризуем точки
	updClaster(pointsLayer);	// galadrielmap.js  и покажем
	layer.addLayer(pointsLayer);
}
return layer;
}

function polylineParse(txt, options, layer) {
    layer = layer || L.geoJson();
    options = options || {};
    var coords = polyline.decode(txt, options.precision);
    var geojson = { type: 'LineString', coordinates: [] };
    for (var i = 0; i < coords.length; i++) {
        // polyline returns coords in lat, lng order, so flip for geojson
        geojson.coordinates[i] = [coords[i][1], coords[i][0]];
    }
    addData(layer, geojson);
    return layer;
}

function wktParse(wkt, options, layer) {
    layer = layer || L.geoJson();
    var geojson = wellknown(wkt);
    addData(layer, geojson);
    return layer;
}

function parseXML(str) {
    if (typeof str === 'string') {
        return (new DOMParser()).parseFromString(str, 'text/xml');
    } else {
        return str;
    }
}

},{"corslite":3,"csv2geojson":4,"polyline":6,"togeojson":9,"topojson":10,"wellknown":11}],2:[function(require,module,exports){

},{}],3:[function(require,module,exports){
function corslite(url, callback, cors) {
    var sent = false;

    if (typeof window.XMLHttpRequest === 'undefined') {
        return callback(Error('Browser not supported'));
    }

    if (typeof cors === 'undefined') {
        var m = url.match(/^\s*https?:\/\/[^\/]*/);
        cors = m && (m[0] !== location.protocol + '//' + location.hostname +
                (location.port ? ':' + location.port : ''));
    }

    var x = new window.XMLHttpRequest();

    function isSuccessful(status) {
        return status >= 200 && status < 300 || status === 304;
    }
	/*
    if (cors && !('withCredentials' in x)) {
        // IE8-9
        x = new window.XDomainRequest();
		
        // Ensure callback is never called synchronously, i.e., before
        // x.send() returns (this has been observed in the wild).
        // See https://github.com/mapbox/mapbox.js/issues/472
        // Это костыль к косяку?
        var original = callback;
        callback = function() {
            if (sent) {
                original.apply(this, arguments);	// это эквивалентно просто вызову callback с её штатными аргументами
            } else {
                var that = this, args = arguments;
                setTimeout(function() {	// а это -- вызову callback после завершения текущего цикла корпоративной многозадачности, т.е. здесь -- заведомо не раньше, чем выполнится x.send(null);
                    original.apply(that, args);
                }, 0);
            }
        }
        
    }
	*/
    function loaded() {
        if (
            // XDomainRequest
            x.status === undefined ||
            // modern browsers
            isSuccessful(x.status)) callback.call(x, null, x);
        else callback.call(x, x, null);
    }

    // Both `onreadystatechange` and `onload` can fire. `onreadystatechange`
    // has [been supported for longer](http://stackoverflow.com/a/9181508/229001).
    if ('onload' in x) {
        x.onload = loaded;
    } else {
        x.onreadystatechange = function readystate() {
            if (x.readyState === 4) {
                loaded();
            }
        };
    }

    // Call the callback with the XMLHttpRequest object as an error and prevent
    // it from ever being called again by reassigning it to `noop`
    x.onerror = function error(evt) {
        // XDomainRequest provides no evt parameter
        callback.call(this, evt || true, null);
        callback = function() { };
    };

    // IE9 must have onprogress be set to a unique function.
    x.onprogress = function() { };

    x.ontimeout = function(evt) {
        callback.call(this, evt, null);
        callback = function() { };
    };

    x.onabort = function(evt) {
        callback.call(this, evt, null);
        callback = function() { };
    };

    // GET is the only supported HTTP Verb by XDomainRequest and is the
    // only one supported here.
    x.open('GET', url, true);	// асинхронно
    //x.open('GET', url, true);	// синхронно
	x.setRequestHeader("Cache-Control", "no-cache, no-store, max-age=0");	// иначе файл жестоко кешировался браузером, и никакого обновления не происходило

    // Send the request. Sending data is not supported.
    x.send(null);
    sent = true;

    return x;
}

if (typeof module !== 'undefined') module.exports = corslite;

},{}],4:[function(require,module,exports){
'use strict';

var dsv = require('d3-dsv'),
    sexagesimal = require('sexagesimal');

var latRegex = /(Lat)(itude)?/gi,
    lonRegex = /(L)(on|ng)(gitude)?/i;

function guessHeader(row, regexp) {
    var name, match, score;
    for (var f in row) {
        match = f.match(regexp);
        if (match && (!name || match[0].length / f.length > score)) {
            score = match[0].length / f.length;
            name = f;
        }
    }
    return name;
}

function guessLatHeader(row) { return guessHeader(row, latRegex); }
function guessLonHeader(row) { return guessHeader(row, lonRegex); }

function isLat(f) { return !!f.match(latRegex); }
function isLon(f) { return !!f.match(lonRegex); }

function keyCount(o) {
    return (typeof o == 'object') ? Object.keys(o).length : 0;
}

function autoDelimiter(x) {
    var delimiters = [',', ';', '\t', '|'];
    var results = [];

    delimiters.forEach(function (delimiter) {
        var res = dsv.dsvFormat(delimiter).parse(x);
        if (res.length >= 1) {
            var count = keyCount(res[0]);
            for (var i = 0; i < res.length; i++) {
                if (keyCount(res[i]) !== count) return;
            }
            results.push({
                delimiter: delimiter,
                arity: Object.keys(res[0]).length,
            });
        }
    });

    if (results.length) {
        return results.sort(function (a, b) {
            return b.arity - a.arity;
        })[0].delimiter;
    } else {
        return null;
    }
}

/**
 * Silly stopgap for dsv to d3-dsv upgrade
 *
 * @param {Array} x dsv output
 * @returns {Array} array without columns member
 */
function deleteColumns(x) {
    delete x.columns;
    return x;
}

function auto(x) {
    var delimiter = autoDelimiter(x);
    if (!delimiter) return null;
    return deleteColumns(dsv.dsvFormat(delimiter).parse(x));
}

function csv2geojson(x, options, callback) { // text csv целиком, options, onparse

    if (!callback) {
        callback = options;
        options = {};
    }

    options.delimiter = options.delimiter || ',';

    var latfield = options.latfield || '',
        lonfield = options.lonfield || '',
        crs = options.crs || '';

    var features = [],
        featurecollection = {type: 'FeatureCollection', features: features};

    if (crs !== '') {
        featurecollection.crs = {type: 'name', properties: {name: crs}};
    }

    if (options.delimiter === 'auto' && typeof x == 'string') {
        options.delimiter = autoDelimiter(x);
        if (!options.delimiter) {
            callback({
                type: 'Error',
                message: 'Could not autodetect delimiter'
            });
            return;
        }
    }
	// массив объектов из строк csv файла, начиная со второй, с именами атрибутов - из первой
    var parsed = (typeof x == 'string') ?
        dsv.dsvFormat(options.delimiter).parse(x) : x;
  	//console.log(parsed);

    if (!parsed.length) {
        callback(null, featurecollection);
        return;
    }

    var errors = [];
    var i;


    if (!latfield) latfield = guessLatHeader(parsed[0]);
    if (!lonfield) lonfield = guessLonHeader(parsed[0]);
    var noGeometry = (!latfield || !lonfield);

    if (noGeometry) {
        for (i = 0; i < parsed.length; i++) {
            features.push({
                type: 'Feature',
                properties: parsed[i],
                geometry: null
            });
        }
        callback(errors.length ? errors : null, featurecollection);
        return;
    }

    for (i = 0; i < parsed.length; i++) {
        if (parsed[i][lonfield] !== undefined &&
            parsed[i][latfield] !== undefined) {

            var lonk = parsed[i][lonfield],
                latk = parsed[i][latfield],
                lonf, latf,
                a;

            a = sexagesimal(lonk, 'EW');
            if (a) lonk = a;
            a = sexagesimal(latk, 'NS');
            if (a) latk = a;

            lonf = parseFloat(lonk);
            latf = parseFloat(latk);

            if (isNaN(lonf) ||
                isNaN(latf)) {
                errors.push({
                    message: 'A row contained an invalid value for latitude or longitude',
                    row: parsed[i],
                    index: i
                });
            } else {
                if (!options.includeLatLon) {
                    delete parsed[i][lonfield];
                    delete parsed[i][latfield];
                }

                features.push({
                    type: 'Feature',
                    properties: parsed[i],
                    geometry: {
                        type: 'Point',
                        coordinates: [
                            parseFloat(lonf),
                            parseFloat(latf)
                        ]
                    }
                });
            }
        }
    }

    callback(errors.length ? errors : null, featurecollection);
}

function toLine(gj) {
    var features = gj.features;
    var line = {
        type: 'Feature',
        geometry: {
            type: 'LineString',
            coordinates: []
        }
    };
    for (var i = 0; i < features.length; i++) {
        line.geometry.coordinates.push(features[i].geometry.coordinates);
    }
    line.properties = features.reduce(function (aggregatedProperties, newFeature) {
        for (var key in newFeature.properties) {
            if (!aggregatedProperties[key]) {
                aggregatedProperties[key] = [];
            }
            aggregatedProperties[key].push(newFeature.properties[key]);
        }
        return aggregatedProperties;
    }, {});
    return {
        type: 'FeatureCollection',
        features: [line]
    };
}

function toPolygon(gj) {
    var features = gj.features;
    var poly = {
        type: 'Feature',
        geometry: {
            type: 'Polygon',
            coordinates: [[]]
        }
    };
    for (var i = 0; i < features.length; i++) {
        poly.geometry.coordinates[0].push(features[i].geometry.coordinates);
    }
    poly.properties = features.reduce(function (aggregatedProperties, newFeature) {
        for (var key in newFeature.properties) {
            if (!aggregatedProperties[key]) {
                aggregatedProperties[key] = [];
            }
            aggregatedProperties[key].push(newFeature.properties[key]);
        }
        return aggregatedProperties;
    }, {});
    return {
        type: 'FeatureCollection',
        features: [poly]
    };
}

module.exports = {
    isLon: isLon,
    isLat: isLat,
    guessLatHeader: guessLatHeader,
    guessLonHeader: guessLonHeader,
    csv: dsv.csvParse,
    tsv: dsv.tsvParse,
    dsv: dsv,
    auto: auto,
    csv2geojson: csv2geojson,
    toLine: toLine,
    toPolygon: toPolygon
};

},{"d3-dsv":5,"sexagesimal":8}],5:[function(require,module,exports){
// https://d3js.org/d3-dsv/ Version 1.0.1. Copyright 2016 Mike Bostock.
(function (global, factory) {
  typeof exports === 'object' && typeof module !== 'undefined' ? factory(exports) :
  typeof define === 'function' && define.amd ? define(['exports'], factory) :
  (factory((global.d3 = global.d3 || {})));
}(this, function (exports) { 'use strict';

  function objectConverter(columns) {
    return new Function("d", "return {" + columns.map(function(name, i) {
      return JSON.stringify(name).toLowerCase() + ": d[" + i + "]"; 		// !!!
    }).join(",") + "}");
  }

  function customConverter(columns, f) {
    var object = objectConverter(columns);
    return function(row, i) {
      return f(object(row), i, columns);
    };
  }

  // Compute unique columns in order of discovery.
  function inferColumns(rows) {
    var columnSet = Object.create(null),
        columns = [];

    rows.forEach(function(row) {
      for (var column in row) {
        if (!(column in columnSet)) {
          columns.push(columnSet[column] = column);
        }
      }
    });

    return columns;
  }

  function dsv(delimiter) {
    var reFormat = new RegExp("[\"" + delimiter + "\n]"),
        delimiterCode = delimiter.charCodeAt(0);

    function parse(text, f) { 	// (строка - файл csv целиком,?), эта функция вызывается для разбора файла
      var convert, columns; 
      var rows = parseRows(text, function(row, i) {
		//console.log(row);
        if (convert) return convert(row, i - 1);
        //columns = row.map(function(colname){return colname.toLowerCase();}), convert = f ? customConverter(row, f) : objectConverter(row);
        columns = row, convert = f ? customConverter(row, f) : objectConverter(row);
		//console.log(convert);
      });
	  //console.log(columns);
      rows.columns = columns;
	  //console.log(rows);
      return rows;
    }

    function parseRows(text, f) {
      var EOL = {}, // sentinel value for end-of-line
          EOF = {}, // sentinel value for end-of-file
          rows = [], // output rows
          N = text.length,
          I = 0, // current character index
          n = 0, // the current line number
          t, // the current token
          eol; // is the current token followed by EOL?

      function token() {
        if (I >= N) return EOF; // special case: end of file
        if (eol) return eol = false, EOL; // special case: end of line

        // special case: quotes
        var j = I, c;
        if (text.charCodeAt(j) === 34) {
          var i = j;
          while (i++ < N) {
            if (text.charCodeAt(i) === 34) {
              if (text.charCodeAt(i + 1) !== 34) break;
              ++i;
            }
          }
          I = i + 2;
          c = text.charCodeAt(i + 1);
          if (c === 13) {
            eol = true;
            if (text.charCodeAt(i + 2) === 10) ++I;
          } else if (c === 10) {
            eol = true;
          }
          return text.slice(j + 1, i).replace(/""/g, "\"");
        }

        // common case: find next delimiter or newline
        while (I < N) {
          var k = 1;
          c = text.charCodeAt(I++);
          if (c === 10) eol = true; // \n
          else if (c === 13) { eol = true; if (text.charCodeAt(I) === 10) ++I, ++k; } // \r|\r\n
          else if (c !== delimiterCode) continue;
          return text.slice(j, I - k);
        }

        // special case: last token before EOF
        return text.slice(j);
      } // end function token

      while ((t = token()) !== EOF) {
      	//console.log(t);
        var a = [];
        while (t !== EOL && t !== EOF) {
			//console.log(t);
          a.push(t);
          t = token();
        }
      	//console.log(a);
        if (f && (a = f(a, n++)) == null) continue;
        rows.push(a);
      }

      return rows;
    } // end function parseRows

    function format(rows, columns) {
     if (columns == null) columns = inferColumns(rows);
      return [columns.map(formatValue).join(delimiter)].concat(rows.map(function(row) {
        return columns.map(function(column) {
          return formatValue(row[column]);
        }).join(delimiter);
      })).join("\n");
    }

    function formatRows(rows) {
      return rows.map(formatRow).join("\n");
    }

    function formatRow(row) {
      return row.map(formatValue).join(delimiter);
    }

    function formatValue(text) {
      return text == null ? ""
          : reFormat.test(text += "") ? "\"" + text.replace(/\"/g, "\"\"") + "\""
          : text;
    }

    return {
      parse: parse,
      parseRows: parseRows,
      format: format,
      formatRows: formatRows
    };
  }

  var csv = dsv(",");

  var csvParse = csv.parse;
  var csvParseRows = csv.parseRows;
  var csvFormat = csv.format;
  var csvFormatRows = csv.formatRows;

  var tsv = dsv("\t");

  var tsvParse = tsv.parse;
  var tsvParseRows = tsv.parseRows;
  var tsvFormat = tsv.format;
  var tsvFormatRows = tsv.formatRows;

  exports.dsvFormat = dsv;
  exports.csvParse = csvParse;
  exports.csvParseRows = csvParseRows;
  exports.csvFormat = csvFormat;
  exports.csvFormatRows = csvFormatRows;
  exports.tsvParse = tsvParse;
  exports.tsvParseRows = tsvParseRows;
  exports.tsvFormat = tsvFormat;
  exports.tsvFormatRows = tsvFormatRows;

  Object.defineProperty(exports, '__esModule', { value: true });

}));
},{}],6:[function(require,module,exports){
'use strict';

/**
 * Based off of [the offical Google document](https://developers.google.com/maps/documentation/utilities/polylinealgorithm)
 *
 * Some parts from [this implementation](http://facstaff.unca.edu/mcmcclur/GoogleMaps/EncodePolyline/PolylineEncoder.js)
 * by [Mark McClure](http://facstaff.unca.edu/mcmcclur/)
 *
 * @module polyline
 */

var polyline = {};

function encode(coordinate, factor) {
    coordinate = Math.round(coordinate * factor);
    coordinate <<= 1;
    if (coordinate < 0) {
        coordinate = ~coordinate;
    }
    var output = '';
    while (coordinate >= 0x20) {
        output += String.fromCharCode((0x20 | (coordinate & 0x1f)) + 63);
        coordinate >>= 5;
    }
    output += String.fromCharCode(coordinate + 63);
    return output;
}

/**
 * Decodes to a [latitude, longitude] coordinates array.
 *
 * This is adapted from the implementation in Project-OSRM.
 *
 * @param {String} str
 * @param {Number} precision
 * @returns {Array}
 *
 * @see https://github.com/Project-OSRM/osrm-frontend/blob/master/WebContent/routing/OSRM.RoutingGeometry.js
 */
polyline.decode = function(str, precision) {
    var index = 0,
        lat = 0,
        lng = 0,
        coordinates = [],
        shift = 0,
        result = 0,
        byte = null,
        latitude_change,
        longitude_change,
        factor = Math.pow(10, precision || 5);

    // Coordinates have variable length when encoded, so just keep
    // track of whether we've hit the end of the string. In each
    // loop iteration, a single coordinate is decoded.
    while (index < str.length) {

        // Reset shift, result, and byte
        byte = null;
        shift = 0;
        result = 0;

        do {
            byte = str.charCodeAt(index++) - 63;
            result |= (byte & 0x1f) << shift;
            shift += 5;
        } while (byte >= 0x20);

        latitude_change = ((result & 1) ? ~(result >> 1) : (result >> 1));

        shift = result = 0;

        do {
            byte = str.charCodeAt(index++) - 63;
            result |= (byte & 0x1f) << shift;
            shift += 5;
        } while (byte >= 0x20);

        longitude_change = ((result & 1) ? ~(result >> 1) : (result >> 1));

        lat += latitude_change;
        lng += longitude_change;

        coordinates.push([lat / factor, lng / factor]);
    }

    return coordinates;
};

/**
 * Encodes the given [latitude, longitude] coordinates array.
 *
 * @param {Array.<Array.<Number>>} coordinates
 * @param {Number} precision
 * @returns {String}
 */
polyline.encode = function(coordinates, precision) {
    if (!coordinates.length) { return ''; }

    var factor = Math.pow(10, precision || 5),
        output = encode(coordinates[0][0], factor) + encode(coordinates[0][1], factor);

    for (var i = 1; i < coordinates.length; i++) {
        var a = coordinates[i], b = coordinates[i - 1];
        output += encode(a[0] - b[0], factor);
        output += encode(a[1] - b[1], factor);
    }

    return output;
};

function flipped(coords) {
    var flipped = [];
    for (var i = 0; i < coords.length; i++) {
        flipped.push(coords[i].slice().reverse());
    }
    return flipped;
}

/**
 * Encodes a GeoJSON LineString feature/geometry.
 *
 * @param {Object} geojson
 * @param {Number} precision
 * @returns {String}
 */
polyline.fromGeoJSON = function(geojson, precision) {
    if (geojson && geojson.type === 'Feature') {
        geojson = geojson.geometry;
    }
    if (!geojson || geojson.type !== 'LineString') {
        throw new Error('Input must be a GeoJSON LineString');
    }
    return polyline.encode(flipped(geojson.coordinates), precision);
};

/**
 * Decodes to a GeoJSON LineString geometry.
 *
 * @param {String} str
 * @param {Number} precision
 * @returns {Object}
 */
polyline.toGeoJSON = function(str, precision) {
    var coords = polyline.decode(str, precision);
    return {
        type: 'LineString',
        coordinates: flipped(coords)
    };
};

if (typeof module === 'object' && module.exports) {
    module.exports = polyline;
}

},{}],7:[function(require,module,exports){
// shim for using process in browser
var process = module.exports = {};

// cached from whatever global is present so that test runners that stub it
// don't break things.  But we need to wrap it in a try catch in case it is
// wrapped in strict mode code which doesn't define any globals.  It's inside a
// function because try/catches deoptimize in certain engines.

var cachedSetTimeout;
var cachedClearTimeout;

function defaultSetTimout() {
    throw new Error('setTimeout has not been defined');
}
function defaultClearTimeout () {
    throw new Error('clearTimeout has not been defined');
}
(function () {
    try {
        if (typeof setTimeout === 'function') {
            cachedSetTimeout = setTimeout;
        } else {
            cachedSetTimeout = defaultSetTimout;
        }
    } catch (e) {
        cachedSetTimeout = defaultSetTimout;
    }
    try {
        if (typeof clearTimeout === 'function') {
            cachedClearTimeout = clearTimeout;
        } else {
            cachedClearTimeout = defaultClearTimeout;
        }
    } catch (e) {
        cachedClearTimeout = defaultClearTimeout;
    }
} ())
function runTimeout(fun) {
    if (cachedSetTimeout === setTimeout) {
        //normal enviroments in sane situations
        return setTimeout(fun, 0);
    }
    // if setTimeout wasn't available but was latter defined
    if ((cachedSetTimeout === defaultSetTimout || !cachedSetTimeout) && setTimeout) {
        cachedSetTimeout = setTimeout;
        return setTimeout(fun, 0);
    }
    try {
        // when when somebody has screwed with setTimeout but no I.E. maddness
        return cachedSetTimeout(fun, 0);
    } catch(e){
        try {
            // When we are in I.E. but the script has been evaled so I.E. doesn't trust the global object when called normally
            return cachedSetTimeout.call(null, fun, 0);
        } catch(e){
            // same as above but when it's a version of I.E. that must have the global object for 'this', hopfully our context correct otherwise it will throw a global error
            return cachedSetTimeout.call(this, fun, 0);
        }
    }


}
function runClearTimeout(marker) {
    if (cachedClearTimeout === clearTimeout) {
        //normal enviroments in sane situations
        return clearTimeout(marker);
    }
    // if clearTimeout wasn't available but was latter defined
    if ((cachedClearTimeout === defaultClearTimeout || !cachedClearTimeout) && clearTimeout) {
        cachedClearTimeout = clearTimeout;
        return clearTimeout(marker);
    }
    try {
        // when when somebody has screwed with setTimeout but no I.E. maddness
        return cachedClearTimeout(marker);
    } catch (e){
        try {
            // When we are in I.E. but the script has been evaled so I.E. doesn't  trust the global object when called normally
            return cachedClearTimeout.call(null, marker);
        } catch (e){
            // same as above but when it's a version of I.E. that must have the global object for 'this', hopfully our context correct otherwise it will throw a global error.
            // Some versions of I.E. have different rules for clearTimeout vs setTimeout
            return cachedClearTimeout.call(this, marker);
        }
    }



}
var queue = [];
var draining = false;
var currentQueue;
var queueIndex = -1;

function cleanUpNextTick() {
    if (!draining || !currentQueue) {
        return;
    }
    draining = false;
    if (currentQueue.length) {
        queue = currentQueue.concat(queue);
    } else {
        queueIndex = -1;
    }
    if (queue.length) {
        drainQueue();
    }
}

function drainQueue() {
    if (draining) {
        return;
    }
    var timeout = runTimeout(cleanUpNextTick);
    draining = true;

    var len = queue.length;
    while(len) {
        currentQueue = queue;
        queue = [];
        while (++queueIndex < len) {
            if (currentQueue) {
                currentQueue[queueIndex].run();
            }
        }
        queueIndex = -1;
        len = queue.length;
    }
    currentQueue = null;
    draining = false;
    runClearTimeout(timeout);
}

process.nextTick = function (fun) {
    var args = new Array(arguments.length - 1);
    if (arguments.length > 1) {
        for (var i = 1; i < arguments.length; i++) {
            args[i - 1] = arguments[i];
        }
    }
    queue.push(new Item(fun, args));
    if (queue.length === 1 && !draining) {
        runTimeout(drainQueue);
    }
};

// v8 likes predictible objects
function Item(fun, array) {
    this.fun = fun;
    this.array = array;
}
Item.prototype.run = function () {
    this.fun.apply(null, this.array);
};
process.title = 'browser';
process.browser = true;
process.env = {};
process.argv = [];
process.version = ''; // empty string to avoid regexp issues
process.versions = {};

function noop() {}

process.on = noop;
process.addListener = noop;
process.once = noop;
process.off = noop;
process.removeListener = noop;
process.removeAllListeners = noop;
process.emit = noop;
process.prependListener = noop;
process.prependOnceListener = noop;

process.listeners = function (name) { return [] }

process.binding = function (name) {
    throw new Error('process.binding is not supported');
};

process.cwd = function () { return '/' };
process.chdir = function (dir) {
    throw new Error('process.chdir is not supported');
};
process.umask = function() { return 0; };

},{}],8:[function(require,module,exports){
module.exports = element;
module.exports.pair = pair;
module.exports.format = format;
module.exports.formatPair = formatPair;
module.exports.coordToDMS = coordToDMS;

function element(x, dims) {
  return search(x, dims).val;
}

function formatPair(x) {
  return format(x.lat, 'lat') + ' ' + format(x.lon, 'lon');
}

// Is 0 North or South?
function format(x, dim) {
  var dms = coordToDMS(x,dim);
  return dms.whole + '° ' +
    (dms.minutes ? dms.minutes + '\' ' : '') +
    (dms.seconds ? dms.seconds + '" ' : '') + dms.dir;
}

function coordToDMS(x,dim) {
  var dirs = {
    lat: ['N', 'S'],
    lon: ['E', 'W']
  }[dim] || '',
  dir = dirs[x >= 0 ? 0 : 1],
    abs = Math.abs(x),
    whole = Math.floor(abs),
    fraction = abs - whole,
    fractionMinutes = fraction * 60,
    minutes = Math.floor(fractionMinutes),
    seconds = Math.floor((fractionMinutes - minutes) * 60);

  return {
    whole: whole,
    minutes: minutes,
    seconds: seconds,
    dir: dir
  };
}

function search(x, dims, r) {
  if (!dims) dims = 'NSEW';
  if (typeof x !== 'string') return { val: null, regex: r };
  r = r || /[\s\,]*([\-|\—|\―]?[0-9.]+)°? *(?:([0-9.]+)['’′‘] *)?(?:([0-9.]+)(?:''|"|”|″) *)?([NSEW])?/gi;
  var m = r.exec(x);
  if (!m) return { val: null, regex: r };
  else if (m[4] && dims.indexOf(m[4]) === -1) return { val: null, regex: r };
  else return {
    val: (((m[1]) ? parseFloat(m[1]) : 0) +
          ((m[2] ? parseFloat(m[2]) / 60 : 0)) +
          ((m[3] ? parseFloat(m[3]) / 3600 : 0))) *
          ((m[4] && m[4] === 'S' || m[4] === 'W') ? -1 : 1),
    regex: r,
    raw: m[0],
    dim: m[4]
  };
}

function pair(x, dims) {
  x = x.trim();
  var one = search(x, dims);
  if (one.val === null) return null;
  var two = search(x, dims, one.regex);
  if (two.val === null) return null;
  // null if one/two are not contiguous.
  if (one.raw + two.raw !== x) return null;
  if (one.dim) {
    return swapdim(one.val, two.val, one.dim);
  } else {
    return [one.val, two.val];
  }
}

function swapdim(a, b, dim) {
  if (dim === 'N' || dim === 'S') return [a, b];
  if (dim === 'W' || dim === 'E') return [b, a];
}

},{}],9:[function(require,module,exports){
(function (process){
var toGeoJSON = (function() {
    'use strict';

    var removeSpace = (/\s*/g),
        trimSpace = (/^\s*|\s*$/g),
        splitSpace = (/\s+/);
    // generate a short, numeric hash of a string
    function okhash(x) {
        if (!x || !x.length) return 0;
        for (var i = 0, h = 0; i < x.length; i++) {
            h = ((h << 5) - h) + x.charCodeAt(i) | 0;
        } return h;
    }
    // all Y children of X
    function get(x, y) { return x.getElementsByTagName(y); }
    function attr(x, y) { return x.getAttribute(y); }
    function attrf(x, y) { return parseFloat(attr(x, y)); }
    // one Y child of X, if any, otherwise null
    function get1(x, y) { 
    	const n = get(x, y);
    	//if(y == 'desc') console.log(n); 
    	return n.length ? n[0] : null; 
    }
    // https://developer.mozilla.org/en-US/docs/Web/API/Node.normalize
    function norm(el) { if (el.normalize) { el.normalize(); } return el; }
    // cast array x into numbers
    function numarray(x) {
        for (var j = 0, o = []; j < x.length; j++) { o[j] = parseFloat(x[j]); }
        return o;
    }
    function clean(x) {
        var o = {};
        for (var i in x) { if (x[i]) { o[i] = x[i]; } }
        return o;
    }
    // get the content of a text node, if any
    function nodeVal(x) {
        if (x) { norm(x); }
        return (x && x.textContent) || '';
    }
    // get one coordinate from a coordinate array, if any
    function coord1(v) { return numarray(v.replace(removeSpace, '').split(',')); }
    // get all coordinates from a coordinate array as [[],[]]
    function coord(v) {
        var coords = v.replace(trimSpace, '').split(splitSpace),
            o = [];
        for (var i = 0; i < coords.length; i++) {
            o.push(coord1(coords[i]));
        }
        return o;
    }
    function coordPair(x) {
        var ll = [attrf(x, 'lon'), attrf(x, 'lat')],
            ele = get1(x, 'ele'),
            // handle namespaced attribute in browser
            heartRate = get1(x, 'gpxtpx:hr') || get1(x, 'hr'),
            time = get1(x, 'time'),
            e;
        if (ele) {
            e = parseFloat(nodeVal(ele));
            if (!isNaN(e)) {
                ll.push(e);
            }
        }
        return {
            coordinates: ll,
            time: time ? nodeVal(time) : null,
            heartRate: heartRate ? parseFloat(nodeVal(heartRate)) : null
        };
    }

    // create a new feature collection parent object
    function fc() {
        return {
            type: 'FeatureCollection',
            features: []
        };
    }

    var serializer;
    if (typeof XMLSerializer !== 'undefined') {
        /* istanbul ignore next */
        serializer = new XMLSerializer();
    // only require xmldom in a node environment
    } else if (typeof exports === 'object' && typeof process === 'object' && !process.browser) {
        serializer = new (require('xmldom').XMLSerializer)();
    }
    function xml2str(str) {
        // IE9 will create a new XMLSerializer but it'll crash immediately.
        // This line is ignored because we don't run coverage tests in IE9
        /* istanbul ignore next */
        if (str.xml !== undefined) return str.xml;
        return serializer.serializeToString(str);
    }

    var t = {
        kml: function(doc) {

            var gj = fc(),
                // styleindex keeps track of hashed styles in order to match features
                styleIndex = {},
                // atomic geospatial types supported by KML - MultiGeometry is
                // handled separately
                geotypes = ['Polygon', 'LineString', 'Point', 'Track', 'gx:Track'],
                // all root placemarks in the file
                placemarks = get(doc, 'Placemark'),
                styles = get(doc, 'Style'),
                styleMaps = get(doc, 'StyleMap');

            for (var k = 0; k < styles.length; k++) {
                styleIndex['#' + attr(styles[k], 'id')] = okhash(xml2str(styles[k])).toString(16);
            }
            for (var l = 0; l < styleMaps.length; l++) {
                styleIndex['#' + attr(styleMaps[l], 'id')] = okhash(xml2str(styleMaps[l])).toString(16);
            }
            for (var j = 0; j < placemarks.length; j++) {
                gj.features = gj.features.concat(getPlacemark(placemarks[j]));
            }
            function kmlColor(v) {
                var color, opacity;
                v = v || '';
                if (v.substr(0, 1) === '#') { v = v.substr(1); }
                if (v.length === 6 || v.length === 3) { color = v; }
                if (v.length === 8) {
                    opacity = parseInt(v.substr(0, 2), 16) / 255;
                    color = '#'+v.substr(2);
                }
                return [color, isNaN(opacity) ? undefined : opacity];
            }
            function gxCoord(v) { return numarray(v.split(' ')); }
            function gxCoords(root) {
                var elems = get(root, 'coord', 'gx'), coords = [], times = [];
                if (elems.length === 0) elems = get(root, 'gx:coord');
                for (var i = 0; i < elems.length; i++) coords.push(gxCoord(nodeVal(elems[i])));
                var timeElems = get(root, 'when');
                for (var j = 0; j < timeElems.length; j++) times.push(nodeVal(timeElems[j]));
                return {
                    coords: coords,
                    times: times
                };
            }
            function getGeometry(root) {
                var geomNode, geomNodes, i, j, k, geoms = [], coordTimes = [];
                if (get1(root, 'MultiGeometry')) { return getGeometry(get1(root, 'MultiGeometry')); }
                if (get1(root, 'MultiTrack')) { return getGeometry(get1(root, 'MultiTrack')); }
                if (get1(root, 'gx:MultiTrack')) { return getGeometry(get1(root, 'gx:MultiTrack')); }
                for (i = 0; i < geotypes.length; i++) {
                    geomNodes = get(root, geotypes[i]);
                    if (geomNodes) {
                        for (j = 0; j < geomNodes.length; j++) {
                            geomNode = geomNodes[j];
                            if (geotypes[i] === 'Point') {
                                geoms.push({
                                    type: 'Point',
                                    coordinates: coord1(nodeVal(get1(geomNode, 'coordinates')))
                                });
                            } else if (geotypes[i] === 'LineString') {
                                geoms.push({
                                    type: 'LineString',
                                    coordinates: coord(nodeVal(get1(geomNode, 'coordinates')))
                                });
                            } else if (geotypes[i] === 'Polygon') {
                                var rings = get(geomNode, 'LinearRing'),
                                    coords = [];
                                for (k = 0; k < rings.length; k++) {
                                    coords.push(coord(nodeVal(get1(rings[k], 'coordinates'))));
                                }
                                geoms.push({
                                    type: 'Polygon',
                                    coordinates: coords
                                });
                            } else if (geotypes[i] === 'Track' ||
                                geotypes[i] === 'gx:Track') {
                                var track = gxCoords(geomNode);
                                geoms.push({
                                    type: 'LineString',
                                    coordinates: track.coords
                                });
                                if (track.times.length) coordTimes.push(track.times);
                            }
                        }
                    }
                }
                return {
                    geoms: geoms,
                    coordTimes: coordTimes
                };
            }
            function getPlacemark(root) {
                var geomsAndTimes = getGeometry(root), i, properties = {},
                    name = nodeVal(get1(root, 'name')),
                    styleUrl = nodeVal(get1(root, 'styleUrl')),
                    description = nodeVal(get1(root, 'description')),
                    timeSpan = get1(root, 'TimeSpan'),
                    extendedData = get1(root, 'ExtendedData'),
                    lineStyle = get1(root, 'LineStyle'),
                    polyStyle = get1(root, 'PolyStyle'),
                    icon = get1(root, 'Icon'); 	// !!

                if (!geomsAndTimes.geoms.length) return [];
                if (name) properties.name = name;
                if (icon) properties.icon = icon; 	// !!
                if (styleUrl[0] !== '#') {
                    styleUrl = '#' + styleUrl;
                }
                if (styleUrl && styleIndex[styleUrl]) {
                    properties.styleUrl = styleUrl;
                    properties.styleHash = styleIndex[styleUrl];
                }
                if (description) properties.description = description;
                if (timeSpan) {
                    var begin = nodeVal(get1(timeSpan, 'begin'));
                    var end = nodeVal(get1(timeSpan, 'end'));
                    properties.timespan = { begin: begin, end: end };
                }
                if (lineStyle) {
                    var linestyles = kmlColor(nodeVal(get1(lineStyle, 'color'))),
                        color = linestyles[0],
                        opacity = linestyles[1],
                        width = parseFloat(nodeVal(get1(lineStyle, 'width')));
                    if (color) properties.stroke = color;
                    if (!isNaN(opacity)) properties['stroke-opacity'] = opacity;
                    if (!isNaN(width)) properties['stroke-width'] = width;
                }
                if (polyStyle) {
                    var polystyles = kmlColor(nodeVal(get1(polyStyle, 'color'))),
                        pcolor = polystyles[0],
                        popacity = polystyles[1],
                        fill = nodeVal(get1(polyStyle, 'fill')),
                        outline = nodeVal(get1(polyStyle, 'outline'));
                    if (pcolor) properties.fill = pcolor;
                    if (!isNaN(popacity)) properties['fill-opacity'] = popacity;
                    if (fill) properties['fill-opacity'] = fill === '1' ? 1 : 0;
                    if (outline) properties['stroke-opacity'] = outline === '1' ? 1 : 0;
                }
                if (extendedData) {
                    var datas = get(extendedData, 'Data'),
                        simpleDatas = get(extendedData, 'SimpleData');

                    for (i = 0; i < datas.length; i++) {
                        properties[datas[i].getAttribute('name')] = nodeVal(get1(datas[i], 'value'));
                    }
                    for (i = 0; i < simpleDatas.length; i++) {
                        properties[simpleDatas[i].getAttribute('name')] = nodeVal(simpleDatas[i]);
                    }
                }
                if (geomsAndTimes.coordTimes.length) {
                    properties.coordTimes = (geomsAndTimes.coordTimes.length === 1) ?
                        geomsAndTimes.coordTimes[0] : geomsAndTimes.coordTimes;
                }
                var feature = {
                    type: 'Feature',
                    geometry: (geomsAndTimes.geoms.length === 1) ? geomsAndTimes.geoms[0] : {
                        type: 'GeometryCollection',
                        geometries: geomsAndTimes.geoms
                    },
                    properties: properties
                };
                if (attr(root, 'id')) feature.id = attr(root, 'id');
                return [feature];
            }
            return gj;
        },
        gpx: function(doc) {
        	//console.log(doc);
            var i,
                tracks = get(doc, 'trk'),	// get() возвращает HTML Collection
                routes = get(doc, 'rte'),
                waypoints = get(doc, 'wpt'),
                metadata = get(doc, 'metadata'), 	// no way to save metadata to GeoJSON А, собственно, почему?
                gj = fc(), 	// a feature collection
                feature,
            	prevPoint; 	// для показа сегментов из одной точки будем делать из них сегмент из двух точек - своей и предыдущей
			//if(metadata.length) console.log('leaflet-omnivore [gpx:] metadata:',metadata,getProperties(metadata[0]));
			//if(routes.length) console.log('leaflet-omnivore [gpx:] routes:',routes,getProperties(routes[0]));
			if(metadata.length) gj.properties = getProperties(metadata[0]);
            for (i = 0; i < tracks.length; i++) {
                feature = getTrack(tracks[i]);
                if (feature) gj.features.push(feature);
            }
            for (i = 0; i < routes.length; i++) {
                feature = getRoute(routes[i]);
                if (feature) gj.features.push(feature);
            }
            for (i = 0; i < waypoints.length; i++) {
                gj.features.push(getPoint(waypoints[i]));
            }
			//console.log('leaflet-omnivore [gpx:] gj:',gj);
            function getPoints(node, pointname) {
                var pts = get(node, pointname),
                    line = [],
                    times = [],
                    heartRates = [],
                    l = pts.length;
				//console.log(prevPoint);
                if (l < 2) {  					// Invalid line in GeoJSON  !!
                	if(prevPoint && (prevPoint!=pts[l-1])) {
                		var pts1 = []; 			// pts - HTMLcollection, и хрен туда что добавишь
                		pts1.push(prevPoint); 	// добавим в начало предыдущую точку
				        for (var i = 0; i < l; i++) {
				            pts1.push(pts[i]); 	// добавим всё остальное
						}
						prevPoint = pts[l-1];
						pts = pts1; 			// теперь pts - массив
	                    l = pts.length;
                	}
                	else prevPoint = pts[l-1];
                }
				else prevPoint = pts[l-1];
				//console.log(pts);
                for (var i = 0; i < l; i++) {
                    var c = coordPair(pts[i]);
                    line.push(c.coordinates);
                    if (c.time) times.push(c.time);
                    if (c.heartRate) heartRates.push(c.heartRate);
                }
                return {
                    line: line,
                    times: times,
                    heartRates: heartRates
                };
            }
            function getTrack(node) {
                var segments = get(node, 'trkseg'),
                    track = [],
                    times = [],
                    heartRates = [],
                    line;
                for (var i = 0; i < segments.length; i++) {
                    line = getPoints(segments[i], 'trkpt');
                    if (line.line) track.push(line.line);
                    if (line.times && line.times.length) times.push(line.times);
                    if (line.heartRates && line.heartRates.length) heartRates.push(line.heartRates);
                }
                if (track.length === 0) return;
                var properties = getProperties(node);
                if (times.length) properties.coordTimes = track.length === 1 ? times[0] : times;
                if (heartRates.length) properties.heartRates = track.length === 1 ? heartRates[0] : heartRates;
                return {
                    type: 'Feature',
                    properties: properties,
                    geometry: {
                        type: track.length === 1 ? 'LineString' : 'MultiLineString',
                        coordinates: track.length === 1 ? track[0] : track
                    }
                };
            }
            function getRoute(node) {
                var line = getPoints(node, 'rtept');
                if (!line.line) return;
                //console.log('leaflet-omnivore [getRoute] node:',node,getProperties(node));
                var routeObj = {
                    type: 'Feature',
                    properties: getProperties(node),
                    geometry: {
                        type: 'LineString',
                        coordinates: line.line
                    }
                };
                routeObj.properties.isRoute = true;
                //console.log('leaflet-omnivore [getRoute] routeObj:',routeObj);
                return routeObj;
            }
            function getPoint(node) {
                var prop = getProperties(node);
                prop.sym = nodeVal(get1(node, 'sym'));
                return {
                    type: 'Feature',
                    properties: prop,
                    geometry: {
                        type: 'Point',
                        coordinates: coordPair(node).coordinates
                    }
                };
            }
            function getProperties(node) {
                let meta = ['ele', 'name', 'cmt', 'desc', 'src', 'number', 'author', 'copyright', 'sym', 'type', 'time', 'keywords'], 	// список уникальных свойств, которые будем получать
                    prop = {},
                    k;
                for (k = 0; k < meta.length; k++) {
                    prop[meta[k]] = nodeVal(get1(node, meta[k]));
                }
                meta=['link','extensions']; 	 	// список неуникальных и/или составных свойств, которые будем получать
                for (k = 0; k < meta.length; k++) {
                	prop[meta[k]] = node.getElementsByTagName(meta[k]);
                }
                return clean(prop);
            }
            return gj;
        }
    };
    return t;
})();

if (typeof module !== 'undefined') module.exports = toGeoJSON;

}).call(this,require('_process'))
},{"_process":7,"xmldom":2}],10:[function(require,module,exports){
(function (global, factory) {
  typeof exports === 'object' && typeof module !== 'undefined' ? factory(exports) :
  typeof define === 'function' && define.amd ? define(['exports'], factory) :
  (factory((global.topojson = global.topojson || {})));
}(this, function (exports) { 'use strict';

  function noop() {}

  function transformAbsolute(transform) {
    if (!transform) return noop;
    var x0,
        y0,
        kx = transform.scale[0],
        ky = transform.scale[1],
        dx = transform.translate[0],
        dy = transform.translate[1];
    return function(point, i) {
      if (!i) x0 = y0 = 0;
      point[0] = (x0 += point[0]) * kx + dx;
      point[1] = (y0 += point[1]) * ky + dy;
    };
  }

  function transformRelative(transform) {
    if (!transform) return noop;
    var x0,
        y0,
        kx = transform.scale[0],
        ky = transform.scale[1],
        dx = transform.translate[0],
        dy = transform.translate[1];
    return function(point, i) {
      if (!i) x0 = y0 = 0;
      var x1 = Math.round((point[0] - dx) / kx),
          y1 = Math.round((point[1] - dy) / ky);
      point[0] = x1 - x0;
      point[1] = y1 - y0;
      x0 = x1;
      y0 = y1;
    };
  }

  function reverse(array, n) {
    var t, j = array.length, i = j - n;
    while (i < --j) t = array[i], array[i++] = array[j], array[j] = t;
  }

  function bisect(a, x) {
    var lo = 0, hi = a.length;
    while (lo < hi) {
      var mid = lo + hi >>> 1;
      if (a[mid] < x) lo = mid + 1;
      else hi = mid;
    }
    return lo;
  }

  function feature(topology, o) {
    return o.type === "GeometryCollection" ? {
      type: "FeatureCollection",
      features: o.geometries.map(function(o) { return feature$1(topology, o); })
    } : feature$1(topology, o);
  }

  function feature$1(topology, o) {
    var f = {
      type: "Feature",
      id: o.id,
      properties: o.properties || {},
      geometry: object(topology, o)
    };
    if (o.id == null) delete f.id;
    return f;
  }

  function object(topology, o) {
    var absolute = transformAbsolute(topology.transform),
        arcs = topology.arcs;

    function arc(i, points) {
      if (points.length) points.pop();
      for (var a = arcs[i < 0 ? ~i : i], k = 0, n = a.length, p; k < n; ++k) {
        points.push(p = a[k].slice());
        absolute(p, k);
      }
      if (i < 0) reverse(points, n);
    }

    function point(p) {
      p = p.slice();
      absolute(p, 0);
      return p;
    }

    function line(arcs) {
      var points = [];
      for (var i = 0, n = arcs.length; i < n; ++i) arc(arcs[i], points);
      if (points.length < 2) points.push(points[0].slice());
      return points;
    }

    function ring(arcs) {
      var points = line(arcs);
      while (points.length < 4) points.push(points[0].slice());
      return points;
    }

    function polygon(arcs) {
      return arcs.map(ring);
    }

    function geometry(o) {
      var t = o.type;
      return t === "GeometryCollection" ? {type: t, geometries: o.geometries.map(geometry)}
          : t in geometryType ? {type: t, coordinates: geometryType[t](o)}
          : null;
    }

    var geometryType = {
      Point: function(o) { return point(o.coordinates); },
      MultiPoint: function(o) { return o.coordinates.map(point); },
      LineString: function(o) { return line(o.arcs); },
      MultiLineString: function(o) { return o.arcs.map(line); },
      Polygon: function(o) { return polygon(o.arcs); },
      MultiPolygon: function(o) { return o.arcs.map(polygon); }
    };

    return geometry(o);
  }

  function stitchArcs(topology, arcs) {
    var stitchedArcs = {},
        fragmentByStart = {},
        fragmentByEnd = {},
        fragments = [],
        emptyIndex = -1;

    // Stitch empty arcs first, since they may be subsumed by other arcs.
    arcs.forEach(function(i, j) {
      var arc = topology.arcs[i < 0 ? ~i : i], t;
      if (arc.length < 3 && !arc[1][0] && !arc[1][1]) {
        t = arcs[++emptyIndex], arcs[emptyIndex] = i, arcs[j] = t;
      }
    });

    arcs.forEach(function(i) {
      var e = ends(i),
          start = e[0],
          end = e[1],
          f, g;

      if (f = fragmentByEnd[start]) {
        delete fragmentByEnd[f.end];
        f.push(i);
        f.end = end;
        if (g = fragmentByStart[end]) {
          delete fragmentByStart[g.start];
          var fg = g === f ? f : f.concat(g);
          fragmentByStart[fg.start = f.start] = fragmentByEnd[fg.end = g.end] = fg;
        } else {
          fragmentByStart[f.start] = fragmentByEnd[f.end] = f;
        }
      } else if (f = fragmentByStart[end]) {
        delete fragmentByStart[f.start];
        f.unshift(i);
        f.start = start;
        if (g = fragmentByEnd[start]) {
          delete fragmentByEnd[g.end];
          var gf = g === f ? f : g.concat(f);
          fragmentByStart[gf.start = g.start] = fragmentByEnd[gf.end = f.end] = gf;
        } else {
          fragmentByStart[f.start] = fragmentByEnd[f.end] = f;
        }
      } else {
        f = [i];
        fragmentByStart[f.start = start] = fragmentByEnd[f.end = end] = f;
      }
    });

    function ends(i) {
      var arc = topology.arcs[i < 0 ? ~i : i], p0 = arc[0], p1;
      if (topology.transform) p1 = [0, 0], arc.forEach(function(dp) { p1[0] += dp[0], p1[1] += dp[1]; });
      else p1 = arc[arc.length - 1];
      return i < 0 ? [p1, p0] : [p0, p1];
    }

    function flush(fragmentByEnd, fragmentByStart) {
      for (var k in fragmentByEnd) {
        var f = fragmentByEnd[k];
        delete fragmentByStart[f.start];
        delete f.start;
        delete f.end;
        f.forEach(function(i) { stitchedArcs[i < 0 ? ~i : i] = 1; });
        fragments.push(f);
      }
    }

    flush(fragmentByEnd, fragmentByStart);
    flush(fragmentByStart, fragmentByEnd);
    arcs.forEach(function(i) { if (!stitchedArcs[i < 0 ? ~i : i]) fragments.push([i]); });

    return fragments;
  }

  function mesh(topology) {
    return object(topology, meshArcs.apply(this, arguments));
  }

  function meshArcs(topology, o, filter) {
    var arcs = [];

    function arc(i) {
      var j = i < 0 ? ~i : i;
      (geomsByArc[j] || (geomsByArc[j] = [])).push({i: i, g: geom});
    }

    function line(arcs) {
      arcs.forEach(arc);
    }

    function polygon(arcs) {
      arcs.forEach(line);
    }

    function geometry(o) {
      if (o.type === "GeometryCollection") o.geometries.forEach(geometry);
      else if (o.type in geometryType) geom = o, geometryType[o.type](o.arcs);
    }

    if (arguments.length > 1) {
      var geomsByArc = [],
          geom;

      var geometryType = {
        LineString: line,
        MultiLineString: polygon,
        Polygon: polygon,
        MultiPolygon: function(arcs) { arcs.forEach(polygon); }
      };

      geometry(o);

      geomsByArc.forEach(arguments.length < 3
          ? function(geoms) { arcs.push(geoms[0].i); }
          : function(geoms) { if (filter(geoms[0].g, geoms[geoms.length - 1].g)) arcs.push(geoms[0].i); });
    } else {
      for (var i = 0, n = topology.arcs.length; i < n; ++i) arcs.push(i);
    }

    return {type: "MultiLineString", arcs: stitchArcs(topology, arcs)};
  }

  function cartesianTriangleArea(triangle) {
    var a = triangle[0], b = triangle[1], c = triangle[2];
    return Math.abs((a[0] - c[0]) * (b[1] - a[1]) - (a[0] - b[0]) * (c[1] - a[1]));
  }

  function ring(ring) {
    var i = -1,
        n = ring.length,
        a,
        b = ring[n - 1],
        area = 0;

    while (++i < n) {
      a = b;
      b = ring[i];
      area += a[0] * b[1] - a[1] * b[0];
    }

    return area / 2;
  }

  function merge(topology) {
    return object(topology, mergeArcs.apply(this, arguments));
  }

  function mergeArcs(topology, objects) {
    var polygonsByArc = {},
        polygons = [],
        components = [];

    objects.forEach(function(o) {
      if (o.type === "Polygon") register(o.arcs);
      else if (o.type === "MultiPolygon") o.arcs.forEach(register);
    });

    function register(polygon) {
      polygon.forEach(function(ring$$) {
        ring$$.forEach(function(arc) {
          (polygonsByArc[arc = arc < 0 ? ~arc : arc] || (polygonsByArc[arc] = [])).push(polygon);
        });
      });
      polygons.push(polygon);
    }

    function area(ring$$) {
      return Math.abs(ring(object(topology, {type: "Polygon", arcs: [ring$$]}).coordinates[0]));
    }

    polygons.forEach(function(polygon) {
      if (!polygon._) {
        var component = [],
            neighbors = [polygon];
        polygon._ = 1;
        components.push(component);
        while (polygon = neighbors.pop()) {
          component.push(polygon);
          polygon.forEach(function(ring$$) {
            ring$$.forEach(function(arc) {
              polygonsByArc[arc < 0 ? ~arc : arc].forEach(function(polygon) {
                if (!polygon._) {
                  polygon._ = 1;
                  neighbors.push(polygon);
                }
              });
            });
          });
        }
      }
    });

    polygons.forEach(function(polygon) {
      delete polygon._;
    });

    return {
      type: "MultiPolygon",
      arcs: components.map(function(polygons) {
        var arcs = [], n;

        // Extract the exterior (unique) arcs.
        polygons.forEach(function(polygon) {
          polygon.forEach(function(ring$$) {
            ring$$.forEach(function(arc) {
              if (polygonsByArc[arc < 0 ? ~arc : arc].length < 2) {
                arcs.push(arc);
              }
            });
          });
        });

        // Stitch the arcs into one or more rings.
        arcs = stitchArcs(topology, arcs);

        // If more than one ring is returned,
        // at most one of these rings can be the exterior;
        // choose the one with the greatest absolute area.
        if ((n = arcs.length) > 1) {
          for (var i = 1, k = area(arcs[0]), ki, t; i < n; ++i) {
            if ((ki = area(arcs[i])) > k) {
              t = arcs[0], arcs[0] = arcs[i], arcs[i] = t, k = ki;
            }
          }
        }

        return arcs;
      })
    };
  }

  function neighbors(objects) {
    var indexesByArc = {}, // arc index -> array of object indexes
        neighbors = objects.map(function() { return []; });

    function line(arcs, i) {
      arcs.forEach(function(a) {
        if (a < 0) a = ~a;
        var o = indexesByArc[a];
        if (o) o.push(i);
        else indexesByArc[a] = [i];
      });
    }

    function polygon(arcs, i) {
      arcs.forEach(function(arc) { line(arc, i); });
    }

    function geometry(o, i) {
      if (o.type === "GeometryCollection") o.geometries.forEach(function(o) { geometry(o, i); });
      else if (o.type in geometryType) geometryType[o.type](o.arcs, i);
    }

    var geometryType = {
      LineString: line,
      MultiLineString: polygon,
      Polygon: polygon,
      MultiPolygon: function(arcs, i) { arcs.forEach(function(arc) { polygon(arc, i); }); }
    };

    objects.forEach(geometry);

    for (var i in indexesByArc) {
      for (var indexes = indexesByArc[i], m = indexes.length, j = 0; j < m; ++j) {
        for (var k = j + 1; k < m; ++k) {
          var ij = indexes[j], ik = indexes[k], n;
          if ((n = neighbors[ij])[i = bisect(n, ik)] !== ik) n.splice(i, 0, ik);
          if ((n = neighbors[ik])[i = bisect(n, ij)] !== ij) n.splice(i, 0, ij);
        }
      }
    }

    return neighbors;
  }

  function compareArea(a, b) {
    return a[1][2] - b[1][2];
  }

  function minAreaHeap() {
    var heap = {},
        array = [],
        size = 0;

    heap.push = function(object) {
      up(array[object._ = size] = object, size++);
      return size;
    };

    heap.pop = function() {
      if (size <= 0) return;
      var removed = array[0], object;
      if (--size > 0) object = array[size], down(array[object._ = 0] = object, 0);
      return removed;
    };

    heap.remove = function(removed) {
      var i = removed._, object;
      if (array[i] !== removed) return; // invalid request
      if (i !== --size) object = array[size], (compareArea(object, removed) < 0 ? up : down)(array[object._ = i] = object, i);
      return i;
    };

    function up(object, i) {
      while (i > 0) {
        var j = ((i + 1) >> 1) - 1,
            parent = array[j];
        if (compareArea(object, parent) >= 0) break;
        array[parent._ = i] = parent;
        array[object._ = i = j] = object;
      }
    }

    function down(object, i) {
      while (true) {
        var r = (i + 1) << 1,
            l = r - 1,
            j = i,
            child = array[j];
        if (l < size && compareArea(array[l], child) < 0) child = array[j = l];
        if (r < size && compareArea(array[r], child) < 0) child = array[j = r];
        if (j === i) break;
        array[child._ = i] = child;
        array[object._ = i = j] = object;
      }
    }

    return heap;
  }

  function presimplify(topology, triangleArea) {
    var absolute = transformAbsolute(topology.transform),
        relative = transformRelative(topology.transform),
        heap = minAreaHeap();

    if (!triangleArea) triangleArea = cartesianTriangleArea;

    topology.arcs.forEach(function(arc) {
      var triangles = [],
          maxArea = 0,
          triangle,
          i,
          n,
          p;

      // To store each point’s effective area, we create a new array rather than
      // extending the passed-in point to workaround a Chrome/V8 bug (getting
      // stuck in smi mode). For midpoints, the initial effective area of
      // Infinity will be computed in the next step.
      for (i = 0, n = arc.length; i < n; ++i) {
        p = arc[i];
        absolute(arc[i] = [p[0], p[1], Infinity], i);
      }

      for (i = 1, n = arc.length - 1; i < n; ++i) {
        triangle = arc.slice(i - 1, i + 2);
        triangle[1][2] = triangleArea(triangle);
        triangles.push(triangle);
        heap.push(triangle);
      }

      for (i = 0, n = triangles.length; i < n; ++i) {
        triangle = triangles[i];
        triangle.previous = triangles[i - 1];
        triangle.next = triangles[i + 1];
      }

      while (triangle = heap.pop()) {
        var previous = triangle.previous,
            next = triangle.next;

        // If the area of the current point is less than that of the previous point
        // to be eliminated, use the latter's area instead. This ensures that the
        // current point cannot be eliminated without eliminating previously-
        // eliminated points.
        if (triangle[1][2] < maxArea) triangle[1][2] = maxArea;
        else maxArea = triangle[1][2];

        if (previous) {
          previous.next = next;
          previous[2] = triangle[2];
          update(previous);
        }

        if (next) {
          next.previous = previous;
          next[0] = triangle[0];
          update(next);
        }
      }

      arc.forEach(relative);
    });

    function update(triangle) {
      heap.remove(triangle);
      triangle[1][2] = triangleArea(triangle);
      heap.push(triangle);
    }

    return topology;
  }

  var version = "1.6.26";

  exports.version = version;
  exports.mesh = mesh;
  exports.meshArcs = meshArcs;
  exports.merge = merge;
  exports.mergeArcs = mergeArcs;
  exports.feature = feature;
  exports.neighbors = neighbors;
  exports.presimplify = presimplify;

}));
},{}],11:[function(require,module,exports){
/*eslint-disable no-cond-assign */
module.exports = parse;
module.exports.parse = parse;
module.exports.stringify = stringify;

var numberRegexp = /[-+]?([0-9]*\.[0-9]+|[0-9]+)([eE][-+]?[0-9]+)?/;
// Matches sequences like '100 100' or '100 100 100'.
var tuples = new RegExp('^' + numberRegexp.source + '(\\s' + numberRegexp.source + '){1,}');

/*
 * Parse WKT and return GeoJSON.
 *
 * @param {string} _ A WKT geometry
 * @return {?Object} A GeoJSON geometry object
 */
function parse (input) {
  var parts = input.split(';');
  var _ = parts.pop();
  var srid = (parts.shift() || '').split('=').pop();

  var i = 0;

  function $ (re) {
    var match = _.substring(i).match(re);
    if (!match) return null;
    else {
      i += match[0].length;
      return match[0];
    }
  }

  function crs (obj) {
    if (obj && srid.match(/\d+/)) {
      obj.crs = {
        type: 'name',
        properties: {
          name: 'urn:ogc:def:crs:EPSG::' + srid
        }
      };
    }

    return obj;
  }

  function white () { $(/^\s*/); }

  function multicoords () {
    white();
    var depth = 0;
    var rings = [];
    var stack = [rings];
    var pointer = rings;
    var elem;

    while (elem =
           $(/^(\()/) ||
             $(/^(\))/) ||
               $(/^(\,)/) ||
                 $(tuples)) {
      if (elem === '(') {
        stack.push(pointer);
        pointer = [];
        stack[stack.length - 1].push(pointer);
        depth++;
      } else if (elem === ')') {
        // For the case: Polygon(), ...
        if (pointer.length === 0) return null;

        pointer = stack.pop();
        // the stack was empty, input was malformed
        if (!pointer) return null;
        depth--;
        if (depth === 0) break;
      } else if (elem === ',') {
        pointer = [];
        stack[stack.length - 1].push(pointer);
      } else if (!elem.split(/\s/g).some(isNaN)) {
        Array.prototype.push.apply(pointer, elem.split(/\s/g).map(parseFloat));
      } else {
        return null;
      }
      white();
    }

    if (depth !== 0) return null;

    return rings;
  }

  function coords () {
    var list = [];
    var item;
    var pt;
    while (pt =
           $(tuples) ||
             $(/^(\,)/)) {
      if (pt === ',') {
        list.push(item);
        item = [];
      } else if (!pt.split(/\s/g).some(isNaN)) {
        if (!item) item = [];
        Array.prototype.push.apply(item, pt.split(/\s/g).map(parseFloat));
      }
      white();
    }

    if (item) list.push(item);
    else return null;

    return list.length ? list : null;
  }

  function point () {
    if (!$(/^(point)/i)) return null;
    white();
    if (!$(/^(\()/)) return null;
    var c = coords();
    if (!c) return null;
    white();
    if (!$(/^(\))/)) return null;
    return {
      type: 'Point',
      coordinates: c[0]
    };
  }

  function multipoint () {
    if (!$(/^(multipoint)/i)) return null;
    white();
    var newCoordsFormat = _
      .substring(_.indexOf('(') + 1, _.length - 1)
      .replace(/\(/g, '')
      .replace(/\)/g, '');
    _ = 'MULTIPOINT (' + newCoordsFormat + ')';
    var c = multicoords();
    if (!c) return null;
    white();
    return {
      type: 'MultiPoint',
      coordinates: c
    };
  }

  function multilinestring () {
    if (!$(/^(multilinestring)/i)) return null;
    white();
    var c = multicoords();
    if (!c) return null;
    white();
    return {
      type: 'MultiLineString',
      coordinates: c
    };
  }

  function linestring () {
    if (!$(/^(linestring)/i)) return null;
    white();
    if (!$(/^(\()/)) return null;
    var c = coords();
    if (!c) return null;
    if (!$(/^(\))/)) return null;
    return {
      type: 'LineString',
      coordinates: c
    };
  }

  function polygon () {
    if (!$(/^(polygon)/i)) return null;
    white();
    var c = multicoords();
    if (!c) return null;
    return {
      type: 'Polygon',
      coordinates: c
    };
  }

  function multipolygon () {
    if (!$(/^(multipolygon)/i)) return null;
    white();
    var c = multicoords();
    if (!c) return null;
    return {
      type: 'MultiPolygon',
      coordinates: c
    };
  }

  function geometrycollection () {
    var geometries = [];
    var geometry;

    if (!$(/^(geometrycollection)/i)) return null;
    white();

    if (!$(/^(\()/)) return null;
    while (geometry = root()) {
      geometries.push(geometry);
      white();
      $(/^(\,)/);
      white();
    }
    if (!$(/^(\))/)) return null;

    return {
      type: 'GeometryCollection',
      geometries: geometries
    };
  }

  function root () {
    return point() ||
      linestring() ||
      polygon() ||
      multipoint() ||
      multilinestring() ||
      multipolygon() ||
      geometrycollection();
  }

  return crs(root());
}

/**
 * Stringifies a GeoJSON object into WKT
 */
function stringify (gj) {
  if (gj.type === 'Feature') {
    gj = gj.geometry;
  }

  function pairWKT (c) {
    return c.join(' ');
  }

  function ringWKT (r) {
    return r.map(pairWKT).join(', ');
  }

  function ringsWKT (r) {
    return r.map(ringWKT).map(wrapParens).join(', ');
  }

  function multiRingsWKT (r) {
    return r.map(ringsWKT).map(wrapParens).join(', ');
  }

  function wrapParens (s) { return '(' + s + ')'; }

  switch (gj.type) {
    case 'Point':
      return 'POINT (' + pairWKT(gj.coordinates) + ')';
    case 'LineString':
      return 'LINESTRING (' + ringWKT(gj.coordinates) + ')';
    case 'Polygon':
      return 'POLYGON (' + ringsWKT(gj.coordinates) + ')';
    case 'MultiPoint':
      return 'MULTIPOINT (' + ringWKT(gj.coordinates) + ')';
    case 'MultiPolygon':
      return 'MULTIPOLYGON (' + multiRingsWKT(gj.coordinates) + ')';
    case 'MultiLineString':
      return 'MULTILINESTRING (' + ringsWKT(gj.coordinates) + ')';
    case 'GeometryCollection':
      return 'GEOMETRYCOLLECTION (' + gj.geometries.map(stringify).join(', ') + ')';
    default:
      throw new Error('stringify requires a valid GeoJSON Feature or geometry object as input');
  }
}

},{}]},{},[1])(1)
});
