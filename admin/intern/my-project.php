<?php
// Intern — My Project (read-only overview of assigned site)
require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::auth(); Guard::role(['superadmin','intern']);
// TODO: Phase 3 — Project summary, milestones, site contacts, documents
