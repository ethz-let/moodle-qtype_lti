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
 * Renderer class for template library.
 *
 * @package qtype_lti
 * @copyright 2019 ETH Zurich
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace qtype_lti\output;

defined('MOODLE_INTERNAL') || die();

use plugin_renderer_base;

/**
 * Renderer class for template library.
 *
 * @package qtype_lti
 * @copyright 2015 Ryan Wyllie <ryan@moodle.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_renderer extends \plugin_renderer_base {

    /**
     * Defer to template.
     *
     * @param tool_configure_page $page
     *
     * @return string html for the page
     */
    protected function render_tool_configure_page($page) {
        $data = $page->export_for_template($this);
        return parent::render_from_template('qtype_lti/tool_configure', $data);
    }

    /**
     * Render the external registration return page
     *
     * @param tool_configure_page $page
     *
     * @return string html for the page
     */
    protected function render_external_registration_return_page($page) {
        $data = $page->export_for_template($this);
        return parent::render_from_template('qtype_lti/external_registration_return', $data);
    }
}

