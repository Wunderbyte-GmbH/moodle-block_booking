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
 * Block booking search form for students.
 *
 * @package   block_booking
 * @copyright 2021 Wunderbyte GmbH {@link http://www.wunderbyte.at}
 * @author    Bernhard Fischer
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_booking\form;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir.'/formslib.php');

use context_system;
use moodleform;

/**
 * Student search form.
 *
 * @copyright 2022 Wunderbyte GmbH {@link http://www.wunderbyte.at}
 * @author    Bernhard Fischer
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class search_form extends moodleform {

    /** @var context_system $context The system context. */
    public $context = null;

    /**
     * Constructor method.
     *
     * @param string $name
     */
    public function __construct(context_system $context, array $params = []) {
        $this->context = $context;
        parent::__construct();
    }

    /**
     * Defines the form fields.
     */
    public function definition() {
        global $DB, $USER;

        $mform = $this->_form;

        // Determine if current user is a student or not.
        $isstudent = !has_capability('block/booking:managesitebookingoptions', $this->context);

        // Get the custom user profile field id to show additional courses...
        // ... and to limit the courses autocomplete in student view.
        $userinfofieldid = get_config('block_booking', 'userinfofield');

        // Id can be from course or mod, so we just get it from url param.
        $id = optional_param('id', 0, PARAM_INT);

        // Important: This is needed to make the block work within courses.
        $mform->addElement('hidden', 'id', $id);
        $mform->setType('id', PARAM_INT);

        // For most autocompletes, we only wanto to search into the future.
        $startendparamsfuture = [
            'coursestarttime' => strtotime('today midnight'),
            'coursestarttime2' => strtotime('today midnight'),
            'courseendtime' => 9999999999,
            'courseendtime2' => 9999999999
        ];
        // WHERE part of SQL checking if either the option of one of the optiondates lies in the future.
        $whereinfuture = "WHERE (
            (bo.coursestarttime >= :coursestarttime AND bo.courseendtime <= :courseendtime) OR
            (bod.coursestarttime >= :coursestarttime2 AND bod.courseendtime <= :courseendtime2)
        )";

        // First entry is selected by default, so let's make it empty.
        $coursenames = ['' => ''];

        /* For students, we limit the autocomplete for all courses having a group matching
        the value of the custom user profile field defined in setting.
        If the field is not specified in settings or we are in admin view, we get all course
        names from DB that have at least one booking instance instead. */
        if (!empty($userinfofieldid) && $isstudent) {
            $coursenamessql = "SELECT DISTINCT c.fullname
                FROM m_groups g
                JOIN {user_info_data} ud
                ON g.name = ud.data
                AND ud.userid = :userid AND ud.fieldid = :fieldid
                JOIN {course} c
                ON c.id = g.courseid
                JOIN {course_modules} cm
                ON c.id = cm.course
                JOIN {modules} m
                ON m.id = cm.module
                WHERE m.name = 'booking'
                AND cm.visible = 1";

            $coursenamesparams = [
                'userid' => $USER->id,
                'fieldid' => $userinfofieldid
            ];
        } else {
            // Get all course names from DB that have at least one booking instance.
            $coursenamessql = "SELECT DISTINCT c.fullname
                FROM {course} c
                JOIN {course_modules} cm
                ON c.id = cm.course
                JOIN {modules} m
                ON m.id = cm.module
                WHERE m.name = 'booking'
                AND cm.visible = 1";

            $coursenamesparams = [];
        }

        if ($records = $DB->get_records_sql($coursenamessql, $coursenamesparams)) {
            // Add every course name to the array (both as key and value so autocomplete will work).
            foreach ($records as $record) {
                // Bugfix: Quotes are not supported by autocomplete, so we need to escape them.
                $escapedcoursename = str_replace('"', '\"', $record->fullname);
                $escapedcoursename = str_replace("'", "\'", $escapedcoursename);
                $coursenames[$escapedcoursename] = $record->fullname;
            }
        }
        $acparams = ['tags' => true, 'multiple' => false];
        $mform->addElement('autocomplete', 'sfcourse', get_string('sfcourse', 'block_booking'),
            $coursenames, $acparams);
        $mform->setType('sfcourse', PARAM_TEXT);

        // First entry is selected by default, so let's make it empty.
        $optionnames = ['' => ''];

        // Get names of booking options starting in the future or having an optiondate in the future.
        $optionnamessql = "SELECT DISTINCT bo.text
            FROM {booking_options} bo
            LEFT JOIN {booking_optiondates} bod
            ON bo.bookingid = bod.bookingid AND bo.id = bod.optionid
            $whereinfuture
            AND text <> '' AND text IS NOT NULL";

        if ($records = $DB->get_records_sql($optionnamessql, $startendparamsfuture)) {
            // Add every option name to the array (both as key and value so autocomplete will work).
            foreach ($records as $record) {
                // Bugfix: Quotes are not supported by autocomplete, so we need to escape them.
                $escapedoptionname = str_replace('"', '\"', $record->text);
                $escapedoptionname = str_replace("'", "\'", $escapedoptionname);
                $optionnames[$escapedoptionname] = $record->text;
            }
        }
        $acparams = ['tags' => true, 'multiple' => false];
        $mform->addElement('autocomplete', 'sfbookingoption', get_string('sfbookingoption', 'block_booking'),
            $optionnames, $acparams);
        $mform->setType('sfbookingoption', PARAM_TEXT);

        $mform->addElement('header', 'sfmorefilters', get_string('sfmorefilters', 'block_booking'));
        $mform->setType('sfmorefilters', PARAM_TEXT);
        $mform->setExpanded('sfmorefilters', false);

        // Check if the global setting to show additional bookings is active...
        // ...and only show for students.
        if (!empty($userinfofieldid) && $isstudent) {
            // We only need an option to turn this off, if the global setting is active.
            $mform->addElement('checkbox', 'sfbookedmodulesonly', get_string('sfbookedmodulesonly', 'block_booking'),
                '', array('group' => 1), array(0, 1));
            $mform->setDefault('sfbookedmodulesonly', 0);
            $mform->setType('sfbookedmodulesonly', PARAM_INT);
        }

        // First entry is selected by default, so let's make it empty.
        $locations = ['' => ''];
        // Get all locations from options in the future.
        $locationssql = "SELECT DISTINCT bo.location
                        FROM {booking_options} bo
                        LEFT JOIN {booking_optiondates} bod
                        ON bo.bookingid = bod.bookingid AND bo.id = bod.optionid
                        $whereinfuture
                        AND bo.location <> '' AND bo.location IS NOT NULL";

        if ($records = $DB->get_records_sql($locationssql, $startendparamsfuture)) {
            // Add every location to the array (both as key and value so autocomplete will work).
            foreach ($records as $record) {
                // Bugfix: Quotes are not supported by autocomplete, so we need to escape them.
                $escapedlocationname = str_replace('"', '\"', $record->location);
                $escapedlocationname = str_replace("'", "\'", $escapedlocationname);
                $locations[$escapedlocationname] = $record->location;
            }
        }
        $options = ['tags' => false, 'multiple' => true];
        $mform->addElement('autocomplete', 'sflocation', get_string('sflocation', 'block_booking'),
            $locations, $options);
        $mform->setType('sflocation', PARAM_TEXT);

        // First entry is selected by default, so let's make it empty.
        $nonlocations = ['' => ''];
        // Get all locations from options in the future.
        $nonlocationssql = "SELECT DISTINCT bo.location AS nonlocation
                            FROM {booking_options} bo
                            LEFT JOIN {booking_optiondates} bod
                            ON bo.bookingid = bod.bookingid AND bo.id = bod.optionid
                            $whereinfuture
                            AND bo.location <> '' AND bo.location IS NOT NULL";

        if ($records = $DB->get_records_sql($nonlocationssql, $startendparamsfuture)) {
            // Add every location to the array (both as key and value so autocomplete will work).
            foreach ($records as $record) {
                // Bugfix: Quotes are not supported by autocomplete, so we need to escape them.
                $escapednonlocationname = str_replace('"', '\"', $record->nonlocation);
                $escapednonlocationname = str_replace("'", "\'", $escapednonlocationname);
                $nonlocations[$escapednonlocationname] = $record->nonlocation;
            }
        }
        $options = ['tags' => false, 'multiple' => true];
        $mform->addElement('autocomplete', 'sfnonlocation', get_string('sfnonlocation', 'block_booking'),
            $nonlocations, $options);
        $mform->setType('sfnonlocation', PARAM_TEXT);

        // First entry is selected by default, so let's make it empty.
        $teachers = ['' => ''];
        // Get all teachers from DB.
        $teacherssql = "SELECT DISTINCT bt.userid, u.firstname, u.lastname
                        FROM {booking_teachers} bt
                        JOIN {user} u
                        ON bt.userid = u.id
                        JOIN {booking_options} bo
                        ON bo.id = bt.optionid
                        LEFT JOIN {booking_optiondates} bod
                        ON bo.bookingid = bod.bookingid AND bo.id = bod.optionid
                        $whereinfuture";

        if ($records = $DB->get_records_sql($teacherssql, $startendparamsfuture)) {
            // Add every teacher to the array (userid as key, full name as value).
            foreach ($records as $record) {
                $teachers[$record->userid] = $record->lastname . ' ' . $record->firstname;
            }
        }
        $options = ['tags' => false, 'multiple' => false];
        $mform->addElement('autocomplete', 'sfteacher', get_string('sfteacher', 'block_booking'),
            $teachers, $options);
        $mform->setType('sfteacher', PARAM_TEXT);

        $mform->addElement('checkbox', 'sffromcheckbox', get_string('sffromcheckbox', 'block_booking'));
        $mform->setDefault('sffromcheckbox', 1);

        $mform->addElement('date_selector', 'sfcoursestarttime', get_string('sfcoursestarttime', 'block_booking'));
        $mform->setType('sfcoursestarttime', PARAM_INT);
        $mform->hideIf('sfcoursestarttime', 'sffromcheckbox');

        $mform->addElement('checkbox', 'sfuntilcheckbox', get_string('sfuntilcheckbox', 'block_booking'));

        $mform->addElement('date_selector', 'sfcourseendtime', get_string('sfcourseendtime', 'block_booking'));
        $mform->setType('sfcourseendtime', PARAM_INT);
        $mform->hideIf('sfcourseendtime', 'sfuntilcheckbox');

        $this->add_action_buttons(false, get_string('sfsearchbtn', 'block_booking'));
    }
}
