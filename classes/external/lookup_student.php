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
use core_external\external_single_structure;
use core_external\external_value;
use local_inneallmcp\local\tool_guard;
use local_inneallmcp\student_resolver;

/**
 * Resolve a student by userid / username / email / idnumber.
 *
 * @package   local_inneallmcp
 * @copyright 2026 Inneall <support@inneall.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class lookup_student extends external_api {
    /**
     * Parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'userid' => new external_value(PARAM_INT, 'Moodle user id', VALUE_DEFAULT, 0),
            'username' => new external_value(PARAM_RAW, 'Username (e.g. student number)', VALUE_DEFAULT, ''),
            'email' => new external_value(PARAM_RAW, 'Email address', VALUE_DEFAULT, ''),
            'idnumber' => new external_value(PARAM_RAW, 'Idnumber', VALUE_DEFAULT, ''),
        ]);
    }

    /**
     * Execute.
     *
     * @param int $userid
     * @param string $username
     * @param string $email
     * @param string $idnumber
     * @return array
     */
    public static function execute(int $userid = 0, string $username = '', string $email = '', string $idnumber = ''): array {
        self::validate_parameters(self::execute_parameters(), compact('userid', 'username', 'email', 'idnumber'));
        tool_guard::enter('local_inneallmcp_lookup_student', [
            'userid' => $userid,
            'username' => $username !== '' ? '(set)' : '',
            'email' => $email !== '' ? '(set)' : '',
            'idnumber' => $idnumber !== '' ? '(set)' : '',
        ]);

        $user = student_resolver::resolve(
            $userid > 0 ? $userid : null,
            $username !== '' ? $username : null,
            $email !== '' ? $email : null,
            $idnumber !== '' ? $idnumber : null
        );

        $courses = enrol_get_users_courses($user->id, true, 'id');
        return [
            'student' => student_resolver::map_profile($user),
            'enrolledcoursecount' => count($courses),
            'visibleenrolledcourses' => count($courses),
        ];
    }

    /**
     * Returns.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'student' => self::student_structure(),
            'enrolledcoursecount' => new external_value(PARAM_INT, 'Enrolled course count'),
            'visibleenrolledcourses' => new external_value(PARAM_INT, 'Visible enrolled course count'),
        ]);
    }

    /**
     * Shared student structure.
     *
     * @return external_single_structure
     */
    public static function student_structure(): external_single_structure {
        return new external_single_structure([
            'id' => new external_value(PARAM_INT, 'User id'),
            'username' => new external_value(PARAM_RAW, 'Username'),
            'firstname' => new external_value(PARAM_TEXT, 'First name', VALUE_OPTIONAL, null, NULL_NOT_ALLOWED),
            'lastname' => new external_value(PARAM_TEXT, 'Last name', VALUE_OPTIONAL, null, NULL_NOT_ALLOWED),
            'fullname' => new external_value(PARAM_TEXT, 'Full name'),
            'email' => new external_value(PARAM_RAW, 'Email', VALUE_OPTIONAL, null, NULL_NOT_ALLOWED),
            'idnumber' => new external_value(PARAM_RAW, 'Idnumber', VALUE_OPTIONAL, null, NULL_NOT_ALLOWED),
            'auth' => new external_value(PARAM_AUTH, 'Auth plugin', VALUE_OPTIONAL, null, NULL_NOT_ALLOWED),
            'suspended' => new external_value(PARAM_BOOL, 'Suspended'),
            'firstaccess' => new external_value(PARAM_INT, 'First access unix'),
            'lastaccess' => new external_value(PARAM_INT, 'Last access unix'),
            'city' => new external_value(PARAM_TEXT, 'City', VALUE_OPTIONAL, null, NULL_NOT_ALLOWED),
            'country' => new external_value(PARAM_TEXT, 'Country', VALUE_OPTIONAL, null, NULL_NOT_ALLOWED),
        ]);
    }
}
