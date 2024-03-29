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
 * De language file for the plugin.
 *
 * @package    block_booking
 * @author     David Bogner, Bernhard Fischer <info@wunderbyte.at>
 * @copyright  2014-2021 https://www.wunderbyte.at
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
$string['pluginname'] = 'Bookings Quickfinder Block';
$string['title'] = 'Buchungen suchen (Schnellsuche)';
$string['sortbycourse'] = 'Nach Kurs sortieren';
$string['sortbyuser'] = 'Nach Nutzer/in sortieren';

$string['booking:addinstance'] = 'Block "Buchungen suchen (Schnellsuche)" hinzufügen';
$string['booking:myaddinstance'] = 'Block "Buchungen suchen (Schnellsuche)" zum Dashboard hinzufügen';
$string['booking:viewallbookings'] = 'Alle Buchungen ansehen';
$string['booking:managesitebookingoptions'] = 'Alle Buchungsoptionen auf der gesamten Plattform verwalten';

// Datei: block_booking.php.
$string['createdbywunderbyte'] = 'Entwickelt mit &#128156; &nbsp;von <a href="https:www.wunderbyte.at">Wunderbyte</a>';

// Datei: search_form.mustache.
$string['sfcourse'] = 'Kurs';
$string['sfbookingoption'] = 'Buchungsoption';
$string['sfbookedmodulesonly'] = 'Nur gebuchte Module';
$string['sflocation'] = 'Ort';
$string['sfnonlocation'] = 'Nicht-Ort';
$string['sfteacher'] = 'Trainer/in';
$string['sffromcheckbox'] = 'Ab Datum suchen...';
$string['sfuntilcheckbox'] = 'Bis zu Datum suchen...';
$string['sfcoursestarttime'] = 'Von';
$string['sfcourseendtime'] = 'Bis';
$string['sfsearchbtn'] = 'Buchungen suchen';
$string['sfmorefilters'] = 'Weitere Filter...';

// Datei: searchresults_student_view.mustache.
$string['searchresultsfound'] = '{$a->count} Buchungsoptionen gefunden (zum Anzeigen anklicken)';
$string['nosearchresults'] = 'Es konnten keine Buchungsoptionen gefunden werden. Bitte probieren Sie es mit anderen (oder weniger) Filtern noch einmal...';
$string['booked'] = 'Sie haben diese Option gebucht.';
$string['onwaitinglist'] = 'Sie sind auf der Warteliste.';
$string['notenrolled'] = 'Sie sind in diesen Kurs nicht eingeschrieben.';
$string['modalheadertitle'] = '{$a->count} Buchungsoptionen gefunden';
$string['btnshow'] = 'Anzeigen';
$string['showhidedates'] = 'Termine anzeigen / verstecken...';

// File: settings.php.
$string['settingsheader'] = 'Block "Buchungen suchen (Schnellsuche)" - Einstellungen';
$string['settingsheaderdesc'] = 'Hier können Sie globale Einstellungen für alle Instanzen des Blocks "Buchungen suchen (Schnellsuche)" auf ihrer Plattform durchführen.';
$string['userinfofield'] = 'Zusätzliche Buchungen anzeigen';
$string['userinfofielddesc'] =
    'Die User können zusätzliche Buchungen von Kursen sehen, auch wenn Sie nicht in diese Kurse eingeschrieben sind.
    Wenn der Wert des ausgewählten Userprofil-Felds mit dem Namen einer Gruppe innerhalb eines Kurses übereinstimmt,
    dann werden dem User / der Userin alle Buchungsoptionen aus allen Buchungsinstanzen innerhalb dieses Kurses angezeigt.';
$string['userinfofieldoff'] = 'Nicht anzeigen';
