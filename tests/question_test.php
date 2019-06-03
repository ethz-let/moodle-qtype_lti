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
 * Unit tests for the lti question definition class.
 *
 * @package qtype
 * @subpackage lti
 * @author Amr Hourani <amr.hourani@let.ethz.ch>
 * @copyright 2018 ETH Zurich
 * @license http://www.lti.org/license
 */
defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/question/engine/tests/helpers.php');
require_once($CFG->dirroot . '/question/type/lti/question.php');

/**
 * Unit tests for the lti question definition class.
 *
 * @copyright 2018 ETH Zurich
 * @license http://www.lti.org/license
 */
class qtype_lti_question_test extends advanced_testcase {

    public function test_get_question_summary() {
        question_bank::load_question_definition_classes('lti');
        $lti = new qtype_lti_question();
        $lti->questiontext = 'LTI';
        $this->assertEquals('LTI', $lti->get_question_summary());
    }
}
