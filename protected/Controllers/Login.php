<?php

namespace App\Controllers;

use T4\Mvc\Controller;

class Login extends Controller
{
    public function actionDefault()
    {

    }

    public function actionIndex()
    {
        if (empty($_POST['login']) || empty($_POST['passwd'])) {
            header('location: /login');
        }
        if (true === self::auth($_POST['login'], $_POST['passwd'])) {
            header('location: /');
        } else {
            header('location: /login');
        }
    }

    protected function auth($login, $passwd)
    {
        if ($login == 'kdl') {
            return true;
        } else {
            return false;
        }
    }
}