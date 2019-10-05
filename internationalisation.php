<?php
//if(strpos($_SERVER['HTTP_ACCEPT_LANGUAGE'],'ru')===FALSE) { 	// клиент - нерусский
if(TRUE) { 	// клиент - нерусский
	$homeHeaderTXT = 'Maps';
	$dashboardHeaderTXT = 'Velocity&heading';
	$dashboardSpeedMesTXT = 'km/h';
	$dashboardHeadingTXT = 'Heading';
	$dashboardHeadingAltTXT = 'Истинный курс';
	$dashboardPosTXT = 'Position';
	$dashboardPosAltTXT = 'Широта / Долгота';
	$dashboardSpeedZoomTXT = 'Velocity vector - distance for';
	$dashboardSpeedZoomMesTXT = 'minutes';

	$tracksHeaderTXT = 'Tracks';

	$measureHeaderTXT = 'Handle route';
	$routeControlsBeginTXT = 'Begin';
	$routeControlsContinueTXT = 'Continue';
	$routeControlsClearTXT = 'Erase';
	$goToPositionTXT = 'Flay map to';
	$routeSaveTXT = 'Label';
	$routeSaveTitle = 'Save to server';
	$routeSaveDescrTXT = 'Description to route';
	
	$routesHeaderTXT = 'Routes and POI';
	
	$downloadHeaderTXT = 'Download';
	$downloadZoomTXT = 'Zoom';
	$downloadJobListTXT = 'Started downloading';
	
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
}
else {
	$homeHeaderTXT = 'Карты';
	$dashboardHeaderTXT = 'Скорость и направление';
	$dashboardSpeedMesTXT = 'км/ч';
	$dashboardHeadingTXT = 'Истинный курс';
	$dashboardHeadingAltTXT = 'Heading';
	$dashboardPosTXT = 'Местоположение';
	$dashboardPosAltTXT = 'Latitude / Longitude';
	$dashboardSpeedZoomTXT = 'Вектор скорости - расстояние за';
	$dashboardSpeedZoomMesTXT = 'минут';

	$tracksHeaderTXT = 'Треки';

	$measureHeaderTXT = 'Маршрут';
	$routeControlsBeginTXT = 'Начать';
	$routeControlsContinueTXT = 'Продолжить';
	$routeControlsClearTXT = 'Стереть';
	$routeSaveTXT = 'Название';
	$goToPositionTXT = 'Переместить карту в';
	$routeSaveTitle = 'Сохранить на сервере';
	$routeSaveDescrTXT = 'Описание маршрута';
	
	$routesHeaderTXT = 'Места и маршруты';
	
	$downloadHeaderTXT = 'Загрузки';
	$downloadZoomTXT = 'Масштаб';
	$downloadJobListTXT = 'Поставлены загрузки';
	
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
}
?>
