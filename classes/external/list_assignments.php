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
 * List assignments in a course (works without service-user enrolment).
 *
 * @package   local_inneallmcp
 * @copyright 2026 Inneall <support@inneall.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class list_assignments extends external_api {
    /**
     * Parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course id'),
        ]);
    }

    /**
     * Execute.
     *
     * @param int $courseid
     * @return array
     */
    public static function execute(int $courseid): array {
        global $CFG;
        require_once($CFG->dirroot . '/mod/assign/externallib.php');

        self::validate_parameters(self::execute_parameters(), ['courseid' => $courseid]);
        tool_guard::enter('local_inneallmcp_list_assignments');
        course_access::require_course_view($courseid);

        $payload = \mod_assign_external::get_assignments([$courseid], [], true);
        $assignments = [];
        foreach ($payload['courses'] as $course) {
            if ((int) $course['id'] !== $courseid) {
                continue;
            }
            foreach ($course['assignments'] ?? [] as $a) {
                $cmid = (int) ($a['cmid'] ?? 0);
                if ($cmid) {
                    $modcontext = \context_module::instance($cmid);
                    self::validate_context($modcontext);
                    if (!has_capability('mod/assign:view', $modcontext)) {
                        continue;
                    }
                }
                $assignments[] = [
                    'id' => (int) ($a['id'] ?? 0),
                    'cmid' => $cmid,
                    'name' => $a['name'] ?? null,
                    'duedate' => (int) ($a['duedate'] ?? 0),
                    'cutoffdate' => (int) ($a['cutoffdate'] ?? 0),
                    'gradingduedate' => (int) ($a['gradingduedate'] ?? 0),
                    'allowsubmissionsfromdate' => (int) ($a['allowsubmissionsfromdate'] ?? 0),
                ];
            }
        }

        return [
            'courseid' => $courseid,
            'assignments' => $assignments,
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
            'assignments' => new external_multiple_structure(
                new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'Assignment id'),
                    'cmid' => new external_value(PARAM_INT, 'Course module id'),
                    'name' => new external_value(PARAM_TEXT, 'Name', VALUE_OPTIONAL, null, NULL_ALLOWED),
                    'duedate' => new external_value(PARAM_INT, 'Due date'),
                    'cutoffdate' => new external_value(PARAM_INT, 'Cutoff'),
                    'gradingduedate' => new external_value(PARAM_INT, 'Grading due'),
                    'allowsubmissionsfromdate' => new external_value(PARAM_INT, 'Open from'),
                ])
            ),
        ]);
    }
}
