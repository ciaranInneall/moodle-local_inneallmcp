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

namespace local_inneallmcp;

use context_system;
use invalid_parameter_exception;
use moodle_exception;

/**
 * Resolve a student by exactly one natural key.
 *
 * @package   local_inneallmcp
 * @copyright 2026 Inneall <support@inneall.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class student_resolver {
    /**
     * Resolve user from lookup fields.
     *
     * @param int|null $userid
     * @param string|null $username
     * @param string|null $email
     * @param string|null $idnumber
     * @return \stdClass
     */
    public static function resolve(?int $userid, ?string $username, ?string $email, ?string $idnumber): \stdClass {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/user/lib.php');

        $keys = array_filter([
            $userid !== null && $userid > 0 ? 'userid' : null,
            ($username !== null && $username !== '') ? 'username' : null,
            ($email !== null && $email !== '') ? 'email' : null,
            ($idnumber !== null && $idnumber !== '') ? 'idnumber' : null,
        ]);

        if (count($keys) !== 1) {
            throw new invalid_parameter_exception(get_string('error_lookup_required', 'local_inneallmcp'));
        }

        $context = context_system::instance();
        require_capability('local/inneallmcp:use', $context);
        require_capability('moodle/user:viewdetails', $context);

        if ($userid) {
            $user = $DB->get_record('user', ['id' => $userid, 'deleted' => 0], '*', IGNORE_MISSING);
        } else if ($username) {
            $user = $DB->get_record('user', ['username' => trim($username), 'deleted' => 0], '*', IGNORE_MISSING);
        } else if ($email) {
            $user = $DB->get_record('user', ['email' => strtolower(trim($email)), 'deleted' => 0], '*', IGNORE_MISSING);
        } else {
            $user = $DB->get_record('user', ['idnumber' => trim($idnumber), 'deleted' => 0], '*', IGNORE_MISSING);
        }

        if (!$user) {
            throw new moodle_exception('error_user_not_found', 'local_inneallmcp');
        }

        // Enforce profile visibility (same silent-filter idea as core_user_get_users_by_field).
        if (!\user_can_view_profile($user) && !has_capability('moodle/user:viewalldetails', $context)) {
            throw new moodle_exception('error_user_not_found', 'local_inneallmcp');
        }

        return $user;
    }

    /**
     * Map user to a compact profile array for AI clients.
     *
     * @param \stdClass $user
     * @return array
     */
    public static function map_profile(\stdClass $user): array {
        $email = $user->email ?? null;
        $idnumber = $user->idnumber !== '' ? $user->idnumber : null;
        if (\local_inneallmcp\local\config::redact_pii()) {
            $email = $email ? self::redact_email($email) : null;
            $idnumber = $idnumber ? self::redact_mid($idnumber) : null;
        }
        return [
            'id' => (int) $user->id,
            'username' => $user->username,
            'firstname' => $user->firstname ?? null,
            'lastname' => $user->lastname ?? null,
            'fullname' => fullname($user),
            'email' => $email,
            'idnumber' => $idnumber,
            'auth' => $user->auth ?? null,
            'suspended' => !empty($user->suspended),
            'firstaccess' => (int) ($user->firstaccess ?? 0),
            'lastaccess' => (int) ($user->lastaccess ?? 0),
            'city' => $user->city ?? null,
            'country' => $user->country ?? null,
        ];
    }

    /**
     * Mask an email address for AI client responses.
     *
     * @param string $email
     * @return string
     */
    private static function redact_email(string $email): string {
        $parts = explode('@', $email, 2);
        if (count($parts) !== 2) {
            return '***';
        }
        $local = $parts[0];
        $keep = min(2, strlen($local));
        return substr($local, 0, $keep) . '***@' . $parts[1];
    }

    /**
     * Mask an idnumber for AI client responses.
     *
     * @param string $value
     * @return string
     */
    private static function redact_mid(string $value): string {
        $len = strlen($value);
        if ($len <= 3) {
            return '***';
        }
        return substr($value, 0, 2) . str_repeat('*', max(3, $len - 4)) . substr($value, -2);
    }
}
