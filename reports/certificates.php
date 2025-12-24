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
 * Certificates report page for block_reports_custom.
 *
 * @package    block_reports_custom
 * @copyright  2025 Alonso Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once($CFG->dirroot . '/blocks/reports_custom/lib.php');

// Require login and capability check.
require_login();
$context = context_system::instance();
require_capability('block/reports_custom:viewreports', $context);

// Check if customcert plugin is installed.
if (!block_reports_custom_is_customcert_installed()) {
    throw new moodle_exception('customcert_not_installed', 'block_reports_custom');
}

// Get filter parameters with proper sanitization.
$category = optional_param('category', 0, PARAM_INT);
$course = optional_param('course', 0, PARAM_INT);
$firstname = optional_param('firstname', '', PARAM_ALPHA);
$lastname = optional_param('lastname', '', PARAM_ALPHA);
$usertype = optional_param('usertype', '', PARAM_TEXT);
$idnumber = optional_param('idnumber', '', PARAM_TEXT);
$startdate = optional_param('startdate', '', PARAM_TEXT);
$enddate = optional_param('enddate', '', PARAM_TEXT);
$format = optional_param('format', 'excel', PARAM_ALPHA);
$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', 100, PARAM_INT);
$download = optional_param('download', '', PARAM_TEXT);

// Validate perpage value.
$validperpageoptions = [10, 25, 50, 100, 200, 500];
if (!in_array($perpage, $validperpageoptions)) {
    $perpage = 100;
}

// Get allowed categories for current user.
$allowedcategories = block_reports_custom_get_allowed_categories_for_user($USER->id);

// Prepare filters array.
$filters = [
    'category' => $category,
    'course' => $course,
    'firstname' => $firstname,
    'lastname' => $lastname,
    'usertype' => $usertype,
    'idnumber' => $idnumber,
    'startdate' => !empty($startdate) ? strtotime($startdate) : null,
    'enddate' => !empty($enddate) ? strtotime($enddate . ' 23:59:59') : null,
    'allowed_categories' => $allowedcategories,
];

// Remove null/empty values for cleaner processing.
$filters = array_filter($filters, function($value) {
    return $value !== null && $value !== '' && $value !== 0;
});

// Re-add allowed_categories if it was set (even if empty array).
if ($allowedcategories !== null) {
    $filters['allowed_categories'] = $allowedcategories;
}

// Get table headers.
$headers = [
    get_string('header_cedula', 'block_reports_custom'),
    get_string('header_nombres', 'block_reports_custom'),
    get_string('header_apellidos', 'block_reports_custom'),
    get_string('header_clinica', 'block_reports_custom'),
    get_string('header_area', 'block_reports_custom'),
    get_string('header_nombrecurso', 'block_reports_custom'),
    get_string('header_fecha', 'block_reports_custom'),
    get_string('header_categoriacurso', 'block_reports_custom'),
    get_string('header_user_type', 'block_reports_custom'),
];

// Handle download request.
if (!empty($download)) {
    // Get all records for export.
    $records = block_reports_custom_get_certificates_records($filters);

    $rows = [];
    foreach ($records as $record) {
        $rows[] = [
            $record->cedula,
            $record->nombres,
            $record->apellidos,
            $record->clinica,
            $record->area,
            $record->nombrecurso,
            block_reports_custom_format_date($record->fechatimestamp),
            $record->categoriacurso,
            $record->user_type,
        ];
    }

    // Export based on format.
    if ($format === 'csv') {
        block_reports_custom_export_to_csv($headers, $rows, 'certificates_report');
    } else {
        block_reports_custom_export_to_spreadsheet(
            $headers,
            $rows,
            'certificates_report',
            $format,
            get_string('certificates_report', 'block_reports_custom')
        );
    }
    exit;
}

// Page setup.
$PAGE->set_url(new moodle_url('/blocks/reports_custom/reports/certificates.php'));
$PAGE->set_context($context);
$PAGE->set_title(get_string('certificates_report', 'block_reports_custom'));
$PAGE->set_heading(get_string('certificates_report', 'block_reports_custom'));
$PAGE->set_pagelayout('report');

// Include AMD JavaScript module.
$PAGE->requires->js_call_amd('block_reports_custom/certificates', 'init', [[
    'formSelector' => '#filtersForm',
    'reportContainerSelector' => '#reportData',
]]);

// Get total count and paginated records.
$totalcount = block_reports_custom_count_certificates_records($filters);
$records = block_reports_custom_get_certificates_records($filters, $page * $perpage, $perpage);

// Output page.
echo $OUTPUT->header();

// Render filter form.
echo '<form id="filtersForm" method="GET" class="mb-3">';
echo '<div class="card"><div class="card-body">';

// Row 1: Category and Course.
echo '<div class="form-row">';

// Category filter.
echo '<div class="form-group col-md-4">';
echo '<label for="category">' . get_string('option_category', 'block_reports_custom') . '</label>';
echo '<select id="category" name="category" class="form-control">';
echo '<option value="">' . get_string('option_todos', 'block_reports_custom') . '</option>';
$categories = block_reports_custom_get_all_categories($allowedcategories);
foreach ($categories as $cat) {
    $selected = ($category == $cat->id) ? 'selected' : '';
    $catpath = block_reports_custom_get_category_path($cat->id);
    echo '<option value="' . s($cat->id) . '" ' . $selected . '>' . s($catpath) . '</option>';
}
echo '</select>';
echo '</div>';

// Course filter.
echo '<div class="form-group col-md-4">';
echo '<label for="course">' . get_string('option_course', 'block_reports_custom') . '</label>';
echo '<select id="course" name="course" class="form-control">';
echo '<option value="">' . get_string('option_todos', 'block_reports_custom') . '</option>';
$courses = block_reports_custom_get_courses_by_category($category, $allowedcategories);
foreach ($courses as $c) {
    $selected = ($course == $c->id) ? 'selected' : '';
    echo '<option value="' . s($c->id) . '" ' . $selected . '>' . s($c->fullname) . '</option>';
}
echo '</select>';
echo '</div>';

// User type filter.
echo '<div class="form-group col-md-4">';
echo '<label for="usertype">' . get_string('option_usertype', 'block_reports_custom') . '</label>';
echo '<select id="usertype" name="usertype" class="form-control">';
echo '<option value="">' . get_string('option_todos', 'block_reports_custom') . '</option>';
$usertypes = block_reports_custom_get_user_types();
foreach ($usertypes as $type) {
    $selected = ($usertype == $type->usertype) ? 'selected' : '';
    echo '<option value="' . s($type->usertype) . '" ' . $selected . '>' . s($type->usertype) . '</option>';
}
echo '</select>';
echo '</div>';

echo '</div>'; // End form-row.

// Row 2: ID number, dates and records per page.
echo '<div class="form-row">';

echo '<div class="form-group col-md-3">';
echo '<label for="idnumber">' . get_string('idnumber', 'block_reports_custom') . '</label>';
echo '<input type="text" id="idnumber" name="idnumber" value="' . s($idnumber) . '" class="form-control">';
echo '</div>';

echo '<div class="form-group col-md-3">';
echo '<label for="startdate">' . get_string('start_date', 'block_reports_custom') . '</label>';
echo '<input type="date" id="startdate" name="startdate" value="' . s($startdate) . '" class="form-control">';
echo '</div>';

echo '<div class="form-group col-md-3">';
echo '<label for="enddate">' . get_string('end_date', 'block_reports_custom') . '</label>';
echo '<input type="date" id="enddate" name="enddate" value="' . s($enddate) . '" class="form-control">';
echo '</div>';

// Records per page selector.
echo '<div class="form-group col-md-3">';
echo '<label for="perpage">' . get_string('records_per_page', 'block_reports_custom') . '</label>';
echo '<select id="perpage" name="perpage" class="form-control">';
foreach ($validperpageoptions as $option) {
    $selected = ($perpage == $option) ? ' selected' : '';
    echo '<option value="' . $option . '"' . $selected . '>' . $option . '</option>';
}
echo '</select>';
echo '</div>';

echo '</div>'; // End form-row.

// Row 3: Alphabet filters.
echo '<div class="form-row">';

// First name alphabet filter.
echo '<div class="form-group col-md-6">';
echo '<label>' . get_string('option_nombre', 'block_reports_custom') . '</label>';
echo '<div class="alphabet-filter d-flex flex-wrap" data-filter="firstname">';
echo '<a href="#" class="btn btn-outline-secondary btn-sm m-1' . (empty($firstname) ? ' active' : '') . '" data-letter="">';
echo get_string('alphabet_all', 'block_reports_custom') . '</a>';
foreach (range('A', 'Z') as $letter) {
    $active = ($firstname === $letter) ? ' active' : '';
    echo '<a href="#" class="btn btn-outline-secondary btn-sm m-1' . $active . '" data-letter="' . $letter . '">' . $letter . '</a>';
}
echo '</div>';
echo '<input type="hidden" name="firstname" id="firstname" value="' . s($firstname) . '">';
echo '</div>';

// Last name alphabet filter.
echo '<div class="form-group col-md-6">';
echo '<label>' . get_string('option_apellido', 'block_reports_custom') . '</label>';
echo '<div class="alphabet-filter d-flex flex-wrap" data-filter="lastname">';
echo '<a href="#" class="btn btn-outline-secondary btn-sm m-1' . (empty($lastname) ? ' active' : '') . '" data-letter="">';
echo get_string('alphabet_all', 'block_reports_custom') . '</a>';
foreach (range('A', 'Z') as $letter) {
    $active = ($lastname === $letter) ? ' active' : '';
    echo '<a href="#" class="btn btn-outline-secondary btn-sm m-1' . $active . '" data-letter="' . $letter . '">' . $letter . '</a>';
}
echo '</div>';
echo '<input type="hidden" name="lastname" id="lastname" value="' . s($lastname) . '">';
echo '</div>';

echo '</div>'; // End form-row.

echo '</div></div>'; // End card.
echo '</form>';

// Report data section.
echo '<div id="reportData">';

// Build table.
$table = new html_table();
$table->head = $headers;
$table->attributes['class'] = 'table table-striped table-hover';
$table->data = [];

foreach ($records as $record) {
    $table->data[] = [
        s($record->cedula),
        s($record->nombres),
        s($record->apellidos),
        s($record->clinica),
        s($record->area),
        s($record->nombrecurso),
        block_reports_custom_format_date($record->fechatimestamp),
        s($record->categoriacurso),
        s($record->user_type),
    ];
}

echo html_writer::table($table);

// Total records count.
echo '<div class="mt-3 mb-3">';
echo '<strong>' . get_string('total_records', 'block_reports_custom') . ': ' . $totalcount . '</strong>';
echo '</div>';

// Download form.
echo '<form id="downloadForm" method="GET" class="form-inline mb-3">';
echo '<input type="hidden" name="category" value="' . s($category) . '">';
echo '<input type="hidden" name="course" value="' . s($course) . '">';
echo '<input type="hidden" name="firstname" value="' . s($firstname) . '">';
echo '<input type="hidden" name="lastname" value="' . s($lastname) . '">';
echo '<input type="hidden" name="usertype" value="' . s($usertype) . '">';
echo '<input type="hidden" name="idnumber" value="' . s($idnumber) . '">';
echo '<input type="hidden" name="startdate" value="' . s($startdate) . '">';
echo '<input type="hidden" name="enddate" value="' . s($enddate) . '">';
echo '<input type="hidden" name="perpage" value="' . s($perpage) . '">';

echo '<div class="form-group mr-2">';
echo '<label for="format" class="mr-2">' . get_string('option_download_format', 'block_reports_custom') . ':</label>';
echo '<select id="format" name="format" class="form-control">';
echo '<option value="excel"' . ($format === 'excel' ? ' selected' : '') . '>' . get_string('option_download_excel', 'block_reports_custom') . '</option>';
echo '<option value="ods"' . ($format === 'ods' ? ' selected' : '') . '>' . get_string('option_download_ods', 'block_reports_custom') . '</option>';
echo '<option value="csv"' . ($format === 'csv' ? ' selected' : '') . '>' . get_string('option_download_csv', 'block_reports_custom') . '</option>';
echo '</select>';
echo '</div>';

echo '<button type="submit" name="download" value="1" class="btn btn-primary">';
echo get_string('btn_descargar', 'block_reports_custom');
echo '</button>';
echo '</form>';

// Pagination.
$baseurl = new moodle_url('/blocks/reports_custom/reports/certificates.php', [
    'category' => $category,
    'course' => $course,
    'firstname' => $firstname,
    'lastname' => $lastname,
    'usertype' => $usertype,
    'idnumber' => $idnumber,
    'startdate' => $startdate,
    'enddate' => $enddate,
    'perpage' => $perpage,
]);
echo $OUTPUT->paging_bar($totalcount, $page, $perpage, $baseurl);

echo '</div>'; // End reportData.

echo $OUTPUT->footer();
