-- Add archive field to transactions table
ALTER TABLE transactions ADD COLUMN is_archived TINYINT(1) NOT NULL DEFAULT 0 AFTER price_per_kg;

-- Add index for better performance when filtering archived transactions
CREATE INDEX idx_transactions_archived ON transactions(is_archived);

-- Add index for better performance when filtering by date and archive status
CREATE INDEX idx_transactions_date_archived ON transactions(transaction_date, is_archived);
