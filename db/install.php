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
 * Post-install for local_inneallmcp.
 *
 * @package   local_inneallmcp
 * @copyright 2026 Inneall <support@inneall.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Install defaults and notify admins.
 *
 * @return bool
 */
function xmldb_local_inneallmcp_install(): bool {
    set_config('enableaudit', 1, 'local_inneallmcp');
    set_config('ratelimit', 120, 'local_inneallmcp');
    set_config('redactpii', 1, 'local_inneallmcp');
    set_config('proupgradeurl', \local_inneallmcp\local\config::DEFAULT_PRO_URL, 'local_inneallmcp');

    $message = new \core\message\message();
    // Prefer admin notification via \core\notification on next page; message API needs user.
    // Store a one-shot admin notice flag instead.
    set_config('showinstallnotice', 1, 'local_inneallmcp');

    return true;
}
