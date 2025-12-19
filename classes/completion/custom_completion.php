<?php

namespace mod_booking\completion;

use core_completion\activity_custom_completion;

/**
 * Custom completion rules for mod_booking (Moodle 4.3+ API).
 */
class custom_completion extends activity_custom_completion {

    /**
     * Modul definira ta pravila (ID-ji pravil).
     * Ključi naj se ujemajo z imeni v mod_form (brez suffixa).
     */
    public static function get_defined_custom_rules(): array {
        // En prag (npr. “mora imeti vsaj X potrditev/prijav”).
        return ['enablecompletion'];
    }

    /**
     * Opisi pravil, prikazani učencem v “pills”.
     */
    public function get_custom_rule_descriptions(): array {
        global $DB;

        $threshold = (int)$DB->get_field('booking', 'enablecompletion', ['id' => $this->cm->instance], IGNORE_MISSING);

        return [
            'enablecompletion' => get_string('completiondetail_enablecompletion', 'mod_booking', $threshold),
        ];
    }

    /**
     * Vrstni red prikaza pravil.
     */
    public function get_sort_order(): array {
        return ['enablecompletion'];
    }

    /**
     * Ali je posamezno pravilo v tej instanci sploh uporabljeno?
     * Če vrne false, se pravilo ne prikaže.
     */
    public function is_available(string $rule): bool {
        $inst = $this->cm->instance; // id instance v {booking}.
        $value = (int)$this->get_instance_setting($inst, 'enablecompletion');
        return ($rule === 'enablecompletion' && $value > 0);
    }

    /**
     * Izračun stanja za dano pravilo.
     * Vrni COMPLETION_COMPLETE ali COMPLETION_INCOMPLETE.
     */
    public function get_state(string $rule): int {
        global $DB;

        $this->validate_rule($rule);

        if ($rule !== 'enablecompletion') {
            return COMPLETION_INCOMPLETE;
        }

        $instanceid = $this->cm->instance;
        $userid     = $this->userid; // ← uporabnik iz base classa

        $threshold = (int)$DB->get_field('booking', 'enablecompletion', ['id' => $instanceid], IGNORE_MISSING);
        if ($threshold <= 0) {
            return COMPLETION_INCOMPLETE; // pravilo ni aktivno za to instanco
        }

        // Primer: šteješ potrjene prijave uporabnika za to instanco.
        $count = (int)$DB->get_field_sql(
            "SELECT COUNT(1)
               FROM {booking_answers} ba
              WHERE ba.userid = :u
                AND ba.bookingid = :bid
                AND ba.completed = 1",
            ['u' => $userid, 'bid' => $instanceid]
        );

        return ($count >= $threshold) ? COMPLETION_COMPLETE : COMPLETION_INCOMPLETE;
    }

    /**
     * Helper: preberi nastavitev instance.
     */
    protected function get_instance_setting(int $instanceid, string $field) {
        global $DB;
        return $DB->get_field('booking', $field, ['id' => $instanceid]);
    }
}
