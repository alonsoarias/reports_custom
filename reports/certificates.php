<?php
require_once('../../../config.php');
require_login();
$context = context_system::instance();
require_capability('moodle/site:config', $context);

$PAGE->set_url(new moodle_url('/blocks/reports_custom/reports/certificates.php'));
$PAGE->set_context($context);
$PAGE->set_title('Certificates Report');
$PAGE->set_heading('Certificates Report');
$PAGE->requires->jquery();
$PAGE->requires->js(new moodle_url('/blocks/reports_custom/reports/certificates.js'));

// Parámetros para paginación
$perpage = 100; // Número de filas por página
$page = optional_param('page', 0, PARAM_INT); // Página actual

// Obtener parámetros de filtros
$category = optional_param('category', '', PARAM_INT);
$course = optional_param('course', '', PARAM_INT);
$firstname = optional_param('firstname', '', PARAM_TEXT);
$lastname = optional_param('lastname', '', PARAM_TEXT);

echo $OUTPUT->header();

// Formulario de filtros
echo '<form id="filtersForm" method="GET" class="form-inline mb-3">';
echo '<div class="form-group mr-2">';
echo '<label for="category" class="mr-2">Category:</label>';
echo '<select id="category" name="category" class="form-control">';
echo '<option value="">All</option>';
$categories = $DB->get_records('course_categories');
foreach ($categories as $categoryObj) {
    $selected = $category == $categoryObj->id ? 'selected' : '';
    echo '<option value="'.$categoryObj->id.'" '.$selected.'>'.$categoryObj->name.'</option>';
}
echo '</select>';
echo '</div>';

echo '<div class="form-group mr-2">';
echo '<label for="course" class="mr-2">Course:</label>';
echo '<select id="course" name="course" class="form-control">';
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

// Filtros de nombre y apellido usando la estructura de menú alfabético
echo '<div class="form-group mr-2">';
echo '<label for="firstname" class="mr-2">Nombre:</label>';
echo '<div class="alphabet-filter" data-filter="firstname">';
echo '<a href="#" data-letter="">Todos</a>';
foreach (range('A', 'Z') as $letter) {
    echo '<a href="#" data-letter="'.$letter.'">'.$letter.'</a>';
}
echo '</div>';
echo '</div>';

echo '<div class="form-group mr-2">';
echo '<label for="lastname" class="mr-2">Apellido(s):</label>';
echo '<div class="alphabet-filter" data-filter="lastname">';
echo '<a href="#" data-letter="">Todos</a>';
foreach (range('A', 'Z') as $letter) {
    echo '<a href="#" data-letter="'.$letter.'">'.$letter.'</a>';
}
echo '</div>';
echo '</div>';

echo '</form>';

echo '<div id="reportData">';

// Consulta SQL
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

// Obtener total de registros
$totalcount = $DB->count_records_sql("SELECT COUNT(*) FROM ($sql) AS total", $params);

// Obtener registros con limitación y desplazamiento
$records = $DB->get_records_sql($sql, $params, $page * $perpage, $perpage);

if (optional_param('downloadxls', '', PARAM_TEXT)) {
    // Generar el archivo XLS
    require_once($CFG->libdir . '/excellib.class.php');
    $filename = 'certificates_report_' . date('Ymd_His') . '.xls';
    $workbook = new MoodleExcelWorkbook("-");
    $workbook->send($filename);
    $worksheet = $workbook->add_worksheet('Certificates Report');

    // Encabezados
    $headers = ['Cedula', 'Nombres', 'Apellidos', 'Clinica', 'Area', 'NombreCurso', 'Fecha', 'CategoriaCurso'];
    $col = 0;
    foreach ($headers as $header) {
        $worksheet->write(0, $col++, $header);
    }

    // Datos
    $row = 1;
    foreach ($records as $record) {
        $worksheet->write($row, 0, $record->cedula);
        $worksheet->write($row, 1, $record->nombres);
        $worksheet->write($row, 2, $record->apellidos);
        $worksheet->write($row, 3, $record->clinica);
        $worksheet->write($row, 4, $record->area);
        $worksheet->write($row, 5, $record->nombrecurso);
        $worksheet->write($row, 6, $record->fecha);
        $worksheet->write($row, 7, $record->categoriacurso);
        $row++;
    }

    $workbook->close();
    exit;
}

// Generar tabla
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

// Paginación
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
