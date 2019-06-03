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

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir.'/formslib.php');
require_once($CFG->dirroot.'/question/type/lti/locallib.php');

class qtype_lti_edit_types_form extends moodleform{
    public function definition() {
        global $CFG;

        $mform    =& $this->_form;
        $istool = $this->_customdata && $this->_customdata->istool;

        // Add basiclti elements.
        $mform->addElement('header', 'setup', get_string('tool_settings', 'qtype_lti'));
        $mform->addElement('text', 'lti_typename', get_string('typename', 'qtype_lti'));
        $mform->setType('lti_typename', PARAM_TEXT);
        $mform->addHelpButton('lti_typename', 'typename', 'qtype_lti');
        $mform->addRule('lti_typename', null, 'required', null, 'client');

        $mform->addElement('text', 'lti_toolurl', get_string('toolurl', 'qtype_lti'), array('size' => '64'));
        $mform->setType('lti_toolurl', PARAM_URL);
        $mform->addHelpButton('lti_toolurl', 'toolurl', 'qtype_lti');

        $mform->addElement('hidden', 'lti_description', ' ');
        $mform->setType('lti_description', PARAM_TEXT);
        if (!$istool) {
            $mform->addRule('lti_toolurl', null, 'required', null, 'client');
        } else {
            $mform->disabledIf('lti_toolurl', null);
        }

        if (!$istool) {
            $mform->addElement('text', 'lti_resourcekey', get_string('resourcekey_admin', 'qtype_lti'));
            $mform->setType('lti_resourcekey', PARAM_TEXT);
            $mform->addHelpButton('lti_resourcekey', 'resourcekey_admin', 'qtype_lti');
            $mform->setForceLtr('lti_resourcekey');

            $mform->addElement('passwordunmask', 'lti_password', get_string('password_admin', 'qtype_lti'));
            $mform->setType('lti_password', PARAM_TEXT);
            $mform->addHelpButton('lti_password', 'password_admin', 'qtype_lti');
        }

        if ($istool) {
            $mform->addElement('textarea', 'lti_parameters', get_string('parameter', 'qtype_lti'),
                            array('rows' => 4, 'cols' => 60));
            $mform->setType('lti_parameters', PARAM_TEXT);
            $mform->addHelpButton('lti_parameters', 'parameter', 'qtype_lti');
            $mform->disabledIf('lti_parameters', null);
            $mform->setForceLtr('lti_parameters');
        }

        $mform->addElement('textarea', 'lti_customparameters', get_string('custom', 'qtype_lti'), array('rows' => 4, 'cols' => 60));
        $mform->setType('lti_customparameters', PARAM_TEXT);
        $mform->addHelpButton('lti_customparameters', 'custom', 'qtype_lti');
        $mform->setForceLtr('lti_customparameters');

        if (!empty($this->_customdata->isadmin)) {
            $options = array(
                QTYPE_LTI_COURSEVISIBLE_NO => get_string('show_in_course_no', 'qtype_lti'),
                QTYPE_LTI_COURSEVISIBLE_PRECONFIGURED => get_string('show_in_course_preconfigured', 'qtype_lti'),
                QTYPE_LTI_COURSEVISIBLE_ACTIVITYCHOOSER => get_string('show_in_course_activity_chooser', 'qtype_lti'),
            );
            if ($istool) {
                // LTI2 tools can not be matched by URL, they have to be either in preconfigured tools or in activity chooser.
                unset($options[QTYPE_LTI_COURSEVISIBLE_NO]);
                $stringname = 'show_in_course_lti2';
            } else {
                $stringname = 'show_in_course_lti1';
            }
            $mform->addElement('select', 'lti_coursevisible', get_string($stringname, 'qtype_lti'), $options);
            $mform->addHelpButton('lti_coursevisible', $stringname, 'qtype_lti');
            $mform->setDefault('lti_coursevisible', '0');
        } else {
            $mform->addElement('hidden', 'lti_coursevisible', QTYPE_LTI_COURSEVISIBLE_PRECONFIGURED);
        }
        $mform->setType('lti_coursevisible', PARAM_INT);

        $mform->addElement('hidden', 'typeid');
        $mform->setType('typeid', PARAM_INT);

        $launchoptions = array();
        $launchoptions[QTYPE_LTI_LAUNCH_CONTAINER_EMBED] = get_string('embed', 'qtype_lti');
        $launchoptions[QTYPE_LTI_LAUNCH_CONTAINER_EMBED_NO_BLOCKS] = get_string('embed_no_blocks', 'qtype_lti');
        $launchoptions[QTYPE_LTI_LAUNCH_CONTAINER_REPLACE_MOODLE_WINDOW] = get_string('existing_window', 'qtype_lti');
        $launchoptions[QTYPE_LTI_LAUNCH_CONTAINER_WINDOW] = get_string('new_window', 'qtype_lti');

        $mform->addElement('select', 'lti_launchcontainer', get_string('default_launch_container', 'qtype_lti'), $launchoptions);
        $mform->setDefault('lti_launchcontainer', QTYPE_LTI_LAUNCH_CONTAINER_EMBED_NO_BLOCKS);
        $mform->addHelpButton('lti_launchcontainer', 'default_launch_container', 'qtype_lti');
        $mform->setType('lti_launchcontainer', PARAM_INT);

        $mform->addElement('advcheckbox', 'lti_contentitem', get_string('contentitem', 'qtype_lti'));
        $mform->addHelpButton('lti_contentitem', 'contentitem', 'qtype_lti');
        $mform->setAdvanced('lti_contentitem');
        if ($istool) {
            $mform->disabledIf('lti_contentitem', null);
        }

        $mform->addElement('hidden', 'oldicon');
        $mform->setType('oldicon', PARAM_URL);

        $mform->addElement('text', 'lti_icon', get_string('icon_url', 'qtype_lti'), array('size' => '64'));
        $mform->setType('lti_icon', PARAM_URL);
        $mform->setAdvanced('lti_icon');
        $mform->addHelpButton('lti_icon', 'icon_url', 'qtype_lti');

        $mform->addElement('text', 'lti_secureicon', get_string('secure_icon_url', 'qtype_lti'), array('size' => '64'));
        $mform->setType('lti_secureicon', PARAM_URL);
        $mform->setAdvanced('lti_secureicon');
        $mform->addHelpButton('lti_secureicon', 'secure_icon_url', 'qtype_lti');

        $options = array();
        $options[0] = get_string('never', 'qtype_lti');
        $options[1] = get_string('always', 'qtype_lti');
        $options[2] = get_string('delegate', 'qtype_lti');

        $mform->addElement('checkbox', 'lti_forcessl', '&nbsp;', ' ' . get_string('force_ssl', 'qtype_lti'), $options);
        $mform->setType('lti_forcessl', PARAM_BOOL);
        if (!empty($CFG->qtype_lti_forcessl)) {
            $mform->setDefault('lti_forcessl', '1');
            $mform->freeze('lti_forcessl');
        } else {
            $mform->setDefault('lti_forcessl', '0');
        }
        $mform->addHelpButton('lti_forcessl', 'force_ssl', 'qtype_lti');

        if (!$istool) {

            // Privacy.
            $mform->addElement('hidden', 'lti_sendname', 1);
            $mform->setType('lti_sendname', PARAM_INT);
            $mform->addElement('hidden', 'lti_sendemailaddr', 1);
            $mform->setType('lti_sendemailaddr', PARAM_INT);
            $mform->addElement('hidden', 'lti_acceptgrades', 1);
            $mform->setType('lti_acceptgrades', PARAM_INT);

            if (!empty($this->_customdata->isadmin)) {
                // Add setup parameters fieldset.
                $mform->addElement('header', 'setupoptions', get_string('miscellaneous', 'qtype_lti'));

                // Adding option to change id that is placed in context_id.
                $idoptions = array();
                $idoptions[0] = get_string('id', 'qtype_lti');
                $idoptions[1] = get_string('courseid', 'qtype_lti');

                $mform->addElement('text', 'lti_organizationid', get_string('organizationid', 'qtype_lti'));
                $mform->setType('lti_organizationid', PARAM_TEXT);
                $mform->addHelpButton('lti_organizationid', 'organizationid', 'qtype_lti');

                $mform->addElement('text', 'lti_organizationurl', get_string('organizationurl', 'qtype_lti'));
                $mform->setType('lti_organizationurl', PARAM_URL);
                $mform->addHelpButton('lti_organizationurl', 'organizationurl', 'qtype_lti');
            }
        }

        $tab = optional_param('tab', '', PARAM_ALPHAEXT);
        $mform->addElement('hidden', 'tab', $tab);
        $mform->setType('tab', PARAM_ALPHAEXT);

        $courseid = optional_param('courseid', 1, PARAM_INT);
        $mform->addElement('hidden', 'course', $courseid);
        $mform->setType('course', PARAM_INT);

        // Add standard buttons, common to all modules.
        $this->add_action_buttons();

    }

    /**
     * Retrieves the data of the submitted form.
     *
     * @return stdClass
     */
    public function get_data() {
        $data = parent::get_data();
        if ($data && !empty($this->_customdata->istool)) {
            // Content item checkbox is disabled in tool settings, so this cannot be edited. Just unset it.
            unset($data->lti_contentitem);
        }
        return $data;
    }
}
