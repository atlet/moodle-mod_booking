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
 * This class contains a webservice function returns bookings categories.
 *
 * @package    mod_booking
 * @copyright  2022 Georg Maißer <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace mod_booking\external;

use external_api;
use external_function_parameters;
use external_multiple_structure;
use external_single_structure;
use external_value;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

/**
 * External Service for return course users.
 *
 * @package   mod_booking
 * @copyright 2022 Wunderbyte GmbH {@link http://www.wunderbyte.at}
 * @author    Andraž Prinčič
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_booking_search_users extends external_api {

    /**
     * Describes the parameters for bookings categories.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters(
            [
                'courseid' => new external_value(PARAM_INT, 'Course id'),
                'query' => new external_value(PARAM_TEXT, 'Search query', VALUE_DEFAULT, ''),
            ]
        );
    }

    /**
     * Webservice for return bookings categories.
     *
     * @return array
     */
    public static function execute($courseid = 0, $query = ''): array {
        global $DB;

        $params = self::validate_parameters(self::execute_parameters(), [
            'courseid' => $courseid,
            'query' => $query
        ]);

        $options = [];
        $options[] = 'ue.status = 0';
        $options[] = "e.courseid = {$courseid}";

        $ousers = [];

        if (!empty($query)) {
            $q = explode(' ', $query);
            foreach ($q as $key => $value) {
                $ousers[] = "u.firstname LIKE '{$value}%'";
                $ousers[] = "u.lastname LIKE '{$value}%'";
            }

            $options[] = "(" . implode(' OR ', $ousers) . ")";
        }

        $o = implode(' AND ', $options);

        $userrecords = $DB->get_records_sql(
            "SELECT
            u.id,
          u.firstname,
          u.lastname,
          u.email
        FROM
          {user_enrolments} ue
          JOIN {enrol} e ON e.id = ue.enrolid
          AND e.status = 0
          JOIN {user} u ON u.id = ue.userid
          AND u.deleted = 0
          AND u.suspended = 0
        WHERE {$o}"
        );

        $allusers = [];

        foreach ($userrecords as $userrecord) {
            $allusers[] = [
                'id' => $userrecord->id,
                'firstname' => $userrecord->firstname,
                'lastname' => $userrecord->lastname,
                'email' => $userrecord->email,
                'label' => "$userrecord->firstname $userrecord->lastname ($userrecord->email)"
            ];
        }

        return $allusers;
    }

    /**
     * Returns description of method result value.
     *
     * @return external_multiple_structure
     */
    public static function execute_returns(): external_multiple_structure {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'id' => new external_value(PARAM_INT, 'User ID'),
                    'firstname' => new external_value(PARAM_TEXT, 'Name'),
                    'lastname' => new external_value(PARAM_TEXT, 'Surname'),
                    'email' => new external_value(PARAM_TEXT, 'Email'),
                    'label' => new external_value(PARAM_TEXT, 'Label'),
                )
            )
        );
    }
}
