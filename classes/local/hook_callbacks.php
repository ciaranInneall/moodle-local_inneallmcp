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
 * Hook callbacks.
 *
 * @package   local_inneallmcp
 * @copyright 2026 Inneall <support@inneall.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_callbacks {
    /**
     * One-shot install/upgrade notice for site admins.
     *
     * @param \core\hook\output\before_http_headers $hook
     */
    public static function before_http_headers(\core\hook\output\before_http_headers $hook): void {
        if (!isloggedin() || isguestuser() || !is_siteadmin()) {
            return;
        }
        if (!(int) get_config('local_inneallmcp', 'showinstallnotice')) {
            return;
        }
        $url = new \moodle_url('/local/inneallmcp/setup.php');
        \core\notification::info(get_string('installnotice', 'local_inneallmcp', (object) [
            'setupurl' => $url->out(false),
            'prourl' => config::upgrade_url(),
        ]));
        set_config('showinstallnotice', 0, 'local_inneallmcp');
    }
}
