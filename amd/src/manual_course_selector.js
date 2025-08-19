define(['jquery', 'core/ajax'], function ($, Ajax) {
    "use strict";

    return {
        /**
        * Source of data for Ajax element.
        *
        * @param {String} selector The selector of the auto complete element.
        * @param {String} query The query string.
        * @param {Function} success A callback function receiving an array of results.
        * @param {Function} failure A callback function to be called in case of failure, receiving the error message.
        * @return {Void}
        */
        transport: function (selector, query, success, failure) {
            var promise = Ajax.call([{
                methodname: 'mod_booking_manual_course_selector',
                args: { term: query, page: 0, limit: 20 }
            }]);

            promise[0].done(function (data) {
                success(data.results);
            });
            promise[0].fail(failure);

            return promise;
        },

        /**
         * Process the results for auto complete elements.
         *
         * @param {String} selector The selector of the auto complete element.
         * @param {Array} results An array or results.
         * @return {Array} New array of results.
         */
        processResults: function (selector, results) {
            var options = [];
            $.each(results, function (index, course) {
                options.push({
                    value: course.id,
                    label: course.text
                });
            });
            return options;
        }
    };
});