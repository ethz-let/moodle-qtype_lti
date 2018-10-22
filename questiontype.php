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
 * Question type class for the lti question type.
 *
 * @package    qtype
 * @subpackage lti
 * @copyright  2005 Mark Nielsen
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/questionlib.php');
require_once($CFG->dirroot . '/question/type/lti/question.php');
require_once($CFG->dirroot.'/question/type/lti/locallib.php');
/**
 * The lti question type.
 *
 * @copyright  2005 Mark Nielsen
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_lti extends question_type {
	
    public function is_manual_graded() {
        return true;
    }

    public function get_question_options($question) {
        global $DB;

        $question->options = $DB->get_record('qtype_lti_options',
                array('questionid' => $question->id), '*', MUST_EXIST);
        $question->questiontext = $question->name; // COD-4 for Import and Export title. 
        parent::get_question_options($question);
    }

    public function save_question_options($question) {
        global $DB, $CFG;

        $options = $DB->get_record('qtype_lti_options', array('questionid' => $question->id));

        $context = $question->context;
        $question->grade = 100; //Default.
        $question->timecreated = time();
        $question->timemodified = $question->timecreated;
       
       
        if (!$options) { // Insertion.
            if (!isset($question->toolurl)) {
                $question->toolurl = '';
            }
            
            $question->servicesalt = uniqid('', true);
            if (!isset($question->typeid)) {
                $question->typeid = null;
            }

        } else { // Update.
            if ($question->typeid == 0 && isset($question->urlmatchedtypeid)) {
            	$question->typeid = $question->urlmatchedtypeid;
            }
        }

        
        qtype_lti_load_tool_if_cartridge($question);

        qtype_lti_force_type_config_settings($question, qtype_lti_get_type_config_by_instance($question));

        if (empty($question->typeid) && isset($question->urlmatchedtypeid)) {
            $question->typeid = $question->urlmatchedtypeid;
        }

        qtype_lti_ensure_user_can_use_type($question);


        if (!isset($question->instructorchoiceacceptgrades) || $question->instructorchoiceacceptgrades != QTYPE_LTI_SETTING_ALWAYS) {
            // The instance does not accept grades back from the provider, so set to "No grade" value 0.
            $question->grade = 0;
        }


        if (!$options) {
            $question->questionid = $question->id;
            $DB->insert_record('qtype_lti_options', $question);
        } else {

            if (!isset($question->showtitlelaunch)) {
                $question->showtitlelaunch = 0;
            }
            if (!isset($question->showdescriptionlaunch)) {
                $question->showdescriptionlaunch = 0;
            }


            $question->questionid = $question->id;
            $question->id = $options->id;
            $DB->update_record('qtype_lti_options', $question);
        }

        $this->save_hints($question, true);

        if (isset($question->instructorchoiceacceptgrades) && $question->instructorchoiceacceptgrades == QTYPE_LTI_SETTING_ALWAYS) {
            if (!isset($question->cmidnumber)) {
                $question->cmidnumber = '';
            }

          //qtype_lti_grade_item_update($question);
        }
  }

/*
  public function set_default_options($question) {
      if (!isset($question->options)) {
          $question->options = new stdClass();
      }
      if (!isset($question->options->instancecode)) {
          $question->options->instancecode = uniqid('');
      }
  }
  */

    protected function initialise_question_instance(question_definition $question, $questiondata) {
        parent::initialise_question_instance($question, $questiondata);

        $question->questionid = $questiondata->options->questionid;
        $question->typeid = $questiondata->options->typeid;
        $question->toolurl = $questiondata->options->toolurl;
        $question->securetoolurl = $questiondata->options->securetoolurl;
        $question->instructorchoicesendname = $questiondata->options->instructorchoicesendname;
        $question->instructorchoicesendemailaddr = $questiondata->options->instructorchoicesendemailaddr;
        $question->instructorchoiceallowroster = $questiondata->options->instructorchoiceallowroster;
        $question->instructorchoiceallowsetting = $questiondata->options->instructorchoiceallowsetting;
        $question->instructorcustomparameters = $questiondata->options->instructorcustomparameters;
        $question->instructorchoiceacceptgrades = $questiondata->options->instructorchoiceacceptgrades;
        $question->grade = $questiondata->options->grade;
        $question->launchcontainer = $questiondata->options->launchcontainer;
        $question->resourcekey = $questiondata->options->resourcekey;
        $question->password = $questiondata->options->password;
        $question->debuglaunch = $questiondata->options->debuglaunch;
        $question->showtitlelaunch =  $questiondata->options->showtitlelaunch;
        $question->showdescriptionlaunch =  $questiondata->options->showdescriptionlaunch;
        $question->servicesalt =  $questiondata->options->servicesalt;
        $question->instancecode = $questiondata->options->instancecode;
      	// $question->originalinstancecode =  $questiondata->options->originalinstancecode;
        $question->icon =  $questiondata->options->icon;
        $question->secureicon=  $questiondata->options->secureicon;
        
        $question->answers =  $questiondata->options->answers;
    }

    public function delete_question($questionid, $contextid) {
      global $DB;

      if (! $basiclti = $DB->get_record("qtype_lti_options", array("questionid" => $questionid))) {
          return false;
      }

      $ltitype = $DB->get_record('qtype_lti_types', array('id' => $basiclti->typeid));
      if ($ltitype) {
          $DB->delete_records('qtype_lti_tool_settings',
              array('toolproxyid' => $ltitype->toolproxyid, 'questionid' => $questionid));
      }

        $DB->delete_records('qtype_lti_options', array('questionid' => $questionid));
        parent::delete_question($questionid, $contextid);
    }

    public function get_random_guess_score($questiondata) {
        return 0;
    }


    public function get_possible_responses($questiondata) {
        $responses = array();
        $starfound = false;
        foreach ($questiondata->options->answers as $aid => $answer) {
            $responses[$aid] = new question_possible_response($answer->answer, $answer->fraction);
            if ($answer->answer === '*') {
                $starfound = true;
            }
        }
        if (!$starfound) {
            $responses[0] = new question_possible_response(
                    get_string('didnotmatchanyanswer', 'question'), 0);
        }
        $responses[null] = question_possible_response::no_response();
        return array($questiondata->id => $responses);
    }



    public function move_files($questionid, $oldcontextid, $newcontextid) {
        parent::move_files($questionid, $oldcontextid, $newcontextid);
        $fs = get_file_storage();
        $fs->move_area_files_to_new_context($oldcontextid,
                $newcontextid, 'qtype_lti', 'graderinfo', $questionid);
    }

    protected function delete_files($questionid, $contextid) {
        parent::delete_files($questionid, $contextid);
        $fs = get_file_storage();
        $fs->delete_area_files($contextid, 'qtype_lti', 'graderinfo', $questionid);
    }

     /**
     * Provide export functionality for xml format.
     *
     * @param question object the question object
     * @param format object the format object so that helper methods can be used
     * @param extra mixed any additional format specific data that may be passed by the format (see
     *        format code for info)
     *
     * @return string the data to append to the output buffer or false if error
     */
    public function export_to_xml($question, qformat_xml $format, $extra = null) {
        global $DB;
        $expout = '';
        $contextid = $question->contextid;

        // Set the additional fields.
        $expout .= '    <instancecode>' . $format->writetext($question->options->instancecode) .
                 "</instancecode>\n";

        $expout .= '    <course>' . $question->options->course .
        "</course>\n";
        $expout .= '    <cmid>' . $question->options->cmid .
        "</cmid>\n";

        $expout .= '    <typeid>' . $question->options->typeid .
                 "</typeid>\n";
        $expout .= '    <toolurl>' . $format->writetext($question->options->toolurl) .
                 "</toolurl>\n";
        $expout .= '    <securetoolurl>' . $format->writetext($question->options->securetoolurl) .
                "</securetoolurl>\n";
        $expout .= '    <instructorchoicesendname>' . $question->options->instructorchoicesendname .
                 "</instructorchoicesendname>\n";
        $expout .= '    <instructorchoicesendemailaddr>' . $question->options->instructorchoicesendemailaddr .
                "</instructorchoicesendemailaddr>\n";
        $expout .= '    <instructorchoiceallowroster>' . $question->options->instructorchoiceallowroster .
                "</instructorchoiceallowroster>\n";
        $expout .= '    <instructorchoiceallowsetting>' . $question->options->instructorchoiceallowsetting .
                 "</instructorchoiceallowsetting>\n";
        $expout .= '    <instructorcustomparameters>' . $format->writetext($question->options->instructorcustomparameters) .
                 "</instructorcustomparameters>\n";
        $expout .= '    <grade>' . $question->options->grade .
                "</grade>\n";
        $expout .= '    <launchcontainer>' . $question->options->launchcontainer .
                "</launchcontainer>\n";
        $expout .= '    <resourcekey>' . $format->writetext($question->options->resourcekey) .
                 "</resourcekey>\n";
        $expout .= '    <password>' . $format->writetext($question->options->password) .
                 "</password>\n";
        $expout .= '    <debuglaunch>' . $question->options->debuglaunch.
                "</debuglaunch>\n";
        $expout .= '    <showtitlelaunch>' . $question->options->showtitlelaunch .
                "</showtitlelaunch>\n";
        $expout .= '    <showdescriptionlaunch>' . $question->options->showdescriptionlaunch .
                 "</showdescriptionlaunch>\n";
        $expout .= '    <servicesalt>' . $format->writetext($question->options->servicesalt) .
                 "</servicesalt>\n";
        $expout .= '    <icon>' . $format->writetext($question->options->icon) .
                "</icon>\n";
        $expout .= '    <secureicon>' . $format->writetext($question->options->secureicon) .
                "</secureicon>\n";


/*      KEEP FOR ROUND 2 - ADVANCED EXPORT/IMPORT.
        // Backup LTI type, based on typeid.
        if(!is_null($question->options->typeid) && $question->options->typeid != 0){

          $ltitype = $DB->get_record("qtype_lti_types", array('id' => $question->options->typeid));

          if($ltitype) {
            $expout .= "    <ltitype>\n";

            $expout .= "<name>";
            $expout .= $format->writetext($ltitype->name);
            $expout .= "</name>\n";

            $expout .= "<baseurl>";
            $expout .= $format->writetext($ltitype->baseurl);
            $expout .= "</baseurl>\n";

            $expout .= "<tooldomain>";
            $expout .= $format->writetext($ltitype->tooldomain);
            $expout .= "</tooldomain>\n";

            $expout .= "<state>";
            $expout .= $ltitype->state;
            $expout .= "</state>\n";

            $expout .= "<coursevisible>";
            $expout .= $ltitype->coursevisible;
            $expout .= "</coursevisible>\n";

            $expout .= "<toolproxyid>";
            $expout .= $ltitype->toolproxyid;
            $expout .= "</toolproxyid>\n";

            $expout .= "<enabledcapability>";
            $expout .= $format->writetext($ltitype->enabledcapability);
            $expout .= "</enabledcapability>\n";

            $expout .= "<icon>";
            $expout .= $format->writetext($ltitype->icon);
            $expout .= "</icon>\n";

            $expout .= "<secureicon>";
            $expout .= $format->writetext($ltitype->secureicon);
            $expout .= "</secureicon>\n";


            $expout .= "<createdby>";
            $expout .= $ltitype->createdby;
            $expout .= "</createdby>\n";

            $expout .= "<timecreated>";
            $expout .= $ltitype->timecreated;
            $expout .= "</timecreated>\n";

            $expout .= "<timemodified>";
            $expout .= $ltitype->timemodified;
            $expout .= "</timemodified>\n";

            $expout .= "<description>";
            $expout .= $format->writetext($ltitype->description);
            $expout .= "</description>\n";


            // LTI Tpyes configs.

            $ltitypesconfigs = $DB->get_records('qtype_lti_types_config', array('typeid' => $question->options->typeid));

            if($ltitypesconfigs) {

              $expout .= "      <ltitypesconfigs>\n";

              foreach ($ltitypesconfigs as $ltitypesconfig) {

                $expout .= "<ltitypesconfig id=\"$ltitypesconfig->id\">";

                $expout .= "<name>";
                $expout .= $format->writetext($ltitypesconfig->name);
                $expout .= "</name>\n";

                $expout .= "<value>";
                $expout .= $format->writetext($ltitypesconfig->value);
                $expout .= "</value>\n";

                $expout .= "</ltitypesconfig>\n";
              }

              $expout .= "      </ltitypesconfigs>\n";

            }


            // If this is LTI 2 tool add settings for the current question.

            if( $ltitype->toolproxyid && $ltitype->toolproxyid != 0 ){

                $ltitoolproxy = $DB->get_record('qtype_lti_tool_proxies', array('id' => $ltitype->toolproxyid));

                if($ltitoolproxy) {
                  $expout .= "      <ltitoolproxy>\n";

                  $expout .= "<name>";
                  $expout .= $format->writetext($ltitoolproxy->name);
                  $expout .= "</name>\n";

                  $expout .= "<state>";
                  $expout .= $ltitoolproxy->state;
                  $expout .= "</state>\n";

                  $expout .= "<guid>";
                  $expout .= $format->writetext($ltitoolproxy->guid);
                  $expout .= "</guid>\n";

                  $expout .= "<secret>";
                  $expout .= $format->writetext($ltitoolproxy->secret);
                  $expout .= "</secret>\n";

                  $expout .= "<vendorcode>";
                  $expout .= $format->writetext($ltitoolproxy->vendorcode);
                  $expout .= "</vendorcode>\n";

                  $expout .= "<capabilityoffered>";
                  $expout .= $format->writetext($ltitoolproxy->capabilityoffered);
                  $expout .= "</capabilityoffered>\n";

                  $expout .= "<serviceoffered>";
                  $expout .= $format->writetext($ltitoolproxy->serviceoffered);
                  $expout .= "</serviceoffered>\n";

                  $expout .= "<toolproxy>";
                  $expout .= $format->writetext($ltitoolproxy->toolproxy);
                  $expout .= "</toolproxy>\n";

                  $expout .= "<createdby>";
                  $expout .= $ltitoolproxy->createdby;
                  $expout .= "</createdby>\n";

                  $expout .= "<timecreated>";
                  $expout .= $ltitoolproxy->timecreated;
                  $expout .= "</timecreated>\n";

                  $expout .= "</timemodified>";
                  $expout .= $ltitoolproxy->timemodified;
                  $expout .= "</timemodified>\n";

                  // Now Tool Proxy Settings

                  $ltitoolsettings = $DB->get_records('qtype_lti_tool_settings', array('toolproxyid' => $ltitype->toolproxyid));

                  if($ltitoolsettings) {

                    $expout .= "      <ltitoolsettings>\n";

                    foreach ($ltitoolsettings as $ltitoolsetting) {

                      $expout .= "<ltitoolsetting id=\"$ltitoolsetting->id\">\n";

                      $expout .= "<settings>";
                      $expout .= $format->writetext($ltitoolsetting->settings);
                      $expout .= "</settings>\n";

                      $expout .= "<timecreated>";
                      $expout .= $ltitoolsetting->timecreated;
                      $expout .= "</timecreated>\n";

                      $expout .= "<timemodified>";
                      $expout .= $ltitoolsetting->timemodified;
                      $expout .= "</timemodified>\n";

                      $expout .= "</ltitoolsetting>\n";

                    }

                    $expout .= "      </ltitoolsettings>\n";

                  }

                  $expout .= "      </ltitoolproxy>\n";
                }
            }


            $expout .= "    </ltitype>\n";
          }


        }
*/
        return $expout;
    }

    /**
     * Provide import functionality for xml format.
     *
     * @param data mixed the segment of data containing the question
     * @param question object question object processed (so far) by standard import code
     * @param format object the format object so that helper methods can be used (in particular
     *        error())
     * @param extra mixed any additional format specific data that may be passed by the format (see
     *        format code for info)
     *
     * @return object question object suitable for save_options() call or false if cannot handle
     */
    public function import_from_xml($data, $question, qformat_xml $format, $extra = null) {
        global $COURSE, $DB;
        // Check whether the question is for us.
        if (!isset($data['@']['type']) || $data['@']['type'] != 'lti') {
            return false;
        }
        $question = $format->import_headers($data);
        $question->qtype = 'lti';

        $question->instancecode = $format->getpath($data,
        array('#', 'instancecode', 0, '#', 'text', 0, '#'
        ), '');

        /*
        $question->course = $format->getpath($data,
                array('#', 'course', 0, '#'), 1);
        */

        // Update course to be the current import one.
        $question->course = $COURSE->id;

        $question->cmid = $format->getpath($data,
                array('#', 'cmid', 0, '#'), 1);

        $question->typeid = $format->getpath($data,
        array('#', 'typeid', 0, '#'), null);

        $question->toolurl = $format->getpath($data,
        array('#', 'toolurl', 0, '#', 'text', 0, '#'
        ), '');
        $question->securetoolurl = $format->getpath($data,
        array('#', 'securetoolurl', 0, '#', 'text', 0, '#'
        ), '');

        $question->instructorchoicesendname = $format->getpath($data,
        array('#', 'instructorchoicesendname', 0, '#'), 1);

        $question->instructorchoicesendemailaddr = $format->getpath($data,
        array('#', 'instructorchoicesendemailaddr', 0, '#'), 1);

        $question->instructorchoiceallowroster = $format->getpath($data,
        array('#', 'instructorchoiceallowroster', 0, '#'), 1);

        $question->instructorchoiceallowsetting = $format->getpath($data,
        array('#', 'instructorchoiceallowsetting', 0, '#'), 1);

        $question->instructorchoiceacceptgrades = $format->getpath($data,
        array('#', 'instructorchoiceacceptgrades', 0, '#'), 1);

        $question->instructorcustomparameters = $format->getpath($data,
        array('#', 'instructorcustomparameters', 0, '#', 'text', 0, '#'
        ), '');

        $question->grade = $format->getpath($data,
        array('#', 'grade', 0, '#'), 100);


        $question->resourcekey = $format->getpath($data,
        array('#', 'resourcekey', 0, '#', 'text', 0, '#'
        ), '');
        $question->password = $format->getpath($data,
        array('#', 'password', 0, '#', 'text', 0, '#'
        ), '');

        $question->launchcontainer = $format->getpath($data,
        array('#', 'launchcontainer', 0, '#'), 1);
        $question->debuglaunch = $format->getpath($data,
        array('#', 'debuglaunch', 0, '#'), 0);
        $question->showtitlelaunch = $format->getpath($data,
        array('#', 'showtitlelaunch', 0, '#'), 0);
        $question->showdescriptionlaunch = $format->getpath($data,
        array('#', 'showdescriptionlaunch', 0, '#'), 0);


        $question->servicesalt = $format->getpath($data,
        array('#', 'servicesalt', 0, '#', 'text', 0, '#'
        ), '');


        $question->icon = $format->getpath($data,
        array('#', 'icon', 0, '#', 'text', 0, '#'
        ), '');
        $question->secureicon = $format->getpath($data,
        array('#', 'secureicon', 0, '#', 'text', 0, '#'
        ), '');



        // Do types, proxy, config etc. KEEP FOR ROUND 2.



        return $question;
    }

}
