<?php

/**
 * Test helpers for the lti question type.
 *
 * @package        qtype
 * @subpackage     lti
 * @author         Amr Hourani <amr.hourani@let.ethz.ch>
 * @copyright  (c) ETH Zurich
 * @license        http://www.moodle.org/license
 */

defined('MOODLE_INTERNAL') || die();
global $CFG;


/**
 * Test helper class for the lti question type.
 *
 * @copyright  (c) ETH Zurich
 * @license        http://www.moodle.org/license
 */
class qtype_lti_test_helper extends question_test_helper {
    
    /**
     * @return array of example question names that can be passed as the $which
     * argument of {@link test_question_maker::make_question} when $qtype is
     * this question type.
     */
    public function get_test_questions() {
        return array('manually');
    }

    /**
     * Make a question which has to be manually graded because answers are unknown.
     *
     * @return qtype_lti_question
     */
    public function make_lti_question_manually() {
        question_bank::load_question_definition_classes('lti');
        $lti = new qtype_lti_question();
        test_question_maker::initialise_a_question($lti);
        $lti->name = "Finding a point in the plane";
        $lti->questiontext = "Drag the point to ({a}/{b})";
        $lti->generalfeedback = 'Generalfeedback: LTI question isn\'t too hard.';
        $lti->ggbturl = 'https://sample.lti.org/example';
        $lti->ggbcodebaseversion = '5.0';
        $lti->israndomized = 0;
        $lti->randomizedvar = '';
        $lti->qtype = question_bank::get_qtype('lti');
        
        return $lti;
    }
}