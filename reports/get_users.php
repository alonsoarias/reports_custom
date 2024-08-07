<?php
require_once('../../../config.php');
require_login();

$courseid = optional_param('course', 0, PARAM_INT);
$categoryid = optional_param('category', 0, PARAM_INT);
$usertype = optional_param('usertype', '', PARAM_TEXT);

$params = [];
$sql = "SELECT DISTINCT u.id, CONCAT(u.firstname, ' ', u.lastname) AS fullname
        FROM {user} u
        JOIN {user_enrolments} ue ON ue.userid = u.id
        JOIN {enrol} e ON e.id = ue.enrolid";

if ($courseid) {
    $sql .= " WHERE e.courseid = :courseid";
    $params['courseid'] = $courseid;
} elseif ($categoryid) {
    $sql .= " JOIN {course} c ON c.id = e.courseid WHERE c.category = :categoryid";
    $params['categoryid'] = $categoryid;
}

if ($usertype) {
    $sql .= " JOIN {user_info_data} d1 ON d1.userid = u.id
              JOIN {user_info_field} f1 ON d1.fieldid = f1.id AND f1.shortname = 'user_type'
              WHERE d1.data = :usertype";
    $params['usertype'] = $usertype;
}

$users = $DB->get_records_sql($sql, $params);

echo json_encode(array_values($users));
?>
