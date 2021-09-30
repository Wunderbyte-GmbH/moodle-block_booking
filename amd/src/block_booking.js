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
 * JS helper functions for the Bookings Quickfinder block.
 *
 * @module     block_booking/js
 * @package    block_booking
 * @copyright  2021 Wunderbyte GmbH <info@wunderbyte.at>
 * @author     Bernhard Fischer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      3.0
 */

define({
    /**
     * Set Page URL via JS.
     *
     * @param Y
     * @param pageurl The page URL including params (e.g. ?id=3).
     */
    setpageurlwithjs: function(Y, pageurl){
        const nextState = { additionalInformation: 'Updated the URL with JS' };

        // This will create a new entry in the browser's history, without reloading
        window.history.pushState(nextState, '', pageurl);

        // This will replace the current entry in the browser's history, without reloading
        window.history.replaceState(nextState, '', pageurl);
    }
});

/*
function setpageurlwithjs(Y, pageurl) {
    const nextState = { additionalInformation: 'Updated the URL with JS' };

    // This will create a new entry in the browser's history, without reloading
    window.history.pushState(nextState, '', pageurl);

    // This will replace the current entry in the browser's history, without reloading
    window.history.replaceState(nextState, '', pageurl);
}

function movemodal(Y) {

}
*/
