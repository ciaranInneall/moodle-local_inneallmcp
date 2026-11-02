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

use context_system;
use html_writer;

/**
 * Setup wizard checks for Site administration.
 *
 * @package   local_inneallmcp
 * @copyright 2026 Inneall <support@inneall.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class setup_status {
    /**
     * Run readiness checks.
     *
     * @return array{ok:bool,checks:array<int,array{id:string,ok:bool,label:string,detail:string}>}
     */
    public static function run(): array {
        global $DB, $CFG;

        $checks = [];

        $service = $DB->get_record('external_services', ['shortname' => 'inneallmcp'], '*', IGNORE_MISSING);
        $checks[] = [
            'id' => 'service',
            'ok' => !empty($service) && !empty($service->enabled),
            'label' => get_string('check_service', 'local_inneallmcp'),
            'detail' => $service
                ? get_string('check_service_ok', 'local_inneallmcp', $service->id)
                : get_string('check_service_missing', 'local_inneallmcp'),
        ];

        $tokencount = 0;
        if ($service) {
            $tokencount = $DB->count_records('external_tokens', ['externalserviceid' => $service->id]);
        }
        $checks[] = [
            'id' => 'token',
            'ok' => $tokencount > 0,
            'label' => get_string('check_token', 'local_inneallmcp'),
            'detail' => get_string('check_token_detail', 'local_inneallmcp', $tokencount),
        ];

        $fncount = $DB->count_records_select('external_functions', "name LIKE 'local_inneallmcp_%'");
        $checks[] = [
            'id' => 'functions',
            'ok' => $fncount >= 11,
            'label' => get_string('check_functions', 'local_inneallmcp'),
            'detail' => get_string('check_functions_detail', 'local_inneallmcp', $fncount),
        ];

        $wsok = !empty($CFG->enablewebservices);
        $checks[] = [
            'id' => 'webservices',
            'ok' => $wsok,
            'label' => get_string('check_webservices', 'local_inneallmcp'),
            'detail' => $wsok
                ? get_string('check_webservices_ok', 'local_inneallmcp')
                : get_string('check_webservices_off', 'local_inneallmcp'),
        ];

        $restok = false;
        $protocols = empty($CFG->webserviceprotocols) ? [] : explode(',', $CFG->webserviceprotocols);
        foreach ($protocols as $p) {
            if (trim($p) === 'rest') {
                $restok = true;
                break;
            }
        }
        $checks[] = [
            'id' => 'rest',
            'ok' => $restok,
            'label' => get_string('check_rest', 'local_inneallmcp'),
            'detail' => $restok
                ? get_string('check_rest_ok', 'local_inneallmcp')
                : get_string('check_rest_off', 'local_inneallmcp'),
        ];

        $allok = true;
        foreach ($checks as $c) {
            if (!$c['ok']) {
                $allok = false;
                break;
            }
        }

        return [
            'ok' => $allok,
            'checks' => $checks,
            'wwwroot' => $CFG->wwwroot,
            'restendpoint' => rtrim($CFG->wwwroot, '/') . '/webservice/rest/server.php',
            'edition' => config::edition(),
            'upgradeurl' => config::upgrade_url(),
        ];
    }

    /**
     * HTML summary for admin settings heading.
     *
     * @return string
     */
    public static function render_html(): string {
        $status = self::run();
        $lines = [];
        foreach ($status['checks'] as $c) {
            $icon = $c['ok'] ? '[OK]' : '[FAIL]';
            $lines[] = html_writer::tag('li', $icon . ' <strong>' . s($c['label']) . '</strong> - ' . s($c['detail']));
        }
        $banner = $status['ok']
            ? html_writer::div(get_string('setup_ready', 'local_inneallmcp'), 'alert alert-success')
            : html_writer::div(get_string('setup_notready', 'local_inneallmcp'), 'alert alert-warning');
        return $banner . html_writer::tag('ul', implode('', $lines));
    }
}
