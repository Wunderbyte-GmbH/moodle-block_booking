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
use block_booking\output\fullscreen_modal;
use block_booking\output\search_form;

/**
 * Block base class.
 *
 * @package    block
 * @subpackage booking
 * @author     David Bogner <info@wunderbyte.at>
 * @copyright  2014-2021 https://www.wunderbyte.at
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class block_booking extends block_base {

    public function init() {
        $this->blockname = get_class($this);
        $this->title   = get_string('title', 'block_booking');
    }

    function applicable_formats() {
        return array('all' => true);
    }

    public function get_content() {
        global $PAGE;

        if ($this->content !== null) {
            return $this->content;
        }

        // The Modal HTML:
        /* $modalhtml =
          '<a href="#" data-toggle="modal" data-target="#booking-block-modal">' .
                get_string("booking:viewallbookings", "block_booking") .
            '</a>
            <div id="booking-block-modal" class="modal" tabindex="-1" role="dialog"
                aria-labelledby="bookingBlockModalLabel" aria-hidden="true">
              <div class="modal-dialog">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title" id="bookingBlockModalLabel">Modal title</h5>
                    <button type="button" class="btn btn-closex" data-dismiss="modal">X</button>
                  </div>
                  <div class="modal-body">
                    Content goes here...
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-dismiss="modal">Close</button>
                  </div>
                </div>
              </div>
            </div>'; */

        // The content.
        $this->content = new stdClass();
        $this->content->text = '';

        // Get the renderer for this plugin.
        $output = $PAGE->get_renderer('block_booking');

        // Add the search form.
        $data = new search_form();
        $this->content->text .= $output->render_search_form($data);

        // Add the fullscreen modal.
        $data = new fullscreen_modal();
        $this->content->text .= $output->render_fullscreen_modal($data);

        //$this->content->text .= $modalhtml;

        // $this->content->text .= '<ul>';
        // $this->content->text .= '<li>';
        // $this->content->text .= '<a href="'.$CFG->wwwroot.'/blocks/booking/booking.php?courseid='.$COURSE->id .'">';
        // $this->content->text .= get_string('booking:viewallbookings', 'block_booking');
        // $this->content->text .= '</a>';
        // $this->content->text .= '</li>';
        // $this->content->text .= '</ul>';

        // The footer.
        $this->content->footer = '';

        return $this->content;
    }

    public function has_config() {
        return false;
    }
}