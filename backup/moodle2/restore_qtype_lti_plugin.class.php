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
        return $paths;
    }

    /**
     * Detect if the question is created or mapped.
     *
     * @return bool
     */
    protected function is_question_created() {
        $oldquestionid = $this->get_old_parentid('question');
        $questioncreated = $this->get_mappingid('question_created', $oldquestionid) ? true : false;
        return $questioncreated;
    }
    public function process_lti($data) {
        global $DB;
        $data = (object)$data;
        $oldid = $data->id;


        // Detect if the question is created or mapped.
        $oldquestionid = $this->get_old_parentid('question');
        $newquestionid = $this->get_new_parentid('question');
        $questioncreated = $this->get_mappingid('question_created', $oldquestionid) ? true : false;

        // Any changes to the list of dates that needs to be rolled should be same during course restore and course reset.
        // See MDL-9367.
        // Grade used to be a float (whole numbers only), restore as int.
        $data->grade = (int)$data->grade;
        $data->course = $this->task->get_courseid();
        // Try to get resourcekey and password. Null if not possible (DB default).
        $data->resourcekey = isset($data->resourcekey) ? $data->resourcekey : null;
        $data->password = isset($data->password) ? $data->password : null;

        $ltipluginconfig = get_config('qtype_lti');

        if (isset($ltipluginconfig->removerestoredlink) && $ltipluginconfig->removerestoredlink == 1) {
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

    public function recode_response($questionid, $sequencenumber, array $response) {
        if (array_key_exists('_order', $response)) {
            $response['_order'] = $response['_order'];
        }

        return $response;
    }
     /**
     * Return the contents of this qtype to be processed by the links decoder
     */
    public static function define_decode_contents() {
        $contents = [];

        $contents[] = new restore_decode_content('qtype_lti_options', ['instancecode', 'course', 'cmid', 'questionid', 'typeid', 'toolurl', 'securetoolurl',
                                            'launchcontainer', 'instructorchoicesendname',
                                            'instructorchoicesendemailaddr', 'instructorchoiceacceptgrades',
                                            'instructorchoiceallowroster', 'instructorchoiceallowsetting', 'grade',
                                            'instructorcustomparameters', 'debuglaunch', 'showtitlelaunch', 'showdescriptionlaunch',
                                            'icon', 'secureicon', 'resourcekey', 'password'], 'qtype_lti_options');

        return $contents;
    }
     /**
     * Convert the backup structure of the LTI question type into a structure matching its
     * question data. This data will then be used to produce an identity hash for comparison with
     * questions in the database. We have to override the parent function, because we use a special
     * structure during backup.
     *
     * @param array $backupdata
     * @return stdClass
     */

    public static function convert_backup_to_questiondata(array $backupdata): stdClass {
        // First, convert standard data via the parent function.
        $questiondata = parent::convert_backup_to_questiondata($backupdata);
        
        if(is_null($questiondata->options->securetoolurl) || empty($questiondata->options->securetoolurl)){
           $questiondata->options->securetoolurl = '';
        }
        if(is_null($questiondata->options->toolurl) || empty($questiondata->options->toolurl)){
            $questiondata->options->toolurl = '';
         }
        if(is_null($questiondata->options->instructorcustomparameters) || empty($questiondata->options->instructorcustomparameters)){
            $questiondata->options->instructorcustomparameters = '';
        }
        if(is_null($questiondata->options->icon) || empty($questiondata->options->icon)){
            $questiondata->options->icon = '';
        }
        if(is_null($questiondata->options->secureicon) || empty($questiondata->options->secureicon)){
            $questiondata->options->secureicon = '';
        }
        if(is_null($questiondata->options->resourcekey) || empty($questiondata->options->resourcekey)){
            $questiondata->options->resourcekey = '';
        }
        if(is_null($questiondata->options->password) || empty($questiondata->options->password)){
            $questiondata->options->password = '';
        }
        if(is_null($questiondata->options->servicesalt) || empty($questiondata->options->servicesalt)){
            $questiondata->options->servicesalt = '';
        }


        return $questiondata;
    }

    /**
     * Return a list of paths to fields to be removed from questiondata before creating an identity hash.
     * We have to remove the id and questionid property from all rows, columns and weights.
     *
     * @return array
     */
    protected function define_excluded_identity_hash_fields(): array {
        return [
            '/options/id',
            '/options/questionid',

        ];
     
    }
    
    public static function remove_excluded_question_data(stdClass $questiondata, array $excludefields = []): stdClass {
        return parent::remove_excluded_question_data($questiondata, $excludefields);
    }
}
