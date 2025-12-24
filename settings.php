<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Settings for block_reports_custom.
 *
 * @package    block_reports_custom
 * @copyright  2025 Alonso Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    // Heading for role-category restrictions.
    $settings->add(new admin_setting_heading(
        'block_reports_custom/restrictionsheading',
        get_string('settings_restrictions_heading', 'block_reports_custom'),
        get_string('settings_restrictions_desc', 'block_reports_custom')
    ));

    // Role to category mappings.
    $settings->add(new admin_setting_configtextarea(
        'block_reports_custom/role_category_mappings',
        get_string('settings_role_category_mappings', 'block_reports_custom'),
        get_string('settings_role_category_mappings_desc', 'block_reports_custom'),
        '',
        PARAM_RAW
    ));

    // Heading for report settings.
    $settings->add(new admin_setting_heading(
        'block_reports_custom/reportsheading',
        get_string('settings_reports_heading', 'block_reports_custom'),
        get_string('settings_reports_desc', 'block_reports_custom')
    ));

    // Records per page.
    $settings->add(new admin_setting_configtext(
        'block_reports_custom/records_per_page',
        get_string('settings_records_per_page', 'block_reports_custom'),
        get_string('settings_records_per_page_desc', 'block_reports_custom'),
        '100',
        PARAM_INT
    ));

    // User type field name.
    $settings->add(new admin_setting_configtext(
        'block_reports_custom/user_type_field',
        get_string('settings_user_type_field', 'block_reports_custom'),
        get_string('settings_user_type_field_desc', 'block_reports_custom'),
        'user_type',
        PARAM_ALPHANUMEXT
    ));

    // Default unassigned label.
    $settings->add(new admin_setting_configtext(
        'block_reports_custom/unassigned_label',
        get_string('settings_unassigned_label', 'block_reports_custom'),
        get_string('settings_unassigned_label_desc', 'block_reports_custom'),
        'No asignado',
        PARAM_TEXT
    ));
}
