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
 * LTI question definition class.
 *
 * @package qtype
 * @subpackage lti
 * @copyright 2019 ETH Zurich
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/question/type/questionbase.php');
/**
 * Represents an lti question.
 *
 * @copyright 2009 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_lti_question extends question_graded_automatically_with_countback {

    public function compute_final_grade($responses, $totaltries) {
        $totalstemscore = 0;
        foreach ($this->order as $key => $rowid) {
            $fieldname = 'answer';
            $lastwrongindex = -1;
            $finallyright = false;
            foreach ($responses as $i => $response) {
                if (!array_key_exists($fieldname, $response) || !$response[$fieldname]) {
                    $lastwrongindex = $i;
                    $finallyright = false;
                } else {
                    $finallyright = true;
                }
            }
            if ($finallyright) {
                $totalstemscore += max(0, 1 - ($lastwrongindex + 1) * $this->penalty);
            }
        }
        return $totalstemscore / count($this->order);
    }

    public function get_response(question_attempt $qa) {
        return $qa->get_last_qt_data();
    }

    public function get_expected_data() {
        return array('answer' => PARAM_RAW, 'instanceid' => PARAM_RAW, 'userid' => PARAM_RAW,
                    'attemptid' => PARAM_RAW, 'username' => PARAM_RAW, 'linkid' => PARAM_RAW,
                    'resultid' => PARAM_RAW, 'currentattemptid' => PARAM_RAW);
    }

    public function is_complete_response(array $response) {
        /* LTI question type is always complete (i.e. ready for grading) */
        /* always returns true, since from this point, there is no possibility though to the question_attempt_step */
        return true;
    }

    public function is_same_response(array $prevresponse, array $newresponse) {
        /* LTI question responses are never the same */
        /*
         * should return true when there is an answer, and the same answer in the comment field (or answers are empty and/or
         * identical
         */
        /* Always returns false, since from this point, there is no possibility though to the question_attempt_step */
        return false;
    }

    public function is_gradable_response(array $response) {
        return array_key_exists('answer', $response) &&
             ($response['answer'] || $response['answer'] === '0' || $response['answer'] === 0);
    }

    /**
     *
     * @param
     *        moodle_page the page we are outputting to.
     * @return qtype_lti_format_renderer_base the response-format-specific renderer.
     */
    public function summarise_response(array $response) {
        return null;
    }

    public function get_correct_response() {
        return null;
    }

    public function get_validation_error(array $response) {
        return '';
    }

    public function check_file_access($qa, $options, $component, $filearea, $args, $forcedownload) {
        if ($component == 'question' && $filearea == 'response_attachments') {
            // Response attachments visible if the question has them.
            return $this->attachments != 0;
        } else if ($component == 'question' && $filearea == 'response_answer') {
            // Response attachments visible if the question has them.
            return $this->responseformat === 'editorfilepicker';
        } else if ($component == 'qtype_lti' && $filearea == 'graderinfo') {
            return $options->manualcomment && $args[0] == $this->id;
        } else {
            return parent::check_file_access($qa, $options, $component, $filearea, $args, $forcedownload);
        }
    }

    public function grade_question($question, $answers) {
        global $USER, $DB;

        $value = 0;
        $instanceid = $answers['instanceid'];
        $userid = $answers['userid'];
        $mattempt = $answers['attemptid'];
        $username = $answers['username'];
        $linkid = $answers['linkid'];
        $result = $answers['resultid'];


        $submissiongrade = $DB->get_record('qtype_lti_submission',
                        array('username' => $username, 'linkid' => $result, 'resultid' => $linkid, 'ltiid' => $instanceid,
                              'mattempt' => $mattempt
                        ));
        if ($submissiongrade) {
            $value = $submissiongrade->gradepercent;
        }

        return $value;
    }

    public function grade_response(array $response) {
        $grade = $this->grade_question($this, $response);
        $state = question_state::graded_state_for_fraction($grade);
        return array($grade, $state);
    }
}
