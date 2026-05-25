<?php

require_once dirname(__DIR__) . '/core/bootstrap.php';

Guard::guest(Url::to('auth/dashboard.php'));
