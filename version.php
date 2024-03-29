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
 * Version information
 *
 * @package    block_booking
 * @author     David Bogner, Bernhard Fischer
 * @copyright  Wunderbyte GmbH 2014-2022 https://www.wunderbyte.at <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->version = 2023081000;
$plugin->release = '4.0.1';
$plugin->component = 'block_booking';
$plugin->maturity = MATURITY_STABLE;
$plugin->requires = 2022041900; // Requires this Moodle version. Current: Moodle 4.0.0.
$plugin->dependencies = [
    'mod_booking' => 2023080700,
    'local_wunderbyte_table' => 2023080300
];
