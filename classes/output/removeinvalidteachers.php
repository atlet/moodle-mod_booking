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
 * Renderable class for removing invalid teachers.
 *
 * @package   mod_booking
 * @copyright 2024 Wunderbyte GmbH {@link http://www.wunderbyte.at}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_booking\output;

use mod_booking\booking_option;
use mod_booking\singleton_service;
use moodle_url;
use renderer_base;
use renderable;
use templatable;

/**
 * Class for displaying and managing invalid teachers.
 *
 * Teachers are considered invalid if they no longer have the required
 * profile field value that was set in the autocreate settings.
 */
class removeinvalidteachers implements renderable, templatable {

    /** @var int Course module ID */
    private $cmid;

    /** @var object Booking settings */
    private $bookingsettings;

    /** @var array Invalid teachers data */
    private $invalidteachers = [];

    /**
     * Constructor.
     *
     * @param int $cmid Course module ID
     */
    public function __construct(int $cmid) {
        $this->cmid = $cmid;
        $this->bookingsettings = singleton_service::get_instance_of_booking_settings_by_cmid($cmid);
        $this->invalidteachers = $this->get_invalid_teachers();
    }

    /**
     * Get all teachers who no longer have the required profile field value.
     *
     * @return array Array of invalid teachers with their options
     */
    public function get_invalid_teachers(): array {
        global $DB;

        $invalidteachers = [];

        // Check if autocreate is enabled and has required settings.
        if (
            empty($this->bookingsettings->autcractive) ||
            empty($this->bookingsettings->autcrprofile) ||
            empty($this->bookingsettings->autcrvalue)
        ) {
            return $invalidteachers;
        }

        $profilefield = $this->bookingsettings->autcrprofile;
        $requiredvalue = $this->bookingsettings->autcrvalue;

        // Get all teachers for this booking instance.
        $sql = "SELECT DISTINCT bt.userid,
               u.firstname, u.lastname,
               u.firstnamephonetic, u.lastnamephonetic,
               u.middlename, u.alternatename
        FROM {booking_teachers} bt
        JOIN {user} u ON u.id = bt.userid
        WHERE bt.bookingid = :bookingid
        ORDER BY u.lastname, u.firstname";

        $teachers = $DB->get_records_sql($sql, ['bookingid' => $this->bookingsettings->id]);

        foreach ($teachers as $teacher) {
            // Get user's custom profile fields.
            $customfields = profile_user_record($teacher->userid);

            // Check if the user has the required profile field with the correct value.
            $hasvalidprofile = isset($customfields->{$profilefield}) &&
                $customfields->{$profilefield} == $requiredvalue;

            if (!$hasvalidprofile) {
                // Get all options where this user is a teacher.
                $options = $DB->get_records_sql(
                    "SELECT bo.id, bo.text
                     FROM {booking_options} bo
                     JOIN {booking_teachers} bt ON bt.optionid = bo.id
                     WHERE bt.userid = :userid AND bt.bookingid = :bookingid
                     ORDER BY bo.text",
                    ['userid' => $teacher->userid, 'bookingid' => $this->bookingsettings->id]
                );

                $optionsarray = [];
                foreach ($options as $option) {
                    $optionurl = new moodle_url('/mod/booking/view.php', [
                        'id' => $this->cmid,
                        'whichview' => 'showonlyone',
                        'optionid' => $option->id,
                    ]);
                    $optionsarray[] = [
                        'optionid' => $option->id,
                        'optionname' => $option->text,
                        'optionurl' => $optionurl->out(false),
                    ];
                }

                $invalidteachers[] = [
                    'userid' => $teacher->userid,
                    'fullname' => fullname($teacher),
                    'options' => $optionsarray,
                ];
            }
        }

        return $invalidteachers;
    }

    /**
     * Remove invalid teachers from their options.
     *
     * @return int Number of teachers removed
     */
    public function remove_teachers(): int {
        global $DB;

        $count = 0;

        foreach ($this->invalidteachers as $teacher) {
            foreach ($teacher['options'] as $option) {
                // Remove from booking_teachers.
                $DB->delete_records('booking_teachers', [
                    'userid' => $teacher['userid'],
                    'optionid' => $option['optionid'],
                    'bookingid' => $this->bookingsettings->id,
                ]);

                // Remove from booking_optiondates_teachers.
                $optiondates = $DB->get_records('booking_optiondates', ['optionid' => $option['optionid']]);
                foreach ($optiondates as $optiondate) {
                    $DB->delete_records('booking_optiondates_teachers', [
                        'userid' => $teacher['userid'],
                        'optiondateid' => $optiondate->id,
                    ]);
                }

                $count++;
            }
        }

        return $count;
    }

    /**
     * Remove invalid teachers and delete their booking options.
     *
     * @return int Number of options removed
     */
    public function remove_teachers_and_options(): int {
        global $DB;

        $count = 0;
        $deletedoptions = [];

        foreach ($this->invalidteachers as $teacher) {
            foreach ($teacher['options'] as $option) {
                // Skip if already deleted (option might have multiple invalid teachers).
                if (in_array($option['optionid'], $deletedoptions)) {
                    continue;
                }

                // Use booking_option class to properly delete the option.
                $bookingoption = singleton_service::get_instance_of_booking_option($this->cmid, $option['optionid']);
                if ($bookingoption) {
                    $bookingoption->delete_booking_option();
                    $deletedoptions[] = $option['optionid'];
                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * Export data for template.
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output): array {
        $hasinvalidteachers = !empty($this->invalidteachers);

        $removeurl = new moodle_url('/mod/booking/removeinvalidteachers.php', [
            'id' => $this->cmid,
            'action' => 'remove',
            'sesskey' => sesskey(),
        ]);

        $removewithoptionsurl = new moodle_url('/mod/booking/removeinvalidteachers.php', [
            'id' => $this->cmid,
            'action' => 'removewithoptions',
            'sesskey' => sesskey(),
        ]);

        $cancelurl = new moodle_url('/mod/booking/view.php', [
            'id' => $this->cmid,
        ]);

        return [
            'cmid' => $this->cmid,
            'has_invalid_teachers' => $hasinvalidteachers,
            'invalid_teachers' => $this->invalidteachers,
            'remove_url' => $removeurl->out(false),
            'remove_with_options_url' => $removewithoptionsurl->out(false),
            'cancel_url' => $cancelurl->out(false),
        ];
    }
}
