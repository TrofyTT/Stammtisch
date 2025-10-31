-- Update für Benutzer-Farben
USE kdph7973_pimmel;

ALTER TABLE users 
ADD COLUMN color VARCHAR(7) NULL AFTER rang;

