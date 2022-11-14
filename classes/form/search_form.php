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
 * @copyright 2021 Wunderbyte GmbH {@link http://www.wunderbyte.at}
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
        $this->params = $params;
        parent::__construct();
    }

    /**
     * Defines the form fields.
     */
    public function definition() {
        global $DB;

        $mform = $this->_form;

        // Determine if current user is a student or not.
        $isstudent = !has_capability('block/booking:managesitebookingoptions', $this->context);

        // Id can be from course or mod, so we just get it from url param.
        $id = optional_param('id', 0, PARAM_INT);

        // Important: This is needed to make the block work within courses.
        $mform->addElement('hidden', 'id', $id);
        $mform->setType('id', PARAM_INT);

        // First entry is selected by default, so let's make it empty.
        $coursenames = ['' => ''];
        // Get all course names from DB.
        $coursenamessql = "SELECT DISTINCT fullname from {course}";
        if ($records = $DB->get_records_sql($coursenamessql)) {
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
        // Get all option names from DB.
        $optionnamessql = "SELECT DISTINCT text
                           FROM {booking_options}
                           WHERE text <> '' AND text IS NOT NULL";
        if ($records = $DB->get_records_sql($optionnamessql)) {
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
        if (!empty(get_config('block_booking', 'userinfofield')) && $isstudent) {
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
                        WHERE (
                           (bo.coursestarttime >= :coursestarttime AND bo.courseendtime <= :courseendtime)
                        OR (bod.coursestarttime >= :coursestarttime2 AND bod.courseendtime <= :courseendtime2)
                        OR bo.coursestarttime = ''
                        OR bo.coursestarttime IS NULL
                        OR bo.courseendtime = ''
                        OR bo.courseendtime IS NULL)
                        AND location <> '' AND location IS NOT NULL";

        // Only locations that matched the search will be added to the dropdown.
        $startendparams = [];
        if (!empty($this->params['coursestarttime'])) {
            $startendparams['coursestarttime'] = $this->params['coursestarttime'];
            // Params cannot be used twice, so we need to add them again.
            $startendparams['coursestarttime2'] = $this->params['coursestarttime'];
        } else {
            $startendparams['coursestarttime'] = 0;
            $startendparams['coursestarttime2'] = 0;
        }
        if (!empty($this->params['courseendtime'])) {
            $startendparams['courseendtime'] = strtotime('+1 day', $this->params['courseendtime']);
            $startendparams['courseendtime2'] = $startendparams['courseendtime'];
        } else {
            $startendparams['courseendtime'] = 9999999999;
            $startendparams['courseendtime2'] = 9999999999;
        }

        if ($records = $DB->get_records_sql($locationssql, $startendparams)) {
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
        $nonlocationssql = "SELECT DISTINCT location AS nonlocation
                        FROM {booking_options}
                        WHERE location <> '' AND location IS NOT NULL";

        if ($records = $DB->get_records_sql($nonlocationssql)) {
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
        $teacherssql = "SELECT DISTINCT bt.userid, u.firstname, u.lastname, u.username
                        FROM {booking_teachers} bt
                        JOIN {user} u
                        ON bt.userid = u.id";
        if ($records = $DB->get_records_sql($teacherssql)) {
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
