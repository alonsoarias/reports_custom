<?php

require_once("$CFG->libdir/excellib.class.php");
require_once("$CFG->libdir/odslib.class.php");
require_once("$CFG->libdir/csvlib.class.php");

/**
 * Export data to .xlsx or .ods format.
 *
 * @param array $headers The headers for the data.
 * @param array $rows The rows of data.
 * @param string $filename The base name of the file without extension.
 * @param string $format The format of the export: 'excel' or 'ods'.
 * @param string $sheetname The name of the worksheet.
 */
function export_to_spreadsheet($headers, $rows, $filename, $format, $sheetname = 'Sheet1')
{
    global $CFG;

    // Clean the output buffer to fix the headers already sent error
    while (ob_get_level()) {
        ob_end_clean();
    }

    if ($format === 'excel') {
        $filename .= ".xlsx";
        $workbook = new MoodleExcelWorkbook("-");
    } else {
        $filename .= ".ods";
        $workbook = new MoodleODSWorkbook("-");
    }

    // Sending HTTP headers.
    $workbook->send($filename);

    // Creating the worksheet.
    $worksheet = $workbook->add_worksheet($sheetname);

    // Define the format for the header.
    $formatbc = $workbook->add_format(array('bold' => 1));

    // Write the headers.
    $col = 0;
    foreach ($headers as $header) {
        $worksheet->write(0, $col++, $header, $formatbc);
    }

    // Write the data.
    $row = 1;
    foreach ($rows as $record) {
        $col = 0;
        foreach ($record as $value) {
            $worksheet->write($row, $col++, $value);
        }
        $row++;
    }

    $workbook->close();
}

/**
 * Export data to .csv format.
 *
 * @param array $headers The headers for the data.
 * @param array $rows The rows of data.
 * @param string $filename The base name of the file.
 */
function export_to_csv($headers, $rows, $filename)
{
    // Clean the output buffer to fix the headers already sent error
    while (ob_get_level()) {
        ob_end_clean();
    }

    $filename .= ".csv";

    header("Content-Type: text/csv; charset=utf-8");
    header("Content-Disposition: attachment; filename=\"$filename\"");
    header("Expires: 0");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Pragma: public");

    $output = fopen('php://output', 'w');
    if ($output === false) {
        debugging("Failed to open output stream", DEBUG_DEVELOPER);
        return;
    }

    // Output headers.
    fputcsv($output, $headers);

    // Output data.
    foreach ($rows as $record) {
        fputcsv($output, $record);
    }

    fclose($output);
}

/**
 * Get the full category path name.
 *
 * @param int $categoryId The ID of the category.
 * @param object $DB The database object.
 * @return string The full path name of the category.
 */
function get_category_path($categoryId, $DB)
{
    $categoryPath = $DB->get_record('course_categories', array('id' => $categoryId), 'path');
    $fullPathNames = [];
    if ($categoryPath) {
        $pathIds = explode('/', trim($categoryPath->path, '/'));
        foreach ($pathIds as $pathId) {
            $categoryName = $DB->get_record('course_categories', array('id' => $pathId), 'name');
            if ($categoryName) {
                $fullPathNames[] = $categoryName->name;
            }
        }
    }
    return implode(' / ', $fullPathNames);
}

/**
 * Get all categories.
 *
 * @param object $DB The database object.
 * @return array The list of categories.
 */
function get_all_categories($DB)
{
    return $DB->get_records('course_categories');
}

/**
 * Get courses by category.
 *
 * @param int $categoryId The ID of the category.
 * @param object $DB The database object.
 * @return array The list of courses.
 */
function get_courses_by_category($categoryId, $DB)
{
    if ($categoryId) {
        return $DB->get_records('course', array('category' => $categoryId), 'fullname ASC', 'id, fullname');
    } else {
        return $DB->get_records('course', null, 'fullname ASC', 'id, fullname');
    }
}

/**
 * Get distinct user types.
 *
 * @param object $DB The database object.
 * @return array The list of user types.
 */
function get_user_types($DB)
{
    return $DB->get_records_sql("SELECT DISTINCT d1.data AS usertype FROM {user_info_data} d1 JOIN {user_info_field} f1 ON d1.fieldid = f1.id WHERE f1.shortname = 'user_type'");
}

/**
 * Get progress records.
 *
 * @param array $params The parameters for the query.
 * @param object $DB The database object.
 * @return array The list of progress records.
 */
function get_progress_records($params, $DB)
{
    $sql = "SELECT
                gg.id AS uniqueid,
                u.idnumber AS cedula,
                u.username AS usuario,
                u.firstname AS nombre, 
                u.lastname AS apellido, 
                CONCAT(u.firstname, ' ', u.lastname) AS nombre_completo, 
                u.institution AS clinica,
                u.department AS area,
                cc.name AS categoria,
                c.shortname AS curso,
                CASE 
                    WHEN gi.itemtype = 'course' THEN c.fullname
                    ELSE gi.itemname
                END AS item,
                ROUND(gg.finalgrade, 2) AS calificacion,
                FROM_UNIXTIME(gg.timemodified) AS fecha,
                COALESCE(d1.data, 'No asignado') AS user_type
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
            JOIN
                {user_info_data} d1 ON d1.userid = u.id
            JOIN
                {user_info_field} f1 ON d1.fieldid = f1.id AND f1.shortname = 'user_type'
            WHERE 
                gi.courseid = c.id";

    if (!empty($params['category'])) {
        $sql .= " AND cc.id = :category";
    }
    if (!empty($params['course'])) {
        $sql .= " AND c.id = :course";
    }
    if (!empty($params['firstname'])) {
        $sql .= " AND u.firstname LIKE :firstname";
        $params['firstname'] = $params['firstname'] . "%";
    }
    if (!empty($params['lastname'])) {
        $sql .= " AND u.lastname LIKE :lastname";
        $params['lastname'] = $params['lastname'] . "%";
    }
    if (!empty($params['usertype'])) {
        $sql .= " AND d1.data = :usertype";
    }

return $DB->get_records_sql($sql, $params);
}

/**
 * Get certificate records.
 *
 * @param array $params The parameters for the query.
 * @param object $DB The database object.
 * @return array The list of certificate records.
 */
function get_certificates_records($params, $DB) {
    $sql = "SELECT
                CerGene.id AS uniqueid,
                usua.idnumber AS cedula,
                usua.firstname AS nombres,
                usua.lastname AS apellidos,
                usua.institution AS clinica,
                usua.department AS area,
                Curso.fullname AS nombrecurso,
                FROM_UNIXTIME(CerGene.timecreated) AS fecha,
                CatCurso.name AS categoriacurso,
                COALESCE(d1.data, 'No asignado') AS user_type
            FROM
                {customcert_issues} AS CerGene
            JOIN
                {customcert} AS CusCert ON CerGene.customcertid = CusCert.id
            JOIN
                {course} AS Curso ON Curso.id = CusCert.course
            JOIN
                {user} AS usua ON usua.id = CerGene.userid
            JOIN
                {course_categories} AS CatCurso ON Curso.category = CatCurso.id
            JOIN
                {user_info_data} d1 ON d1.userid = usua.id
            JOIN
                {user_info_field} f1 ON d1.fieldid = f1.id AND f1.shortname = 'user_type'
            WHERE
                usua.idnumber <> ''";

    if (!empty($params['category'])) {
        $sql .= " AND Curso.category = :category";
    }
    if (!empty($params['course'])) {
        $sql .= " AND Curso.id = :course";
    }
    if (!empty($params['firstname'])) {
        $sql .= " AND usua.firstname LIKE :firstname";
        $params['firstname'] = $params['firstname'] . "%";
    }
    if (!empty($params['lastname'])) {
        $sql .= " AND usua.lastname LIKE :lastname";
        $params['lastname'] = $params['lastname'] . "%";
    }
    if (!empty($params['usertype'])) {
        $sql .= " AND d1.data = :usertype";
    }

    return $DB->get_records_sql($sql, $params);
}
