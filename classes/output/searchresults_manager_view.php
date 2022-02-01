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
 * This file contains the definition for the renderable class searchresults_manager_view.
 *
 * @package   block_booking
 * @copyright 2021 Wunderbyte GmbH {@link http://www.wunderbyte.at}
 * @author    Bernhard Fischer
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_booking\output;

use coding_exception;
use renderer_base;
use renderable;
use templatable;

/**
 * This class prepares data for displaying the search results (manager view).
 *
 * @package   block_booking
 * @copyright 2021 Wunderbyte GmbH {@link http://www.wunderbyte.at}
 * @author    Bernhard Fischer
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class searchresults_manager_view implements renderable, templatable {

    /**
     * @var string $searchresultstablehtml The HTML of the results table.
     */
    public $searchresultstablehtml = '';

    /**
     * @var string|null $resultsmessage The message to be shown after search.
     */
    public $resultsmessage = null;

    /**
     * @var bool $success True when search results are found.
     */
    public $success = false;

    /**
     * Constructor to prepare the data for the search results.
     * @param string $searchresultstablehtml
     * @param int $count
     * @throws coding_exception
     */
    public function __construct($searchresultstablehtml, $count) {

        $this->searchresultstablehtml = $searchresultstablehtml;

        if ($count === -1) {
            // Do not set a results message on initializing.
            $this->resultsmessage = '';
            $this->success = false;
        } else if ($count === 0) {
            $this->resultsmessage = get_string('nosearchresults', 'block_booking');
            $this->title = get_string('title', 'block_booking');
            $this->success = false;
        } else {
            $this->resultsmessage = get_string('searchresultsfound', 'block_booking', ['count' => $count]);
            $this->title = get_string('modalheadertitle', 'block_booking', ['count' => $count]);
            $this->success = true;
        }
    }

    /**
     * Export the template parameters.
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output) {
        return array(
            'searchresultstablehtml' => $this->searchresultstablehtml,
            'success' => $this->success,
            'resultsmessage' => $this->resultsmessage,
            'title' => $this->title
        );
    }
}
