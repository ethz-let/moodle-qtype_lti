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
 * Provides the backup for lti questions.
 */
class backup_qtype_lti_plugin extends backup_qtype_plugin {

    /**
     * Returns the qtype information to attach to the question element.
     */
    protected function define_question_plugin_structure() {
        global $DB;

        // Define the virtual plugin element with the condition to fulfill.
        $plugin = $this->get_plugin_element(null, '../../qtype', 'lti');
        // Create one standard named plugin element (the visible container).
        $name = $this->get_recommended_name();

        $pluginwrapper = new backup_nested_element($name);

        // Connect the visible container ASAP.
        $plugin->add_child($pluginwrapper);

        // Define each element separated.
        $lti = new backup_nested_element('lti', array('id'),
                                        array('instancecode', 'course', 'cmid', 'questionid', 'typeid', 'toolurl', 'securetoolurl',
                                            'preferheight', 'launchcontainer', 'instructorchoicesendname',
                                            'instructorchoicesendemailaddr', 'instructorchoiceacceptgrades',
                                            'instructorchoiceallowroster', 'instructorchoiceallowsetting', 'grade',
                                            'instructorcustomparameters', 'debuglaunch', 'showtitlelaunch', 'showdescriptionlaunch',
                                            'icon', 'secureicon', 'resourcekey', 'password'));

        $ltitype = new backup_nested_element('ltitype', array('id'),
                                            array('name', 'baseurl', 'tooldomain', 'state', 'course', 'coursevisible',
                                                'toolproxyid', 'enabledcapability', 'parameter', 'icon', 'secureicon', 'createdby',
                                                'timecreated', 'timemodified', 'description'));

        $ltitypesconfigs = new backup_nested_element('ltitypesconfigs');

        $ltitypesconfig = new backup_nested_element('ltitypesconfig', array('id'),
                                                    array('name', 'value'));
        $ltitypesconfigencrypted = new backup_nested_element('ltitypesconfigencrypted', array('id'),
                                                            array('name', 'value'));

        $ltitoolproxy = new backup_nested_element('ltitoolproxy', array('id'));

        $ltitoolsettings = new backup_nested_element('ltitoolsettings');

        $ltitoolsetting = new backup_nested_element('ltitoolsetting', array('id'),
                                                    array('settings', 'timecreated', 'timemodified'));
        $ltisubmissions = new backup_nested_element('ltisubmissions');

        $ltisubmission = new backup_nested_element('ltisubmission', array('id'),
                                                array('username', 'linkid', 'resultid', 'datesubmitted', 'dateupdated',
                                                    'gradepercent', 'originalgrade', 'state'));

        $ltiusagemappings = new backup_nested_element('qtype_lti_usages');
        $ltiusagemapping = new backup_nested_element('qtype_lti_usage', array('id'),
                                                    array('ltiid', 'instancecode', 'attemptid', 'mattemptid', 'questionid',
                                                        'quizid', 'courseid', 'userid', 'resourcelinkid', 'resultid', 'origin',
                                                        'destination', 'timeadded'));

        // Now the qtype tree.
        $pluginwrapper->add_child($lti);
        // Build the tree.
        $lti->add_child($ltitype);
        $ltitype->add_child($ltitypesconfigs);
        $ltitypesconfigs->add_child($ltitypesconfig);
        $ltitypesconfigs->add_child($ltitypesconfigencrypted);
        $ltitype->add_child($ltitoolproxy);
        $ltitoolproxy->add_child($ltitoolsettings);
        $ltitoolsettings->add_child($ltitoolsetting);
        $lti->add_child($ltisubmissions);
        $ltisubmissions->add_child($ltisubmission);
        $lti->add_child($ltiusagemappings);
        $ltiusagemappings->add_child($ltiusagemapping);

        // Define sources.

        // LTI Options.
        $lti->set_source_table('qtype_lti_options', array('questionid' => backup::VAR_PARENTID));

        // LTI types, per QuestionID.
        $ltitype->set_source_sql('SELECT * FROM {qtype_lti_types} WHERE id =
                                (select typeid from {qtype_lti_options} where id = :questionid)',
                                array('questionid' => backup::VAR_PARENTID));

        // Add type config values.
        $ltitypesconfig->set_source_sql(
                                        "SELECT id, name, value
            FROM {qtype_lti_types_config}
            WHERE typeid = :typeid",
                                        array('typeid' => backup::VAR_PARENTID));
        $ltitypesconfigencrypted->set_source_sql(
                                                "SELECT id, name, value
            FROM {qtype_lti_types_config}
            WHERE typeid = :typeid",
                                                array('typeid' => backup::VAR_PARENTID));
        // If this is LTI 2 tool add settings for the current activity.

        $ltitoolproxy->set_source_sql(
                                    "SELECT id
            FROM {qtype_lti_tool_proxies}
            WHERE id = ( select toolproxyid from {qtype_lti_types} WHERE id = :typeid) ",
                                    array('typeid' => backup::VAR_PARENTID));

        $ltitoolsetting->set_source_sql("SELECT *
                                        FROM {qtype_lti_tool_settings}
                                        WHERE toolproxyid = :toolproxyid ",
                                        array('toolproxyid' => backup::VAR_PARENTID));

        // All the rest of elements only happen if we are including user info.
        $ltisubmission->set_source_sql('SELECT * FROM {qtype_lti_submission} WHERE ltiid = :ltiid',
                                    array('ltiid' => backup::VAR_PARENTID));

        // All the rest of mapping elements only happen if we are including user info.
        $ltiusagemapping->set_source_sql('SELECT * FROM {qtype_lti_usage} WHERE ltiid = :ltiid',
                                        array('ltiid' => backup::VAR_PARENTID));

        // Define id annotations.
        $ltitype->annotate_ids('user', 'createdby');
        $ltiusagemapping->annotate_ids('user', 'userid');

        // Return the root element (activity).
        return $plugin;
    }

    /**
     * Retrieves a record from {qtype_lti_type} table associated with the current qtype
     * Information about site tools is not returned because it is insecure to back it up,
     * only fields necessary for same-site tool matching are left in the record
     *
     * @param stdClass $ltirecord
     *        record from {qtype_lti_options} table
     * @return stdClass|null
     */
    protected function retrieve_lti_type($ltirecord) {
        global $DB;
        if (!$ltirecord->typeid) {
            return null;
        }
        $record = $DB->get_record('qtype_lti_types', ['id' => $ltirecord->typeid]);
        if ($record && $record->course == SITEID) {
            // Site LTI types or registrations are not backed up except for their name (which is visible).
            // Predefined course types can be backed up.
            $allowedkeys = ['id', 'course', 'name', 'toolproxyid'];
            foreach ($record as $key => $value) {
                if (!in_array($key, $allowedkeys)) {
                    $record->$key = null;
                }
            }
        }
        return $record;
    }
}
