<?php
if(strpos($_SERVER['HTTP_ACCEPT_LANGUAGE'],'ru')===FALSE) { 	// клиент - нерусский
//if(TRUE) { 	// 
	$homeHeaderTXT = 'Maps';
	$dashboardHeaderTXT = 'Velocity&heading';
	$dashboardSpeedMesTXT = 'km/h';
	$dashboardMeterMesTXT = 'meters';
	$dashboardHeadingTXT = 'Heading';
	$dashboardHeadingAltTXT = 'Истинный курс';
	$dashboardPosTXT = 'Position';
	$dashboardPosAltTXT = 'Широта &nbsp; Долгота';
	$dashboardSpeedZoomTXT = 'Velocity vector - distance for';
	$dashboardSpeedZoomMesTXT = 'minutes';

	$tracksHeaderTXT = 'Tracks';
	$loggingTXT = 'Track logging';

	$measureHeaderTXT = 'Handle route';
	$routeControlsBeginTXT = 'Begin';
	$routeControlsContinueTXT = 'Continue';
	$routeControlsClearTXT = 'Erase';
	$goToPositionTXT = 'Flay map to';
	$routeSaveTXT = 'Label';
	$routeSaveTitle = 'Save route to a server';
	$routeSaveDescrTXT = 'Description to route';
	
	$routesHeaderTXT = 'Routes and POI';
	
	$coverTXT = 'Coverage by the zoom';
	$downloadHeaderTXT = 'Download';
	$downloadZoomTXT = 'Zoom';
	$downloadJobListTXT = 'Started downloading';
	$downloadLoaderIndicatorOnTXT = 'Loader runs';
	$downloadLoaderIndicatorOffTXT = 'Loader not runs. Click to run'; 
	
	$settingsHeaderTXT = 'Settings';
	$settingsCursorTXT = 'Follow <br>to cursor';
	$settingsTrackTXT = 'Current track<br>always visible';
	$settingsRoutesAlwaysTXT = 'Selected routes <br>always visible';
	
	$integerTXT = 'Integer';
	$clearTXT = 'Clear';
	$okTXT = 'Create!';
	$latTXT = 'Lat';
	$longTXT = 'Lng';
	$completeTXT = 'complete';
	$copyToClipboardMessageOkTXT = 'Copy to clipboard OK ';
	$copyToClipboardMessageBadTXT = 'Copy to clipboard FAILED ';

	$AISstatusTXT = array(
	0=>'under way using engine',
	1=>'at anchor',
	2=>'not under command',
	3=>'restricted maneuverability',
	4=>'constrained by her draught',
	5=>'moored',
	6=>'aground',
	7=>'engaged in fishing',
	8=>'under way sailing',
	11=>'power-driven vessel towing astern',
	12=>'power-driven vessel pushing ahead or towing alongside'
	);

	$mobTXT = "A man overboard!";
	$addMarkerTXT = "Another marker";
	$bearingTXT = "Bearing";
	$distanceTXT = 'Distance';
	$altDistanceTXT = 'расстояние';
	$altBearingTXT = "Абсолютный азимут";
	$removeMarkerTXT = "Remove marker";
	$cancelMOBTXT = "Terminate";
	$relBearingTXT = "var relBearingTXT = [
	'straight ahead',
	'right ahead',
	'to starboard',	
	'right rear',
	'directly astern',
	'left rear',
	'to port',	
	'left ahead',
	];";
}
else {
	$homeHeaderTXT = 'Карты';
	$dashboardHeaderTXT = 'Скорость и направление';
	$dashboardSpeedMesTXT = 'км/ч';
	$dashboardMeterMesTXT = 'м.';
	$dashboardHeadingTXT = 'Истинный курс';
	$dashboardHeadingAltTXT = 'Heading';
	$dashboardPosTXT = 'Местоположение';
	$dashboardPosAltTXT = 'Latitude &nbsp; Longitude';
	$dashboardSpeedZoomTXT = 'Вектор скорости - расстояние за';
	$dashboardSpeedZoomMesTXT = 'минут';

	$tracksHeaderTXT = 'Треки';
	$loggingTXT = 'Запись пути';

	$measureHeaderTXT = 'Маршрут';
	$routeControlsBeginTXT = 'Начать';
	$routeControlsContinueTXT = 'Продолжить';
	$routeControlsClearTXT = 'Стереть';
	$routeSaveTXT = 'Название';
	$goToPositionTXT = 'Переместить карту в';
	$routeSaveTitle = 'Сохранить маршрут на сервере';
	$routeSaveDescrTXT = 'Описание маршрута';
	
	$routesHeaderTXT = 'Места и маршруты';
	
	$coverTXT = 'Покрытие масштабом';
	$downloadHeaderTXT = 'Загрузки';
	$downloadZoomTXT = 'Масштаб';
	$downloadJobListTXT = 'Поставлены загрузки';
	$downloadLoaderIndicatorOnTXT = 'Загрузчик работает';
	$downloadLoaderIndicatorOffTXT = 'Загрузчик не работает. Нажмите, чтобы запустить';
	
	$settingsHeaderTXT = 'Параметры';
	$settingsCursorTXT = 'Следование <br>за курсором';
	$settingsTrackTXT = 'Текущй трек <br>всегда показывается';
	$settingsRoutesAlwaysTXT = 'Выбранные маршруты <br>всегда показываются';
	
	$integerTXT = 'Целое число';
	$clearTXT = 'Очистить';
	$okTXT = 'Создать!';
	$latTXT = 'Ш';
	$longTXT = 'Д';
	$completeTXT = 'выполнено';
	$copyToClipboardMessageOkTXT = 'Копирование в буфер обмена выполнено ';
	$copyToClipboardMessageBadTXT = 'Копирование в буфер обмена не удалось ';
	
	$AISstatusTXT = array(
	0=>'Двигаюсь под мотором',
	1=>'На якоре',
	2=>'Без экипажа',
	3=>'Ограничен в манёвре',
	4=>'Ограничен осадкой',
	5=>'Ошвартован',
	6=>'На мели',
	7=>'Занят ловлей рыбы',
	8=>'Двигаюсь под парусом',
	11=>'Тяну буксир',
	12=>'Толкаю состав или буксирую под бортом'
	);

	$mobTXT = "Человек за бортом!";
	$addMarkerTXT = "Ещё маркер";
	$bearingTXT = "Абсолютный азимут";
	$altBearingTXT = "bearing";
	$distanceTXT = 'Расстояние';
	$altDistanceTXT = 'distance';
	$removeMarkerTXT = "Убрать маркер";
	$cancelMOBTXT = "Завершить";
	$relBearingTXT = "var relBearingTXT = [
	'прямо по курсу',
	'справа впереди',
	'справа по борту',	
	'справа сзади',
	'сзади по корме',
	'слева сзади',
	'слева по борту',	
	'слева впереди',
	];";
}
?>
