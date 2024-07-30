<?php
/**
 * Generate worksheet for report export
 *
 * @param stdClass $data The data for the report
 * @param string $filename The name of the file
 * @param string $format excel|ods
 *
 */
function attendance_exporttotableed($data, $filename, $format)
{
    global $CFG;

    if ($format === 'excel') {
        require_once("$CFG->libdir/excellib.class.php");
        $filename .= ".xls";
        $workbook = new MoodleExcelWorkbook("-");
    } else {
        require_once("$CFG->libdir/odslib.class.php");
        $filename .= ".ods";
        $workbook = new MoodleODSWorkbook("-");
    }
    // Sending HTTP headers.
    $workbook->send($filename);
    // Creating the first worksheet.
    $myxls = $workbook->add_worksheet('Report');
    // Format types.
    $formatbc = $workbook->add_format();
    $formatbc->set_bold(1);

    // Writing headers.
    $i = 0;
    foreach ($data->tabhead as $j => $header) {
        $myxls->write($i, $j, $header, $formatbc);
    }

    // Writing data.
    $i = 1;
    foreach ($data->table as $row) {
        foreach ($row as $j => $cell) {
            $myxls->write($i, $j, $cell);
        }
        $i++;
    }
    $workbook->close();
}

/**
 * Generate csv for report export
 *
 * @param stdClass $data The data for the report
 * @param string $filename The name of the file
 *
 */
function attendance_exporttocsv($data, $filename)
{
    $filename .= ".csv";

    header("Content-Type: text/csv; charset=utf-8");
    header("Content-Disposition: attachment; filename=\"$filename\"");
    header("Expires: 0");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Pragma: public");

    $output = fopen('php://output', 'w');
    // Output headers.
    fputcsv($output, $data->tabhead);

    // Output data.
    foreach ($data->table as $row) {
        fputcsv($output, $row);
    }

    fclose($output);
}
