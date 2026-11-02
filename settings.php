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
 * Admin settings for local_inneallmcp.
 *
 * @package   local_inneallmcp
 * @copyright 2026 Inneall <support@inneall.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_inneallmcp', get_string('pluginname', 'local_inneallmcp'));

    $settings->add(new admin_setting_heading(
        'local_inneallmcp/intro',
        get_string('settingsheading', 'local_inneallmcp'),
        get_string('settingsintro', 'local_inneallmcp')
    ));

    // Live setup wizard summary.
    $wizardhtml = '';
    try {
        $wizardhtml = \local_inneallmcp\local\setup_status::render_html();
    } catch (Throwable $e) {
        $wizardhtml = html_writer::div(s($e->getMessage()), 'alert alert-danger');
    }
    $settings->add(new admin_setting_heading(
        'local_inneallmcp/wizard',
        get_string('setupwizard', 'local_inneallmcp'),
        $wizardhtml . html_writer::div(
            html_writer::link(
                new moodle_url('/local/inneallmcp/setup.php'),
                get_string('opensetuppage', 'local_inneallmcp'),
                ['class' => 'btn btn-secondary mt-2']
            )
        )
    ));

    $settings->add(new admin_setting_heading(
        'local_inneallmcp/safety',
        get_string('safetyheading', 'local_inneallmcp'),
        get_string('safetyintro', 'local_inneallmcp')
    ));
    $settings->add(new admin_setting_configcheckbox(
        'local_inneallmcp/enableaudit',
        get_string('enableaudit', 'local_inneallmcp'),
        get_string('enableaudit_desc', 'local_inneallmcp'),
        1
    ));
    $settings->add(new admin_setting_configtext(
        'local_inneallmcp/ratelimit',
        get_string('ratelimit', 'local_inneallmcp'),
        get_string('ratelimit_desc', 'local_inneallmcp'),
        120,
        PARAM_INT
    ));
    $settings->add(new admin_setting_configcheckbox(
        'local_inneallmcp/redactpii',
        get_string('redactpii', 'local_inneallmcp'),
        get_string('redactpii_desc', 'local_inneallmcp'),
        1
    ));

    // Client paste packs (templates via lang strings).
    global $CFG;
    $rest = rtrim($CFG->wwwroot, '/') . '/webservice/rest/server.php';
    $packa = (object) [
        'wwwroot' => $CFG->wwwroot,
        'rest' => $rest,
        'token' => 'YOUR_INNEALLMCP_TOKEN',
    ];
    $claude = get_string('clientpack_claude', 'local_inneallmcp', $packa);
    $cursor = get_string('clientpack_cursor', 'local_inneallmcp', $packa);
    $curl = get_string('clientpack_curl', 'local_inneallmcp', $packa);
    $settings->add(new admin_setting_heading(
        'local_inneallmcp/clientpacks',
        get_string('clientpacks', 'local_inneallmcp'),
        get_string('clientpacks_desc', 'local_inneallmcp') .
        html_writer::tag('h4', get_string('clientpack_curl_heading', 'local_inneallmcp')) .
        html_writer::tag('pre', s($curl)) .
        html_writer::tag('h4', get_string('clientpack_claude_heading', 'local_inneallmcp')) .
        html_writer::tag('pre', s($claude)) .
        html_writer::tag('h4', get_string('clientpack_cursor_heading', 'local_inneallmcp')) .
        html_writer::tag('pre', s($cursor))
    ));

    // Soft Pro upsell — free tools never gated.
    $upgrade = \local_inneallmcp\local\config::upgrade_url();
    $edition = \local_inneallmcp\local\config::edition();
    $proteasers = html_writer::tag(
        'ul',
        html_writer::tag('li', get_string('proteaser_multisite', 'local_inneallmcp')) .
        html_writer::tag('li', get_string('proteaser_write', 'local_inneallmcp')) .
        html_writer::tag('li', get_string('proteaser_sla', 'local_inneallmcp')) .
        html_writer::tag('li', get_string('proteaser_playbooks', 'local_inneallmcp'))
    );
    $settings->add(new admin_setting_heading(
        'local_inneallmcp/pro',
        get_string('proheading', 'local_inneallmcp'),
        html_writer::div(
            get_string('prointro', 'local_inneallmcp', $edition) . $proteasers .
            html_writer::link($upgrade, get_string('requestpro', 'local_inneallmcp'), [
                'class' => 'btn btn-primary',
                'target' => '_blank',
                'rel' => 'noopener',
            ]),
            'border rounded p-3 bg-light'
        )
    ));
    $settings->add(new admin_setting_configpasswordunmask(
        'local_inneallmcp/prolicensekey',
        get_string('prolicensekey', 'local_inneallmcp'),
        get_string('prolicensekey_desc', 'local_inneallmcp'),
        ''
    ));
    $settings->add(new admin_setting_configtext(
        'local_inneallmcp/proupgradeurl',
        get_string('proupgradeurl', 'local_inneallmcp'),
        get_string('proupgradeurl_desc', 'local_inneallmcp'),
        \local_inneallmcp\local\config::DEFAULT_PRO_URL,
        PARAM_URL
    ));

    $ADMIN->add('localplugins', $settings);

    $ADMIN->add('localplugins', new admin_externalpage(
        'local_inneallmcp_setup',
        get_string('setupwizard', 'local_inneallmcp'),
        new moodle_url('/local/inneallmcp/setup.php'),
        'moodle/site:config'
    ));
}
