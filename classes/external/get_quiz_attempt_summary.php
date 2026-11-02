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
 * Quiz attempt rollup for a student in a course (or all enrolled courses).
 *
 * @package   local_inneallmcp
 * @copyright 2026 Inneall <support@inneall.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_quiz_attempt_summary extends external_api {
    /**
     * Parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'userid' => new external_value(PARAM_INT, 'User id', VALUE_DEFAULT, 0),
            'username' => new external_value(PARAM_RAW, 'Username', VALUE_DEFAULT, ''),
            'email' => new external_value(PARAM_RAW, 'Email', VALUE_DEFAULT, ''),
            'idnumber' => new external_value(PARAM_RAW, 'Idnumber', VALUE_DEFAULT, ''),
            'courseid' => new external_value(PARAM_INT, 'Limit to one course (0 = all enrolled)', VALUE_DEFAULT, 0),
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
        global $DB;

        self::validate_parameters(self::execute_parameters(), compact(
            'userid',
            'username',
            'email',
            'idnumber',
            'courseid'
        ));
        tool_guard::enter('local_inneallmcp_get_quiz_attempt_summary', [
            'userid' => $userid,
            'username' => $username,
            'courseid' => $courseid,
        ]);

        $user = student_resolver::resolve(
            $userid > 0 ? $userid : null,
            $username !== '' ? $username : null,
            $email !== '' ? $email : null,
            $idnumber !== '' ? $idnumber : null
        );

        $courses = enrol_get_users_courses($user->id, true, 'id,fullname,shortname');
        if ($courseid > 0) {
            $courses = array_filter($courses, static fn($c) => (int) $c->id === $courseid);
        }

        $quizzes = [];
        $attempttotal = 0;
        $finishedtotal = 0;

        foreach ($courses as $course) {
            try {
                course_access::require_course_view((int) $course->id);
            } catch (\Throwable $e) {
                continue;
            }
            $rows = $DB->get_records_sql(
                "SELECT q.id, q.name, q.course, cm.id AS cmid,
                        (SELECT COUNT(1) FROM {quiz_attempts} qa
                          WHERE qa.quiz = q.id AND qa.userid = :userid1) AS attemptcount,
                        (SELECT COUNT(1) FROM {quiz_attempts} qa
                          WHERE qa.quiz = q.id AND qa.userid = :userid2 AND qa.state = 'finished') AS finishedcount,
                        (SELECT MAX(qa.timestart) FROM {quiz_attempts} qa
                          WHERE qa.quiz = q.id AND qa.userid = :userid3) AS lastattempt
                   FROM {quiz} q
                   JOIN {course_modules} cm ON cm.instance = q.id
                   JOIN {modules} m ON m.id = cm.module AND m.name = 'quiz'
                  WHERE q.course = :courseid AND cm.deletioninprogress = 0
               ORDER BY q.name ASC",
                [
                    'courseid' => $course->id,
                    'userid1' => $user->id,
                    'userid2' => $user->id,
                    'userid3' => $user->id,
                ]
            );
            foreach ($rows as $q) {
                $cm = (object) ['id' => (int) $q->cmid];
                try {
                    course_access::require_quiz_attempt_view($cm);
                } catch (\Throwable $e) {
                    continue;
                }
                $attempttotal += (int) $q->attemptcount;
                $finishedtotal += (int) $q->finishedcount;
                $quizzes[] = [
                    'quizid' => (int) $q->id,
                    'cmid' => (int) $q->cmid,
                    'courseid' => (int) $course->id,
                    'coursename' => format_string($course->fullname),
                    'name' => format_string($q->name),
                    'attemptcount' => (int) $q->attemptcount,
                    'finishedcount' => (int) $q->finishedcount,
                    'lastattempt' => (int) ($q->lastattempt ?? 0),
                ];
            }
        }

        return [
            'student' => student_resolver::map_profile($user),
            'courseid' => $courseid,
            'quizcount' => count($quizzes),
            'attempttotal' => $attempttotal,
            'finishedtotal' => $finishedtotal,
            'quizzes' => $quizzes,
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
            'courseid' => new external_value(PARAM_INT, 'Course filter'),
            'quizcount' => new external_value(PARAM_INT, 'Quizzes scanned'),
            'attempttotal' => new external_value(PARAM_INT, 'Total attempts'),
            'finishedtotal' => new external_value(PARAM_INT, 'Finished attempts'),
            'quizzes' => new external_multiple_structure(
                new external_single_structure([
                    'quizid' => new external_value(PARAM_INT, 'Quiz id'),
                    'cmid' => new external_value(PARAM_INT, 'CM id'),
                    'courseid' => new external_value(PARAM_INT, 'Course id'),
                    'coursename' => new external_value(PARAM_TEXT, 'Course name'),
                    'name' => new external_value(PARAM_TEXT, 'Quiz name'),
                    'attemptcount' => new external_value(PARAM_INT, 'Attempts'),
                    'finishedcount' => new external_value(PARAM_INT, 'Finished'),
                    'lastattempt' => new external_value(PARAM_INT, 'Last attempt start'),
                ])
            ),
        ]);
    }
}
