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

use mod_booking\form\teachers_instance_report_form;
use mod_booking\singleton_service;
use mod_booking\table\teachers_instance_report_table;

require_once(__DIR__ . '/../../config.php');
require_once "$CFG->libdir/tablelib.php";
require_once "users_instance_report_table.php";

$cmid = required_param('cmid', PARAM_INT);
$download = optional_param('download', '', PARAM_ALPHA);

$urlparams = [
    'cmid' => $cmid
];

$params = []; // SQL params.

list($course, $cm) = get_course_and_cm_from_cmid($cmid, 'booking');
require_course_login($course, false, $cm);
$context = context_module::instance($cm->id);

$bookingid = (int) $cm->instance;

// In Moodle 4.0+ we want to turn the instance description off on every page except view.php.
$PAGE->activityheader->disable();

$PAGE->set_context($context);

$baseurl = new moodle_url('/mod/booking/users_instance_report.php', $urlparams);
$PAGE->set_url($baseurl);

if ((has_capability('mod/booking:updatebooking', $context) || has_capability('mod/booking:addeditownoption', $context)) == false) {
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('accessdenied', 'mod_booking'), 4);
    echo get_string('nopermissiontoaccesspage', 'mod_booking');
    echo $OUTPUT->footer();
    die();
}

if (!$cmidobj = $DB->get_record_sql(
    "SELECT cm.id FROM {course_modules} cm
     JOIN {modules} m
     ON m.id = cm.module
     WHERE m.name = 'booking' AND cm.id = :cmid",
    ['cmid' => $cmid]
)) {
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('error'), 4);
    echo get_string('error:invalidcmid', 'mod_booking');
    echo $OUTPUT->footer();
    die();
}

$table = new users_instance_report_table('uniqueid', $cmid);

$table->is_downloading($download, 'test', 'testing123');

if (!$table->is_downloading()) {
    // Only print headers if not asked to download data
    // Print the page header
    $PAGE->set_title(get_string('users_instance_report', 'mod_booking'));
    $PAGE->set_heading(get_string('users_instance_report', 'mod_booking'));
    echo $OUTPUT->header();
}

if ($CFG->version >= 2021051700) {
    // This only works in Moodle 3.11 and later.
    $mainuserfields = \core_user\fields::for_name()->get_sql('u')->selects;
    $mainuserfields = trim($mainuserfields, ', ');
} else {
    // This is only here to support Moodle versions earlier than 3.11.
    $mainuserfields = get_all_user_name_fields(true, 'u');
}

$fields = "ba.id, u.firstname AS firstname,
{$mainuserfields}
, u.institution, bo.id boid, bo.text botext, bo.coursestarttime, bo.courseendtime";
$from = "{booking_answers} ba
        LEFT JOIN {user} u on u.id = ba.userid
        LEFT JOIN {booking_options} bo ON ba.optionid = bo.id";

$where = "ba.bookingid = :bookingid";

$params['bookingid'] = $bookingid;

if (!empty($bname)) {
    $where .= " AND b.name LIKE '%{$bname}%'";
}

$table->set_sql($fields, $from, $where, $params);

$table->define_baseurl("{$CFG->wwwroot}/mod/booking/users_instance_report.php?cmid={$cmid}");

$table->out(40, true);

if (!$table->is_downloading()) {
    echo $OUTPUT->footer();
}
