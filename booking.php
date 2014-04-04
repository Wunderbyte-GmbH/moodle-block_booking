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
 * Configuration page.
 *
 * @package    block
 * @subpackage booking
 * @author     David Bogner <info@edulabs.org>
 * @copyright  2014 www.edulabs.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once('../../mod/booking/locallib.php');

$courseid     = required_param('courseid', PARAM_INT);
$sort         = optional_param('sort', null, PARAM_ALPHANUM);

if (!$course = $DB->get_record('course', array('id' => $courseid))) {
    print_error('invalidaccess');
}

require_login($course);

$url = new moodle_url('/blocks/booking/booking.php', array('courseid'=>$courseid));

$PAGE->set_url($url);
$context = CONTEXT_SYSTEM::instance();
$PAGE->set_title(format_string(get_string('booking:viewallbookings','block_booking')));
$PAGE->set_heading(get_string('title','block_booking'));

echo $OUTPUT->header();
echo html_writer::tag('h1', get_string('title','block_booking'));
$sortbyuser = new moodle_url('/blocks/booking/booking.php', array('courseid'=>$courseid, 'sort' => 'user'));
$onlymybookings = new moodle_url('/blocks/booking/booking.php', array('courseid'=>$courseid, 'sort' => 'my'));

echo html_writer::link($sortbyuser, get_string('sortbyuser', 'block_booking'));
echo html_writer::span("  //  ");
echo html_writer::link($url,get_string('sortbycourse', 'block_booking'));
echo html_writer::span("  //  ");
echo html_writer::link($onlymybookings,get_string('showmybookings', 'booking'));
$allbookings = new booking_all_bookings();
$allbookings->get_all_bookings_visible();
echo $allbookings->display($sort);
echo $OUTPUT->footer();
