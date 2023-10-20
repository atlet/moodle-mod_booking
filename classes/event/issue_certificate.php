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
 * The issue_certificate event.
 *
 * @package mod_booking
 * @copyright 2023 Wunderbyte GmbH <info@wunderbyte.at>
 * @author Andraž Prinčič
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_booking\event;

/**
 * The issue_certificate event.
 */
class issue_certificate extends \core\event\base {

    protected function init() {
        $this->data['edulevel'] = self::LEVEL_TEACHING;
        $this->data['crud'] = 'u';
        $this->data['objecttable'] = 'booking_options';
    }

    public static function get_name() {
        return get_string('issue_certificate', 'mod_booking');
    }

    public function get_description() {
        $not = "";
        if (!$this->other['issued']) {
            $not = "not ";
        }

        return "Teacher with id '{$this->userid}' {$not}issued certificate for user id '{$this->relateduserid}' in option id '{$this->objectid}'.";
    }

    public function get_url() {
        return new \moodle_url('/mod/booking/report.php', array('id' => $this->contextid, 'optionid' => $this->objectid));
    }
}