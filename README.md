# GaladrielMap
This is a web tile map viewer to serve in a weak computers such as RaspberryPi or NAS and use on a thick clients such as tablets and smartphones. Author use it from the wi-fi router/GSM modem under OpenWRT on his sailboat Galadriel.

GaladrielMap designed for use mainly with GaladrielCache, but may be used with any tile OSM-like map sources or file tile cache for explore map. The author is not responsible for the consequences of using the GaladrielMap as navigator!

## Features:
1. Stacked maps (with GaladrielCache)
2. Positioning via gpsd
3. Display current (writing now) track file in gpx format
4. Display track/route files in gpx format
5. Control of a GaladrielCache loader

## Screenshots
![s1](screenshots/s1.jpg) ![s2](screenshots/s2.jpg) ![s3](screenshots/s3.jpg) ![s5](screenshots/s4.jpg) ![s6](screenshots/s6.jpg)![s7](screenshots/s7.jpg)

## Install&configure:
You must have a web server with php support. Just copy.

Paths and other set and describe in _params.php_

### gpsd
GaladrielMap get position and velosity from gpsd via _askGPSD.php_ service. You may configure _askGPSD.php_ to you gpsd host and port. Default are localhost and 2947 port (default for gpsd).

How to install and configure gpsd see [gpsd pages](http://www.catb.org/gpsd/).

### tracks
You may use gpxlogger app from gpsd-clients packet to logging on your server. GaladrielMap displaying current not well-formed gpx file. Other tracks may be diplayed simultaneously.

Run _chkGPXfiles.php_ in cli to repair non well-formed gpx files.


