<?php

require_once __DIR__ . '/../app/core/bootstrap.php';

Auth::logout();
Session::flash('status', 'You have been signed out.');

Response::redirect('login.php');
