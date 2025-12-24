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
 * Library functions for block_reports_custom.
 *
 * @package    block_reports_custom
 * @copyright  2025 Alonso Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/excellib.class.php");
require_once("$CFG->libdir/odslib.class.php");
require_once("$CFG->libdir/csvlib.class.php");

/**
 * Export data to .xlsx or .ods format.
 *
 * @param array $headers The headers for the data.
 * @param array $rows The rows of data.
 * @param string $filename The base name of the file without extension.
 * @param string $format The format of the export: 'excel' or 'ods'.
 * @param string $sheetname The name of the worksheet.
 * @return void
 */
function block_reports_custom_export_to_spreadsheet($headers, $rows, $filename, $format, $sheetname = 'Sheet1') {
    global $CFG;

    while (ob_get_level()) {
        ob_end_clean();
    }

    if ($format === 'excel') {
        $filename .= ".xlsx";
        $workbook = new MoodleExcelWorkbook("-");
    } else {
        $filename .= ".ods";
        $workbook = new MoodleODSWorkbook("-");
    }

    $workbook->send($filename);
    $worksheet = $workbook->add_worksheet($sheetname);
    $formatbc = $workbook->add_format(array('bold' => 1));

    $col = 0;
    foreach ($headers as $header) {
        $worksheet->write(0, $col++, $header, $formatbc);
    }

    $row = 1;
    foreach ($rows as $record) {
        $col = 0;
        foreach ($record as $value) {
            $worksheet->write($row, $col++, $value);
        }
        $row++;
    }

    $workbook->close();
}

/**
 * Export data to .csv format.
 *
 * @param array $headers The headers for the data.
 * @param array $rows The rows of data.
 * @param string $filename The base name of the file.
 * @return void
 */
function block_reports_custom_export_to_csv($headers, $rows, $filename) {
    while (ob_get_level()) {
        ob_end_clean();
    }

    $filename .= ".csv";

    header("Content-Type: text/csv; charset=utf-8");
    header("Content-Disposition: attachment; filename=\"$filename\"");
    header("Expires: 0");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Pragma: public");

    $output = fopen('php://output', 'w');
    if ($output === false) {
        debugging("Failed to open output stream", DEBUG_DEVELOPER);
        return;
    }

    // Add BOM for Excel UTF-8 compatibility.
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
    fputcsv($output, $headers);

    foreach ($rows as $record) {
        fputcsv($output, $record);
    }

    fclose($output);
}

/**
 * Get the full category path name (optimized - single query).
 *
 * @param int $categoryid The ID of the category.
 * @return string The full path name of the category.
 */
function block_reports_custom_get_category_path($categoryid) {
    global $DB;

    if (empty($categoryid)) {
        return '';
    }

    $category = $DB->get_record('course_categories', array('id' => $categoryid), 'id, path');
    if (!$category) {
        return '';
    }

    $pathids = array_filter(explode('/', trim($category->path, '/')));
    if (empty($pathids)) {
        return '';
    }

    // Single query to get all category names.
    list($insql, $inparams) = $DB->get_in_or_equal($pathids, SQL_PARAMS_NAMED);
    $categories = $DB->get_records_select(
        'course_categories',
        "id $insql",
        $inparams,
        '',
        'id, name'
    );

    // Build path in correct order.
    $fullpathnames = [];
    foreach ($pathids as $pathid) {
        if (isset($categories[$pathid])) {
            $fullpathnames[] = $categories[$pathid]->name;
        }
    }

    return implode(' / ', $fullpathnames);
}

/**
 * Get all categories that user has access to.
 *
 * @param array|null $allowedcategories Restricted category IDs or null for all.
 * @return array The list of categories.
 */
function block_reports_custom_get_all_categories($allowedcategories = null) {
    global $DB;

    if ($allowedcategories === null) {
        return $DB->get_records('course_categories', null, 'sortorder ASC', 'id, name, path');
    }

    if (empty($allowedcategories)) {
        return [];
    }

    list($insql, $params) = $DB->get_in_or_equal($allowedcategories, SQL_PARAMS_NAMED);
    return $DB->get_records_select(
        'course_categories',
        "id $insql",
        $params,
        'sortorder ASC',
        'id, name, path'
    );
}

/**
 * Get courses by category with access control.
 *
 * @param int $categoryid The ID of the category (0 for all).
 * @param array|null $allowedcategories Restricted category IDs or null for all.
 * @return array The list of courses.
 */
function block_reports_custom_get_courses_by_category($categoryid = 0, $allowedcategories = null) {
    global $DB;

    $params = [];
    $where = ['1=1'];

    if (!empty($categoryid)) {
        $where[] = 'category = :categoryid';
        $params['categoryid'] = $categoryid;
    }

    if ($allowedcategories !== null && !empty($allowedcategories)) {
        list($insql, $inparams) = $DB->get_in_or_equal($allowedcategories, SQL_PARAMS_NAMED, 'cat');
        $where[] = "category $insql";
        $params = array_merge($params, $inparams);
    }

    $wheresql = implode(' AND ', $where);
    return $DB->get_records_select('course', $wheresql, $params, 'fullname ASC', 'id, fullname');
}

/**
 * Get distinct user types including 'No asignado'.
 *
 * @return array The list of user types.
 */
function block_reports_custom_get_user_types() {
    global $DB;

    $sql = "SELECT DISTINCT d.data AS usertype
            FROM {user_info_data} d
            JOIN {user_info_field} f ON d.fieldid = f.id
            WHERE f.shortname = :shortname
              AND d.data IS NOT NULL
              AND d.data <> ''
            ORDER BY d.data ASC";

    $usertypes = $DB->get_records_sql($sql, ['shortname' => 'user_type']);
    $result = [];

    foreach ($usertypes as $record) {
        $result[] = (object)['usertype' => $record->usertype];
    }

    // Always include 'No asignado' option.
    $hasunassigned = false;
    foreach ($result as $type) {
        if ($type->usertype === 'No asignado') {
            $hasunassigned = true;
            break;
        }
    }
    if (!$hasunassigned) {
        $result[] = (object)['usertype' => 'No asignado'];
    }

    return $result;
}

/**
 * Get role to category mappings from plugin configuration.
 *
 * @return array Associative array of role_id => [category_ids].
 */
function block_reports_custom_get_role_category_mappings() {
    $config = get_config('block_reports_custom');
    $mappings = [];

    if (!empty($config->role_category_mappings)) {
        $lines = explode("\n", $config->role_category_mappings);
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || strpos($line, ':') === false) {
                continue;
            }
            list($roleid, $catids) = explode(':', $line, 2);
            $roleid = trim($roleid);
            if (is_numeric($roleid)) {
                $catarray = array_filter(array_map('trim', explode(',', $catids)));
                $catarray = array_filter($catarray, 'is_numeric');
                if (!empty($catarray)) {
                    $mappings[(int)$roleid] = array_map('intval', $catarray);
                }
            }
        }
    }

    return $mappings;
}

/**
 * Get allowed categories for a user based on their roles and plugin configuration.
 *
 * @param int $userid The ID of the user.
 * @return array|null The list of allowed category IDs or null if no restrictions.
 */
function block_reports_custom_get_allowed_categories_for_user($userid) {
    global $DB;

    // Get role-category mappings from configuration.
    $mappings = block_reports_custom_get_role_category_mappings();

    if (empty($mappings)) {
        // No restrictions configured.
        return null;
    }

    // Get user's roles.
    $sql = "SELECT DISTINCT r.id, r.shortname
            FROM {role_assignments} ra
            JOIN {role} r ON ra.roleid = r.id
            WHERE ra.userid = :userid";
    $userroles = $DB->get_records_sql($sql, ['userid' => $userid]);

    $allowedcategories = [];
    $userhasrestrictedrole = false;

    foreach ($userroles as $role) {
        if (isset($mappings[$role->id])) {
            $userhasrestrictedrole = true;
            $allowedcategories = array_merge($allowedcategories, $mappings[$role->id]);
        }
    }

    // If user doesn't have any restricted role, return null (no restrictions).
    if (!$userhasrestrictedrole) {
        return null;
    }

    return array_unique($allowedcategories);
}

/**
 * Get certificate records with proper SQL parameterization.
 *
 * @param array $filters The filter parameters.
 * @param int $limitfrom Start of records to return.
 * @param int $limitnum Number of records to return (0 = all).
 * @return array The list of certificate records.
 */
function block_reports_custom_get_certificates_records($filters, $limitfrom = 0, $limitnum = 0) {
    global $DB;

    $params = [];
    $where = ["u.idnumber <> ''", "u.deleted = 0"];

    $sql = "SELECT
                ci.id AS uniqueid,
                u.idnumber AS cedula,
                u.firstname AS nombres,
                u.lastname AS apellidos,
                u.institution AS clinica,
                u.department AS area,
                c.fullname AS nombrecurso,
                ci.timecreated AS fechatimestamp,
                cc.name AS categoriacurso,
                COALESCE(uid.data, :defaultusertype) AS user_type
            FROM {customcert_issues} ci
            JOIN {customcert} cert ON ci.customcertid = cert.id
            JOIN {course} c ON c.id = cert.course
            JOIN {user} u ON u.id = ci.userid
            JOIN {course_categories} cc ON c.category = cc.id
            LEFT JOIN {user_info_field} uif ON uif.shortname = :usertypefield
            LEFT JOIN {user_info_data} uid ON uid.userid = u.id AND uid.fieldid = uif.id";

    $params['defaultusertype'] = 'No asignado';
    $params['usertypefield'] = 'user_type';

    // Apply filters with proper parameterization.
    if (!empty($filters['category'])) {
        $where[] = "c.category = :category";
        $params['category'] = $filters['category'];
    }

    if (!empty($filters['course'])) {
        $where[] = "c.id = :course";
        $params['course'] = $filters['course'];
    }

    if (!empty($filters['firstname'])) {
        $where[] = $DB->sql_like('u.firstname', ':firstname', false);
        $params['firstname'] = $DB->sql_like_escape($filters['firstname']) . '%';
    }

    if (!empty($filters['lastname'])) {
        $where[] = $DB->sql_like('u.lastname', ':lastname', false);
        $params['lastname'] = $DB->sql_like_escape($filters['lastname']) . '%';
    }

    if (!empty($filters['usertype'])) {
        if ($filters['usertype'] === 'No asignado') {
            $where[] = "(uid.data IS NULL OR uid.data = '' OR uid.data = :usertype)";
        } else {
            $where[] = "uid.data = :usertype";
        }
        $params['usertype'] = $filters['usertype'];
    }

    if (!empty($filters['idnumber'])) {
        $where[] = $DB->sql_like('u.idnumber', ':idnumber', false);
        $params['idnumber'] = '%' . $DB->sql_like_escape($filters['idnumber']) . '%';
    }

    if (!empty($filters['startdate'])) {
        $where[] = "ci.timecreated >= :startdate";
        $params['startdate'] = $filters['startdate'];
    }

    if (!empty($filters['enddate'])) {
        $where[] = "ci.timecreated <= :enddate";
        $params['enddate'] = $filters['enddate'];
    }

    // Secure handling of allowed categories.
    if (!empty($filters['allowed_categories']) && is_array($filters['allowed_categories'])) {
        list($insql, $inparams) = $DB->get_in_or_equal($filters['allowed_categories'], SQL_PARAMS_NAMED, 'allowcat');
        $where[] = "c.category $insql";
        $params = array_merge($params, $inparams);
    }

    $sql .= " WHERE " . implode(' AND ', $where);
    $sql .= " ORDER BY ci.timecreated DESC";

    try {
        return $DB->get_records_sql($sql, $params, $limitfrom, $limitnum);
    } catch (dml_exception $e) {
        debugging('Error in get_certificates_records: ' . $e->getMessage(), DEBUG_DEVELOPER);
        return [];
    }
}

/**
 * Count certificate records matching filters.
 *
 * @param array $filters The filter parameters.
 * @return int Total count.
 */
function block_reports_custom_count_certificates_records($filters) {
    global $DB;

    $params = [];
    $where = ["u.idnumber <> ''", "u.deleted = 0"];

    $sql = "SELECT COUNT(ci.id)
            FROM {customcert_issues} ci
            JOIN {customcert} cert ON ci.customcertid = cert.id
            JOIN {course} c ON c.id = cert.course
            JOIN {user} u ON u.id = ci.userid
            JOIN {course_categories} cc ON c.category = cc.id
            LEFT JOIN {user_info_field} uif ON uif.shortname = :usertypefield
            LEFT JOIN {user_info_data} uid ON uid.userid = u.id AND uid.fieldid = uif.id";

    $params['usertypefield'] = 'user_type';

    if (!empty($filters['category'])) {
        $where[] = "c.category = :category";
        $params['category'] = $filters['category'];
    }

    if (!empty($filters['course'])) {
        $where[] = "c.id = :course";
        $params['course'] = $filters['course'];
    }

    if (!empty($filters['firstname'])) {
        $where[] = $DB->sql_like('u.firstname', ':firstname', false);
        $params['firstname'] = $DB->sql_like_escape($filters['firstname']) . '%';
    }

    if (!empty($filters['lastname'])) {
        $where[] = $DB->sql_like('u.lastname', ':lastname', false);
        $params['lastname'] = $DB->sql_like_escape($filters['lastname']) . '%';
    }

    if (!empty($filters['usertype'])) {
        if ($filters['usertype'] === 'No asignado') {
            $where[] = "(uid.data IS NULL OR uid.data = '' OR uid.data = :usertype)";
        } else {
            $where[] = "uid.data = :usertype";
        }
        $params['usertype'] = $filters['usertype'];
    }

    if (!empty($filters['idnumber'])) {
        $where[] = $DB->sql_like('u.idnumber', ':idnumber', false);
        $params['idnumber'] = '%' . $DB->sql_like_escape($filters['idnumber']) . '%';
    }

    if (!empty($filters['startdate'])) {
        $where[] = "ci.timecreated >= :startdate";
        $params['startdate'] = $filters['startdate'];
    }

    if (!empty($filters['enddate'])) {
        $where[] = "ci.timecreated <= :enddate";
        $params['enddate'] = $filters['enddate'];
    }

    if (!empty($filters['allowed_categories']) && is_array($filters['allowed_categories'])) {
        list($insql, $inparams) = $DB->get_in_or_equal($filters['allowed_categories'], SQL_PARAMS_NAMED, 'allowcat');
        $where[] = "c.category $insql";
        $params = array_merge($params, $inparams);
    }

    $sql .= " WHERE " . implode(' AND ', $where);

    try {
        return $DB->count_records_sql($sql, $params);
    } catch (dml_exception $e) {
        debugging('Error in count_certificates_records: ' . $e->getMessage(), DEBUG_DEVELOPER);
        return 0;
    }
}

/**
 * Get progress records with proper SQL parameterization.
 *
 * @param array $filters The filter parameters.
 * @param int $limitfrom Start of records to return.
 * @param int $limitnum Number of records to return (0 = all).
 * @return array The list of progress records.
 */
function block_reports_custom_get_progress_records($filters, $limitfrom = 0, $limitnum = 0) {
    global $DB;

    $params = [];
    $where = ["u.idnumber <> ''", "u.deleted = 0"];

    // Use database-agnostic concatenation.
    $fullnameconcat = $DB->sql_concat('u.firstname', "' '", 'u.lastname');
    $uniqueidconcat = $DB->sql_concat('gg.id', "'_'", 'gg.userid', "'_'", 'gi.id');

    $sql = "SELECT
                $uniqueidconcat AS unique_id,
                u.id AS userid,
                u.idnumber AS cedula,
                u.username AS usuario,
                u.firstname AS nombre,
                u.lastname AS apellido,
                $fullnameconcat AS nombre_completo,
                u.institution AS clinica,
                u.department AS area,
                cc.name AS categoria,
                c.fullname AS curso,
                gi.itemname AS item,
                gg.finalgrade AS calificacion,
                gg.timemodified AS fechatimestamp,
                COALESCE(uid.data, :defaultusertype) AS user_type
            FROM {grade_grades} gg
            JOIN {grade_items} gi ON gg.itemid = gi.id
            JOIN {course} c ON gi.courseid = c.id
            JOIN {course_categories} cc ON c.category = cc.id
            JOIN {user} u ON gg.userid = u.id
            LEFT JOIN {user_info_field} uif ON uif.shortname = :usertypefield
            LEFT JOIN {user_info_data} uid ON uid.userid = u.id AND uid.fieldid = uif.id";

    $params['defaultusertype'] = 'No asignado';
    $params['usertypefield'] = 'user_type';

    // Apply filters with proper parameterization.
    if (!empty($filters['category'])) {
        $where[] = "c.category = :category";
        $params['category'] = $filters['category'];
    }

    if (!empty($filters['course'])) {
        $where[] = "c.id = :course";
        $params['course'] = $filters['course'];
    }

    if (!empty($filters['firstname'])) {
        $where[] = $DB->sql_like('u.firstname', ':firstname', false);
        $params['firstname'] = $DB->sql_like_escape($filters['firstname']) . '%';
    }

    if (!empty($filters['lastname'])) {
        $where[] = $DB->sql_like('u.lastname', ':lastname', false);
        $params['lastname'] = $DB->sql_like_escape($filters['lastname']) . '%';
    }

    if (!empty($filters['usertype'])) {
        if ($filters['usertype'] === 'No asignado') {
            $where[] = "(uid.data IS NULL OR uid.data = '' OR uid.data = :usertype)";
        } else {
            $where[] = "uid.data = :usertype";
        }
        $params['usertype'] = $filters['usertype'];
    }

    if (!empty($filters['idnumber'])) {
        $where[] = $DB->sql_like('u.idnumber', ':idnumber', false);
        $params['idnumber'] = '%' . $DB->sql_like_escape($filters['idnumber']) . '%';
    }

    if (!empty($filters['startdate'])) {
        $where[] = "gg.timemodified >= :startdate";
        $params['startdate'] = $filters['startdate'];
    }

    if (!empty($filters['enddate'])) {
        $where[] = "gg.timemodified <= :enddate";
        $params['enddate'] = $filters['enddate'];
    }

    // Secure handling of allowed categories.
    if (!empty($filters['allowed_categories']) && is_array($filters['allowed_categories'])) {
        list($insql, $inparams) = $DB->get_in_or_equal($filters['allowed_categories'], SQL_PARAMS_NAMED, 'allowcat');
        $where[] = "c.category $insql";
        $params = array_merge($params, $inparams);
    }

    $sql .= " WHERE " . implode(' AND ', $where);
    $sql .= " ORDER BY gg.timemodified DESC";

    try {
        return $DB->get_records_sql($sql, $params, $limitfrom, $limitnum);
    } catch (dml_exception $e) {
        debugging('Error in get_progress_records: ' . $e->getMessage(), DEBUG_DEVELOPER);
        return [];
    }
}

/**
 * Count progress records matching filters.
 *
 * @param array $filters The filter parameters.
 * @return int Total count.
 */
function block_reports_custom_count_progress_records($filters) {
    global $DB;

    $params = [];
    $where = ["u.idnumber <> ''", "u.deleted = 0"];

    $sql = "SELECT COUNT(gg.id)
            FROM {grade_grades} gg
            JOIN {grade_items} gi ON gg.itemid = gi.id
            JOIN {course} c ON gi.courseid = c.id
            JOIN {course_categories} cc ON c.category = cc.id
            JOIN {user} u ON gg.userid = u.id
            LEFT JOIN {user_info_field} uif ON uif.shortname = :usertypefield
            LEFT JOIN {user_info_data} uid ON uid.userid = u.id AND uid.fieldid = uif.id";

    $params['usertypefield'] = 'user_type';

    if (!empty($filters['category'])) {
        $where[] = "c.category = :category";
        $params['category'] = $filters['category'];
    }

    if (!empty($filters['course'])) {
        $where[] = "c.id = :course";
        $params['course'] = $filters['course'];
    }

    if (!empty($filters['firstname'])) {
        $where[] = $DB->sql_like('u.firstname', ':firstname', false);
        $params['firstname'] = $DB->sql_like_escape($filters['firstname']) . '%';
    }

    if (!empty($filters['lastname'])) {
        $where[] = $DB->sql_like('u.lastname', ':lastname', false);
        $params['lastname'] = $DB->sql_like_escape($filters['lastname']) . '%';
    }

    if (!empty($filters['usertype'])) {
        if ($filters['usertype'] === 'No asignado') {
            $where[] = "(uid.data IS NULL OR uid.data = '' OR uid.data = :usertype)";
        } else {
            $where[] = "uid.data = :usertype";
        }
        $params['usertype'] = $filters['usertype'];
    }

    if (!empty($filters['idnumber'])) {
        $where[] = $DB->sql_like('u.idnumber', ':idnumber', false);
        $params['idnumber'] = '%' . $DB->sql_like_escape($filters['idnumber']) . '%';
    }

    if (!empty($filters['startdate'])) {
        $where[] = "gg.timemodified >= :startdate";
        $params['startdate'] = $filters['startdate'];
    }

    if (!empty($filters['enddate'])) {
        $where[] = "gg.timemodified <= :enddate";
        $params['enddate'] = $filters['enddate'];
    }

    if (!empty($filters['allowed_categories']) && is_array($filters['allowed_categories'])) {
        list($insql, $inparams) = $DB->get_in_or_equal($filters['allowed_categories'], SQL_PARAMS_NAMED, 'allowcat');
        $where[] = "c.category $insql";
        $params = array_merge($params, $inparams);
    }

    $sql .= " WHERE " . implode(' AND ', $where);

    try {
        return $DB->count_records_sql($sql, $params);
    } catch (dml_exception $e) {
        debugging('Error in count_progress_records: ' . $e->getMessage(), DEBUG_DEVELOPER);
        return 0;
    }
}

/**
 * Format a Unix timestamp as a human-readable date.
 *
 * @param int|null $timestamp Unix timestamp.
 * @param string $format Date format (default: 'd/m/Y H:i').
 * @return string Formatted date or empty string if null.
 */
function block_reports_custom_format_date($timestamp, $format = 'd/m/Y H:i') {
    if (empty($timestamp)) {
        return '';
    }
    return userdate($timestamp, get_string('strftimedatetime', 'langconfig'));
}

/**
 * Check if the customcert plugin is installed.
 *
 * @return bool True if customcert is installed.
 */
function block_reports_custom_is_customcert_installed() {
    global $DB;
    return $DB->get_manager()->table_exists('customcert');
}

/**
 * Get users enrolled in courses with optional filters.
 *
 * @param int $courseid Course ID (0 for all).
 * @param int $categoryid Category ID (0 for all).
 * @param string $usertype User type filter.
 * @param array|null $allowedcategories Allowed categories or null for all.
 * @return array List of users.
 */
function block_reports_custom_get_enrolled_users($courseid = 0, $categoryid = 0, $usertype = '', $allowedcategories = null) {
    global $DB;

    $params = [];
    $where = ["u.deleted = 0"];

    $fullnameconcat = $DB->sql_concat('u.firstname', "' '", 'u.lastname');

    $sql = "SELECT DISTINCT u.id, $fullnameconcat AS fullname
            FROM {user} u
            JOIN {user_enrolments} ue ON ue.userid = u.id
            JOIN {enrol} e ON e.id = ue.enrolid
            JOIN {course} c ON c.id = e.courseid";

    if (!empty($usertype)) {
        $sql .= " LEFT JOIN {user_info_data} uid ON uid.userid = u.id
                  LEFT JOIN {user_info_field} uif ON uid.fieldid = uif.id AND uif.shortname = :fieldname";
        $params['fieldname'] = 'user_type';

        if ($usertype === 'No asignado') {
            $where[] = "(uid.data IS NULL OR uid.data = '' OR uid.data = :usertype)";
        } else {
            $where[] = "uid.data = :usertype";
        }
        $params['usertype'] = $usertype;
    }

    if (!empty($courseid)) {
        $where[] = "e.courseid = :courseid";
        $params['courseid'] = $courseid;
    } elseif (!empty($categoryid)) {
        $where[] = "c.category = :categoryid";
        $params['categoryid'] = $categoryid;
    }

    // Secure handling of allowed categories.
    if ($allowedcategories !== null && !empty($allowedcategories)) {
        list($insql, $inparams) = $DB->get_in_or_equal($allowedcategories, SQL_PARAMS_NAMED, 'allowcat');
        $where[] = "c.category $insql";
        $params = array_merge($params, $inparams);
    }

    $sql .= " WHERE " . implode(' AND ', $where);
    $sql .= " ORDER BY fullname ASC";

    try {
        return $DB->get_records_sql($sql, $params);
    } catch (dml_exception $e) {
        debugging('Error in get_enrolled_users: ' . $e->getMessage(), DEBUG_DEVELOPER);
        return [];
    }
}

// Legacy function wrappers for backwards compatibility.

/**
 * @deprecated Use block_reports_custom_export_to_spreadsheet instead.
 */
function export_to_spreadsheet($headers, $rows, $filename, $format, $sheetname = 'Sheet1') {
    debugging('export_to_spreadsheet() is deprecated. Use block_reports_custom_export_to_spreadsheet() instead.', DEBUG_DEVELOPER);
    return block_reports_custom_export_to_spreadsheet($headers, $rows, $filename, $format, $sheetname);
}

/**
 * @deprecated Use block_reports_custom_export_to_csv instead.
 */
function export_to_csv($headers, $rows, $filename) {
    debugging('export_to_csv() is deprecated. Use block_reports_custom_export_to_csv() instead.', DEBUG_DEVELOPER);
    return block_reports_custom_export_to_csv($headers, $rows, $filename);
}

/**
 * @deprecated Use block_reports_custom_get_category_path instead.
 */
function get_category_path($categoryid, $DB = null) {
    debugging('get_category_path() is deprecated. Use block_reports_custom_get_category_path() instead.', DEBUG_DEVELOPER);
    return block_reports_custom_get_category_path($categoryid);
}

/**
 * @deprecated Use block_reports_custom_get_all_categories instead.
 */
function get_all_categories($DB = null) {
    debugging('get_all_categories() is deprecated. Use block_reports_custom_get_all_categories() instead.', DEBUG_DEVELOPER);
    return block_reports_custom_get_all_categories();
}

/**
 * @deprecated Use block_reports_custom_get_courses_by_category instead.
 */
function get_courses_by_category($categoryid, $DB = null) {
    debugging('get_courses_by_category() is deprecated. Use block_reports_custom_get_courses_by_category() instead.', DEBUG_DEVELOPER);
    return block_reports_custom_get_courses_by_category($categoryid);
}

/**
 * @deprecated Use block_reports_custom_get_user_types instead.
 */
function get_user_types($DB = null) {
    debugging('get_user_types() is deprecated. Use block_reports_custom_get_user_types() instead.', DEBUG_DEVELOPER);
    return block_reports_custom_get_user_types();
}

/**
 * @deprecated Use block_reports_custom_get_allowed_categories_for_user instead.
 */
function get_allowed_categories_for_user($userid) {
    debugging('get_allowed_categories_for_user() is deprecated. Use block_reports_custom_get_allowed_categories_for_user() instead.', DEBUG_DEVELOPER);
    return block_reports_custom_get_allowed_categories_for_user($userid);
}

/**
 * @deprecated Use block_reports_custom_get_certificates_records instead.
 */
function get_certificates_records($params, $DB = null) {
    debugging('get_certificates_records() is deprecated. Use block_reports_custom_get_certificates_records() instead.', DEBUG_DEVELOPER);
    // Convert old format to new format.
    $filters = $params;
    if (!empty($params['allowed_categories']) && is_string($params['allowed_categories'])) {
        $filters['allowed_categories'] = array_filter(array_map('intval', explode(',', $params['allowed_categories'])));
    }
    return block_reports_custom_get_certificates_records($filters);
}

/**
 * @deprecated Use block_reports_custom_get_progress_records instead.
 */
function get_progress_records($params, $DB = null) {
    debugging('get_progress_records() is deprecated. Use block_reports_custom_get_progress_records() instead.', DEBUG_DEVELOPER);
    // Convert old format to new format.
    $filters = $params;
    if (!empty($params['allowed_categories']) && is_string($params['allowed_categories'])) {
        $filters['allowed_categories'] = array_filter(array_map('intval', explode(',', $params['allowed_categories'])));
    }
    return block_reports_custom_get_progress_records($filters);
}
