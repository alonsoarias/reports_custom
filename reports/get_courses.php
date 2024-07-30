<?php
require_once('../../../config.php');
require_login();

$categoryid = required_param('category', PARAM_INT);
$courses = $DB->get_records('course', array('category' => $categoryid), 'fullname ASC', 'id, fullname');

echo json_encode(array_values($courses));
?>
