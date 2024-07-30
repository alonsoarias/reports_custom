<?php
class block_reports_custom extends block_base {
    public function init() {
        $this->title = get_string('reports_custom', 'block_reports_custom');
    }

    public function get_content() {
        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass;
        $urlcertificates = new moodle_url('/blocks/reports_custom/reports/certificates.php');
        $urlprogress = new moodle_url('/blocks/reports_custom/reports/progress.php');
        $this->content->text = html_writer::link($urlcertificates, get_string('certificates_report', 'block_reports_custom')).'<br>';
        $this->content->text .= html_writer::link($urlprogress, get_string('progress_report', 'block_reports_custom'));

        return $this->content;
    }

    public function applicable_formats() {
        return array(
            'site' => true,
            'my' => true,
            'course-view' => true,
            'mod' => false,
            'mod-quiz' => false
        );
    }

    public function instance_allow_multiple() {
        return true;
    }

    public function has_config() {
        return true;
    }
}
