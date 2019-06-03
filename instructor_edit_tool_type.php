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
 * This page allows instructors to configure course level tool providers.
 *
 * @package qtype_lti
 * @copyright  Copyright (c) 2011 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Chris Scribner
 */

require_once('../../../config.php');
require_once($CFG->dirroot.'/question/type/lti/edit_form.php');
require_once($CFG->dirroot.'/question/type/lti/lib.php');

$courseid = required_param('course', PARAM_INT);

require_login($courseid, false);
$url = new moodle_url('/question/type/lti/instructor_edit_tool_type.php');
$PAGE->set_url($url);
$PAGE->set_pagelayout('popup');
$PAGE->set_title(get_string('edittype', 'qtype_lti'));

$action = optional_param('action', null, PARAM_TEXT);
$typeid = optional_param('typeid', null, PARAM_INT);

require_sesskey();

require_capability('qtype/lti:addcoursetool', context_course::instance($courseid));

if (!empty($typeid)) {
    $type = qtype_lti_get_type($typeid);
    if ($type->course != $courseid) {
        throw new Exception('You do not have permissions to edit this tool type.
                                You are trying to edit an LTI qtype tool which does not belong to
                                the question in the course you are in.
                                Tool Course ID: '.$type->course.' and you are currently in Course ID: '.$courseid);
        die;
    }
}

// Delete action is called via ajax.
if ($action == 'delete') {
    qtype_lti_delete_type($typeid);
    die;
}

// Add a timeout for closing for behat so it can check for errors before switching back to the main window.
$timeout = 0;
if (defined('BEHAT_SITE_RUNNING') && BEHAT_SITE_RUNNING) {
    $timeout = 2000;
}

echo $OUTPUT->header();

$form = new qtype_lti_edit_types_form();

// If the user just opened an add or edit form.
if ($action == 'add' || $action == 'edit') {
    if ($action == 'edit') {
        $type = qtype_lti_get_type_type_config($typeid);
        $form->set_data($type);
    }
    echo $OUTPUT->heading(get_string('toolsetup', 'lti'));
    $form->display();
} else {
    $script = '';
    $closewindow = <<<EOF
        setTimeout(function() {
            window.close();
        }, $timeout);
EOF;

    if ($data = $form->get_data()) {
        $type = new stdClass();

        if (!empty($typeid)) {
            $type->id = $typeid;

            qtype_lti_load_type_if_cartridge($data);

            qtype_lti_update_type($type, $data);

            $fromdb = qtype_lti_get_type($typeid);
            $json = json_encode($fromdb);

            // Output script to update the calling window.
            $script = <<<EOF
                window.opener.M.qtype_lti.editor.updateToolType({$json});
EOF;
        } else {
            $type->state = QTYPE_LTI_TOOL_STATE_CONFIGURED;
            $type->course = $COURSE->id;

            qtype_lti_load_type_if_cartridge($data);

            $id = qtype_lti_add_type($type, $data);

            $fromdb = qtype_lti_get_type($id);
            $json = json_encode($fromdb);

            // Output script to update the calling window.
            $script = <<<EOF
                window.opener.M.qtype_lti.editor.addToolType({$json});
EOF;
        }
    }
    echo html_writer::script($script . $closewindow);
}

echo $OUTPUT->footer();
