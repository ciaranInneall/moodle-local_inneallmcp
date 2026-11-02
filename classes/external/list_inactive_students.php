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

use context_system;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use local_inneallmcp\local\course_access;
use local_inneallmcp\local\tool_guard;
use local_inneallmcp\student_resolver;

/**
 * List enrolled students with no login for N days (at-risk signal).
 *
 * @package   local_inneallmcp
 * @copyright 2026 Inneall <support@inneall.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class list_inactive_students extends external_api {
    /**
     * Parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course id (0 = all site courses the caller can see)', VALUE_DEFAULT, 0),
            'dayssinceaccess' => new external_value(PARAM_INT, 'Inactive if lastaccess older than this many days', VALUE_DEFAULT, 14),
            'limit' => new external_value(PARAM_INT, 'Max students to return', VALUE_DEFAULT, 50),
        ]);
    }

    /**
     * Execute.
     *
     * @param int $courseid
     * @param int $dayssinceaccess
     * @param int $limit
     * @return array
     */
    public static function execute(int $courseid = 0, int $dayssinceaccess = 14, int $limit = 50): array {
        global $DB;

        self::validate_parameters(self::execute_parameters(), compact('courseid', 'dayssinceaccess', 'limit'));
        tool_guard::enter('local_inneallmcp_list_inactive_students', [
            'courseid' => $courseid,
            'dayssinceaccess' => $dayssinceaccess,
        ]);
        require_capability('moodle/user:viewdetails', context_system::instance());

        $dayssinceaccess = max(1, min(365, $dayssinceaccess));
        $limit = max(1, min(200, $limit));
        $cutoff = time() - ($dayssinceaccess * DAYSECS);

        $params = ['cutoff' => $cutoff];
        $coursefilter = '';
        if ($courseid > 0) {
            $course = $DB->get_record('course', ['id' => $courseid], 'id,fullname,shortname', MUST_EXIST);
            course_access::require_course_view($courseid);
            $coursefilter = ' AND ue.userid IN (
                SELECT ue2.userid FROM {user_enrolments} ue2
                JOIN {enrol} e2 ON e2.id = ue2.enrolid
                WHERE e2.courseid = :courseid AND ue2.status = 0
            )';
            $params['courseid'] = $courseid;
        } else {
            $course = null;
        }

        $sql = "SELECT DISTINCT u.id, u.username, u.firstname, u.lastname, u.email, u.idnumber,
                       u.auth, u.suspended, u.firstaccess, u.lastaccess, u.city, u.country
                  FROM {user} u
                  JOIN {user_enrolments} ue ON ue.userid = u.id AND ue.status = 0
                  JOIN {enrol} e ON e.id = ue.enrolid AND e.status = 0
                 WHERE u.deleted = 0 AND u.suspended = 0
                   AND u.id > 1
                   AND (u.lastaccess = 0 OR u.lastaccess < :cutoff)
                   $coursefilter
              ORDER BY u.lastaccess ASC, u.id ASC";

        $users = $DB->get_records_sql($sql, $params, 0, $limit);
        $students = [];
        foreach ($users as $user) {
            $students[] = array_merge(student_resolver::map_profile($user), [
                'dayssinceaccess' => $user->lastaccess
                    ? (int) floor((time() - (int) $user->lastaccess) / DAYSECS)
                    : null,
                'neverloggedin' => empty($user->lastaccess),
            ]);
        }

        return [
            'courseid' => $courseid,
            'coursename' => $course->fullname ?? null,
            'dayssinceaccess' => $dayssinceaccess,
            'cutoff' => $cutoff,
            'count' => count($students),
            'students' => $students,
            'partial' => count($students) >= $limit,
        ];
    }

    /**
     * Returns.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'courseid' => new external_value(PARAM_INT, 'Course filter'),
            'coursename' => new external_value(PARAM_TEXT, 'Course name', VALUE_OPTIONAL, null, NULL_ALLOWED),
            'dayssinceaccess' => new external_value(PARAM_INT, 'Threshold days'),
            'cutoff' => new external_value(PARAM_INT, 'Unix cutoff'),
            'count' => new external_value(PARAM_INT, 'Returned count'),
            'students' => new external_multiple_structure(
                new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'User id'),
                    'username' => new external_value(PARAM_RAW, 'Username'),
                    'firstname' => new external_value(PARAM_TEXT, 'First name', VALUE_OPTIONAL, null, NULL_ALLOWED),
                    'lastname' => new external_value(PARAM_TEXT, 'Last name', VALUE_OPTIONAL, null, NULL_ALLOWED),
                    'fullname' => new external_value(PARAM_TEXT, 'Full name'),
                    'email' => new external_value(PARAM_RAW, 'Email', VALUE_OPTIONAL, null, NULL_ALLOWED),
                    'idnumber' => new external_value(PARAM_RAW, 'Idnumber', VALUE_OPTIONAL, null, NULL_ALLOWED),
                    'auth' => new external_value(PARAM_AUTH, 'Auth', VALUE_OPTIONAL, null, NULL_ALLOWED),
                    'suspended' => new external_value(PARAM_BOOL, 'Suspended'),
                    'firstaccess' => new external_value(PARAM_INT, 'First access'),
                    'lastaccess' => new external_value(PARAM_INT, 'Last access'),
                    'city' => new external_value(PARAM_TEXT, 'City', VALUE_OPTIONAL, null, NULL_ALLOWED),
                    'country' => new external_value(PARAM_TEXT, 'Country', VALUE_OPTIONAL, null, NULL_ALLOWED),
                    'dayssinceaccess' => new external_value(PARAM_INT, 'Days since last access', VALUE_OPTIONAL, null, NULL_ALLOWED),
                    'neverloggedin' => new external_value(PARAM_BOOL, 'Never logged in'),
                ])
            ),
            'partial' => new external_value(PARAM_BOOL, 'Hit limit'),
        ]);
    }
}
