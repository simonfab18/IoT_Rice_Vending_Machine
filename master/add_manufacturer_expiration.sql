-- Add manufacturer and expiration_date columns to rice_inventory table
ALTER TABLE rice_inventory 
ADD COLUMN manufacturer VARCHAR(255) AFTER unit,
ADD COLUMN expiration_date DATE AFTER manufacturer;

-- Update existing records with default values
UPDATE rice_inventory 
SET manufacturer = 'Unknown Manufacturer', 
    expiration_date = DATE_ADD(NOW(), INTERVAL 1 YEAR)
WHERE manufacturer IS NULL OR expiration_date IS NULL;
