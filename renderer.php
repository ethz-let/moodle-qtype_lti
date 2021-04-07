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
 *
 * @package qtype_lti
 * @author Amr Hourani amr.hourani@id.ethz.ch
 * @copyright ETHz 2018 amr.hourani@id.ethz.ch
 */
defined('MOODLE_INTERNAL') || die;
require_once($CFG->dirroot . '/question/type/rendererbase.php');
class qtype_lti_renderer extends \qtype_renderer {

    /**
     * Returns the last response in a question attempt.
     * @param question_attempt $qa
     * @return array|mixed
     */
    protected function qtype_lti_generate_usage_record($ltiid, $instancecode, $userid, $username, $attempt, $questionid, $quizid,
                    $courseid, $toolurl, $currentanswer, $currentlinkid, $previousresponse) {
                        global $CFG, $DB;

                        $checkrecord = $DB->get_record('qtype_lti_usage',
                                        array('mattemptid' => $attempt, 'instancecode' => $instancecode,
                                            'userid' => $userid, 'questionid' => $questionid,
                                            'courseid' => $courseid, 'quizid' => $quizid,
                                            'ltiid' => $ltiid
                                        ));
                        if($checkrecord) {
                            return $checkrecord;
                        } else {

                            // Check if it was restored.
                            $params = array('mattemptid' => -1, 'instancecode' => $instancecode,
                                'userid' => $userid, 'questionid' => $questionid,
                                'courseid' => $courseid, 'quizid' => 0,
                                'ltiid' => $ltiid);
                            $where_clause = 'where mattemptid = :mattemptid and instancecode = :instancecode
                                             and userid = :userid and questionid = :questionid and courseid = :courseid
                                             and quizid = :quizid and ltiid = :ltiid ';
                            if(isset($previousresponse['currentattemptid']) && trim($previousresponse['currentattemptid']) != '') {
                                $params['attemptid'] = trim($previousresponse['currentattemptid']);
                                $where_clause .= ' and '. $DB->sql_compare_text('attemptid') . ' = ' . $DB->sql_compare_text(':attemptid');
                            }
                            if(isset($previousresponse['resultid']) && trim($previousresponse['resultid']) != '') {
                                $params['resultid'] = trim($previousresponse['resultid']);
                                $where_clause .= ' and '. $DB->sql_compare_text('resultid') . ' = ' . $DB->sql_compare_text(':resultid');
                            }
                            if(isset($previousresponse['linkid']) && trim($previousresponse['linkid']) != '') {
                                $params['resourcelinkid'] = trim($previousresponse['linkid']);
                                $where_clause .= ' and '. $DB->sql_compare_text('resourcelinkid') . ' = ' . $DB->sql_compare_text(':resourcelinkid');
                            }
                            // $checkrecord = $DB->get_records('qtype_lti_usage', $params);
                            $checkrecord = $DB->get_records_sql('select * from {qtype_lti_usage} '.$where_clause, $params);
                            if($checkrecord) {
                                foreach($checkrecord as $rec) {
                                    // Map them based on parentattempt.
                                    // Only take one record, maybe many restores happened and some are not used yet.
                                    $updaterecord = new stdClass();
                                    $updaterecord->id = $rec->id;
                                    $updaterecord->mattemptid = $attempt;
                                    $updaterecord->quizid = $quizid;
                                    $DB->update_record('qtype_lti_usage', $updaterecord);
                                    $rec->mattemptid = $attempt;
                                    $rec->quizid = $quizid;
                                    // Update any grade submission table in case or restore.
                                    $gparams = array('mattempt' => -1, 'ltiid' => $ltiid, 'username' => $username);
                                    if(isset($params['resultid']) && trim($params['resultid']) != '') {
                                        $gparams['linkid'] =  $params['resultid'];
                                    }
                                    if(isset($params['resourcelinkid']) && trim($params['resourcelinkid']) != '') {
                                        $gparams['resultid'] =  $params['resourcelinkid'];
                                    }
                                    $gradesneedupdate = $DB->get_record('qtype_lti_submission', $gparams);
                                    if($gradesneedupdate) {
                                        $updategraderec = new stdClass();
                                        $updategraderec->id = $gradesneedupdate->id;
                                        $updategraderec->mattempt = $attempt;
                                        $DB->update_record('qtype_lti_submission', $updategraderec);
                                    }

                                    return $rec;
                                }

                            }

                            // Seems to be totally new attempt, insert it.
                            $userceattemptrecord = new stdClass();
                            $userceattemptrecord->ltiid = $ltiid;
                            $userceattemptrecord->instancecode = $instancecode;
                            if(isset($previousresponse['currentattemptid']) && trim($previousresponse['currentattemptid']) != '') {
                                $userceattemptrecord->attemptid = trim($previousresponse['currentattemptid']);
                            } else {
                                $userceattemptrecord->attemptid = uniqid();
                            }
                            $userceattemptrecord->mattemptid = $attempt;
                            $userceattemptrecord->questionid = $questionid;
                            $userceattemptrecord->quizid = $quizid;
                            $userceattemptrecord->courseid = $courseid;
                            $userceattemptrecord->userid = $userid;
                            // ResourceLinkId: attemptid + instancecode + username.
                            if(isset($previousresponse['linkid']) && trim($previousresponse['linkid']) != '') {
                                $userceattemptrecord->resourcelinkid = trim($previousresponse['linkid']);
                            } else {
                                $userceattemptrecord->resourcelinkid = trim($userceattemptrecord->attemptid . '-' . $instancecode . '-' . $username);
                            }

                            // What if onlastattempt = 1 and new attempt?.
                            $quizinfo = $DB->get_record('quiz', array('id' => $quizid));
                            if(!$quizinfo){
                                $quizinfo = new stdClass();
                                $quizinfo->attemptonlast = 0;
                            }
                            if($quizinfo->attemptonlast == 1 && isset($previousresponse['attemptid']) &&  $previousresponse['attemptid'] != $attempt){
                                unset($previousresponse['resultid']);
                            }

                            // RestultID: attemptid + instancecode + username + uniqid().
                            if(isset($previousresponse['resultid']) && trim($previousresponse['resultid']) != '') {
                                $userceattemptrecord->resultid = trim($previousresponse['resultid']);
                            } else {
                                $userceattemptrecord->resultid = trim($userceattemptrecord->resourcelinkid . '-' . uniqid());
                            }
                            $userceattemptrecord->parentlti = $ltiid;
                            $userceattemptrecord->parentattempt = $attempt;
                            $userceattemptrecord->timeadded = time();
                            $insertceattemptecord = $DB->insert_record('qtype_lti_usage', $userceattemptrecord);
                            return $userceattemptrecord;


                        }

    }

    public function formulation_and_controls(question_attempt $qa, question_display_options $options) {
        global $PAGE, $CFG, $DB, $USER;
        $question = $qa->get_question();

        $previousresponse = $question->get_response($qa);
        // Answer field.
        $step = $qa->get_last_step_with_qt_var('answer');

        $originaluserid = $step->get_user_id();

        $currentanswer = $qa->get_last_qt_var('answer');
        $currentlinkid = $qa->get_last_qt_var('linkid');
        $currentattemptid = $qa->get_last_qt_var('currentattemptid');
        $inputname = $qa->get_qt_field_name('answer');
        $instanceid = $qa->get_qt_field_name('instanceid');
        $attemptfieldname = $qa->get_qt_field_name('attemptid');
        $resultidfieldname = $qa->get_qt_field_name('resultid');
        $linkidfieldname = $qa->get_qt_field_name('linkid');
        $uniqueattemptfieldname = $qa->get_qt_field_name('currentattemptid');
        $usernamefieldname = $qa->get_qt_field_name('username');
        $userid = $qa->get_qt_field_name('userid');

        $attemptinfo = $DB->get_record('question_attempt_steps', array('id' => $qa->get_database_id()));

        require_once($CFG->dirroot . '/mod/quiz/locallib.php');

        $attempt = optional_param('attempt', null, PARAM_INT);

        if ($attempt) {
            $attemptfullrecord = $DB->get_record('quiz_attempts', array('id' => $attempt));
        } else {
            $attemptfullrecord = new stdClass();
            $attemptfullrecord->quiz = 0;
            $attempt = 0;
            $attemptfullrecord->state = 'preview';
        }

        if (empty($question->unittest) || is_null($question->unittest)) {

            $lti = $DB->get_record('qtype_lti_options', array('questionid' => $question->id), '*', MUST_EXIST);
            $user = $DB->get_record('user', array('id' => $originaluserid), 'id,username', MUST_EXIST);
            global $COURSE;

            $userceattemptrecord = $this->qtype_lti_generate_usage_record($lti->id, $lti->instancecode, $user->id,
                            $user->username, $attempt, $question->id,
                            $attemptfullrecord->quiz, $COURSE->id, $lti->toolurl, $currentanswer, $currentlinkid,
                            $previousresponse);

            $ltiparams = qtype_lti_build_sourcedid($userceattemptrecord->resultid, $user->username, $lti->servicesalt,
                            $lti->typeid, $userceattemptrecord->attemptid, $lti->id, $attempt);

            $serialparams = $ltiparams->data;
        } else {

            $ltiparams = new stdClass();
            $serialparams = new stdClass();
            $ltiparams->data = '';
            $serialparams->userid = 2;
            $serialparams->instanceid = '';

            $lti = new stdClass();
            $lti->id = 1;
            $lti->servicesalt = '';
            $lti->typeid = null;
        }

        if (!$step->has_qt_var('answer') && empty($options->readonly)) {
            // Question has never been answered, fill it with response template.
            $step = new question_attempt_step(array('answer' => ''));
        } else {
            $step = $qa->get_current_manual_mark();
            // Is there a current value in the current POST data? If so, use that.
            $mark = $qa->get_submitted_var($qa->get_behaviour_field_name('mark'), PARAM_RAW_TRIMMED);
            if ($mark === null) {
                // Otherwise, use the stored value.
                // If the question max mark has not changed, use the stored value that was input.
                $storedmaxmark = $qa->get_last_behaviour_var('maxmark');
                if ($storedmaxmark !== null && ($storedmaxmark - $qa->get_max_mark()) < 0.0000005) {
                    $mark = $qa->get_last_behaviour_var('mark');
                }
            }
        }

        if (!empty($question->typeid)) {
            $toolconfig = qtype_lti_get_type_config($question->typeid);
        } else if ($tool = qtype_lti_get_tool_by_url_match($question->toolurl)) {
            $toolconfig = qtype_lti_get_type_config($tool->id);
        } else {
            $toolconfig = array();
        }

        if (empty($options->readonly)) {
            $readonlyclass = '  style="border:0px;background: #D3D3D3;" ';
            $readonly = 'manuallygraded=0&questionmode=solve&';
            $readonlydevstyle = '';
            $reviewmodetext = '';
        } else {
            // If the logged user not the same user attempting the question, then mode is 'correction'.
            if ($user->id != $USER->id) {
                $questionmode = 'correction';
            } else {
                $questionmode = 'review';
            }

            $reviewmodetext = get_string('reviewmode', 'qtype_lti');

            // Disable Read-only overlay as per COD-12.
            $readonlyclass = '  style="border:0px;background: #D3D3D3;" ';
            $readonly = 'questionmode=' . $questionmode . '&readonly=1&';
            $readonlydevstyle = ' title="' . $reviewmodetext . '" style="background-color:#EBEBE4;" '; // Originally: #EBEBE4.

            // Check if manual grade in moodle has been done to this person.

            if ($mark !== null) { // Already manually graded.
                $readonly = 'manuallygraded=1&' . $readonly;
            } else {
                $readonly = 'manuallygraded=0&' . $readonly;
            }
        }

        // Extra Parameters specific to CodeExpert.

        $extracodeexpertparameters = 'questionid=' . $question->id . '&ltid=' . $lti->id . '&quizid=' . $attemptfullrecord->quiz .
        '&attemptid=' . $userceattemptrecord->attemptid . '&attemptstate=' . $attemptfullrecord->state .
        '&mattempt=' . $attempt . '&';

        $result = '<div id="qtype_lti_framediv_' . $question->id . '" class="qtype_lti_framediv" ' . $readonlydevstyle .
        '><span id="quiz_timer_lti_' . $question->id .
        '" style="display:none; background-color:#fff"></span>
              <span class="qtype_lti_togglebutton" id="qtype_lti_togglebutton_id_' .
              $question->id . '" onclick="qtype_lti_fullscreen_'.$question->id.'()">&nbsp;</span><iframe id="qtype_lti_contentframe_' . $question->id .
              '" border="0" height="600px" width="100%" src="' . $CFG->wwwroot . '/question/type/lti/launch.php?' .
              $extracodeexpertparameters . $readonly . 'id=' . $question->id . '&userid=' . $user->id . '&resourcelinkid=' .
              $userceattemptrecord->resourcelinkid . '&resultid=' . $userceattemptrecord->resultid . '" ' . $readonlyclass .
              ' loading="eager"></iframe></div>';
              $result .= "<input type=\"hidden\" class=\"qtype_lti_input\" name=\"$inputname\"
              value=\"$userceattemptrecord->resultid\" id=\"qtype_lti_input_id_" . $question->id . "\">";
              $result .= "<input type=\"hidden\" class=\"qtype_lti_input\" name=\"$attemptfieldname\"
              value=\"$attempt\" id=\"qtype_lti_attempt_id_" . $question->id . "\">";
              $result .= "<input type=\"hidden\" class=\"qtype_lti_input\" name=\"$instanceid\"
              value=\"$lti->id\" id=\"qtype_lti_instance_id_" . $question->id . "\">";
              $result .= "<input type=\"hidden\" class=\"qtype_lti_input\"
              name=\"$userid\" value=\"$user->id\" id=\"qtype_lti_user_id_" . $question->id . "\">";
              $result .= "<input type=\"hidden\" class=\"qtype_lti_input\" name=\"$resultidfieldname\"
              value=\"$userceattemptrecord->resultid\" id=\"qtype_lti_user_id_" . $question->id . "\">";
              $result .= "<input type=\"hidden\" class=\"qtype_lti_input\" name=\"$linkidfieldname\"
              value=\"$userceattemptrecord->resourcelinkid\" id=\"qtype_lti_user_id_" . $question->id . "\">";
              $result .= "<input type=\"hidden\" class=\"qtype_lti_input\" name=\"$usernamefieldname\"
              value=\"$user->username\" id=\"qtype_lti_user_id_" . $question->id . "\">";
              $result .= "<input type=\"hidden\" class=\"qtype_lti_input\" name=\"$uniqueattemptfieldname\"
              value=\"$userceattemptrecord->attemptid\" id=\"qtype_lti_user_id_" . $question->id . "\">";

              // Output script to make the iframe tag be as large as possible.
              $result .= '
                            <script type="text/javascript">
							//<![CDATA[
									YUI().use("node", "event", function(Y) {
                                            Y.one("#qtype_lti_input_id_"+'.$question->id.').set("value", "'.$userceattemptrecord->resultid.'");
											var doc = Y.one("body");
											var lti_iframeid = "#qtype_lti_contentframe_'.$question->id.'";
											var lti_toggle_btn = "#qtype_lti_togglebutton_id_'.$question->id.'";

											var frame = Y.one("#qtype_lti_contentframe_'.$question->id.'");
											var padding = 150;
											var lastHeight;
											var resize = function(e) {

    											var viewportHeight =  window.innerHeight;
                          var quiz_timer_div = document.getElementById("quiz-time-left");
                          var lti_fullsc_'.$question->id.' =
                          document.getElementById("quiz_timer_lti_'.$question->id.'");
                          if (quiz_timer_div && quiz_timer_div.innerHTML !== "") {
                               Y.one("#quiz_timer_lti_'.$question->id.'").
                                setStyle("display", "block");
                               var calculatedheight =  viewportHeight -
                                    Y.one("#quiz_timer_lti_'.$question->id.'").get("clientHeight");
                               Y.one("#qtype_lti_contentframe_'.$question->id.'").
                                setStyle("height", calculatedheight+ "px");
                          } else {
                               var calculatedheight = viewportHeight;
                               Y.one("#qtype_lti_contentframe_'.$question->id.'").
                               setStyle("height", calculatedheight+ "px");
                          }

                          if (!Y.one("#qtype_lti_framediv_'.$question->id.'").
                            hasClass("qtype_lti_maximized") &&
                              calculatedheight > 650) {
                              Y.one("#qtype_lti_contentframe_'.$question->id.'").
                                setStyle("height", "650px");
                              Y.one("#qtype_lti_framediv_'.$question->id.'").
                                setStyle("height", "650px");
                          } else {
                              Y.one("#qtype_lti_framediv_'.$question->id.'").
                              setStyle("height", calculatedheight +"px");
                              Y.one("#qtype_lti_framediv_'.$question->id.'").
                              set("height", calculatedheight +"px");
                          }

                  	  };

                      Y.on("domready", function() {
                        resize();
                      });

                  	  Y.on("windowresize", resize);

                  	  });

                  function qtype_lti_fullscreen_'.$question->id.'() {

                      var doc = Y.one("body");
                      var lti_iframeid = "#qtype_lti_contentframe_'.$question->id.'";
                      var lti_toggle_btn = "#qtype_lti_togglebutton_id_'.$question->id.'";

                      var frame = Y.one("#qtype_lti_contentframe_'.$question->id.'");
                      var padding = 150;
                      var lastHeight;
                      var quiz_is_timed = 0;
                      Y.one("#qtype_lti_framediv_'.$question->id.'").
                        toggleClass("qtype_lti_maximized");
                      Y.one("#qtype_lti_contentframe_'.$question->id.'").
                        set("height","100%");

                      if (Y.one("#qtype_lti_framediv_'.$question->id.'").
                          hasClass("qtype_lti_maximized")) {
                          Y.one("#qtype_lti_framediv_'.$question->id.'").
                            set("height","100%");
                          Y.one("#qtype_lti_framediv_'.$question->id.'").
                            setStyle("height","100%");

                          Y.one("#qtype_lti_contentframe_'.$question->id.'").
                            set("height","100%");
                          Y.one("#qtype_lti_contentframe_'.$question->id.'").
                            setStyle("height","100%");

                          var quiz_timer_div = document.getElementById("quiz-time-left");
                          var lti_fullsc_'.$question->id.' =
                              document.getElementById("quiz_timer_lti_'.$question->id.'");

                          if (quiz_timer_div && quiz_timer_div.innerHTML !== "") {
                               lti_fullsc_'.$question->id.'.
                                appendChild(document.getElementById("quiz-timer").cloneNode(true));
                               Y.one("#quiz_timer_lti_'.$question->id.'").
                                setStyle("display", "block");
                               Y.one("#quiz_timer_lti_'.$question->id.'").
                                setStyle("margin-top", "-1em");
                               var calculatedheight = doc.get("winHeight") -
                                   Y.one("#quiz_timer_lti_'.$question->id.'").
                                    get("clientHeight");
                               Y.one("#qtype_lti_contentframe_'.$question->id.'").
                                setStyle("height", calculatedheight+ "px");
                          } else {
                              if (lti_fullsc_'.$question->id.'.hasChildNodes()) {
                                  lti_fullsc_'.$question->id.'.
                                    removeChild(document.getElementById("quiz-timer").cloneNode(true));
                              }
                              var  calculatedheight = doc.get("winHeight");
                              Y.one("#qtype_lti_contentframe_'.$question->id.'").
                                setStyle("height", calculatedheight+ "px");

                          }
                      } else {

                      	var viewportHeight = doc.get("winHeight");
                          if (viewportHeight > 650 || viewportHeight <= 500) viewportHeight = 650;
                	       Y.one("#qtype_lti_contentframe_'.$question->id.'").
                          setStyle("height", viewportHeight + "px");
                          Y.one("#qtype_lti_contentframe_'.$question->id.'").
                          set("height", viewportHeight + "px");

                          Y.one("#qtype_lti_framediv_'.$question->id.'").
                          setStyle("height", viewportHeight +"px");
                          Y.one("#qtype_lti_framediv_'.$question->id.'").
                          set("height", viewportHeight +"px");

                          if (document.getElementById("quiz-timer")) {
                               var lti_fullsc_'.$question->id.' =
                                    document.getElementById("quiz_timer_lti_'.$question->id.'");
                               lti_fullsc_'.$question->id.'.innerHTML = "";
                               Y.one("#quiz_timer_lti_'.$question->id.'").setStyle("margin-top", "0em");
                               Y.one("#quiz_timer_lti_'.$question->id.'").setStyle("display", "none");
                          }

                      }

                  }
              //]]
          </script>



';

              return $result;
    }

    /**
     * Defer to template.
     *
     * @param tool_configure_page $page
     *
     * @return string html for the page
     */
    protected function render_tool_configure_page($page) {
        $data = $page->export_for_template($this);
        return parent::render_from_template('qtype_lti/tool_configure', $data);
    }

    /**
     * Render the external registration return page
     *
     * @param tool_configure_page $page
     *
     * @return string html for the page
     */
    protected function render_external_registration_return_page($page) {
        $data = $page->export_for_template($this);
        return parent::render_from_template('qtype_lti/external_registration_return', $data);
    }
}