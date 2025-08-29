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
 * AJAX helper for the inline editing a value.
 *
 * This script is automatically included from template core/inplace_editable
 * It registers a click-listener on [data-inplaceeditablelink] link (the "inplace edit" icon),
 * then replaces the displayed value with an input field. On "Enter" it sends a request
 * to web service core_update_inplace_editable, which invokes the specified callback.
 * Any exception thrown by the web service (or callback) is displayed as an error popup.
 *
 * @module     mod_booking/bookinginstancetemplateselect
 * @copyright  2019 Andraž Prinčič
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      4.5
 */

define(['jquery', 'core/ajax', 'core/form-autocomplete', 'editor_tiny/loader'], function ($, ajax, formAutocomplete, loader) {

    /**
     * Sets the initial values for a Moodle autocomplete widget and synchronizes them with the underlying <select> element.
     *
     * This function:
     * 1. Filters the provided values to match existing <option> values (unless the select is AJAX-only).
     * 2. Maps values to their corresponding labels from <option> elements, or falls back to using the value as the label.
     * 3. Locates the related autocomplete input and sets its initial value as a JSON string.
     * 4. Updates the <select multiple> element to ensure correct POST data.
     * 5. Re-initializes the autocomplete widget to reflect the new state.
     * 6. Triggers relevant events to update UI and form state.
     *
     * @param {string} selectId - The ID of the <select> element to update.
     * @param {string[]} values - Array of values to set as selected in the autocomplete and <select> element.
     */
    function setAutocompleteValues(selectId, values) {
        const $select = $('#' + selectId);

        // 1) preveri veljavne option value (če niso AJAX-only).
        const existing = $select.find('option').map(function () { return this.value; }).get();
        const wanted = values.filter(v => !existing.length || existing.includes(v));

        // 2) zgradi mapo labelov (če obstajajo optioni) – drugače uporabi fallback label = value.
        const labelByVal = {};
        $select.find('option').each(function () {
            labelByVal[this.value] = $(this).text().trim() || this.value;
        });

        // 3) najdi povezani INPUT od autocomplete in nastavi data-initial-value
        const $felem = $select.closest('.felement[data-fieldtype="autocomplete"]');
        const $input = $felem.find('input[id^="form_autocomplete_input-"][data-fieldtype="autocomplete"]');

        const initial = wanted.map(v => ({
            value: v,
            label: labelByVal[v] || v
        }));

        // Popolnoma počisti obstoječe stanje (tudi bage).
        clearAutocomplete(selectId);

        // Nastavi initial-value JSON za widget.
        $input.attr('data-initial-value', JSON.stringify(initial));

        // 4) Vrednosti zapiši tudi v <select multiple> (da bo POST ok).
        $select.val(wanted);
        $select.find('option').prop('selected', false);
        wanted.forEach(v => $select.find(`option[value="${v}"]`).prop('selected', true));

        // 5) Ponovno “enhance-aj” kontrolnik ali poženi njegov init.
        // Če je že enhancan, ga najprej od-jarmarkiramo:
        // (preprosto sprožimo njihov init še enkrat nad felement-om)
        //formAutocomplete.enhance($felem.get(0));

        // 6) In še tipični signali za posodobitev.
        $select.trigger('change');             // zaradi POST vrednosti
        $input.trigger('change');              // zaradi oznak
        $input.trigger('input');               // za prikaz badge-ov
        $input.blur();                         // zapri menije ipd.
    }

    /**
     * Nastavi vrednosti iz obj v ustrezne elemente glede na property.
     * @param {Object} obj
     */
    function setValuesFromObj(obj) {
        var eventChange = new Event('change');

        Object.keys(obj).forEach(function (key) {
            var idel = 'id_' + key;
            var ideljq = '#id_' + key;

            if (key === 'intro') {
                idel = 'id_introeditor';
                ideljq = '#id_introeditor';
            }

            const el = document.getElementById(idel);
            if (!el) { return; }

            switch (key) {
                case 'intro':
                case 'bookedtext':
                case 'waitingtext':
                case 'notifyemail':
                case 'notifyemailteachers':
                case 'statuschangetext':
                case 'userleave':
                case 'deletedtext':
                case 'bookingchangedtext':
                case 'pollurltext':
                case 'pollurlteacherstext':
                case 'activitycompletiontext':
                case 'bookingpolicy':
                case 'beforecompletedtext':
                case 'aftercompletedtext':
                case 'beforebookedtext':
                    loader.getTinyMCE().then(function (tinyMCE) {
                        const editor = tinyMCE.get(idel); // textarea id brez #
                        editor.setContent(obj[key]);
                    });
                    break;
                case 'semesterid':
                case 'eventtype':
                case 'organizatorname':
                case 'showviews':
                case 'optionsfields':
                case 'optionsdownloadfields':
                case 'responsesfields':
                case 'reportfields':
                case 'signinsheetfields':
                case 'bookingimagescustomfield':
                case 'bookingmanager':
                    setAutocompleteValues(idel, obj[key] ? obj[key].split(',').map(function (v) { return v.trim(); }) : []);
                    break;
                // Dodaj ostale posebne primere tukaj
                default:
                    // Privzeto nastavi value
                    $(ideljq).val(obj[key]);
                    document.getElementById(idel).dispatchEvent(eventChange);
            }
        });
    }

    /**
     * Clear all selected values from a Moodle autocomplete element.
     * @param {*} selectId
     * @returns
     */
    function clearAutocomplete(selectId) {
        const $sel = $('#' + selectId);
        if (!$sel.length) {return;}

        // počisti vse izbrane option-e
        $sel.find('option:selected').prop('selected', false);

        // sproži native change, da core/form-autocomplete osveži UI
        $sel[0].dispatchEvent(new Event('change', { bubbles: true }));
    }

    return {
        init: function () {

            // Put whatever you like here. $ is available
            // to you as normal.
            $(document).on('change', '[id^=id_instancetemplateid]', function () {
                const $me = $(this);

                if ($me.val() === '') { return; }
                ajax
                    .call([{
                        methodname: 'mod_booking_instancetemplate',
                        args: { id: $me.val() },
                        done: function (data) {
                            var obj = $.parseJSON(data.template);

                            setValuesFromObj(obj);
                        }
                    }], true);
            });
        }
    };
});
