<?php
class block_reports_custom extends block_base {
    public function init() {
        // Establece el título del bloque, que se muestra en la página
        $this->title = get_string('reports_custom', 'block_reports_custom');
    }

    public function get_content() {
        global $USER, $PAGE;

        // Verifica si el contenido ya está definido para evitar reconstruirlo
        if ($this->content !== null) {
            return $this->content;
        }

        // Creación de un objeto estándar para almacenar el contenido del bloque
        $this->content = new stdClass;
        $this->content->text = ''; // Inicializa el contenido de texto como vacío

        // Define los enlaces a los reportes específicos
        $urlcertificates = new moodle_url('/blocks/reports_custom/reports/certificates.php');
        $urlprogress = new moodle_url('/blocks/reports_custom/reports/progress.php');

        // Obtén el contexto del bloque, necesario para comprobar las capacidades
        $context = context_system::instance();

        // Comprobar si el usuario tiene permiso para ver los reportes
        if (!has_capability('block/reports_custom:viewreports', $context)) {
            // Si el usuario no tiene la capacidad necesaria, devuelve el contenido vacío
            return $this->content;
        }

        // Si el usuario tiene permisos, añade los enlaces al contenido del bloque
        $this->content->text = html_writer::link($urlcertificates, get_string('certificates_report', 'block_reports_custom')) . '<br>';
        $this->content->text .= html_writer::link($urlprogress, get_string('progress_report', 'block_reports_custom'));

        return $this->content;
    }

    public function applicable_formats() {
        // Define en qué formatos de página es aplicable el bloque
        return array(
            'site' => true,         // Página principal
            'my' => true,           // Página Mi Moodle
            'course-view' => true,  // Páginas de curso
            'mod' => false,         // Módulos de curso, no aplicable
            'mod-quiz' => false     // Quizzes específicamente, no aplicable
        );
    }

    public function instance_allow_multiple() {
        // Permite múltiples instancias del bloque en una sola página
        return true;
    }

    public function has_config() {
        // Indica que el bloque tiene una página de configuración
        return true;
    }
}
