# Changelog

All notable changes to the Custom Reports Block plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.0.0] - 2024-12-24

### Added

- **Settings page** (`settings.php`) for administrative configuration
  - Role-to-category mappings (replaces hardcoded values)
  - Configurable records per page
  - Configurable user type field name
  - Configurable unassigned label
- **GDPR Privacy provider** (`classes/privacy/provider.php`)
  - Implements `null_provider` interface
  - Declares plugin does not store personal data
- **New library functions** with proper prefixes:
  - `block_reports_custom_get_certificates_records()`
  - `block_reports_custom_count_certificates_records()`
  - `block_reports_custom_get_progress_records()`
  - `block_reports_custom_count_progress_records()`
  - `block_reports_custom_get_allowed_categories_for_user()`
  - `block_reports_custom_get_role_category_mappings()`
  - `block_reports_custom_format_date()`
  - `block_reports_custom_is_customcert_installed()`
  - `block_reports_custom_get_enrolled_users()`
- **Dependency declaration** for `mod_customcert` in `version.php`
- **Backwards compatibility wrappers** for deprecated functions
- **Comprehensive PHPDoc** documentation for all functions
- **GPL license headers** in all PHP files
- **New language strings** for settings and error messages
- **Database-agnostic SQL** using `$DB->sql_concat()` for portability

### Changed

- **Major version bump** to 2.0.0 due to breaking changes in function signatures
- **Refactored all library functions** with `block_reports_custom_` prefix
- **Optimized `get_category_path()`** from N+1 queries to single query
- **Improved pagination** to use SQL LIMIT/OFFSET instead of PHP array_slice
- **Enhanced security** in all database queries:
  - Uses `$DB->get_in_or_equal()` for IN clauses
  - Uses `$DB->sql_like()` and `$DB->sql_like_escape()` for LIKE clauses
  - Proper parameter binding throughout
- **Updated block class** with:
  - `specialization()` method for custom titles
  - `applicable_formats()` method
  - `instance_allow_config()` method
  - Improved content rendering with icons
- **Improved filter handling** to properly manage empty/null values
- **Enhanced date formatting** using Moodle's `userdate()` function
- **Updated capabilities** comments and structure in `access.php`

### Fixed

- **Critical: SQL Injection vulnerabilities** in 4 locations:
  - `lib.php` line 274 (certificates query)
  - `lib.php` line 349 (progress query)
  - `get_courses.php` line 21
  - `get_users.php` line 39
- **Critical: Error 500** caused by empty `IN ()` clause when `allowed_categories` was empty string
- **XSS vulnerabilities** in form value attributes (now escaped with `s()`)
- **Missing capability checks** in AJAX endpoints (`get_courses.php`, `get_users.php`)
- **MySQL-specific SQL** (`FROM_UNIXTIME`, `CONCAT`) replaced with portable alternatives
- **Potential memory issues** with large datasets by implementing proper pagination
- **Deleted users appearing in reports** (added `u.deleted = 0` filter)

### Deprecated

- `export_to_spreadsheet()` - Use `block_reports_custom_export_to_spreadsheet()` instead
- `export_to_csv()` - Use `block_reports_custom_export_to_csv()` instead
- `get_category_path()` - Use `block_reports_custom_get_category_path()` instead
- `get_all_categories()` - Use `block_reports_custom_get_all_categories()` instead
- `get_courses_by_category()` - Use `block_reports_custom_get_courses_by_category()` instead
- `get_user_types()` - Use `block_reports_custom_get_user_types()` instead
- `get_allowed_categories_for_user()` - Use `block_reports_custom_get_allowed_categories_for_user()` instead
- `get_certificates_records()` - Use `block_reports_custom_get_certificates_records()` instead
- `get_progress_records()` - Use `block_reports_custom_get_progress_records()` instead

### Removed

- **Hardcoded role IDs** (11, 12) - Now configurable via settings
- **Hardcoded category IDs** (72, 74) - Now configurable via settings
- **Trailing `?>` tags** from all PHP files (Moodle standard)

### Security

- Implemented prepared statements with named parameters throughout
- Added `AJAX_SCRIPT` constant to AJAX endpoints
- Added `defined('MOODLE_INTERNAL') || die()` to all internal files
- Proper escaping of all user-supplied output
- Capability verification on all sensitive operations

---

## [1.0.0] - 2024-08-12

### Added

- Initial release
- Certificates report with filtering and export
- Progress report with filtering and export
- Basic role-based category restrictions (hardcoded)
- Multi-language support (English, Spanish)
- Export to Excel, ODS, and CSV formats
- Alphabetical filtering for names
- Date range filtering
- User type filtering via custom profile field
- AJAX-powered dynamic filtering
- Pagination support

### Known Issues (Fixed in 2.0.0)

- SQL Injection vulnerabilities in query building
- Hardcoded role and category IDs
- Performance issues with large datasets
- MySQL-specific SQL functions
- Missing GDPR privacy provider
- Missing capability checks on AJAX endpoints

---

## Migration Guide: 1.0 to 2.0

### For Administrators

1. **Backup your Moodle site** before upgrading

2. **Note your current role-category mappings** (if using the hardcoded values):
   - Role ID 11 → Category 72
   - Role ID 12 → Category 74

3. **Upgrade the plugin** via Moodle's plugin installer

4. **Configure role-category mappings**:
   - Go to: Site administration → Plugins → Blocks → Custom Reports Block
   - Enter your mappings in the format: `role_id:category_id1,category_id2`
   - Example:
     ```
     11:72
     12:74
     ```

5. **Purge caches**: Site administration → Development → Purge caches

### For Developers

1. **Update function calls** to use new prefixed versions:
   ```php
   // Old (deprecated, still works with warning)
   $records = get_progress_records($params, $DB);

   // New (recommended)
   $records = block_reports_custom_get_progress_records($filters);
   ```

2. **Update parameter format** for record functions:
   ```php
   // Old format
   $params = [
       'allowed_categories' => '72,74', // String
   ];

   // New format
   $filters = [
       'allowed_categories' => [72, 74], // Array
   ];
   ```

3. **Handle new pagination parameters**:
   ```php
   // Now supports pagination directly
   $records = block_reports_custom_get_progress_records($filters, $offset, $limit);
   $total = block_reports_custom_count_progress_records($filters);
   ```

4. **Date handling has changed**:
   ```php
   // Records now return timestamp instead of formatted date
   $record->fechatimestamp // Unix timestamp

   // Format with:
   block_reports_custom_format_date($record->fechatimestamp);
   ```

---

## Version Numbering

- **Major** (X.0.0): Breaking changes, API modifications
- **Minor** (0.X.0): New features, backwards compatible
- **Patch** (0.0.X): Bug fixes, security patches

## Support

For support with upgrades or issues:
- Check the [README.md](README.md) for documentation
- Report issues via GitHub Issues
- Consult Moodle community forums
