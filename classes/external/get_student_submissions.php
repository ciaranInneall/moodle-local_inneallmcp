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
 * Assignment submission summary for a student.
 *
 * @package   local_inneallmcp
 * @copyright 2026 Inneall <support@inneall.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_student_submissions extends external_api {
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
            'courseid' => new external_value(PARAM_INT, 'Optional course filter', VALUE_DEFAULT, 0),
            'coursescanlimit' => new external_value(PARAM_INT, 'Max courses to scan', VALUE_DEFAULT, 10),
            'assignmentspercourse' => new external_value(PARAM_INT, 'Max assignments per course', VALUE_DEFAULT, 15),
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
     * @param int $coursescanlimit
     * @param int $assignmentspercourse
     * @return array
     */
    public static function execute(
        int $userid = 0,
        string $username = '',
        string $email = '',
        string $idnumber = '',
        int $courseid = 0,
        int $coursescanlimit = 10,
        int $assignmentspercourse = 15
    ): array {
        global $CFG;
        require_once($CFG->dirroot . '/mod/assign/locallib.php');
        require_once($CFG->dirroot . '/mod/assign/externallib.php');

        self::validate_parameters(self::execute_parameters(), compact(
            'userid',
            'username',
            'email',
            'idnumber',
            'courseid',
            'coursescanlimit',
            'assignmentspercourse'
        ));
        tool_guard::enter('local_inneallmcp_get_student_submissions');

        $user = student_resolver::resolve(
            $userid > 0 ? $userid : null,
            $username !== '' ? $username : null,
            $email !== '' ? $email : null,
            $idnumber !== '' ? $idnumber : null
        );

        $enrolled = enrol_get_users_courses($user->id, true, 'id,fullname,shortname');
        $courseids = [];
        if ($courseid > 0) {
            if (isset($enrolled[$courseid])) {
                $courseids = [$courseid];
            }
        } else {
            $courseids = array_slice(array_keys($enrolled), 0, max(1, min(30, $coursescanlimit)));
        }

        $assignmentspercourse = max(1, min(40, $assignmentspercourse));
        $submissions = [];
        $scanned = 0;

        foreach ($courseids as $cid) {
            try {
                course_access::require_course_view((int) $cid);
            } catch (\Throwable $e) {
                continue;
            }
            $payload = \mod_assign_external::get_assignments([$cid], [], true);
            $courseblock = null;
            foreach ($payload['courses'] as $c) {
                if ((int) $c['id'] === (int) $cid) {
                    $courseblock = $c;
                    break;
                }
            }
            $coursename = $courseblock['fullname'] ?? ($enrolled[$cid]->fullname ?? null);
            $assigns = $courseblock['assignments'] ?? [];

            foreach (array_slice($assigns, 0, $assignmentspercourse) as $assignment) {
                $assignid = (int) ($assignment['id'] ?? 0);
                if (!$assignid) {
                    continue;
                }

                $cm = get_coursemodule_from_instance('assign', $assignid, $cid, false, MUST_EXIST);
                $modcontext = \context_module::instance($cm->id);
                if (!course_access::can_view_assign_grades($modcontext)) {
                    continue;
                }
                $scanned++;

                $assign = new \assign($modcontext, $cm, $cid);
                $submission = $assign->get_user_submission($user->id, false);
                $grade = $assign->get_user_grade($user->id, false);

                $submissions[] = [
                    'courseid' => (int) $cid,
                    'coursename' => $coursename,
                    'assignmentid' => $assignid,
                    'assignmentname' => $assignment['name'] ?? null,
                    'duedate' => (int) ($assignment['duedate'] ?? 0),
                    'submissionstatus' => $submission ? $submission->status : null,
                    'graded' => ($grade && $grade->grade !== null && $grade->grade !== '' && (float) $grade->grade >= 0),
                    'grade' => ($grade && $grade->grade !== null && $grade->grade !== '') ? (string) $grade->grade : null,
                    'submissiontimemodified' => $submission ? (int) $submission->timemodified : null,
                ];
            }
        }

        $withsubmission = count(array_filter($submissions, fn($s) => $s['submissionstatus'] !== null));
        $submitted = count(array_filter($submissions, fn($s) => $s['submissionstatus'] === 'submitted'));
        $draft = count(array_filter($submissions, fn($s) => $s['submissionstatus'] === 'draft'));
        $graded = count(array_filter($submissions, fn($s) => $s['graded'] === true));

        return [
            'student' => student_resolver::map_profile($user),
            'coursesscanned' => count($courseids),
            'assignmentsscanned' => $scanned,
            'submissions' => $submissions,
            'summary' => [
                'assignmentsscanned' => $scanned,
                'withsubmission' => $withsubmission,
                'submitted' => $submitted,
                'draft' => $draft,
                'graded' => $graded,
                'notstarted' => $scanned - $withsubmission,
            ],
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
            'coursesscanned' => new external_value(PARAM_INT, 'Courses scanned'),
            'assignmentsscanned' => new external_value(PARAM_INT, 'Assignments scanned'),
            'submissions' => new external_multiple_structure(
                new external_single_structure([
                    'courseid' => new external_value(PARAM_INT, 'Course id'),
                    'coursename' => new external_value(PARAM_TEXT, 'Course name', VALUE_OPTIONAL, null, NULL_ALLOWED),
                    'assignmentid' => new external_value(PARAM_INT, 'Assignment id'),
                    'assignmentname' => new external_value(PARAM_TEXT, 'Assignment name', VALUE_OPTIONAL, null, NULL_ALLOWED),
                    'duedate' => new external_value(PARAM_INT, 'Due date'),
                    'submissionstatus' => new external_value(PARAM_ALPHANUMEXT, 'Status', VALUE_OPTIONAL, null, NULL_ALLOWED),
                    'graded' => new external_value(PARAM_BOOL, 'Has grade'),
                    'grade' => new external_value(PARAM_RAW, 'Grade', VALUE_OPTIONAL, null, NULL_ALLOWED),
                    'submissiontimemodified' => new external_value(PARAM_INT, 'Submission modified', VALUE_OPTIONAL, null, NULL_ALLOWED),
                ])
            ),
            'summary' => new external_single_structure([
                'assignmentsscanned' => new external_value(PARAM_INT, 'Scanned'),
                'withsubmission' => new external_value(PARAM_INT, 'With submission row'),
                'submitted' => new external_value(PARAM_INT, 'Submitted'),
                'draft' => new external_value(PARAM_INT, 'Draft'),
                'graded' => new external_value(PARAM_INT, 'Graded'),
                'notstarted' => new external_value(PARAM_INT, 'Not started'),
            ]),
        ]);
    }
}
