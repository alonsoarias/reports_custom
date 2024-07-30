<?php
require_once('../../../config.php');
require_login();
$context = context_system::instance();
require_capability('moodle/site:config', $context);

$PAGE->set_url(new moodle_url('/blocks/reports_custom/reports/progress.php'));
$PAGE->set_context($context);
$PAGE->set_title('Progress Report');
$PAGE->set_heading('Progress Report');
$PAGE->requires->jquery();
$PAGE->requires->js(new moodle_url('/blocks/reports_custom/reports/progress.js'));

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

// Obtener total de registros
$totalcount = $DB->count_records_sql("SELECT COUNT(*) FROM ($sql) AS total", $params);

// Obtener registros con limitación y desplazamiento
$records = $DB->get_records_sql($sql, $params, $page * $perpage, $perpage);

if (optional_param('downloadxls', '', PARAM_TEXT)) {
    // Generar el archivo XLS
    require_once($CFG->libdir . '/excellib.class.php');
    $filename = 'progress_report_' . date('Ymd_His') . '.xls';
    $workbook = new MoodleExcelWorkbook("-");
    $workbook->send($filename);
    $worksheet = $workbook->add_worksheet('Progress Report');

    // Encabezados
    $headers = ['Cedula', 'Usuario', 'Nombre', 'Apellido', 'Nombre Completo', 'Clinica', 'Area', 'Categoria', 'Curso', 'Item', 'Calificacion', 'Fecha'];
    $col = 0;
    foreach ($headers as $header) {
        $worksheet->write(0, $col++, $header);
    }

    // Datos
    $row = 1;
    foreach ($records as $record) {
        $worksheet->write($row, 0, $record->cedula);
        $worksheet->write($row, 1, $record->usuario);
        $worksheet->write($row, 2, $record->nombre);
        $worksheet->write($row, 3, $record->apellido);
        $worksheet->write($row, 4, $record->nombre_completo);
        $worksheet->write($row, 5, $record->clinica);
        $worksheet->write($row, 6, $record->area);
        $worksheet->write($row, 7, $record->categoria);
        $worksheet->write($row, 8, $record->curso);
        $worksheet->write($row, 9, $record->item);
        $worksheet->write($row, 10, $record->calificacion);
        $worksheet->write($row, 11, $record->fecha);
        $row++;
    }

    $workbook->close();
    exit;
}

// Generar tabla
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

// Paginación
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
