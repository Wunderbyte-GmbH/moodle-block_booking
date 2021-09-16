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
 * This file contains the definition for the renderable class searchresults_manager.
 *
 * @package   block_booking
 * @copyright 2021 Wunderbyte GmbH {@link http://www.wunderbyte.at}
 * @author    Bernhard Fischer
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_booking\output;

defined('MOODLE_INTERNAL') || die();

use moodle_url;
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
class searchresults_manager implements renderable, templatable {

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
     * @param array $results An array containing the search results.
     */
    public function __construct($searchresultstablehtml) {

        global $CFG;

        $this->searchresultstablehtml = $searchresultstablehtml;

        // Results are an array of objects but need to be typecast to an associative array so the template will work.
        /*foreach ($results as $objectentry) {

            // Prepare date string.
            if ($objectentry->coursestarttime != 0 && $objectentry->courseendtime != 0) {
                $objectentry->datestring = userdate($objectentry->coursestarttime, get_string('strftimedatetime'))
                    . ' - ' . userdate($objectentry->courseendtime, get_string('strftimedatetime'));
            }

            // Add a link to redirect to the clicked booking option.
            $link = new moodle_url($CFG->wwwroot . '/mod/booking/view.php', array(
                'id' => $objectentry->cmid,
                'optionid' => $objectentry->optionid,
                'action' => 'showonlyone',
                'whichview' => 'showonlyone'
            ));
            // Use html_entity_decode to convert "&amp;" to a simple "&" character.
            $objectentry->link = html_entity_decode($link->out());

            // Convert to array, otherwise the mustache template won't work.
            $this->resultsarray[] = (array) $objectentry;
        }*/

        // TODO: Also count the results and show appropriate messages.
        /*$count = count($this->resultsarray);
        if ($count <= 0) {
            $this->resultsmessage = get_string('nosearchresults', 'block_booking');
            $this->success = false;
        } else {*/
            // $this->resultsmessage = get_string('searchresultsfound', 'block_booking', ['count' => $count]);
            $this->resultsmessage = get_string('searchresultsfound', 'block_booking', ['count' => '???']);
            $this->success = true;
        //}
    }

    /**
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output) {
        return array(
            'searchresultstablehtml' => $this->searchresultstablehtml,
            'success' => $this->success,
            'resultsmessage' => $this->resultsmessage
        );
    }
}