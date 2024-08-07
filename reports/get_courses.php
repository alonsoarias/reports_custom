<?php
require_once('../../../config.php');
require_login();

$categoryid = optional_param('category', 0, PARAM_INT); // Cambiado a optional_param con valor por defecto 0

if ($categoryid) {
    // Si se seleccionó una categoría, obtener cursos de esa categoría
    $courses = $DB->get_records('course', array('category' => $categoryid), 'fullname ASC', 'id, fullname');
} else {
    // Si no se seleccionó categoría, obtener todos los cursos
    $courses = $DB->get_records('course', null, 'fullname ASC', 'id, fullname');
}

echo json_encode(array_values($courses));
?>
