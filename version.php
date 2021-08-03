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
 *
 * @package qtype_lti
 * @author Amr Hourani amr.hourani@id.ethz.ch
 * @copyright 2019 ETH Zurich
 */
defined('MOODLE_INTERNAL') || die();

$plugin->component = 'qtype_lti';
$plugin->version = 2021080301;
$plugin->requires = 2018051700; // Moodle >=3.5.

$plugin->maturity = MATURITY_STABLE;
$plugin->release = '0.4 for Moodle 3.5+';
