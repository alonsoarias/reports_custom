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
 * AJAX endpoint to get courses by category.
 *
 * @package    block_reports_custom
 * @copyright  2024 Your Organization
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

require_once('../../../config.php');
require_once($CFG->dirroot . '/blocks/reports_custom/lib.php');

// Require login and capability.
require_login();
require_capability('block/reports_custom:viewreports', context_system::instance());

// Get parameters.
$categoryid = optional_param('category', 0, PARAM_INT);

// Get allowed categories for current user.
$allowedcategories = block_reports_custom_get_allowed_categories_for_user($USER->id);

// Get courses with security.
$courses = block_reports_custom_get_courses_by_category($categoryid, $allowedcategories);

// Format output.
$result = [];
foreach ($courses as $course) {
    $result[] = [
        'id' => $course->id,
        'fullname' => $course->fullname,
    ];
}

// Return JSON response.
header('Content-Type: application/json; charset=utf-8');
echo json_encode($result);
