INSERT INTO `passwort` (hash, projekt_id, salt)
VALUES (SHA2('masterabcdefghijklmnopqRsTuvWXzY!@#$%^', 256), null, 'abcdefghijklmnopqRsTuvWXzY!@#$%^');

INSERT INTO `projekt` (projektname)
VALUES ('Testprojekt zum Testen');

INSERT INTO `passwort` (hash, projekt_id, salt)
VALUES (SHA2('TestprojektA3G57VsJ^8Ch*5$pqRsTuvWXzY!F#$%^', 256), (SELECT id FROM `projekt` WHERE projektname = 'Testprojekt zum Testen'), 'A3G57VsJ^8Ch*5$pqRsTuvWXzY!F#$%^'); 

INSERT INTO `messreihe` (projekt_id)
VALUES ((SELECT id FROM `projekt` WHERE projektname = 'Testprojekt zum Testen')); 

INSERT INTO `datentyp` (typ)
VALUES ('datum'), ('string'), ('int'), ('float'); 

INSERT INTO `metainfo` (metaname, datentyp_id)
VALUES ('Name', (SELECT id FROM `datentyp` where typ = 'string')),
       ('Datum', (SELECT id FROM `datentyp` where typ = 'datum')),
       ('Material', (SELECT id FROM `datentyp` where typ = 'string')),
       ('Trocknungstemp', (SELECT id FROM `datentyp` where typ = 'string')),
       ('Taupunkt', (SELECT id FROM `datentyp` where typ = 'string')),
       ('Masse', (SELECT id FROM `datentyp` where typ = 'string'));

INSERT INTO `messreihe_metainfo` (messreihe_id, metainfo_id, metawert)
VALUES ((SELECT id FROM `messreihe` ORDER BY id DESC LIMIT 1), (SELECT id FROM `metainfo` WHERE metaname = 'Name'), 'Trocknungslauf kont. Förderung'),
       ((SELECT id FROM `messreihe` ORDER BY id DESC LIMIT 1), (SELECT id FROM `metainfo` WHERE metaname = 'Datum'), '14.10.2014'),
       ((SELECT id FROM `messreihe` ORDER BY id DESC LIMIT 1), (SELECT id FROM `metainfo` WHERE metaname = 'Material'), 'PA6'),
       ((SELECT id FROM `messreihe` ORDER BY id DESC LIMIT 1), (SELECT id FROM `metainfo` WHERE metaname = 'Trocknungstemp'), '80'),
       ((SELECT id FROM `messreihe` ORDER BY id DESC LIMIT 1), (SELECT id FROM `metainfo` WHERE metaname = 'Taupunkt'), '-15'),
       ((SELECT id FROM `messreihe` ORDER BY id DESC LIMIT 1), (SELECT id FROM `metainfo` WHERE metaname = 'Masse'), 'kont. Förderung	kg');

INSERT INTO `sensor` (sensorname)
VALUE ('Material Eintritt'), ('Trichter 1'), ('Trichter 2'),	('Trichter 3'),
('Trichter 4'),	('Trichter 5'), ('Abluft'), ('Material Austritt'),	('Zuluft'),
('Geschwindigkeit'),	('Temperatur bei Geschwindigkeitsmessung');

INSERT INTO `messreihe_sensor` (messreihe_id, sensor_id, anzeigename)
VALUES ((SELECT id FROM `messreihe` ORDER BY id DESC LIMIT 1), (SELECT id FROM `sensor` WHERE sensorname = 'Trichter 1'), 'Trichter Eingang'),
((SELECT id FROM `messreihe` ORDER BY id DESC LIMIT 1), (SELECT id FROM `sensor` WHERE sensorname = 'Trichter 2'), 'Trichter Ausgang'),
((SELECT id FROM `messreihe` ORDER BY id DESC LIMIT 1), (SELECT id FROM `sensor` WHERE sensorname = 'Trichter 3'), 'Trichter Mitte'),
((SELECT id FROM `messreihe` ORDER BY id DESC LIMIT 1), (SELECT id FROM `sensor` WHERE sensorname = 'Trichter 4'), 'Trichter Abluft');

INSERT INTO `messung` (sensor_id, messreihe_id, zeitpunkt, messwert)
VALUES ((SELECT id FROM `sensor` WHERE sensorname = 'Trichter 1'), (SELECT id FROM `messreihe` ORDER BY id DESC LIMIT 1), 1, 22.882503),
       ((SELECT id FROM `sensor` WHERE sensorname = 'Trichter 1'), (SELECT id FROM `messreihe` ORDER BY id DESC LIMIT 1), 2, 22.379281),
       ((SELECT id FROM `sensor` WHERE sensorname = 'Trichter 1'), (SELECT id FROM `messreihe` ORDER BY id DESC LIMIT 1), 3, 22.378268),
       ((SELECT id FROM `sensor` WHERE sensorname = 'Trichter 2'), (SELECT id FROM `messreihe` ORDER BY id DESC LIMIT 1), 1, 22.980477),
       ((SELECT id FROM `sensor` WHERE sensorname = 'Trichter 2'), (SELECT id FROM `messreihe` ORDER BY id DESC LIMIT 1), 2, 22.865348),
       ((SELECT id FROM `sensor` WHERE sensorname = 'Trichter 2'), (SELECT id FROM `messreihe` ORDER BY id DESC LIMIT 1), 3, 22.981266),
       ((SELECT id FROM `sensor` WHERE sensorname = 'Trichter 3'), (SELECT id FROM `messreihe` ORDER BY id DESC LIMIT 1), 1, 22.999486),
       ((SELECT id FROM `sensor` WHERE sensorname = 'Trichter 3'), (SELECT id FROM `messreihe` ORDER BY id DESC LIMIT 1), 2, 22.998877),
       ((SELECT id FROM `sensor` WHERE sensorname = 'Trichter 3'), (SELECT id FROM `messreihe` ORDER BY id DESC LIMIT 1), 3, 22.789654);



