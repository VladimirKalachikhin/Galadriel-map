(function() {
	var Mercator = L.TileLayer.extend({
		options: {
			tilesCRS: L.CRS.EPSG3395
		},
		_getTiledPixelBounds: function (center) {
			var pixelBounds = L.TileLayer.prototype._getTiledPixelBounds.call(this, center);
			this._shiftY = this._getShiftY(this._tileZoom);
			pixelBounds.min.y += this._shiftY;
			pixelBounds.max.y += this._shiftY;
			return pixelBounds;
		},

		_getTilePos: function (coords) {
			var tilePos = L.TileLayer.prototype._getTilePos.call(this, coords);
			return tilePos.subtract([0, this._shiftY]);
		},

		_getShiftY: function(zoom) {
			var map = this._map,
				pos = map.getCenter(),
				shift = (map.options.crs.project(pos).y - this.options.tilesCRS.project(pos).y);

			return Math.floor(L.CRS.scale(zoom) * shift / 40075016.685578496);
		}
	});
	L.TileLayer.Mercator = Mercator;
	L.tileLayer.Mercator = function (url, options) {
		return new Mercator(url, options);
	};
	module.exports = L.tileLayer.Mercator;	
})();
