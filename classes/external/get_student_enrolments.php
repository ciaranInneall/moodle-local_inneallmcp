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
use local_inneallmcp\student_resolver;

/**
 * List student enrolments with optional completion.
 *
 * @package   local_inneallmcp
 * @copyright 2026 Inneall <support@inneall.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_student_enrolments extends external_api {
    /**
     * Parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'userid' => new external_value(PARAM_INT, 'Moodle user id', VALUE_DEFAULT, 0),
            'username' => new external_value(PARAM_RAW, 'Username', VALUE_DEFAULT, ''),
            'email' => new external_value(PARAM_RAW, 'Email', VALUE_DEFAULT, ''),
            'idnumber' => new external_value(PARAM_RAW, 'Idnumber', VALUE_DEFAULT, ''),
            'limit' => new external_value(PARAM_INT, 'Max courses to return', VALUE_DEFAULT, 40),
            'includecompletion' => new external_value(PARAM_BOOL, 'Include completion counts', VALUE_DEFAULT, false),
            'modulescanlimit' => new external_value(PARAM_INT, 'Courses to scan for completion', VALUE_DEFAULT, 8),
        ]);
    }

    /**
     * Execute.
     *
     * @param int $userid
     * @param string $username
     * @param string $email
     * @param string $idnumber
     * @param int $limit
     * @param bool $includecompletion
     * @param int $modulescanlimit
     * @return array
     */
    public static function execute(
        int $userid = 0,
        string $username = '',
        string $email = '',
        string $idnumber = '',
        int $limit = 40,
        bool $includecompletion = false,
        int $modulescanlimit = 8
    ): array {
        self::validate_parameters(self::execute_parameters(), compact(
            'userid',
            'username',
            'email',
            'idnumber',
            'limit',
            'includecompletion',
            'modulescanlimit'
        ));
        tool_guard::enter('local_inneallmcp_get_student_enrolments');

        $user = student_resolver::resolve(
            $userid > 0 ? $userid : null,
            $username !== '' ? $username : null,
            $email !== '' ? $email : null,
            $idnumber !== '' ? $idnumber : null
        );

        $limit = max(1, min(100, $limit));
        $modulescanlimit = max(0, min(20, $modulescanlimit));

        $enrolled = enrol_get_users_courses($user->id, true, 'id,fullname,shortname,visible');
        $slice = array_slice($enrolled, 0, $limit, true);
        $rows = [];
        $i = 0;

        foreach ($slice as $course) {
            try {
                course_access::require_course_view((int) $course->id);
            } catch (\Throwable $e) {
                continue;
            }
            $completion = null;
            if ($includecompletion && $i < $modulescanlimit) {
                try {
                    $cinfo = new \completion_info($course);
                    if ($cinfo->is_enabled()) {
                        $activities = $cinfo->get_activities();
                        $tracked = count($activities);
                        $completed = 0;
                        foreach ($activities as $cm) {
                            $data = $cinfo->get_data($cm, false, $user->id);
                            if (!empty($data->completionstate)) {
                                $completed++;
                            }
                        }
                        $completion = [
                            'trackedactivities' => $tracked,
                            'completedactivities' => $completed,
                        ];
                    }
                } catch (\Throwable $e) {
                    $completion = null;
                }
            }

            $rows[] = [
                'id' => (int) $course->id,
                'fullname' => $course->fullname,
                'shortname' => $course->shortname,
                'visible' => (bool) $course->visible,
                'completion' => $completion,
            ];
            $i++;
        }

        return [
            'student' => student_resolver::map_profile($user),
            'enrolledcoursecount' => count($enrolled),
            'courses' => $rows,
            'partial' => count($enrolled) > $limit,
        ];
    }

    /**
     * Returns.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'student' => lookup_student::student_structure(),
            'enrolledcoursecount' => new external_value(PARAM_INT, 'Total enrolled'),
            'courses' => new external_multiple_structure(
                new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'Course id'),
                    'fullname' => new external_value(PARAM_TEXT, 'Full name'),
                    'shortname' => new external_value(PARAM_TEXT, 'Short name'),
                    'visible' => new external_value(PARAM_BOOL, 'Visible'),
                    'completion' => new external_single_structure([
                        'trackedactivities' => new external_value(PARAM_INT, 'Tracked'),
                        'completedactivities' => new external_value(PARAM_INT, 'Completed'),
                    ], 'Completion summary', VALUE_OPTIONAL, null, NULL_ALLOWED),
                ])
            ),
            'partial' => new external_value(PARAM_BOOL, 'More courses exist beyond limit'),
        ]);
    }
}
