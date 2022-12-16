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

require_once($CFG->dirroot . '/question/type/edit_question_form.php');
require_once($CFG->dirroot . '/question/type/lti/lib.php');
require_once($CFG->dirroot . '/question/type/lti/locallib.php');
require_once($CFG->dirroot . '/question/engine/bank.php');

/**
 * lti editing form definition.
 *
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_lti_edit_form extends question_edit_form {
    private $numberofrows;
    private $numberofcolumns;

    /**
     * (non-PHPdoc).
     *
     * @see myquestion_edit_form::qtype()
     */
    public function qtype() {
        return 'lti';
    }

    /**
     * Build the form definition.
     * This adds all the form fields that the default question type supports.
     * If your question type does not support all these fields, then you can
     * override this method and remove the ones you don't want with $mform->removeElement().
     */
    protected function definition() {
        global $COURSE, $CFG, $DB, $PAGE, $OUTPUT;

        $qtype = $this->qtype();
        $langfile = "qtype_$qtype";
        $mform = $this->_form;

        // Standard fields at the start of the form.
        $mform->addElement('header', 'generalheader', get_string("general", 'form'));

        if (!isset($this->question->id)) {
            if (!empty($this->question->formoptions->mustbeusable)) {
                $contexts = $this->contexts->having_add_and_use();
            } else {
                $contexts = $this->contexts->having_cap('moodle/question:add');
            }

            // Adding question.
            $mform->addElement('questioncategory', 'category', get_string('category', 'question'), array('contexts' => $contexts));
        } else if (!($this->question->formoptions->canmove || $this->question->formoptions->cansaveasnew)) {
            // Editing question with no permission to move from category.
            $mform->addElement('questioncategory', 'category', get_string('category', 'question'),
                            array('contexts' => array($this->categorycontext)));
            $mform->addElement('hidden', 'usecurrentcat', 1);
            $mform->setType('usecurrentcat', PARAM_BOOL);
            $mform->setConstant('usecurrentcat', 1);
        } else {
            // Editing question with permission to move from category or save as new q.
            $currentgrp = array();
            $currentgrp[0] = $mform->createElement('questioncategory', 'category', get_string('categorycurrent', 'question'),
                            array('contexts' => array($this->categorycontext)));
            // Validate if the question is being duplicated.
            $beingcopied = false;
            if (isset($this->question->beingcopied)) {
                $beingcopied = $this->question->beingcopied;
            }
            if (($this->question->formoptions->canedit || $this->question->formoptions->cansaveasnew) && ($beingcopied)) {
                // Not move only form.
                $currentgrp[1] = $mform->createElement('checkbox', 'usecurrentcat', '',
                                get_string('categorycurrentuse', 'question'));
                $mform->setDefault('usecurrentcat', 1);
            }
            $currentgrp[0]->freeze();
            $currentgrp[0]->setPersistantFreeze(false);
            $mform->addGroup($currentgrp, 'currentgrp', get_string('categorycurrent', 'question'), null, false);

            if (($beingcopied)) {
                $mform->addElement('questioncategory', 'categorymoveto', get_string('categorymoveto', 'question'),
                                array('contexts' => array($this->categorycontext)));
                if ($this->question->formoptions->canedit || $this->question->formoptions->cansaveasnew) {
                    // Not move only form.
                    $mform->disabledIf('categorymoveto', 'usecurrentcat', 'checked');
                }
            }
        }

        if (class_exists('qbank_editquestion\\editquestion_helper') && !empty($this->question->id) && !$this->question->beingcopied) {
            // Add extra information from plugins when editing a question (e.g.: Authors, version control and usage).
            $functionname = 'edit_form_display';
            $questiondata = [];
            $plugins = get_plugin_list_with_function('qbank', $functionname);
            foreach ($plugins as $componentname => $plugin) {
                $element = new StdClass();
                $element->pluginhtml = component_callback($componentname, $functionname, [$this->question]);
                $questiondata['editelements'][] = $element;
            }
            $mform->addElement('static', 'versioninfo', get_string('versioninfo', 'qbank_editquestion'),
                            $PAGE->get_renderer('qbank_editquestion')->render_question_info($questiondata));
        }

        $mform->addElement('text', 'name', get_string('tasktitle', 'qtype_lti'),
                        array('size' => 50, 'maxlength' => 255));
        $mform->setType('name', PARAM_TEXT);

        $mform->addRule('name', null, 'required', null, 'client');

        $mform->addElement('hidden', 'instancecode', ' instance code');
        $mform->setType('instancecode', PARAM_TEXT);
        if (!empty($this->question->options->instancecode)) {
            $instancecode = $this->question->options->instancecode;
        } else {
            $instancecode = uniqid('');
        }
        $mform->setDefault('instancecode', $instancecode);

        $mform->addElement('text', 'defaultmark', get_string('maxpoints', 'qtype_lti'), array('size' => 7));
        $mform->setType('defaultmark', PARAM_FLOAT);
        $mform->setDefault('defaultmark', 1);
        $mform->addRule('defaultmark', null, 'required', null, 'client');

        $mform->addElement('editor', 'questiontext', get_string('stem', 'qtype_lti'),
                        array('rows' => 15, 'class' => 'qtype_lti_stem_hidden'), $this->editoroptions);
        $mform->setType('questiontext', PARAM_RAW);

        $mform->setDefault('questiontext', array('text' => ' '));

        if (class_exists('qbank_editquestion\\editquestion_helper')) {
            $mform->addElement('select', 'status', get_string('status', 'qbank_editquestion'),
                            \qbank_editquestion\editquestion_helper::get_question_status_list());
        }

        $mform->addElement('editor', 'generalfeedback', get_string('generalfeedback', 'question'),
                        array('rows' => 10), $this->editoroptions);
        $mform->setType('generalfeedback', PARAM_RAW);
        $mform->addHelpButton('generalfeedback', 'generalfeedback', 'qtype_lti');

        $mform->addElement('text', 'idnumber', get_string('idnumber', 'question'), 'maxlength="100"  size="10"');
        $mform->addHelpButton('idnumber', 'idnumber', 'question');
        $mform->setType('idnumber', PARAM_RAW);


        // Any questiontype specific fields.

        if ($type = optional_param('type', false, PARAM_ALPHA)) {
            component_callback("ltisource_$type", 'add_instance_hook');
        }

        $this->typeid = 0;

        // Tool settings.
        $tooltypes = $mform->addElement('select', 'typeid', get_string('external_tool_type', 'qtype_lti'));
        // Type ID parameter being passed when adding an preconfigured tool from activity chooser.
        $typeid = optional_param('typeid', false, PARAM_INT);
        if ($typeid) {
            $mform->getElement('typeid')->setValue($typeid);
        }
        $mform->addHelpButton('typeid', 'external_tool_type', 'qtype_lti');
        $toolproxy = array();

        // Array of tool type IDs that don't support ContentItemSelectionRequest.
        $noncontentitemtypes = [];

        foreach (qtype_lti_get_types_for_add_instance() as $id => $type) {
            if (!empty($type->toolproxyid)) {
                $toolproxy[] = $type->id;
                $attributes = array('globalTool' => 1, 'toolproxy' => 1);
                $enabledcapabilities = explode("\n", $type->enabledcapability);
                if (!in_array('Result.autocreate', $enabledcapabilities)) {
                    $attributes['nogrades'] = 1;
                }
                if (!in_array('Person.name.full', $enabledcapabilities) && !in_array('Person.name.family', $enabledcapabilities) &&
                     !in_array('Person.name.given', $enabledcapabilities)) {
                    $attributes['noname'] = 1;
                }
                if (!in_array('Person.email.primary', $enabledcapabilities)) {
                    $attributes['noemail'] = 1;
                }
            } else if ($type->course == $COURSE->id) {

                $attributes = array('editable' => 1, 'courseTool' => 1, 'domain' => $type->tooldomain);
            } else if ($id != 0) {
                $attributes = array('globalTool' => 1, 'domain' => $type->tooldomain);
            } else {
                $attributes = array();
            }

            if ($id) {
                $config = qtype_lti_get_type_config($id);
                if (!empty($config['contentitem'])) {
                    $attributes['data-contentitem'] = 1;
                    $attributes['data-id'] = $id;
                } else {
                    $noncontentitemtypes[] = $id;
                }
            }
            $tooltypes->addOption($type->name, $id, $attributes);
        }

        // Add button that launches the content-item selection dialogue.
        // Set contentitem URL.
        $contentitemurl = new moodle_url('/question/type/lti/contentitem.php');
        $contentbuttonattributes = ['data-contentitemurl' => $contentitemurl->out(false)];
        $contentbuttonlabel = get_string('selectcontent', 'qtype_lti');
        $contentbutton = $mform->addElement('button', 'selectcontent', $contentbuttonlabel, $contentbuttonattributes);
        // Disable select content button if the selected tool doesn't support content item or it's set to Automatic.
        $allnoncontentitemtypes = $noncontentitemtypes;
        $allnoncontentitemtypes[] = '0'; // Add option value for "Automatic, based on tool URL".

        $mform->disabledIf('selectcontent', 'typeid', 'in', $allnoncontentitemtypes);

        $mform->addElement('text', 'toolurl', get_string('launch_url', 'qtype_lti'), array('size' => '64'));
        $mform->setType('toolurl', PARAM_URL);
        $mform->addHelpButton('toolurl', 'launch_url', 'qtype_lti');

        $mform->addElement('checkbox', 'unlockabletoolurl', get_string('unlockabletoolurl_edit', 'qtype_lti'));
        $mform->addHelpButton('unlockabletoolurl', 'unlockabletoolurl_edit', 'qtype_lti');

        if (isset(get_config('qtype_lti')->unlockabletoolurl_adv)) {
            if (get_config('qtype_lti')->unlockabletoolurl_adv == 1) {
                $mform->setAdvanced('unlockabletoolurl');
            }
        }

        if (isset($this->question->id)) {
            $mform->disabledIf('toolurl', null);
        }
        if (isset(get_config('qtype_lti')->unlockabletoolurl)) {
            if (get_config('qtype_lti')->unlockabletoolurl == 0) {
                $mform->disabledIf('unlockabletoolurl', null);
            }
        }

        $mform->addElement('text', 'securetoolurl', get_string('secure_launch_url', 'qtype_lti'), array('size' => '64'));
        $mform->setType('securetoolurl', PARAM_URL);
        $mform->setAdvanced('securetoolurl');
        $mform->addHelpButton('securetoolurl', 'secure_launch_url', 'qtype_lti');
        $mform->disabledIf('securetoolurl', 'typeid', 'in', $noncontentitemtypes);

        $mform->addElement('hidden', 'urlmatchedtypeid', '', array('id' => 'id_urlmatchedtypeid'));
        $mform->setType('urlmatchedtypeid', PARAM_INT);

        $launchoptions = array();
        $launchoptions[QTYPE_LTI_LAUNCH_CONTAINER_DEFAULT] = get_string('default', 'qtype_lti');
        $launchoptions[QTYPE_LTI_LAUNCH_CONTAINER_EMBED] = get_string('embed', 'qtype_lti');
        $launchoptions[QTYPE_LTI_LAUNCH_CONTAINER_EMBED_NO_BLOCKS] = get_string('embed_no_blocks', 'qtype_lti');
        $launchoptions[QTYPE_LTI_LAUNCH_CONTAINER_REPLACE_MOODLE_WINDOW] = get_string('existing_window', 'qtype_lti');
        $launchoptions[QTYPE_LTI_LAUNCH_CONTAINER_WINDOW] = get_string('new_window', 'qtype_lti');

        $mform->addElement('select', 'launchcontainer', get_string('launchinpopup', 'qtype_lti'), $launchoptions,
                        'class="qtype_lti_stem_hidden"');
        $mform->setDefault('launchcontainer', QTYPE_LTI_LAUNCH_CONTAINER_DEFAULT);
        $mform->addHelpButton('launchcontainer', 'launchinpopup', 'qtype_lti');
        $mform->setAdvanced('launchcontainer');

        $mform->addElement('text', 'resourcekey', get_string('resourcekey', 'qtype_lti'));
        $mform->setType('resourcekey', PARAM_TEXT);
        $mform->setAdvanced('resourcekey');
        $mform->addHelpButton('resourcekey', 'resourcekey', 'qtype_lti');
        $mform->setForceLtr('resourcekey');
        $mform->disabledIf('resourcekey', 'typeid', 'in', $noncontentitemtypes);

        // Hacky solution for "advanced" to work without hurting boost theme.
        global $PAGE;
        $themeisboost = 0;

        if (strpos($PAGE->theme->name, 'boost') !== false) {
            $themeisboost = 1;
        }

        if ($themeisboost == 0) {
            $mform->addElement('html', '<div class="fitem advanced">');
        }

        // Disable Auto-fill in a nasty way.
        $mform->addElement('password', 'password', get_string('password', 'qtype_lti'), array('readonly'=>'true', 'onfocus'=>'this.removeAttribute("readonly");'));
        $mform->setType('password', PARAM_TEXT);
        $mform->setAdvanced('password', true);
        $mform->addHelpButton('password', 'password', 'qtype_lti');
        $mform->disabledIf('password', 'typeid', 'in', $noncontentitemtypes);
        if ($themeisboost == 0) {
            $mform->addElement('html', '</div>');
        }

        $mform->addElement('textarea', 'instructorcustomparameters', get_string('custom', 'qtype_lti'),
                        array('rows' => 4, 'cols' => 60));
        $mform->setType('instructorcustomparameters', PARAM_TEXT);
        $mform->setAdvanced('instructorcustomparameters');
        $mform->addHelpButton('instructorcustomparameters', 'custom', 'qtype_lti');
        $mform->setForceLtr('instructorcustomparameters');

        $mform->addElement('text', 'icon', get_string('icon_url', 'qtype_lti'), array('size' => '64'));
        $mform->setType('icon', PARAM_URL);
        $mform->setAdvanced('icon');
        $mform->addHelpButton('icon', 'icon_url', 'qtype_lti');
        $mform->disabledIf('icon', 'typeid', 'in', $noncontentitemtypes);

        $mform->addElement('text', 'secureicon', get_string('secure_icon_url', 'qtype_lti'), array('size' => '64'));
        $mform->setType('secureicon', PARAM_URL);
        $mform->setAdvanced('secureicon');
        $mform->addHelpButton('secureicon', 'secure_icon_url', 'qtype_lti');
        $mform->disabledIf('secureicon', 'typeid', 'in', $noncontentitemtypes);

        $ctxcourse = context_course::instance($COURSE->id);

        $editurl = new moodle_url('/question/type/lti/instructor_edit_tool_type.php',
                                array('sesskey' => sesskey(), 'course' => $COURSE->id));
        $ajaxurl = new moodle_url('/question/type/lti/ajax.php');

        // All these icon uses are incorrect. LTI JS needs updating to use AMD modules and templates so it can use
        // the mustache pix helper - until then LTI will have inconsistent icons.
        $jsinfo = (object)array('edit_icon_url' => (string)$OUTPUT->image_url('t/edit'),
            'add_icon_url' => (string)$OUTPUT->image_url('t/add'), 'delete_icon_url' => (string)$OUTPUT->image_url('t/delete'),
            'green_check_icon_url' => (string)$OUTPUT->image_url('i/valid'),
            'warning_icon_url' => (string)$OUTPUT->image_url('warning', 'qtype_lti'),
            'instructor_tool_type_edit_url' => $editurl->out(false), 'ajax_url' => $ajaxurl->out(true), 'courseId' => $COURSE->id,
            'questionid' => isset($this->question->id) ? $this->question->id : null,
            'can_add_course_tool' => has_capability('qtype/lti:addcoursetool', $ctxcourse));

        $module = array('name' => 'qtype_lti_edit', 'fullpath' => '/question/type/lti/form.js',
            'requires' => array('base', 'io', 'querystring-stringify-simple', 'node', 'event', 'json-parse'),
            'strings' => array(array('addtype', 'qtype_lti'), array('edittype', 'qtype_lti'), array('deletetype', 'qtype_lti'),
                array('delete_confirmation', 'qtype_lti'), array('cannot_add', 'qtype_lti'), array('cannot_edit', 'qtype_lti'),
                array('cannot_delete', 'qtype_lti'), array('global_tool_types', 'qtype_lti'),
                array('course_tool_types', 'qtype_lti'), array('using_tool_configuration', 'qtype_lti'),
                array('using_tool_cartridge', 'qtype_lti'), array('domain_mismatch', 'qtype_lti'),
                array('custom_config', 'qtype_lti'), array('tool_config_not_found', 'qtype_lti'),
                array('tool_config_duplicate_url', 'qtype_lti'), array('tool_config_not_valid', 'qtype_lti'),
                array('tooltypeadded', 'qtype_lti'),
                array('tooltypedeleted', 'qtype_lti'), array('tooltypenotdeleted', 'qtype_lti'),
                array('tooltypeupdated', 'qtype_lti'), array('forced_help', 'qtype_lti')));

        if (!empty($typeid)) {
            $mform->setAdvanced('typeid');
            $mform->setAdvanced('toolurl');
        }

        $PAGE->requires->js_init_call('M.qtype_lti.editor.init', array(json_encode($jsinfo)), true, $module);

        // Simplify screen by showing configuration details only if not preset (when new) or have the capabilities to do so (when
        // editing).

        if (optional_param('update', 0, PARAM_INT)) {
            $listtypes = has_capability('qtype/lti:addgloballypreconfigedtoolinstance', $ctxcourse);
            $listoptions = has_capability('qtype/lti:adddefaultinstance', $ctxcourse);
        } else {
            $listtypes = !$typeid;
            $listoptions = !$typeid && has_capability('qtype/lti:adddefaultinstance', $ctxcourse);
        }
        if (!$listtypes) {
            $mform->removeElement('typeid');
            $mform->addElement('hidden', 'typeid', $typeid);
            $mform->setType('typeid', PARAM_INT);
        }
        if (!$listoptions) {
            $mform->removeElement('selectcontent');
            $mform->removeElement('toolurl');
            $mform->removeElement('securetoolurl');
            $mform->removeElement('launchcontainer');
            $mform->removeElement('resourcekey');
            $mform->removeElement('password');
            $mform->removeElement('instructorcustomparameters');
            $mform->removeElement('icon');
            $mform->removeElement('secureicon');
        }

        $courseid = optional_param('courseid', 1, PARAM_INT);
        $mform->addElement('hidden', 'course', $courseid);
        $mform->setType('course', PARAM_INT);

        $mform->addElement('hidden', 'cmid', 0);
        $mform->setType('cmid', PARAM_INT);
        $mform->setDefault('cmid', 0);

        // Privacy.
        $mform->addElement('hidden', 'instructorchoicesendname', 1);
        $mform->setType('instructorchoicesendname', PARAM_INT);

        $mform->addElement('hidden', 'instructorchoicesendemailaddr', 1);
        $mform->setType('instructorchoicesendemailaddr', PARAM_INT);

        $mform->addElement('hidden', 'instructorchoiceacceptgrades', 1);
        $mform->setType('instructorchoiceacceptgrades', PARAM_INT);

        $mform->addElement('hidden', 'instructorchoiceallowroster', 1);
        $mform->setType('instructorchoiceallowroster', PARAM_INT);

        $mform->addElement('hidden', 'instructorchoiceallowroster', 1);
        $mform->setType('instructorchoiceallowroster', PARAM_INT);

        $mform->addElement('hidden', 'instructorchoiceallowsetting', 1);
        $mform->setType('instructorchoiceallowsetting', PARAM_INT);

        $mform->addElement('hidden', 'showdescription', 0, array('id' => 'id_showdescription'));
        $mform->setType('showdescription', PARAM_INT);
        $mform->addElement('hidden', 'showtitlelaunch', 1, array('id' => 'id_showtitlelaunch'));
        $mform->setType('showtitlelaunch', PARAM_INT);
        $mform->addElement('hidden', 'showdescriptionlaunch', 1, array('id' => 'id_showdescriptionlaunch'));
        $mform->setType('showdescriptionlaunch', PARAM_INT);

        $this->add_hidden_fields();
        if (core_tag_tag::is_enabled('core_question', 'question') && class_exists('qbank_tagquestion\\tags_action_column') &&
                        \core\plugininfo\qbank::is_plugin_enabled('qbank_tagquestion')) {
                            $this->add_tag_fields($mform);
                        }

                        if (!empty($this->customfieldpluginenabled) && $this->customfieldpluginenabled) {
                            // Add custom fields to the form.
                            $this->customfieldhandler = qbank_customfields\customfield\question_handler::create();
                            $this->customfieldhandler->set_parent_context($this->categorycontext); // For question handler only.
                            $this->customfieldhandler->instance_form_definition($mform, empty($this->question->id) ? 0 : $this->question->id);
                        }


        $this->add_interactive_settings(true, true);

        $mform->addElement('hidden', 'qtype');
        $mform->setType('qtype', PARAM_ALPHA);

        $mform->addElement('hidden', 'makecopy');
        $mform->setType('makecopy', PARAM_INT);

        $buttonarray = array();
        $buttonarray[] = $mform->createElement('submit', 'updatebutton', get_string('savechangesandcontinueediting', 'question'));
        if ($this->can_preview()) {
            if (class_exists('qbank_editquestion\\editquestion_helper')) {
                if (\core\plugininfo\qbank::is_plugin_enabled('qbank_previewquestion')) {
                    $previewlink = $PAGE->get_renderer('qbank_previewquestion')->question_preview_link($this->question->id,
                                    $this->context, true);
                    $buttonarray[] = $mform->createElement('static', 'previewlink', '', $previewlink);
                }
            } else {
                $previewlink = $PAGE->get_renderer('core_question')->question_preview_link($this->question->id, $this->context, true);
                $buttonarray[] = $mform->createElement('static', 'previewlink', '', $previewlink);
            }
        }

        $mform->addGroup($buttonarray, 'updatebuttonar', '', array(' '), false);
        $mform->closeHeaderBefore('updatebuttonar');

        $this->add_action_buttons(true, get_string('savechanges'));

        if ((!empty($this->question->id)) && (!($this->question->formoptions->canedit || $this->question->formoptions->cansaveasnew))) {
            $mform->hardFreezeAllVisibleExcept(array('categorymoveto', 'buttonar', 'currentgrp'));
        }
    }

    /**
     * (non-PHPdoc).
     *
     * @see question_edit_form::data_preprocessing()
     */
    protected function data_preprocessing($question) {
        $question = parent::data_preprocessing($question);

        if (isset($question->options)) {
            $question->instancecode = $question->options->instancecode;
            $question->typeid = $question->options->typeid;
            $question->toolurl = $question->options->toolurl;
            $question->securetoolurl = $question->options->securetoolurl;
            $question->instructorchoicesendname = $question->options->instructorchoicesendname;
            $question->instructorchoicesendemailaddr = $question->options->instructorchoicesendemailaddr;
            $question->instructorchoiceallowroster = $question->options->instructorchoiceallowroster;
            $question->instructorchoiceallowsetting = $question->options->instructorchoiceallowsetting;
            $question->instructorcustomparameters = $question->options->instructorcustomparameters;
            $question->instructorchoiceacceptgrades = $question->options->instructorchoiceacceptgrades;
            $question->grade = $question->options->grade;
            $question->launchcontainer = $question->options->launchcontainer;
            $question->resourcekey = $question->options->resourcekey;
            $question->password = $question->options->password;
            $question->debuglaunch = $question->options->debuglaunch;
            $question->showtitlelaunch = $question->options->showtitlelaunch;
            $question->showdescriptionlaunch = $question->options->showdescriptionlaunch;
            $question->servicesalt = $question->options->servicesalt;
            $question->icon = $question->options->icon;
            $question->secureicon = $question->options->secureicon;
        }

        return $question;
    }

    /**
     * (non-PHPdoc).
     *
     * @see question_edit_form::validation()
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        return $errors;
    }
}
