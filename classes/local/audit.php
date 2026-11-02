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

/**
 * Lightweight audit log for curated tool calls.
 *
 * @package   local_inneallmcp
 * @copyright 2026 Inneall <support@inneall.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class audit {
    /**
     * Record a successful (or denied) tool invocation.
     *
     * @param string $function WS function name
     * @param array $params Safe summary params (no secrets)
     * @param string $status ok|denied|error
     */
    public static function log(string $function, array $params = [], string $status = 'ok'): void {
        global $DB, $USER;

        if (!config::audit_enabled()) {
            return;
        }

        if (!$DB->get_manager()->table_exists('local_inneallmcp_audit')) {
            return;
        }

        $record = (object) [
            'userid' => (int) ($USER->id ?? 0),
            'functionname' => substr($function, 0, 100),
            'paramsummary' => substr(json_encode(self::sanitize_params($params)), 0, 1000),
            'status' => substr($status, 0, 20),
            'timecreated' => time(),
            'ip' => substr(getremoteaddr(), 0, 45),
        ];
        try {
            $DB->insert_record('local_inneallmcp_audit', $record, false);
        } catch (\Throwable $e) {
            // Never break the WS response for audit failures.
            debugging('local_inneallmcp audit failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }

    /**
     * Drop obviously sensitive keys.
     *
     * @param array $params
     * @return array
     */
    private static function sanitize_params(array $params): array {
        $out = [];
        foreach ($params as $k => $v) {
            $key = strtolower((string) $k);
            if (strpos($key, 'token') !== false || strpos($key, 'password') !== false || strpos($key, 'secret') !== false) {
                continue;
            }
            if (is_scalar($v) || $v === null) {
                $out[$k] = $v;
            } else {
                $out[$k] = gettype($v);
            }
        }
        return $out;
    }
}
