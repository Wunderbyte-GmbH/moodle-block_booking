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

use block_booking\form\search_form;
use block_booking\output\search_form_container;
use block_booking\output\searchresults_manager_view;
use block_booking\output\searchresults_student_view;
use mod_booking\table\bookingoptions_simple_table;

/**
 * Block base class.
 *
 * @package    block_booking
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

        // Determine if current user is a student or not.
        $isstudent = !has_capability('block/booking:managesitebookingoptions', $context);

        if ($this->content !== null) {
            return $this->content;
        }

        // Initialize the search form.
        $searchform = new search_form($context);

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

        // The search form.
        $data = new search_form_container($searchformhtml); // TODO: hier $isstudent übergeben => Filter für booked options nur für student-Ansicht implementieren.
        $this->content->text .= $output->render_search_form_container($data);

        // Process the form data after submit button has been pressed.
        if ($fromform = $searchform->get_data()) {

            $params = self::get_search_params_from_form($fromform);

            set_user_preference('sfcoursestarttime', $params['coursestarttime']);
            set_user_preference('sfcourseendtime', $params['courseendtime']);

            // Create the actual table mod differently for students or teachers.
            if ($isstudent) {
                list($sqldata, $inactivecoursesids) = self::search_booking_options_student_get_sqldata($params);
                // Show search results for students.
                $results = $this->search_booking_options_student_view($sqldata);
                $data = new searchresults_student_view($results, $inactivecoursesids);
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
     * @param array $sqldata
     * @return array rendered table and count of found records
     * @throws moodle_exception
     */
    private function search_booking_options_manager_view(array $sqldata):array {

        global $CFG, $DB;

        $resultstable = new bookingoptions_simple_table('block_booking_resultstable');

        // Define the list of columns to show.
        $columns = [
            'course', 'text', 'coursestarttime', 'courseendtime', 'location', 'teacher', 'participants',
            'waitinglist', 'manageresponses', 'link'
        ];
        $resultstable->define_columns($columns);

        // Define the titles of columns to show in header.
        $headers = [];
        foreach ($columns as $column) {
            // Prefix bst means bookingoptions_simple_table.
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
     * @param array $sqldata
     * @return array records found in DB
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
     * @param array $params parameters to be used in the SQL query
     * @return array [array $sqldata, array $inactivecoursesids]
     * @throws coding_exception
     * @throws dml_exception
     */
    private static function search_booking_options_student_get_sqldata($params):array {
        global $DB, $USER;

        // Get all courses where the current user is enrolled and active.
        $visiblecoursesids = [];
        $visiblecourses = enrol_get_users_courses($USER->id, true, ['id']);
        foreach ($visiblecourses as $courserecord) {
            $visiblecoursesids[] = $courserecord->id;
        }

        /* If the global setting to show additional bookings is active
         * then show additional bookings of courses even if the user is not actually enrolled in them.
         * If the value of the selected user profile field corresponds with the name of a group within a course,
         * the user can see all the booking options from any booking instance within this course. */
        $fieldid = get_config('block_booking', 'userinfofield');

        // Check the "Booked modules only" filter.
        if (isset($params['bookedmodulesonly']) && $params['bookedmodulesonly'] === 1) {
            $bookedmodulesonly = true;
        } else {
            $bookedmodulesonly = false;
        }

        // Store course ids of courses to which the user is not enrolled.
        $inactivecoursesids = [];

        if (!empty($fieldid) && !$bookedmodulesonly) {

            $additionalcourses = $DB->get_records_sql(
                "SELECT DISTINCT courseid FROM {groups} WHERE name IN (
                    SELECT data FROM {user_info_data}
                    WHERE userid = :userid and fieldid = :fieldid
                )", ['fieldid' => $fieldid, 'userid' => $USER->id]);

            foreach ($additionalcourses as $additionalcourse) {

                $currentid = $additionalcourse->courseid;

                // Track inactive courses in an extra array.
                // This will be needed for the template.
                if (!in_array($currentid, $visiblecoursesids)) {
                    $inactivecoursesids[] = $currentid;
                }

                // Add the additional courses.
                $visiblecoursesids[] = $currentid;
            }
        }

        // Get the 'in' part of the SQL for visible courses.
        if (!empty($visiblecoursesids)) {
            list($insql, $inparams) = $DB->get_in_or_equal($visiblecoursesids, SQL_PARAMS_NAMED, 'courseid_');
            if (!empty($inparams)) {
                $params = array_merge($params, $inparams);
            }
            $andcourseid = "AND c.id $insql";
        } else {
            // No courses visible, so don't show any.
            $andcourseid = 'AND c.id = null';
        }

        // Generate the part needed for multi-location search.
        list($inlocationssql, $inlocationsparams) = self::generate_in_locations_sql($params['locationsarray']);
        if (!empty($inlocationsparams)) {
            $params = array_merge($params, $inlocationsparams);
        }

        // Generate the part needed for non-location search.
        list($nonlocationssql, $nonlocationsparams) = self::generate_non_locations_sql($params['nonlocationsarray']);
        if (!empty($nonlocationsparams)) {
            $params = array_merge($params, $nonlocationsparams);
        }

        // Generate the "AND..." part needed for teacher search.
        $andteacher = ''; // Empty by default.
        if (!empty($params['teacherid'])) {
            $andteacher = 'AND bt.userid = :teacherid';
            $params = array_merge($params, ['teacherid' => $params['teacherid']]);
        }

        $sqldata = [];
        $sqldata['select'] = "SELECT DISTINCT bo.id optionid, s1.cmid, bo.bookingid, bo.text, b.course courseid,
            c.fullname course, bo.location, bo.coursestarttime, bo.courseendtime, bo.description, ba.waitinglist";
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
                ON ba.optionid = bo.id
                LEFT JOIN {booking_optiondates} bod
                ON bo.bookingid = bod.bookingid AND bo.id = bod.optionid
                LEFT JOIN {booking_teachers} bt
                ON bo.bookingid = bt.bookingid AND bo.id = bt.optionid";
        $sqldata['where'] = "WHERE bo.bookingid <> 0
                AND s1.visible <> 0
                AND LOWER(bo.text) LIKE LOWER(:bookingoption)
                AND LOWER(c.fullname) LIKE LOWER(:course)
                $inlocationssql
                $nonlocationssql
                AND ((bo.coursestarttime >= :coursestarttime AND bo.courseendtime <= :courseendtime) OR
                (bod.coursestarttime >= :coursestarttime2 AND bod.courseendtime <= :courseendtime2))
                $andcourseid
                $andteacher
                ORDER BY b.course ASC, bo.text ASC, bo.coursestarttime ASC";

        // Params cannot be used twice, so we need to add them again.
        $params = array_merge($params, ['coursestarttime2' => $params['coursestarttime'],
                                        'courseendtime2' => $params['courseendtime']]);

        $sqldata['params'] = $params;

        return [$sqldata, $inactivecoursesids];
    }

    /**
     * Create SQL for the teachers view.
     * @param array $params parameters to be used in the SQL query
     * @return array An array containing all SQL data needed for \mod_booking\table\bookingoptions_simple_table
     */
    public static function search_booking_options_manager_get_sqldata($params): array {

        // If no form data can be fetched an empty object will be returned.
        $sqldata = [];

        // Create all parts of the SQL select query.
        $sqldata['fields'] = "DISTINCT bo.id optionid, s1.cmid, bo.bookingid, bo.text, b.course courseid, c.fullname course,
            bo.location, bo.coursestarttime, bo.courseendtime, bo.description, p.participants, w.waitinglist";

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
            ) w ON bo.id = w.optionid
            LEFT JOIN {booking_optiondates} bod
            ON bo.bookingid = bod.bookingid AND bo.id = bod.optionid
            LEFT JOIN {booking_teachers} bt
            ON bo.bookingid = bt.bookingid AND bo.id = bt.optionid";

        // Generate the part needed for multi-location search.
        list($inlocationssql, $inlocationsparams) = self::generate_in_locations_sql($params['locationsarray']);
        if (!empty($inlocationsparams)) {
            $params = array_merge($params, $inlocationsparams);
        }

        // Generate the part needed for non-location search.
        list($nonlocationssql, $nonlocationsparams) = self::generate_non_locations_sql($params['nonlocationsarray']);
        if (!empty($nonlocationsparams)) {
            $params = array_merge($params, $nonlocationsparams);
        }

        // Generate the "AND..." part needed for teacher search.
        $andteacher = ''; // Empty by default.
        if (!empty($params['teacherid'])) {
            $andteacher = 'AND bt.userid = :teacherid';
            $params = array_merge($params, ['teacherid' => $params['teacherid']]);
        }

        $sqldata['where'] = "bo.bookingid <> 0 AND s1.visible <> 0 AND LOWER(bo.text) LIKE LOWER(:bookingoption)
            AND LOWER(c.fullname) LIKE LOWER(:course) " .
            $inlocationssql .
            $nonlocationssql .
            "AND ((bo.coursestarttime >= :coursestarttime AND bo.courseendtime <= :courseendtime) " .
            "OR (bod.coursestarttime >= :coursestarttime2 AND bod.courseendtime <= :courseendtime2)) " .
            $andteacher .
            " ORDER BY b.course ASC, bo.text ASC, bo.coursestarttime ASC";

        // Params cannot be used twice, so we need to add them again.
        $params = array_merge($params, ['coursestarttime2' => $params['coursestarttime'],
                                        'courseendtime2' => $params['courseendtime']]);

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
     * Helper function to generate the
     * "AND bo.location NOT IN (:loc1, :loc2, ...)" SQL part
     * and the according params.
     *
     * @param array $nonlocationsarray an array of (negative) location strings
     * @return array the SQL part needed (string) and the params needed (array)
     */
    private static function generate_non_locations_sql(array $nonlocationsarray): array {
        global $DB;

        // Generate the locations SQL part.
        if (!empty($nonlocationsarray)) {
            list($nonlocationssql, $nonlocationsparams) = $DB->get_in_or_equal($nonlocationsarray, SQL_PARAMS_NAMED, 'nonloc');
            $nonlocationssql = "AND bo.location NOT " . $nonlocationssql;
        } else {
            $nonlocationssql = "";
            $nonlocationsparams = [];
        }

        return [$nonlocationssql, $nonlocationsparams];
    }

    /**
     * This will tell Moodle that the block has a settings.php.
     * @return true
     */
    public function has_config() {
        return true;
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
        $params['nonlocationsarray'] = $fromform->sfnonlocation;
        $params['teacherid'] = $fromform->sfteacher;

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

        // Check if the global setting to show additional bookings is active.
        if (!empty(get_config('block_booking', 'userinfofield')) && isset($fromform->sfbookedmodulesonly)) {
            // We only need to set the param, if the global setting is active.
            $params['bookedmodulesonly'] = $fromform->sfbookedmodulesonly;
        }

        return $params;
    }
}
