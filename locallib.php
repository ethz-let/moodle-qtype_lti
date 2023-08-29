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
 * This file contains the library of functions and constants for the lti module
 *
 * @package qtype_lti
 * @copyright 2019 ETH Zurich
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/question/type/lti/OAuth.php');

// TODO: Switch to core oauthlib once implemented - MDL-30149.
use moodle\qtype\lti as lti;

require_once($CFG->libdir . '/weblib.php');
require_once($CFG->dirroot . '/course/modlib.php');
require_once($CFG->dirroot . '/question/type/lti/TrivialStore.php');

define('QTYPE_LTI_URL_DOMAIN_REGEX', '/(?:https?:\/\/)?(?:www\.)?([^\/]+)(?:\/|$)/i');

define('QTYPE_LTI_LAUNCH_CONTAINER_DEFAULT', 1);
define('QTYPE_LTI_LAUNCH_CONTAINER_EMBED', 2);
define('QTYPE_LTI_LAUNCH_CONTAINER_EMBED_NO_BLOCKS', 3);
define('QTYPE_LTI_LAUNCH_CONTAINER_WINDOW', 4);
define('QTYPE_LTI_LAUNCH_CONTAINER_REPLACE_MOODLE_WINDOW', 5);

define('QTYPE_LTI_TOOL_STATE_ANY', 0);
define('QTYPE_LTI_TOOL_STATE_CONFIGURED', 1);
define('QTYPE_LTI_TOOL_STATE_PENDING', 2);
define('QTYPE_LTI_TOOL_STATE_REJECTED', 3);
define('QTYPE_LTI_TOOL_PROXY_TAB', 4);

define('QTYPE_LTI_TOOL_PROXY_STATE_CONFIGURED', 1);
define('QTYPE_LTI_TOOL_PROXY_STATE_PENDING', 2);
define('QTYPE_LTI_TOOL_PROXY_STATE_ACCEPTED', 3);
define('QTYPE_LTI_TOOL_PROXY_STATE_REJECTED', 4);

define('QTYPE_LTI_SETTING_NEVER', 0);
define('QTYPE_LTI_SETTING_ALWAYS', 1);
define('QTYPE_LTI_SETTING_DELEGATE', 2);

define('QTYPE_LTI_COURSEVISIBLE_NO', 0);
define('QTYPE_LTI_COURSEVISIBLE_PRECONFIGURED', 1);
define('QTYPE_LTI_COURSEVISIBLE_ACTIVITYCHOOSER', 2);

define('QTYPE_LTI_VERSION_1', 'LTI-1p0');
define('QTYPE_LTI_VERSION_2', 'LTI-2p0');

/**
 * Return the launch data required for opening the external tool.
 *
 * @param stdClass $instance
 *        the external tool activity settings
 * @return array the endpoint URL and parameters (including the signature)
 * @since Moodle 3.0
 */
function qtype_lti_get_launch_data($instance, $userid = null, $readonly = null,
                                   $questionmode = 'create', $manuallygradedinmoodle = 0,
                                   $extracodeexpertparams = array()) {
    global $PAGE, $CFG, $DB;

    if (empty($instance->typeid)) {
        $tool = qtype_lti_get_tool_by_url_match($instance->toolurl, $instance->course);
        if ($tool) {
            $typeid = $tool->id;
        } else {
            $typeid = null;
        }
    } else {
        $typeid = $instance->typeid;
        $tool = qtype_lti_get_type($typeid);
    }

    if ($typeid) {
        $typeconfig = qtype_lti_get_type_config($typeid);
        $noadminconfigfound = 0;
    } else {
        // There is no admin configuration for this tool. Use configuration in the lti instance record plus some defaults.
        $typeconfig = (array)$instance;

        $typeconfig['sendname'] = $instance->instructorchoicesendname;
        $typeconfig['sendemailaddr'] = $instance->instructorchoicesendemailaddr;
        $typeconfig['customparameters'] = $instance->instructorcustomparameters;
        $typeconfig['acceptgrades'] = $instance->instructorchoiceacceptgrades;
        $typeconfig['allowroster'] = $instance->instructorchoiceallowroster;
        $typeconfig['forcessl'] = '0';
        $noadminconfigfound = 1;
    }

    // Default the organizationid if not specified.
    if (empty($typeconfig['organizationid'])) {
        $urlparts = parse_url($CFG->wwwroot);

        $typeconfig['organizationid'] = $urlparts['host'];
    }

    if (isset($tool->toolproxyid)) {
        $toolproxy = qtype_lti_get_tool_proxy($tool->toolproxyid);
        $key = $toolproxy->guid;
        $secret = $toolproxy->secret;
    } else {
        $toolproxy = null;
        if (!empty($instance->resourcekey)) {
            $key = $instance->resourcekey;
        } else if (!empty($typeconfig['resourcekey'])) {
            $key = $typeconfig['resourcekey'];
        } else {
            $key = '';
        }
        if (!empty($instance->password)) {
            $secret = $instance->password;
        } else if (!empty($typeconfig['password'])) {
            $secret = $typeconfig['password'];
        } else {
            $secret = '';
        }
    }

    $endpoint = !empty($instance->toolurl) ? $instance->toolurl : $typeconfig['toolurl'];
    $endpoint = trim($endpoint);

    // If the current request is using SSL and a secure tool URL is specified, use it.
    if (qtype_lti_request_is_using_ssl() && !empty($instance->securetoolurl)) {
        $endpoint = trim($instance->securetoolurl);
    }

    // If SSL is forced, use the secure tool url if specified. Otherwise, make sure https is on the normal launch URL.
    if (isset($typeconfig['forcessl']) && ($typeconfig['forcessl'] == '1')) {
        if (!empty($instance->securetoolurl)) {
            $endpoint = trim($instance->securetoolurl);
        }

        $endpoint = qtype_lti_ensure_url_is_https($endpoint);
    } else {
        if (!strstr($endpoint, '://')) {
            $endpoint = 'http://' . $endpoint;
        }
    }

    $orgid = $typeconfig['organizationid'];

    $course = $DB->get_record('course', array('id' => $instance->course));

    $islti2 = isset($tool->toolproxyid);
    $allparams = qtype_lti_build_request($instance, $typeconfig, $course, $islti2, $userid, $readonly, $questionmode,
                                        $manuallygradedinmoodle, $extracodeexpertparams, $typeid);
    if ($islti2) {
        $requestparams = qtype_lti_build_request_lti2($tool, $allparams, $userid, $readonly, $questionmode,
                                                    $manuallygradedinmoodle, $extracodeexpertparams);
    } else {
        $requestparams = $allparams;
    }
    $requestparams = array_merge($requestparams,
                                qtype_lti_build_standard_request($instance, $orgid, $islti2, 'basic-lti-launch-request',
                                                                $extracodeexpertparams));
    $customstr = '';
    if (isset($typeconfig['customparameters'])) {
        $customstr = $typeconfig['customparameters'];
    }
    $requestparams = array_merge($requestparams,
                                qtype_lti_build_custom_parameters($toolproxy, $tool, $instance, $allparams, $customstr,
                                                                $instance->instructorcustomparameters, $islti2, $userid));

    $launchcontainer = qtype_lti_get_launch_container($instance, $typeconfig);
    $returnurlparams = array('course' => $course->id, 'launch_container' => $launchcontainer, 'instanceid' => $instance->id,
        'sesskey' => sesskey());

    // Add the return URL. We send the launch container along to help us avoid frames-within-frames when the user returns.
    $url = new \moodle_url('/question/type/lti/return.php', $returnurlparams);
    $returnurl = $url->out(false);

    if (isset($typeconfig['forcessl']) && ($typeconfig['forcessl'] == '1')) {
        $returnurl = qtype_lti_ensure_url_is_https($returnurl);
    }

    $target = '';
    switch ($launchcontainer) {
        case QTYPE_LTI_LAUNCH_CONTAINER_EMBED:
        case QTYPE_LTI_LAUNCH_CONTAINER_EMBED_NO_BLOCKS:
            $target = 'iframe';
        break;
        case QTYPE_LTI_LAUNCH_CONTAINER_REPLACE_MOODLE_WINDOW:
            $target = 'frame';
        break;
        case QTYPE_LTI_LAUNCH_CONTAINER_WINDOW:
            $target = 'window';
        break;
    }
    if (!empty($target)) {
        $requestparams['launch_presentation_document_target'] = $target;
    }

    $requestparams['launch_presentation_return_url'] = $returnurl;

    // Allow request params to be updated by sub-plugins.
    $plugins = core_component::get_plugin_list('ltisource');
    foreach (array_keys($plugins) as $plugin) {
        $pluginparams = component_callback('ltisource_' . $plugin, 'before_launch', array($instance, $endpoint, $requestparams),
                                        array());

        if (!empty($pluginparams) && is_array($pluginparams)) {
            $requestparams = array_merge($requestparams, $pluginparams);
        }
    }

    if (!empty($key) && !empty($secret)) {
        $parms = qtype_lti_sign_parameters($requestparams, $endpoint, "POST", $key, $secret);

        $endpointurl = new \moodle_url($endpoint);
        $endpointparams = $endpointurl->params();

        // Strip querystring params in endpoint url from $parms to avoid duplication.
        if (!empty($endpointparams) && !empty($parms)) {
            foreach (array_keys($endpointparams) as $paramname) {
                if (isset($parms[$paramname])) {
                    unset($parms[$paramname]);
                }
            }
        }
    } else {
        // If no key and secret, do the launch unsigned.
        $returnurlparams['unsigned'] = '1';
        $parms = $requestparams;
    }

    return array($endpoint, $parms);
}

/**
 * Launch an external tool activity.
 *
 * @param stdClass $instance
 *        the external tool activity settings
 * @return string The HTML code containing the javascript code for the launch
 */
function qtype_lti_launch_tool($instance, $userid = null, $readonly = null, $questionmode = 'create', $manuallygradedinmoodle = 0,
                            $extracodeexpertparams = array()) {
    list($endpoint, $parms) = qtype_lti_get_launch_data($instance, $userid, $readonly, $questionmode, $manuallygradedinmoodle,
                                                        $extracodeexpertparams);
    $debuglaunch = ($instance->debuglaunch == 1);
    $content = qtype_lti_post_launch_html($parms, $endpoint, $debuglaunch);

    echo $content;
}

/**
 * Prepares an LTI registration request message
 * $param object $instance Tool Proxy instance object
 */
function qtype_lti_register($toolproxy) {
    $endpoint = $toolproxy->regurl;

    // Change the status to pending.
    $toolproxy->state = QTYPE_LTI_TOOL_PROXY_STATE_PENDING;
    qtype_lti_update_tool_proxy($toolproxy);

    $requestparams = qtype_lti_build_registration_request($toolproxy);

    $content = qtype_lti_post_launch_html($requestparams, $endpoint, false);

    echo $content;
}

/**
 * Gets the parameters for the regirstration request
 *
 * @param object $toolproxy
 *        Tool Proxy instance object
 * @return array Registration request parameters
 */
function qtype_lti_build_registration_request($toolproxy) {
    $key = $toolproxy->guid;
    $secret = $toolproxy->secret;

    $requestparams = array();
    $requestparams['lti_message_type'] = 'ToolProxyRegistrationRequest';
    $requestparams['lti_version'] = 'LTI-2p0';
    $requestparams['reg_key'] = $key;
    $requestparams['reg_password'] = $secret;
    $requestparams['reg_url'] = $toolproxy->regurl;

    // Add the profile URL.
    $profileservice = qtype_lti_get_service_by_name('profile');
    $profileservice->set_tool_proxy($toolproxy);
    $requestparams['tc_profile_url'] = $profileservice->parse_value('$ToolConsumerProfile.url');

    // Add the return URL.
    $returnurlparams = array('id' => $toolproxy->id, 'sesskey' => sesskey());
    $url = new \moodle_url('/question/type/lti/externalregistrationreturn.php', $returnurlparams);
    $returnurl = $url->out(false);

    $requestparams['launch_presentation_return_url'] = $returnurl;

    return $requestparams;
}

/**
 * Build source ID
 *
 * @param int $instanceid
 * @param int $userid
 * @param string $servicesalt
 * @param null|int $typeid
 * @param null|int $launchid
 * @return stdClass
 */
function qtype_lti_build_sourcedid($mattempt, $instanceid, $userid, $servicesalt, $typeid = null, $attemptid = null, $ltiid = null) {
    $data = new \stdClass();

    $data->instance = $instanceid;
    $data->username = $userid;
    if (!empty($attemptid)) {
        $data->attemptid = $attemptid;
    } else {
        $data->attemptid = uniqid();
    }
    $data->ltiid = $ltiid;
    $data->mattempt = $mattempt;
    $json = json_encode($data);
    $hash = hash('sha256', $json . $servicesalt, false);

    $container = new \stdClass();
    $container->data = $data;
    $container->hash = $hash;

    return $container;
}

/**
 * This function builds the request that must be sent to the tool producer
 *
 * @param object $instance
 *        Basic LTI instance object
 * @param array $typeconfig
 *        Basic LTI tool configuration
 * @param object $course
 *        Course object
 * @param int|null $typeid
 *        Basic LTI tool ID
 * @param boolean $islti2
 *        True if an LTI 2 tool is being launched
 * @return array Request details
 */
function qtype_lti_build_request($instance, $typeconfig, $course,
                                $islti2 = false, $userid, $readonly, $questionmode,
                                $manuallygradedinmoodle, $extracodeexpertparams,
				$typeid = null) {
    global $USER, $CFG, $DB;
    if (empty($instance->cmid)) {
        $instance->cmid = 0;
    }
    $originaluser = $USER;

    $role = qtype_lti_get_ims_role($originaluser, $instance->cmid, $instance->course, $islti2);

    $requestparams = array('user_id' => $originaluser->id, 'lis_person_sourcedid' => $originaluser->idnumber, 'roles' => $role,
        'context_id' => $course->id, 'context_label' => trim(html_to_text($course->shortname, 0)),
        'context_title' => trim(html_to_text($course->fullname, 0)));

    if ($readonly) {
        $requestparams['ext_question_readonly_mode'] = 1;
    }

    if (!empty($instance->name)) {
        $requestparams['resource_link_title'] = trim(html_to_text($instance->name, 0));
    }
    if (!empty($instance->cmid)) {
        $intro = format_module_intro('qtype_lti', $instance, $instance->cmid);
        $intro = trim(html_to_text($intro, 0, false));

        // This may look weird, but this is required for new lines
        // so we generate the same OAuth signature as the tool provider.
        $intro = str_replace("\n", "\r\n", $intro);
        $requestparams['resource_link_description'] = $intro;
    }
    if (!empty($instance->id)) {
        $requestparams['resource_link_id'] = $instance->instancecode;
    }
    if (!empty($instance->resource_link_id)) {
        $requestparams['resource_link_id'] = $instance->instancecode;
    }

    $requestparams['resource_link_id'] = $extracodeexpertparams['resourcelinkid'];

    if ($course->format == 'site') {
        $requestparams['context_type'] = 'Group';
    } else {
        $requestparams['context_type'] = 'CourseSection';
        if (!$course->idnumber || empty($course->idnumber)) {
            $course->idnumber = $course->shortname;
        }
        $requestparams['lis_course_section_sourcedid'] = $course->idnumber;
    }

    if (!empty($instance->id) && !empty($instance->servicesalt) &&
         ($islti2 || $typeconfig['acceptgrades'] == QTYPE_LTI_SETTING_ALWAYS ||
         ($typeconfig['acceptgrades'] == QTYPE_LTI_SETTING_DELEGATE &&
         $instance->instructorchoiceacceptgrades == QTYPE_LTI_SETTING_ALWAYS))) {
        $placementsecret = $instance->servicesalt;
        $sourcedid = json_encode(
                                qtype_lti_build_sourcedid($extracodeexpertparams['resultid'], $originaluser->username,
                                                          $placementsecret, $typeid, $extracodeexpertparams['attemptid'],
                                                          $instance->id, $extracodeexpertparams['mattempt']));
        $requestparams['lis_result_sourcedid'] = $sourcedid;

        // Add outcome service URL.
        $serviceurl = new \moodle_url('/question/type/lti/service.php');
        $serviceurl = $serviceurl->out();

        $forcessl = false;
        if (!empty($CFG->qtype_lti_forcessl)) {
            $forcessl = true;
        }

        if ((isset($typeconfig['forcessl']) && ($typeconfig['forcessl'] == '1')) or $forcessl) {
            $serviceurl = qtype_lti_ensure_url_is_https($serviceurl);
        }

        $requestparams['lis_outcome_service_url'] = $serviceurl;
    }

    $requestparams['lis_person_name_given'] = $originaluser->firstname;
    $requestparams['lis_person_name_family'] = $originaluser->lastname;
    $requestparams['lis_person_name_full'] = $originaluser->firstname . ' ' . $originaluser->lastname;
    $requestparams['ext_user_username'] = $originaluser->username;
    // In case the teacher wants to see the users submission in review mode.
    $requestparams['ext_student_username'] = $userid;
    $requestparams['ext_moodleattemptid'] = $extracodeexpertparams['mattempt'];

    /*
     * The workflow used for the launch.
     * The 4 following workflows based on the processes described in Spezifikation Code Expert in Moodle are supported:
     * "create" Create & Edit Mode describes the process of a Lecturer creating or modifying a Code Expert question in Moodle.
     * "solve" Solve Task Mode stands for the the student solving a Moodle exam containing Code Expert questions.
     * The "student" may also be a lecturer impersonating a student to verify his own exam.
     * "correction" Correction Mode is used by an assistant or a lecturer while correcting an solved exam by one or multiple
     * students.
     * "review" for Reviewing the answer (after submission and usually attached to readonly mode).
     */

    // Due to inability to control the launch url in question edit mode, questionmodede is defaulted to 1.
    // Transform it to 'create' shall it be 1.

    if (!$questionmode || $questionmode == '1') {
        $questionmode = 'create';
    }

    $requestparams['ext_workflow_mode'] = $questionmode;
    $requestparams['ext_manually_graded'] = $manuallygradedinmoodle;

    $requestparams['ext_attempt_id'] = $extracodeexpertparams['attemptid'];
    $requestparams['ext_attempt_state'] = $extracodeexpertparams['attemptstate'];
    $requestparams['ext_attempt_onlast'] = $extracodeexpertparams['attemptonlast'];
    $requestparams['ext_quiz_id'] = $extracodeexpertparams['quizid'];
    $requestparams['ext_quiz_title'] = trim(html_to_text($extracodeexpertparams['quiztitle'], 0));
    $requestparams['ext_question_id'] = $extracodeexpertparams['questionid'];
    $requestparams['ext_lti_id'] = $extracodeexpertparams['ltiid'];
    $requestparams['ext_instancecode'] = $extracodeexpertparams['instancecode'];
    $requestparams['ext_course_id'] = $extracodeexpertparams['courseid'];
    $requestparams['ext_unique_connection'] = $extracodeexpertparams['resultid'];

    $requestparams["ext_student_matriculationnumber"] = '';
    if (isset($typeconfig['whichadditionalfield'])) {
        // Case 1: Get optional userfield
        if ($typeconfig['whichadditionalfield'] == 'optional') {
            if (isset($typeconfig['userfield_optional'])) {
                $param = clean_param($typeconfig['userfield_optional'], PARAM_ALPHAEXT);
                if (!empty($param) && !in_array($param, ['password'])) {
        
                    $dbman = $DB->get_manager();
                    $table = new xmldb_table('user');
                    $field = new xmldb_field($param);
                    if ($dbman->field_exists($table, $field)) {
                        if ($result = $DB->get_record_select('user', "username = :username", ['username' => $userid], $param)) {
                            if (!empty($result->{$param})) {
                                $requestparams["ext_student_matriculationnumber"] = $result->{$param};
                            }
                        }
                    }
                }
            }
        // Case 2: Or get other userfield
        } else if ($typeconfig['whichadditionalfield'] == 'other') {
            if (isset($typeconfig['userfield_other'])) {
                $param = clean_param($typeconfig['userfield_other'], PARAM_INT);
                if (!empty($param)) {
        
                    $sql = "SELECT uda.data
                            FROM {user_info_data} uda
                            JOIN {user} u ON u.id = uda.userid
                            WHERE u.username = :username AND uda.fieldid = :fieldid";
        
                    if ($result = $DB->get_record_sql($sql, ['username' => $userid, 'fieldid' => $param], $param)) {
                        if (!empty($result->data)) {
                            $requestparams["ext_student_matriculationnumber"]  = $result->data;
                        }
                    }
                }
            }
        }
    }

    if ($islti2 || $typeconfig['sendemailaddr'] == QTYPE_LTI_SETTING_ALWAYS || ($typeconfig['sendemailaddr'] ==
         QTYPE_LTI_SETTING_DELEGATE && isset($instance->instructorchoicesendemailaddr) &&
         $instance->instructorchoicesendemailaddr == QTYPE_LTI_SETTING_ALWAYS)) {
        $requestparams['lis_person_contact_email_primary'] = $originaluser->email;
    }
    return $requestparams;
}

/**
 * This function builds the request that must be sent to an LTI 2 tool provider
 *
 * @param object $tool
 *        Basic LTI tool object
 * @param array $params
 *        Custom launch parameters
 * @return array Request details
 */
function qtype_lti_build_request_lti2($tool, $params, $userid, $readonly, $questionmode, $manuallygradedinmoodle,
                                    $extracodeexpertparams) {
    $requestparams = array();

    $capabilities = qtype_lti_get_capabilities();
    $enabledcapabilities = explode("\n", $tool->enabledcapability);
    foreach ($enabledcapabilities as $capability) {
        if (array_key_exists($capability, $capabilities)) {
            $val = $capabilities[$capability];
            if ($val && (substr($val, 0, 1) != '$')) {
                if (isset($params[$val])) {
                    $requestparams[$capabilities[$capability]] = $params[$capabilities[$capability]];
                }
            }
        }
    }

    return $requestparams;
}

/**
 * This function builds the standard parameters for an LTI 1 or 2 request that must be sent to the tool producer
 *
 * @param stdClass $instance
 *        Basic LTI instance object
 * @param string $orgid
 *        Organisation ID
 * @param boolean $islti2
 *        True if an LTI 2 tool is being launched
 * @param string $messagetype
 *        The request message type. Defaults to basic-lti-launch-request if empty.
 * @return array Request details
 */
function qtype_lti_build_standard_request($instance, $orgid, $islti2,
                                          $messagetype = 'basic-lti-launch-request', $extracodeexpertparams) {
    global $CFG;

    $requestparams = array();
    if ($instance) {
        global $DB;
        $requestparams['resource_link_id'] = $extracodeexpertparams['resourcelinkid'];

        if (property_exists($instance, 'resource_link_id') and !empty($instance->resource_link_id)) {
            $requestparams['resource_link_id'] = $extracodeexpertparams['resourcelinkid'];
        }
    }

    $requestparams['launch_presentation_locale'] = current_language();

    // Make sure we let the tool know what LMS they are being called from.
    $requestparams['ext_lms'] = 'moodle-2';
    $requestparams['tool_consumer_info_product_family_code'] = 'moodle';
    $requestparams['tool_consumer_info_version'] = strval($CFG->version);

    // Add oauth_callback to be compliant with the 1.0A spec.
    $requestparams['oauth_callback'] = 'about:blank';

    if (!$islti2) {
        $requestparams['lti_version'] = 'LTI-1p0';
    } else {
        $requestparams['lti_version'] = 'LTI-2p0';
    }
    $requestparams['lti_message_type'] = $messagetype;

    if ($orgid) {
        $requestparams["tool_consumer_instance_guid"] = $orgid;
    }
    if (!empty($CFG->qtype_lti_institution_name)) {
        $requestparams['tool_consumer_instance_name'] = trim(html_to_text($CFG->qtype_lti_institution_name, 0));
    } else {
        $requestparams['tool_consumer_instance_name'] = get_site()->shortname;
    }
    $requestparams['tool_consumer_instance_description'] = trim(html_to_text(get_site()->fullname, 0));

    return $requestparams;
}

/**
 * This function builds the custom parameters
 *
 * @param object $toolproxy
 *        Tool proxy instance object
 * @param object $tool
 *        Tool instance object
 * @param object $instance
 *        Tool placement instance object
 * @param array $params
 *        LTI launch parameters
 * @param string $customstr
 *        Custom parameters defined for tool
 * @param string $instructorcustomstr
 *        Custom parameters defined for this placement
 * @param boolean $islti2
 *        True if an LTI 2 tool is being launched
 * @return array Custom parameters
 */
function qtype_lti_build_custom_parameters($toolproxy, $tool, $instance, $params,
                                           $customstr, $instructorcustomstr, $islti2, $userid) {

    // Concatenate the custom parameters from the administrator and the instructor
    // Instructor parameters are only taken into consideration if the administrator
    // has given permission.
    $custom = array();
    if ($customstr) {
        $custom = qtype_lti_split_custom_parameters($toolproxy, $tool, $params, $customstr, $islti2, $userid);
    }
    if (!isset($typeconfig['allowinstructorcustom']) || $typeconfig['allowinstructorcustom'] != QTYPE_LTI_SETTING_NEVER) {
        if ($instructorcustomstr) {
            $custom = array_merge(
                                qtype_lti_split_custom_parameters($toolproxy, $tool, $params, $instructorcustomstr, $islti2,
                                                                $userid), $custom);
        }
    }
    if ($islti2) {
        $custom = array_merge(
                            qtype_lti_split_custom_parameters($toolproxy, $tool, $params,
                                                              $tool->parameter, true, $userid), $custom);
        $settings = qtype_lti_get_tool_settings($tool->toolproxyid);
        $custom = array_merge($custom, qtype_lti_get_custom_parameters($toolproxy, $tool, $params, $settings));
        if (!empty($instance->course)) {
            $settings = qtype_lti_get_tool_settings($tool->toolproxyid, $instance->course);
            $custom = array_merge($custom, qtype_lti_get_custom_parameters($toolproxy, $tool, $params, $settings));
            if (!empty($instance->id)) {
                $settings = qtype_lti_get_tool_settings($tool->toolproxyid, $instance->course, $instance->id);
                $custom = array_merge($custom, qtype_lti_get_custom_parameters($toolproxy, $tool, $params, $settings));
            }
        }
    }

    return $custom;
}

/**
 * Builds a standard LTI Content-Item selection request.
 *
 * @param int $id
 *        The tool type ID.
 * @param stdClass $course
 *        The course object.
 * @param moodle_url $returnurl
 *        The return URL in the tool consumer (TC) that the tool provider (TP)
 *        will use to return the Content-Item message.
 * @param string $title
 *        The tool's title, if available.
 * @param string $text
 *        The text to display to represent the content item. This value may be a long description of the content item.
 * @param array $mediatypes
 *        Array of MIME types types supported by the TC. If empty, the TC will support ltilink by default.
 * @param array $presentationtargets
 *        Array of ways in which the selected content item(s) can be requested to be opened
 *        (via the presentationDocumentTarget element for a returned content item).
 *        If empty, "frame", "iframe", and "window" will be supported by default.
 * @param bool $autocreate
 *        Indicates whether any content items returned by the TP would be automatically persisted without
 * @param bool $multiple
 *        Indicates whether the user should be permitted to select more than one item. False by default.
 *        any option for the user to cancel the operation. False by default.
 * @param bool $unsigned
 *        Indicates whether the TC is willing to accept an unsigned return message, or not.
 *        A signed message should always be required when the content item is being created automatically in the
 *        TC without further interaction from the user. False by default.
 * @param bool $canconfirm
 *        Flag for can_confirm parameter. False by default.
 * @param bool $copyadvice
 *        Indicates whether the TC is able and willing to make a local copy of a content item. False by default.
 * @return stdClass The object containing the signed request parameters and the URL to the TP's Content-Item selection interface.
 * @throws moodle_exception When the LTI tool type does not exist.`
 * @throws coding_exception For invalid media type and presentation target parameters.
 */
function qtype_lti_build_content_item_selection_request($id, $course, moodle_url $returnurl, $title = '',
                                                        $text = '', $mediatypes = [], $presentationtargets = [],
                                                        $autocreate = false, $multiple = false, $unsigned = false,
                                                        $canconfirm = false, $copyadvice = false, $userid = null, $readonly = null,
                                                        $questionmode = 'create', $manuallygradedinmoodle = 0,
                                                        $extracodeexpertparams = array()) {
    $tool = qtype_lti_get_type($id);
    // Validate parameters.
    if (!$tool) {
        throw new moodle_exception('errortooltypenotfound', 'qtype_lti');
    }
    if (!is_array($mediatypes)) {
        throw new coding_exception('The list of accepted media types should be in an array');
    }
    if (!is_array($presentationtargets)) {
        throw new coding_exception('The list of accepted presentation targets should be in an array');
    }

    // Check title. If empty, use the tool's name.
    if (empty($title)) {
        $title = $tool->name;
    }

    $typeconfig = qtype_lti_get_type_config($id);
    $key = '';
    $secret = '';
    $islti2 = false;
    if (isset($tool->toolproxyid)) {
        $islti2 = true;
        $toolproxy = qtype_lti_get_tool_proxy($tool->toolproxyid);
        $key = $toolproxy->guid;
        $secret = $toolproxy->secret;
    } else {
        $toolproxy = null;
        if (!empty($typeconfig['resourcekey'])) {
            $key = $typeconfig['resourcekey'];
        }
        if (!empty($typeconfig['password'])) {
            $secret = $typeconfig['password'];
        }
    }
    $tool->enabledcapability = '';
    if (!empty($typeconfig['enabledcapability_ContentItemSelectionRequest'])) {
        $tool->enabledcapability = $typeconfig['enabledcapability_ContentItemSelectionRequest'];
    }

    $tool->parameter = '';
    if (!empty($typeconfig['parameter_ContentItemSelectionRequest'])) {
        $tool->parameter = $typeconfig['parameter_ContentItemSelectionRequest'];
    }

    // Set the tool URL.
    if (!empty($typeconfig['toolurl_ContentItemSelectionRequest'])) {
        $toolurl = new moodle_url($typeconfig['toolurl_ContentItemSelectionRequest']);
    } else {
        $toolurl = new moodle_url($typeconfig['toolurl']);
    }

    // Check if SSL is forced.
    if (!empty($typeconfig['forcessl'])) {
        // Make sure the tool URL is set to https.
        if (strtolower($toolurl->get_scheme()) === 'http') {
            $toolurl->set_scheme('https');
        }
        // Make sure the return URL is set to https.
        if (strtolower($returnurl->get_scheme()) === 'http') {
            $returnurl->set_scheme('https');
        }
    }
    $toolurlout = $toolurl->out(false);

    // Get base request parameters.
    $instance = new stdClass();
    $instance->course = $course->id;
    $requestparams = qtype_lti_build_request($instance, $typeconfig, $course, $islti2, $userid, $readonly, $questionmode,
                                            $manuallygradedinmoodle, $extracodeexpertparams, $id);

    // Get LTI2-specific request parameters and merge to the request parameters if applicable.
    if ($islti2) {
        $lti2params = qtype_lti_build_request_lti2($tool, $requestparams);
        $requestparams = array_merge($requestparams, $lti2params);
    }

    // Get standard request parameters and merge to the request parameters.
    $orgid = !empty($typeconfig['organizationid']) ? $typeconfig['organizationid'] : '';
    $standardparams = qtype_lti_build_standard_request(null, $orgid, $islti2, 'ContentItemSelectionRequest',
                                                    $extracodeexpertparams);

    $requestparams = array_merge($requestparams, $standardparams);

    // Get custom request parameters and merge to the request parameters.
    $customstr = '';
    if (!empty($typeconfig['customparameters'])) {
        $customstr = $typeconfig['customparameters'];
    }
    $customparams = qtype_lti_build_custom_parameters($toolproxy, $tool, $instance, $requestparams, $customstr, '', $islti2,
                                                    $userid);
    $requestparams = array_merge($requestparams, $customparams);

    // Allow request params to be updated by sub-plugins.
    $plugins = core_component::get_plugin_list('ltisource');
    foreach (array_keys($plugins) as $plugin) {
        $pluginparams = component_callback('ltisource_' . $plugin, 'before_launch', [$instance, $toolurlout, $requestparams],
                                        []);

        if (!empty($pluginparams) && is_array($pluginparams)) {
            $requestparams = array_merge($requestparams, $pluginparams);
        }
    }

    // Media types. Set to ltilink by default if empty.
    if (empty($mediatypes)) {
        $mediatypes = ['application/vnd.ims.lti.v1.ltilink'];
    }
    $requestparams['accept_media_types'] = implode(',', $mediatypes);

    // Presentation targets. Supports frame, iframe, window by default if empty.
    if (empty($presentationtargets)) {
        $presentationtargets = ['frame', 'iframe', 'window'];
    }
    $requestparams['accept_presentation_document_targets'] = implode(',', $presentationtargets);

    // Other request parameters.
    $requestparams['accept_copy_advice'] = $copyadvice === true ? 'true' : 'false';
    $requestparams['accept_multiple'] = $multiple === true ? 'true' : 'false';
    $requestparams['accept_unsigned'] = $unsigned === true ? 'true' : 'false';
    $requestparams['auto_create'] = $autocreate === true ? 'true' : 'false';
    $requestparams['can_confirm'] = $canconfirm === true ? 'true' : 'false';
    $requestparams['content_item_return_url'] = $returnurl->out(false);
    $requestparams['title'] = $title;
    $requestparams['text'] = $text;
    $signedparams = qtype_lti_sign_parameters($requestparams, $toolurlout, 'POST', $key, $secret);
    $toolurlparams = $toolurl->params();

    // Strip querystring params in endpoint url from $signedparams to avoid duplication.
    if (!empty($toolurlparams) && !empty($signedparams)) {
        foreach (array_keys($toolurlparams) as $paramname) {
            if (isset($signedparams[$paramname])) {
                unset($signedparams[$paramname]);
            }
        }
    }

    // Check for params that should not be passed. Unset if they are set.
    $unwantedparams = ['resource_link_id', 'resource_link_title', 'resource_link_description', 'launch_presentation_return_url',
        'lis_result_sourcedid'];
    foreach ($unwantedparams as $param) {
        if (isset($signedparams[$param])) {
            unset($signedparams[$param]);
        }
    }

    // Prepare result object.
    $result = new stdClass();
    $result->params = $signedparams;
    $result->url = $toolurlout;

    return $result;
}

/**
 * Processes the tool provider's response to the ContentItemSelectionRequest and builds the configuration data from the
 * selected content item.
 * This configuration data can be then used when adding a tool into the course.
 *
 * @param int $typeid
 *        The tool type ID.
 * @param string $messagetype
 *        The value for the qtype_lti_message_type parameter.
 * @param string $ltiversion
 *        The value for the qtype_lti_version parameter.
 * @param string $consumerkey
 *        The consumer key.
 * @param string $contentitemsjson
 *        The JSON string for the content_items parameter.
 * @return stdClass The array of module information objects.
 * @throws moodle_exception
 * @throws lti\OAuthException
 */
function qtype_lti_tool_configuration_from_content_item($typeid, $messagetype, $ltiversion, $consumerkey, $contentitemsjson) {
    $tool = qtype_lti_get_type($typeid);
    // Validate parameters.
    if (!$tool) {
        throw new moodle_exception('errortooltypenotfound', 'qtype_lti');
    }
    // Check qtype_lti_message_type. Show debugging if it's not set to ContentItemSelection.
    // No need to throw exceptions for now since qtype_lti_message_type does not seem to be used in this processing at the moment.
    if ($messagetype !== 'ContentItemSelection') {
        debugging("lti_message_type is invalid: {$messagetype}. It should be set to 'ContentItemSelection'.", DEBUG_DEVELOPER);
    }

    $typeconfig = qtype_lti_get_type_config($typeid);

    if (isset($tool->toolproxyid)) {
        $islti2 = true;
        $toolproxy = qtype_lti_get_tool_proxy($tool->toolproxyid);
        $key = $toolproxy->guid;
        $secret = $toolproxy->secret;
    } else {
        $islti2 = false;
        $toolproxy = null;
        if (!empty($typeconfig['resourcekey'])) {
            $key = $typeconfig['resourcekey'];
        } else {
            $key = '';
        }
        if (!empty($typeconfig['password'])) {
            $secret = $typeconfig['password'];
        } else {
            $secret = '';
        }
    }

    // Check LTI versions from our side and the response's side. Show debugging if they don't match.
    // No need to throw exceptions for now since LTI version does not seem to be used in this processing at the moment.
    $expectedversion = QTYPE_LTI_VERSION_1;
    if ($islti2) {
        $expectedversion = QTYPE_LTI_VERSION_2;
    }
    if ($ltiversion !== $expectedversion) {
        debugging(
                "lti_version from response does not match the tool's configuration. Tool: {$expectedversion}," .
                     " Response: {$ltiversion}", DEBUG_DEVELOPER);
    }

    if ($consumerkey !== $key) {
        throw new moodle_exception('errorincorrectconsumerkey', 'qtype_lti');
    }

    $store = new lti\TrivialOAuthDataStore();
    $store->add_consumer($key, $secret);
    $server = new lti\OAuthServer($store);
    $method = new lti\OAuthSignatureMethod_HMAC_SHA1();
    $server->add_signature_method($method);
    $request = lti\OAuthRequest::from_request();
    try {
        $server->verify_request($request);
    } catch (lti\OAuthException $e) {
        throw new lti\OAuthException("OAuth signature failed: " . $e->getMessage());
    }

    $items = json_decode($contentitemsjson);
    if (empty($items)) {
        throw new moodle_exception('errorinvaliddata', 'qtype_lti', '', $contentitemsjson);
    }
    if ($items->{'@context'} !== 'http://purl.imsglobal.org/ctx/lti/v1/ContentItem') {
        throw new moodle_exception('errorinvalidmediatype', 'qtype_lti', '', $items->{'@context'});
    }
    if (!isset($items->{'@graph'}) || !is_array($items->{'@graph'}) || (count($items->{'@graph'}) > 1)) {
        throw new moodle_exception('errorinvalidresponseformat', 'qtype_lti');
    }

    $config = null;
    if (!empty($items->{'@graph'})) {
        $item = $items->{'@graph'}[0];

        $config = new stdClass();
        $config->name = '';
        if (isset($item->title)) {
            $config->name = $item->title;
        }
        if (empty($config->name)) {
            $config->name = $tool->name;
        }
        if (isset($item->text)) {
            $config->introeditor = ['text' => $item->text, 'format' => FORMAT_PLAIN];
        }
        if (isset($item->icon->{'@id'})) {
            $iconurl = new moodle_url($item->icon->{'@id'});
            // Assign item's icon URL to secureicon or icon depending on its scheme.
            if (strtolower($iconurl->get_scheme()) === 'https') {
                $config->secureicon = $iconurl->out(false);
            } else {
                $config->icon = $iconurl->out(false);
            }
        }
        if (isset($item->url)) {
            $url = new moodle_url($item->url);
            $config->toolurl = $url->out(false);
            $config->typeid = 0;
        } else {
            $config->typeid = $typeid;
        }
        $config->instructorchoicesendname = QTYPE_LTI_SETTING_NEVER;
        $config->instructorchoicesendemailaddr = QTYPE_LTI_SETTING_NEVER;
        $config->instructorchoiceacceptgrades = QTYPE_LTI_SETTING_NEVER;
        $config->launchcontainer = QTYPE_LTI_LAUNCH_CONTAINER_DEFAULT;
        if (isset($item->placementAdvice->presentationDocumentTarget)) {
            if ($item->placementAdvice->presentationDocumentTarget === 'window') {
                $config->launchcontainer = QTYPE_LTI_LAUNCH_CONTAINER_WINDOW;
            } else if ($item->placementAdvice->presentationDocumentTarget === 'frame') {
                $config->launchcontainer = QTYPE_LTI_LAUNCH_CONTAINER_EMBED_NO_BLOCKS;
            } else if ($item->placementAdvice->presentationDocumentTarget === 'iframe') {
                $config->launchcontainer = QTYPE_LTI_LAUNCH_CONTAINER_EMBED;
            }
        }
        if (isset($item->custom)) {
            $customparameters = [];
            foreach ($item->custom as $key => $value) {
                $customparameters[] = "{$key}={$value}";
            }
            $config->instructorcustomparameters = implode("\n", $customparameters);
        }
    }
    return $config;
}

function qtype_lti_get_tool_table($tools, $id) {
    global $CFG, $OUTPUT, $USER;
    $html = '';

    $typename = get_string('typename', 'qtype_lti');
    $baseurl = get_string('baseurl', 'qtype_lti');
    $action = get_string('action', 'qtype_lti');
    $createdon = get_string('createdon', 'qtype_lti');

    if (!empty($tools)) {
        $html .= "
        <div id=\"{$id}_tools_container\" style=\"margin-top:.5em;margin-bottom:.5em\">
            <table id=\"{$id}_tools\">
                <thead>
                    <tr>
                        <th>$typename</th>
                        <th>$baseurl</th>
                        <th>$createdon</th>
                        <th>$action</th>
                    </tr>
                </thead>
        ";

        foreach ($tools as $type) {
            $date = userdate($type->timecreated, get_string('strftimedatefullshort', 'core_langconfig'));
            $accept = get_string('accept', 'qtype_lti');
            $update = get_string('update', 'qtype_lti');
            $delete = get_string('delete', 'qtype_lti');

            if (empty($type->toolproxyid)) {
                $baseurl = new \moodle_url('/question/type/lti/typessettings.php',
                                        array('action' => 'accept', 'id' => $type->id, 'sesskey' => sesskey(), 'tab' => $id));
                $ref = $type->baseurl;
            } else {
                $baseurl = new \moodle_url('/question/type/lti/toolssettings.php',
                                        array('action' => 'accept', 'id' => $type->id, 'sesskey' => sesskey(), 'tab' => $id));
                $ref = $type->tpname;
            }

            $accepthtml = $OUTPUT->action_icon($baseurl, new \pix_icon('t/check', $accept, '', array('class' => 'iconsmall')), null,
                                            array('title' => $accept, 'class' => 'editing_accept'));

            $deleteaction = 'delete';

            if ($type->state == QTYPE_LTI_TOOL_STATE_CONFIGURED) {
                $accepthtml = '';
            }

            if ($type->state != QTYPE_LTI_TOOL_STATE_REJECTED) {
                $deleteaction = 'reject';
                $delete = get_string('reject', 'qtype_lti');
            }

            $updateurl = clone ($baseurl);
            $updateurl->param('action', 'update');
            $updatehtml = $OUTPUT->action_icon($updateurl, new \pix_icon('t/edit', $update, '', array('class' => 'iconsmall')),
                                            null, array('title' => $update, 'class' => 'editing_update'));

            if (($type->state != QTYPE_LTI_TOOL_STATE_REJECTED) || empty($type->toolproxyid)) {
                $deleteurl = clone ($baseurl);
                $deleteurl->param('action', $deleteaction);
                $deletehtml = $OUTPUT->action_icon($deleteurl,
                                                new \pix_icon('t/delete', $delete, '', array('class' => 'iconsmall')), null,
                                                array('title' => $delete, 'class' => 'editing_delete'));
            } else {
                $deletehtml = '';
            }
            $html .= "
            <tr>
                <td>
                    {$type->name}
                </td>
                <td>
                    {$ref}
                </td>
                <td>
                    {$date}
                </td>
                <td align=\"center\">
                    {$accepthtml}{$updatehtml}{$deletehtml}
                </td>
            </tr>
            ";
        }
        $html .= '</table></div>';
    } else {
        $html .= get_string('no_' . $id, 'qtype_lti');
    }

    return $html;
}

/**
 * This function builds the tab for a category of tool proxies
 *
 * @param object $toolproxies
 *        Tool proxy instance objects
 * @param string $id
 *        Category ID
 * @return string HTML for tab
 */
function qtype_lti_get_tool_proxy_table($toolproxies, $id) {
    global $OUTPUT;

    if (!empty($toolproxies)) {
        $typename = get_string('typename', 'qtype_lti');
        $url = get_string('registrationurl', 'qtype_lti');
        $action = get_string('action', 'qtype_lti');
        $createdon = get_string('createdon', 'qtype_lti');

        $html = <<< EOD
        <div id="{$id}_tool_proxies_container" style="margin-top: 0.5em; margin-bottom: 0.5em">
            <table id="{$id}_tool_proxies">
                <thead>
                    <tr>
                        <th>{$typename}</th>
                        <th>{$url}</th>
                        <th>{$createdon}</th>
                        <th>{$action}</th>
                    </tr>
                </thead>
EOD;
        foreach ($toolproxies as $toolproxy) {
            $date = userdate($toolproxy->timecreated, get_string('strftimedatefullshort', 'core_langconfig'));
            $accept = get_string('register', 'qtype_lti');
            $update = get_string('update', 'qtype_lti');
            $delete = get_string('delete', 'qtype_lti');

            $baseurl = new \moodle_url('/question/type/lti/registersettings.php',
                                    array('action' => 'accept', 'id' => $toolproxy->id, 'sesskey' => sesskey(), 'tab' => $id));

            $registerurl = new \moodle_url('/question/type/lti/register.php',
                                        array('id' => $toolproxy->id, 'sesskey' => sesskey(), 'tab' => 'tool_proxy'));

            $accepthtml = $OUTPUT->action_icon($registerurl, new \pix_icon('t/check', $accept, '', array('class' => 'iconsmall')),
                                            null, array('title' => $accept, 'class' => 'editing_accept'));

            $deleteaction = 'delete';

            if ($toolproxy->state != QTYPE_LTI_TOOL_PROXY_STATE_CONFIGURED) {
                $accepthtml = '';
            }

            if (($toolproxy->state == QTYPE_LTI_TOOL_PROXY_STATE_CONFIGURED) ||
                 ($toolproxy->state == QTYPE_LTI_TOOL_PROXY_STATE_PENDING)) {
                $delete = get_string('cancel', 'qtype_lti');
            }

            $updateurl = clone ($baseurl);
            $updateurl->param('action', 'update');
            $updatehtml = $OUTPUT->action_icon($updateurl, new \pix_icon('t/edit', $update, '', array('class' => 'iconsmall')),
                                            null, array('title' => $update, 'class' => 'editing_update'));

            $deleteurl = clone ($baseurl);
            $deleteurl->param('action', $deleteaction);
            $deletehtml = $OUTPUT->action_icon($deleteurl, new \pix_icon('t/delete', $delete, '', array('class' => 'iconsmall')),
                                            null, array('title' => $delete, 'class' => 'editing_delete'));
            $html .= <<< EOD
            <tr>
                <td>
                    {$toolproxy->name}
                </td>
                <td>
                    {$toolproxy->regurl}
                </td>
                <td>
                    {$date}
                </td>
                <td align="center">
                    {$accepthtml}{$updatehtml}{$deletehtml}
                </td>
            </tr>
EOD;
        }
        $html .= '</table></div>';
    } else {
        $html = get_string('no_' . $id, 'qtype_lti');
    }

    return $html;
}

/**
 * Extracts the enabled capabilities into an array, including those implicitly declared in a parameter
 *
 * @param object $tool
 *        Tool instance object
 * @return Array of enabled capabilities
 */
function qtype_lti_get_enabled_capabilities($tool) {
    if (!empty($tool->enabledcapability)) {
        $enabledcapabilities = explode("\n", $tool->enabledcapability);
    } else {
        $enabledcapabilities = array();
    }
    $paramstr = str_replace("\r\n", "\n", $tool->parameter);
    $paramstr = str_replace("\n\r", "\n", $paramstr);
    $paramstr = str_replace("\r", "\n", $paramstr);
    $params = explode("\n", $paramstr);
    foreach ($params as $param) {
        $pos = strpos($param, '=');
        if (($pos === false) || ($pos < 1)) {
            continue;
        }
        $value = trim(core_text::substr($param, $pos + 1, strlen($param)));
        if (substr($value, 0, 1) == '$') {
            $value = substr($value, 1);
            if (!in_array($value, $enabledcapabilities)) {
                $enabledcapabilities[] = $value;
            }
        }
    }
    return $enabledcapabilities;
}

/**
 * Splits the custom parameters field to the various parameters
 *
 * @param object $toolproxy
 *        Tool proxy instance object
 * @param object $tool
 *        Tool instance object
 * @param array $params
 *        LTI launch parameters
 * @param string $customstr
 *        String containing the parameters
 * @param boolean $islti2
 *        True if an LTI 2 tool is being launched
 * @return array of custom parameters
 */
function qtype_lti_split_custom_parameters($toolproxy, $tool, $params, $customstr, $islti2 = false, $userid) {
    $customstr = str_replace("\r\n", "\n", $customstr);
    $customstr = str_replace("\n\r", "\n", $customstr);
    $customstr = str_replace("\r", "\n", $customstr);
    $lines = explode("\n", $customstr); // Or should this split on "/[\n;]/"?
    $retval = array();
    foreach ($lines as $line) {
        $pos = strpos($line, '=');
        if ($pos === false || $pos < 1) {
            continue;
        }
        $key = trim(core_text::substr($line, 0, $pos));
        $key = qtype_lti_map_keyname($key, false);
        $val = trim(core_text::substr($line, $pos + 1, strlen($line)));
        $val = qtype_lti_parse_custom_parameter($toolproxy, $tool, $params, $val, $islti2, $userid);
        $key2 = qtype_lti_map_keyname($key);
        $retval['custom_' . $key2] = $val;
        if ($key != $key2) {
            $retval['custom_' . $key] = $val;
        }
    }
    return $retval;
}

/**
 * Adds the custom parameters to an array
 *
 * @param object $toolproxy
 *        Tool proxy instance object
 * @param object $tool
 *        Tool instance object
 * @param array $params
 *        LTI launch parameters
 * @param array $parameters
 *        Array containing the parameters
 * @return array Array of custom parameters
 */
function qtype_lti_get_custom_parameters($toolproxy, $tool, $params, $parameters) {
    $retval = array();
    foreach ($parameters as $key => $val) {
        $key2 = qtype_lti_map_keyname($key);
        $val = qtype_lti_parse_custom_parameter($toolproxy, $tool, $params, $val, true);
        $retval['custom_' . $key2] = $val;
        if ($key != $key2) {
            $retval['custom_' . $key] = $val;
        }
    }
    return $retval;
}

/**
 * Parse a custom parameter to replace any substitution variables
 *
 * @param object $toolproxy
 *        Tool proxy instance object
 * @param object $tool
 *        Tool instance object
 * @param array $params
 *        LTI launch parameters
 * @param string $value
 *        Custom parameter value
 * @param boolean $islti2
 *        True if an LTI 2 tool is being launched
 * @return Parsed value of custom parameter
 */
function qtype_lti_parse_custom_parameter($toolproxy, $tool, $params, $value, $islti2, $userid) {
    global $USER, $COURSE;

    $originaluser = $USER;

    if ($value) {
        if (substr($value, 0, 1) == '\\') {
            $value = substr($value, 1);
        } else if (substr($value, 0, 1) == '$') {
            $value1 = substr($value, 1);
            $enabledcapabilities = qtype_lti_get_enabled_capabilities($tool);
            if (!$islti2 || in_array($value1, $enabledcapabilities)) {
                $capabilities = qtype_lti_get_capabilities();
                if (array_key_exists($value1, $capabilities)) {
                    $val = $capabilities[$value1];
                    if ($val) {
                        if (substr($val, 0, 1) != '$') {
                            $value = $params[$val];
                        } else {
                            $valarr = explode('->', substr($val, 1), 2);
                            $value = "{${$valarr[0]}->{$valarr[1]}}";
                            $value = str_replace('<br />', ' ', $value);
                            $value = str_replace('<br>', ' ', $value);
                            $value = format_string($value);
                        }
                    } else {
                        $value = qtype_lti_calculate_custom_parameter($value1, $originaluser->id);
                    }
                } else if ($islti2) {
                    $val = $value;
                    $services = qtype_lti_get_services();
                    foreach ($services as $service) {
                        $service->set_tool_proxy($toolproxy);
                        $value = $service->parse_value($val);
                        if ($val != $value) {
                            break;
                        }
                    }
                }
            }
        }
    }
    return $value;
}

/**
 * Calculates the value of a custom parameter that has not been specified earlier
 *
 * @param string $value
 *        Custom parameter value
 * @return string Calculated value of custom parameter
 */
function qtype_lti_calculate_custom_parameter($value, $userid) {
    global $USER, $COURSE;

    switch ($value) {
        case 'Moodle.Person.userGroupIds':
            return implode(",", groups_get_user_groups($COURSE->id, $userid)[0]);
    }
    return null;
}

/**
 * Used for building the names of the different custom parameters
 *
 * @param string $key
 *        Parameter name
 * @param bool $tolower
 *        Do we want to convert the key into lower case?
 * @return string Processed name
 */
function qtype_lti_map_keyname($key, $tolower = true) {
    $newkey = "";
    if ($tolower) {
        $key = core_text::strtolower(trim($key));
    }
    foreach (str_split($key) as $ch) {
        if (($ch >= 'a' && $ch <= 'z') || ($ch >= '0' && $ch <= '9') || (!$tolower && ($ch >= 'A' && $ch <= 'Z'))) {
            $newkey .= $ch;
        } else {
            $newkey .= '_';
        }
    }
    return $newkey;
}

/**
 * Gets the IMS role string for the specified user and LTI course module.
 *
 * @param mixed $user
 *        User object or user id
 * @param int $cmid
 *        The course module id of the LTI activity
 * @param int $courseid
 *        The course id of the LTI activity
 * @param boolean $islti2
 *        True if an LTI 2 tool is being launched
 * @return string A role string suitable for passing with an LTI launch
 */
function qtype_lti_get_ims_role($user, $cmid, $courseid, $islti2) {
    $roles = array();

    if (empty($cmid)) {
        // If no cmid is passed, check if the user is a teacher in the course
        // This allows other modules to programmatically "fake" a launch without
        // a real LTI instance.
        $context = context_course::instance($courseid);

        if (has_capability('moodle/course:manageactivities', $context, $user)) {
            array_push($roles, 'Instructor');
        } else {
            array_push($roles, 'Learner');
        }
    } else {
        $context = context_module::instance($cmid);

        if (has_capability('qtype/lti:manage', $context)) {
            array_push($roles, 'Instructor');
        } else {
            array_push($roles, 'Learner');
        }
    }

    if (is_siteadmin($user) || has_capability('qtype/lti:admin', $context)) {
        // Make sure admins do not have the Learner role, then set admin role.
        $roles = array_diff($roles, array('Learner'));
        if (!$islti2) {
            array_push($roles, 'urn:lti:sysrole:ims/lis/Administrator', 'urn:lti:instrole:ims/lis/Administrator');
        } else {
            array_push($roles, 'http://purl.imsglobal.org/vocab/lis/v2/person#Administrator');
        }
    }

    return join(',', $roles);
}

/**
 * Returns configuration details for the tool
 *
 * @param int $typeid
 *        Basic LTI tool typeid
 * @return array Tool Configuration
 */
function qtype_lti_get_type_config($typeid) {
    global $DB;

    $query = "SELECT 1000000 + id AS uniqueid1, name, value
                FROM {qtype_lti_types_config}
               WHERE typeid = :typeid1
           UNION ALL
              SELECT 2000000 + id AS uniqueid2, 'toolurl' AS name, baseurl AS value
                FROM {qtype_lti_types}
               WHERE id = :typeid2
           UNION ALL
              SELECT 3000000 + id AS uniqueid3, 'icon' AS name, icon AS value
                FROM {qtype_lti_types}
               WHERE id = :typeid3
           UNION ALL
              SELECT 4000000 + id AS uniqueid4, 'secureicon' AS name, secureicon AS value
                FROM {qtype_lti_types}
               WHERE id = :typeid4";

    $typeconfig = array();
    $configs = $DB->get_records_sql($query,
                                    array('typeid1' => $typeid, 'typeid2' => $typeid, 'typeid3' => $typeid, 'typeid4' => $typeid));

    if (!empty($configs)) {
        foreach ($configs as $config) {
            $typeconfig[$config->name] = $config->value;
        }
    }

    return $typeconfig;
}

function qtype_lti_get_tools_by_url($url, $state, $courseid = null) {
    $domain = qtype_lti_get_domain_from_url($url);

    return qtype_lti_get_tools_by_domain($domain, $state, $courseid);
}

function qtype_lti_get_tools_by_domain($domain, $state = null, $courseid = null) {
    global $DB, $SITE;

    $filters = array('tooldomain' => $domain);

    $statefilter = '';
    $coursefilter = '';

    if ($state) {
        $statefilter = 'AND state = :state';
    }

    if ($courseid && $courseid != $SITE->id) {
        $coursefilter = 'OR course = :courseid';
    }

    $query = "SELECT *
                FROM {qtype_lti_types}
               WHERE tooldomain = :tooldomain
                 AND (course = :siteid $coursefilter)
                 $statefilter";

    return $DB->get_records_sql($query,
                                array('courseid' => $courseid, 'siteid' => $SITE->id, 'tooldomain' => $domain, 'state' => $state));
}

/**
 * Returns all basicLTI tools configured by the administrator
 */
function qtype_lti_filter_get_types($course) {
    global $DB;

    if (!empty($course)) {
        $where = "WHERE t.course = :course";
        $params = array('course' => $course);
    } else {
        $where = '';
        $params = array();
    }
    $query = "SELECT t.id, t.name, t.baseurl, t.state, t.toolproxyid, t.timecreated, tp.name tpname
                FROM {qtype_lti_types} t LEFT OUTER JOIN {qtype_lti_tool_proxies} tp ON t.toolproxyid = tp.id
                {$where}";
    return $DB->get_records_sql($query, $params);
}

/**
 * Given an array of tools, filter them based on their state
 *
 * @param array $tools
 *        An array of qtype_lti_types records
 * @param int $state
 *        One of the QTYPE_LTI_TOOL_STATE_* constants
 * @return array
 */
function qtype_lti_filter_tool_types(array $tools, $state) {
    $return = array();
    foreach ($tools as $key => $tool) {
        if ($tool->state == $state) {
            $return[$key] = $tool;
        }
    }
    return $return;
}

/**
 * Returns all lti types visible in this course
 *
 * @param int $courseid
 *        The id of the course to retieve types for
 * @param array $coursevisible
 *        options for 'coursevisible' field,
 *        default [QTYPE_LTI_COURSEVISIBLE_PRECONFIGURED, QTYPE_LTI_COURSEVISIBLE_ACTIVITYCHOOSER]
 * @return stdClass[] All the lti types visible in the given course
 */
function qtype_lti_get_lti_types_by_course($courseid, $coursevisible = null) {
    global $DB, $SITE;

    if ($coursevisible === null) {
        $coursevisible = [QTYPE_LTI_COURSEVISIBLE_PRECONFIGURED, QTYPE_LTI_COURSEVISIBLE_ACTIVITYCHOOSER];
    }

    list($coursevisiblesql, $coursevisparams) = $DB->get_in_or_equal($coursevisible, SQL_PARAMS_NAMED, 'coursevisible');

    $courseconds = [];
    if (has_capability('qtype/lti:adddefaultinstance', context_course::instance($courseid))) {
        $courseconds[] = "course = :courseid";
    }
    if (has_capability('qtype/lti:addgloballypreconfigedtoolinstance', context_course::instance($courseid))) {
        $courseconds[] = "course = :siteid";
    }
    if (!$courseconds) {
        return [];
    }
    $coursecond = implode(" OR ", $courseconds);
    $query = "SELECT *
    FROM {qtype_lti_types}
    WHERE coursevisible $coursevisiblesql
    AND ($coursecond)
    AND state = :active";

    return $DB->get_records_sql($query,
                                array('siteid' => $SITE->id, 'courseid' => $courseid, 'active' => QTYPE_LTI_TOOL_STATE_CONFIGURED) +
                                     $coursevisparams);
}

/**
 * Returns tool types for lti add instance and edit page
 *
 * @return array Array of lti types
 */
function qtype_lti_get_types_for_add_instance() {
    global $COURSE;
    $admintypes = qtype_lti_get_lti_types_by_course($COURSE->id);

    $types = array();
    // Shall we remove Automatic option? For now, No.
    if (has_capability('qtype/lti:adddefaultinstance', context_course::instance($COURSE->id))) {
        $types[0] = (object)array('name' => get_string('automatic', 'qtype_lti'), 'course' => 0, 'toolproxyid' => null);
    }

    foreach ($admintypes as $type) {
        $types[$type->id] = $type;
    }

    return $types;
}

/**
 * Returns a list of configured types in the given course
 *
 * @param int $courseid
 *        The id of the course to retieve types for
 * @param int $sectionreturn
 *        section to return to for forming the URLs
 * @return array Array of lti types. Each element is object with properties: name, title, icon, help, helplink, link
 */
function qtype_lti_get_configured_types($courseid, $sectionreturn = 0) {
    global $OUTPUT;
    $types = array();
    $admintypes = qtype_lti_get_lti_types_by_course($courseid, [QTYPE_LTI_COURSEVISIBLE_ACTIVITYCHOOSER]);

    foreach ($admintypes as $ltitype) {
        $type = new stdClass();
        $type->modclass = MOD_CLASS_ACTIVITY;
        $type->name = 'lti_type_' . $ltitype->id;
        // Clean the name. We don't want tags here.
        $type->title = clean_param($ltitype->name, PARAM_NOTAGS);
        $trimmeddescription = trim($ltitype->description);
        if ($trimmeddescription != '') {
            // Clean the description. We don't want tags here.
            $type->help = clean_param($trimmeddescription, PARAM_NOTAGS);
            $type->helplink = get_string('modulename_shortcut_link', 'qtype_lti');
        }
        if (empty($ltitype->icon)) {
            $type->icon = $OUTPUT->pix_icon('icon', '', 'qtype_lti', array('class' => 'icon'));
        } else {
            $type->icon = html_writer::empty_tag('img', array('src' => $ltitype->icon, 'alt' => $ltitype->name, 'class' => 'icon'));
        }
        $type->link = new moodle_url('/course/modedit.php',
                                    array('add' => 'qtype_lti', 'return' => 0, 'course' => $courseid, 'sr' => $sectionreturn,
                                        'typeid' => $ltitype->id));
        $types[] = $type;
    }
    return $types;
}

function qtype_lti_get_domain_from_url($url) {
    $matches = array();

    if (preg_match(QTYPE_LTI_URL_DOMAIN_REGEX, $url, $matches)) {

        return $matches[1];
    }
}

function qtype_lti_get_tool_by_url_match($url, $courseid = null, $state = QTYPE_LTI_TOOL_STATE_CONFIGURED) {
    $possibletools = qtype_lti_get_tools_by_url($url, $state, $courseid);

    return qtype_lti_get_best_tool_by_url($url, $possibletools, $courseid);
}

function qtype_lti_get_url_thumbprint($url) {
    // Parse URL requires a schema otherwise everything goes into 'path'. Fixed 5.4.7 or later.
    if (preg_match('/https?:\/\//', $url) !== 1) {
        $url = 'http://' . $url;
    }
    $urlparts = gtype_lti_get_parsed_url(strtolower($url));

    $urllower = $urlparts['host'] . '/' . $urlparts['path'] . $urlparts['query'];

    return $urllower;
}

function gtype_lti_get_parsed_url($url) {
    $urlparts = parse_url($url);
   
    if (!isset($urlparts['host'])) {
        $urlparts['host'] = '';
    }

    $urlparts['host'] = ltrim($urlparts['host'], 'www.');

    if (!isset($urlparts['path'])) {
        $urlparts['path'] = '';
    }

    if (!isset($urlparts['query'])) {
        $urlparts['query'] = '';
    }

    if ($urlparts['query'] != '') {
        $urlparts['query'] = '?' . $urlparts['query'];
    }

    if (!isset($urlparts['scheme'])) {
        $urlparts['scheme'] = 'http';
    }

    if ($urlparts['scheme'] == '') {
        $urlparts['scheme'] = 'http';
    }

    return $urlparts;
}

function qtype_lti_get_best_tool_by_url($url, $tools, $courseid = null) {
    if (count($tools) === 0) {
        return null;
    }

    $urllower = qtype_lti_get_url_thumbprint($url);

    foreach ($tools as $tool) {
        $tool->_matchscore = 0;

        $toolbaseurllower = qtype_lti_get_url_thumbprint($tool->baseurl);

        if ($urllower === $toolbaseurllower) {
            // 100 points for exact thumbprint match.
            $tool->_matchscore += 100;
        } else if (substr($urllower, 0, strlen($toolbaseurllower)) === $toolbaseurllower) {
            // 50 points if tool thumbprint starts with the base URL thumbprint.
            $tool->_matchscore += 50;
        }

        // Prefer course tools over site tools.
        if (!empty($courseid)) {
            // Minus 10 points for not matching the course id (global tools).
            if ($tool->course != $courseid) {
                $tool->_matchscore -= 10;
            }
        }
    }

    $bestmatch = array_reduce($tools,
                            function ($value, $tool) {
                                if ($tool->_matchscore > $value->_matchscore) {
                                    return $tool;
                                } else {
                                    return $value;
                                }
                            }, (object)array('_matchscore' => -1));

    // None of the tools are suitable for this URL.
    if ($bestmatch->_matchscore <= 0) {
        return null;
    }

    return $bestmatch;
}

function qtype_lti_get_shared_secrets_by_key($key) {
    global $DB;

    // Look up the shared secret for the specified key in both the types_config table (for configured tools)
    // And in the lti resource table for ad-hoc tools.
    $query = "SELECT t2.value
                FROM {qtype_lti_types_config} t1
                JOIN {qtype_lti_types_config} t2 ON t1.typeid = t2.typeid
                JOIN {qtype_lti_types} type ON t2.typeid = type.id
              WHERE t1.name = 'resourcekey'
                AND t1.value = :key1
                AND t2.name = 'password'
                AND type.state = :configured1
               UNION
              SELECT tp.secret AS value
                FROM {qtype_lti_tool_proxies} tp
                JOIN {qtype_lti_types} t ON tp.id = t.toolproxyid
              WHERE tp.guid = :key2
                AND t.state = :configured2
              UNION
             SELECT password AS value
               FROM {qtype_lti_options}
              WHERE resourcekey = :key3";

    $sharedsecrets = $DB->get_records_sql($query,
                                        array('configured1' => QTYPE_LTI_TOOL_STATE_CONFIGURED,
                                            'configured2' => QTYPE_LTI_TOOL_STATE_CONFIGURED, 'key1' => $key, 'key2' => $key,
                                            'key3' => $key));

    $values = array_map(function ($item) {
        return $item->value;
    }, $sharedsecrets);

    // There should really only be one shared secret per key. But, we can't prevent
    // more than one getting entered. For instance, if the same key is used for two tool providers.
    return $values;
}

/**
 * Delete a Basic LTI configuration
 *
 * @param int $id
 *        Configuration id
 */
function qtype_lti_delete_type($id) {
    global $DB;

    // We should probably just copy the launch URL to the tool instances in this case... using a single query.
    $DB->delete_records('qtype_lti_types', array('id' => $id));
    $DB->delete_records('qtype_lti_types_config', array('typeid' => $id));
}

function qtype_lti_set_state_for_type($id, $state) {
    global $DB;

    $DB->update_record('qtype_lti_types', array('id' => $id, 'state' => $state));
}

/**
 * Transforms a basic LTI object to an array
 *
 * @param object $ltiobject
 *        Basic LTI object
 * @return array Basic LTI configuration details
 */
function qtype_lti_get_config($ltiobject) {
    $typeconfig = array();
    $typeconfig = (array)$ltiobject;
    $additionalconfig = qtype_lti_get_type_config($ltiobject->typeid);
    $typeconfig = array_merge($typeconfig, $additionalconfig);
    return $typeconfig;
}

/**
 * Generates some of the tool configuration based on the instance details
 *
 * @param int $id
 *
 * @return Instance configuration
 */
function qtype_lti_get_type_config_from_instance($id) {
    global $DB;

    $instance = $DB->get_record('qtype_lti_options', array('id' => $id));
    $config = qtype_lti_get_config($instance);

    $type = new \stdClass();
    $type->lti_fix = $id;
    if (isset($config['toolurl'])) {
        $type->lti_toolurl = $config['toolurl'];
    }
    if (isset($config['instructorchoicesendname'])) {
        $type->lti_sendname = $config['instructorchoicesendname'];
    }
    if (isset($config['instructorchoicesendemailaddr'])) {
        $type->lti_sendemailaddr = $config['instructorchoicesendemailaddr'];
    }
    if (isset($config['instructorchoiceacceptgrades'])) {
        $type->lti_acceptgrades = $config['instructorchoiceacceptgrades'];
    }
    if (isset($config['instructorchoiceallowroster'])) {
        $type->lti_allowroster = $config['instructorchoiceallowroster'];
    }

    if (isset($config['instructorcustomparameters'])) {
        $type->lti_allowsetting = $config['instructorcustomparameters'];
    }
    return $type;
}

function qtype_lti_get_user_fields() {
    global $DB;

    $fields = [''];
    foreach ($DB->get_columns('user') as $field) {
        if ($field->name && !in_array($field->name, ['password'])) {
            $fields[$field->name] = $field->name;
        }
    }

    return $fields;
}

function qtype_lti_get_custom_fields() {
    global $DB;

    $fields = [''];
    foreach ($DB->get_records_sql('SELECT DISTINCT id, name FROM {user_info_field}') as $field) {
        if ($field->id && $field->name) {
            $fields[$field->id] = $field->name;
        }
    }

    return $fields;
}

/**
 * Generates some of the tool configuration based on the admin configuration details
 *
 * @param int $id
 *
 * @return Configuration details
 */
function qtype_lti_get_type_type_config($id) {
    global $DB;

    $basicltitype = $DB->get_record('qtype_lti_types', array('id' => $id));
    $config = qtype_lti_get_type_config($id);

    $type = new \stdClass();

    $type->lti_typename = $basicltitype->name;

    $type->typeid = $basicltitype->id;

    $type->toolproxyid = $basicltitype->toolproxyid;

    $type->lti_toolurl = $basicltitype->baseurl;

    $type->lti_description = $basicltitype->description;

    $type->lti_parameters = $basicltitype->parameter;

    $type->lti_icon = $basicltitype->icon;

    $type->lti_secureicon = $basicltitype->secureicon;

    if (isset($config['resourcekey'])) {
        $type->lti_resourcekey = $config['resourcekey'];
    }
    if (isset($config['password'])) {
        $type->lti_password = $config['password'];
    }

    if (isset($config['sendname'])) {
        $type->lti_sendname = $config['sendname'];
    }
    if (isset($config['instructorchoicesendname'])) {
        $type->lti_instructorchoicesendname = $config['instructorchoicesendname'];
    }
    if (isset($config['sendemailaddr'])) {
        $type->lti_sendemailaddr = $config['sendemailaddr'];
    }
    if (isset($config['instructorchoicesendemailaddr'])) {
        $type->lti_instructorchoicesendemailaddr = $config['instructorchoicesendemailaddr'];
    }
    if (isset($config['acceptgrades'])) {
        $type->lti_acceptgrades = $config['acceptgrades'];
    }
    if (isset($config['instructorchoiceacceptgrades'])) {
        $type->lti_instructorchoiceacceptgrades = $config['instructorchoiceacceptgrades'];
    }
    if (isset($config['allowroster'])) {
        $type->lti_allowroster = $config['allowroster'];
    }
    if (isset($config['instructorchoiceallowroster'])) {
        $type->lti_instructorchoiceallowroster = $config['instructorchoiceallowroster'];
    }

    if (isset($config['customparameters'])) {
        $type->lti_customparameters = $config['customparameters'];
    }

    if (isset($config['forcessl'])) {
        $type->lti_forcessl = $config['forcessl'];
    }

    if (isset($config['organizationid'])) {
        $type->lti_organizationid = $config['organizationid'];
    }
    if (isset($config['organizationurl'])) {
        $type->lti_organizationurl = $config['organizationurl'];
    }
    if (isset($config['organizationdescr'])) {
        $type->lti_organizationdescr = $config['organizationdescr'];
    }
    if (isset($config['launchcontainer'])) {
        $type->lti_launchcontainer = $config['launchcontainer'];
    }

    if (isset($config['coursevisible'])) {
        $type->lti_coursevisible = $config['coursevisible'];
    }

    if (isset($config['contentitem'])) {
        $type->lti_contentitem = $config['contentitem'];
    }

    if (isset($config['verifyltiurl'])) {
        $type->lti_verifyltiurl = $config['verifyltiurl'];
    }
    
    if (isset($config['checkduplicateltiurl'])) {
        $type->lti_checkduplicateltiurl = $config['checkduplicateltiurl'];
    }

    if (isset($config['userfield_optional'])) {
        $type->lti_userfield_optional = $config['userfield_optional'];
    }

    if (isset($config['userfield_other'])) {
        $type->lti_userfield_other = $config['userfield_other'];
    }

    if (isset($config['whichadditionalfield'])) {
        $type->lti_whichadditionalfield = $config['whichadditionalfield'];
    }

    if (isset($config['debuglaunch'])) {
        $type->lti_debuglaunch = $config['debuglaunch'];
    }

    if (isset($config['module_class_type'])) {
        $type->lti_module_class_type = $config['module_class_type'];
    }

    return $type;
}

function qtype_lti_prepare_type_for_save($type, $config) {
    if (isset($config->lti_toolurl)) {
        $type->baseurl = $config->lti_toolurl;
        $type->tooldomain = qtype_lti_get_domain_from_url($config->lti_toolurl);
    }
    if (isset($config->lti_description)) {
        $type->description = $config->lti_description;
    }
    if (isset($config->lti_typename)) {
        $type->name = $config->lti_typename;
    }
    if (isset($config->lti_coursevisible)) {
        $type->coursevisible = $config->lti_coursevisible;
    }

    if (isset($config->lti_icon)) {
        $type->icon = $config->lti_icon;
    }
    if (isset($config->lti_secureicon)) {
        $type->secureicon = $config->lti_secureicon;
    }

    $type->forcessl = !empty($config->lti_forcessl) ? $config->lti_forcessl : 0;
    $config->lti_forcessl = $type->forcessl;
    if (isset($config->lti_contentitem)) {
        $type->contentitem = !empty($config->lti_contentitem) ? $config->lti_contentitem : 0;
        $config->lti_contentitem = $type->contentitem;
    }

    $type->timemodified = time();

    unset($config->lti_typename);
    unset($config->lti_toolurl);
    unset($config->lti_description);
    unset($config->lti_icon);
    unset($config->lti_secureicon);
}

function qtype_lti_update_type($type, $config) {
    global $DB, $CFG;

    qtype_lti_prepare_type_for_save($type, $config);

    $clearcache = false;
    if (qtype_lti_request_is_using_ssl() && !empty($type->secureicon)) {
        $clearcache = !isset($config->oldicon) || ($config->oldicon !== $type->secureicon);
    } else {
        $clearcache = isset($type->icon) && (!isset($config->oldicon) || ($config->oldicon !== $type->icon));
    }
    unset($config->oldicon);

    if ($DB->update_record('qtype_lti_types', $type)) {
        foreach ($config as $key => $value) {
            if (substr($key, 0, 4) == 'lti_' && !is_null($value)) {
                $record = new \StdClass();
                $record->typeid = $type->id;
                $record->name = substr($key, 4);
                $record->value = $value;
                qtype_lti_update_config($record);
            }
        }

        require_once($CFG->libdir . '/modinfolib.php');
        if ($clearcache) {
            $sql = "SELECT DISTINCT course
                      FROM {qtype_lti_options}
                     WHERE typeid = ?";
            $courses = $DB->get_fieldset_sql($sql, array($type->id));

            foreach ($courses as $courseid) {
                rebuild_course_cache($courseid, true);
            }
        }
    }
}

function qtype_lti_add_type($type, $config) {
    global $USER, $SITE, $DB;

    qtype_lti_prepare_type_for_save($type, $config);

    if (!isset($type->state)) {
        $type->state = QTYPE_LTI_TOOL_STATE_PENDING;
    }

    if (!isset($type->timecreated)) {
        $type->timecreated = time();
    }

    if (!isset($type->createdby)) {
        $type->createdby = $USER->id;
    }

    if (!isset($type->course)) {
        $type->course = $SITE->id;
    }

    // Create a salt value to be used for signing passed data to extension services
    // The outcome service uses the service salt on the instance. This can be used
    // for communication with services not related to a specific LTI instance.
    $config->lti_servicesalt = uniqid('', true);

    $id = $DB->insert_record('qtype_lti_types', $type);

    if ($id) {
        foreach ($config as $key => $value) {
            if (substr($key, 0, 4) == 'lti_' && !is_null($value)) {
                $record = new \StdClass();
                $record->typeid = $id;
                $record->name = substr($key, 4);
                $record->value = $value;

                qtype_lti_add_config($record);
            }

            // Some records might not be connected to type id but rely on type id.
            $loosequestions = $DB->get_records_sql(
                                                    'select id, toolurl from {qtype_lti_options}
												 where ' . $DB->sql_like('toolurl', ':toolurl') . ' and
												 typeid = :typeid',
                                                    array('toolurl' => '%' . $type->baseurl . '%', 'typeid' => 0));
            if ($loosequestions) {
                foreach ($loosequestions as $loosequestion) {
                    $record = new \StdClass();
                    $record->id = $loosequestion->id;
                    $record->typeid = $id;
                    $DB->update_record('qtype_lti_options', $record);
                }
            }
        }
    }
    return $id;
}

/**
 * Given an array of tool proxies, filter them based on their state
 *
 * @param array $toolproxies
 *        An array of qtype_lti_tool_proxies records
 * @param int $state
 *        One of the QTYPE_LTI_TOOL_PROXY_STATE_* constants
 * @return array
 */
function qtype_lti_filter_tool_proxy_types(array $toolproxies, $state) {
    $return = array();
    foreach ($toolproxies as $key => $toolproxy) {
        if ($toolproxy->state == $state) {
            $return[$key] = $toolproxy;
        }
    }
    return $return;
}

/**
 * Get the tool proxy instance given its GUID
 *
 * @param string $toolproxyguid
 *        Tool proxy GUID value
 * @return object
 */
function qtype_lti_get_tool_proxy_from_guid($toolproxyguid) {
    global $DB;

    $toolproxy = $DB->get_record('qtype_lti_tool_proxies', array('guid' => $toolproxyguid));

    return $toolproxy;
}

/**
 * Get the tool proxy instance given its registration URL
 *
 * @param string $regurl
 *        Tool proxy registration URL
 * @return array The record of the tool proxy with this url
 */
function qtype_lti_get_tool_proxies_from_registration_url($regurl) {
    global $DB;

    return $DB->get_records_sql(
                                'SELECT * FROM {qtype_lti_tool_proxies}
        WHERE ' . $DB->sql_compare_text('regurl', 256) . ' = :regurl',
                                array('regurl' => $regurl));
}

/**
 * Generates some of the tool proxy configuration based on the admin configuration details
 *
 * @param int $id
 *
 * @return Tool Proxy details
 */
function qtype_lti_get_tool_proxy($id) {
    global $DB;

    $toolproxy = $DB->get_record('qtype_lti_tool_proxies', array('id' => $id));
    return $toolproxy;
}

/**
 * Returns lti tool proxies.
 *
 * @param bool $orphanedonly
 *        Only retrieves tool proxies that have no type associated with them
 * @return array of basicLTI types
 */
function qtype_lti_get_tool_proxies($orphanedonly) {
    global $DB;

    if ($orphanedonly) {
        $tools = $DB->get_records('qtype_lti_types');
        $usedproxyids = array_values($DB->get_fieldset_select('qtype_lti_types', 'toolproxyid', 'toolproxyid IS NOT NULL'));
        $proxies = $DB->get_records('qtype_lti_tool_proxies', null, 'state DESC, timemodified DESC');
        foreach ($proxies as $key => $value) {
            if (in_array($value->id, $usedproxyids)) {
                unset($proxies[$key]);
            }
        }
        return $proxies;
    } else {
        return $DB->get_records('qtype_lti_tool_proxies', null, 'state DESC, timemodified DESC');
    }
}

/**
 * Generates some of the tool proxy configuration based on the admin configuration details
 *
 * @param int $id
 *
 * @return Tool Proxy details
 */
function qtype_lti_get_tool_proxy_config($id) {
    $toolproxy = qtype_lti_get_tool_proxy($id);

    $tp = new \stdClass();
    $tp->lti_registrationname = $toolproxy->name;
    $tp->toolproxyid = $toolproxy->id;
    $tp->state = $toolproxy->state;
    $tp->lti_registrationurl = $toolproxy->regurl;
    $tp->lti_capabilities = explode("\n", $toolproxy->capabilityoffered);
    $tp->lti_services = explode("\n", $toolproxy->serviceoffered);

    return $tp;
}

/**
 * Update the database with a tool proxy instance
 *
 * @param object $config
 *        Tool proxy definition
 * @return int Record id number
 */
function qtype_lti_add_tool_proxy($config) {
    global $USER, $DB;

    $toolproxy = new \stdClass();
    if (isset($config->lti_registrationname)) {
        $toolproxy->name = trim($config->lti_registrationname);
    }
    if (isset($config->lti_registrationurl)) {
        $toolproxy->regurl = trim($config->lti_registrationurl);
    }
    if (isset($config->lti_capabilities)) {
        $toolproxy->capabilityoffered = implode("\n", $config->lti_capabilities);
    } else {
        $toolproxy->capabilityoffered = implode("\n", array_keys(lti_get_capabilities()));
    }
    if (isset($config->lti_services)) {
        $toolproxy->serviceoffered = implode("\n", $config->lti_services);
    } else {
        $func = function ($s) {
            return $s->get_id();
        };
        $servicenames = array_map($func, qtype_lti_get_services());
        $toolproxy->serviceoffered = implode("\n", $servicenames);
    }
    if (isset($config->toolproxyid) && !empty($config->toolproxyid)) {
        $toolproxy->id = $config->toolproxyid;
        if (!isset($toolproxy->state) || ($toolproxy->state != QTYPE_LTI_TOOL_PROXY_STATE_ACCEPTED)) {
            $toolproxy->state = QTYPE_LTI_TOOL_PROXY_STATE_CONFIGURED;
            $toolproxy->guid = random_string();
            $toolproxy->secret = random_string();
        }
        $id = qtype_lti_update_tool_proxy($toolproxy);
    } else {
        $toolproxy->state = QTYPE_LTI_TOOL_PROXY_STATE_CONFIGURED;
        $toolproxy->timemodified = time();
        $toolproxy->timecreated = $toolproxy->timemodified;
        if (!isset($toolproxy->createdby)) {
            $toolproxy->createdby = $USER->id;
        }
        $toolproxy->guid = random_string();
        $toolproxy->secret = random_string();
        $id = $DB->insert_record('qtype_lti_tool_proxies', $toolproxy);
    }

    return $id;
}

/**
 * Updates a tool proxy in the database
 *
 * @param object $toolproxy
 *        Tool proxy
 * @return int Record id number
 */
function qtype_lti_update_tool_proxy($toolproxy) {
    global $DB;

    $toolproxy->timemodified = time();
    $id = $DB->update_record('qtype_lti_tool_proxies', $toolproxy);

    return $id;
}

/**
 * Delete a Tool Proxy
 *
 * @param int $id
 *        Tool Proxy id
 */
function qtype_lti_delete_tool_proxy($id) {
    global $DB;
    $DB->delete_records('qtype_lti_tool_settings', array('toolproxyid' => $id));
    $tools = $DB->get_records('qtype_lti_types', array('toolproxyid' => $id));
    foreach ($tools as $tool) {
        qtype_lti_delete_type($tool->id);
    }
    $DB->delete_records('qtype_lti_tool_proxies', array('id' => $id));
}

/**
 * Add a tool configuration in the database
 *
 * @param object $config
 *        Tool configuration
 * @return int Record id number
 */
function qtype_lti_add_config($config) {
    global $DB;

    return $DB->insert_record('qtype_lti_types_config', $config);
}

/**
 * Updates a tool configuration in the database
 *
 * @param object $config
 *        Tool configuration
 * @return Record id number
 */
function qtype_lti_update_config($config) {
    global $DB;

    $return = true;
    $old = $DB->get_records('qtype_lti_types_config', array('typeid' => $config->typeid, 'name' => $config->name));

    if ($old) {
        foreach ($old as $o) {
            $config->id = $o->id;
            $return = $DB->update_record('qtype_lti_types_config', $config);
        }
    } else {
        $return = $DB->insert_record('qtype_lti_types_config', $config);
    }
    return $return;
}

/**
 * Gets the tool settings
 *
 * @param int $toolproxyid
 *        Id of tool proxy record
 * @param int $courseid
 *        Id of course (null if system settings)
 * @param int $instanceid
 *        Id of course module (null if system or context settings)
 * @return array Array settings
 */
function qtype_lti_get_tool_settings($toolproxyid, $courseid = null, $instanceid = null) {
    global $DB;

    $settings = array();
    $settingsstr = $DB->get_field('qtype_lti_tool_settings', 'settings',
                                array('toolproxyid' => $toolproxyid, 'course' => $courseid, 'questionid' => $instanceid));
    if ($settingsstr !== false) {
        $settings = json_decode($settingsstr, true);
    }
    return $settings;
}

/**
 * Sets the tool settings (
 *
 * @param array $settings
 *        Array of settings
 * @param int $toolproxyid
 *        Id of tool proxy record
 * @param int $courseid
 *        Id of course (null if system settings)
 * @param int $instanceid
 *        Id of course module (null if system or context settings)
 */
function qtype_lti_set_tool_settings($settings, $toolproxyid, $courseid = null, $instanceid = null) {
    global $DB;

    $json = json_encode($settings);
    $record = $DB->get_record('qtype_lti_tool_settings',
                            array('toolproxyid' => $toolproxyid, 'course' => $courseid, 'questionid' => $instanceid));
    if ($record !== false) {
        $DB->update_record('qtype_lti_tool_settings', array('id' => $record->id, 'settings' => $json, 'timemodified' => time()));
    } else {
        $record = new \stdClass();
        $record->toolproxyid = $toolproxyid;
        $record->course = $courseid;
        $record->questionid = $instanceid;
        $record->settings = $json;
        $record->timecreated = time();
        $record->timemodified = $record->timecreated;
        $DB->insert_record('qtype_lti_tool_settings', $record);
    }
}

/**
 * Signs the petition to launch the external tool using OAuth
 *
 * @param $oldparms Parameters
 *        to be passed for signing
 * @param $endpoint url
 *        of the external tool
 * @param $method Method
 *        for sending the parameters (e.g. POST)
 * @param $oauth_consumoer_key Key
 * @param $oauth_consumoer_secret Secret
 */
function qtype_lti_sign_parameters($oldparms, $endpoint, $method, $oauthconsumerkey, $oauthconsumersecret) {
    $parms = $oldparms;

    $testtoken = '';

    // TODO: Switch to core oauthlib once implemented - MDL-30149.
    $hmacmethod = new lti\OAuthSignatureMethod_HMAC_SHA1();
    $testconsumer = new lti\OAuthConsumer($oauthconsumerkey, $oauthconsumersecret, null);
    $accreq = lti\OAuthRequest::from_consumer_and_token($testconsumer, $testtoken, $method, $endpoint, $parms);
    $accreq->sign_request($hmacmethod, $testconsumer, $testtoken);

    $newparms = $accreq->get_parameters();

    return $newparms;
}

/**
 * Posts the launch petition HTML
 *
 * @param $newparms Signed
 *        parameters
 * @param $endpoint URL
 *        of the external tool
 * @param $debug Debug
 *        (true/false)
 */
function qtype_lti_post_launch_html($newparms, $endpoint, $debug = false) {
    $r = "<form action=\"" . $endpoint .
         "\" name=\"ltiLaunchForm\" id=\"ltiLaunchForm\" method=\"post\" encType=\"application/x-www-form-urlencoded\">\n";

    // Contruct html for the launch parameters.
    foreach ($newparms as $key => $value) {
        $key = htmlspecialchars($key);
        $value = htmlspecialchars($value);
        if ($key == "ext_submit") {
            $r .= "<input type=\"submit\"";
        } else {
            $r .= "<input type=\"hidden\" name=\"{$key}\"";
        }
        $r .= " value=\"";
        $r .= $value;
        $r .= "\"/>\n";
    }

    if ($debug) {
        $r .= "<script language=\"javascript\"> \n";
        $r .= "  //<![CDATA[ \n";
        $r .= "function basicltiDebugToggle() {\n";
        $r .= "    var ele = document.getElementById(\"basicltiDebug\");\n";
        $r .= "    if (ele.style.display == \"block\") {\n";
        $r .= "        ele.style.display = \"none\";\n";
        $r .= "    }\n";
        $r .= "    else {\n";
        $r .= "        ele.style.display = \"block\";\n";
        $r .= "    }\n";
        $r .= "} \n";
        $r .= "  //]]> \n";
        $r .= "</script>\n";
        $r .= "<a id=\"displayText\" href=\"javascript:basicltiDebugToggle();\">";
        $r .= get_string("toggle_debug_data", "lti") . "</a>\n";
        $r .= "<div id=\"basicltiDebug\" style=\"display:none\">\n";
        $r .= "<b>" . get_string("basiclti_endpoint", "lti") . "</b><br/>\n";
        $r .= $endpoint . "<br/>\n&nbsp;<br/>\n";
        $r .= "<b>" . get_string("basiclti_parameters", "lti") . "</b><br/>\n";
        foreach ($newparms as $key => $value) {
            $key = htmlspecialchars($key);
            $value = htmlspecialchars($value);
            $r .= "$key = $value<br/>\n";
        }
        $r .= "&nbsp;<br/>\n";
        $r .= "</div>\n";
    }
    $r .= "</form>\n";

    if (!$debug) {
        $r .= " <script type=\"text/javascript\"> \n" .
              "  //<![CDATA[ \n" . "    document.ltiLaunchForm.submit(); \n" . "  //]]> \n" .
              " </script> \n";
    }
    return $r;
}

function qtype_lti_get_type($typeid) {
    global $DB;

    return $DB->get_record('qtype_lti_types', array('id' => $typeid));
}

function qtype_lti_get_launch_container($lti, $toolconfig) {
    if (empty($lti->launchcontainer)) {
        $lti->launchcontainer = QTYPE_LTI_LAUNCH_CONTAINER_DEFAULT;
    }

    if ($lti->launchcontainer == QTYPE_LTI_LAUNCH_CONTAINER_DEFAULT) {
        if (isset($toolconfig['launchcontainer'])) {
            $launchcontainer = $toolconfig['launchcontainer'];
        }
    } else {
        $launchcontainer = $lti->launchcontainer;
    }

    if (empty($launchcontainer) || $launchcontainer == QTYPE_LTI_LAUNCH_CONTAINER_DEFAULT) {
        $launchcontainer = QTYPE_LTI_LAUNCH_CONTAINER_EMBED_NO_BLOCKS;
    }

    $devicetype = core_useragent::get_device_type();

    // Scrolling within the object element doesn't work on iOS or Android
    // Opening the popup window also had some issues in testing
    // For mobile devices, always take up the entire screen to ensure the best experience.
    if ($devicetype === core_useragent::DEVICETYPE_MOBILE || $devicetype === core_useragent::DEVICETYPE_TABLET) {
        $launchcontainer = QTYPE_LTI_LAUNCH_CONTAINER_REPLACE_MOODLE_WINDOW;
    }

    return $launchcontainer;
}

function qtype_lti_request_is_using_ssl() {
    global $CFG;
    return (stripos($CFG->wwwroot, 'https://') === 0);
}

function qtype_lti_ensure_url_is_https($url) {
    if (!strstr($url, '://')) {
        $url = 'https://' . $url;
    } else {
        // If the URL starts with http, replace with https.
        if (stripos($url, 'http://') === 0) {
            $url = 'https://' . substr($url, 7);
        }
    }

    return $url;
}

/**
 * Determines if we should try to log the request
 *
 * @param string $rawbody
 * @return bool
 */
function qtype_lti_should_log_request($rawbody) {
    global $CFG;
    if (empty($CFG->qtype_lti_log_users)) {
        return false;
    }

    $logusers = explode(',', $CFG->qtype_lti_log_users);
    if (empty($logusers)) {
        return false;
    }

    try {
        $xml = new \SimpleXMLElement($rawbody);
        $ns = $xml->getNamespaces();
        $ns = array_shift($ns);
        $xml->registerXPathNamespace('qtype_lti', $ns);
        $requestuserid = '';
        if ($node = $xml->xpath('//lti:userId')) {
            $node = $node[0];
            $requestuserid = clean_param((string)$node, PARAM_INT);
        } else if ($node = $xml->xpath('//lti:sourcedId')) {
            $node = $node[0];
            $resultjson = json_decode((string)$node);
            $requestuserid = clean_param($resultjson->data->userid, PARAM_INT);
        }
    } catch (Exception $e) {
        return false;
    }

    if (empty($requestuserid) or !in_array($requestuserid, $logusers)) {
        return false;
    }

    return true;
}

/**
 * Logs the request to a file in temp dir.
 *
 * @param string $rawbody
 */
function qtype_lti_log_request($rawbody) {
    if ($tempdir = make_temp_directory('qtype_lti', false)) {
        if ($tempfile = tempnam($tempdir, 'qtype_lti_request' . date('YmdHis'))) {
            $content = "Request Headers:\n";
            foreach (moodle\qtype\lti\OAuthUtil::get_headers() as $header => $value) {
                $content .= "$header: $value\n";
            }
            $content .= "Request Body:\n";
            $content .= $rawbody;

            file_put_contents($tempfile, $content);
            chmod($tempfile, 0644);
        }
    }
}

/**
 * Log an LTI response.
 *
 * @param string $responsexml
 *        The response XML
 * @param Exception $e
 *        If there was an exception, pass that too
 */
function qtype_lti_log_response($responsexml, $e = null) {
    if ($tempdir = make_temp_directory('qtype_lti', false)) {
        if ($tempfile = tempnam($tempdir, 'qtype_lti_response' . date('YmdHis'))) {
            $content = '';
            if ($e instanceof Exception) {
                $info = get_exception_info($e);

                $content .= "Exception:\n";
                $content .= "Message: $info->message\n";
                $content .= "Debug info: $info->debuginfo\n";
                $content .= "Backtrace:\n";
                $content .= format_backtrace($info->backtrace, true);
                $content .= "\n";
            }
            $content .= "Response XML:\n";
            $content .= $responsexml;

            file_put_contents($tempfile, $content);
            chmod($tempfile, 0644);
        }
    }
}

/**
 * Fetches LTI type configuration for an LTI instance
 *
 * @param stdClass $instance
 * @return array Can be empty if no type is found
 */
function qtype_lti_get_type_config_by_instance($instance) {
    $typeid = null;
    if (empty($instance->typeid)) {
        $tool = qtype_lti_get_tool_by_url_match($instance->toolurl, $instance->course);
        if ($tool) {
            $typeid = $tool->id;
        }
    } else {
        $typeid = $instance->typeid;
    }
    if (!empty($typeid)) {
        return qtype_lti_get_type_config($typeid);
    }
    return array();
}

/**
 * Enforce type config settings onto the LTI instance
 *
 * @param stdClass $instance
 * @param array $typeconfig
 */
function qtype_lti_force_type_config_settings($instance, array $typeconfig) {
    $forced = array('instructorchoicesendname' => 'sendname', 'instructorchoicesendemailaddr' => 'sendemailaddr',
        'instructorchoiceacceptgrades' => 'acceptgrades');

    foreach ($forced as $instanceparam => $typeconfigparam) {
        if (array_key_exists($typeconfigparam, $typeconfig) && $typeconfig[$typeconfigparam] != QTYPE_LTI_SETTING_DELEGATE) {
            $instance->$instanceparam = $typeconfig[$typeconfigparam];
        }
    }
}

/**
 * Initializes an array with the capabilities supported by the LTI module
 *
 * @return array List of capability names (without a dollar sign prefix)
 */
function qtype_lti_get_capabilities() {
    $capabilities = array('basic-lti-launch-request' => '', 'ContentItemSelectionRequest' => '',
        'ToolProxyRegistrationRequest' => '', 'Context.id' => 'context_id', 'Context.title' => 'context_title',
        'Context.label' => 'context_label', 'Context.sourcedId' => 'lis_course_section_sourcedid',
        'Context.longDescription' => '$COURSE->summary', 'Context.timeFrame.begin' => '$COURSE->startdate',
        'CourseSection.title' => 'context_title', 'CourseSection.label' => 'context_label',
        'CourseSection.sourcedId' => 'lis_course_section_sourcedid', 'CourseSection.longDescription' => '$COURSE->summary',
        'CourseSection.timeFrame.begin' => '$COURSE->startdate', 'ResourceLink.id' => 'resource_link_id',
        'ResourceLink.title' => 'resource_link_title', 'ResourceLink.description' => 'resource_link_description',
        'User.id' => 'user_id', 'User.username' => '$USER->username', 'Person.name.full' => 'lis_person_name_full',
        'Person.name.given' => 'lis_person_name_given', 'Person.name.family' => 'lis_person_name_family',
        'Person.email.primary' => 'lis_person_contact_email_primary', 'Person.sourcedId' => 'lis_person_sourcedid',
        'Person.name.middle' => '$USER->middlename', 'Person.address.street1' => '$USER->address',
        'Person.address.locality' => '$USER->city', 'Person.address.country' => '$USER->country',
        'Person.address.timezone' => '$USER->timezone', 'Person.phone.primary' => '$USER->phone1',
        'Person.phone.mobile' => '$USER->phone2', 'Person.webaddress' => '$USER->url', 'Membership.role' => 'roles',
        'Result.sourcedId' => 'lis_result_sourcedid', 'Result.autocreate' => 'lis_outcome_service_url',
        'Moodle.Person.userGroupIds' => null);

    return $capabilities;
}

/**
 * Initializes an array with the services supported by the LTI module
 *
 * @return array List of services
 */
function qtype_lti_get_services() {
    $services = array();
    $definedservices = core_component::get_plugin_list('ltiservice');
    foreach ($definedservices as $name => $location) {
        $classname = "\\ltiservice_{$name}\\local\\service\\{$name}";
        $services[] = new $classname();
    }

    return $services;
}

/**
 * Initializes an instance of the named service
 *
 * @param string $servicename
 *        Name of service
 * @return qtype_lti\local\ltiservice\service_base Service
 */
function qtype_lti_get_service_by_name($servicename) {
    $service = false;
    $classname = "\\ltiservice_{$servicename}\\local\\service\\{$servicename}";
    if (class_exists($classname)) {
        $service = new $classname();
    }

    return $service;
}

/**
 * Finds a service by id
 *
 * @param array $services
 *        Array of services
 * @param string $resourceid
 *        ID of resource
 * @return qtype_lti\local\ltiservice\service_base Service
 */
function qtype_lti_get_service_by_resource_id($services, $resourceid) {
    $service = false;
    foreach ($services as $aservice) {
        foreach ($aservice->get_resources() as $resource) {
            if ($resource->get_id() === $resourceid) {
                $service = $aservice;
                break 2;
            }
        }
    }

    return $service;
}

/**
 * Extracts the named contexts from a tool proxy
 *
 * @param object $json
 *
 * @return array Contexts
 */
function qtype_lti_get_contexts($json) {
    $contexts = array();
    if (isset($json->{'@context'})) {
        foreach ($json->{'@context'} as $context) {
            if (is_object($context)) {
                $contexts = array_merge(get_object_vars($context), $contexts);
            }
        }
    }

    return $contexts;
}

/**
 * Converts an ID to a fully-qualified ID
 *
 * @param array $contexts
 * @param string $id
 *
 * @return string Fully-qualified ID
 */
function qtype_lti_get_fqid($contexts, $id) {
    $parts = explode(':', $id, 2);
    if (count($parts) > 1) {
        if (array_key_exists($parts[0], $contexts)) {
            $id = $contexts[$parts[0]] . $parts[1];
        }
    }

    return $id;
}

/**
 * Returns the icon for the given tool type
 *
 * @param stdClass $type
 *        The tool type
 * @return string The url to the tool type's corresponding icon
 */
function qtype_get_tool_type_icon_url(stdClass $type) {
    global $OUTPUT;

    $iconurl = $type->secureicon;

    if (empty($iconurl)) {
        $iconurl = $type->icon;
    }

    if (empty($iconurl)) {
        $iconurl = $OUTPUT->image_url('icon', 'qtype_lti')->out();
    }

    return $iconurl;
}

/**
 * Returns the edit url for the given tool type
 *
 * @param stdClass $type
 *        The tool type
 * @return string The url to edit the tool type
 */
function qtype_get_tool_type_edit_url(stdClass $type) {
    $url = new moodle_url('/question/type/lti/typessettings.php',
                        array('action' => 'update', 'id' => $type->id, 'sesskey' => sesskey(), 'returnto' => 'toolconfigure'));
    return $url->out();
}

/**
 * Returns the edit url for the given tool proxy.
 *
 * @param stdClass $proxy
 *        The tool proxy
 * @return string The url to edit the tool type
 */
function qtype_get_tool_proxy_edit_url(stdClass $proxy) {
    $url = new moodle_url('/question/type/lti/registersettings.php',
                        array('action' => 'update', 'id' => $proxy->id, 'sesskey' => sesskey(), 'returnto' => 'toolconfigure'));
    return $url->out();
}

/**
 * Returns the course url for the given tool type
 *
 * @param stdClass $type
 *        The tool type
 * @return string|void The url to the course of the tool type, void if it is a site wide type
 */
function qtype_get_tool_type_course_url(stdClass $type) {
    if ($type->course == 1) {
        return;
    } else {
        $url = new moodle_url('/course/view.php', array('id' => $type->course));
        return $url->out();
    }
}

/**
 * Returns the icon and edit urls for the tool type and the course url if it is a course type.
 *
 * @param stdClass $type
 *        The tool type
 * @return string The urls of the tool type
 */
function qtype_get_tool_type_urls(stdClass $type) {
    $courseurl = qtype_get_tool_type_course_url($type);

    $urls = array('icon' => qtype_get_tool_type_icon_url($type), 'edit' => qtype_get_tool_type_edit_url($type));

    if ($courseurl) {
        $urls['course'] = $courseurl;
    }

    return $urls;
}

/**
 * Returns the icon and edit urls for the tool proxy.
 *
 * @param stdClass $proxy
 *        The tool proxy
 * @return string The urls of the tool proxy
 */
function qtype_get_tool_proxy_urls(stdClass $proxy) {
    global $OUTPUT;

    $urls = array('icon' => $OUTPUT->image_url('icon', 'qtype_lti')->out(), 'edit' => qtype_get_tool_proxy_edit_url($proxy));

    return $urls;
}

/**
 * Returns information on the current state of the tool type
 *
 * @param stdClass $type
 *        The tool type
 * @return array An array with a text description of the state, and boolean for whether it is in each state:
 *         pending, configured, rejected, unknown
 */
function qtype_get_tool_type_state_info(stdClass $type) {
    $state = '';
    $isconfigured = false;
    $ispending = false;
    $isrejected = false;
    $isunknown = false;
    switch ($type->state) {
        case QTYPE_LTI_TOOL_STATE_CONFIGURED:
            $state = get_string('active', 'qtype_lti');
            $isconfigured = true;
        break;
        case QTYPE_LTI_TOOL_STATE_PENDING:
            $state = get_string('pending', 'qtype_lti');
            $ispending = true;
        break;
        case QTYPE_LTI_TOOL_STATE_REJECTED:
            $state = get_string('rejected', 'qtype_lti');
            $isrejected = true;
        break;
        default:
            $state = get_string('unknownstate', 'qtype_lti');
            $isunknown = true;
        break;
    }

    return array('text' => $state, 'pending' => $ispending, 'configured' => $isconfigured, 'rejected' => $isrejected,
        'unknown' => $isunknown);
}

/**
 * Returns a summary of each LTI capability this tool type requires in plain language
 *
 * @param stdClass $type
 *        The tool type
 * @return array An array of text descriptions of each of the capabilities this tool type requires
 */
function qtype_get_tool_type_capability_groups($type) {
    $capabilities = qtype_lti_get_enabled_capabilities($type);
    $groups = array();
    $hascourse = false;
    $hasactivities = false;
    $hasuseraccount = false;
    $hasuserpersonal = false;

    foreach ($capabilities as $capability) {
        // Bail out early if we've already found all groups.
        if (count($groups) >= 4) {
            continue;
        }

        if (!$hascourse && preg_match('/^CourseSection/', $capability)) {
            $hascourse = true;
            $groups[] = get_string('courseinformation', 'qtype_lti');
        } else if (!$hasactivities && preg_match('/^ResourceLink/', $capability)) {
            $hasactivities = true;
            $groups[] = get_string('courseactivitiesorresources', 'qtype_lti');
        } else if (!$hasuseraccount && preg_match('/^User/', $capability) || preg_match('/^Membership/', $capability)) {
            $hasuseraccount = true;
            $groups[] = get_string('useraccountinformation', 'qtype_lti');
        } else if (!$hasuserpersonal && preg_match('/^Person/', $capability)) {
            $hasuserpersonal = true;
            $groups[] = get_string('userpersonalinformation', 'qtype_lti');
        }
    }

    return $groups;
}

/**
 * Returns the ids of each instance of this tool type
 *
 * @param stdClass $type
 *        The tool type
 * @return array An array of ids of the instances of this tool type
 */
function qtype_get_tool_type_instance_ids($type) {
    global $DB;

    return array_keys($DB->get_fieldset_select('qtype_lti_options', 'id', 'typeid = ?', array($type->id)));
}

/**
 * Serialises this tool type
 *
 * @param stdClass $type
 *        The tool type
 * @return array An array of values representing this type
 */
function qtype_serialise_tool_type(stdClass $type) {
    $capabilitygroups = qtype_get_tool_type_capability_groups($type);
    $instanceids = qtype_get_tool_type_instance_ids($type);
    // Clean the name. We don't want tags here.
    $name = clean_param($type->name, PARAM_NOTAGS);
    if (!empty($type->description)) {
        // Clean the description. We don't want tags here.
        $description = clean_param($type->description, PARAM_NOTAGS);
    } else {
        $description = get_string('editdescription', 'qtype_lti');
    }
    return array('id' => $type->id, 'name' => $name, 'description' => $description, 'urls' => qtype_get_tool_type_urls($type),
        'state' => qtype_get_tool_type_state_info($type), 'hascapabilitygroups' => !empty($capabilitygroups),
        'capabilitygroups' => $capabilitygroups,
        'courseid' => $type->course == 1 ? 0 : $type->course,
        'instanceids' => $instanceids, 'instancecount' => count($instanceids));
}

/**
 * Serialises this tool proxy.
 *
 * @param stdClass $proxy
 *        The tool proxy
 * @return array An array of values representing this type
 */
function qtype_serialise_tool_proxy(stdClass $proxy) {
    return array('id' => $proxy->id, 'name' => $proxy->name, 'description' => get_string('activatetoadddescription', 'qtype_lti'),
        'urls' => qtype_get_tool_proxy_urls($proxy),
        'state' => array('text' => get_string('pending', 'qtype_lti'), 'pending' => true, 'configured' => false,
            'rejected' => false, 'unknown' => false), 'hascapabilitygroups' => true, 'capabilitygroups' => array(), 'courseid' => 0,
        'instanceids' => array(), 'instancecount' => 0);
}

/**
 * Loads the cartridge information into the tool type, if the launch url is for a cartridge file
 *
 * @param stdClass $type
 *        The tool type object to be filled in
 * @since Moodle 3.1
 */
function qtype_lti_load_type_if_cartridge($type) {
    if (!empty($type->lti_toolurl) && qtype_lti_is_cartridge($type->lti_toolurl)) {
        qtype_lti_load_type_from_cartridge($type->lti_toolurl, $type);
    }
}

/**
 * Loads the cartridge information into the new tool, if the launch url is for a cartridge file
 *
 * @param stdClass $lti
 *        The tools config
 * @since Moodle 3.1
 */
function qtype_lti_load_tool_if_cartridge($lti) {
    if (!empty($lti->toolurl) && qtype_lti_is_cartridge($lti->toolurl)) {
        qtype_lti_load_tool_from_cartridge($lti->toolurl, $lti);
    }
}

/**
 * Determines if the given url is for a IMS basic cartridge
 *
 * @param string $url
 *        The url to be checked
 * @return True if the url is for a cartridge
 * @since Moodle 3.1
 */
function qtype_lti_is_cartridge($url) {
    // If it is empty, it's not a cartridge.
    if (empty($url)) {
        return false;
    }
    // If it has xml at the end of the url, it's a cartridge.
    if (preg_match('/\.xml$/', $url)) {
        return true;
    }
    // Even if it doesn't have .xml, load the url to check if it's a cartridge..
    try {
        $toolinfo = qtype_lti_load_cartridge($url, array("launch_url" => "launchurl"));
        if (!empty($toolinfo['launchurl'])) {
            return true;
        }
    } catch (moodle_exception $e) {
        return false; // Error loading the xml, so it's not a cartridge.
    }
    return false;
}

/**
 * Allows you to load settings for an external tool type from an IMS cartridge.
 *
 * @param string $url
 *        The URL to the cartridge
 * @param stdClass $type
 *        The tool type object to be filled in
 * @throws moodle_exception if the cartridge could not be loaded correctly
 * @since Moodle 3.1
 */
function qtype_lti_load_type_from_cartridge($url, $type) {
    $toolinfo = qtype_lti_load_cartridge($url,
                                        array("title" => "lti_typename", "launch_url" => "lti_toolurl",
                                            "description" => "lti_description", "icon" => "lti_icon",
                                            "secure_icon" => "lti_secureicon"),
                                        array("icon_url" => "lti_extension_icon", "secure_icon_url" => "lti_extension_secureicon"));
    // If an activity name exists, unset the cartridge name so we don't override it.
    if (isset($type->lti_typename)) {
        unset($toolinfo['lti_typename']);
    }

    // Always prefer cartridge core icons first, then, if none are found, look at the extension icons.
    if (empty($toolinfo['lti_icon']) && !empty($toolinfo['lti_extension_icon'])) {
        $toolinfo['lti_icon'] = $toolinfo['lti_extension_icon'];
    }
    unset($toolinfo['lti_extension_icon']);

    if (empty($toolinfo['lti_secureicon']) && !empty($toolinfo['lti_extension_secureicon'])) {
        $toolinfo['lti_secureicon'] = $toolinfo['lti_extension_secureicon'];
    }
    unset($toolinfo['lti_extension_secureicon']);

    foreach ($toolinfo as $property => $value) {
        $type->$property = $value;
    }
}

/**
 * Allows you to load in the configuration for an external tool from an IMS cartridge.
 *
 * @param string $url
 *        The URL to the cartridge
 * @param stdClass $lti
 *        LTI object
 * @throws moodle_exception if the cartridge could not be loaded correctly
 * @since Moodle 3.1
 */
function qtype_lti_load_tool_from_cartridge($url, $lti) {
    $toolinfo = qtype_lti_load_cartridge($url,
                                        array("title" => "name", "launch_url" => "toolurl", "secure_launch_url" => "securetoolurl",
                                            "description" => "intro", "icon" => "icon", "secure_icon" => "secureicon"),
                                        array("icon_url" => "extension_icon", "secure_icon_url" => "extension_secureicon"));
    // If an activity name exists, unset the cartridge name so we don't override it.
    if (isset($lti->name)) {
        unset($toolinfo['name']);
    }

    // Always prefer cartridge core icons first, then, if none are found, look at the extension icons.
    if (empty($toolinfo['icon']) && !empty($toolinfo['extension_icon'])) {
        $toolinfo['icon'] = $toolinfo['extension_icon'];
    }
    unset($toolinfo['extension_icon']);

    if (empty($toolinfo['secureicon']) && !empty($toolinfo['extension_secureicon'])) {
        $toolinfo['secureicon'] = $toolinfo['extension_secureicon'];
    }
    unset($toolinfo['extension_secureicon']);

    foreach ($toolinfo as $property => $value) {
        $lti->$property = $value;
    }
}

/**
 * Search for a tag within an XML DOMDocument
 *
 * @param string $url
 *        The url of the cartridge to be loaded
 * @param array $map
 *        The map of tags to keys in the return array
 * @param array $propertiesmap
 *        The map of properties to keys in the return array
 * @return array An associative array with the given keys and their values from the cartridge
 * @throws moodle_exception if the cartridge could not be loaded correctly
 * @since Moodle 3.1
 */
function qtype_lti_load_cartridge($url, $map, $propertiesmap = array()) {
    global $CFG;
    require_once($CFG->libdir . "/filelib.php");

    $curl = new curl();
    $response = $curl->get($url);

    // TODO MDL-46023 Replace this code with a call to the new library.
    $origerrors = libxml_use_internal_errors(true);
    $origentity = libxml_disable_entity_loader(true);
    libxml_clear_errors();

    $document = new DOMDocument();
    @$document->loadXML($response, LIBXML_DTDLOAD | LIBXML_DTDATTR);

    $cartridge = new DomXpath($document);

    $errors = libxml_get_errors();

    libxml_clear_errors();
    libxml_use_internal_errors($origerrors);
    libxml_disable_entity_loader($origentity);

    if (count($errors) > 0) {
        $message = 'Failed to load cartridge.';
        foreach ($errors as $error) {
            $message .= "\n" . trim($error->message, "\n\r\t .") . " at line " . $error->line;
        }
        throw new moodle_exception('errorreadingfile', '', '', $url, $message);
    }

    $toolinfo = array();
    foreach ($map as $tag => $key) {
        $value = qtype_get_tag($tag, $cartridge);
        if ($value) {
            $toolinfo[$key] = $value;
        }
    }
    if (!empty($propertiesmap)) {
        foreach ($propertiesmap as $property => $key) {
            $value = qtype_get_tag("property", $cartridge, $property);
            if ($value) {
                $toolinfo[$key] = $value;
            }
        }
    }

    return $toolinfo;
}

/**
 * Search for a tag within an XML DOMDocument
 *
 * @param stdClass $tagname
 *        The name of the tag to search for
 * @param XPath $xpath
 *        The XML to find the tag in
 * @param XPath $attribute
 *        The attribute to search for (if we should search for a child node with the given
 *        value for the name attribute
 * @since Moodle 3.1
 */
function qtype_get_tag($tagname, $xpath, $attribute = null) {
    if ($attribute) {
        $result = $xpath->query('//*[local-name() = \'' . $tagname . '\'][@name="' . $attribute . '"]');
    } else {
        $result = $xpath->query('//*[local-name() = \'' . $tagname . '\']');
    }
    if ($result->length > 0) {
        return $result->item(0)->nodeValue;
    }
    return null;
}

/**
 * Checks if user can add tool.
 * Throws a capability error otherwise.
 *
 * @param object $lti
 * @return void
 * @throws moodle_exception
 */
function qtype_lti_ensure_user_can_use_type($lti) {
    global $DB, $SITE, $COURSE;

    if ($lti->course == 1) {
        $lti->course = $COURSE->id;
    }

    if (empty($lti->typeid)) {
        // Not a global tool.
        require_capability('qtype/lti:adddefaultinstance', context_course::instance($lti->course));
        return;
    }

    $toolcourseid = $DB->get_field('qtype_lti_types', 'course', ['id' => $lti->typeid], MUST_EXIST);
    if ($toolcourseid != $SITE->id) {
        // Not a global tool.
        require_capability('qtype/lti:adddefaultinstance', context_course::instance($lti->course));
        return;
    }

    // Global tool. Always allow.
    require_capability('qtype/lti:addgloballypreconfigedtoolinstance', context_course::instance($lti->course));
}
