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
 * This file contains all necessary code to view a lti activity instance
 *
 * @package qtype_lti
 * @copyright 2019 ETH Zurich
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once("../../../config.php");
require_once($CFG->dirroot . '/question/type/lti/lib.php');
require_once($CFG->dirroot . '/question/type/lti/locallib.php');

$id = required_param('id', PARAM_INT); // Course Module ID.
$userid = required_param('userid', PARAM_INT); // User ID.
$resourcelinkid = required_param('resourcelinkid', PARAM_RAW);
$resultid = required_param('resultid', PARAM_RAW);

$triggerview = optional_param('triggerview', 1, PARAM_BOOL);
$readonly = optional_param('readonly', 0, PARAM_BOOL);
$manuallygradedinmoodle = optional_param('manuallygraded', 0, PARAM_BOOL);
$attemptid = optional_param('attemptid', '', PARAM_RAW);
$quizid = optional_param('quizid', 0, PARAM_INT);
$questionid = optional_param('questionid', 0, PARAM_INT);
$attemptstate = optional_param('attemptstate', 'preview', PARAM_RAW);

$questionmode = optional_param('questionmode', 'create', PARAM_RAW); // 1 is for question creation/edit mode.

$lti = $DB->get_record('qtype_lti_options', array('questionid' => $id), '*', MUST_EXIST);

$user = $DB->get_record('user', array('id' => $userid), '*', MUST_EXIST);

$course = $DB->get_record('course', array('id' => $lti->course), '*', MUST_EXIST);

$quiz = $DB->get_record('quiz', array('id' => $quizid));

if (!$quiz) {
    $quiz = new stdClass();
    $quiz->id = 0;
    $quiz->name = 'noquiz';
    $quiz->attemptonlast = 0;
}

$extracodeexpertparams = array('attemptid' => $attemptid, 'quizid' => $quiz->id, 'questionid' => $questionid,
    'attemptstate' => $attemptstate, 'instancecode' => $lti->instancecode, 'quiztitle' => $quiz->name, 'courseid' => $course->id,
    'ltiid' => $lti->id, 'attemptonlast' => $quiz->attemptonlast, 'resourcelinkid' => $resourcelinkid, 'resultid' => $resultid);

require_login();
$lti->cmid = 0;
qtype_lti_launch_tool($lti, $user->username, $readonly, $questionmode, $manuallygradedinmoodle, $extracodeexpertparams);
