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

global $CFG, $PAGE, $COURSE;

require_once("../../config.php");
require_once("$CFG->dirroot/blocks/booking/block_booking.php");
require_login($COURSE);

require("$CFG->libdir/tablelib.php");

use mod_booking\table\bookingoptions_simple_table;

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url('/block_booking_table.php');

$params = [];
$download = optional_param('download', '', PARAM_ALPHA);
//$params['course'] = optional_param('sfcourse', '', PARAM_TEXT);
//$params['bookingoption'] = optional_param('sfbookingoption', '', PARAM_TEXT);
//$params['location'] = optional_param('sflocation', '', PARAM_TEXT);
//$params['institution'] = optional_param('sfinstitution', '', PARAM_TEXT);
//$params['coursestarttime'] = optional_param('sfcoursestarttime', '', PARAM_INT);
//$params['courseendtime'] = optional_param('sfcourseendtime', '', PARAM_INT);
//
$table = new bookingoptions_simple_table('block_booking_resultstable');

$blockbooking = new block_booking();

// Should be possible to get all this information via this function.
$params = $blockbooking::get_search_params_from_form((object)$_POST);

$sqldata = $blockbooking->search_booking_options_manager_get_sqldata($params);

$table->is_downloading($download, 'booking_quickfinder_found_bookings');
$table->set_sql($sqldata['fields'], $sqldata['from'], $sqldata['where'], $sqldata['params']);
$table->out(40, true);
