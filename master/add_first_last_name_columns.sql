-- Add first_name and last_name columns to admin_users table
-- This script will add the new columns while preserving existing data

-- Add first_name column
ALTER TABLE admin_users ADD COLUMN first_name VARCHAR(100) AFTER full_name;

-- Add last_name column  
ALTER TABLE admin_users ADD COLUMN last_name VARCHAR(100) AFTER first_name;

-- Update existing records to split full_name into first_name and last_name
-- This will attempt to split existing full names
UPDATE admin_users 
SET 
    first_name = CASE 
        WHEN full_name IS NOT NULL AND full_name != '' THEN
            CASE 
                WHEN LOCATE(' ', full_name) > 0 THEN 
                    SUBSTRING(full_name, 1, LOCATE(' ', full_name) - 1)
                ELSE 
                    full_name
            END
        ELSE ''
    END,
    last_name = CASE 
        WHEN full_name IS NOT NULL AND full_name != '' THEN
            CASE 
                WHEN LOCATE(' ', full_name) > 0 THEN 
                    SUBSTRING(full_name, LOCATE(' ', full_name) + 1)
                ELSE 
                    ''
            END
        ELSE ''
    END
WHERE first_name IS NULL OR last_name IS NULL;

-- Optional: Make the new columns NOT NULL if you want to enforce them
-- ALTER TABLE admin_users MODIFY COLUMN first_name VARCHAR(100) NOT NULL;
-- ALTER TABLE admin_users MODIFY COLUMN last_name VARCHAR(100) NOT NULL;

-- Show the updated table structure
DESCRIBE admin_users;
