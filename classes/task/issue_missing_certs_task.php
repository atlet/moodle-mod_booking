<?php

namespace mod_booking\task;

defined('MOODLE_INTERNAL') || die();

use core\task\adhoc_task;
use mod_booking\singleton_service;

class issue_missing_certs_task extends adhoc_task {

    public function get_component() {
        return 'mod_booking';
    }

    public function execute() {
        global $DB;

        $data = (object)($this->get_custom_data() ?? []);
        $bookingid = (int)($data->bookingid ?? 0);
        $courseid  = (int)($data->courseid  ?? 0);
        $cmid      = (int)($data->cmid      ?? 0);
        $actorid   = (int)($data->actorid   ?? 0);

        if (!$bookingid || !$courseid || !$cmid) {
            mtrace('[mod_booking] issue_missing_certs_task: missing custom data.');
            return;
        }

        // Vzemi vsa manjkajoča (userid, optionid).
        $pairs = $DB->get_records_sql("
            SELECT bt.userid, bt.optionid, bt.certificateid
              FROM {booking_teachers} bt
             WHERE bt.bookingid = :bookingid
               AND (bt.certificateid IS NULL OR bt.certificateid = 0)
        ", ['bookingid' => $bookingid]);

        $issued = 0;

        foreach ($pairs as $p) {
            $bookingoption = singleton_service::get_instance_of_booking_option($cmid, $p->optionid);

            $issuedata = $bookingoption->get_data_for_certificate();

            $template = \tool_certificate\template::instance($bookingoption->booking->settings->ttemplate);

            $rn = $DB->count_records_sql("SELECT COUNT(*) FROM  {booking_teachers} WHERE bookingid = :bookingid AND userid = :userid AND certificateid IS NOT null", ['bookingid' => $bookingid, 'userid' => $p->userid]);

            if ($rn < $bookingoption->booking->settings->tmaxcerts) {
                if (!is_numeric($p->certificateid)) {
                    $cid = $template->issue_certificate(
                        $p->userid,
                        $bookingoption->booking->settings->texpires,
                        $issuedata,
                        'mod_booking',
                        $courseid
                    );

                    $DB->execute("UPDATE {booking_teachers} SET certificateid = :cid WHERE optionid = :optionid AND userid = :userid", ['cid' => $cid, 'optionid' => $p->optionid, 'userid' => $p->userid]);
                    $issued++;
                }
            }
        }

        mtrace("[mod_booking] Issued {$issued} certificates (bookingid={$bookingid}).");
    }
}
