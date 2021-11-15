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

use block_booking\form\search_form;
use block_booking\output\search_form_container;
use block_booking\output\searchresults_manager_view;
use block_booking\output\searchresults_student_view;
use mod_booking\table\bookingoptions_simple_table;

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
    public function applicable_formats() {
        return array(
            'all' => true,
            'course-view-social' => false
        );
    }

    /**
     * Get content.
     * @return stdClass|stdObject|null
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function get_content() {

        // Set system context.
        $context = context_system::instance();

        if ($this->content !== null) {
            return $this->content;
        }

        // Initialize the search form.
        $searchform = new search_form();

        // Collect the search form HTML in a buffer.
        ob_start();
        $searchform->display();
        // And store it in a variable.
        $searchformhtml = ob_get_clean();

        // Get the renderer for this plugin.
        $output = $this->page->get_renderer('block_booking');

        // The content.
        $this->content = new stdClass();
        $this->content->text = '';

        $isstudent = !has_capability('block/booking:managesitebookingoptions', $context);

        // The search form.
        $data = new search_form_container($searchformhtml);
        $this->content->text .= $output->render_search_form_container($data);

        // Process the form data after submit button has been pressed.
        if ($fromform = $searchform->get_data()) {

            $params = self::get_search_params_from_form($fromform);

            // Create the actual table mod differently for students or teachers.
            if ($isstudent) {
                $sqldata = self::search_booking_options_student_get_sqldata($params);
                // Show search results for students.
                $results = $this->search_booking_options_student_view($sqldata);
                $data = new searchresults_student_view($results);
                $this->content->text .= $output->render_searchresults_student_view($data);
            } else {
                // Execute the search.
                $sqldata = self::search_booking_options_manager_get_sqldata($params);
                // Return the rendered table from table lib.
                list($resultstablehtml, $count) = $this->search_booking_options_manager_view($sqldata);
                // Use template on table.
                $data = new searchresults_manager_view($resultstablehtml, $count);
                // Pass the rendered html to content->text.
                $this->content->text .= $output->render_searchresults_manager_view($data);
            }
        }

        // Call JS to set pageurl. This is needed, in order not to loose course id.
        $this->page->requires->js_call_amd('block_booking/actions', 'setpageurlwithjs',
                array($this->page->url->out()));

        // Call JS to fix modal (in Moove theme it's behind the backdrop).
        $this->page->requires->js_call_amd('block_booking/actions', 'fixmodal');

        return $this->content;
    }

    /**
     * Execute SQL and return rendered table from table lib.
     * @param $sqldata
     * @return array
     * @throws moodle_exception
     */
    private function search_booking_options_manager_view($sqldata):array {

        global $CFG, $DB;

        $resultstable = new bookingoptions_simple_table('block_booking_resultstable');

        // Define the list of columns to show.
        $columns = [
            'text', 'course', 'coursestarttime', 'courseendtime', 'location', 'participants',
            'waitinglist', 'manageresponses', 'link'
        ];
        $resultstable->define_columns($columns);

        // Define the titles of columns to show in header.
        $headers = [];
        foreach ($columns as $column) {
            // Use prefix 'bst' (meaning: bookingoptions_simple_table).
            $headers[] = get_string('bst' . $column, 'mod_booking');
        }
        $resultstable->define_headers($headers);

        $resultstable->is_downloading(false); // This is necessary to show the download button.
        $resultstable->set_sql($sqldata['fields'], $sqldata['from'], $sqldata['where'], $sqldata['params']);
        
        // As it seems, we do not need any URL params for this to work.
        $baseurl = new moodle_url("$CFG->wwwroot/blocks/booking/block_booking_table.php");

        // Write the results table to buffer and store HTML in a variable.
        ob_start();
        $resultstable->define_baseurl($baseurl);
        $resultstable->out(40, true);
        $resultstablehtml = ob_get_clean();

        $sql = 'SELECT ' . $sqldata['fields'] . ' FROM ' . $sqldata['from'] . ' WHERE '. $sqldata['where'];

        $records = $DB->get_records_sql($sql, $sqldata['params']);

        $count = count($records);

        return [$resultstablehtml, $count];
    }

    /**
     * Execute sql and return results array for student.
     * @param $sqldata
     * @return array
     * @throws dml_exception
     */
    private function search_booking_options_student_view($sqldata) {
        global $DB;
        $params = array_pop($sqldata);
        $sql = implode(' ', $sqldata);
        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Create SQL for the students view.
     * @param $params
     * @return array
     * @throws coding_exception
     * @throws dml_exception
     */
    private static function search_booking_options_student_get_sqldata($params):array {
        global $DB, $USER;

        // Get all courses where the current user is enrolled and active.
        $enrolledactivecoursesids = [];
        $enrolledactivecourses = enrol_get_users_courses($USER->id, true, ['id']);
        foreach ($enrolledactivecourses as $courserecord) {
            $enrolledactivecoursesids[] = $courserecord->id;
        }

        // Get the 'in' part of the SQL.
        list($insql, $inparams) = $DB->get_in_or_equal($enrolledactivecoursesids, SQL_PARAMS_NAMED, 'courseid_');
        if (!empty($inparams)) {
            $params = array_merge($params, $inparams);
        }

        // Generate the part needed for multi-location search.
        list($inlocationssql, $inlocationsparams) = self::generate_in_locations_sql($params['locationsarray']);
        if (!empty($inlocationsparams)) {
            $params = array_merge($params, $inlocationsparams);
        }

        $sqldata = [];
        $sqldata['select'] = "SELECT bo.id optionid, s1.cmid, bo.bookingid, bo.text, b.course courseid,
            c.fullname course, bo.location, bo.coursestarttime, bo.courseendtime, ba.waitinglist";
        $sqldata['from'] = "FROM {booking_options} bo
                LEFT JOIN {booking} b
                ON b.id = bo.bookingid
                LEFT JOIN {course} c
                ON c.id = b.course
                LEFT JOIN (SELECT cm.id cmid, cm.instance bookingid, cm.visible
                FROM {course_modules} cm
                WHERE module = (
                  SELECT id FROM {modules} WHERE name = 'booking'
                )) s1
                ON bo.bookingid = s1.bookingid
                LEFT JOIN (SELECT id, optionid, completed, waitinglist
                FROM {booking_answers}
                WHERE userid = :userid) ba
                ON ba.optionid = bo.id";
        $sqldata['where'] = "WHERE bo.bookingid <> 0
                AND s1.visible <> 0
                AND LOWER(bo.text) LIKE LOWER(:bookingoption)
                AND LOWER(c.fullname) LIKE LOWER(:course)" .
                $inlocationssql .
                "AND bo.coursestarttime >= :coursestarttime
                AND bo.courseendtime <= :courseendtime
                AND c.id $insql";

        $sqldata['params'] = $params;

        return $sqldata;
    }

    /**
     * Create sql for the teachers view.
     * @return stdClass An object containing all SQL data needed for \mod_booking\table\bookingoptions_simple_table
     */
    public static function search_booking_options_manager_get_sqldata($params): array {

        // If no form data can be fetched an empty object will be returned.
        $sqldata = [];

        // Create all parts of the SQL select query.
        $sqldata['fields'] = "bo.id optionid, s1.cmid, bo.bookingid, bo.text, b.course courseid, c.fullname course,
            bo.location, bo.coursestarttime, bo.courseendtime, p.participants, w.waitinglist";

        $sqldata['from'] = "{booking_options} bo
            LEFT JOIN {booking} b
            ON b.id = bo.bookingid
            LEFT JOIN {course} c
            ON c.id = b.course
            LEFT JOIN (
                SELECT cm.id cmid, cm.instance bookingid, cm.visible
                FROM {course_modules} cm WHERE module in (
                    SELECT id FROM {modules} WHERE name = 'booking'
                )
            ) s1
            ON bo.bookingid = s1.bookingid
            LEFT JOIN (
                SELECT ba.optionid, COUNT(ba.optionid) participants
                FROM {booking_answers} ba
                WHERE waitinglist = 0
                GROUP BY ba.optionid
            ) p ON bo.id = p.optionid
            LEFT JOIN (
                SELECT ba.optionid, COUNT(ba.optionid) waitinglist
                FROM {booking_answers} ba
                WHERE waitinglist = 1
                GROUP BY ba.optionid
            ) w ON bo.id = w.optionid";

        // Generate the part needed for multi-location search.
        list($inlocationssql, $inlocationsparams) = self::generate_in_locations_sql($params['locationsarray']);
        if (!empty($inlocationsparams)) {
            $params = array_merge($params, $inlocationsparams);
        }

        $sqldata['where'] = "bo.bookingid <> 0 AND s1.visible <> 0 AND LOWER(bo.text) LIKE LOWER(:bookingoption) 
            AND LOWER(c.fullname) LIKE LOWER(:course) " .
            $inlocationssql .
            "AND bo.coursestarttime >= :coursestarttime AND bo.courseendtime <= :courseendtime";

        $sqldata['params'] = $params;

        return $sqldata;
    }

    /**
     * Helper function to generate the 
     * "AND bo.location in (:loc1, :loc2, ...)" SQL part
     * and the according params.
     *
     * @param array $locationsarray an array of location strings
     * @return array the SQL part needed (string) and the params needed (array)
     */
    private static function generate_in_locations_sql(array $locationsarray): array {
        global $DB;

        // Generate the locations SQL part.
        if (!empty($locationsarray)) {
            list($inlocationssql, $inlocationsparams) = $DB->get_in_or_equal($locationsarray, SQL_PARAMS_NAMED, 'loc');
            $inlocationssql = "AND bo.location " . $inlocationssql;
        } else {
            $inlocationssql = "";
            $inlocationsparams = [];
        }

        return [$inlocationssql, $inlocationsparams];
    }

    /**
     * This block currently has no settings.php.
     * @return false
     */
    public function has_config() {
        return false;
    }

    /**
     * Gets all relevant params from form and puts them in an array.
     * Also adds the wildcard where needed.
     * @param object $fromform
     * @return array
     */
    public static function get_search_params_from_form(object $fromform):array {
        global $USER;

        $params = [];
        $params['userid'] = $USER->id;
        $params['course'] = "%$fromform->sfcourse%";
        $params['bookingoption'] = "%$fromform->sfbookingoption%";
        $params['locationsarray'] = $fromform->sflocation;

        // Only use from-date if checkbox is active.
        if (isset($fromform->sffromcheckbox) && $fromform->sffromcheckbox == 1) {
            $params['coursestarttime'] = $fromform->sfcoursestarttime;
        } else {
            $params['coursestarttime'] = 0;
        }

        // Only use until-date if checkbox is active.
        if (isset($fromform->sfuntilcheckbox) && $fromform->sfuntilcheckbox == 1) {
            // As courseendtime is set to time 00:00, we just add one day.
            $params['courseendtime'] = strtotime('+1 day', $fromform->sfcourseendtime);
        } else {
            $params['courseendtime'] = 9999999999;
        }

        return $params;
    }
}
