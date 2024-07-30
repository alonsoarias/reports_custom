<?php

require_once("$CFG->libdir/excellib.class.php");
require_once("$CFG->libdir/odslib.class.php");
require_once("$CFG->libdir/csvlib.class.php");

/**
 * Export report to .xlsx or .ods format.
 *
 * @param stdClass $data The data for the report.
 * @param string $filename The base name of the file without extension.
 * @param string $format The format of the export: 'excel' or 'ods'.
 */
function attendance_exporttotableed($data, $filename, $format) {
    global $CFG;

    debugging("Starting attendance_exporttotableed function", DEBUG_DEVELOPER);

    // Clean the output buffer to fix the headers already sent error
    while (ob_get_level()) {
        ob_end_clean();
    }

    if ($format === 'excel') {
        debugging("Loading Excel library", DEBUG_DEVELOPER);
        require_once($CFG->libdir.'/excellib.class.php');
        $filename .= ".xlsx";
        $workbook = new MoodleExcelWorkbook("-");
    } else {
        debugging("Loading ODS library", DEBUG_DEVELOPER);
        require_once($CFG->libdir.'/odslib.class.php');
        $filename .= ".ods";
        $workbook = new MoodleODSWorkbook("-");
    }

    debugging("Sending workbook headers for $filename", DEBUG_DEVELOPER);
    $workbook->send($filename);

    $sheettitle = get_string('report', 'block_reports_custom');
    $worksheet = $workbook->add_worksheet($sheettitle);

    // Define the format for the header
    $formatbc = $workbook->add_format(array('bold' => 1));
    $headers = $data->tabhead;

    // Write the headers
    debugging("Writing headers", DEBUG_DEVELOPER);
    $col = 0;
    foreach ($headers as $header) {
        $worksheet->write(0, $col++, $header, $formatbc);
    }

    // Write the data
    debugging("Writing data", DEBUG_DEVELOPER);
    $row = 1;
    foreach ($data->table as $record) {
        $col = 0;
        foreach ($record as $value) {
            $worksheet->write($row, $col++, $value);
        }
        $row++;
    }

    debugging("Closing workbook", DEBUG_DEVELOPER);
    $workbook->close();
    debugging("Workbook closed successfully", DEBUG_DEVELOPER);
}

/**
 * Export report to .txt format.
 *
 * @param stdClass $data The data for the report.
 * @param string $filename The base name of the file.
 */
function attendance_exporttocsv($data, $filename) {
    debugging("Starting attendance_exporttocsv function", DEBUG_DEVELOPER);

    $filename .= ".csv";

    // Prevent the error "headers already sent"
    while (ob_get_level()) {
        ob_end_clean();
    }
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

    // Output headers
    debugging("Writing CSV headers", DEBUG_DEVELOPER);
    fputcsv($output, $data->tabhead);

    // Output data
    debugging("Writing CSV data", DEBUG_DEVELOPER);
    foreach ($data->table as $record) {
        fputcsv($output, $record);
    }

    fclose($output);
    debugging("CSV file generated successfully", DEBUG_DEVELOPER);
}

?>
