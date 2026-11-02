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

namespace local_inneallmcp\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use local_inneallmcp\local\course_access;
use local_inneallmcp\local\tool_guard;

/**
 * List assignments past due date in a course (or site-wide sample).
 *
 * @package   local_inneallmcp
 * @copyright 2026 Inneall <support@inneall.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class list_overdue_assignments extends external_api {
    /**
     * Parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course id (required)'),
            'includewithcutoff' => new external_value(PARAM_BOOL, 'Also treat cutoffdate as overdue marker', VALUE_DEFAULT, true),
        ]);
    }

    /**
     * Execute.
     *
     * @param int $courseid
     * @param bool $includewithcutoff
     * @return array
     */
    public static function execute(int $courseid, bool $includewithcutoff = true): array {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/mod/assign/locallib.php');

        self::validate_parameters(self::execute_parameters(), [
            'courseid' => $courseid,
            'includewithcutoff' => $includewithcutoff,
        ]);
        tool_guard::enter('local_inneallmcp_list_overdue_assignments', ['courseid' => $courseid]);

        $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
        course_access::require_course_view($courseid);

        $now = time();
        $assigns = $DB->get_records_sql(
            "SELECT a.id, a.name, a.duedate, a.cutoffdate, a.gradingduedate, a.course, cm.id AS cmid
               FROM {assign} a
               JOIN {course_modules} cm ON cm.instance = a.id
               JOIN {modules} m ON m.id = cm.module AND m.name = 'assign'
              WHERE a.course = :courseid AND cm.deletioninprogress = 0 AND cm.visible = 1
                AND a.duedate > 0 AND a.duedate < :now
           ORDER BY a.duedate ASC",
            ['courseid' => $courseid, 'now' => $now]
        );

        $items = [];
        foreach ($assigns as $a) {
            $modcontext = \context_module::instance((int) $a->cmid);
            self::validate_context($modcontext);
            if (!has_capability('mod/assign:view', $modcontext)) {
                continue;
            }
            $items[] = [
                'id' => (int) $a->id,
                'cmid' => (int) $a->cmid,
                'name' => $a->name,
                'duedate' => (int) $a->duedate,
                'cutoffdate' => (int) $a->cutoffdate,
                'gradingduedate' => (int) $a->gradingduedate,
                'daysoverdue' => (int) floor(($now - (int) $a->duedate) / DAYSECS),
                'cutoffpassed' => $includewithcutoff && !empty($a->cutoffdate) && (int) $a->cutoffdate < $now,
            ];
        }

        return [
            'courseid' => $courseid,
            'coursename' => format_string($course->fullname),
            'count' => count($items),
            'assignments' => $items,
        ];
    }

    /**
     * Returns.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'courseid' => new external_value(PARAM_INT, 'Course id'),
            'coursename' => new external_value(PARAM_TEXT, 'Course name'),
            'count' => new external_value(PARAM_INT, 'Overdue count'),
            'assignments' => new external_multiple_structure(
                new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'Assign id'),
                    'cmid' => new external_value(PARAM_INT, 'CM id'),
                    'name' => new external_value(PARAM_TEXT, 'Name'),
                    'duedate' => new external_value(PARAM_INT, 'Due'),
                    'cutoffdate' => new external_value(PARAM_INT, 'Cutoff'),
                    'gradingduedate' => new external_value(PARAM_INT, 'Grading due'),
                    'daysoverdue' => new external_value(PARAM_INT, 'Days overdue'),
                    'cutoffpassed' => new external_value(PARAM_BOOL, 'Cutoff passed'),
                ])
            ),
        ]);
    }
}
