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
 * @copyright ETHz 2016 amr.hourani@id.ethz.ch
 */
defined('MOODLE_INTERNAL') || die();


/**
 * Restore plugin class that provides the necessary information
 * needed to restore one lti qtype plugin.
 */
class restore_qtype_lti_plugin extends restore_qtype_plugin {

    /**
     * Returns the paths to be handled by the plugin at question level.
     */
    protected function define_question_plugin_structure() {
        $paths = array();
        $paths[] = new restore_path_element('lti', $this->get_pathfor('/lti'));
        $paths[] = new restore_path_element('ltitype', $this->get_pathfor('/lti/ltitype'));
        $paths[] = new restore_path_element('ltitypesconfig', $this->get_pathfor('/lti/ltitype/ltitypesconfigs/ltitypesconfig'));
        $paths[] = new restore_path_element('ltitypesconfigencrypted', $this->get_pathfor('/lti/ltitype/ltitypesconfigs/ltitypesconfigencrypted'));
        $paths[] = new restore_path_element('ltitoolproxy', $this->get_pathfor('/lti/ltitype/ltitoolproxy'));
        $paths[] = new restore_path_element('ltitoolsetting', $this->get_pathfor('/lti/ltitype/ltitoolproxy/ltitoolsettings/ltitoolsetting'));
        $paths[] = new restore_path_element('ltisubmission', $this->get_pathfor('/lti/ltisubmissions/ltisubmission'));
        $paths[] = new restore_path_element('qtype_lti_usage', $this->get_pathfor('/lti/qtype_lti_usages/qtype_lti_usage'));
        // Return the paths wrapped into standard activity structure.
        return $paths;
    }


    /**
     * Detect if the question is created or mapped.
     *
     * @return bool
     */
    protected function is_question_created() {
        $oldquestionid = $this->get_old_parentid('question');
        $questioncreated = (bool) $this->get_mappingid('question_created', $oldquestionid);
        return $questioncreated;
    }

    public function process_lti($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->task->get_courseid();
        $data->servicesalt = uniqid('', true);

        // Detect if the question is created or mapped.
        $oldquestionid = $this->get_old_parentid('question');
        $newquestionid = $this->get_new_parentid('question');

        $questioncreated = (bool) $this->get_mappingid('question_created', $oldquestionid);


        // Any changes to the list of dates that needs to be rolled should be same during course restore and course reset.
        // See MDL-9367.
        // Grade used to be a float (whole numbers only), restore as int.
        $data->grade = (int) $data->grade;
        $data->typeid = 0;
        // Try to get resourcekey and password. Null if not possible (DB default).
        $data->resourcekey = isset($data->resourcekey) ? $data->resourcekey : null;
        $data->password = isset($data->password) ? $data->password : null;
        
        
        
        $ltipluginconfig= get_config('qtype_lti');
        
        if (isset($ltipluginconfig->removerestoredlink) && $ltipluginconfig->removerestoredlink == 1){
        	$data->securetoolurl = '';
        	$data->toolurl = '';
        }

        // If the question has been created by restore, we need to create its
        // qtype_lti_options too.
        if ($questioncreated) {
            // Adjust some columns.
            $data->questionid = $newquestionid;
            $newitemid = $DB->insert_record('qtype_lti_options', $data);
            // Create mapping (needed for decoding links).
            $this->set_mapping('lti', $oldid, $newitemid);
          }
    }
    /**
     * Process an lti type restore
     * @param mixed $data The data from backup XML file
     * @return void
     */
    public function process_ltitype($data) {
        global $DB, $USER;

        if (!$this->is_question_created()) {
            return;
        }

        $data = (object)$data;
        $oldid = $data->id;
        if (!empty($data->createdby)) {
            $data->createdby = $this->get_mappingid('user', $data->createdby) ?: $USER->id;
        }
        $courseid = $this->task->get_courseid();
        $data->course = ($this->get_mappingid('course', $data->course) == $courseid) ? $courseid : SITEID;
        // Try to find existing lti type with the same properties.
        $ltitypeid = $this->find_existing_lti_type($data);
        $this->newltitype = false;
        if (!$ltitypeid) {// && $data->course == $courseid
            unset($data->toolproxyid); // Course tools can not use LTI2.
            $data->coursevisible = 0;
            $ltitypeid = $DB->insert_record('qtype_lti_types', $data);
            $this->newltitype = true;
            $this->set_mapping('ltitype', $oldid, $ltitypeid);
        }
        // Add the typeid entry back to LTI module.
        $DB->update_record('qtype_lti_options', ['id' => $this->get_new_parentid('lti'), 'typeid' => $ltitypeid]);
    }
    /**
     * Attempts to find existing record in lti_type
     * @param stdClass $data
     * @return int|null field lti_types.id or null if tool is not found
     */
    protected function find_existing_lti_type($data) {
        global $DB;
        if ($ltitypeid = $this->get_mappingid('ltitype', $data->id)) {
            return $ltitypeid;
        }
        $ltitype = null;
        $params = (array)$data;
        if ($this->task->is_samesite()) {
            // If we are restoring on the same site try to find lti type with the same id.
            $sql = 'id = :id AND course = :course';
            $sql .= ($data->toolproxyid) ? ' AND toolproxyid = :toolproxyid' : ' AND toolproxyid IS NULL';
            if ($DB->record_exists_select('qtype_lti_types', $sql, $params)) {
                $this->set_mapping('ltitype', $data->id, $data->id);
                if ($data->toolproxyid) {
                    $this->set_mapping('ltitoolproxy', $data->toolproxyid, $data->toolproxyid);
                }
                return $data->id;
            }
        }
        if ($data->course != $this->task->get_courseid()) {
            // Site tools are not backed up and are not restored.
            return null;
        }
        // Now try to find the same type on the current site available in this course.
        // Compare only fields baseurl, course and name, if they are the same we assume it is the same tool.
        // LTI2 is not possible in the course so we add "lt.toolproxyid IS NULL" to the query.
        $sql = 'SELECT id
            FROM {qtype_lti_types}
           WHERE ' . $DB->sql_compare_text('baseurl', 255) . ' = ' . $DB->sql_compare_text(':baseurl', 255) . ' AND
                 course = :course AND name = :name AND toolproxyid IS NULL';
        if ($ltitype = $DB->get_record_sql($sql, $params, IGNORE_MULTIPLE)) {
            $this->set_mapping('ltitype', $data->id, $ltitype->id);
            return $ltitype->id;
        }
        return null;
    }
    /**
     * Process an lti config restore
     * @param mixed $data The data from backup XML file
     */
    public function process_ltitypesconfig($data) {
        global $DB;
        if (!$this->is_question_created()) {
            return;
        }
        $data = (object)$data;
        $data->typeid = $this->get_new_parentid('ltitype');
        // Only add configuration if the new lti_type was created.
        if ($data->typeid && $this->newltitype) {
            if ($data->name == 'servicesalt') {
                $data->value = uniqid('', true);
            }
            $DB->insert_record('qtype_lti_types_config', $data);
        }
    }
    /**
     * Process an lti config restore
     * @param mixed $data The data from backup XML file
     */
    public function process_ltitypesconfigencrypted($data) {
        global $DB;
        if (!$this->is_question_created()) {
            return;
        }
        $data = (object)$data;
        $data->typeid = $this->get_new_parentid('ltitype');
        // Only add configuration if the new lti_type was created.
        if ($data->typeid && $this->newltitype) {
          //  $data->value = $this->decrypt($data->value);
            if (!is_null($data->value)) {
                $DB->insert_record('qtype_lti_types_config', $data);
            }
        }
    }
    /**
     * Process a restore of LTI tool registration
     * This method is empty because we actually process registration as part of process_ltitype()
     * @param mixed $data The data from backup XML file
     */
    public function process_ltitoolproxy($data) {
    }
    /**
     * Process an lti tool registration settings restore (only settings for the current activity)
     * @param mixed $data The data from backup XML file
     */
    public function process_ltitoolsetting($data) {
        global $DB;
        if (!$this->is_question_created()) {
            return;
        }
        $data = (object)$data;
        $data->toolproxyid = $this->get_new_parentid('ltitoolproxy');
        if (!$data->toolproxyid) {
            return;
        }
        $newquestionid = $this->get_new_parentid('question');
        $data->course = $this->task->get_courseid();
        $data->questionid = $newquestionid;
        $DB->insert_record('qtype_lti_tool_settings', $data);
    }
    /**
     * Process a submission restore
     * @param mixed $data The data from backup XML file
     */
    public function process_ltisubmission($data) {
        global $DB;
        if (!$this->is_question_created()) {
            return;
        }
        $data = (object)$data;
        $oldid = $data->id;
        $data->ltiid = $this->get_new_parentid('lti');
        $data->datesubmitted = $this->apply_date_offset($data->datesubmitted);
        $data->dateupdated = $this->apply_date_offset($data->dateupdated);

        $newitemid = $DB->insert_record('qtype_lti_submission', $data);
        $this->set_mapping('ltisubmission', $oldid, $newitemid);
    }
    /**
     * Process a usage mapping restore
     * @param mixed $data The data from backup XML file
     */
    public function process_qtype_lti_usage($data) {
    	global $DB;
    	if (!$this->is_question_created()) {
    		return;
    	}

    	$data = (object)$data;
    	$oldid = $data->id;
    	$data->ltiid = $this->get_new_parentid('lti');
    	if ($data->userid > 0) {
    		$data->userid = $this->get_mappingid('user', $data->userid);
    	}

    	if ($data->questionid > 0) {
	    	// Detect if the question is created or mapped.
	        $oldquestionid = $this->get_old_parentid('question');
	        $newquestionid = $this->get_new_parentid('question');
	        $questioncreated = (bool) $this->get_mappingid('question_created', $oldquestionid);
	        if ($questioncreated) {
	            $data->questionid = $newquestionid;
	        }
    	}
    	if ($data->courseid > 0) {
    		$courseid = $this->task->get_courseid();
    		$data->courseid = ($this->get_mappingid('course', $data->courseid) == $courseid) ? $courseid : SITEID;
    	}
    	
    	$newitemid = $DB->insert_record('qtype_lti_usage', $data);
    	$this->set_mapping('qtype_lti_usage', $oldid, $newitemid);
    }
    
    public function recode_response($questionid, $sequencenumber, array $response) {
        if (array_key_exists('_order', $response)) {
            $response['_order'] = $response['_order'];
        }

        return $response;
    }


}
