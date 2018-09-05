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
 * This file contains the script used to register a new external tool.
 *
 * It is used to create a new form used to configure the capabilities
 * and services to be offered to the tool provider.
 *
 * @package qtype_lti
 * @copyright  2014 Vital Source Technologies http://vitalsource.com
 * @author     Stephen Vickers
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/question/type/lti/register_form.php');
require_once($CFG->dirroot.'/question/type/lti/locallib.php');

$action       = optional_param('action', null, PARAM_ALPHANUMEXT);
$id           = optional_param('id', null, PARAM_INT);
$tab          = optional_param('tab', '', PARAM_ALPHAEXT);
$returnto     = optional_param('returnto', '', PARAM_ALPHA);

if ($returnto == 'toolconfigure') {
    $returnurl = new moodle_url($CFG->wwwroot . '/question/type/lti/toolconfigure.php');
}

// No guest autologin.
require_login(0, false);

$isupdate = !empty($id);
$pageurl = new moodle_url('/question/type/lti/registersettings.php');
if ($isupdate) {
    $pageurl->param('id', $id);
}
if (!empty($returnto)) {
    $pageurl->param('returnto', $returnto);
}
$PAGE->set_url($pageurl);

admin_externalpage_setup('ltitoolproxies');

$redirect = new moodle_url('/question/type/lti/toolproxies.php', array('tab' => $tab));
$redirect = $redirect->out();
if (!empty($returnurl)) {
    $redirect = $returnurl;
}

require_sesskey();

if ($action == 'delete') {
    qtype_lti_delete_tool_proxy($id);
    redirect($redirect);
}

$data = array();
if ($isupdate) {
    $data['isupdate'] = true;
}

$form = new qtype_lti_register_types_form($pageurl, (object)$data);

if ($form->is_cancelled()) {
    redirect($redirect);
} else if ($data = $form->get_data()) {
    $id = qtype_lti_add_tool_proxy($data);
    redirect($redirect);
} else {
    $PAGE->set_title("{$SITE->shortname}: " . get_string('toolregistration', 'qtype_lti'));
    $PAGE->navbar->add(get_string('lti_administration', 'qtype_lti'), $redirect);

    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('toolregistration', 'qtype_lti'));
    echo $OUTPUT->box_start('generalbox');
    if ($action == 'update') {
        $toolproxy = qtype_lti_get_tool_proxy_config($id);
        $form->set_data($toolproxy);
        if ($toolproxy->state == QTYPE_LTI_TOOL_PROXY_STATE_ACCEPTED) {
            $form->disable_fields();
        }
    }
    $form->display();

    echo $OUTPUT->box_end();
    echo $OUTPUT->footer();
}
