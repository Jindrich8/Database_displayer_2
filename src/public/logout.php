<?php
session_start();
require_once __DIR__ . "/../bootstrap/bootstrap.php";

$_SESSION = [];
session_destroy();
Utils::redirect_to_page('/login.php');
