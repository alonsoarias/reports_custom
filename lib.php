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
function export_to_spreadsheet($headers, $rows, $filename, $format, $sheetname = 'Sheet1') {
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
function export_to_csv($headers, $rows, $filename) {
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
?>
