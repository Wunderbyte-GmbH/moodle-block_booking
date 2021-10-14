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

use moodleform;

/**
 * Student search form.
 *
 * @copyright 2021 Wunderbyte GmbH {@link http://www.wunderbyte.at}
 * @author    Bernhard Fischer
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class search_form extends moodleform {

    /**
     * Defines the form fields.
     */
    public function definition() {
        global $COURSE;

        $mform = $this->_form;

        // Id can be from course or mod, so we just get it from url param.
        $id = optional_param('id', 0,PARAM_INT);

        // Important: This is needed to make the block work within courses.
        $mform->addElement('hidden', 'id', $id);
        $mform->setType('id', PARAM_INT);

        $mform->addElement('text', 'sfcourse', get_string('sfcourse', 'block_booking'));
        $mform->setType('sfcourse', PARAM_TEXT);

        $mform->addElement('text', 'sfbookingoption', get_string('sfbookingoption', 'block_booking'));
        $mform->setType('sfbookingoption', PARAM_TEXT);

        $mform->addElement('header', 'sfmorefilters', get_string('sfmorefilters', 'block_booking'));
        $mform->setType('sfmorefilters', PARAM_TEXT);
        $mform->setExpanded('sfmorefilters', false);

        $mform->addElement('text', 'sflocation', get_string('sflocation', 'block_booking'));
        $mform->setType('sflocation', PARAM_TEXT);

        $mform->addElement('checkbox', 'sftimespancheckbox', get_string('sftimespancheckbox', 'block_booking'));

        $mform->addElement('date_selector', 'sfcoursestarttime', get_string('sfcoursestarttime', 'block_booking'));
        $mform->setType('sfcoursestarttime', PARAM_INT);
        $mform->hideIf('sfcoursestarttime', 'sftimespancheckbox');

        $mform->addElement('date_selector', 'sfcourseendtime', get_string('sfcourseendtime', 'block_booking'));
        $mform->setType('sfcourseendtime', PARAM_INT);
        $mform->hideIf('sfcourseendtime', 'sftimespancheckbox');

        $this->add_action_buttons(false, get_string('sfsearchbtn', 'block_booking'));
    }
}
