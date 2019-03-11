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
 * @package qtype_lti
 * @author Amr Hourani amr.hourani@id.ethz.ch
 * @copyright ETHz 2018 amr.hourani@id.ethz.ch
 */


require_once($CFG->dirroot . '/question/type/rendererbase.php');

//require_once($CFG->libdir . '/outputcomponents.php');

class qtype_lti_renderer extends \qtype_renderer{


    public function formulation_and_controls(question_attempt $qa,
            question_display_options $options) {
                global $PAGE, $CFG, $DB, $USER;

                $question = $qa->get_question();
                // Answer field.
                $step = $qa->get_last_step_with_qt_var('answer');

                $original_userid = $step->get_user_id();

                $currentanswer = $qa->get_last_qt_var('answer');
                $inputname = $qa->get_qt_field_name('answer');
                // $launchid = $qa->get_qt_field_name('launchid');
                $instanceid = $qa->get_qt_field_name('instanceid');
                $attemptfieldname = $qa->get_qt_field_name('attemptid');
                $userid = $qa->get_qt_field_name('userid');

                $attemptinfo = $DB->get_record('question_attempt_steps', array('id' => $qa->get_database_id()));
                //$attempobj = quiz_attempt::create($attemptid);\
                require_once($CFG->dirroot . '/mod/quiz/locallib.php');

                //   $attemptobj = quiz_attempt::create($attemptinfo->questionattemptid);
                //    print_r($attemptobj);exit;
                $attempt = optional_param('attempt', null, PARAM_INT);

                if($attempt) {
                    $attemptfullrecord = $DB->get_record('quiz_attempts',array('id' => $attempt));
                    // $quiz = $DB->get_record('quiz',array('id' => $attemptfullrecord->quiz));
                    // $courseid = $quiz->course;
                } else {
                    $attemptfullrecord = new stdClass();
                    $attemptfullrecord->quiz = 0;
                    // $courseid = 1;
                    $attempt = 0;
                    $attemptfullrecord->state = 'preview';

                }
                
                if (empty($question->unittest) || is_null($question->unittest)){

	                $lti = $DB->get_record('qtype_lti_options', array('questionid' => $question->id), '*', MUST_EXIST);
	                $lti_params = qtype_lti_build_sourcedid($lti->id, $original_userid, $lti->servicesalt, $lti->typeid);
                	$serial_params = $lti_params->data;

                } else {
                	
                	$lti_params = new stdClass();
                	$serial_params  = new stdClass();
                	$lti_params->data = '';
                	$serial_params->userid = 2;
                	$serial_params->instanceid = '';
                	
                	$lti = new stdClass();
                	$lti->id = 1;
                	$lti->servicesalt = '';
                	$lti->typeid = null;
                	
                }
	
                if (!$step->has_qt_var('answer') && empty($options->readonly)) {
                    // Question has never been answered, fill it with response template.
                    $step = new question_attempt_step(array('answer'=>''));
                } else {
                    //$step = new question_attempt_step(array('answer'=>$question->responsetemplate));
                    $sstep = $qa->get_current_manual_mark();

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
                    if($serial_params->userid != $USER->id){
                        $questionmode = 'correction';
                    } else {
                        $questionmode = 'review';
                    }
                    
                    $reviewmodetext = get_string('reviewmode','qtype_lti');
                    
                    // Disable Read-only overlay as per COD-12.
                    $readonlyclass = '  style="border:0px;background: #D3D3D3;" ';
                   	// $readonlyclass = ' style="pointer-events: none; background-color: white; filter:alpha(opacity=50); opacity: 0.5; -moz-opacity:0.50; z-index: 20; background-repeat:no-repeat; background-position:center; border:0px;" ';
                    $readonly = 'questionmode='.$questionmode.'&readonly=1&';
                    $readonlydevstyle = ' title="'.$reviewmodetext.'" style="background-color:#EBEBE4;" '; //Originally: #EBEBE4.
                    

                    // check if manual grade in moodle has been done to this person.

                    if($mark !== null) { // already manually graded!
                        $readonly = 'manuallygraded=1&'.$readonly;
                    } else {
                        $readonly = 'manuallygraded=0&'.$readonly;
                    }


                }

                // Extra Parameters specific to CodeExpert

                $extra_code_expert_parameters = 'questionid='.$question->id.'&ltid='.$lti->id.'&quizid='.$attemptfullrecord->quiz.'&attemptid='.$attempt.'&attemptstate='.$attemptfullrecord->state.'&';
				
                $result =  '<!--'.$question->questiontext.'--><div id="qtype_lti_framediv_'.$question->id.'" class="qtype_lti_framediv" '.$readonlydevstyle.'><span id="quiz_timer_lti_'.$question->id.'" style="display:none; margin-top:-1em; background-color:#fff"></span><span class="qtype_lti_togglebutton" id="qtype_lti_togglebutton_id_'.$question->id.'">&nbsp;</span><iframe id="qtype_lti_contentframe_'.$question->id.'" border="0" height="600px" width="100%" src="'.$CFG->wwwroot.'/question/type/lti/launch.php?'.$extra_code_expert_parameters.$readonly.'id='.$question->id.'&userid='.$serial_params->userid.'" '.$readonlyclass.'></iframe></div>';

                $result .= "<input type=\"hidden\" class=\"qtype_lti_input\" name=\"$inputname\" id=\"qtype_lti_input_id_".$question->id."\">";
                // $result .= "<input type=\"hidden\" class=\"qtype_lti_input\" name=\"$launchid\"  value=\"$serial_params->launchid\" id=\"qtype_lti_luanch_id_".$question->id."\">";
                $result .= "<input type=\"hidden\" class=\"qtype_lti_input\" name=\"$attemptfieldname\"  value=\"$attempt\" id=\"qtype_lti_attempt_id_".$question->id."\">";
                $result .= "<input type=\"hidden\" class=\"qtype_lti_input\" name=\"$instanceid\" value=\"$serial_params->instanceid\" id=\"qtype_lti_instance_id_".$question->id."\">";
                $result .= "<input type=\"hidden\" class=\"qtype_lti_input\" name=\"$userid\" value=\"$serial_params->userid\" id=\"qtype_lti_user_id_".$question->id."\">";


                // Output script to make the iframe tag be as large as possible.
                $result .= '
                            <script type="text/javascript">
                            //<![CDATA[
                                YUI().use("node", "event", function(Y) {
                                    var doc = Y.one("body");
                                    var lti_iframeid = "#qtype_lti_contentframe_"+'.$question->id.';
                                    var lti_inputid = "#qtype_lti_input_id_"+'.$question->id.';
                                    var lti_toggle_btn = "#qtype_lti_togglebutton_id_"+'.$question->id.';
                                    var lti_question_area_id = "#qtype_lti_framediv_"+'.$question->id.';
                                    var frame = Y.one("#qtype_lti_contentframe_"+'.$question->id.');
                                    var padding = 15; //The bottom of the iframe wasn\'t visible on some themes. Probably because of border widths, etc.
                                    var lastHeight;
                                    var resize = function(e) {
                                        var viewportHeight = doc.get("winHeight");
                                        if(lastHeight !== Math.min(doc.get("docHeight"), viewportHeight)){
											//var resize_height = viewportHeight - Y.one("#qtype_lti_contentframe_"+'.$question->id.').getY() - padding;
											if(viewportHeight > 500 ) viewportHeight = 500;
                                            Y.one("#qtype_lti_contentframe_"+'.$question->id.').setStyle("height", viewportHeight + "px");
                                            lastHeight = Math.min(doc.get("docHeight"), doc.get("winHeight"));
                                        }
                                    };

                                    resize();

                                    Y.one("#qtype_lti_input_id_"+'.$question->id.').set("value", Math.random());

                                    Y.on("windowresize", resize);
									var quiz_is_timed = 0;

                                    Y.one("#qtype_lti_togglebutton_id_"+'.$question->id.').on("click", function (e) {
                                          Y.one("#qtype_lti_framediv_"+'.$question->id.').toggleClass("qtype_lti_maximized");
                                          Y.one("#qtype_lti_contentframe_'.$question->id.'").set("height","100%");
                                          // Y.one("#qtype_lti_contentframe_"+'.$question->id.').setStyle("height","100%");
 									      Y.one("#qtype_lti_contentframe_"+'.$question->id.').setStyle("height", doc.get("winHeight") - 25 + "px");
											if (document.getElementById("quiz-timer")) {
												var lti_fullsc_'.$question->id.' = document.getElementById("quiz_timer_lti_'.$question->id.'");
												// lti_fullsc_'.$question->id.'.innerHTML = document.getElementById("quiz-timer").innerHTML;
												lti_fullsc_'.$question->id.'.appendChild(document.getElementById("quiz-timer").cloneNode(true));
												if(document.getElementById("quiz-time-left").innerHTML === ""){
													Y.one("#quiz_timer_lti_"+'.$question->id.').setStyle("display", "none");
													Y.one("#qtype_lti_contentframe_"+'.$question->id.').setStyle("height","100%");
												} else {
													Y.one("#quiz_timer_lti_"+'.$question->id.').setStyle("display", "block");
												}
										  }

                                          if (!Y.one("#qtype_lti_framediv_"+'.$question->id.').hasClass("qtype_lti_maximized") && lastHeight > 0) {

                                         //   Y.one("#qtype_lti_contentframe_'.$question->id.'").set("height",lastHeight);
                                         //   Y.one("#qtype_lti_contentframe_"+'.$question->id.').setStyle("height", lastHeight + "px");


										  var viewportHeight_resized = doc.get("winHeight"); 
										//  var yiframePosition = Y.one("#qtype_lti_contentframe_"+'.$question->id.').getY(); 
										 // var current_height_iframe = viewportHeight_resized - yiframePosition - 15;
										  if(viewportHeight_resized && viewportHeight_resized > 500) viewportHeight_resized = 500;                              
                                           Y.one("#qtype_lti_contentframe_'.$question->id.'").set("height", viewportHeight_resized); 
                                           Y.one("#qtype_lti_contentframe_'.$question->id.'").setStyle("height", viewportHeight_resized + "px");
										 //  Y.one("#qtype_lti_contentframe_'.$question->id.'").setStyle("min-height", "100%");
                                      


											if (document.getElementById("quiz-timer")) {
												var lti_fullsc_'.$question->id.' = document.getElementById("quiz_timer_lti_'.$question->id.'");
												lti_fullsc_'.$question->id.'.innerHTML = "";
												Y.one("#quiz_timer_lti_"+'.$question->id.').setStyle("display", "none");
											}


                                          }


                                    });
                

                                });

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
