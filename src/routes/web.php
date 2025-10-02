<?php

use Illuminate\Support\Facades\Route;



Route::get('/register', function () {
    $nav = 'none';
    return view('user.auth.register', compact('nav'));
});

Route::get('/login', function () {
    $nav = 'none';
    return view('user.auth.login', compact('nav'));
});

Route::get('/admin/login', function () {
    $nav = 'none';
    return view('admin.auth.login', compact('nav'));
});

Route::get('/email/verify', function () {
    $nav = 'none';
    return view('user.auth.verify-email', compact('nav'));
});