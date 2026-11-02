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

use moodle_exception;

/**
 * Simple per-user per-minute rate limit using Moodle cache.
 *
 * @package   local_inneallmcp
 * @copyright 2026 Inneall <support@inneall.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class rate_limiter {
    /**
     * Enforce configured rate limit for the current user.
     *
     * @throws moodle_exception
     */
    public static function enforce(): void {
        global $USER;

        $limit = config::rate_limit_per_minute();
        if ($limit <= 0) {
            return;
        }

        $userid = (int) ($USER->id ?? 0);
        $bucket = (string) intdiv(time(), 60);
        $key = 'rl_' . $userid . '_' . $bucket;

        $cache = \cache::make('local_inneallmcp', 'ratelimit');
        $count = (int) $cache->get($key);
        $count++;
        $cache->set($key, $count);

        if ($count > $limit) {
            audit::log('rate_limit', ['limit' => $limit, 'count' => $count], 'denied');
            throw new moodle_exception('error_rate_limited', 'local_inneallmcp');
        }
    }
}
