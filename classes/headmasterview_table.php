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
 * Headmaster reports view table
 *
 * @package mod_booking
 * @copyright 2021 Wunderbyte GmbH <info@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_booking;

use table_sql;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/tablelib.php');

/**
 * headmasterview_table class
 */
class headmasterview_table extends table_sql {

    public function __construct($uniqueid) {
        parent::__construct($uniqueid);

        $columnheaders = [
            'firstname'  => get_string('firstname'),
            'lastname'  => get_string('lastname'),
            'cfullname'  => get_string('bstcourse', 'booking'),
            'bname'       => get_string('bookingname', 'booking'),
            'botext'     => get_string('bookingoptionsmenu', 'booking'),
            'completed'  => get_string('completed', 'booking'),
            'waitinglist'   => get_string('waitinglist', 'booking'),
            'status'   => get_string('status', 'booking'),
        ];
        $this->define_columns(array_keys($columnheaders));
        $this->define_headers(array_values($columnheaders));

        $this->pageable(true);
    }
}