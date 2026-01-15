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
 * Dynamic change semester form
 *
 * @package mod_booking
 * @copyright 2021 Wunderbyte GmbH <info@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_booking\form\condition;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once("$CFG->libdir/formslib.php");
require_once("$CFG->dirroot/mod/booking/lib.php");

use cache;
use context;
use context_system;
use core_form\dynamic_form;
use mod_booking\singleton_service;
use moodle_url;
use stdClass;

/**
 * Add holidays form.
 *
 * @copyright Wunderbyte GmbH <info@wunderbyte.at>
 * @author Bernhard Fischer
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class customform_form extends dynamic_form {

    /** @param int $id */
    private $id = null;

    /**
     * Get context for dynamic submission.
     * @return context
     */
    protected function get_context_for_dynamic_submission(): context {
        return context_system::instance();
    }

    /**
     * Check access for dynamic submission.
     * @return void
     */
    protected function check_access_for_dynamic_submission(): void {
        require_capability('mod/booking:conditionforms', context_system::instance());
    }


    /**
     * Set data for dynamic submission.
     * @return void
     */
    public function set_data_for_dynamic_submission(): void {

        global $USER;

        $data = new stdClass();

        $formdata = $this->_ajaxformdata;

        // Todo: get these values.
        $optionid = $formdata['id'];

        $cache = cache::make('mod_booking', 'conditionforms');
        $userid = $data->userid ?? $USER->id;
        $cachekey = $userid . '_' . $optionid . '_customform';

        if ($cachedata = $cache->get($cachekey)) {
            $data->customform_checkbox = $cachedata->customform_checkbox;
        }

        $this->set_data($data);
    }

    /**
     * Process dynamic submission.
     * @return stdClass|null
     */
    public function process_dynamic_submission(): stdClass {

        global $USER;

        $data = $this->get_data();

        $userid = $data->userid ?? $USER->id;

        $cache = cache::make('mod_booking', 'customformuserdata');
        $cachekey = $userid . "_" . $data->id . '_customform';

        $cache->set($cachekey, $data);

        return $data;
    }

    /**
     * Form definition.
     * @return void
     */
    public function definition(): void {
        global $DB;

        $formdata = $this->_ajaxformdata;
        $mform = $this->_form;

        $id = $formdata['id'];

        // We have to pass by the option settings.
        $settings = singleton_service::get_instance_of_booking_option_settings((int)$id);

        $mform->addElement('hidden', 'id', $id);

        // Get custom form fields from booking instance.
        $fields = [];
        if (!empty($settings->bookingid)) {
            $booking = $DB->get_record('booking', ['id' => $settings->bookingid], 'customformfields');
            if (!empty($booking->customformfields)) {
                $fields = json_decode($booking->customformfields);
            }
        }

        // Fallback: Try to get fields from booking option availability (legacy support).
        if (empty($fields)) {
            $availability = json_decode($settings->availability);
            if (!empty($availability)) {
                foreach ($availability as $condition) {
                    if ($condition->id == BO_COND_JSON_CUSTOMFORM && !empty($condition->fields)) {
                        $fields = $condition->fields;
                        break;
                    }
                }
            }
        }

        if (empty($fields)) {
            return;
        }

        // Handle fields array structure.
        $counter = 1;
        foreach ($fields as $field) {
            $fieldname = 'customform_' . $field->type . '_' . $counter;

            switch ($field->type) {
                case 'static':
                    $mform->addElement('static', $fieldname, '', $field->value);
                    break;

                case 'advcheckbox':
                    $mform->addElement('advcheckbox', $fieldname, '', $field->label);
                    break;

                case 'shorttext':
                    $mform->addElement('text', $fieldname, $field->label);
                    $mform->setType($fieldname, PARAM_TEXT);
                    if (!empty($field->required)) {
                        $mform->addRule($fieldname, get_string('required'), 'required', null, 'client');
                    }
                    break;

                case 'textarea':
                    $mform->addElement('textarea', $fieldname, $field->label, ['rows' => 3, 'cols' => 50]);
                    $mform->setType($fieldname, PARAM_TEXT);
                    if (!empty($field->required)) {
                        $mform->addRule($fieldname, get_string('required'), 'required', null, 'client');
                    }
                    break;

                case 'select':
                    $optionsarray = array_filter(explode("\n", $field->options ?? ''));
                    $optionsarray = array_map('trim', $optionsarray);
                    $options = ['' => get_string('choose')] + array_combine($optionsarray, $optionsarray);
                    $mform->addElement('select', $fieldname, $field->label, $options);
                    if (!empty($field->required)) {
                        $mform->addRule($fieldname, get_string('required'), 'required', null, 'client');
                    }
                    break;

                case 'email':
                    $mform->addElement('text', $fieldname, $field->label);
                    $mform->setType($fieldname, PARAM_EMAIL);
                    if (!empty($field->required)) {
                        $mform->addRule($fieldname, get_string('required'), 'required', null, 'client');
                    }
                    $mform->addRule($fieldname, get_string('invalidemail'), 'email', null, 'client');
                    break;

                case 'tel':
                    $mform->addElement('text', $fieldname, $field->label);
                    $mform->setType($fieldname, PARAM_TEXT);
                    if (!empty($field->required)) {
                        $mform->addRule($fieldname, get_string('required'), 'required', null, 'client');
                    }
                    break;

                case 'date':
                    $mform->addElement('date_selector', $fieldname, $field->label);
                    break;
            }

            $counter++;
        }
    }

    /**
     * Server-side form validation.
     * @param array $data
     * @param array $files
     * @return array $errors
     */
    public function validation($data, $files): array {
        global $DB;

        $errors = [];

        // Get option settings to check required fields.
        $optionid = $data['id'] ?? 0;
        if (!empty($optionid)) {
            $settings = singleton_service::get_instance_of_booking_option_settings((int)$optionid);

            // Get custom form fields from booking instance.
            $fields = [];
            if (!empty($settings->bookingid)) {
                $booking = $DB->get_record('booking', ['id' => $settings->bookingid], 'customformfields');
                if (!empty($booking->customformfields)) {
                    $fields = json_decode($booking->customformfields);
                }
            }

            // Fallback: Try to get fields from booking option availability (legacy support).
            if (empty($fields)) {
                $availability = json_decode($settings->availability);
                if (!empty($availability)) {
                    foreach ($availability as $condition) {
                        if ($condition->id == BO_COND_JSON_CUSTOMFORM && !empty($condition->fields)) {
                            $fields = $condition->fields;
                            break;
                        }
                    }
                }
            }

            // Validate required fields.
            if (!empty($fields)) {
                $counter = 1;
                foreach ($fields as $field) {
                    $fieldname = 'customform_' . $field->type . '_' . $counter;

                    // Required checkbox must be checked.
                    if ($field->type === 'advcheckbox' && !empty($field->required)) {
                        if (empty($data[$fieldname])) {
                            $errors[$fieldname] = get_string('customformnotchecked', 'mod_booking');
                        }
                    }

                    $counter++;
                }
            }
        }

        // Legacy: All checkboxes must be checked.
        foreach ($data as $key => $value) {
            if (strpos($key, 'customform_checkbox_') !== false) {
                if ($value != 1) {
                    $errors[$key] = get_string('customformnotchecked', 'mod_booking');
                }
            }
        }

        return $errors;
    }

    /**
     * Get page URL for dynamic submission.
     * @return moodle_url
     */
    protected function get_page_url_for_dynamic_submission(): moodle_url {
        return new moodle_url('/mod/booking/view.php', ['id' => $this->id]);
    }
}
