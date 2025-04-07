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
     * Get the name of this question type.
     *
     * @return string the question type, like 'ddmarker'.
     */
    protected static function qtype_name() {
        return 'lti';
    }
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
                                            'launchcontainer', 'instructorchoicesendname',
                                            'instructorchoicesendemailaddr', 'instructorchoiceacceptgrades',
                                            'instructorchoiceallowroster', 'instructorchoiceallowsetting', 'grade',
                                            'instructorcustomparameters', 'debuglaunch', 'showtitlelaunch', 'showdescriptionlaunch',
                                            'icon', 'secureicon', 'resourcekey', 'password'));
        // Now the qtype tree.
        $pluginwrapper->add_child($lti);
        // Define sources.

        // LTI Options.
        $lti->set_source_table('qtype_lti_options', array('questionid' => backup::VAR_PARENTID));

        // Return the root element (activity).
        return $plugin;
    }

    /**
     * Returns one array with filearea => mappingname elements for the qtype
     *
     * Used by {@link get_components_and_fileareas} to know about all the qtype
     * files to be processed both in backup and restore.
     */
    public static function get_qtype_fileareas() {
        return array('toolurl' => 'question_created');
    }
}
