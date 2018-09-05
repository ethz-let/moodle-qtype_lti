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
 * This file contains all necessary code to launch a Tool Proxy registration
 *
 * @package qtype_lti
 * @copyright  2014 Vital Source Technologies http://vitalsource.com
 * @author     Stephen Vickers
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/question/type/lti/locallib.php');

// No guest autologin.
require_login(0, false);

$pageurl = new moodle_url('/question/type/lti/toolproxies.php');
$PAGE->set_url($pageurl);

admin_externalpage_setup('manageqtypes');

$PAGE->set_title("{$SITE->shortname}: " . get_string('toolregistration', 'qtype_lti'));

$configuredtoolproxieshtml = '';
$pendingtoolproxieshtml = '';
$acceptedtoolproxieshtml = '';
$rejectedtoolproxieshtml = '';

$configured = get_string('configured', 'qtype_lti');
$pending = get_string('pending', 'qtype_lti');
$accepted = get_string('accepted', 'qtype_lti');
$rejected = get_string('rejected', 'qtype_lti');

$name = get_string('name', 'qtype_lti');
$url = get_string('registrationurl', 'qtype_lti');
$action = get_string('action', 'qtype_lti');
$createdon = get_string('createdon', 'qtype_lti');

$toolproxies = $DB->get_records('qtype_lti_tool_proxies');

$configuredtoolproxies = qtype_lti_filter_tool_proxy_types($toolproxies, QTYPE_LTI_TOOL_PROXY_STATE_CONFIGURED);
$configuredtoolproxieshtml = qtype_lti_get_tool_proxy_table($configuredtoolproxies, 'tp_configured');

$pendingtoolproxies = qtype_lti_filter_tool_proxy_types($toolproxies, QTYPE_LTI_TOOL_PROXY_STATE_PENDING);
$pendingtoolproxieshtml = qtype_lti_get_tool_proxy_table($pendingtoolproxies, 'tp_pending');

$acceptedtoolproxies = qtype_lti_filter_tool_proxy_types($toolproxies, QTYPE_LTI_TOOL_PROXY_STATE_ACCEPTED);
$acceptedtoolproxieshtml = qtype_lti_get_tool_proxy_table($acceptedtoolproxies, 'tp_accepted');

$rejectedtoolproxies = qtype_lti_filter_tool_proxy_types($toolproxies, QTYPE_LTI_TOOL_PROXY_STATE_REJECTED);
$rejectedtoolproxieshtml = qtype_lti_get_tool_proxy_table($rejectedtoolproxies, 'tp_rejected');

$tab = optional_param('tab', '', PARAM_ALPHAEXT);
$configuredselected = '';
$pendingselected = '';
$acceptedselected = '';
$rejectedselected = '';
switch ($tab) {
    case 'tp_pending':
        $pendingselected = 'class="selected"';
        break;
    case 'tp_accepted':
        $acceptedselected = 'class="selected"';
        break;
    case 'tp_rejected':
        $rejectedselected = 'class="selected"';
        break;
    default:
        $configuredselected = 'class="selected"';
        break;
}
$registertype = get_string('registertype', 'lti');
$config = get_string('manage_tools', 'lti');

$registertypeurl = "{$CFG->wwwroot}/question/type/lti/registersettings.php?action=add&amp;sesskey={$USER->sesskey}&amp;tab=tool_proxy";

$template = <<< EOD
<div id="tp_tabs" class="yui-navset">
    <ul id="tp_tab_heading" class="yui-nav" style="display:none">
        <li {$configuredselected}>
            <a href="#tab1">
                <em>$configured</em>
            </a>
        </li>
        <li {$pendingselected}>
            <a href="#tab2">
                <em>$pending</em>
            </a>
        </li>
        <li {$acceptedselected}>
            <a href="#tab3">
                <em>$accepted</em>
            </a>
        </li>
        <li {$rejectedselected}>
            <a href="#tab4">
                <em>$rejected</em>
            </a>
        </li>
    </ul>
    <div class="yui-content">
        <div>
            <div><a style="margin-top:.25em" href="{$registertypeurl}">{$registertype}</a></div>
            $configuredtoolproxieshtml
        </div>
        <div>
            $pendingtoolproxieshtml
        </div>
        <div>
            $acceptedtoolproxieshtml
        </div>
        <div>
            $rejectedtoolproxieshtml
        </div>
    </div>
</div>

<script type="text/javascript">
//<![CDATA[
    YUI().use('yui2-tabview', 'yui2-datatable', function(Y) {
        //If javascript is disabled, they will just see the three tabs one after another
        var tp_tab_heading = document.getElementById('tp_tab_heading');
        tp_tab_heading.style.display = '';

        new Y.YUI2.widget.TabView('tp_tabs');

        var setupTools = function(id, sort){
            var tp_tool_proxies = Y.YUI2.util.Dom.get(id);

            if(tp_tool_proxies){
                var dataSource = new Y.YUI2.util.DataSource(tp_tool_proxies);

                var configuredColumns = [
                    {key:'name', label:'$name', sortable:true},
                    {key:'url', label:'$url', sortable:true},
                    {key:'timecreated', label:'$createdon', sortable:true},
                    {key:'action', label:'$action'}
                ];

                dataSource.responseType = Y.YUI2.util.DataSource.TYPE_HTMLTABLE;
                dataSource.responseSchema = {
                    fields: [
                        {key:'name'},
                        {key:'url'},
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

        setupTools('tp_configured_tool_proxies', {key:'name', dir:'asc'});
        setupTools('tp_pending_tool_proxies', {key:'timecreated', dir:'desc'});
        setupTools('tp_accepted_tool_proxies', {key:'timecreated', dir:'desc'});
        setupTools('tp_rejected_tool_proxies', {key:'timecreated', dir:'desc'});
    });
//]]
</script>
EOD;

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('manage_tool_proxies', 'qtype_lti'), 2);
echo $OUTPUT->heading(new lang_string('toolproxy', 'qtype_lti') .
        $OUTPUT->help_icon('toolproxy', 'qtype_lti'), 3);

echo $OUTPUT->box_start('generalbox');

echo $template;

echo $OUTPUT->box_end();
echo $OUTPUT->footer();
