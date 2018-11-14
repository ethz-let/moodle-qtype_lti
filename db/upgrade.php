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

    return true;
}
