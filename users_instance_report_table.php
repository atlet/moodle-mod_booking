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
 * Overall report for all teachers within a booking instance.
 *
 * @package     mod_booking
 * @copyright   2022 Wunderbyte GmbH <info@wunderbyte.at>
 * @author      Bernhard Fischer
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class users_instance_report_table extends table_sql {

    private $cmid = null;

    /**
     * Constructor
     * @param int $uniqueid all tables have to have a unique id, this is used
     *      as a key when storing table properties like sort order in the session.
     */
    function __construct($uniqueid, $cmid) {
        parent::__construct($uniqueid);

        $this->cmid = $cmid;

        $columns = [
            'fullname',
            'institution',
            'botext',
            'coursestarttime',
            'courseendtime'
        ];
        $headers = [
            get_string('fullname'),
            get_string('institution', 'mod_booking'),
            get_string('bookingoption', 'mod_booking'),
            get_string('coursestarttime', 'mod_booking'),
            get_string('courseendtime', 'mod_booking')
        ];

        $this->define_columns($columns);
        $this->define_headers($headers);
    }

    /**
     * This function is called for each data row to allow processing of the
     * username value.
     *
     * @param object $values Contains object with all the values of record.
     * @return $string Return username with link to profile or username only
     *     when downloading.
     */
    function col_botext($values) {
        // If the data is being downloaded than we don't want to show HTML.
        if ($this->is_downloading()) {
            return $values->botext;
        } else {
            return '<a href="/mod/booking/report.php?id=' . $this->cmid . '&optionid=' . $values->boid . '">' . $values->botext . '</a>';
        }
    }

    function col_coursestarttime($values) {
        if ($values->coursestarttime == 0) {
            return '';
        } else {
            return userdate($values->coursestarttime, get_string('strftimedatetime', 'langconfig'));
        }
    }

    function col_courseendtime($values) {
        if ($values->courseendtime == 0) {
            return '';
        } else {
            return userdate($values->courseendtime, get_string('strftimedatetime', 'langconfig'));
        }
    }
}
