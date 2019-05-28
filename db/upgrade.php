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
 * @package qtype_lti
 * @author Amr Hourani amr.hourani@id.ethz.ch
 * @copyright ETHz 2016 amr.hourani@id.ethz.ch
 */
defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade code for the lti question type.
 *
 * @param int $oldversion the version we are upgrading from.
 */
function xmldb_qtype_lti_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();
    
    if ($oldversion < 2018111300) {
    	$old_record = $DB->get_record('role_capabilities', array('capability' => 'qtype/lti:addcoursetool', 'roleid' => 3));
    	if($old_record){
    		$params = [
    				'id' => $old_record->id
    		];
    		
    		$DB->delete_records('role_capabilities', $params);
    	}
    	
    	upgrade_plugin_savepoint(true, 2018111300, 'qtype', 'lti');	
    } 	
    if ($oldversion < 2019031902) {
    	// Define field lti_usage to control display of result table
    	$table = new xmldb_table('qtype_lti_usage');
    	
    	// Adding fields to table lti_usage
    	$table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
    	$table->add_field('ltiid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, false, null);
    	$table->add_field('instancecode', XMLDB_TYPE_CHAR, '40', null, XMLDB_NOTNULL, false, null);
    	$table->add_field('attemptid', XMLDB_TYPE_CHAR, '40', null, XMLDB_NOTNULL, false, null);
    	$table->add_field('mattemptid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, false, null);
    	$table->add_field('questionid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, false, null);
    	$table->add_field('quizid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, false, null);
    	$table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, false, null);
    	$table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, false, null);
    	$table->add_field('resourcelinkid', XMLDB_TYPE_CHAR, '250', null, XMLDB_NOTNULL, false, null);
    	$table->add_field('resultid', XMLDB_TYPE_CHAR, '250', null, XMLDB_NOTNULL, false, null);
    	$table->add_field('origin', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, false, null);
    	$table->add_field('destination', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, false, null);
    	$table->add_field('timeadded', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, false, null);

    	// Adding keys to table lti_usage
    	$table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

    	if (!$dbman->table_exists($table)) {
    		$dbman->create_table($table);
    	}
    	
    	unset($table);
    	
    	$DB->delete_records('qtype_lti_submission');

    	$table = new xmldb_table('qtype_lti_submission');

    	if ($dbman->field_exists($table, 'attemptid')) {
    		$field = new xmldb_field('attemptid');
    		$dbman->drop_field($table, $field);
    	}
    	if ($dbman->field_exists($table, 'userid')) {
    		$field = new xmldb_field('userid');
    		$dbman->drop_field($table, $field);
    	}
    	
    	// Add needed columns.
    	if (!$dbman->field_exists($table, 'username')) {
    		$field = new xmldb_field('username', XMLDB_TYPE_CHAR, '60', null, XMLDB_NOTNULL, false, null);
    		$dbman->add_field($table, $field);
    	}
    	if (!$dbman->field_exists($table, 'linkid')) {
    		$field = new xmldb_field('linkid', XMLDB_TYPE_CHAR, '250', null, XMLDB_NOTNULL, false, null);	
    		$dbman->add_field($table, $field);
    	}
    	if (!$dbman->field_exists($table, 'resultid')) {
    		$field = new xmldb_field('resultid', XMLDB_TYPE_CHAR, '250', null, XMLDB_NOTNULL, false, null);
    		$dbman->add_field($table, $field);
    	}
    	
    	upgrade_plugin_savepoint(true, 2019031902, 'qtype', 'lti');
    	
    }
    
    
    return true;
}
