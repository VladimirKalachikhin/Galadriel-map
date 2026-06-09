# L.TileLayer.Mercator

L.TileLayer.Mercator is used for shift tile layer from EPSG3395 to EPSG3857 - for Leaflet_1.2.

Leaflet plugin to draw tile raster layers in Spherical Mercator projection on maps in Elliptical Mercator projection and vice versa.

The plugin defines class:
  * `L.TileLayer.Mercator`

Additional option `tilesCRS` defaults to `L.CRS.EPSG3395`.

These class work similar to corresponding native Leaflet classes, but with tiles in `options.tilesCRS` projection.

Mainly, this plugin should be used to show raster tiled layers on map with CRS `L.CRS.EPSG3395` and vice versa, show tiles in CRS `L.CRS.EPSG3395` on classical Spherical Mercator maps.

## Demos

- [View OSM and Rumap &rarr;](http://scanex.github.io/L.TileLayer.Mercator/index.html)

## Basic Usage

```js
    var rumap = L.tileLayer.Mercator('http://{s}.tile.cart.kosmosnimki.ru/m/{z}/{x}/{y}.png', {
        maxZoom: 19,
        maxNativeZoom: 17,
        attribution: 'RDC ScanEx'
    });
```
