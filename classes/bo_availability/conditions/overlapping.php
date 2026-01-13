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
 * Overlapping condition - prevents booking options with overlapping times.
 *
 * @package mod_booking
 * @copyright 2025 Wunderbyte GmbH
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_booking\bo_availability\conditions;

use context_system;
use mod_booking\bo_availability\bo_condition;
use mod_booking\bo_availability\bo_info;
use mod_booking\booking_option_settings;
use mod_booking\singleton_service;
use MoodleQuickForm;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/booking/lib.php');

/**
 * Condition to prevent booking overlapping options.
 *
 * This condition checks if the user has already booked another option
 * in this booking instance that has overlapping coursestarttime/courseendtime.
 *
 * @package mod_booking
 * @copyright 2025 Wunderbyte GmbH
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class overlapping implements bo_condition {

    /** @var int $id Standard Conditions have hardcoded ids. */
    public $id = BO_COND_OVERLAPPING;

    /**
     * Needed to see if class can take JSON.
     * @return bool
     */
    public function is_json_compatible(): bool {
        return false; // Hardcoded condition.
    }

    /**
     * Needed to see if it shows up in mform.
     * @return bool
     */
    public function is_shown_in_mform(): bool {
        return false;
    }

    /**
     * Determines whether a particular item is currently available
     * according to this availability condition.
     *
     * @param booking_option_settings $settings Item we're checking
     * @param int $userid User ID to check availability for
     * @param bool $not Set true if we are inverting the condition
     * @return bool True if available
     */
    public function is_available(booking_option_settings $settings, int $userid, bool $not = false): bool {
        global $DB;

        // This is the return value. Available by default.
        $isavailable = true;

        $booking = singleton_service::get_instance_of_booking_by_optionid($settings->id);

        // Check if preventoverbooking is enabled for this booking instance.
        if (empty($booking->settings->preventoverbooking)) {
            // Feature not enabled, so booking is available.
            return $not ? !$isavailable : $isavailable;
        }

        // Get the times of current option we want to book.
        $currentstarttime = $settings->coursestarttime ?? 0;
        $currentendtime = $settings->courseendtime ?? 0;

        // If the current option has no times set, we allow booking.
        if (empty($currentstarttime) || empty($currentendtime)) {
            return $not ? !$isavailable : $isavailable;
        }

        // Find all options the user has already booked in this booking instance.
        $sql = "SELECT bo.id, bo.coursestarttime, bo.courseendtime
                FROM {booking_answers} ba
                JOIN {booking_options} bo ON bo.id = ba.optionid
                WHERE ba.bookingid = :bookingid
                AND ba.userid = :userid
                AND ba.waitinglist <= :statuswaitinglist
                AND bo.id != :currentoptionid
                AND bo.coursestarttime > 0
                AND bo.courseendtime > 0";

        $params = [
            'bookingid' => $settings->bookingid,
            'userid' => $userid,
            'statuswaitinglist' => STATUSPARAM_WAITINGLIST,
            'currentoptionid' => $settings->id,
        ];

        $bookedoptions = $DB->get_records_sql($sql, $params);

        // Check each booked option for overlap with the current option.
        foreach ($bookedoptions as $bookedoption) {
            if ($this->times_overlap(
                $currentstarttime,
                $currentendtime,
                $bookedoption->coursestarttime,
                $bookedoption->courseendtime
            )) {
                $isavailable = false;
                break;
            }
        }

        // If it's inversed, we inverse.
        if ($not) {
            $isavailable = !$isavailable;
        }

        return $isavailable;
    }

    /**
     * Check if two time ranges overlap.
     *
     * Two ranges [start1, end1] and [start2, end2] overlap if:
     * start1 < end2 AND start2 < end1
     *
     * @param int $start1 Start time of first range
     * @param int $end1 End time of first range
     * @param int $start2 Start time of second range
     * @param int $end2 End time of second range
     * @return bool True if the ranges overlap
     */
    private function times_overlap(int $start1, int $end1, int $start2, int $end2): bool {
        return ($start1 < $end2) && ($start2 < $end1);
    }

    /**
     * The hard block is complementary to the is_available check.
     * While is_available is used to build eg also the prebooking modals and...
     * ... introduces eg the booking policy or the subbooking page, the hard block is meant to prevent ...
     * ... unwanted booking. It's the check just before booking if we really...
     * ... want the user to book. It will return always return false on subbookings...
     * ... as they are not necessary, but return true when the booking policy is not yet answered.
     * Hard block is only checked if is_available already returns false.
     *
     * @param booking_option_settings $settings
     * @param int $userid
     * @return bool
     */
    public function hard_block(booking_option_settings $settings, $userid): bool {
        $context = context_system::instance();
        if (has_capability('mod/booking:overrideboconditions', $context)) {
            return false;
        }

        return true;
    }

    /**
     * Obtains a string describing this restriction (whether or not
     * it actually applies). Used to obtain information that is displayed to
     * students if the activity is not available to them, and for staff to see
     * what conditions are.
     *
     * @param booking_option_settings $settings Item we're checking
     * @param int $userid User ID to check availability for
     * @param bool $full Set true if this is the 'full information' view
     * @param bool $not Set true if we are inverting the condition
     * @return array availability and Information string (for admin) about all restrictions on
     *   this item
     */
    public function get_description(booking_option_settings $settings, $userid = null, $full = false, $not = false): array {

        $description = '';

        $isavailable = $this->is_available($settings, $userid, $not);

        $description = $this->get_description_string($isavailable, $full);

        return [$isavailable, $description, BO_PREPAGE_NONE, BO_BUTTON_MYALERT];
    }

    /**
     * Only customizable functions need to return their necessary form elements.
     *
     * @param MoodleQuickForm $mform
     * @param int $optionid
     * @return void
     */
    public function add_condition_to_mform(MoodleQuickForm &$mform, int $optionid = 0) {
        // Do nothing.
    }

    /**
     * The page refers to an additional page which a booking option can inject before the booking process.
     * Not all bo_conditions need to take advantage of this. But eg a condition which requires...
     * ... the acceptance of a booking policy would render the policy with this function.
     *
     * @param int $optionid
     * @param int $userid optional user id
     * @return array
     */
    public function render_page(int $optionid, int $userid = 0) {
        return [];
    }

    /**
     * Some conditions (like price & bookit) provide a button.
     * Renders the button, attaches js to the Page footer and returns the html.
     * Return should look somehow like this.
     * ['mod_booking/bookit_button', $data];
     *
     * @param booking_option_settings $settings
     * @param int $userid
     * @param bool $full
     * @param bool $not
     * @param bool $fullwidth
     * @return array
     */
    public function render_button(booking_option_settings $settings,
        int $userid = 0, bool $full = false, bool $not = false, bool $fullwidth = true): array {

        $label = $this->get_description_string(false, $full);

        return bo_info::render_button($settings, $userid, $label, 'alert alert-warning', true, $fullwidth, 'alert', 'option');
    }

    /**
     * Helper function to return localized description strings.
     *
     * @param bool $isavailable
     * @param bool $full
     * @return string
     */
    private function get_description_string($isavailable, $full) {
        if ($isavailable) {
            $description = $full ? get_string('bo_cond_overlapping_full_available', 'mod_booking') :
                get_string('bo_cond_overlapping_available', 'mod_booking');
        } else {
            $description = $full ? get_string('bo_cond_overlapping_full_not_available', 'mod_booking') :
                get_string('bo_cond_overlapping_not_available', 'mod_booking');
        }
        return $description;
    }
}
