<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Block reports_custom class definition.
 *
 * @package    block_reports_custom
 * @copyright  2024 Your Organization
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Block reports_custom class.
 *
 * Provides links to custom reports for certificates and user progress.
 *
 * @package    block_reports_custom
 * @copyright  2024 Your Organization
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_reports_custom extends block_base {

    /**
     * Initialize the block.
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_reports_custom');
    }

    /**
     * Allow the block title to be customized.
     *
     * @return void
     */
    public function specialization() {
        if (!empty($this->config->title)) {
            $this->title = format_string($this->config->title);
        }
    }

    /**
     * Get the block content.
     *
     * @return stdClass|null The block content.
     */
    public function get_content() {
        global $OUTPUT;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->text = '';
        $this->content->footer = '';

        // Check if user has permission to view the block.
        $context = context_block::instance($this->instance->id);
        if (!has_capability('block/reports_custom:view', $context)) {
            return $this->content;
        }

        // Build the report links.
        $reports = [];

        // Certificates report link.
        $certificatesurl = new moodle_url('/blocks/reports_custom/reports/certificates.php');
        $reports[] = html_writer::link(
            $certificatesurl,
            $OUTPUT->pix_icon('i/report', '') . ' ' . get_string('certificates_report', 'block_reports_custom'),
            ['class' => 'btn btn-outline-primary btn-block mb-2']
        );

        // Progress report link.
        $progressurl = new moodle_url('/blocks/reports_custom/reports/progress.php');
        $reports[] = html_writer::link(
            $progressurl,
            $OUTPUT->pix_icon('i/grades', '') . ' ' . get_string('progress_report', 'block_reports_custom'),
            ['class' => 'btn btn-outline-primary btn-block mb-2']
        );

        $this->content->text = html_writer::div(implode('', $reports), 'block-reports-custom-links');

        return $this->content;
    }

    /**
     * Allow multiple instances of this block.
     *
     * @return bool
     */
    public function instance_allow_multiple() {
        return true;
    }

    /**
     * This block has global configuration.
     *
     * @return bool
     */
    public function has_config() {
        return true;
    }

    /**
     * Locations where block can be displayed.
     *
     * @return array
     */
    public function applicable_formats() {
        return [
            'all' => false,
            'site' => true,
            'site-index' => true,
            'course-view' => true,
            'my' => true,
        ];
    }

    /**
     * Allow block configuration per instance.
     *
     * @return bool
     */
    public function instance_allow_config() {
        return true;
    }
}
