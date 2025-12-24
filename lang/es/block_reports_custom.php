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
 * Spanish language strings for block_reports_custom.
 *
 * @package    block_reports_custom
 * @copyright  2025 Alonso Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Nombre del Plugin.
$string['pluginname'] = 'Bloque de Informes Personalizados';
$string['reports_custom'] = 'Informes Personalizados';

// Títulos de Informes.
$string['certificates_report'] = 'Certificados Generados por Cursos';
$string['progress_report'] = 'Cursos - Avance por usuario detallado - Calificación y fecha';

// Capacidades.
$string['reports_custom:addinstance'] = 'Agregar un nuevo bloque de Informes Personalizados';
$string['reports_custom:myaddinstance'] = 'Agregar un nuevo bloque de Informes Personalizados al Área personal';
$string['reports_custom:view'] = 'Ver el bloque de Informes Personalizados';
$string['reports_custom:viewreports'] = 'Ver los informes personalizados';

// Encabezados de Tablas.
$string['header_cedula'] = 'Cédula';
$string['header_nombres'] = 'Nombres';
$string['header_apellidos'] = 'Apellidos';
$string['header_clinica'] = 'Clínica';
$string['header_area'] = 'Área';
$string['header_nombrecurso'] = 'Nombre del Curso';
$string['header_fecha'] = 'Fecha';
$string['header_categoriacurso'] = 'Categoría del Curso';
$string['header_user_type'] = 'Tipo de Usuario';
$string['header_usuario'] = 'Usuario';
$string['header_nombre'] = 'Nombre';
$string['header_apellido'] = 'Apellido';
$string['header_nombre_completo'] = 'Nombre Completo';
$string['header_categoria'] = 'Categoría';
$string['header_curso'] = 'Curso';
$string['header_item'] = 'Ítem';
$string['header_calificacion'] = 'Calificación';

// Opciones de Formulario.
$string['option_todos'] = 'Todos';
$string['option_category'] = 'Categoría';
$string['option_course'] = 'Curso';
$string['option_usertype'] = 'Tipo de Usuario';
$string['option_nombre'] = 'Nombre';
$string['option_apellido'] = 'Apellido(s)';
$string['option_download_format'] = 'Formato de descarga';
$string['option_download_excel'] = 'Excel';
$string['option_download_ods'] = 'ODS';
$string['option_download_csv'] = 'CSV';

// Otros.
$string['report_title'] = 'Informe';
$string['report_heading'] = 'Informe';
$string['btn_descargar'] = 'Descargar';
$string['alphabet_all'] = 'Todos';
$string['total_records'] = 'Total de Registros';
$string['idnumber'] = 'Número de Identificación';
$string['start_date'] = 'Fecha de Inicio';
$string['end_date'] = 'Fecha de Fin';
$string['records_per_page'] = 'Registros por página';
$string['show_records'] = 'Mostrar';

// Configuración.
$string['settings_restrictions_heading'] = 'Restricciones de Rol-Categoría';
$string['settings_restrictions_desc'] = 'Configure qué roles están restringidos a ver solo ciertas categorías de cursos en los informes.';
$string['settings_role_category_mappings'] = 'Mapeo de Roles a Categorías';
$string['settings_role_category_mappings_desc'] = 'Ingrese un mapeo por línea en el formato: id_rol:id_categoria1,id_categoria2<br>Ejemplo:<br>11:72,73<br>12:74<br><br>Los usuarios con estos roles solo verán datos de las categorías especificadas. Los usuarios sin ninguno de estos roles no tendrán restricciones.';
$string['settings_reports_heading'] = 'Configuración de Informes';
$string['settings_reports_desc'] = 'Configure los ajustes generales de los informes.';
$string['settings_records_per_page'] = 'Registros por página';
$string['settings_records_per_page_desc'] = 'Número de registros a mostrar por página en los informes.';
$string['settings_user_type_field'] = 'Campo de tipo de usuario';
$string['settings_user_type_field_desc'] = 'El nombre corto del campo de perfil de usuario que contiene el tipo de usuario.';
$string['settings_unassigned_label'] = 'Etiqueta de no asignado';
$string['settings_unassigned_label_desc'] = 'Etiqueta a mostrar cuando un usuario no tiene tipo de usuario asignado.';

// Errores.
$string['customcert_not_installed'] = 'El plugin de Certificados Personalizados es requerido pero no está instalado.';

// Privacidad.
$string['privacy:metadata'] = 'El bloque de Informes Personalizados no almacena ningún dato personal. Solo muestra datos de otros componentes de Moodle.';
