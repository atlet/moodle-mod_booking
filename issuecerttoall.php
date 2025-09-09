<?php
require(__DIR__ . '/../../config.php');

use core\task\manager as task_manager;

$cmid = required_param('cmid', PARAM_INT); // cmid
$action = optional_param('action', '', PARAM_ALPHA);
$userid = optional_param('userid', 0, PARAM_INT);

$cm = get_coursemodule_from_id('booking', $cmid, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$context = context_module::instance($cm->id);

require_login($course, false, $cm);
require_capability('mod/booking:readresponses', $context);

$PAGE->set_url(new moodle_url('/mod/booking/issuecerttoall.php', ['cmid' => $cmid]));
$PAGE->set_title(get_string('issuecerttoall', 'mod_booking'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_pagelayout('incourse');

function booking_collect_teachers_aggregate(int $bookingid): array {
    global $DB;
    $sql = "SELECT bt.userid,
                   u.id AS userid, u.firstname, u.lastname, u.email,
                   u.firstnamephonetic, u.lastnamephonetic, u.middlename, u.alternatename,
                   bt.optionid, bt.certificateid, bt.completed,
                   bo.text AS optionname
              FROM {booking_teachers} bt
              JOIN {user} u ON u.id = bt.userid
              JOIN {booking_options} bo ON bo.id = bt.optionid
             WHERE bt.bookingid = :bookingid
          ORDER BY u.lastname, u.firstname, bo.text";
    return $DB->get_records_sql($sql, ['bookingid' => $bookingid]);
}

// Actions.
if ($action === 'issueall' && confirm_sesskey()) {
    require_sesskey();

    // Oceni koliko je manjkajočih (ni nujno, a je uporabno za info).
    $missingcount = (int)$DB->count_records_select(
        'booking_teachers',
        'bookingid = :bid AND (certificateid IS NULL OR certificateid = 0)',
        ['bid' => $cm->instance]
    );

    // Ustvari in v vrsto postavi adhoc task.
    $task = new \mod_booking\task\issue_missing_certs_task();
    $task->set_custom_data([
        'courseid'  => $course->id,
        'cmid'      => $cm->id,
        'bookingid' => $cm->instance,
        'actorid'   => $USER->id
    ]);
    // Dodeli "v imenu" uporabnika (lahko pomaga pri audit trailih).
    $task->set_userid($USER->id);
    // Zaženi ASAP.
    $task->set_next_run_time(time());

    task_manager::queue_adhoc_task($task);

    \core\notification::success(get_string('queued_issue_all_ok', 'mod_booking', $missingcount));
    redirect($PAGE->url);
}

// Page output.
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('issuecerttoall', 'mod_booking'));

// Gumb z dejanskim confirm dialogom.
$btn = new single_button(
    new moodle_url($PAGE->url, ['action' => 'issueall', 'sesskey' => sesskey()]),
    get_string('issuetoall', 'mod_booking'),
    'post'
);
$btn->class = 'btn';
$btn->add_confirm_action(get_string('confirm_issue_all', 'mod_booking'));
echo $OUTPUT->render($btn);

// Nariši tabelo agregatov.
$agg = booking_collect_teachers_aggregate($cm->instance);

if (empty($agg)) {
    echo html_writer::div(get_string('no_teachers_instance', 'mod_booking'), 'alert alert-info');
    echo $OUTPUT->footer();
    exit;
}

$table = new html_table();
$table->head = [
    get_string('fullnameuser'),
    'Email',
    get_string('bookingoption', 'mod_booking'),
    get_string('hascert', 'mod_booking'),
];
//var_dump($agg); die();
foreach ($agg as $uid => $data) {

    $table->data[] = [
        fullname($data),
        s($data->email),
        $data->optionname,
        (is_null($data->certificateid) ? get_string('no') : get_string('yes'))
    ];
}

echo html_writer::table($table);
echo $OUTPUT->footer();
