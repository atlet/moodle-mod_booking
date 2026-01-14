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
 * Remove teachers without valid permissions page.
 *
 * This page allows admins to remove teachers who no longer have the required
 * profile field value that was set in autocreate settings.
 *
 * @package mod_booking
 * @copyright 2024 Wunderbyte GmbH <info@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_booking\output\removeinvalidteachers;
use mod_booking\singleton_service;

require_once(__DIR__ . '/../../config.php');

global $DB, $PAGE, $OUTPUT;

$cmid = required_param('id', PARAM_INT); // Course Module ID.
$action = optional_param('action', '', PARAM_ALPHA);

list($course, $cm) = get_course_and_cm_from_cmid($cmid, 'booking');
require_course_login($course, false, $cm);
$context = context_module::instance($cm->id);

// Check capability.
require_capability('mod/booking:updatebooking', $context);

// Get booking settings.
$bookingsettings = singleton_service::get_instance_of_booking_settings_by_cmid($cmid);

// Check if autcractive is enabled.
if (empty($bookingsettings->autcractive)) {
    redirect(new moodle_url('/mod/booking/view.php', ['id' => $cmid]),
        get_string('error:missingcapability', 'mod_booking'),
        null,
        \core\output\notification::NOTIFY_ERROR);
}

$baseurl = new moodle_url('/mod/booking/removeinvalidteachers.php', ['id' => $cmid]);
$PAGE->set_url($baseurl);
$PAGE->set_context($context);
$PAGE->set_title(format_string($bookingsettings->name));
$PAGE->set_heading($course->fullname);
$PAGE->set_pagelayout('incourse');

$PAGE->navbar->add(get_string('removeinvalidteachers', 'mod_booking'));

// Handle actions.
if ($action) {
    require_sesskey();

    $removeinvalidteachersobj = new removeinvalidteachers($cmid);

    if ($action === 'remove') {
        $count = $removeinvalidteachersobj->remove_teachers();
        redirect($baseurl,
            get_string('teachersremoved', 'mod_booking'),
            null,
            \core\output\notification::NOTIFY_SUCCESS);
    } else if ($action === 'removewithoptions') {
        $count = $removeinvalidteachersobj->remove_teachers_and_options();
        redirect($baseurl,
            get_string('teachersandoptionsremoved', 'mod_booking'),
            null,
            \core\output\notification::NOTIFY_SUCCESS);
    }
}

// Display the page.
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('removeinvalidteachers', 'mod_booking'));

$output = $PAGE->get_renderer('mod_booking');
$removeinvalidteachers = new removeinvalidteachers($cmid);
echo $output->render($removeinvalidteachers);

echo $OUTPUT->footer();
