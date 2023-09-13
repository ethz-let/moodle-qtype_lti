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
 * LTI web service endpoints
 *
 * @package qtype_lti
 * @copyright Copyright (c) 2019 ETH Zurich
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define('NO_DEBUG_DISPLAY', true);
define('NO_MOODLE_COOKIES', true);

require_once(__DIR__ . "/../../../config.php");
require_once($CFG->dirroot . '/question/type/lti/locallib.php');
require_once($CFG->dirroot . '/question/type/lti/servicelib.php');

/**
 * Handles exceptions when handling incoming LTI messages.
 * Ensures that LTI always returns a XML message that can be consumed by the caller.
 *
 * @package qtype_lti
 * @copyright Copyright 2019 ETH Zurich
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_lti_service_exception_handler {
    /**
     * Enable error response logging.
     *
     * @var bool
     */
    protected $log = false;

    /**
     * The LTI service message ID, if known.
     *
     * @var string
     */
    protected $id = '';

    /**
     * The LTI service message type, if known.
     *
     * @var string
     */
    protected $type = 'unknownRequest';

    /**
     * Constructor.
     *
     * @param boolean $log
     *        Enable error response logging.
     */
    public function __construct($log) {
        $this->log = $log;
    }

    /**
     * Set the LTI message ID being handled.
     *
     * @param string $id
     */
    public function set_message_id($id) {
        if (!empty($id)) {
            $this->id = $id;
        }
    }

    /**
     * Set the LTI message type being handled.
     *
     * @param string $type
     */
    public function set_message_type($type) {
        if (!empty($type)) {
            $this->type = $type;
        }
    }

    /**
     * Echo an exception message encapsulated in XML.
     *
     * @param \Exception|\Throwable $exception
     *        The exception that was thrown
     */
    public function handle($exception) {
        $message = $exception->getMessage();

        // Add the exception backtrace for developers.
        if (debugging('', DEBUG_DEVELOPER)) {
            $message .= "\n" . format_backtrace(get_exception_info($exception)->backtrace, true);
        }

        // Switch to response.
        $type = str_replace('Request', 'Response', $this->type);

        // Build the appropriate xml.
        $response = qtype_lti_get_response_xml('failure', $message, $this->id, $type);

        $xml = $response->asXML();

        // Log the request if necessary.
        if ($this->log) {
            qtype_lti_log_response($xml, $exception);
        }

        echo $xml;
    }
}

// TODO: Switch to core oauthlib once implemented - MDL-30149.
use moodle\qtype\lti as lti;

$rawbody = file_get_contents("php://input");

$logrequests = qtype_lti_should_log_request($rawbody);
$errorhandler = new qtype_lti_service_exception_handler($logrequests);

// Register our own error handler so we can always send valid XML response.
set_exception_handler(array($errorhandler, 'handle'));

if ($logrequests) {
    qtype_lti_log_request($rawbody);
}
$ltiheaders = lti\OAuthUtil::get_headers();
foreach ($ltiheaders as $name => $value) {
    if ($name === 'Authorization') {
        $oauthparams = lti\OAuthUtil::split_header($value);
        $consumerkey = $oauthparams['oauth_consumer_key'];
        break;
    }
}

if (empty($consumerkey)) {
    throw new Exception('Consumer key is missing, or Authorization header is not readable by the server.');
}
$returnedsecret = qtype_lti_get_shared_secrets_by_key($consumerkey);

$sharedsecret = qtype_lti_verify_message($consumerkey, $returnedsecret, $rawbody);

if ($sharedsecret === false) {
    throw new Exception('Message signature not valid');
}

// TODO MDL-46023 Replace this code with a call to the new library.
$origentity = libxml_disable_entity_loader(true);
$xml = simplexml_load_string($rawbody);

if (!$xml) {
    libxml_disable_entity_loader($origentity);
    throw new Exception('Invalid XML content');
}
libxml_disable_entity_loader($origentity);

$body = $xml->imsx_POXBody;

foreach ($body->children() as $child) {
    $messagetype = $child->getName();
}

// We know more about the message, update error handler to send better errors.
$errorhandler->set_message_id(qtype_lti_parse_message_id($xml));
$errorhandler->set_message_type($messagetype);

switch ($messagetype) {
    case 'replaceResultRequest':
        $parsed = qtype_lti_parse_grade_replace_message($xml);
        $ltiinstance = $DB->get_record('qtype_lti_options', array('id' => $parsed->ltiid));

        if (!$ltiinstance) {
            throw new Exception('No such tool.');
        }

        if (!qtype_lti_accepts_grades($ltiinstance)) {
            throw new Exception('Tool does not accept grades');
        }

        qtype_lti_verify_sourcedid($ltiinstance, $parsed);
        qtype_lti_set_session_user($parsed->username);

        $gradestatus = qtype_lti_update_grade($parsed->username, $parsed->linkid, $parsed->resultid, $parsed->gradeval,
                                              $parsed->ltiid, $parsed->mattempt);

        if (!$gradestatus) {
            throw new Exception('Grade replace response');
        }

        $responsexml = qtype_lti_get_response_xml('success', 'Grade replace response', $parsed->messageid, 'replaceResultResponse');

        echo $responsexml->asXML();

        break;

    case 'readResultRequest':

        $parsed = qtype_lti_parse_grade_read_message($xml);

        $ltiinstance = $DB->get_record('qtype_lti_options', array('id' => $parsed->ltiid));

        if (!$ltiinstance) {
            throw new Exception('No such tool.');
        }

        if (!qtype_lti_accepts_grades($ltiinstance)) {
            throw new Exception('Tool does not accept grades');
        }

        qtype_lti_verify_sourcedid($ltiinstance, $parsed);

        $grade = qtype_lti_read_grade($parsed->username, $parsed->linkid, $parsed->resultid, $parsed->mattempt);

        $responsexml = qtype_lti_get_response_xml('success', 'Result read', $parsed->messageid, 'readResultResponse');

        $node = $responsexml->imsx_POXBody->readResultResponse;
        $node = $node->addChild('result')->addChild('resultScore');
        $node->addChild('language', 'en');
        $node->addChild('textString', isset($grade) ? $grade : '');

        echo $responsexml->asXML();

        break;

    case 'deleteResultRequest':
        $parsed = qtype_lti_parse_grade_delete_message($xml);

        $ltiinstance = $DB->get_record('qtype_lti_options', array('id' => $parsed->ltiid));

        if (!$ltiinstance) {
            throw new Exception('No such tool.');
        }

        if (!qtype_lti_accepts_grades($ltiinstance)) {
            throw new Exception('Tool does not accept grades');
        }

        qtype_lti_verify_sourcedid($ltiinstance, $parsed);
        qtype_lti_set_session_user($parsed->username);

        $gradestatus = qtype_lti_delete_grade($parsed->username, $parsed->linkid, $parsed->resultid, $parsed->mattempt);

        if (!$gradestatus) {
            throw new Exception('Grade delete request was not successful');
        }

        $responsexml = qtype_lti_get_response_xml('success', 'Grade delete request', $parsed->messageid, 'deleteResultResponse');

        echo $responsexml->asXML();

        break;

    default:
        // Fire an event if we get a web service request which we don't support directly.
        // This will allow others to extend the LTI services, which I expect to be a common
        // use case, at least until the spec matures.
        $data = new stdClass();
        $data->body = $rawbody;
        $data->xml = $xml;
        $data->messageid = qtype_lti_parse_message_id($xml);
        $data->messagetype = $messagetype;
        $data->consumerkey = $consumerkey;
        $data->sharedsecret = $sharedsecret;
        $eventdata = array();
        $eventdata['other'] = array();
        $eventdata['other']['messageid'] = $data->messageid;
        $eventdata['other']['messagetype'] = $messagetype;
        $eventdata['other']['consumerkey'] = $consumerkey;

        // Before firing the event, allow subplugins a chance to handle.
        if (qtype_lti_extend_lti_services($data)) {
            break;
        }

        // If an event handler handles the web service, it should set this global to true
        // So this code knows whether to send an "operation not supported" or not.
        global $ltiwebservicehandled;
        $ltiwebservicehandled = false;

        try {
            $event = \qtype_lti\event\unknown_service_api_called::create($eventdata);
            $event->set_message_data($data);
            $event->trigger();
        } catch (Exception $e) {
            $ltiwebservicehandled = false;
        }

        if (!$ltiwebservicehandled) {
            $responsexml = qtype_lti_get_response_xml('unsupported', 'unsupported', qtype_lti_parse_message_id($xml), $messagetype);

            echo $responsexml->asXML();
        }

        break;
}
