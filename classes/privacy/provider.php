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

namespace local_inneallmcp\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\writer;

/**
 * Privacy provider for optional audit log.
 *
 * @package   local_inneallmcp
 * @copyright 2026 Inneall <support@inneall.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {
    /**
     * Describe stored and exported personal data.
     *
     * @param collection $collection
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('local_inneallmcp_audit', [
            'userid' => 'privacy:metadata:local_inneallmcp_audit:userid',
            'functionname' => 'privacy:metadata:local_inneallmcp_audit:functionname',
            'paramsummary' => 'privacy:metadata:local_inneallmcp_audit:paramsummary',
            'status' => 'privacy:metadata:local_inneallmcp_audit:status',
            'timecreated' => 'privacy:metadata:local_inneallmcp_audit:timecreated',
            'ip' => 'privacy:metadata:local_inneallmcp_audit:ip',
        ], 'privacy:metadata:local_inneallmcp_audit');

        // Curated tool responses are returned to the calling MCP / AI client over REST.
        $collection->add_external_location_link('mcp_ai_clients', [
            'userid' => 'privacy:metadata:external:userid',
            'username' => 'privacy:metadata:external:username',
            'firstname' => 'privacy:metadata:external:firstname',
            'lastname' => 'privacy:metadata:external:lastname',
            'fullname' => 'privacy:metadata:external:fullname',
            'email' => 'privacy:metadata:external:email',
            'idnumber' => 'privacy:metadata:external:idnumber',
            'auth' => 'privacy:metadata:external:auth',
            'firstaccess' => 'privacy:metadata:external:firstaccess',
            'lastaccess' => 'privacy:metadata:external:lastaccess',
            'city' => 'privacy:metadata:external:city',
            'country' => 'privacy:metadata:external:country',
            'grades' => 'privacy:metadata:external:grades',
            'activitydata' => 'privacy:metadata:external:activitydata',
            'enrolments' => 'privacy:metadata:external:enrolments',
        ], 'privacy:metadata:external');

        return $collection;
    }

    /**
     * List contexts that hold privacy data for a user.
     *
     * @param int $userid
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();
        global $DB;
        if ($DB->record_exists('local_inneallmcp_audit', ['userid' => $userid])) {
            $contextlist->add_system_context();
        }
        return $contextlist;
    }

    /**
     * List users who have privacy data in a context.
     *
     * @param userlist $userlist
     */
    public static function get_users_in_context(userlist $userlist): void {
        if ($userlist->get_context()->contextlevel != CONTEXT_SYSTEM) {
            return;
        }
        $userlist->add_from_sql('userid', 'SELECT DISTINCT userid FROM {local_inneallmcp_audit}', []);
    }

    /**
     * Export privacy data for the approved contexts.
     *
     * @param approved_contextlist $contextlist
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;
        if (!$contextlist->count()) {
            return;
        }
        $userid = $contextlist->get_user()->id;
        $records = $DB->get_records('local_inneallmcp_audit', ['userid' => $userid], 'timecreated ASC');
        if (!$records) {
            return;
        }
        $data = [];
        foreach ($records as $r) {
            $data[] = [
                'functionname' => $r->functionname,
                'paramsummary' => $r->paramsummary,
                'status' => $r->status,
                'timecreated' => transform::datetime($r->timecreated),
                'ip' => $r->ip,
            ];
        }
        writer::with_context(\context_system::instance())->export_data(
            [get_string('pluginname', 'local_inneallmcp')],
            (object) ['calls' => $data]
        );
    }

    /**
     * Delete privacy data for one user in the approved contexts.
     *
     * @param approved_contextlist $contextlist
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;
        $DB->delete_records('local_inneallmcp_audit', ['userid' => $contextlist->get_user()->id]);
    }

    /**
     * Delete all privacy data in a context.
     *
     * @param \context $context
     */
    public static function delete_data_for_all_users_in_context(\context $context): void {
        global $DB;
        if ($context->contextlevel == CONTEXT_SYSTEM) {
            $DB->delete_records('local_inneallmcp_audit');
        }
    }

    /**
     * Delete privacy data for multiple users in a context.
     *
     * @param approved_userlist $userlist
     */
    public static function delete_data_for_users(approved_userlist $userlist): void {
        global $DB;
        $DB->delete_records_list('local_inneallmcp_audit', 'userid', $userlist->get_userids());
    }
}
