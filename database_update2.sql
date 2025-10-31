-- Update für Rang-Feld
USE kdph7973_pimmel;

ALTER TABLE users 
ADD COLUMN rang VARCHAR(100) NULL AFTER avatar;

