<?php
session_start();
require_once __DIR__ . "/../bootstrap/bootstrap.php";

class IndexPage extends BaseLoggedInPage
{
    public static function getProjectDir()
    {
        return __DIR__;
    }
    public function __construct()
    {
        $this->title = "Prohlížeč databáze firmy";
    }

    protected function pageBody()
    {
        return "Hello World!!!";
    }
}

$page = new IndexPage();
$page->render();
