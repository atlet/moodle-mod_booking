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
require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/booking/locallib.php');

$bname = optional_param('bname', '', PARAM_ALPHANUM);

// No guest autologin.
require_login(0, false);

use mod_booking\headmasterview_table;

$cfgbkg = \get_config('booking');

if (isset($cfgbkg->hmrfield) && isset($cfgbkg->hmrvalue) && !empty($cfgbkg->hmrfield) && !empty($cfgbkg->hmrvalue)) {
    profile_load_custom_fields($USER);

    if (isset($USER->profile[$cfgbkg->hmrfield]) && $USER->profile[$cfgbkg->hmrfield] != $cfgbkg->hmrvalue) {
        throw new moodle_exception('invalidpermission');
    }
}

$url = new moodle_url('/mod/booking/headmasterview.php');
$PAGE->set_url($url);

$course = $DB->get_record('course', array('id' => SITEID), '*', MUST_EXIST);

$mybookingsurl = new moodle_url('/mod/booking/headmasterview.php');
$PAGE->navbar->add(get_string('headmasterview', 'mod_booking'), $mybookingsurl);

$PAGE->set_pagelayout('report');
$PAGE->set_title(format_string($course->fullname));
$PAGE->set_heading(get_string('headmasterview', 'mod_booking'));

echo $OUTPUT->header();

echo $OUTPUT->box_start();

$row = new html_table_row(
    array(get_string('bookingname', 'booking'),
    "<input autocomplete=\"off\" class=\"form-control withclear rounded\" type=\"text\" id=\"bname\" name=\"bname\" value=\"{$bname}\">"
    , "", ""));
$tabledata[] = $row;
$rowclasses[] = "";

$row = new html_table_row(
    array("",
        '<div class="singlebutton"><input class="btn btn-primary" type="submit" id="searchButton" value="' .
        get_string('search') . '"></div><div class="singlebutton"></div>', "", ""));
$tabledata[] = $row;
$rowclasses[] = "";

$table = new html_table();
$table->head = array('', '', '', '');
$table->data = $tabledata;
$table->id = "tableSearch";

echo html_writer::tag('form', html_writer::table($table));

$table = new headmasterview_table('headmasterview');

$fields = "ba.id, u.id uid, u.firstname, u.lastname, u.institution, b.id bid, b.name bname, bo.id boid, bo.text botext, bo.coursestarttime, bo.courseendtime";
$from = "{booking_answers} ba
        LEFT JOIN {user} u on u.id = ba.userid
        LEFT JOIN {booking_options} bo ON ba.optionid = bo.id
        LEFT JOIN {booking} b ON b.id = bo.bookingid";
$where = "u.institution = :institution";

$params['institution'] = $USER->institution;

if (!empty($bname)) {
    $where .= " AND b.name LIKE '%{$bname}%'";
}

$table->set_sql($fields, $from, $where, $params);

$table->define_baseurl($url);
$table->out(25, true);

echo $OUTPUT->box_end();

echo $OUTPUT->footer();
