-- Update für Profilbilder
USE kdph7973_pimmel;

ALTER TABLE users 
ADD COLUMN avatar VARCHAR(255) NULL AFTER name;

