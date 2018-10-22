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
 * This file contains tests that walks lti questions through some attempts.
 *
 * @package   qtype_lti
 * @copyright 2018 ETH Zurich
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/question/engine/tests/helpers.php');


/**
 * Unit tests for the lti question type.
 *
 * @copyright 2013 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_lti_walkthrough_testcase extends qbehaviour_walkthrough_test_base {




    public function test_deferred_feedback_plain_text() {
/*
    	$q = test_question_maker::make_question('lti', 'plain');
    	$this->start_attempt_at_question($q, 'deferredfeedback', 1); // Should result in manualgraded behaviour.
    	$behaviour = $this->quba->get_question_attempt($this->slot)->get_behaviour()->get_name();
    	$this->assertEquals('deferredfeedback', $behaviour);
    	// Check the initial state.
    	$this->check_current_state(question_state::$todo);
    	$this->check_current_mark(null);
    	$this->render();
    	$this->check_current_output(
    			$this->get_contains_marked_out_of_summary(),
    			$this->get_contains_question_text_expectation($q),
    			$this->get_does_not_contain_feedback_expectation());
    	
    	$this->process_submission(array(
    			'-submit'   => 1,
    			'answer' => 'LTI Code',
    			'instanceid' => 1,
    			'userid' => 1,
    			'attemptid' => 1
    	));
    	
    	// Verify.
    	$this->check_current_state(question_state::$complete);
    	$this->check_current_mark(null);
    	$this->check_current_output(
    			$this->get_contains_marked_out_of_summary(),
    			$this->get_contains_question_text_expectation($q),
    			$this->get_does_not_contain_feedback_expectation());
    	$this->finish();
    	$this->check_current_state(question_state::$gradedwrong);
    	$this->quba->manual_grade($this->slot, "Not right, but at least some response", 0.5, FORMAT_HTML);
    	$this->check_current_state(question_state::$mangrpartial);
    	$this->check_current_mark(0.5);
    	$this->check_current_output(
    			$this->get_contains_mark_summary(0.5),
    			$this->get_contains_general_feedback_expectation($q),
    			$this->get_no_hint_visible_expectation());
    	$this->assertEquals('Response graded manually', $this->quba->get_response_summary($this->slot));
*/
       
    	
    	
    	$q = test_question_maker::make_question('lti', 'plain');
    	$this->start_attempt_at_question($q, 'deferredfeedback', 1);
    	// Check the initial state.
    	$this->check_current_state(question_state::$todo);
    	$this->check_current_mark(null);
    	$this->check_current_output(
    			$this->get_contains_marked_out_of_summary(),
    			$this->get_does_not_contain_feedback_expectation(),
    			$this->get_does_not_contain_validation_error_expectation(),
    			$this->get_does_not_contain_try_again_button_expectation(),
    			$this->get_no_hint_visible_expectation());
    	// Submit blank.
    	$this->process_submission(array('answer' => ''));
    	// Verify.
    	$this->check_current_state(question_state::$complete);
    	$this->check_current_mark(null);
    	$this->check_current_output(
    			$this->get_contains_marked_out_of_summary(),
    			$this->get_does_not_contain_feedback_expectation(),
    			$this->get_does_not_contain_validation_error_expectation(),
    			$this->get_does_not_contain_try_again_button_expectation(),
    			$this->get_no_hint_visible_expectation());
    	// Submit something that must not validate - missing ggbbase64...
    	$this->process_submission(array(
    			
    			'answer' => 'LTI Code',
    			'instanceid' => 1,
    			'userid' => 1,
    			'attemptid' => 1
    			
    			));
    	// Verify.
    	$this->check_current_state(question_state::$complete);
    	$this->check_current_mark(null);
    	$this->check_current_output(
    			$this->get_contains_marked_out_of_summary(),
    			$this->get_does_not_contain_feedback_expectation(),
    			$this->get_does_not_contain_try_again_button_expectation(),
    			$this->get_no_hint_visible_expectation());
    	// Submit something that must not validate - wrong responsestring: must only contain 0 and 1.
    	$this->process_submission(array(
    			'answer' => 'LTI Code',
    			'instanceid' => 1,
    			'userid' => 1,
    			'attemptid' => 1
    			
    			
    	)); 
    	$this->check_current_state(question_state::$complete);
    	$this->check_current_mark(null);
    	$this->check_current_output(
    			$this->get_contains_marked_out_of_summary(),
    			$this->get_does_not_contain_feedback_expectation(),
    			$this->get_does_not_contain_try_again_button_expectation(),
    			$this->get_no_hint_visible_expectation());
    	// Now put in the right answer.
    	$this->process_submission(array(
    			'answer' => 'LTI Code',
    			'instanceid' => 1,
    			'userid' => 1,
    			'attemptid' => 1
    			
    	));
    	$this->check_current_state(question_state::$complete);
    	$this->check_current_mark(null);
    	$this->check_current_output(
    			$this->get_contains_marked_out_of_summary(),
    			$this->get_does_not_contain_feedback_expectation(),
    			$this->get_does_not_contain_validation_error_expectation(),
    			$this->get_does_not_contain_try_again_button_expectation(),
    			$this->get_no_hint_visible_expectation());
    	// Submit all and finish.
    	$this->finish();
    	$this->check_current_state(question_state::$gradedwrong);
    	$this->check_current_mark(0);
    	$this->check_current_output(
    			$this->get_contains_mark_summary(0));
    	$this->assertEquals(null,$this->quba->get_response_summary($this->slot));
    	

    }


}
