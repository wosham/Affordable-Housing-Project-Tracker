<?php

require_once dirname(__DIR__) . '/core/bootstrap.php';

Guard::auth(Url::to('auth/login.php'));
