<?php
// thanks to Mastiff
$homeHeaderTXT = 'Kart';
$showMapsTogglerTXT = "'Alle kart','Valgte kart'";
$dashboardHeaderTXT = 'Fart&kurs';
$dashboardSpeedTXT = 'Fart';
$dashboardSpeedMesTXT = 'km/t';
$dashboardDepthMesTXT = 'Dybde';
$dashboardMeterMesTXT = 'meter';
$dashboardKiloMeterMesTXT = 'km.';
$dashboardCourseTXT = "Beholden kurs";
$dashboardCourseAltTXT = "Course over ground";
$dashboardHeadingTXT = 'Kurs';
$dashboardHeadingAltTXT = 'Heading';
$dashboardMHeadingTXT = "Magnetisk kurs";
$dashboardMHeadingAltTXT = "Magnetic heading";
$dashboardPosTXT = 'Posisjon';
$dashboardPosAltTXT = 'Latitude &nbsp; Longitude';
$dashboardSpeedZoomTXT = 'Hastighetsvektor - distanse over';
$dashboardSpeedZoomMesTXT = 'minutter';

$tracksHeaderTXT = 'Spor';
$loggingTXT = 'Sporlogging';

$measureHeaderTXT = 'Behandle rute';
$routeControlsBeginTXT = 'Begynn';
$routeControlsContinueTXT = 'Fortsett';
$routeControlsClearTXT = 'Slett';
$routePosTXT = "Breddegrad &nbsp; Lengdegrad";
$goToPositionTXT = 'Flytt kart til';
$routeSaveTXT = 'Navn';
$routeSaveTitle = 'Lagre rute til en server';
$routeSaveDescrTXT = 'Lagre beskrivelse til fil';
$editableObjectDescrTXT = 'Objektbeskrivelse';
$followWPTbuttonTXT = 'Følge';
$nofollowWPTbuttonTXT = 'Avbryte';

$routesHeaderTXT = 'Ruter og interessepunkter';

$coverTXT = 'Dekning opp til zoomnivå';
$downloadHeaderTXT = 'Last ned';
$downloadZoomTXT = 'Zoom';
$downloadJobListTXT = 'Nedlasting igangsatt';
$downloadLoaderIndicatorOnTXT = 'Nedlasting i gang';
$downloadLoaderIndicatorOffTXT = 'Nedlasting går ikke - Klikk for å starte'; 

$settingsHeaderTXT = 'Innstillinger';
$settingsCursorTXT = 'Følg båten <br>på kartet';
$settingsTrackTXT = 'Nåværende rute<br>alltid synlig';
$settingsRoutesAlwaysTXT = 'Valgte ruter<br>alltid synlige';
$settingsdistCirclesTXT = 'Vis avstandssirkler';
$settingsdistWindTXT = 'Vis vindsymbol';
$DisplayAIS_TXT = 'Vis AIS';
$hideControlsSwitchTXT = "Hide controls";
$hideControlsPositionTXT = "Location of the sensitive area on the screen";
$minWATCHintervalTXT = 'Ikke oppdater posisjonen oftere enn, i sekunder.';

$integerTXT = 'Integer';
$realTXT = 'Sann';
$clearTXT = 'Tøm';
$okTXT = 'Lagre';
$latTXT = 'Bredde';
$longTXT = 'Lengde';
$completeTXT = 'ferdig';
$copyToClipboardMessageOkTXT = 'Kopiering til utklippstavle vellykket ';
$copyToClipboardMessageBadTXT = 'Kopiering til utklippstavle MISLYKKET ';

$AISstatusTXT = array(
0=>'går for motor',
1=>'oppankret',
2=>'ute av stand til å manøvrere',
3=>'begrenset manøvrerbarhet',
4=>'begrenset av størrelse',
5=>'ved kai',
6=>'på grunn',
7=>'driver fiske',
8=>'går for seil',
11=>'motordrevet fartøy med slep bak',
12=>'motordrevet fartøy som skyver eller har slep ved siden',
15=>''
);
$AISshipTypeTXT = array(
0=>'',
20=>'Ekranoplan',
21=>'Ekranoplan, fare A',
22=>'Ekranoplan, fare B',
23=>'Ekranoplan, fare C',
24=>'Ekranoplan, fare D',
25=>'Ekranoplan',
26=>'Ekranoplan',
27=>'Ekranoplan',
28=>'Ekranoplan',
29=>'Redningsluftfartøy',
30=>'Fisker',
31=>'Sleper',
32=>'Sleper, lengde over 200 m eller bredde over 25 m',
33=>'Mudring eller undervannsoperasjon',
34=>'Dykkerfartøy',
35=>'Militære operasjoner',
36=>'Seilbåt',
37=>'Fritidsbåt',
40=>'Høyhastighetsfartøy',
41=>'Høyhastighetsfartøy, fare A',
42=>'Høyhastighetsfartøy, fare B',
43=>'Høyhastighetsfartøy, fare C',
44=>'Høyhastighetsfartøy, fare D',
45=>'Høyhastighetsfartøy',
46=>'Høyhastighetsfartøy',
47=>'Høyhastighetsfartøy',
48=>'Høyhastighetsfartøy',
49=>'Høyhastighetsfartøy',
50=>'Losbåt',
51=>'Søk og redning',
52=>'Taubåt',
53=>'Mindre arbeidsbåt',
54=>'Antiforurensning',
55=>'Politi',
56=>'Lokalt fartøy',
57=>'Lokalt fartøy',
58=>'Ambulansebåt',
59=>'Spesialfartøy',
60=>'Passasjerfartøy',
61=>'Passasjerfartøy, fare A',
62=>'Passasjerfartøy, fare B',
63=>'Passasjerfartøy, fare C',
64=>'Passasjerfartøy, fare D',
65=>'Passasjerfartøy',
66=>'Passasjerfartøy',
67=>'Passasjerfartøy',
68=>'Passasjerfartøy',
69=>'Passasjerfartøy',
70=>'Fraktefartøy',
71=>'Fraktefartøy, fare A',
72=>'Fraktefartøy, fare B',
73=>'Fraktefartøy, fare C',
74=>'Fraktefartøy, fare D',
75=>'Fraktefartøy',
76=>'Fraktefartøy',
77=>'Fraktefartøy',
78=>'Fraktefartøy',
79=>'Fraktefartøy',
80=>'Tankskip',
81=>'Tankskip, fare A',
82=>'Tankskip, fare B',
83=>'Tankskip, fare C',
84=>'Tankskip, fare D',
85=>'Tankskip',
86=>'Tankskip',
87=>'Tankskip',
88=>'Tankskip',
89=>'Tankskip',
90=>'Annet fartøy',
91=>'Annet fartøy, fare A',
92=>'Annet fartøy, fare B',
93=>'Annet fartøy, fare C',
94=>'Annet fartøy, fare D',
95=>'Annet fartøy',
96=>'Annet fartøy',
97=>'Annet fartøy',
98=>'Annet fartøy',
99=>'Annet fartøy',
);

$mobTXT = "Mann over bord!";
$addMarkerTXT = "Nytt merke";
$bearingTXT = "Kurs";
$distanceTXT = 'Avstand';
$altDistanceTXT = 'distance';
$altBearingTXT = "bearing";
$removeMarkerTXT = "Fjern merke";
$cancelMOBTXT = "Avslutt";
$relBearingTXT = "'forut',
'forenom tvers om styrbord',
'styrbord',	
'aktenfor tvers om styrbord',
'akterut',
'aktenfor tvers om babord',
'babord',	
'aktenfor tvers om styrbord'";
?>
