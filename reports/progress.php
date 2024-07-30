<?php
require_once('../../../config.php');
require_once('../lib.php');  // Asegúrate de incluir el archivo con las funciones de exportación.
require_login();
$context = context_system::instance();
require_capability('moodle/site:config', $context);

$category = optional_param('category', '', PARAM_INT);
$course = optional_param('course', '', PARAM_INT);
$firstname = optional_param('firstname', '', PARAM_TEXT);
$lastname = optional_param('lastname', '', PARAM_TEXT);
$format = optional_param('format', 'excel', PARAM_TEXT); // Nuevo parámetro para el formato de descarga

debugging("Starting progress.php script", DEBUG_DEVELOPER);

$params = [];
$sql = "SELECT
            gg.id AS uniqueid,
            u.idnumber AS cedula,
            u.username AS usuario,
            u.firstname AS nombre, 
            u.lastname AS apellido, 
            CONCAT(u.firstname,' ',u.lastname) AS nombre_completo, 
            u.institution AS clinica,
            u.department AS area,
            cc.name AS categoria,
            c.shortname AS curso,
            CASE 
                WHEN gi.itemtype = 'course' 
                THEN c.fullname
                ELSE gi.itemname
            END AS item,
            ROUND(gg.finalgrade, 2) AS calificacion,
            FROM_UNIXTIME(gg.timemodified) AS fecha
        FROM 
            {course} AS c
        JOIN 
            {context} AS ctx ON c.id = ctx.instanceid
        JOIN 
            {role_assignments} AS ra ON ra.contextid = ctx.id
        JOIN 
            {user} AS u ON u.id = ra.userid
        JOIN 
            {grade_grades} AS gg ON gg.userid = u.id
        JOIN 
            {grade_items} AS gi ON gi.id = gg.itemid
        JOIN 
            {course_categories} AS cc ON cc.id = c.category
        WHERE 
            gi.courseid = c.id";

if ($category) {
    $sql .= " AND cc.id = :category";
    $params['category'] = $category;
}
if ($course) {
    $sql .= " AND c.id = :course";
    $params['course'] = $course;
}
if ($firstname) {
    $sql .= " AND u.firstname LIKE :firstname";
    $params['firstname'] = "$firstname%";
}
if ($lastname) {
    $sql .= " AND u.lastname LIKE :lastname";
    $params['lastname'] = "$lastname%";
}

debugging("SQL Query: $sql", DEBUG_DEVELOPER);
debugging("Params: " . json_encode($params), DEBUG_DEVELOPER);

$records = $DB->get_records_sql($sql, $params);

debugging("Number of records fetched: " . count($records), DEBUG_DEVELOPER);

// Datos del reporte
$data = new stdClass();
$data->tabhead = ['Cedula', 'Usuario', 'Nombre', 'Apellido', 'Nombre Completo', 'Clinica', 'Area', 'Categoria', 'Curso', 'Item', 'Calificacion', 'Fecha'];
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
        $record->fecha
    ];
}

if (optional_param('download', '', PARAM_TEXT)) {
    debugging("Download request detected, format: $format", DEBUG_DEVELOPER);
    if ($format === 'csv') {
        attendance_exporttocsv($data, 'progress_report');
    } else {
        attendance_exporttotableed($data, 'progress_report', $format);
    }
    exit;
}

$PAGE->set_url(new moodle_url('/blocks/reports_custom/reports/progress.php'));
$PAGE->set_context($context);
$PAGE->set_title('Progress Report');
$PAGE->set_heading('Progress Report');
$PAGE->requires->jquery();
$PAGE->requires->js(new moodle_url('/blocks/reports_custom/reports/progress.js'));
$PAGE->requires->css(new moodle_url('/blocks/reports_custom/reports/styles.css')); // Para incluir estilos adicionales si es necesario

$perpage = 100; 
$page = optional_param('page', 0, PARAM_INT); 

echo $OUTPUT->header();

echo '<form id="filtersForm" method="GET" class="form-inline mb-3">';
echo '<div class="form-row align-items-center">';
echo '<div class="col-auto">';
echo '<label for="category" class="mr-2">Category:</label>';
echo '<select id="category" name="category" class="form-control mb-2">';
echo '<option value="">All</option>';
$categories = $DB->get_records('course_categories');
foreach ($categories as $categoryObj) {
    $selected = $category == $categoryObj->id ? 'selected' : '';
    echo '<option value="'.$categoryObj->id.'" '.$selected.'>'.$categoryObj->name.'</option>';
}
echo '</select>';
echo '</div>';

echo '<div class="col-auto">';
echo '<label for="course" class="mr-2">Course:</label>';
echo '<select id="course" name="course" class="form-control mb-2">';
echo '<option value="">All</option>';
if ($category) {
    $courses = $DB->get_records('course', array('category' => $category), 'fullname ASC', 'id, fullname');
    foreach ($courses as $courseObj) {
        $selected = $course == $courseObj->id ? 'selected' : '';
        echo '<option value="'.$courseObj->id.'" '.$selected.'>'.$courseObj->fullname.'</option>';
    }
}
echo '</select>';
echo '</div>';
echo '</div>'; // Cierra form-row

echo '<div class="form-row align-items-center">';
echo '<div class="col-auto">';
echo '<label for="firstname" class="mr-2">Nombre:</label>';
echo '<div class="alphabet-filter d-flex mb-2" data-filter="firstname">';
echo '<a href="#" class="btn btn-outline-secondary btn-sm mr-1" data-letter="">Todos</a>';
foreach (range('A', 'Z') as $letter) {
    $active = $firstname == $letter ? 'active' : '';
    echo '<a href="#" class="btn btn-outline-secondary btn-sm mr-1 '.$active.'" data-letter="'.$letter.'">'.$letter.'</a>';
}
echo '</div>';
echo '<input type="hidden" name="firstname" value="'.$firstname.'">';
echo '</div>';

echo '<div class="col-auto">';
echo '<label for="lastname" class="mr-2">Apellido(s):</label>';
echo '<div class="alphabet-filter d-flex mb-2" data-filter="lastname">';
echo '<a href="#" class="btn btn-outline-secondary btn-sm mr-1" data-letter="">Todos</a>';
foreach (range('A', 'Z') as $letter) {
    $active = $lastname == $letter ? 'active' : '';
    echo '<a href="#" class="btn btn-outline-secondary btn-sm mr-1 '.$active.'" data-letter="'.$letter.'">'.$letter.'</a>';
}
echo '</div>';
echo '<input type="hidden" name="lastname" value="'.$lastname.'">';
echo '</div>';
echo '</div>'; // Cierra form-row

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
    'Fecha'
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
        $record->fecha
    ];
}

echo html_writer::table($table);

// ComboBox para seleccionar el formato de descarga
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
    'lastname' => $lastname
]);
echo $OUTPUT->paging_bar($totalcount, $page, $perpage, $baseurl);

echo '</div>';
echo $OUTPUT->footer();
?>
