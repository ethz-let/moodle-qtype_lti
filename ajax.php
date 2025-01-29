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
 * AJAX service used when adding an External Tool.
 *
 * It is used to provide immediate feedback
 * of which tool provider is to be used based on the Launch URL.
 *
 * @package    mod_lti
 * @subpackage xml
 * @copyright Copyright (c) 2011 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Chris Scribner
 */
define('AJAX_SCRIPT', true);

require_once(__DIR__ . "/../../../config.php");
require_once($CFG->dirroot . '/question/type/lti/locallib.php');

$courseid = required_param('course', PARAM_INT);
$context = context_course::instance($courseid);

require_login($courseid, false);

$action = required_param('action', PARAM_TEXT);

$response = new stdClass();

switch ($action) {
    case 'get_toolInfo':
        $toolurl = required_param('toolurl', PARAM_URL);
        $questionid = required_param('questionid', PARAM_INT);
        $toolid = optional_param('toolid', 0, PARAM_INT);

        require_capability('moodle/question:add', $context);

        if (!empty($toolurl) && qtype_lti_is_cartridge($toolurl)) {
            $response->cartridge = true;
        } else {
            if (empty($toolid) && !empty($toolurl)) {
                $tool = qtype_lti_get_tool_by_url_match($toolurl, $courseid);

                if (!empty($tool)) {
                    $toolid = $tool->id;

                    $response->toolid = $tool->id;
                    $response->toolname = s($tool->name);
                    $response->tooldomain = s($tool->tooldomain);
                }
            } else {
                $response->toolid = $toolid;
            }

            if (!empty($toolid)) {
                // Look up privacy settings.
                $query = '
                    SELECT name, value
                    FROM {qtype_lti_types_config}
                    WHERE
                        typeid = :typeid
                    AND name IN (\'sendname\', \'sendemailaddr\', \'acceptgrades\', \'verifyltiurl\', \'checkduplicateltiurl\')
                ';

                $privacyconfigs = $DB->get_records_sql($query, array('typeid' => $toolid));
                $success = count($privacyconfigs) > 0;
                foreach ($privacyconfigs as $config) {
                    $configname = $config->name;
                    $response->$configname = $config->value;
                }
                if (!$success) {
                    $response->error = s(get_string('tool_config_not_found', 'qtype_lti'));
                }
            }

            // Check for duplicate Tool Urls.
            if (empty($questionid)) {
                $questionid = 0;
            }
            if (empty($toolurl)) {
                $response->duplicates = 0;
            } else {

                $urlparts = gtype_lti_get_parsed_url($toolurl);
                $toolurl = $urlparts['host'] . $urlparts['path'] . $urlparts['query'];

                $versions = $DB->get_records_sql("
                SELECT allversions.questionid as qids

                  FROM {question_versions} allversions

                 WHERE allversions.questionbankentryid = (
                            SELECT givenversion.questionbankentryid
                              FROM {question_versions} givenversion
                             WHERE givenversion.questionid = ?
                       )
                   AND allversions.status <> ?

              ORDER BY allversions.version DESC
              ", [$questionid, 'draft']);
               $versionids = [];
               foreach ($versions as $version){
                $versionids[] = $version->qids;
               }
                $questionids = implode(',', $versionids);
                if(!$versions) {
                    $questionids = 0;
                }

                $query = '
                    SELECT *
                    FROM {qtype_lti_options}
                    WHERE 
                        LOWER(toolurl) LIKE LOWER(:toolurl)
                    AND 
                        questionid not in ('.$questionids.')
                ';
                $results = $DB->get_records_sql($query, array('toolurl' => "%$toolurl"));
                $response->duplicates = count($results) ?: 0;
                $response->questionid = $questionid;
            }
        }

        break;
}
echo $OUTPUT->header();
echo json_encode($response);

die;
