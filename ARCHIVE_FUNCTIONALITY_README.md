# Archive Functionality for Rice Dispenser IoT

## Overview
The archive functionality allows users to archive and unarchive transactions in the rice dispenser system. This helps organize transactions by keeping active transactions separate from completed or old ones.

## Features Added

### 1. Database Schema Updates
- Added `is_archived` field to the `transactions` table
- Added database indexes for better performance when filtering archived transactions
- Default value is `0` (not archived)

### 2. User Interface Updates
- **Archive Toggle Switch**: Toggle between viewing active and archived transactions
- **Status Badges**: Visual indicators showing transaction status (Active/Archived)
- **Action Buttons**: Archive and Unarchive buttons for each transaction
- **Responsive Design**: Archive controls work on all screen sizes

### 3. Backend Functionality
- **Archive API**: `archive_transaction.php` handles archive/unarchive operations
- **Filtered Queries**: Database queries now filter by archive status
- **Pagination Support**: Pagination works with archive filtering
- **Error Handling**: Comprehensive error handling and user feedback

## Files Modified/Created

### New Files
1. `add_archive_field.sql` - Database schema updates
2. `archive_transaction.php` - API endpoint for archive operations
3. `test_archive_functionality.php` - Test script to verify functionality
4. `ARCHIVE_FUNCTIONALITY_README.md` - This documentation

### Modified Files
1. `transaction.php` - Updated with archive functionality

## Installation Instructions

### Step 1: Update Database Schema
Run the following SQL commands on your database:

```sql
-- Add archive field to transactions table
ALTER TABLE transactions ADD COLUMN is_archived TINYINT(1) NOT NULL DEFAULT 0 AFTER price_per_kg;

-- Add index for better performance when filtering archived transactions
CREATE INDEX idx_transactions_archived ON transactions(is_archived);

-- Add index for better performance when filtering by date and archive status
CREATE INDEX idx_transactions_date_archived ON transactions(transaction_date, is_archived);
```

Or simply run the `add_archive_field.sql` file in your database.

### Step 2: Upload Files
Upload the following files to your server:
- `transaction.php` (updated)
- `archive_transaction.php` (new)

### Step 3: Test Functionality
Visit `test_archive_functionality.php` to verify everything is working correctly.

## Usage Guide

### Viewing Transactions
1. **Active Transactions**: By default, only active (non-archived) transactions are shown
2. **Archived Transactions**: Toggle the "Show Archived" switch to view archived transactions
3. **Status Indicators**: Each transaction shows its status with colored badges

### Managing Transactions
1. **Archive Transaction**: Click the "Archive" button to move a transaction to archived status
2. **Unarchive Transaction**: Click the "Unarchive" button to restore an archived transaction
3. **Confirmation**: System asks for confirmation before archiving/unarchiving
4. **Feedback**: Success/error notifications appear after operations

### Filtering and Search
- Archive toggle works independently of search and date filters
- Pagination respects the current archive filter setting
- All existing search functionality remains intact

## Technical Details

### Database Schema
```sql
ALTER TABLE transactions ADD COLUMN is_archived TINYINT(1) NOT NULL DEFAULT 0 AFTER price_per_kg;
```

### API Endpoint
**URL**: `archive_transaction.php`
**Method**: POST
**Content-Type**: application/json

**Request Body**:
```json
{
    "transaction_id": 123,
    "action": "archive" // or "unarchive"
}
```

**Response**:
```json
{
    "success": true,
    "message": "Transaction archived successfully",
    "action": "archive",
    "transaction_id": 123
}
```

### Frontend JavaScript
- Archive toggle automatically updates URL parameters
- AJAX calls handle archive/unarchive operations
- Real-time notifications provide user feedback
- Page reloads after successful operations to reflect changes

## Styling
The archive functionality includes:
- Modern toggle switch design
- Color-coded status badges (green for active, red for archived)
- Hover effects on action buttons
- Responsive design for mobile devices
- Print-friendly styles (archive controls hidden when printing)

## Error Handling
- Database connection errors are caught and displayed
- Invalid transaction IDs are handled gracefully
- Network errors show user-friendly messages
- Confirmation dialogs prevent accidental operations

## Performance Considerations
- Database indexes improve query performance
- Pagination limits the number of transactions loaded
- AJAX operations don't require full page reloads
- Efficient filtering reduces database load

## Browser Compatibility
- Works with all modern browsers
- Uses standard JavaScript (no external dependencies)
- CSS3 features with fallbacks for older browsers
- Responsive design works on mobile devices

## Security Considerations
- Input validation on all archive operations
- SQL injection protection through prepared statements
- CSRF protection through session validation
- User confirmation required for destructive operations

## Future Enhancements
Potential improvements could include:
- Bulk archive operations
- Archive by date ranges
- Export archived transactions
- Archive history tracking
- Automatic archiving based on age

## Troubleshooting

### Common Issues
1. **Archive field not found**: Run the SQL commands in `add_archive_field.sql`
2. **Buttons not working**: Check browser console for JavaScript errors
3. **Styling issues**: Ensure CSS is loading properly
4. **Database errors**: Verify database connection and permissions

### Testing
Use `test_archive_functionality.php` to verify:
- Database schema is correct
- Archive operations work
- Filtering functions properly
- API endpoint responds correctly

## Support
For issues or questions about the archive functionality:
1. Check the browser console for JavaScript errors
2. Verify database schema is updated
3. Test with `test_archive_functionality.php`
4. Check server error logs for PHP errors
