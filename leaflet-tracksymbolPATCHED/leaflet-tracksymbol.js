/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2015 Tim Leerhoff <tleerhof@web.de>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */


/**
 * Tracksymbol for leaflet.
 * The visualization is chosen by zoomlevel or heading availability.
 * If zoomlevel is smaller than 'minSilouetteZoom' a triangular symbol is rendered.
 * If zoomlevel is greater than 'minSilouetteZoom' a ship silouette is rendered.
 * If heading is undefined a diamond symbol is rendered.
 * The following options are available:
 * <ul>
 *   <li>trackId: The unique id of the symbol (default: 0). </li>
 *   <li>size: Static size of the symbol in pixels (default:24). </li>
 *   <li>heading: Initial heading of the symbol (default: undefined). </li>
 *   <li>course: Initial course of the symbol (default: undefined). </li>
 *   <li>speed: Initial speed of the symbol-leader (default: undefined). </li>
 *   <li>leaderTime: The length of the leader (speed * leaderTime) (default:60s). </li>
 *   <li>minSilouetteZoom: The zoomlevel to switch from triangle to silouette (default:14). </li>
 *   <li>gpsRefPos: Initial GPS offset of the symbol (default: undefined). </li>
 *   <li>defaultSymbol: The triangular track symbol. Contains an array of n numbers. [x1,y1,x2,y2,...] </li>
 *   <li>noHeadingSymbol: The diamond track symbol. Contains an array of n numbers. [x1,y1,x2,y2,...] </li>
 *   <li>silouetteSymbol: The ship track symbol. Contains an array of n numbers. [x1,y1,x2,y2,...] </li>
 * </ul>
 * @class TrackSymbol
 * @constructor
 * @param latlng {LanLng} The initial position of the symbol.
 * @param options {Object} The initial options described above.
 */

// определение имени файла этого скрипта
var scripts = document.getElementsByTagName('script');
var index = scripts.length - 1; 	// это так, потому что эта часть сработает при загрузке скрипта, и он в этот момент - последний http://feather.elektrum.org/book/src.html
var thisScript = scripts[index];
//console.log(thisScript);

L.TrackSymbol = L.Path.extend({

  initialize: function (latlng, options) {
    L.setOptions(this, options);
    if(latlng === undefined) {
      throw Error('Please give a valid lat/lon-position');
    }
    options = options || {};
    this._id = options.trackId || 0;
    this._leaflet_id = this._id; 
    this._latlng = L.latLng(latlng);
    this._size = options.size || 24;
    this._heading = options.heading;
    this._course = options.course;
    this._speed = options.speed;
    this._leaderTime = options.leaderTime || 60.0;
    this._minSilouetteZoom = options.minSilouetteZoom || 15;
    this.setGPSRefPos(options.gpsRefPos);
    //this._triSymbol = options.defaultSymbol || [0.75,0, -0.25,0.3, -0.25,-0.3];
    this._triSymbol = options.defaultSymbol || [0.8,0, -0.3,0.35, -0.3,-0.35];
    //this._diaSymbol = options.noHeadingSymbol || [0.3,0, 0,0.3, -0.3,0, 0,-0.3];
    this._diaSymbol = options.noHeadingSymbol || [0.35,0, 0,0.35, -0.35,0, 0,-0.35];
    //this._silSymbol = options.silouetteSymbol || [1,0.5, 0.75,1, 0,1, 0,0, 0.75,0];
    this._silSymbol = options.silouetteSymbol || [1.05,0.5, 0.8,1, 0,1, 0,0, 0.8,0];
    this.bindPopup("",{className: "vehiclePopup"}); 	// приклеим popup с указанным стилем
  },

  /**
   * This function is empty but necessary 
   * because it is called during the rendering process of Leaflet v1.0.
   * @method _project
   */
  _project: function(){
  },

  /**
   * Update the path
   * This function is called during the rendering process of leaflet v1.0
   * @method _update
   */
  _update: function(){
    this._setPath();
  },

  /**
   * Sets the contents of the d-attribute in a path-element of an svg-file.  
   * @method _setPath
   */
  _setPath: function(){
  	let pathString = this.getPathString();
  	let center = this._map.latLngToLayerPoint(this._latlng);
  	//console.log("status=",this.options.status);
  	switch(+this.options.status){
  	case 1:
  	case 5:
  	case 6:
	  	pathString += ` M ${center.x-4} ${center.y-4} L ${center.x+4} ${center.y+4} 
M ${center.x+4} ${center.y-4} L ${center.x-4} ${center.y+4} 
M ${center.x} ${center.y-4} L ${center.x} ${center.y+4} 
M ${center.x-4} ${center.y} L ${center.x+4} ${center.y} `;
  	};
  	//console.log('[_setPath] center=',center,'pathString=',pathString);
    this._path.setAttribute('d',pathString);
  },

	addData: function(aisData){ 	// aisData опрелён во внешней функции, типа - глобален

		this._speed = aisData.speed;
		delete aisData.speed;
		//console.log('aisData.heading=',aisData.heading,'aisData.course=',aisData.course);
		this._course = aisData.course * Math.PI/180;
		delete aisData.course;
		this._heading = aisData.heading * Math.PI/180;
		delete aisData.heading;
		if(!this._heading && this._course) this._heading = this._course;
		else if(!this._course && this._heading) this._course = this._heading;
		if(aisData.to_bow && aisData.to_stern && aisData.to_port && aisData.to_starboard) {
			this.options.gpsRefPos = [aisData.to_bow, aisData.to_stern, aisData.to_port, aisData.to_starboard];
			this._gpsRefPos = this.options.gpsRefPos;
			delete aisData.to_bow;
			delete aisData.to_stern;
			delete aisData.to_port;
			delete aisData.to_starboard;
		}
		else if(aisData.length) {
			this.options.gpsRefPos = [2*aisData.length/3, aisData.length/3, aisData.length/16, aisData.length/16];
			this._gpsRefPos = this.options.gpsRefPos;
		}
		this._shiptype = aisData.shiptype;
		delete aisData.shiptype;
		this._setColorsByTypeOfShip();
		if(((aisData.lat !== undefined) && (aisData.lat !== null)) && ((aisData.lon !== undefined) && (aisData.lon !== null))) {
		//if(aisData.lat && aisData.lon) {
		    var oldLatLng = this._latlng;
			this._latlng = L.latLng(aisData.lat,aisData.lon);
		    this.fire('move', {oldLatLng: oldLatLng, latlng: this._latlng});
			delete aisData.lat;
			delete aisData.lon;
		}
	    L.setOptions(this, aisData); 	// остальное запишем в options
		//console.log(this.options);
		//console.log(this.options.mmsi);
		//console.log(this._shiptype);

       	//if(this.options.mmsi==244770791) console.log(this.options);
       	let speedKMH='';
       	if(this._speed) speedKMH = Math.round((this._speed*60*60/1000)*10)/10+' Km/h';
      
		let iconName;
		switch(+aisData['status']) {
		case 0: 	// under way using engine
			iconName = 'boat.png';
			break;
		case 1: 	// at anchor
			iconName = 'anchorage.png';
			break;
		case 2: 	// not under command
			iconName = 'wine.png';
			break;
		case 3: 	// restricted maneuverability
			iconName = 'shore.png';
			break;
		case 4: 	// constrained by her draught
			iconName = 'shallow.png';
			break;
		case 5: 	// moored
			iconName = 'pier.png';
			break;
		case 6: 	// aground
			iconName = 'shipwreck.png';
			break;
		case 7: 	// engaged in fishing
			iconName = 'fishing.png';
			break;
		case 8: 	// under way sailing
			iconName = 'sailing.png';
			break;
		case 11: 	// power-driven vessel towing astern (regional use)
			iconName = 'waterskiing.png';
			break;
		case 12: 	// power-driven vessel pushing ahead or towing alongside (regional use)
			iconName = 'waterskiing.png';
			break;
		case 14:	// AIS-SART (active), MOB-AIS, EPIRB-AIS
			//console.log("this.options.safety_related_text=",this.options.safety_related_text);
			const cautions = ['wreck','sinking','fire','criminal','pirate','robbery','aid','medical'];
			let words = [];
			if(this.options.safety_related_text) words = this.options.safety_related_text.toLowerCase().split(/[^a-zA-Z]+/).filter(word => word.length > 0);	// https://dev.to/kamonwan/the-right-way-to-break-string-into-words-in-javascript-3jp6
			words = new Set(words);
			const caution = [...cautions].filter(word => words.has(word));
			//console.log('[addData] :',caution);
			switch(caution[0]){
			case 'wreck': 
			case 'sinking': 
				iconName = 'shipwreck_danger.png';
				break;
			case 'fire': 
				iconName = 'fire.png';
				break;
			case 'criminal': 
			case 'pirate': 
			case 'robbery': 
				iconName = 'robbery.png';
				break;
			case 'aid': 
			case 'medical': 
				iconName = 'medical.png';
				break;
			default:
				iconName = 'caution.png';
			}
			break;
		default: 	// undefined = default
			iconName = '';
			break;
		}
		//console.log("aisData['status']=",aisData['status'],thisScript.src.substring(0, thisScript.src.lastIndexOf("/"))+"/symbols/"+iconName);
		//console.log("aisData['safety_related_text']=",aisData['safety_related_text']);
		let iconIMG='';
		if(iconName) iconIMG = '<img width="24px" style="float:right;margin:0.1rem;" src="'+(thisScript.src.substring(0, thisScript.src.lastIndexOf("/"))+"/symbols/"+iconName)+'">';
		let statusText;
		if(!aisData.status_text) statusText = AISstatusTXT[aisData.status];	// internationalisation
		else statusText = aisData.status_text.trim();
		let shiptype_text;
		if(!aisData.shiptype_text) shiptype_text = AISshipTypeTXT[this._shiptype];	// internationalisation
		else shiptype_text = aisData.shiptype_text.trim();
		
		let dataStamp = '';
		if(this.options.timestamp){
			const d = new Date(this.options.timestamp*1000);
			dataStamp = d.getHours()+':'+(d.getMinutes()<10?'0'+d.getMinutes():d.getMinutes());
			//dataStamp = d.getHours()+':'+d.getMinutes();
		}

		let PopupContent = `
<div>
	${iconIMG}
	<span style='font-size:120%';'>${this.options.shipname||''}</span><br>
	<div style='width:100%;'>
	${this.options.mmsi} <span style='float:right;'>${this.options.callsign||''}</span>
	</div>
	<div style="text-align: left;">
		${shiptype_text||''}
	</div>
	<div style='width:100%;background-color:lavender;'>
		<span style='font-size:110%;'>${this.options.safety_related_text||''} ${statusText||''}</span><br>
	</div>
	<div style='width:100%;'>
		<div style='width:40%;float:right;text-align:right;'>${speedKMH}</div>
		<span >${this.options.destination||''}</span>
	</div>
${this.options.hazard_text||''} ${this.options.loaded_text||''}<br>`;
		if(this.options.mmsi && this.options.mmsi.substring(0,2)!=='97') PopupContent += `<span style='float:right;'>This on <a href='http://www.marinetraffic.com/ais/details/ships/mmsi:${this.options.mmsi}' target='_blank'>MarineTraffic.com</a></span>`;	// это не AIS-SART
		PopupContent += `<span>${dataStamp}</span>
</div>
		`;
        if(this.getPopup()){
            this.getPopup().setContent(PopupContent);
        }
        else console.log('Нет POPUP!')
        
		if(this.options.sart){	// AIS-SART, но не обязательно mmsi==97XXXXXXX
	        if(!this.getTooltip()){
				// Идея показать картинку вместо Tooltip с целью имитации маркера, который здесь 
				// ну совсем лишний, реализовалась, как обычно, через жопу. Иначе никак.
				this.bindTooltip('',{
					permanent:true,
					direction:"top",
					opacity:1,
					className:"sartTooltip"
				}); 	// приклеим tooltip. .closeTooltip() не надо?
			};
			const TooltipContent = '<img width="24px" style="margin:0; position:relative; bottom:24px; right:12px; opacity:1 !important;" src="'+thisScript.src.substring(0, thisScript.src.lastIndexOf("/"))+'/symbols/'+iconName+'">';
			this.getTooltip().setContent(TooltipContent); 	// .openTooltip() не надо?
		};
		
		//console.log('[addData] result this:',this)
		return this.redraw(); 	// 
	},

  /**
   * Set the default symbol.
   * @method setDefaultSymbol
   * @param symbol {Array} The corner points of the symbol. 
   */
  setDefaultSymbol: function (symbol) {
    this._triSymbol = symbol;
    return this.redraw();
  },

  /**
   * Set the symbol for tracks with no heading.
   * @method setNoHeadingSymbol
   * @param symbol {Array} The corner points of the symbol. 
   */
  setNoHeadingSymbol: function (symbol) {
    this._diaSymbol = symbol;
    return this.redraw();
  },

  /**
   * Set the symbol for tracks with shown silouette.
   * @method setSilouetteSymbol
   * @param symbol {Array} The corner points of the symbol. 
   */
  setSilouetteSymbol: function (symbol) {
    this._silSymbol = symbol;
    return this.redraw();
  },
  
  /**
   * Set latitude/longitude on the symbol.
   * @method setLatLng
   * @param latlng {LatLng} Position of the symbol on the map. 
   */
  setLatLng: function (latlng) {
    var oldLatLng = this._latlng;
    this._latlng = L.latLng(latlng);
    this.fire('move', {oldLatLng: oldLatLng, latlng: this._latlng});
    return this.redraw();
  },
  
  /**
   * Set the speed shown in the symbol [m/s].
   * The leader-length is calculated via leaderTime.
   * @method setSpeed
   * @param speed {Number} The speed in [m/s]. 
   */
  setSpeed: function( speed ) {
    this._speed = speed;
    return this.redraw();
  },
  
  
  /**
   * Sets the leaderTime of the symbol [seconds].
   * @method setLeaderTime
   * @param leaderTime {Number} The leaderTime in seconds.
   */
  setLeaderTime: function( leaderTime ) {
    this._leaderTime = leaderTime;
    return this.redraw();
  },

  /**
   * Sets the position offset of the silouette to the center of the symbol.
   * The array contains the refpoints from ITU R-REC-M.1371-4-201004 page 108
   * in sequence A,B,C,D.
   * @method setGPSRefPos
   * @param gpsRefPos {Array} The GPS offset from center.
   */
  setGPSRefPos: function(gpsRefPos) {
    if(gpsRefPos === undefined || 
       gpsRefPos.length < 4) {
      this._gpsRefPos = undefined;
    }
    else if(gpsRefPos[0] === 0 && 
            gpsRefPos[1] === 0 && 
            gpsRefPos[2] === 0 && 
            gpsRefPos[3] === 0) {
      this._gpsRefPos = undefined;
    }
    else {
      this._gpsRefPos = gpsRefPos;
    }
    return this.redraw();
  },

  /**
   * Returns the trackId.
   * @method getTrackId
   * @return {Number} The track id.
   */
  getTrackId: function() {
    return this._Id;
  },
    
  _getLatSize: function () {
    return this._getLatSizeOf(this._size);
  },

  _getLngSize: function () {
    return this._getLngSizeOf(this._size);
  },
  
  _getLatSizeOf: function (value) {
    return (value / 40075017) * 360;
  },

  _getLngSizeOf: function (value) {
    return ((value / 40075017) * 360) / Math.cos((Math.PI/180) * this._latlng.lat);
  },

  /**
   * Returns the bounding box of the symbol.
   * @method getBounds
   * @return {LatLngBounds} The bounding box.
   */
  getBounds: function () {
     var lngSize = this._getLngSize() / 2.0;
     var latSize = this._getLatSize() / 2.0;
     var latlng = this._latlng;
     return new L.LatLngBounds(
            [latlng.lat - latSize, latlng.lng - lngSize],
            [latlng.lat + latSize, latlng.lng + lngSize]);
  },

  /**
   * Returns the position of the symbol on the map.
   * @mathod getLatLng
   * @return {LatLng} The position object.
   */
  getLatLng: function () {
    return this._latlng;
  },

  //
  // Rotates the given point around the angle.
  // @method _rotate
  // @param point {Array} A point vector 2d.
  // @param angle {Number} Angle for rotation [rad].
  // @return The rotated vector 2d.
  //
  _rotate: function(point, angle) {
    var x = point[0];
    var y = point[1];
    var si_z = Math.sin(angle);
    var co_z = Math.cos(angle);
    var newX = x * co_z - y * si_z;
    var newY = x * si_z + y * co_z;
    return [newX, newY];
  },

  //
  // Rotates the given point-array around the angle.
  // @method _rotateAllPoints
  // @param points {Array} A point vector 2d.
  // @param angle {Number} Angle for rotation [rad].
  // @return The rotated vector-array 2d.
  //
  _rotateAllPoints: function(points, angle) {
    var result = [];
    for(var i=0;i<points.length;i+=2) {
      var x = points[i + 0] * this._size;
      var y = points[i + 1] * this._size;
      var pt = this._rotate([x, y], angle);
      result.push(pt[0]);
      result.push(pt[1]);
    }
    return result;
  },

  _createLeaderViewPoints: function(angle) {
    var result = [];
    var leaderLength = this._speed * this._leaderTime;
    var leaderEndLng = this._latlng.lng + this._getLngSizeOf(leaderLength * Math.cos(angle));
    var leaderEndLat = this._latlng.lat + this._getLatSizeOf(leaderLength * Math.sin(angle));
    var endPoint = this._map.latLngToLayerPoint(L.latLng([leaderEndLat, leaderEndLng]));
    var startPoint = this._map.latLngToLayerPoint(this._latlng);
    return [startPoint.x, startPoint.y, endPoint.x, endPoint.y];
  },

  _transformAllPointsToView: function(points) {
    var result = [];
    var symbolViewCenter = this._map.latLngToLayerPoint(this._latlng);
    for(var i=0;i<points.length;i+=2) {
      var x = symbolViewCenter.x + points[i+0];
      var y = symbolViewCenter.y - points[i+1];
      result.push(x);
      result.push(y);
    }
    return result;
  },

  _createPathFromPoints: function(points) {
    var result;
    for(var i=0;i<points.length;i+=2) {
      var x = points[i+0];
      var y = points[i+1];
      if(result === undefined)
        result = 'M ' + x + ' ' + y + ' ';
      else
        result += 'L ' + x + ' ' + y + ' ';
    }
    return result + ' Z';
  },

  _getViewAngleFromModel:  function(modelAngle) {
    return Math.PI/2.0 - modelAngle;
  },

  _createNoHeadingSymbolPathString: function() {
    var viewPoints = this._transformAllPointsToView( this._rotateAllPoints(this._diaSymbol, 0.0) );
    var viewPath = this._createPathFromPoints(viewPoints);
    if( this._course && this._speed ) {
      var courseAngle = this._getViewAngleFromModel(this._course);
      var leaderPoints = this._createLeaderViewPoints(courseAngle);
      viewPath += '' + this._createPathFromPoints(leaderPoints);
    }
    return viewPath;
  },

  _createWithHeadingSymbolPathString: function() {
    var headingAngle = this._getViewAngleFromModel(this._heading);
    var viewPoints = this._transformAllPointsToView( this._rotateAllPoints(this._triSymbol, headingAngle) );
    var viewPath = this._createPathFromPoints(viewPoints);
    if( this._course && this._speed ) {
      var courseAngle = this._getViewAngleFromModel(this._course);
      var leaderPoints = this._createLeaderViewPoints(courseAngle);
      viewPath += '' + this._createPathFromPoints(leaderPoints);
    }
    return viewPath;
  },

  _resizeAndMovePoint: function(point, size, offset) {
    return [
      point[0] * size[0] + offset[0], 
      point[1] * size[1] + offset[1]
    ];
  },

  _getSizeFromGPSRefPos: function() {
    return [
      this._gpsRefPos[0] + this._gpsRefPos[1],
      this._gpsRefPos[2] + this._gpsRefPos[3]
    ];
  },

  _getOffsetFromGPSRefPos: function() {
    return [
      -this._gpsRefPos[1], 
      -this._gpsRefPos[3]
    ];
  },

  _transformSilouetteSymbol: function() {
    var headingAngle = this._getViewAngleFromModel(this._heading);
    var result = [];
    var size = this._getSizeFromGPSRefPos();
    var offset = this._getOffsetFromGPSRefPos();
    for(var i=0;i<this._silSymbol.length;i+=2) {
      var pt = [
        this._silSymbol[i+0], 
        this._silSymbol[i+1]
      ];
      //if(this.options.mmsi==235084466) console.log(pt);
      pt = this._resizeAndMovePoint(pt, size, offset);
      pt = this._rotate(pt, headingAngle);
      var pointLng = this._latlng.lng + this._getLngSizeOf(pt[0]);
      var pointLat = this._latlng.lat + this._getLatSizeOf(pt[1]);
      var viewPoint = this._map.latLngToLayerPoint(L.latLng([pointLat, pointLng]));
      result.push(viewPoint.x);
      result.push(viewPoint.y);
    }
    return result;
  },

  _createSilouetteSymbolPathString: function() {
    var silouettePoints = this._transformSilouetteSymbol();
    var viewPath = this._createPathFromPoints(silouettePoints);
    if( this._course && this._speed ) {
      var courseAngle = this._getViewAngleFromModel(this._course);
      var leaderPoints = this._createLeaderViewPoints(courseAngle);
      viewPath += '' + this._createPathFromPoints(leaderPoints);
    }
    return viewPath;
  },

  /**
   * Generates the symbol as SVG path string.
   * depending on zoomlevel or track heading different symbol types are generated.
   * @return {String} The symbol path string.
   */
  getPathString: function () {
    if(!this._heading) {
      return this._createNoHeadingSymbolPathString();
    }
    else {
      if(this._gpsRefPos === undefined || this._map.getZoom() <= this._minSilouetteZoom ) {
        return this._createWithHeadingSymbolPathString();
      }
      else {
        return this._createSilouetteSymbolPathString();
      }
    }
  },
  
	/**
	 *
	 * @private
	 */
	_setColorsByTypeOfShip: function(){
		//console.log('setColorsByTypeOfShip: '+this._shiptype);
		//console.log(this);
		switch (this._shiptype) {
		    case 0: //NOT AVAILABLE OR NO SHIP
		        this.setStyle({color: "#000000"});
		        this.setStyle({fillColor: "#888A85"});
		        break;
		    case 1: //RESERVED               
		    case 2: //RESERVED
		    case 3: //RESERVED
		    case 4: //RESERVED
		    case 5: //RESERVED
		    case 6: //RESERVED
		    case 8: //RESERVED
		    case 9: //RESERVED
		    case 10: //RESERVED
		    case 11: //RESERVED
		    case 12: //RESERVED
		    case 13: //RESERVED
		    case 14: //RESERVED
		    case 15: //RESERVED
		    case 16: //RESERVED
		    case 17: //RESERVED
		    case 18: //RESERVED
		    case 19: //RESERVED
		        this.setStyle({color: "#000000"});
		        this.setStyle({fillColor: "#d3d3d3"});
		        break;
		    case 20: //Wing In Grnd
		    case 21: //Wing In Grnd
		    case 22: //Wing In Grnd
		    case 23: //Wing In Grnd
		    case 24: //Wing In Grnd
		    case 25: //Wing In Grnd
		    case 26: //Wing In Grnd
		    case 27: //Wing In Grnd
		    case 28: //Wing In Grnd
		        this.setStyle({color: "#000000"});
		        this.setStyle({fillColor: "#d3d3d3"});
		        break;
		    case 29: //SAR AIRCRAFT
		        this.setStyle({color: "#000000"});
		        this.setStyle({fillColor: "#d3d3d3"});
		        break;
		    case 30: //Fishing
		        this.setStyle({color: "#800000"});
		        this.setStyle({fillColor: "#ffa07a"});
		        break;
		    case 31: //Tug
		    case 32: //Tug
		        this.setStyle({color: "#008b8b"});
		        this.setStyle({fillColor: "#00ffff"});
		        break;
		    case 33: //Dredger
		        this.setStyle({color: "#008b8b"});
		        this.setStyle({fillColor: "#00ffff"});
		        break;
		    case 34: //Dive Vessel
		        this.setStyle({color: "#008b8b"});
		        this.setStyle({fillColor: "#0A84F8"});
		        break;
		    case 35: //Military Ops
		        this.setStyle({color: "#008b8b"});
		        this.setStyle({fillColor: "#000080"});
		        break;
		    case 36: //Sailing Vessel
		        this.setStyle({color: "#8b008b"});
		        this.setStyle({fillColor: "#ff00ff"});
		        break;
		    case 37: //Pleasure Craft
		        this.setStyle({color: "#8b008b"});
		        this.setStyle({fillColor: "#ff00ff"});
		        break;
		    case 38: //RESERVED
		    case 39: //RESERVED
		        this.setStyle({color: "#008b8b"});
		        this.setStyle({fillColor: "#00ffff"});
		        break;
		    case 40: //High-Speed Craft
		    case 41: //High-Speed Craft
		    case 42: //High-Speed Craft
		    case 43: //High-Speed Craft
		    case 44: //High-Speed Craft
		    case 45: //High-Speed Craft
		    case 46: //High-Speed Craft
		    case 47: //High-Speed Craft
		    case 48: //High-Speed Craft
		    case 49: //High-Speed Craft
		        this.setStyle({color: "#00008b"});
		        this.setStyle({fillColor: "#ffff00"});
		        break;
		    case 50: //Pilot Vessel
		        this.setStyle({color: "#008b8b"});
		        this.setStyle({fillColor: "#CF3A0F"});
		        break;
		    case 51: //SAR
		        this.setStyle({color: "#008b8b"});
		        this.setStyle({fillColor: "#F57900"});
		        break;
		    case 52: //Tug
		        this.setStyle({color: "#008b8b"});
		        this.setStyle({fillColor: "#00ffff"});
		        break;
		    case 53: //Port Tender
		        this.setStyle({color: "#008b8b"});
		        this.setStyle({fillColor: "#00ffff"});
		        break;
		    case 54: //Anti-Pollution
		        this.setStyle({color: "#008b8b"});
		        this.setStyle({fillColor: "#4E9A06"});
		        break;
		    case 55: //Law Enforce
		        this.setStyle({color: "#008b8b"});
		        this.setStyle({fillColor: "#FCAF3E"});
		        break;
		    case 56: //Local Vessel
		    case 57: //Local Vessel
		        this.setStyle({color: "#008b8b"});
		        this.setStyle({fillColor: "#00ffff"});
		        break;
		    case 58: //Medical Trans (as defined in the 1949 Geneva Conventions and Additional Protocols)
		        this.setStyle({color: "#008b8b"});
		        this.setStyle({fillColor: "#00ffff"});
		        break;
		    case 59: //Special Craft
		        this.setStyle({color: "#008b8b"});
		        this.setStyle({fillColor: "#00ffff"});
		        break;
		    case 60: //Passenger
		    case 61: //Passenger
		    case 62: //Passenger
		    case 63: //Passenger
		    case 64: //Passenger
		    case 65: //Passenger
		    case 66: //Passenger
		    case 67: //Passenger
		    case 68: //Passenger
		    case 69: //Passenger
		        this.setStyle({color: "#000000"});
		        this.setStyle({fillColor: "#6D00FF"});
		        //this.setStyle({color: "#00008b"});
		        //this.setStyle({fillColor: "#0000ff"});
		        break;
		    case 70: //Cargo
		        this.setStyle({color: "#006400"});
		        this.setStyle({fillColor: "#90ee90"});
		        break;
		    case 71: //Cargo - Hazard A
		        this.setStyle({color: "#006400"});
		        this.setStyle({fillColor: "#90ee90"});
		        break;
		    case 72: //Cargo - Hazard B
		        this.setStyle({color: "#006400"});
		        this.setStyle({fillColor: "#90ee90"});
		        break;
		    case 73: //Cargo - Hazard C
		        this.setStyle({color: "#006400"});
		        this.setStyle({fillColor: "#90ee90"});
		        break;
		    case 74: //Cargo - Hazard D
		        this.setStyle({color: "#006400"});
		        this.setStyle({fillColor: "#90ee90"});
		        break;
		    case 75: //Cargo
		    case 76: //Cargo
		    case 77: //Cargo
		    case 78: //Cargo
		    case 79: //Cargo
		        this.setStyle({color: "#006400"});
		        this.setStyle({fillColor: "#90ee90"});
		        break;
		    case 80: //Tanker
		        this.setStyle({color: "#8b0000"});
		        this.setStyle({fillColor: "#ff0000"});
		        break;
		    case 81: //Tanker - Hazard A
		        this.setStyle({color: "#8b0000"});
		        this.setStyle({fillColor: "#ff0000"});
		        break;
		    case 82: //Tanker - Hazard B
		        this.setStyle({color: "#8b0000"});
		        this.setStyle({fillColor: "#ff0000"});
		        break;
		    case 83: //Tanker - Hazard C
		        this.setStyle({color: "#8b0000"});
		        this.setStyle({fillColor: "#ff0000"});
		        break;
		    case 84: //Tanker - Hazard D
		        this.setStyle({color: "#8b0000"});
		        this.setStyle({fillColor: "#ff0000"});
		        break;
		    case 85: //Tanker
		    case 86: //Tanker
		    case 87: //Tanker
		    case 88: //Tanker
		    case 89: //Tanker
		        this.setStyle({color: "#8b0000"});
		        this.setStyle({fillColor: "#ff0000"});
		        break;
		    case 90: //Other
		    case 91: //Other
		    case 92: //Other
		    case 93: //Other
		    case 94: //Other
		    case 95: //Other
		    case 96: //Other
		    case 97: //Other
		    case 98: //Other
		    case 99: //Other
		        this.setStyle({color: "#008b8b"});
		        this.setStyle({fillColor: "#00ffff"});
		        break;
		    default: //Default
		        this.setStyle({color: "#00008b"});
		        this.setStyle({fillColor: "#0000ff"});
		}
	},
  
});

/**
 * Factory function to create the symbol.
 * @method trackSymbol
 * @param latlng {LatLng} The position on the map.
 * @param options {Object} Additional options. 
 */
L.trackSymbol = function (latlng, options) {
    return new L.TrackSymbol(latlng, options);
};

