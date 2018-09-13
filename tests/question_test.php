<?php

/**
 * Unit tests for the lti question definition class.
 *
 * @package        qtype
 * @subpackage     lti
 * @author         Amr Hourani <amr.hourani@let.ethz.ch>
 * @copyright  (c) ETH Zurich
 * @license        http://www.lti.org/license
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/question/engine/tests/helpers.php');
require_once($CFG->dirroot . '/question/type/lti/question.php');

/**
 * Unit tests for the lti question definition class.
 *
 * @copyright  (c) ETH Zurich
 * @license        http://www.lti.org/license
 */
class qtype_lti_question_test extends advanced_testcase {

  public function test_get_question_summary() {
  	  $lti = test_question_maker::initialise_lti_question();
      $lti->questiontext = 'LTI';
      $this->assertEquals('LTI', $lti->get_question_summary());
  }
}
