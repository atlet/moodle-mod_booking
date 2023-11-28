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
namespace mod_booking\task;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/booking/lib.php');

use mod_booking\singleton_service;
use context_module;

global $CFG;

class issue_certificate extends \core\task\adhoc_task {
    /**
     * Issue certificate
     *
     * @var \stdClass
     */
    public function get_name() {
        return get_string('issuecertificate', 'mod_booking');
    }

    /**
     *
     * {@inheritdoc}
     * @see \core\task\task_base::execute()
     */
    public function execute() {
        global $DB, $USER;
        $taskdata = $this->get_custom_data();

        mtrace('issue_certificate task started');

        if ($taskdata != null) {
            $bookingoption = singleton_service::get_instance_of_booking_option($taskdata->cmid, $taskdata->optionid);

            $allusers = [];

            switch ($taskdata->type) {
                case 'issuecertificateall':
                    $allusers = $DB->get_records_sql("SELECT ba.*, b.duration bookingduration, (SELECT COALESCE(SUM(bo.duration), 0) FROM {booking_options} bo LEFT JOIN {booking_answers} baa ON baa.optionid = bo.id WHERE bo.bookingid = ba.bookingid AND baa.userid = ba.userid AND baa.completed = 1) duration FROM {booking_answers} ba LEFT JOIN {booking} b on b.id = ba.bookingid WHERE ba.optionid = :optionid AND ba.waitinglist != 5", ['optionid' => $taskdata->optionid]);
                    break;
                case 'issuecertificateconfirmed':
                    $allusers = $DB->get_records_sql("SELECT ba.*, b.duration bookingduration, (SELECT COALESCE(SUM(bo.duration), 0) FROM {booking_options} bo LEFT JOIN {booking_answers} baa ON baa.optionid = bo.id WHERE bo.bookingid = ba.bookingid AND baa.userid = ba.userid AND baa.completed = 1) duration FROM {booking_answers} ba LEFT JOIN {booking} b on b.id = ba.bookingid WHERE ba.optionid = :optionid AND ba.waitinglist != 5 AND ba.completed = 1", ['optionid' => $taskdata->optionid]);
                    break;
                case 'issuecertificateselected':
                    $allusers = $DB->get_records_sql("SELECT ba.*, b.duration bookingduration, (SELECT COALESCE(SUM(bo.duration), 0) FROM {booking_options} bo LEFT JOIN {booking_answers} baa ON baa.optionid = bo.id WHERE bo.bookingid = ba.bookingid AND baa.userid = ba.userid AND baa.completed = 1) duration FROM {booking_answers} ba LEFT JOIN {booking} b on b.id = ba.bookingid WHERE ba.optionid = :optionid AND ba.waitinglist != 5 AND ba.userid IN (" . implode(',', $taskdata->allselectedusers) . ")", ['optionid' => $taskdata->optionid]);
                    break;
            }

            $issuedcerts = 0;
            $notissued = 0;

            if (!empty($bookingoption->booking->settings->template)) {
                $issuedata = $bookingoption->get_data_for_certificate();
                $template = \tool_certificate\template::instance($bookingoption->booking->settings->template);

                foreach ($allusers as $user) {
                    $rn = $DB->count_records_sql("SELECT COUNT(*) FROM {booking_answers} WHERE bookingid = :bookingid AND userid = :userid AND certificateid IS NOT null AND waitinglist != 5", ['bookingid' => $bookingoption->booking->id, 'userid' => $user->userid]);
                    $issued = false;
                    if ($rn < $bookingoption->booking->settings->maxcerts) {
                        if (!is_numeric($user->certificateid)) {
                            $issuedata['tnofhours'] = $user->duration / 60 / 60;
                            if ($user->bookingduration < $issuedata['tnofhours']) {
                                $issuedata['tnofhours'] = $user->bookingduration;
                            }
                            $cid = $template->issue_certificate(
                                $user->userid,
                                $bookingoption->booking->settings->expires,
                                $issuedata,
                                'mod_booking',
                                $taskdata->courseid
                            );

                            $DB->execute("UPDATE {booking_answers} SET certificateid = :cid WHERE optionid = :optionid AND userid = :userid", ['cid' => $cid, 'optionid' => $taskdata->optionid, 'userid' => $user->userid]);
                            $issued = true;
                            $issuedcerts++;
                        } else {
                            $notissued++;
                        }
                    } else {
                        $notissued++;
                    }

                    $event = \mod_booking\event\issue_certificate::create([
                        'objectid' => $taskdata->optionid,
                        'context' => context_module::instance($taskdata->cmid),
                        'userid' => $USER->id,
                        'relateduserid' => $user->userid,
                        'other' => [
                            'issued' => $issued
                        ]
                    ]);

                    $event->trigger();
                }
            }

            mtrace("issue_certificate issued {$issuedcerts} and not issued {$notissued}");
        } else {
            mtrace('issue_certificate no data passed to task');
        }

        mtrace('issue_certificate task finished');
    }
}
