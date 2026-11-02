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
 * Best-effort grading queue for a course.
 *
 * @package   local_inneallmcp
 * @copyright 2026 Inneall <support@inneall.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_grading_queue extends external_api {
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
        tool_guard::enter('local_inneallmcp_get_grading_queue');
        course_access::require_course_view($courseid);

        $payload = \mod_assign_external::get_assignments([$courseid], [], true);
        $items = [];
        $scanned = 0;

        foreach ($payload['courses'] as $course) {
            if ((int) $course['id'] !== $courseid) {
                continue;
            }
            foreach (array_slice($course['assignments'] ?? [], 0, 12) as $a) {
                $aid = (int) ($a['id'] ?? 0);
                if (!$aid) {
                    continue;
                }
                $cm = get_coursemodule_from_instance('assign', $aid, $courseid, false, IGNORE_MISSING);
                if (!$cm) {
                    continue;
                }
                $modcontext = \context_module::instance($cm->id);
                if (!course_access::can_view_assign_grades($modcontext)) {
                    continue;
                }
                $scanned++;
                $subs = \mod_assign_external::get_submissions([$aid], 'submitted');
                $pending = [];
                foreach ($subs['assignments'] ?? [] as $block) {
                    if ((int) ($block['assignmentid'] ?? 0) !== $aid) {
                        continue;
                    }
                    foreach ($block['submissions'] ?? [] as $row) {
                        $grade = $row['grade'] ?? null;
                        $ungraded = ($grade === null || $grade === '' || (is_string($grade) && trim($grade) === ''));
                        if (($row['status'] ?? '') === 'submitted' && $ungraded) {
                            $pending[] = (int) ($row['userid'] ?? 0);
                        }
                    }
                }
                $items[] = [
                    'assignmentid' => $aid,
                    'name' => $a['name'] ?? null,
                    'needsgradingapprox' => count($pending),
                    'sampleuserids' => array_values(array_filter(array_slice($pending, 0, 8))),
                ];
            }
        }

        return [
            'courseid' => $courseid,
            'assignmentsscanned' => $scanned,
            'items' => $items,
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
            'assignmentsscanned' => new external_value(PARAM_INT, 'Assignments scanned'),
            'items' => new external_multiple_structure(
                new external_single_structure([
                    'assignmentid' => new external_value(PARAM_INT, 'Assignment id'),
                    'name' => new external_value(PARAM_TEXT, 'Name', VALUE_OPTIONAL, null, NULL_ALLOWED),
                    'needsgradingapprox' => new external_value(PARAM_INT, 'Approx needing grading'),
                    'sampleuserids' => new external_multiple_structure(
                        new external_value(PARAM_INT, 'User id')
                    ),
                ])
            ),
        ]);
    }
}
