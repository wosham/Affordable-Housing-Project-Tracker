<?php
// Finance Officer — Liquidated Damages
require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::role(RoleAccess::area('finance'));
// TODO: Phase 5 — Apply LDs to IPC deductions, track days overdue per contract
