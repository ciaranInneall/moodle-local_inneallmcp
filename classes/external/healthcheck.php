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

use context_system;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use local_inneallmcp\local\config;
use local_inneallmcp\local\setup_status;
use local_inneallmcp\local\tool_guard;

/**
 * Healthcheck for Inneall MCP tools.
 *
 * @package   local_inneallmcp
 * @copyright 2026 Inneall <support@inneall.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class healthcheck extends external_api {
    /**
     * Parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([]);
    }

    /**
     * Execute.
     *
     * @return array
     */
    public static function execute(): array {
        global $CFG, $USER;

        tool_guard::enter('local_inneallmcp_healthcheck');
        $status = setup_status::run();

        $checks = [];
        foreach ($status['checks'] as $c) {
            $checks[] = [
                'id' => $c['id'],
                'ok' => (bool) $c['ok'],
                'label' => $c['label'],
                'detail' => $c['detail'],
            ];
        }

        return [
            'ok' => true,
            'sitename' => format_string($CFG->fullname ?? ''),
            'wwwroot' => $CFG->wwwroot,
            'release' => $CFG->release,
            'wsusername' => $USER->username,
            'wsuserid' => (int) $USER->id,
            'plugin' => 'local_inneallmcp',
            'pluginrelease' => config::plugin_release(),
            'edition' => config::edition(),
            'upgradeurl' => config::upgrade_url(),
            'hostedmcphint' => get_string('hostedmcphint', 'local_inneallmcp'),
            'setupok' => (bool) $status['ok'],
            'checks' => $checks,
        ];
    }

    /**
     * Returns.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'ok' => new external_value(PARAM_BOOL, 'Service reachable'),
            'sitename' => new external_value(PARAM_TEXT, 'Site name'),
            'wwwroot' => new external_value(PARAM_URL, 'Site URL'),
            'release' => new external_value(PARAM_TEXT, 'Moodle release'),
            'wsusername' => new external_value(PARAM_RAW, 'Token user username'),
            'wsuserid' => new external_value(PARAM_INT, 'Token user id'),
            'plugin' => new external_value(PARAM_TEXT, 'Plugin frankenstyle'),
            'pluginrelease' => new external_value(PARAM_TEXT, 'Plugin release'),
            'edition' => new external_value(PARAM_ALPHA, 'free or pro (soft signal)'),
            'upgradeurl' => new external_value(PARAM_URL, 'Hosted MCP Pro request URL'),
            'hostedmcphint' => new external_value(PARAM_TEXT, 'Hint for agents about Pro'),
            'setupok' => new external_value(PARAM_BOOL, 'Setup wizard all-green'),
            'checks' => new external_multiple_structure(
                new external_single_structure([
                    'id' => new external_value(PARAM_ALPHANUMEXT, 'Check id'),
                    'ok' => new external_value(PARAM_BOOL, 'Passed'),
                    'label' => new external_value(PARAM_TEXT, 'Label'),
                    'detail' => new external_value(PARAM_TEXT, 'Detail'),
                ])
            ),
        ]);
    }
}
