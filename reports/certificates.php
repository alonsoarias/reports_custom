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

$params = [];
$sql = "SELECT
            CerGene.id AS uniqueid,
            usua.idnumber AS cedula,
            usua.firstname AS nombres,
            usua.lastname AS apellidos,
            usua.institution AS clinica,
            usua.department AS area,
            Curso.fullname AS nombrecurso,
            FROM_UNIXTIME(CerGene.timecreated) AS fecha,
            CatCurso.name AS categoriacurso
        FROM
            {customcert} AS CusCert
        JOIN
            {course} AS Curso ON Curso.id = CusCert.course
        JOIN
            {customcert_issues} CerGene ON CerGene.customcertid = CusCert.id
        JOIN
            {user} usua ON usua.id = CerGene.userid
        JOIN
            {course_categories} CatCurso ON Curso.category = CatCurso.id
        WHERE
            usua.idnumber <> ''";

if ($category) {
    $sql .= " AND CatCurso.id = :category";
    $params['category'] = $category;
}
if ($course) {
    $sql .= " AND Curso.id = :course";
    $params['course'] = $course;
}
if ($firstname) {
    $sql .= " AND usua.firstname LIKE :firstname";
    $params['firstname'] = "$firstname%";
}
if ($lastname) {
    $sql .= " AND usua.lastname LIKE :lastname";
    $params['lastname'] = "$lastname%";
}

$records = $DB->get_records_sql($sql, $params);

// Datos del reporte
$data = new stdClass();
$data->tabhead = ['Cedula', 'Nombres', 'Apellidos', 'Clinica', 'Area', 'NombreCurso', 'Fecha', 'CategoriaCurso'];
$data->table = [];
foreach ($records as $record) {
    $data->table[] = [
        $record->cedula,
        $record->nombres,
        $record->apellidos,
        $record->clinica,
        $record->area,
        $record->nombrecurso,
        $record->fecha,
        $record->categoriacurso
    ];
}

if (optional_param('download', '', PARAM_TEXT)) {
    if ($format === 'csv') {
        attendance_exporttocsv($data, 'certificates_report');
    } else {
        attendance_exporttotableed($data, 'certificates_report', $format);
    }
    exit;
}

$PAGE->set_url(new moodle_url('/blocks/reports_custom/reports/certificates.php'));
$PAGE->set_context($context);
$PAGE->set_title('Certificates Report');
$PAGE->set_heading('Certificates Report');
$PAGE->requires->jquery();
$PAGE->requires->js(new moodle_url('/blocks/reports_custom/reports/certificates.js'));
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
    'Nombres',
    'Apellidos',
    'Clinica',
    'Area',
    'NombreCurso',
    'Fecha',
    'CategoriaCurso'
];

foreach ($records as $record) {
    $table->data[] = [
        $record->cedula,
        $record->nombres,
        $record->apellidos,
        $record->clinica,
        $record->area,
        $record->nombrecurso,
        $record->fecha,
        $record->categoriacurso
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

$baseurl = new moodle_url('/blocks/reports_custom/reports/certificates.php', [
    'category' => $category,
    'course' => $course,
    'firstname' => $firstname,
    'lastname' => $lastname
]);
echo $OUTPUT->paging_bar($totalcount, $page, $perpage, $baseurl);

echo '</div>';
echo $OUTPUT->footer();
?>
