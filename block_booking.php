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
require_once(dirname(__FILE__) . '/classes/form/search_form_student.php');

use block_booking\form\search_form_student;
use block_booking\output\fullscreen_modal;
use block_booking\output\search_form;
use block_booking\output\searchresults_student;

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
     * @var string $blockname The block name.
     */
    public $blockname = '';

    /**
     * @var string $title The block title.
     */
    public $title = '';

    /**
     * @var stdClass|null $searchformstudent The student search form object.
     */
    public $searchformstudent = null;

    /**
     * @var string $searchformhtml The code of the search form to be passed to the template.
     */
    public $searchformhtml = '';

    /**
     * Initialize the internal variables and search form params.
     * @throws coding_exception
     */
    public function init() {
        $this->blockname = get_class($this);
        $this->title   = get_string('title', 'block_booking');

        // Initialize the search form.
        $this->searchformstudent = new search_form_student();

        // Collect the search form HTML in a buffer.
        ob_start();
        $this->searchformstudent->display();
        // And store it in a member variable.
        $this->searchformhtml = ob_get_clean();
    }

    /**
     * All formats are allowed for the block.
     * @return bool[]
     */
    function applicable_formats() {
        return array('all' => true);
    }

    /**
     * Get content.
     * @return stdClass|null
     * @throws coding_exception
     */
    public function get_content() {
        global $DB, $PAGE, $USER;

        if ($this->content !== null) {
            return $this->content;
        }

        // Get the renderer for this plugin.
        $output = $PAGE->get_renderer('block_booking');

        // The content.
        $this->content = new stdClass();
        $this->content->text = '';

        // Process the form data after submit button has been pressed.
        if ($fromform = $this->searchformstudent->get_data()) {
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

            // And prepare them for the template.
            $searchresultsstudent = new searchresults_student($results);
            $this->content->text .= $output->render_searchresults_student($searchresultsstudent);
        }

        // And redirect it to the search form template.
        $searchformdata = new search_form($this->searchformhtml);
        $this->content->text .= $output->render_search_form($searchformdata);

        // Add the fullscreen modal.
        $fullscreenmodaldata = new fullscreen_modal();
        $this->content->text .= $output->render_fullscreen_modal($fullscreenmodaldata);

        // The footer.
        $this->content->footer = get_string('createdbywunderbyte', 'block_booking');

        return $this->content;
    }

    /**
     * This block currently has no settings.php.
     * @return false
     */
    public function has_config() {
        return false;
    }
}
