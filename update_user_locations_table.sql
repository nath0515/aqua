-- Add additional_info column to user_locations table
ALTER TABLE user_locations ADD COLUMN additional_info TEXT NULL AFTER address;

-- Update existing records to have empty additional_info
UPDATE user_locations SET additional_info = '' WHERE additional_info IS NULL; 