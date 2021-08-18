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
namespace block_booking\output;
use plugin_renderer_base;

defined('MOODLE_INTERNAL') || die();

/**
 * A custom renderer class that extends the plugin_renderer_base and is used by the booking block.
 *
 * @package block_booking
 * @copyright 2021 Wunderbyte GmbH
 * @author Bernhard Fischer
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends plugin_renderer_base {

    /**
     * Render function for search_form template.
     * @param $data array
     * @return string
     * @throws \moodle_exception
     */
    public function render_search_form($data) {
        $o = '';
        $data = $data->export_for_template($this);
        $o .= $this->render_from_template('block_booking/search_form', $data);
        return $o;
    }

    /**
     * Render function for fullscreen_modal template.
     * @param $data array
     * @return string
     * @throws \moodle_exception
     */
    public function render_fullscreen_modal($data) {
        $o = '';
        $data = $data->export_for_template($this);
        $o .= $this->render_from_template('block_booking/fullscreen_modal', $data);
        return $o;
    }

    /**
     * Render function for searchresults_student template.
     * @param $data array
     * @return string
     * @throws \moodle_exception
     */
    public function render_searchresults_student($data) {
        $o = '';
        $data = $data->export_for_template($this);
        $o .= $this->render_from_template('block_booking/searchresults_student', $data);
        return $o;
    }
}