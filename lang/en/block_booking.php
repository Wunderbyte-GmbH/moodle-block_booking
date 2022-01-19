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
 * En language file for the plugin.
 *
 * @package    block_booking
 * @author     David Bogner, Bernhard Fischer <info@wunderbyte.at>
 * @copyright  2014-2021 https://www.wunderbyte.at
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
$string['pluginname'] = 'Bookings Quickfinder block';
$string['title'] = 'Bookings Quickfinder';
$string['sortbycourse'] = 'Sort by course';
$string['sortbyuser'] = 'Sort by user';

$string['booking:addinstance'] = 'Add Bookings Quickfinder block';
$string['booking:myaddinstance'] = 'Add Bookings Quickfinder block to Dashboard';
$string['booking:viewallbookings'] = 'Overview of all bookings';
$string['booking:managesitebookingoptions'] = 'Manage all booking options on the whole site';

// File: block_booking.php.
$string['createdbywunderbyte'] = 'Developed with &#128156; &nbsp;by <a href="https:www.wunderbyte.at">Wunderbyte</a>';

// File: search_form.mustache.
$string['sfcourse'] = 'Course';
$string['sfbookingoption'] = 'Booking option';
$string['sflocation'] = 'Location';
$string['sfteacher'] = 'Teacher';
$string['sffromcheckbox'] = 'Search from date...';
$string['sfuntilcheckbox'] = 'Search until date...';
$string['sfcoursestarttime'] = 'From';
$string['sfcourseendtime'] = 'Until';
$string['sfsearchbtn'] = 'Find bookings';
$string['sfmorefilters'] = 'More filters...';

// File: searchresults_student_view.mustache.
$string['searchresultsfound'] = '{$a->count} booking options found (click to show)';
$string['nosearchresults'] = 'No booking options could be found. Please try changing your filters or use less of them...';
$string['booked'] = 'You have booked this option.';
$string['onwaitinglist'] = 'You are on the waiting list.';
$string['notenrolled'] = 'Your are not enrolled in this course.';
$string['modalheadertitle'] = '{$a->count} booking options found';
$string['btnshow'] = 'Show';

// File: settings.php.
$string['settingsheader'] = 'Bookings Quickfinder Settings';
$string['settingsheaderdesc'] = 'Here you can edit global settings for all instances of the Bookings Quickfinder block on your platform.';
$string['userinfofield'] = 'Show additional bookings';
$string['userinfofielddesc'] =
    'A user can see additional bookings of courses even if (s)he is not actually enrolled in them.
    If the value of the selected user profile field corresponds with the name of a group within a course,
    the user can see all the booking options from any booking instance within this course.';
$string['userinfofieldoff'] = 'Do not show';
