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
global $CFG;

require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/classes/form/search_form.php');

use block_booking\form\search_form;
use block_booking\output\search_form_container;
use block_booking\output\searchresults_manager;
use block_booking\output\searchresults_student;
use mod_booking\table\bookingoptions_simple_table;

defined('MOODLE_INTERNAL') || die();

/**
 * Block base class.
 *
 * @package    block
 * @subpackage booking
 * @author     David Bogner, Bernhard Fischer <info@wunderbyte.at>
 * @copyright  2014-2021 https://www.wunderbyte.at
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_booking extends block_base {

    /**
     * @var string $title The block title.
     */
    public $title = '';

    /**
     * @var stdClass|null $searchform The search form object.
     */
    public $searchform = null;

    /**
     * @var string $searchformhtml The code of the search form to be passed to the template.
     */
    public $searchformhtml = '';

    /**
     * @var string $context The system context.
     */
    public $context = '';

    /**
     * Initialize the internal variables and search form params.
     * @throws coding_exception
     */
    public function init() {
        $this->title   = get_string('title', 'block_booking');
    }

    /**
     * All formats are allowed for the block.
     * @return bool[]
     */
    function applicable_formats() {
        return array(
            'my' => true,
            'course-view' => true,
            'course-view-social' => false
        );
    }

    /**
     * Get content.
     * @return stdClass|null
     * @throws coding_exception
     */
    public function get_content() {
        global $PAGE;

        // Set system context.
        $this->context = context_system::instance();

        $PAGE->set_context($this->context);

        if ($this->content !== null) {
            return $this->content;
        }

        // Initialize the search form.
        $this->searchform = new search_form();

        // Collect the search form HTML in a buffer.
        ob_start();
        $this->searchform->display();
        // And store it in a member variable.
        $this->searchformhtml = ob_get_clean();

        // Get the renderer for this plugin.
        $output = $PAGE->get_renderer('block_booking');

        // The content.
        $this->content = new stdClass();
        $this->content->text = '';

        if (has_capability('block/booking:managesitebookingoptions', $this->context)) {
            // For managers, create search results as a table.

            $sqldata = $this->search_booking_options_manager_get_sqldata();

            // Cast to array is necessary because empty does not work on objects.
            if (!empty((array)$sqldata)) {
                $resultstable = new bookingoptions_simple_table('block_booking_resultstable');
                $resultstable->set_sql($sqldata->fields, $sqldata->from, $sqldata->where, $sqldata->params);

                // Write the results table to buffer and store HTML in a variable.
                ob_start();
                $resultstable->out(40, true);
                $resultstablehtml = ob_get_clean();

                $searchresultsmanager = new searchresults_manager($resultstablehtml);
                $this->content->text .= $output->render_searchresults_manager($searchresultsmanager);
            }

            // TODO: show search results for managers.

        } else {
            // Show search results for students.
            $results = $this->search_booking_options_student();
            $searchresultsstudent = new searchresults_student($results);
            $this->content->text .= $output->render_searchresults_student($searchresultsstudent);
        }

        // The search form.
        $searchformdata = new search_form_container($this->searchformhtml);
        $this->content->text .= $output->render_search_form_container($searchformdata);

        // The footer.
        $this->content->footer = get_string('createdbywunderbyte', 'block_booking');

        return $this->content;
    }

    /**
     * Function to process the form data and do the search for the students view.
     * @return array The results as an array of DB records.
     * @throws coding_exception
     * @throws dml_exception
     */
    private function search_booking_options_student() {
        global $DB, $USER;

        // Process the form data after submit button has been pressed.
        if ($fromform = $this->searchform->get_data()) {
            $sfcourse = $fromform->sfcourse;
            $sfbookingoption = $fromform->sfbookingoption;
            $sflocation = $fromform->sflocation;
            $sfinstitution = $fromform->sfinstitution;

            // Only use timespan from form if checkbox is active.
            if (isset($fromform->sftimespancheckbox) && $fromform->sftimespancheckbox == 1) {
                $sfcoursestarttime = $fromform->sfcoursestarttime;
                $sfcourseendtime = $fromform->sfcourseendtime;
            } else {
                $sfcoursestarttime = 0;
                $sfcourseendtime = 9999999999;
            }

            // Create the conditions params for the SQL.
            $conditionsparams = [
                "sfcourse" => "%{$sfcourse}%",
                "sfbookingoption" => "%{$sfbookingoption}%",
                "sflocation" => "%{$sflocation}%",
                "sfinstitution" => "%{$sfinstitution}%",
                "sfcoursestarttime" => $sfcoursestarttime,
                "sfcourseendtime" => $sfcourseendtime,
            ];

            // Get all courses where the current user is enrolled and active.
            $enrolledactivecoursesids = [];
            $enrolledactivecourses = enrol_get_users_courses($USER->id, true, ['id']);
            foreach($enrolledactivecourses as $courserecord) {
                $enrolledactivecoursesids[] = $courserecord->id;
            }
            // Get the 'in' part of the SQL.
            list($insql, $inparams) = $DB->get_in_or_equal($enrolledactivecoursesids, SQL_PARAMS_NAMED, 'courseid_');

            $sql = 'SELECT bo.id optionid, s1.cmid, bo.bookingid, bo.text bookingoption, c.id courseid, c.fullname course, bo.location, bo.institution, bo.coursestarttime, bo.courseendtime
                    FROM {booking_options} bo
                    LEFT JOIN {course} c
                    ON c.id = bo.courseid
                    LEFT JOIN (SELECT cm.id cmid, cm.instance bookingid
                    FROM {course_modules} cm
                    WHERE module in (
                      SELECT id FROM {modules} WHERE name = "booking"
                    )) s1
                    ON bo.bookingid = s1.bookingid
                    WHERE bo.text like :sfbookingoption
                    AND c.fullname like :sfcourse
                    AND bo.location like :sflocation
                    AND bo.institution like :sfinstitution
                    AND bo.coursestarttime >= :sfcoursestarttime
                    AND bo.courseendtime <= :sfcourseendtime
                    AND c.id ' . $insql;

            $allparams = array_merge($conditionsparams, $inparams);

            // Now let's get those search results.
            $results = $DB->get_records_sql($sql, $allparams);

            return $results;
        }
    }

    /**
     * Function to process the form data and do the search for the manager table.
     * @return stdClass An object containing all SQL data needed for \mod_booking\table\bookingoptions_simple_table
     */
    private function search_booking_options_manager_get_sqldata(): stdClass {

        // If no form data can be fetched an empty object will be returned.
        $sqldata = new stdClass();

        // Process the form data after submit button has been pressed.
        if ($fromform = $this->searchform->get_data()) {
            $sfcourse = $fromform->sfcourse;
            $sfbookingoption = $fromform->sfbookingoption;
            $sflocation = $fromform->sflocation;
            $sfinstitution = $fromform->sfinstitution;

            // Only use timespan from form if checkbox is active.
            if (isset($fromform->sftimespancheckbox) && $fromform->sftimespancheckbox == 1) {
                $sfcoursestarttime = $fromform->sfcoursestarttime;
                $sfcourseendtime = $fromform->sfcourseendtime;
            } else {
                $sfcoursestarttime = 0;
                $sfcourseendtime = 9999999999;
            }

            // Create all parts of the SQL select query.
            $fields = 'bo.id optionid, s1.cmid, bo.bookingid, bo.text bookingoption, c.id courseid, c.fullname course, ' .
                'bo.location, bo.institution, bo.coursestarttime, bo.courseendtime';

            $from = '{booking_options} bo LEFT JOIN {course} c ON c.id = bo.courseid LEFT JOIN (SELECT cm.id cmid, ' .
                'cm.instance bookingid FROM {course_modules} cm WHERE module in (SELECT id FROM {modules} ' .
                'WHERE name = "booking")) s1 ON bo.bookingid = s1.bookingid';

            $where = 'bo.text like :sfbookingoption AND c.fullname like :sfcourse AND bo.location like :sflocation' .
                'AND bo.institution like :sfinstitution AND bo.coursestarttime >= :sfcoursestarttime ' .
                'AND bo.courseendtime <= :sfcourseendtime';

            $params = [
                "sfcourse" => "%{$sfcourse}%",
                "sfbookingoption" => "%{$sfbookingoption}%",
                "sflocation" => "%{$sflocation}%",
                "sfinstitution" => "%{$sfinstitution}%",
                "sfcoursestarttime" => $sfcoursestarttime,
                "sfcourseendtime" => $sfcourseendtime,
            ];

            $sqldata->fields = $fields;
            $sqldata->from = $from;
            $sqldata->where = $where;
            $sqldata->params = $params;
        }
        return $sqldata;
    }

    /**
     * This block currently has no settings.php.
     * @return false
     */
    public function has_config() {
        return false;
    }
}
