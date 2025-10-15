-- Add capacity column to rice_inventory table
ALTER TABLE rice_inventory ADD COLUMN capacity DECIMAL(5,2) DEFAULT 10.00;

-- Update existing records with default capacity
UPDATE rice_inventory SET capacity = 10.00 WHERE capacity IS NULL;

-- Add check constraint to ensure capacity is positive
ALTER TABLE rice_inventory ADD CONSTRAINT chk_capacity_positive CHECK (capacity > 0);

-- Add index for better performance
CREATE INDEX idx_rice_capacity ON rice_inventory(capacity);
