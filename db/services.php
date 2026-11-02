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

/**
 * External functions and built-in service for local_inneallmcp.
 *
 * @package   local_inneallmcp
 * @copyright 2026 Inneall <support@inneall.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'local_inneallmcp_healthcheck' => [
        'classname' => 'local_inneallmcp\\external\\healthcheck',
        'methodname' => 'execute',
        'description' => 'Connectivity, setup checks, edition, and Pro upgrade hint for Inneall MCP tools',
        'type' => 'read',
        'capabilities' => 'local/inneallmcp:use',
        'services' => ['inneallmcp'],
    ],
    'local_inneallmcp_lookup_student' => [
        'classname' => 'local_inneallmcp\\external\\lookup_student',
        'methodname' => 'execute',
        'description' => 'Resolve a student by userid, username, email, or idnumber and return profile + enrolment count',
        'type' => 'read',
        'capabilities' => 'local/inneallmcp:use, moodle/user:viewdetails',
        'services' => ['inneallmcp'],
    ],
    'local_inneallmcp_get_student_enrolments' => [
        'classname' => 'local_inneallmcp\\external\\get_student_enrolments',
        'methodname' => 'execute',
        'description' => 'List courses a student is enrolled in, optionally with completion counts',
        'type' => 'read',
        'capabilities' => 'local/inneallmcp:use, moodle/user:viewdetails',
        'services' => ['inneallmcp'],
    ],
    'local_inneallmcp_get_student_submissions' => [
        'classname' => 'local_inneallmcp\\external\\get_student_submissions',
        'methodname' => 'execute',
        'description' => 'Summarise assignment submission status for a student across enrolled courses',
        'type' => 'read',
        'capabilities' => 'local/inneallmcp:use, moodle/user:viewdetails',
        'services' => ['inneallmcp'],
    ],
    'local_inneallmcp_get_user_activity_summary' => [
        'classname' => 'local_inneallmcp\\external\\get_user_activity_summary',
        'methodname' => 'execute',
        'description' => 'Participation signals: enrolled courses or per-course activity completion for a student',
        'type' => 'read',
        'capabilities' => 'local/inneallmcp:use, moodle/user:viewdetails',
        'services' => ['inneallmcp'],
    ],
    'local_inneallmcp_list_assignments' => [
        'classname' => 'local_inneallmcp\\external\\list_assignments',
        'methodname' => 'execute',
        'description' => 'List assignments in a course without requiring the service user to be enrolled',
        'type' => 'read',
        'capabilities' => 'local/inneallmcp:use',
        'services' => ['inneallmcp'],
    ],
    'local_inneallmcp_get_grading_queue' => [
        'classname' => 'local_inneallmcp\\external\\get_grading_queue',
        'methodname' => 'execute',
        'description' => 'Best-effort grading queue for a course (submitted without recorded grade)',
        'type' => 'read',
        'capabilities' => 'local/inneallmcp:use',
        'services' => ['inneallmcp'],
    ],
    'local_inneallmcp_list_inactive_students' => [
        'classname' => 'local_inneallmcp\\external\\list_inactive_students',
        'methodname' => 'execute',
        'description' => 'List enrolled students with no recent login (at-risk / inactive signal)',
        'type' => 'read',
        'capabilities' => 'local/inneallmcp:use, moodle/user:viewdetails',
        'services' => ['inneallmcp'],
    ],
    'local_inneallmcp_list_overdue_assignments' => [
        'classname' => 'local_inneallmcp\\external\\list_overdue_assignments',
        'methodname' => 'execute',
        'description' => 'List assignments past their due date in a course',
        'type' => 'read',
        'capabilities' => 'local/inneallmcp:use',
        'services' => ['inneallmcp'],
    ],
    'local_inneallmcp_get_quiz_attempt_summary' => [
        'classname' => 'local_inneallmcp\\external\\get_quiz_attempt_summary',
        'methodname' => 'execute',
        'description' => 'Quiz attempt rollup for a student across enrolled courses',
        'type' => 'read',
        'capabilities' => 'local/inneallmcp:use, moodle/user:viewdetails',
        'services' => ['inneallmcp'],
    ],
    'local_inneallmcp_get_forum_participation' => [
        'classname' => 'local_inneallmcp\\external\\get_forum_participation',
        'methodname' => 'execute',
        'description' => 'Forum discussion and post counts for a student',
        'type' => 'read',
        'capabilities' => 'local/inneallmcp:use, moodle/user:viewdetails',
        'services' => ['inneallmcp'],
    ],
];

$services = [
    'Inneall MCP Tools' => [
        'functions' => [
            'local_inneallmcp_healthcheck',
            'local_inneallmcp_lookup_student',
            'local_inneallmcp_get_student_enrolments',
            'local_inneallmcp_get_student_submissions',
            'local_inneallmcp_get_user_activity_summary',
            'local_inneallmcp_list_assignments',
            'local_inneallmcp_get_grading_queue',
            'local_inneallmcp_list_inactive_students',
            'local_inneallmcp_list_overdue_assignments',
            'local_inneallmcp_get_quiz_attempt_summary',
            'local_inneallmcp_get_forum_participation',
        ],
        'restrictedusers' => 1,
        'enabled' => 1,
        'shortname' => 'inneallmcp',
        'downloadfiles' => 0,
        'uploadfiles' => 0,
    ],
];
