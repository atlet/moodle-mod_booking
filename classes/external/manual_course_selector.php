<?php

namespace mod_booking\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->libdir . '/externallib.php');

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use external_multiple_structure;

class manual_course_selector extends external_api {

    public static function execute_parameters() {
        return new external_function_parameters([
            'term' => new external_value(PARAM_RAW, 'Search term', VALUE_DEFAULT, ''),
            'page' => new external_value(PARAM_INT, 'Page number', VALUE_DEFAULT, 0),
            'limit' => new external_value(PARAM_INT, 'Limit per page', VALUE_DEFAULT, 20),
        ]);
    }

    public static function execute($term = '', $page = 0, $limit = 20) {
        global $CFG;
        require_once($CFG->dirroot . '/course/lib.php');

        $params = self::validate_parameters(self::execute_parameters(), [
            'term' => $term,
            'page' => $page,
            'limit' => $limit,
        ]);

        $offset = $params['page'] * $params['limit'];

        $totalcount = 0;
        $courses = get_courses_search(
            ['term' => $params['term']],
            'c.shortname ASC',
            $offset,
            $params['limit'],
            $totalcount,
            ['enrol/manual:enrol']
        );

        $results = [];
        foreach ($courses as $c) {
            $results[] = [
                'id' => $c->id,
                'text' => $c->shortname . ' - ' . $c->fullname
            ];
        }

        return [
            'results' => $results,
            'pagination' => [
                'more' => ($offset + $params['limit']) < $totalcount
            ]
        ];
    }

    public static function execute_returns() {
        return new external_single_structure([
            'results' => new external_multiple_structure(
                new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'Course ID'),
                    'text' => new external_value(PARAM_RAW, 'Course display text'),
                ])
            ),
            'pagination' => new external_single_structure([
                'more' => new external_value(PARAM_BOOL, 'More results available')
            ])
        ]);
    }
}
