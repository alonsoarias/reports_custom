# Custom Reports Block for Moodle

[![Moodle Plugin](https://img.shields.io/badge/Moodle-3.11%2B-orange.svg)](https://moodle.org)
[![License](https://img.shields.io/badge/License-GPL%20v3-blue.svg)](LICENSE)
[![Version](https://img.shields.io/badge/Version-2.0.0-green.svg)](version.php)

A Moodle block plugin that provides customizable reports for certificates and user progress with advanced filtering, export capabilities, and role-based access control.

## Features

- **Two Comprehensive Reports**
  - **Certificates Report**: View all certificates issued through the Custom Certificate plugin
  - **Progress Report**: Track user grades and course progress with detailed breakdowns

- **Advanced Filtering**
  - Filter by category, course, user type, date range
  - Alphabetical navigation for first/last name filtering
  - ID number search
  - Real-time AJAX updates without page reload

- **Export Capabilities**
  - Export to Excel (.xlsx)
  - Export to ODS (LibreOffice)
  - Export to CSV

- **Role-Based Access Control**
  - Configurable role-to-category mappings
  - Restrict which categories specific roles can view
  - Flexible permission system using Moodle capabilities

- **Multi-language Support**
  - English
  - Spanish (Español)

## Requirements

- Moodle 3.11 or higher (version 2021051700+)
- [Custom Certificate Plugin](https://moodle.org/plugins/mod_customcert) (for certificates report)
- PHP 7.4 or higher

## Installation

### Method 1: Via Moodle Plugin Directory
1. Log in as administrator
2. Go to **Site administration → Plugins → Install plugins**
3. Search for "Custom Reports Block"
4. Click Install

### Method 2: Manual Installation
1. Download the plugin
2. Extract to `/blocks/reports_custom/`
3. Log in as administrator
4. Go to **Site administration → Notifications**
5. Follow the installation prompts

## Configuration

### Global Settings

Navigate to **Site administration → Plugins → Blocks → Custom Reports Block**

| Setting | Description |
|---------|-------------|
| **Role to Category Mappings** | Define which roles are restricted to specific categories. Format: `role_id:category_id1,category_id2` (one per line) |
| **Records per page** | Number of records displayed per page (default: 100) |
| **User type field** | Shortname of the user profile field for user type classification |
| **Unassigned label** | Label shown when user has no type assigned |

#### Example Role-Category Mapping

```
11:72,73
12:74
```

This configuration means:
- Users with role ID 11 can only see data from categories 72 and 73
- Users with role ID 12 can only see data from category 74
- Users without these roles have no restrictions

### Capabilities

| Capability | Description | Default Role |
|------------|-------------|--------------|
| `block/reports_custom:addinstance` | Add block to a page | Manager |
| `block/reports_custom:myaddinstance` | Add block to Dashboard | Manager |
| `block/reports_custom:view` | View the block | Manager |
| `block/reports_custom:viewreports` | Access and view reports | Manager |

## Usage

### Adding the Block

1. Turn editing on
2. Add the "Custom Reports Block" from the block drawer
3. The block displays links to available reports

### Using Reports

1. Click on a report link from the block
2. Use filters to narrow down results:
   - Select category/course
   - Choose date range
   - Filter by user type
   - Use alphabetical navigation
3. View results in paginated table
4. Export data using the download options

## User Profile Field Setup

For the user type filtering to work, create a custom user profile field:

1. Go to **Site administration → Users → User profile fields**
2. Create a new field with:
   - **Short name**: `user_type` (or your configured name)
   - **Name**: User Type
   - **Type**: Text or Menu of choices

## File Structure

```
reports_custom/
├── block_reports_custom.php    # Main block class
├── lib.php                     # Library functions
├── version.php                 # Plugin version info
├── settings.php                # Admin settings
├── amd/
│   ├── src/
│   │   ├── repository.js       # AJAX repository module
│   │   ├── filter.js           # Filter handling module
│   │   ├── certificates.js     # Certificates report controller
│   │   └── progress.js         # Progress report controller
│   └── build/
│       ├── repository.min.js   # Minified repository
│       ├── filter.min.js       # Minified filter
│       ├── certificates.min.js # Minified certificates
│       └── progress.min.js     # Minified progress
├── classes/
│   └── privacy/
│       └── provider.php        # GDPR privacy provider
├── db/
│   └── access.php              # Capabilities definition
├── lang/
│   ├── en/
│   │   └── block_reports_custom.php
│   └── es/
│       └── block_reports_custom.php
└── reports/
    ├── certificates.php        # Certificates report page
    ├── progress.php            # Progress report page
    ├── get_courses.php         # AJAX endpoint
    └── get_users.php           # AJAX endpoint
```

## API Functions

All public functions use the `block_reports_custom_` prefix:

| Function | Description |
|----------|-------------|
| `block_reports_custom_get_certificates_records($filters, $limitfrom, $limitnum)` | Get certificate records |
| `block_reports_custom_count_certificates_records($filters)` | Count certificate records |
| `block_reports_custom_get_progress_records($filters, $limitfrom, $limitnum)` | Get progress records |
| `block_reports_custom_count_progress_records($filters)` | Count progress records |
| `block_reports_custom_get_allowed_categories_for_user($userid)` | Get allowed categories for user |
| `block_reports_custom_export_to_spreadsheet($headers, $rows, $filename, $format, $sheetname)` | Export to Excel/ODS |
| `block_reports_custom_export_to_csv($headers, $rows, $filename)` | Export to CSV |

## Security

This plugin implements several security measures:

- **SQL Injection Prevention**: Uses Moodle's `$DB->get_in_or_equal()` and parameterized queries
- **XSS Prevention**: All output escaped with `s()` and `format_string()`
- **Access Control**: Capability checks on all pages and AJAX endpoints
- **CSRF Protection**: Uses Moodle's sesskey validation

## GDPR Compliance

This plugin is GDPR compliant:
- Does not store any personal data
- Only displays data from core Moodle components
- Implements `\core_privacy\local\metadata\null_provider`

## Troubleshooting

### Error 500 on Reports Page

1. Verify Custom Certificate plugin is installed
2. Check that the `user_type` profile field exists
3. Review PHP error logs for specific errors
4. Ensure database user has proper permissions

### No Data Displayed

1. Verify user has `block/reports_custom:viewreports` capability
2. Check role-category mappings in settings
3. Ensure certificates have been issued (for certificates report)
4. Verify grades exist (for progress report)

### Export Not Working

1. Check PHP memory limit
2. Verify PHPSpreadsheet is available
3. Check file permissions on temp directory

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes following Moodle coding standards
4. Submit a pull request

## License

This plugin is licensed under the [GNU GPL v3](https://www.gnu.org/licenses/gpl-3.0.html).

## Support

- **Issues**: Report bugs via GitHub Issues
- **Documentation**: See Moodle Docs
- **Community**: Moodle Forums

## Credits

- Developed by Alonso Arias <soporte@ingeweb.co>
- Contributors welcome!

## See Also

- [Custom Certificate Plugin](https://moodle.org/plugins/mod_customcert)
- [Moodle Block Development](https://docs.moodle.org/dev/Blocks)
- [Moodle Coding Style](https://docs.moodle.org/dev/Coding_style)
