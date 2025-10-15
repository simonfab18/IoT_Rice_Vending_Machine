-- Fix transactions table to store rice names directly
-- This will prevent old transactions from changing when rice names are updated

-- Add rice_name column to transactions table
ALTER TABLE `transactions` 
ADD COLUMN `rice_name` varchar(100) NOT NULL DEFAULT '' AFTER `rice_type`;

-- Update existing transactions with rice names based on their type
UPDATE `transactions` t
LEFT JOIN `rice_inventory` r ON t.rice_type = r.type
SET t.rice_name = COALESCE(r.name, 'Unknown Rice')
WHERE t.rice_name = '';

-- Make rice_name NOT NULL after populating it
ALTER TABLE `transactions` 
MODIFY COLUMN `rice_name` varchar(100) NOT NULL;

-- Remove the rice_type column since we're storing names directly
ALTER TABLE `transactions` 
DROP COLUMN `rice_type`;

-- Update rice_inventory table to remove type system
-- First, remove the type column
ALTER TABLE `rice_inventory` 
DROP COLUMN `type`;

-- Update the table to use only rice names
-- No need to change the structure further as name is already the primary identifier
