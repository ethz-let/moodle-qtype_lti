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
 * Test helpers for the lti question type.
 *
 * @package qtype_lti
 * @copyright 2019 ETH Zurich
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/**
 * Test helper class for the lti question type.
 *
 * @copyright 2019 ETHz
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_lti_test_helper extends question_test_helper {

    public function get_test_questions() {
        return array('plain');
    }

    /**
     * Make the data what would be received from the editing form for an lti
     * question.
     *
     * @return stdClass the data that would be returned by $form->get_gata();
     */
    public function get_lti_question_form_data_plain() {
        $q = new stdClass();
        $q->name = 'lti-question';
        $q->status = \core_question\local\bank\question_version_status::QUESTION_STATUS_READY;
        $q->questiontext = array(
            "text" => 'Questiontext for Question 1',
            'format' => FORMAT_HTML,
            'itemid' => 1
        );
        $q->generalfeedback = array(
            "text" => 'I hope your code had a beginning, a middle and an end.',
            'format' => FORMAT_HTML,
            'itemid' => 2
        );
        $q->defaultmark = 1;
        $q->penalty = 0.3333333;
        $q->qtype = 'lti';
        $q->length = '1';
        $q->hidden = '0';
        $q->createdby = '2';
        $q->modifiedby = '2';
        $q->questionid = 1;
        $q->unittest = 1;
        $q->course = 1;
        $q->typeid = null;
        $q->toolurl = 'https://tool.org';
        $q->securetoolurl = 'https://tool.org';
        $q->instructorchoicesendname = 1;
        $q->instructorchoicesendemailaddr = 1;
        $q->instructorchoiceallowroster = 1;
        $q->instructorchoiceallowsetting = 1;
        $q->instructorcustomparameters = 1;
        $q->instructorchoiceacceptgrades = 1;
        $q->grade = 1;
        $q->launchcontainer = 1;
        $q->resourcekey = 'key';
        $q->password = 'secret';
        $q->debuglaunch = 0;
        $q->showtitlelaunch = 0;
        $q->showdescriptionlaunch = 0;
        $q->servicesalt = 'salt';
        $q->instancecode = 'somerandominstancecode';
        $q->originalinstancecode = null;
        $q->icon = '';
        $q->secureicon = '';

        return $q;
    }
}
