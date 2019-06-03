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
 *
 * @package qtype_lti
 * @author Amr Hourani amr.hourani@id.ethz.ch
 * @copyright ETHz 2016 amr.hourani@id.ethz.ch
 */
defined('MOODLE_INTERNAL') || die();

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
 * @param int $toolproxyid
 *        Tool proxy id
 * @return array of basicLTI types
 */
function qtype_lti_get_lti_types_from_proxy_id($toolproxyid) {
    global $DB;
    return $DB->get_records('qtype_lti_types', array('toolproxyid' => $toolproxyid), 'state DESC, timemodified DESC');
}

/**
 * Defines custom file provider for downloading backup from remote site.
 *
 * @param stdClass $course
 *        the course object
 * @param stdClass $cm
 *        the course module object
 * @param stdClass $context
 *        the context
 * @param string $filearea
 *        the name of the file area
 * @param array $args
 *        extra arguments (itemid, path)
 * @param bool $forcedownload
 *        whether or not force download
 * @param array $options
 *        additional options affecting the file serving
 * @return bool false if the file not found, just send the file otherwise and do not return anything
 */
function qtype_lti_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = array()) {
    // Check that the filearea is sane.
    if ($filearea !== 'backup') {
        return false;
    }
    // Require authentication.
    require_login($course, true);
    // Capability check.
    if (!has_capability('moodle/backup:backupcourse', $context)) {
        return false;
    }

    // Extract the filename / filepath from the $args array.
    $itemid = array_shift($args);
    $filename = array_pop($args);
    if (!$args) {
        $filepath = '/';
    } else {
        $filepath = '/' . implode('/', $args) . '/';
    }
    // Retrieve the file.
    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'qtype_lti', $filearea, $itemid, $filepath, $filename);
    if (!$file) {
        return false;
    }
    send_stored_file($file, 0, 0, $forcedownload, $options);
}

