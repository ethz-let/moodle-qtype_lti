<?php

/**
 * Unit tests for the lti question definition class.
 *
 * @package        qtype
 * @subpackage     lti
 * @author         Amr Hourani <amr.hourani@let.ethz.ch>
 * @copyright      2018 ETH Zurich
 * @license        http://www.lti.org/license
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/question/engine/tests/helpers.php');
require_once($CFG->dirroot . '/question/type/lti/question.php');

/**
 * Unit tests for the lti question definition class.
 *
 * @copyright  	   2018 ETH Zurich
 * @license        http://www.lti.org/license
 */
class qtype_lti_question_test extends advanced_testcase {

  public function test_get_question_summary() {
  	  question_bank::load_question_definition_classes('lti'); //test_question_maker::initialise_lti_question();
  	  $lti = new qtype_lti_question();
      $lti->questiontext = 'LTI';
      $this->assertEquals('LTI', $lti->get_question_summary());
  }
}
