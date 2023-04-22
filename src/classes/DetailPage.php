<?php
abstract class DetailPage extends BaseLoggedInPage
{

    protected function extraHTMLHeaders(): string
    {
        return '<link rel="stylesheet" href="/styles/detailStyle.css">';
    }
}
