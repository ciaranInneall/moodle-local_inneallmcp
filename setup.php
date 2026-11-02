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
 * Setup wizard page for local_inneallmcp.
 *
 * @package   local_inneallmcp
 * @copyright 2026 Inneall <support@inneall.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_login();
require_capability('moodle/site:config', context_system::instance());

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/local/inneallmcp/setup.php'));
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('setupwizard', 'local_inneallmcp'));
$PAGE->set_heading(get_string('setupwizard', 'local_inneallmcp'));

$status = \local_inneallmcp\local\setup_status::run();

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('setupwizard', 'local_inneallmcp'));
echo \local_inneallmcp\local\setup_status::render_html();

echo html_writer::tag('h3', get_string('setupguide', 'local_inneallmcp'));
echo html_writer::div(get_string('setupguide_desc', 'local_inneallmcp'));

echo html_writer::tag('h3', get_string('restendpoint', 'local_inneallmcp'));
echo html_writer::tag('pre', s($status['restendpoint']));

echo html_writer::tag('h3', get_string('proheading', 'local_inneallmcp'));
echo html_writer::div(get_string('prointro', 'local_inneallmcp', $status['edition']));
echo html_writer::link($status['upgradeurl'], get_string('requestpro', 'local_inneallmcp'), [
    'class' => 'btn btn-primary',
    'target' => '_blank',
    'rel' => 'noopener',
]);

echo html_writer::div(
    html_writer::link(
        new moodle_url('/admin/settings.php', ['section' => 'local_inneallmcp']),
        get_string('pluginname', 'local_inneallmcp'),
        ['class' => 'btn btn-link']
    ),
    'mt-3'
);

echo $OUTPUT->footer();
