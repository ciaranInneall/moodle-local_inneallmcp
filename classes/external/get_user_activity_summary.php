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
 * Participation / completion summary for a student.
 *
 * @package   local_inneallmcp
 * @copyright 2026 Inneall <support@inneall.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_user_activity_summary extends external_api {
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
            'courseid' => new external_value(PARAM_INT, 'Optional course for completion detail', VALUE_DEFAULT, 0),
        ]);
    }

    /**
     * Execute.
     *
     * @param int $userid
     * @param string $username
     * @param string $email
     * @param string $idnumber
     * @param int $courseid
     * @return array
     */
    public static function execute(
        int $userid = 0,
        string $username = '',
        string $email = '',
        string $idnumber = '',
        int $courseid = 0
    ): array {
        self::validate_parameters(self::execute_parameters(), compact(
            'userid',
            'username',
            'email',
            'idnumber',
            'courseid'
        ));
        tool_guard::enter('local_inneallmcp_get_user_activity_summary');

        $user = student_resolver::resolve(
            $userid > 0 ? $userid : null,
            $username !== '' ? $username : null,
            $email !== '' ? $email : null,
            $idnumber !== '' ? $idnumber : null
        );

        if ($courseid > 0) {
            course_access::require_course_view($courseid);
            $course = get_course($courseid);
            $cinfo = new \completion_info($course);
            $activities = [];
            $completed = 0;
            if ($cinfo->is_enabled()) {
                foreach ($cinfo->get_activities() as $cm) {
                    $data = $cinfo->get_data($cm, false, $user->id);
                    $state = (int) ($data->completionstate ?? 0);
                    if ($state) {
                        $completed++;
                    }
                    $activities[] = [
                        'cmid' => (int) $cm->id,
                        'modname' => $cm->modname,
                        'name' => $cm->name,
                        'state' => $state,
                        'timecompleted' => (int) ($data->timemodified ?? 0),
                    ];
                }
            }
            $tracked = count($activities);
            return [
                'student' => student_resolver::map_profile($user),
                'mode' => 'completion',
                'courseid' => $courseid,
                'activities' => array_slice($activities, 0, 60),
                'summary' => [
                    'trackedactivities' => $tracked,
                    'completedactivities' => $completed,
                ],
                'courses' => [],
                'partial' => $tracked > 60,
            ];
        }

        $enrolled = enrol_get_users_courses($user->id, true, 'id,fullname,shortname');
        $courses = [];
        foreach (array_slice($enrolled, 0, 40, true) as $c) {
            try {
                course_access::require_course_view((int) $c->id);
            } catch (\Throwable $e) {
                continue;
            }
            $courses[] = [
                'id' => (int) $c->id,
                'fullname' => $c->fullname,
                'shortname' => $c->shortname,
            ];
        }

        return [
            'student' => student_resolver::map_profile($user),
            'mode' => 'enrolled_courses',
            'courseid' => 0,
            'activities' => [],
            'summary' => [
                'trackedactivities' => 0,
                'completedactivities' => 0,
            ],
            'courses' => $courses,
            'partial' => count($enrolled) > 40,
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
            'mode' => new external_value(PARAM_ALPHANUMEXT, 'completion or enrolled_courses'),
            'courseid' => new external_value(PARAM_INT, 'Course id when mode=completion'),
            'activities' => new external_multiple_structure(
                new external_single_structure([
                    'cmid' => new external_value(PARAM_INT, 'CM id'),
                    'modname' => new external_value(PARAM_PLUGIN, 'Module name'),
                    'name' => new external_value(PARAM_TEXT, 'Activity name'),
                    'state' => new external_value(PARAM_INT, 'Completion state'),
                    'timecompleted' => new external_value(PARAM_INT, 'Time modified'),
                ])
            ),
            'summary' => new external_single_structure([
                'trackedactivities' => new external_value(PARAM_INT, 'Tracked'),
                'completedactivities' => new external_value(PARAM_INT, 'Completed'),
            ]),
            'courses' => new external_multiple_structure(
                new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'Course id'),
                    'fullname' => new external_value(PARAM_TEXT, 'Full name'),
                    'shortname' => new external_value(PARAM_TEXT, 'Short name'),
                ])
            ),
            'partial' => new external_value(PARAM_BOOL, 'Truncated'),
        ]);
    }
}
