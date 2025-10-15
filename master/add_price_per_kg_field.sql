-- Add price_per_kg field to transactions table
ALTER TABLE transactions ADD COLUMN price_per_kg DECIMAL(10,2) NOT NULL DEFAULT 60.00 AFTER kilos;

-- Update existing records with default price (you may need to adjust this based on your data)
UPDATE transactions SET price_per_kg = 60.00 WHERE price_per_kg IS NULL;
