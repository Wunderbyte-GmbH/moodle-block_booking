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
 * Global settings of the Bookings Quickfinder block.
 *
 * @package    block_booking
 * @copyright  2021 Wunderbyte GmbH <info@wunderbyte.at>
 * @author     Bernhard Fischer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $DB;

$settings->add(new admin_setting_heading(
            'settingsheader',
            get_string('settingsheader', 'block_booking'),
            get_string('settingsheaderdesc', 'block_booking')
        ));

$userinfofields = $DB->get_records('user_info_field', null, '', 'id, name');

if (!empty($userinfofields)) {

    $userinfofieldsarray = [];
    $userinfofieldsarray[0] = get_string('userinfofieldoff', 'block_booking');

    // Create an array of key => value pairs for the dropdown.
    foreach ($userinfofields as $userinfofield) {
        $userinfofieldsarray[$userinfofield->id] = $userinfofield->name;
    }

    $settings->add(
    new admin_setting_configselect('block_booking/userinfofield',
        get_string('userinfofield', 'block_booking'),
        get_string('userinfofielddesc', 'block_booking'),
        0, $userinfofieldsarray));
}
