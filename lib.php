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
 * @package qtype_lti
 * @author Amr Hourani amr.hourani@id.ethz.ch
 * @copyright ETHz 2016 amr.hourani@id.ethz.ch
 */
defined('MOODLE_INTERNAL') || die();

/**
 * Checks file/image access for lti questions.
 *
 * @category files
 *
 * @param stdClass $course        course object
 * @param stdClass $cm            course module object
 * @param stdClass $context       context object
 * @param string   $filearea      file area
 * @param array    $args          extra arguments
 * @param bool     $forcedownload whether or not force download
 * @param array    $options       additional options affecting the file serving
 *
 * @return bool
 */
function qtype_lti_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload,
        array $options = array()) {
    global $CFG;
    require_once($CFG->libdir.'/questionlib.php');
    question_pluginfile($course, $context, 'qtype_lti', $filearea, $args, $forcedownload,
    $options);
}

/**
 * Returns available Basic LTI types
 *
 * @return array of basicLTI types
 */
function qtype_lti_get_lti_types() {
    global $DB;
    return $DB->get_records('qtype_lti_types', null, 'state DESC, timemodified DESC');
}
/**
 * Returns available Basic LTI types that match the given
 * tool proxy id
 *
 * @param int $toolproxyid Tool proxy id
 * @return array of basicLTI types
 */
function qtype_lti_get_lti_types_from_proxy_id($toolproxyid) {
    global $DB;
    return $DB->get_records('qtype_lti_types', array('toolproxyid' => $toolproxyid), 'state DESC, timemodified DESC');
}

