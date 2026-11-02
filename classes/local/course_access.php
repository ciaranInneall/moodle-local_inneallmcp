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

namespace local_inneallmcp\local;

use context_course;
use context_module;
use core_external\external_api;

/**
 * Per-course / per-module capability helpers for curated tools.
 *
 * @package   local_inneallmcp
 * @copyright 2026 Inneall <support@inneall.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_access {
    /**
     * Validate course context and require moodle/course:view.
     *
     * @param int $courseid
     * @return \context_course
     */
    public static function require_course_view(int $courseid): context_course {
        $context = context_course::instance($courseid);
        external_api::validate_context($context);
        require_capability('moodle/course:view', $context);
        return $context;
    }

    /**
     * True if the current user may view assignment grades in this module.
     *
     * @param \context_module $context
     * @return bool
     */
    public static function can_view_assign_grades(context_module $context): bool {
        return has_capability('mod/assign:viewgrades', $context)
            || has_capability('mod/assign:grade', $context);
    }

    /**
     * Require assignment grade view capability on the module context.
     *
     * @param \stdClass $cm Course module record
     * @return \context_module
     */
    public static function require_assign_grade_view(\stdClass $cm): context_module {
        $context = context_module::instance($cm->id);
        external_api::validate_context($context);
        if (!self::can_view_assign_grades($context)) {
            require_capability('mod/assign:viewgrades', $context);
        }
        return $context;
    }

    /**
     * Require quiz report / grade visibility on the module context.
     *
     * @param \stdClass $cm
     * @return \context_module
     */
    public static function require_quiz_attempt_view(\stdClass $cm): context_module {
        $context = context_module::instance($cm->id);
        external_api::validate_context($context);
        if (
            !has_capability('mod/quiz:viewreports', $context)
            && !has_capability('mod/quiz:grade', $context)
            && !has_capability('moodle/grade:viewall', $context)
        ) {
            require_capability('mod/quiz:viewreports', $context);
        }
        return $context;
    }

    /**
     * Require forum discussion view on the module context.
     *
     * @param \stdClass $cm
     * @return \context_module
     */
    public static function require_forum_view(\stdClass $cm): context_module {
        $context = context_module::instance($cm->id);
        external_api::validate_context($context);
        require_capability('mod/forum:viewdiscussion', $context);
        return $context;
    }
}
