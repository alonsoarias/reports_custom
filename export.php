<?php
require_once('../../config.php');
require_once('lib.php');
require_login();
@ini_set('memory_limit', '512M');
@set_time_limit(300);

$context = context_system::instance();
require_capability('moodle/site:config', $context);

$format = optional_param('format', 'excel', PARAM_TEXT);

$data = new stdClass();
$data->tabhead = ['Cedula', 'Nombres', 'Apellidos', 'Clinica', 'Area', 'NombreCurso', 'Fecha', 'CategoriaCurso'];
$data->table = [
    ['123456789', 'John', 'Doe', 'Clinic 1', 'Area 1', 'Course 1', '2024-07-30', 'Category 1'],
    ['987654321', 'Jane', 'Smith', 'Clinic 2', 'Area 2', 'Course 2', '2024-07-30', 'Category 2']
];

if (optional_param('download', '', PARAM_TEXT)) {
    while (ob_get_level()) {
        ob_end_clean();
    }
    if ($format === 'csv') {
        attendance_exporttocsv($data, 'test_report');
    } else {
        attendance_exporttotableed($data, 'test_report', $format);
    }
    exit;
}

$PAGE->set_url(new moodle_url('/blocks/reports_custom/export.php'));
$PAGE->set_context($context);
$PAGE->set_title('Export Test');
$PAGE->set_heading('Export Test');
$PAGE->requires->jquery();
$PAGE->requires->css(new moodle_url('/blocks/reports_custom/reports/styles.css'));

echo $OUTPUT->header();

echo '<form id="downloadForm" method="GET">';
echo '<div class="form-group">';
echo '<label for="format" class="mr-2">Formato de descarga:</label>';
echo '<select id="format" name="format" class="form-control d-inline w-auto">';
echo '<option value="excel" ' . ($format === 'excel' ? 'selected' : '') . '>Excel</option>';
echo '<option value="ods" ' . ($format === 'ods' ? 'selected' : '') . '>ODS</option>';
echo '<option value="csv" ' . ($format === 'csv' ? 'selected' : '') . '>CSV</option>';
echo '</select>';
echo '<button type="submit" name="download" value="1" class="btn btn-primary ml-2">Descargar</button>';
echo '</div>';
echo '</form>';

echo $OUTPUT->footer();
?>
