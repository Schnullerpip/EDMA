INSERT INTO `passwort` (hash, projekt_id, salt)
VALUES (SHA2('masterabcdefghijklmnopqRsTuvWXzY!@#$%^', 256), null, 'abcdefghijklmnopqRsTuvWXzY!@#$%^');

INSERT INTO `projekt` (projektname)
VALUES ('Testprojekt zum Testen');

INSERT INTO `passwort` (hash, projekt_id, salt)
VALUES (SHA2('TestprojektA3G57VsJ^8Ch*5$pqRsTuvWXzY!F#$%^', 256), (SELECT id FROM `projekt` WHERE projektname = 'Testprojekt zum Testen'), 'A3G57VsJ^8Ch*5$pqRsTuvWXzY!F#$%^'); 

INSERT INTO `datentyp` (typ)
VALUES ('datum'), ('string'), ('numerisch'); 
