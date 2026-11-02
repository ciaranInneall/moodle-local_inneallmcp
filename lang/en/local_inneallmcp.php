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
 * Language strings for local_inneallmcp.
 *
 * @package   local_inneallmcp
 * @copyright 2026 Inneall <support@inneall.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Inneall MCP Tools';
$string['privacy:metadata'] = 'The Inneall MCP Tools plugin stores optional audit rows of which web service tools were called (user id, function name, truncated params, IP). Curated tool responses are returned to the calling MCP / AI client over REST (email and idnumber are redacted by default).';
$string['privacy:metadata:local_inneallmcp_audit'] = 'Audit log of curated MCP tool calls.';
$string['privacy:metadata:local_inneallmcp_audit:userid'] = 'The user id of the web service token user.';
$string['privacy:metadata:local_inneallmcp_audit:functionname'] = 'The external function that was called.';
$string['privacy:metadata:local_inneallmcp_audit:paramsummary'] = 'A truncated, sanitised summary of parameters.';
$string['privacy:metadata:local_inneallmcp_audit:status'] = 'Call status (ok, denied, error).';
$string['privacy:metadata:local_inneallmcp_audit:timecreated'] = 'When the call was logged.';
$string['privacy:metadata:local_inneallmcp_audit:ip'] = 'Caller IP address.';
$string['privacy:metadata:external'] = 'Student and activity summaries returned to the MCP / AI client that called the curated web service.';
$string['privacy:metadata:external:userid'] = 'Moodle user id.';
$string['privacy:metadata:external:username'] = 'Username.';
$string['privacy:metadata:external:firstname'] = 'First name.';
$string['privacy:metadata:external:lastname'] = 'Last name.';
$string['privacy:metadata:external:fullname'] = 'Full name.';
$string['privacy:metadata:external:email'] = 'Email address (redacted by default).';
$string['privacy:metadata:external:idnumber'] = 'Idnumber (redacted by default).';
$string['privacy:metadata:external:auth'] = 'Authentication method.';
$string['privacy:metadata:external:firstaccess'] = 'First access timestamp.';
$string['privacy:metadata:external:lastaccess'] = 'Last access timestamp.';
$string['privacy:metadata:external:city'] = 'City.';
$string['privacy:metadata:external:country'] = 'Country.';
$string['privacy:metadata:external:grades'] = 'Assignment grades and grading-queue summaries when the token user has module grade capabilities.';
$string['privacy:metadata:external:activitydata'] = 'Activity completion, quiz attempt counts, and forum participation summaries.';
$string['privacy:metadata:external:enrolments'] = 'Course enrolment and course name summaries.';
$string['cachedef_ratelimit'] = 'Per-user MCP tool rate limit counters';

$string['inneallmcp:use'] = 'Use Inneall MCP curated web service tools';

$string['settingsheading'] = 'Inneall MCP Tools';
$string['settingsintro'] = 'Curated AI-ready tools for the Moodle platform for MCP clients (Claude, Cursor, Copilot). Unlike a raw web-service passthrough, these tools return agent-friendly summaries: student lookup, enrolments, submissions, inactive students, overdue assignments, quiz and forum participation, and grading queues.<br><br><strong>Compatible with Moodle 4.5-5.2+</strong> (PHP 8.1+). Free forever on your site; optional Campus Desk for multi-site staff AI.';
$string['servicename'] = 'Inneall MCP Tools';
$string['setupguide'] = 'Setup';
$string['setupguide_desc'] = '1) Enable web services and the REST protocol.<br>2) Create a token for the built-in external service <strong>Inneall MCP Tools</strong> (shortname <code>inneallmcp</code>).<br>3) Assign a system role with <code>moodle/user:viewalldetails</code>, <code>moodle/course:view</code>, <code>mod/assign:view</code>, and <code>mod/assign:grade</code> to the token user.<br>4) Point your MCP / REST client at <code>/webservice/rest/server.php</code>.<br>5) For multi-site / SSO seats / write approvals, join Campus Desk.';
$string['setupwizard'] = 'Setup wizard';
$string['opensetuppage'] = 'Open full setup page';
$string['setup_ready'] = 'Ready: service, token, REST, and functions look good.';
$string['setup_notready'] = 'Not ready yet - fix the items marked below, then refresh this page.';
$string['check_service'] = 'Inneall MCP Tools service enabled';
$string['check_service_ok'] = 'Service id {$a} is enabled';
$string['check_service_missing'] = 'Service shortname inneallmcp not found - visit Notifications to finish install';
$string['check_token'] = 'Token exists for the service';
$string['check_token_detail'] = '{$a} token(s) found';
$string['check_functions'] = 'Curated functions registered';
$string['check_functions_detail'] = '{$a} local_inneallmcp_* functions registered';
$string['check_webservices'] = 'Web services enabled';
$string['check_webservices_ok'] = 'enablewebservices is on';
$string['check_webservices_off'] = 'Turn on Site administration → Advanced features → Enable web services';
$string['check_rest'] = 'REST protocol enabled';
$string['check_rest_ok'] = 'REST is in webserviceprotocols';
$string['check_rest_off'] = 'Enable REST under Site administration → Server → Web services → Manage protocols';
$string['restendpoint'] = 'REST endpoint';

$string['safetyheading'] = 'Safety defaults';
$string['safetyintro'] = 'Optional audit logging, rate limits, and PII redaction for AI clients. Free tools are never locked behind a license.';
$string['enableaudit'] = 'Audit tool calls';
$string['enableaudit_desc'] = 'Log which token user called which curated function (no passwords/tokens stored).';
$string['ratelimit'] = 'Rate limit (calls per minute)';
$string['ratelimit_desc'] = 'Per token-user soft limit. Use 0 to disable.';
$string['redactpii'] = 'Redact email and idnumber in responses';
$string['redactpii_desc'] = 'When enabled, student profiles return masked email/idnumber values.';

$string['clientpacks'] = 'Client paste packs';
$string['clientpacks_desc'] = 'Copy these templates into Claude Desktop / Cursor after replacing <code>YOUR_INNEALLMCP_TOKEN</code>. Prefer the hosted Inneall MCP connector for production multi-site setups.';
$string['clientpack_curl_heading'] = 'REST healthcheck';
$string['clientpack_claude_heading'] = 'Claude Desktop (template)';
$string['clientpack_cursor_heading'] = 'Cursor (template)';
$string['clientpack_curl'] = 'curl -sS -X POST \'{$a->rest}\' \\
  --data-urlencode \'wstoken={$a->token}\' \\
  --data-urlencode \'wsfunction=local_inneallmcp_healthcheck\' \\
  --data-urlencode \'moodlewsrestformat=json\'';
$string['clientpack_claude'] = '{
  "mcpServers": {
    "inneall-moodle": {
      "command": "npx",
      "args": ["-y", "mcp-remote", "{$a->wwwroot}/"],
      "env": {
        "MOODLE_URL": "{$a->rest}",
        "MOODLE_TOKEN": "{$a->token}",
        "MOODLE_SERVICE": "inneallmcp"
      }
    }
  }
}';
$string['clientpack_cursor'] = '{
  "mcp": {
    "servers": {
      "inneall-moodle": {
        "url": "{$a->rest}",
        "headers": {
          "Authorization": "Bearer {$a->token}"
        }
      }
    }
  }
}';

$string['proheading'] = 'Campus Desk';
$string['prointro'] = 'This site is on the <strong>{$a}</strong> edition. Free curated tools always keep working. Campus Desk adds a hosted staff desk for Claude and Cursor:';
$string['proteaser_multisite'] = 'One remote MCP URL across many Moodle sites (SSO seats, no wstoken in client configs)';
$string['proteaser_write'] = 'Guarded write actions with an approval inbox (email / Slack)';
$string['proteaser_sla'] = 'Advisor and Tutor role packs, At-Risk Packet, and support SLA';
$string['proteaser_playbooks'] = 'Registry / Ops packs and Slack alerts for grading queues (Multi-campus)';
$string['requestpro'] = 'Join Campus Desk';
$string['prolicensekey'] = 'Campus Desk license key (optional)';
$string['prolicensekey_desc'] = 'Soft signal only. Free tools never require a key. When set, healthcheck reports edition=pro and agents can deep-link to Campus Desk.';
$string['proupgradeurl'] = 'Campus Desk URL';
$string['proupgradeurl_desc'] = 'Where "Join Campus Desk" and healthcheck upgradeurl point.';
$string['hostedmcphint'] = 'Free on-site curated tools. For multi-site Campus Desk, SSO seats, and write approvals, open upgradeurl.';
$string['installnotice'] = 'Inneall MCP Tools is installed. <a href="{$a->setupurl}">Open the setup wizard</a>. For Claude across multiple Moodles, <a href="{$a->prourl}" target="_blank" rel="noopener">join Campus Desk</a>.';

$string['error_lookup_required'] = 'Provide exactly one of userid, username, email, or idnumber.';
$string['error_user_not_found'] = 'No Moodle user found for the given lookup.';
$string['error_course_not_found'] = 'Course not found or not accessible.';
$string['error_rate_limited'] = 'Inneall MCP Tools rate limit exceeded. Try again shortly or raise the limit in plugin settings.';
