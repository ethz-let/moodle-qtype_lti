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
 * This file defines the global lti administration form
 *
 * @package qtype_lti
 * @copyright 2019 ETH Zurich
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/*
 * @var admin_settingpage $settings
 */

if ($ADMIN->fulltree) {
    require_once($CFG->dirroot . '/question/type/lti/locallib.php');

    $configuredtoolshtml = '';
    $pendingtoolshtml = '';
    $rejectedtoolshtml = '';

    $active = get_string('active', 'qtype_lti');
    $pending = get_string('pending', 'qtype_lti');
    $rejected = get_string('rejected', 'qtype_lti');

    // Gather strings used for labels in the inline JS.
    $PAGE->requires->strings_for_js(
                                    array('typename', 'baseurl', 'action', 'createdon'), 'qtype_lti');

    $types = qtype_lti_filter_get_types(get_site()->id);

    $configuredtools = qtype_lti_filter_tool_types($types, QTYPE_LTI_TOOL_STATE_CONFIGURED);

    $configuredtoolshtml = qtype_lti_get_tool_table($configuredtools, 'lti_configured');

    $pendingtools = qtype_lti_filter_tool_types($types, QTYPE_LTI_TOOL_STATE_PENDING);

    $pendingtoolshtml = qtype_lti_get_tool_table($pendingtools, 'lti_pending');

    $rejectedtools = qtype_lti_filter_tool_types($types, QTYPE_LTI_TOOL_STATE_REJECTED);

    $rejectedtoolshtml = qtype_lti_get_tool_table($rejectedtools, 'lti_rejected');

    $tab = optional_param('tab', '', PARAM_ALPHAEXT);
    $activeselected = '';
    $pendingselected = '';
    $rejectedselected = '';
    switch ($tab) {
        case 'lti_pending':
            $pendingselected = 'class="selected"';
        break;
        case 'lti_rejected':
            $rejectedselected = 'class="selected"';
        break;
        default:
            $activeselected = 'class="selected"';
        break;
    }
    $addtype = get_string('addtype', 'qtype_lti');
    $config = get_string('manage_tool_proxies', 'qtype_lti');

    $addtypeurl = "{$CFG->wwwroot}/question/type/lti/typessettings.php?action=add&amp;sesskey={$USER->sesskey}";

    $template = <<< EOD
<div id="qtype_lti_tabs" class="yui-navset">
    <ul id="lti_tab_heading" class="yui-nav" style="display:none">
        <li {$activeselected}>
            <a href="#tab1">
                <em>$active</em>
            </a>
        </li>
        <li {$pendingselected}>
            <a href="#tab2">
                <em>$pending</em>
            </a>
        </li>
        <li {$rejectedselected}>
            <a href="#tab3">
                <em>$rejected</em>
            </a>
        </li>
    </ul>
    <div class="yui-content">
        <div>
            <div><a style="margin-top:.25em" href="{$addtypeurl}">{$addtype}</a></div>
            $configuredtoolshtml
        </div>
        <div>
            $pendingtoolshtml
        </div>
        <div>
            $rejectedtoolshtml
        </div>
    </div>
</div>

<script type="text/javascript">
//<![CDATA[
    YUI().use('yui2-tabview', 'yui2-datatable', function(Y) {
        //If javascript is disabled, they will just see the three tabs one after another
        var lti_tab_heading = document.getElementById('lti_tab_heading');
        lti_tab_heading.style.display = '';

        new Y.YUI2.widget.TabView('qtype_lti_tabs');

        var setupTools = function(id, sort){
            var lti_tools = Y.YUI2.util.Dom.get(id);

            if(lti_tools){
                var dataSource = new Y.YUI2.util.DataSource(lti_tools);

                var configuredColumns = [
                    {key:'name', label: M.util.get_string('typename', 'qtype_lti'), sortable: true},
                    {key:'baseURL', label: M.util.get_string('baseurl', 'qtype_lti'), sortable: true},
                    {key:'timecreated', label: M.util.get_string('createdon', 'qtype_lti'), sortable: true},
                    {key:'action', label: M.util.get_string('action', 'qtype_lti')}
                ];

                dataSource.responseType = Y.YUI2.util.DataSource.TYPE_HTMLTABLE;
                dataSource.responseSchema = {
                    fields: [
                        {key:'name'},
                        {key:'baseURL'},
                        {key:'timecreated'},
                        {key:'action'}
                    ]
                };

                new Y.YUI2.widget.DataTable(id + '_container', configuredColumns, dataSource,
                    {
                        sortedBy: sort
                    }
                );
            }
        };

        setupTools('qtype_lti_configured_tools', {key:'name', dir:'asc'});
        setupTools('qtype_lti_pending_tools', {key:'timecreated', dir:'desc'});
        setupTools('qtype_lti_rejected_tools', {key:'timecreated', dir:'desc'});
    });
//]]
</script>
EOD;

    $settings->add(
                new admin_setting_configcheckbox('qtype_lti/removerestoredlink', get_string('removerestoredlink', 'qtype_lti'),
                                                get_string('removerestoredlink_help', 'qtype_lti'), 0));

    $setting = new admin_setting_configcheckbox('qtype_lti/unlockabletoolurl',
            get_string('unlockabletoolurl', 'qtype_lti'),
            get_string('unlockabletoolurl_help', 'qtype_lti'),
            0);
    $setting->set_advanced_flag_options(admin_setting_flag::ENABLED, false);
    $settings->add($setting);

    $settings->add(
                new admin_setting_heading('qtype_lti_types',
                                        new lang_string('external_tool_types', 'qtype_lti') .
                                             $OUTPUT->help_icon('main_admin', 'qtype_lti'), $template . '<br />'));

    $settings->add(
                new admin_setting_heading('qtypeltitoolproxies',
                                        new lang_string('manage_tool_proxies', 'qtype_lti') .
                                             $OUTPUT->help_icon('main_admin', 'qtype_lti'),
                                            '<a href="' . new moodle_url('/question/type/lti/toolproxies.php') . '">' .
                                             new lang_string('manage_tool_proxies', 'qtype_lti') . '</a>'));

    $settings->add(
                new admin_setting_heading('qtypeltitoolconfigure',
                                        new lang_string('manage_external_tools', 'qtype_lti') .
                                             $OUTPUT->help_icon('main_admin', 'qtype_lti'),
                                            '<a href="' . new moodle_url('/question/type/lti/toolconfigure.php') . '">' .
                                             new lang_string('manage_external_tools', 'qtype_lti') . '</a>'));
}
