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
 * Utility code for LTI service handling.
 *
 * @package qtype_lti
 * @copyright Copyright 2019 ETH Zurich
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/question/type/lti/OAuthBody.php');
require_once($CFG->dirroot . '/question/type/lti/locallib.php');

// TODO: Switch to core oauthlib once implemented - MDL-30149.
use moodle\qtype\lti as lti;

define('QTYPE_LTI_ITEM_TYPE', 'qtype');
define('QTYPE_LTI_ITEM_MODULE', 'lti');
define('QTYPE_LTI_SOURCE', 'qtype/lti');

function qtype_lti_get_response_xml($codemajor, $description, $messageref, $messagetype) {
    $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><imsx_POXEnvelopeResponse />');
    $xml->addAttribute('xmlns', 'http://www.imsglobal.org/services/ltiv1p1/xsd/imsoms_v1p0');

    $headerinfo = $xml->addChild('imsx_POXHeader')->addChild('imsx_POXResponseHeaderInfo');

    $headerinfo->addChild('imsx_version', 'V1.0');
    $headerinfo->addChild('imsx_messageIdentifier', (string)mt_rand());

    $statusinfo = $headerinfo->addChild('imsx_statusInfo');
    $statusinfo->addchild('imsx_codeMajor', $codemajor);
    $statusinfo->addChild('imsx_severity', 'status');
    $statusinfo->addChild('imsx_description', $description);
    $statusinfo->addChild('imsx_messageRefIdentifier', $messageref);
    $incomingtype = str_replace('Response', 'Request', $messagetype);
    $statusinfo->addChild('imsx_operationRefIdentifier', $incomingtype);

    $xml->addChild('imsx_POXBody')->addChild($messagetype);

    return $xml;
}

function qtype_lti_parse_message_id($xml) {
    if (empty($xml->imsx_POXHeader)) {
        return '';
    }

    $node = $xml->imsx_POXHeader->imsx_POXRequestHeaderInfo->imsx_messageIdentifier;
    $messageid = (string)$node;

    return $messageid;
}

function qtype_lti_parse_grade_replace_message($xml) {
    $node = $xml->imsx_POXBody->replaceResultRequest->resultRecord->sourcedGUID->sourcedId;
    $resultjson = json_decode((string)$node);

    $node = $xml->imsx_POXBody->replaceResultRequest->resultRecord->result->resultScore->textString;

    $score = (string)$node;
    if (!is_numeric($score)) {
        throw new Exception('Score must be numeric');
    }
    $grade = floatval($score);
    if ($grade < 0.0 || $grade > 1.0) {
        throw new Exception('Score not between 0.0 and 1.0');
    }

    $parsed = new stdClass();
    $parsed->gradeval = $grade;

    $parsed->instanceid = $resultjson->data->instance;
    $parsed->userid = $resultjson->data->username;
    $parsed->attemptid = $resultjson->data->attemptid;
    $parsed->typeid = $resultjson->data->typeid;
    $parsed->ltiid = $resultjson->data->ltiid;
    $parsed->username = $parsed->userid;
    $pieces = explode('-', $parsed->instanceid);
    $parsed->resultid = $pieces[0].'-'.$pieces[1].'-'.$pieces[2];
    $parsed->linkid = $parsed->instanceid;


    $parsed->sourcedidhash = $resultjson->hash;
    $parsed->messageid = qtype_lti_parse_message_id($xml);

    return $parsed;
}

function qtype_lti_parse_grade_read_message($xml) {
    $node = $xml->imsx_POXBody->readResultRequest->resultRecord->sourcedGUID->sourcedId;
    $resultjson = json_decode((string)$node);

    $parsed = new stdClass();
    $parsed->instanceid = $resultjson->data->instance;
    $parsed->userid = $resultjson->data->username;
    $parsed->attemptid = $resultjson->data->attemptid;
    $parsed->typeid = $resultjson->data->typeid;
    $parsed->ltiid = $resultjson->data->ltiid;
    $parsed->username = $parsed->userid;
    $pieces = explode('-', $parsed->instanceid);
    $parsed->resultid = $pieces[0].'-'.$pieces[1].'-'.$pieces[2];
    $parsed->linkid = $parsed->instanceid;

    $parsed->sourcedidhash = $resultjson->hash;
    $parsed->messageid = qtype_lti_parse_message_id($xml);

    return $parsed;
}

function qtype_lti_parse_grade_delete_message($xml) {
    $node = $xml->imsx_POXBody->deleteResultRequest->resultRecord->sourcedGUID->sourcedId;
    $resultjson = json_decode((string)$node);

    $parsed = new stdClass();
    $parsed->instanceid = $resultjson->data->instance;
    $parsed->userid = $resultjson->data->username;
    $parsed->attemptid = $resultjson->data->attemptid;
    $parsed->typeid = $resultjson->data->typeid;
    $parsed->ltiid = $resultjson->data->ltiid;
    $parsed->username = $parsed->userid;
    $pieces = explode('-', $parsed->instanceid);
    $parsed->resultid = $pieces[0].'-'.$pieces[1].'-'.$pieces[2];
    $parsed->linkid = $parsed->instanceid;

    $parsed->sourcedidhash = $resultjson->hash;
    $parsed->messageid = qtype_lti_parse_message_id($xml);

    return $parsed;
}

function qtype_lti_accepts_grades($ltiinstance) {
    global $DB;
    return true; // Always true!.
}

/**
 * Set the passed user ID to the session user.
 *
 * @param int $userid
 */
function qtype_lti_set_session_user($username) {
    global $DB;

    if ($user = $DB->get_record('user', array('username' => $username))) {
        \core\session\manager::set_user($user);
    }
}

function qtype_lti_update_grade($username, $linkid, $resultid, $gradeval, $ltiid) {
    global $CFG, $DB;
    require_once($CFG->libdir . '/gradelib.php');

    $params = array();
    $params['itemname'] = $ltiinstance->name;

    $grade = new stdClass();
    $grade->userid = $userid;
    $grade->rawgrade = $gradeval;

    $record = $DB->get_record('qtype_lti_submission', array('username' => $username, 'linkid' => $linkid, 'resultid' => $resultid),
                    'id');
    if ($record) {
        $id = $record->id;
    } else {
        $id = null;
    }

    if (!empty($id)) {
        $DB->update_record('qtype_lti_submission',
                        array('id' => $id, 'dateupdated' => time(), 'gradepercent' => $gradeval, 'state' => 2));
    } else {
        $DB->insert_record('qtype_lti_submission',
                        array('ltiid' => $ltiid, 'username' => $username, 'linkid' => $linkid, 'resultid' => $resultid, 'datesubmitted' => time(),
                            'dateupdated' => time(), 'gradepercent' => $gradeval, 'originalgrade' => $gradeval, 'state' => 1));
    }

    return $status == GRADE_UPDATE_OK;
}

function qtype_lti_read_grade($username, $linkid, $resultid) {
    global $CFG, $DB;
    require_once($CFG->libdir . '/gradelib.php');

    $ltigrade = floatval($ltiinstance->grade);

    $submissiongrade = $DB->get_record('qtype_lti_submission',
                    array('username' => $username, 'linkid' => $linkid, 'resultid' => $resultid));

    if ($submissiongrade) {

        return $submissiongrade->gradepercent;
    }
    return null;
}

function qtype_lti_delete_grade($username, $linkid, $resultid) {
    global $CFG, $DB;

    $record = $DB->get_record('qtype_lti_submission', array('username' => $username, 'linkid' => $linkid, 'resultid' => $resultid),
                    'id');

    if ($record) {
        $status = $DB->delete_records('qtype_lti_submission', array('id' => $record->id));
    }

    return $status == GRADE_UPDATE_OK;
}

function qtype_lti_verify_message($key, $sharedsecrets, $body, $headers = null) {
    foreach ($sharedsecrets as $secret) {
        $signaturefailed = false;
        try {
            // TODO: Switch to core oauthlib once implemented - MDL-30149.
            lti\handle_oauth_body_post($key, $secret, $body, $headers);
        } catch (Exception $e) {
            debugging('LTI message verification failed: ' . $e->getMessage());
            $signaturefailed = true;
        }

        if (!$signaturefailed) {
            return $secret; // Return the secret used to sign the message).
        }
    }

    return false;
}

/**
 * Validate source ID from external request
 *
 * @param object $ltiinstance
 * @param object $parsed
 * @throws Exception
 */
function qtype_lti_verify_sourcedid($ltiinstance, $parsed) {
    $sourceid = qtype_lti_build_sourcedid($parsed->instanceid, $parsed->userid, $ltiinstance->servicesalt, $parsed->typeid,
                    $parsed->attemptid, $parsed->ltiid);

    if ($sourceid->hash != $parsed->sourcedidhash) {
        throw new Exception($parsed->instanceid . ': SourcedId hash not valid');
    }
}

/**
 * Extend the LTI services through the ltisource plugins
 *
 * @param stdClass $data
 *        LTI request data
 * @return bool
 * @throws coding_exception
 */
function qtype_lti_extend_lti_services($data) {
    $plugins = get_plugin_list_with_function('ltisource', $data->messagetype);
    if (!empty($plugins)) {
        // There can only be one.
        if (count($plugins) > 1) {
            throw new coding_exception('More than one ltisource plugin handler found');
        }
        $data->xml = new SimpleXMLElement($data->body);
        $callback = current($plugins);
        call_user_func($callback, $data);

        return true;
    }
    return false;
}
