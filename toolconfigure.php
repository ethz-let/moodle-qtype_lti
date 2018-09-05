<?php
use qtype_lti\output;

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
 * This page allows the configuration of external tools that meet the LTI specification.
 *
 * @package    qtype_lti
 * @copyright  2015 Ryan Wyllie <ryan@moodle.com>
 * @author     Ryan Wyllie
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once('../../../config.php');
require_once($CFG->libdir.'/adminlib.php');

require_once($CFG->dirroot.'/question/type/lti/lib.php');
require_once($CFG->dirroot.'/question/type/lti/locallib.php');


$cartridgeurl = optional_param('cartridgeurl', '', PARAM_URL);

// No guest autologin.
require_login(0, false);
admin_externalpage_setup('manageqtypes');


if ($cartridgeurl) {
    $type = new stdClass();
    $data = new stdClass();
    $type->state = QTYPE_LTI_TOOL_STATE_CONFIGURED;
    $data->lti_coursevisible = 1;
    qtype_lti_load_type_from_cartridge($cartridgeurl, $data);
    qtype_lti_add_type($type, $data);
}

$pageurl = new moodle_url('/question/type/lti/toolconfigure.php');
$PAGE->set_url($pageurl);
$PAGE->set_title("{$SITE->shortname}: " . get_string('toolregistration', 'qtype_lti'));
$PAGE->requires->string_for_js('success', 'moodle');
$PAGE->requires->string_for_js('error', 'moodle');
$PAGE->requires->string_for_js('successfullycreatedtooltype', 'qtype_lti');
$PAGE->requires->string_for_js('failedtocreatetooltype', 'qtype_lti');

$output = $PAGE->get_renderer('qtype_lti');


echo $output->header();

$page = new \qtype_lti\output\tool_configure_page();

echo $output->render($page);

echo $output->footer();
