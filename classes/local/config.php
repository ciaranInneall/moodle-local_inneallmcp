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
 * Plugin config helpers (free forever; Pro is hosted / soft upsell only).
 *
 * @package   local_inneallmcp
 * @copyright 2026 Inneall <support@inneall.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class config {
    /** Default hosted Pro landing (request / signup). */
    public const DEFAULT_PRO_URL = 'https://inneall.ie/mcp-pro';

    /**
     * Plugin release from version.php.
     *
     * @return string
     */
    public static function plugin_release(): string {
        $plugin = new \stdClass();
        include(__DIR__ . '/../../version.php');
        return isset($plugin->release) ? (string) $plugin->release : '1.1.0';
    }

    /**
     * True when a Pro license key is stored (soft signal only; free tools always work).
     *
     * @return bool
     */
    public static function has_pro_key(): bool {
        $key = (string) get_config('local_inneallmcp', 'prolicensekey');
        return trim($key) !== '';
    }

    /**
     * Edition label for healthcheck / agents.
     *
     * @return string free|pro
     */
    public static function edition(): string {
        return self::has_pro_key() ? 'pro' : 'free';
    }

    /**
     * Upgrade / request-Pro URL including site fingerprint query args.
     *
     * @return string
     */
    public static function upgrade_url(): string {
        global $CFG;

        $base = (string) get_config('local_inneallmcp', 'proupgradeurl');
        if ($base === '') {
            $base = self::DEFAULT_PRO_URL;
        }
        $sep = (strpos($base, '?') === false) ? '?' : '&';
        return $base . $sep . http_build_query([
            'site' => $CFG->wwwroot,
            'plugin' => 'local_inneallmcp',
            'release' => self::plugin_release(),
            'edition' => self::edition(),
        ], '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * Whether to redact email/idnumber in student profiles.
     *
     * @return bool
     */
    public static function redact_pii(): bool {
        return (bool) get_config('local_inneallmcp', 'redactpii');
    }

    /**
     * Max WS calls per token user per minute (0 = disabled).
     *
     * @return int
     */
    public static function rate_limit_per_minute(): int {
        return max(0, (int) get_config('local_inneallmcp', 'ratelimit'));
    }

    /**
     * Whether to write audit rows for tool calls.
     *
     * @return bool
     */
    public static function audit_enabled(): bool {
        return (bool) get_config('local_inneallmcp', 'enableaudit');
    }
}
