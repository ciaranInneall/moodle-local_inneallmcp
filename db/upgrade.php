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
 * Upgrade steps for local_inneallmcp.
 *
 * @package   local_inneallmcp
 * @copyright 2026 Inneall <support@inneall.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_local_inneallmcp_upgrade($oldversion): bool {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2026100710) {
        $table = new xmldb_table('local_inneallmcp_audit');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('functionname', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->add_field('paramsummary', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('status', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'ok');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('ip', XMLDB_TYPE_CHAR, '45', null, null, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_index('userid_time', XMLDB_INDEX_NOTUNIQUE, ['userid', 'timecreated']);
        $table->add_index('function_time', XMLDB_INDEX_NOTUNIQUE, ['functionname', 'timecreated']);

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        if (get_config('local_inneallmcp', 'enableaudit') === false) {
            set_config('enableaudit', 1, 'local_inneallmcp');
        }
        if (get_config('local_inneallmcp', 'ratelimit') === false) {
            set_config('ratelimit', 120, 'local_inneallmcp');
        }
        if (get_config('local_inneallmcp', 'proupgradeurl') === false) {
            set_config('proupgradeurl', \local_inneallmcp\local\config::DEFAULT_PRO_URL, 'local_inneallmcp');
        }
        set_config('showinstallnotice', 1, 'local_inneallmcp');

        upgrade_plugin_savepoint(true, 2026100710, 'local', 'inneallmcp');
    }

    if ($oldversion < 2026102110) {
        // Marketplace review: default PII redaction on for AI client responses.
        set_config('redactpii', 1, 'local_inneallmcp');
        upgrade_plugin_savepoint(true, 2026102110, 'local', 'inneallmcp');
    }

    return true;
}
