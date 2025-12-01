<?php

namespace App\Controllers;

use App\Models\UserModel;

use App\Controllers\BaseController;



class Auth extends BaseController

{

    public function index()

    {

        // If already logged in, redirect to dashboard/home

        if (session()->get('user_id')) {

            return redirect()->to('/');

        }

        return view('auth/login');

    }



    public function process()
    {
        $request = service('request');

        if ($request->getMethod() !== 'post') {
            return redirect()->to('/login')->with('error', 'Invalid request method.');
        }

        $username = $request->getPost('username');
        $password = $request->getPost('password');

        if ($username === 'System Admin' && $password === 'admin123') {
            session()->set('isLoggedIn', true);
            return redirect()->to('/dashboard');
        } else {
            return redirect()->to('/login')->with('error', 'Invalid username or password.');
        }
    }



    public function logout()

    {

        session()->destroy();

        return redirect()->to('/login');

    }

}

