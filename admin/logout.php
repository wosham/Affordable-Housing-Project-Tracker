<?php
require_once __DIR__ . "/../app/core/bootstrap.php";

$user = Auth::user();
if (is_array($user) && isset($user["id"])) {
    Logger::log("logout", "auth", (int) $user["id"], [
        "email" => $user["email"] ?? "",
    ]);
}

Auth::logout();
Session::flash("status", "You have been signed out.");
Response::redirect(Url::to("admin/login.php"));
