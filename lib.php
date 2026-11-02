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
 * Library callbacks for local_inneallmcp.
 *
 * @package   local_inneallmcp
 * @copyright 2026 Inneall <support@inneall.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Show a one-shot admin notice after install/upgrade.
 */
function local_inneallmcp_before_http_headers(): void {
    if (!isloggedin() || isguestuser() || !is_siteadmin()) {
        return;
    }
    if (!(int) get_config('local_inneallmcp', 'showinstallnotice')) {
        return;
    }
    $url = new moodle_url('/local/inneallmcp/setup.php');
    $pro = \local_inneallmcp\local\config::upgrade_url();
    \core\notification::info(get_string('installnotice', 'local_inneallmcp', (object) [
        'setupurl' => $url->out(false),
        'prourl' => $pro,
    ]));
    set_config('showinstallnotice', 0, 'local_inneallmcp');
}
