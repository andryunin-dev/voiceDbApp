<?php

namespace App\Components;

use T4\Mvc\Application;

class Publisher
{
    public static function publishFrameworks()
    {
        $app = Application::instance();
        $app->assets->publish('/Templates/Frameworks/jquery-ui-min');
        $app->assets->publish('/Templates/Frameworks/bootstrap');

        $app->assets->publishCssFile('/Templates/Frameworks/bootstrap/css/bootstrap.min.css');
        $app->assets->publishCssFile('/Templates/Frameworks/jquery-ui-min/jquery-ui.min.css');

        $app->assets->publishJsFile('/Templates/Frameworks/jquery-ui-min/external/jquery/jquery.js');
        $app->assets->publishJsFile('/Templates/Frameworks/jquery-ui-min/jquery-ui.min.js');
    }
}