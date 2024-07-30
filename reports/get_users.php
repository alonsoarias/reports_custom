<?php
require_once('../../../config.php');
require_login();

$courseid = optional_param('course', 0, PARAM_INT);
$categoryid = optional_param('category', 0, PARAM_INT);

if ($courseid) {
    $users = $DB->get_records_sql(
        "SELECT DISTINCT u.id, CONCAT(u.firstname, ' ', u.lastname) AS fullname
         FROM {user} u
         JOIN {user_enrolments} ue ON ue.userid = u.id
         JOIN {enrol} e ON e.id = ue.enrolid
         WHERE e.courseid = ?", array($courseid)
    );
} elseif ($categoryid) {
    $users = $DB->get_records_sql(
        "SELECT DISTINCT u.id, CONCAT(u.firstname, ' ', u.lastname) AS fullname
         FROM {user} u
         JOIN {user_enrolments} ue ON ue.userid = u.id
         JOIN {enrol} e ON e.id = ue.enrolid
         JOIN {course} c ON c.id = e.courseid
         WHERE c.category = ?", array($categoryid)
    );
}

echo json_encode(array_values($users));
?>
