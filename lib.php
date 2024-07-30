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

    if ($format === 'excel') {
        $filename .= ".xlsx";
        $workbook = new MoodleExcelWorkbook("-");
    } else {
        $filename .= ".ods";
        $workbook = new MoodleODSWorkbook("-");
    }

    ob_clean(); // Clean the output buffer to fix the headers already sent error
    $workbook->send($filename);

    $sheettitle = get_string('report', 'block_reports_custom');
    $worksheet = $workbook->add_worksheet($sheettitle);

    // Define the format for the header
    $formatbc = $workbook->add_format(array('bold' => 1));
    $headers = $data->tabhead;

    // Write the headers
    $col = 0;
    foreach ($headers as $header) {
        $worksheet->write(0, $col++, $header, $formatbc);
    }

    // Write the data
    $row = 1;
    foreach ($data->table as $record) {
        $col = 0;
        foreach ($record as $value) {
            $worksheet->write($row, $col++, $value);
        }
        $row++;
    }

    $workbook->close();
    exit;
}

/**
 * Export report to .txt format.
 *
 * @param stdClass $data The data for the report.
 * @param string $filename The base name of the file.
 */
function attendance_exporttocsv($data, $filename) {
    $filename .= ".txt";

    // Prevent the error "headers already sent"
    ob_clean();
    header("Content-Type: text/plain");
    header("Content-Disposition: attachment; filename=\"$filename\"");

    $headers = $data->tabhead;

    echo implode("\t", $headers) . "\n";

    foreach ($data->table as $record) {
        echo implode("\t", $record) . "\n";
    }

    exit;
}

?>
