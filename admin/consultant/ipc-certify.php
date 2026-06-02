<?php
// Consultant — Certify / Reject IPC
require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::role(RoleAccess::area('consultant'));
// TODO: Phase 4 — Certification workspace: annotate, approve/reject with reason
