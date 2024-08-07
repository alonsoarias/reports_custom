<?php
require_once('../../../config.php');
require_once('../lib.php');
require_login();
@ini_set('memory_limit', '2048M');
@set_time_limit(600);

$context = context_system::instance();
require_capability('moodle/site:config', $context);

$category = optional_param('category', '', PARAM_INT);
$course = optional_param('course', '', PARAM_INT);
$firstname = optional_param('firstname', '', PARAM_TEXT);
$lastname = optional_param('lastname', '', PARAM_TEXT);
$usertype = optional_param('usertype', '', PARAM_TEXT); // New parameter for user type
$format = optional_param('format', 'excel', PARAM_TEXT);

$params = [
    'category' => $category,
    'course' => $course,
    'firstname' => $firstname,
    'lastname' => $lastname,
    'usertype' => $usertype
];

$records = get_progress_records($params, $DB);

$data = new stdClass();
$data->tabhead = ['Cedula', 'Usuario', 'Nombre', 'Apellido', 'Nombre Completo', 'Clinica', 'Area', 'Categoria', 'Curso', 'Item', 'Calificacion', 'Fecha', 'User Type'];
$data->table = [];
foreach ($records as $record) {
    $data->table[] = [
        $record->cedula,
        $record->usuario,
        $record->nombre,
        $record->apellido,
        $record->nombre_completo,
        $record->clinica,
        $record->area,
        $record->categoria,
        $record->curso,
        $record->item,
        $record->calificacion,
        $record->fecha,
        $record->user_type
    ];
}

if (optional_param('download', '', PARAM_TEXT)) {
    // Clean the output buffer to fix the headers already sent error
    while (ob_get_level()) {
        ob_end_clean();
    }

    if ($format === 'csv') {
        export_to_csv($data->tabhead, $data->table, 'progress_report');
    } else {
        export_to_spreadsheet($data->tabhead, $data->table, 'progress_report', $format, 'Progress Report');
    }
    exit;
}

$PAGE->set_url(new moodle_url('/blocks/reports_custom/reports/progress.php'));
$PAGE->set_context($context);
$PAGE->set_title('Progress Report');
$PAGE->set_heading('Progress Report');
$PAGE->requires->jquery();
$PAGE->requires->js(new moodle_url('/blocks/reports_custom/reports/progress.js'));
$PAGE->requires->css(new moodle_url('/blocks/reports_custom/reports/styles.css'));

$perpage = 100;
$page = optional_param('page', 0, PARAM_INT);

echo $OUTPUT->header();

echo '<form id="filtersForm" method="GET" class="form-inline mb-3">';
echo '<div class="form-row align-items-center">';
echo '<div class="col-auto">';
echo '<label for="category" class="mr-2">Category:</label>';
echo '<select id="category" name="category" class="form-control mb-2">';
echo '<option value="">All</option>';
$categories = get_all_categories($DB);
foreach ($categories as $categoryObj) {
    $selected = $category == $categoryObj->id ? 'selected' : '';
    $categoryPath = get_category_path($categoryObj->id, $DB);
    echo '<option value="' . $categoryObj->id . '" ' . $selected . '>' . $categoryPath . '</option>';
}
echo '</select>';
echo '</div>';

echo '<div class="col-auto">';
echo '<label for="course" class="mr-2">Course:</label>';
echo '<select id="course" name="course" class="form-control mb-2">';
echo '<option value="">All</option>';
$courses = get_courses_by_category($category, $DB);
foreach ($courses as $courseObj) {
    $selected = $course == $courseObj->id ? 'selected' : '';
    echo '<option value="' . $courseObj->id . '" ' . $selected . '>' . $courseObj->fullname . '</option>';
}
echo '</select>';
echo '</div>';
echo '</div>';

echo '<div class="col-auto">';
echo '<label for="usertype" class="mr-2">User Type:</label>';
echo '<select id="usertype" name="usertype" class="form-control mb-2">';
echo '<option value="">All</option>';
$userTypes = get_user_types($DB);
foreach ($userTypes as $type) {
    $selected = $usertype == $type->usertype ? 'selected' : '';
    echo '<option value="'.$type->usertype.'" '.$selected.'>'.$type->usertype.'</option>';
}
echo '</select>';
echo '</div>';
echo '</div>';


echo '<div class="form-row align-items-center">';
echo '<div class="col-auto">';
echo '<label for="firstname" class="mr-2">Nombre:</label>';
echo '<div class="alphabet-filter d-flex mb-2" data-filter="firstname">';
echo '<a href="#" class="btn btn-outline-secondary btn-sm mr-1" data-letter="">Todos</a>';
foreach (range('A', 'Z') as $letter) {
    $active = $firstname == $letter ? 'active' : '';
    echo '<a href="#" class="btn btn-outline-secondary btn-sm mr-1 ' . $active . '" data-letter="' . $letter . '">' . $letter . '</a>';
}
echo '</div>';
echo '<input type="hidden" name="firstname" value="' . $firstname . '">';
echo '</div>';

echo '<div class="col-auto">';
echo '<label for="lastname" class="mr-2">Apellido(s):</label>';
echo '<div class="alphabet-filter d-flex mb-2" data-filter="lastname">';
echo '<a href="#" class="btn btn-outline-secondary btn-sm mr-1" data-letter="">Todos</a>';
foreach (range('A', 'Z') as $letter) {
    $active = $lastname == $letter ? 'active' : '';
    echo '<a href="#" class="btn btn-outline-secondary btn-sm mr-1 ' . $active . '" data-letter="' . $letter . '">' . $letter . '</a>';
}
echo '</div>';
echo '<input type="hidden" name="lastname" value="' . $lastname . '">';
echo '</div>';
echo '</div>';

echo '</form>';

echo '<div id="reportData">';

$totalcount = count($records);
$records = array_slice($records, $page * $perpage, $perpage);

$table = new html_table();
$table->head = [
    'Cedula',
    'Usuario',
    'Nombre',
    'Apellido',
    'Nombre Completo',
    'Clinica',
    'Area',
    'Categoria',
    'Curso',
    'Item',
    'Calificacion',
    'Fecha',
    'User Type'
];

foreach ($records as $record) {
    $table->data[] = [
        $record->cedula,
        $record->usuario,
        $record->nombre,
        $record->apellido,
        $record->nombre_completo,
        $record->clinica,
        $record->area,
        $record->categoria,
        $record->curso,
        $record->item,
        $record->calificacion,
        $record->fecha,
        $record->user_type
    ];
}

echo html_writer::table($table);

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

$baseurl = new moodle_url('/blocks/reports_custom/reports/progress.php', [
    'category' => $category,
    'course' => $course,
    'firstname' => $firstname,
    'lastname' => $lastname,
    'usertype' => $usertype // Added to the URL for paging
]);
echo $OUTPUT->paging_bar($totalcount, $page, $perpage, $baseurl);

echo '</div>';
echo $OUTPUT->footer();
?>
