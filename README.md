# Inneall MCP Tools (`local_inneallmcp`)

**Version 1.1.3** · Moodle **4.5–5.2+** · GPL v3+

Issues: https://github.com/ciaranInneall/moodle-local_inneallmcp/issues

Curated AI-ready Moodle web service tools for MCP clients (Claude, Cursor, Copilot).

Unlike protocol-only MCP plugins that dump every Moodle web service onto an agent, this plugin exposes **agent-friendly outcomes**: student lookup, engagement rollups, inactive students, overdue work, quiz/forum participation, and grading queues.

## Tools

| Function | Purpose |
|---|---|
| `local_inneallmcp_healthcheck` | Site + setup checks + free/pro edition hint |
| `local_inneallmcp_lookup_student` | Resolve by userid / username / email / idnumber |
| `local_inneallmcp_get_student_enrolments` | Enrolled courses (+ optional completion) |
| `local_inneallmcp_get_student_submissions` | Assignment submission rollup |
| `local_inneallmcp_get_user_activity_summary` | Enrolments or per-course completion |
| `local_inneallmcp_list_assignments` | Course assignments (no enrolment required) |
| `local_inneallmcp_get_grading_queue` | Submitted-without-grade queue |
| `local_inneallmcp_list_inactive_students` | No login for N days (at-risk) |
| `local_inneallmcp_list_overdue_assignments` | Past-due assignments in a course |
| `local_inneallmcp_get_quiz_attempt_summary` | Quiz attempt rollup |
| `local_inneallmcp_get_forum_participation` | Forum posts / discussions |

## Install

1. Upload / unzip into `local/inneallmcp` (Moodle 5: `public/local/inneallmcp`).
2. Visit **Site administration → Notifications**.
3. Open **Plugins → Local plugins → Inneall MCP Tools** (setup wizard).
4. Create a token for service **Inneall MCP Tools** (`inneallmcp`).
5. Grant the token user system capabilities: `local/inneallmcp:use`, `moodle/user:viewalldetails`, `moodle/course:view`, `mod/assign:view`, `mod/assign:grade`.

## Safety settings

- Audit log of tool calls (optional table `local_inneallmcp_audit`)
- Per-user rate limit (default 120/min)
- Optional redaction of email / idnumber in responses

## Campus Desk (optional)

Free tools always work without a license. Optional Pro key is a soft signal for agents.

Campus Desk is Inneall’s hosted staff desk for Claude and Cursor: SSO seats, role packs, approval inbox, multi-site routing, and At-Risk Packet:

https://inneall.ie/mcp-pro

## REST example

```
POST /webservice/rest/server.php
wstoken=TOKEN
wsfunction=local_inneallmcp_lookup_student
moodlewsrestformat=json
username=10633744
```

## Maintainer

Inneall — support@inneall.com  
License: GPL v3 or later
