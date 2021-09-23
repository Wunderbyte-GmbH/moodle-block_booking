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

defined('MOODLE_INTERNAL') || die();

global $CFG, $PAGE, $COURSE;

require_once("../../config.php");
require_login($COURSE);

require("$CFG->libdir/tablelib.php");

use mod_booking\table\bookingoptions_simple_table;
use block_booking;

$download = optional_param('download', '', PARAM_ALPHA);

$blockbooking = new block_booking();

$sqldata = $blockbooking->search_booking_options_manager_get_sqldata();

$table = new bookingoptions_simple_table('block_booking_resultstable');

$table->set_sql($sqldata);

$table->define_baseurl("$CFG->wwwroot/blocks/booking/block_booking_table.php");

$table->out(40, true);
