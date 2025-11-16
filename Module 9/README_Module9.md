# Module9 — Project Management (Module 9)

## Purpose
Project Management module that:
- Manages projects, tasks, and resource assignments.
- Reads HR (Module 10) and Finance (Module 5) data via read-only connectors to enforce availability and budget rules.

## Files to add (Module9/)
- module9_schema.sql
- connectors/config.php
- connectors/HRConnector.php
- connectors/FinanceConnector.php
- logs/log_helper.php
- project_functions.php

## How to install
1. Add `module9_schema.sql` to your DB via the team's migration process or import into MySQL.
2. Copy `connectors/` and `logs/` directories under `/Module9/`.
3. Update base URLs in `connectors/config.php` if Module5/Module10 endpoints are hosted elsewhere (e.g., remote server or different path).
4. Ensure `project_functions.php` can include your shared DB connection (it looks for `../shared/db.php` by default). Adjust path if necessary.
5. Add navigation link in your main app to `Module9/project_list.php` (to be created by dev when building UI).

## Notes
- All connectors are **read-only**. They use cURL to GET JSON from HR/Finance.
- Logging is append-only to `/Module9/logs/integration.log`. Keep logs for evidence during Integration Test Report.

## Integration Test Plan (summary)
- HR test: mark an employee as "On Leave" in Module10 and verify assigning them in Module9 is blocked.
- Finance test: set used_budget >= allocated_budget in Module5 and verify Module9 blocks further spend/approvals.