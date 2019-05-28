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
//
// This file is part of BasicLTI4Moodle
//
// BasicLTI4Moodle is an IMS BasicLTI (Basic Learning Tools for Interoperability)
// consumer for Moodle 1.9 and Moodle 2.0. BasicLTI is a IMS Standard that allows web
// based learning tools to be easily integrated in LMS as native ones. The IMS BasicLTI
// specification is part of the IMS standard Common Cartridge 1.1 Sakai and other main LMS
// are already supporting or going to support BasicLTI. This project Implements the consumer
// for Moodle. Moodle is a Free Open source Learning Management System by Martin Dougiamas.
// BasicLTI4Moodle is a project iniciated and leaded by Ludo(Marc Alier) and Jordi Piguillem
// at the GESSI research group at UPC.
// SimpleLTI consumer for Moodle is an implementation of the early specification of LTI
// by Charles Severance (Dr Chuck) htp://dr-chuck.com , developed by Jordi Piguillem in a
// Google Summer of Code 2008 project co-mentored by Charles Severance and Marc Alier.
//
// BasicLTI4Moodle is copyright 2009 by Marc Alier Forment, Jordi Piguillem and Nikolas Galanis
// of the Universitat Politecnica de Catalunya http://www.upc.edu
// Contact info: Marc Alier Forment granludo @ gmail.com or marc.alier @ upc.edu.

/**
 * This file contains all necessary code to view a lti activity instance
 *
 * @package qtype_lti
 * @copyright  2009 Marc Alier, Jordi Piguillem, Nikolas Galanis
 *  marc.alier@upc.edu
 * @copyright  2009 Universitat Politecnica de Catalunya http://www.upc.edu
 * @author     Marc Alier
 * @author     Jordi Piguillem
 * @author     Nikolas Galanis
 * @author     Chris Scribner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("../../../config.php");
require_once($CFG->dirroot.'/question/type/lti/lib.php');
require_once($CFG->dirroot.'/question/type/lti/locallib.php');

$id = required_param('id', PARAM_INT); // Course Module ID.
$userid = required_param('userid', PARAM_INT); // User ID.
$resourcelinkid = required_param('resourcelinkid', PARAM_RAW);
$resultid = required_param('resultid', PARAM_RAW);

$triggerview = optional_param('triggerview', 1, PARAM_BOOL);
$readonly = optional_param('readonly', 0, PARAM_BOOL);
$manuallygraded_in_moodle = optional_param('manuallygraded', 0, PARAM_BOOL);
$attemptid = optional_param('attemptid', '', PARAM_RAW);
$quizid = optional_param('quizid', 0, PARAM_INT);
$questionid = optional_param('questionid', 0, PARAM_INT);
$attemptstate= optional_param('attemptstate', 'preview', PARAM_RAW);




$questionmode = optional_param('questionmode', 'create', PARAM_RAW); // 1 is for question creation/edit mode.

$lti = $DB->get_record('qtype_lti_options', array('questionid' => $id), '*', MUST_EXIST);

$user = $DB->get_record('user', array('id' => $userid), '*', MUST_EXIST);

$course = $DB->get_record('course', array('id' => $lti->course), '*', MUST_EXIST);

$quiz = $DB->get_record('quiz',array('id' => $quizid));


if(!$quiz){
    $quiz = new stdClass();
    $quiz->id = 0;
    $quiz->name = 'noquiz';
    $quiz->attemptonlast = 0;
}


$extra_code_expert_params = array(
                'attemptid' => $attemptid,
                'quizid' => $quiz->id,
                'questionid' => $questionid,
                'attemptstate' => $attemptstate,
                'instancecode' => $lti->instancecode,
                'quiztitle' => $quiz->name,
                'courseid' => $course->id,
                'ltiid' => $lti->id,
                'attemptonlast' => $quiz->attemptonlast,
				'resourcelinkid' => $resourcelinkid,
				'resultid' => $resultid
             );


require_login();
$lti->cmid = 0;
qtype_lti_launch_tool($lti, $user->username, $readonly, $questionmode, $manuallygraded_in_moodle, $extra_code_expert_params);
