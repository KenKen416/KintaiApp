<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\Registered;

class RegisterController extends Controller
{
    public function create()
    {
        return view('user.auth.register');
    }
    public function store(RegisterRequest $request)
    {
        $user = User::create([
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'password' => Hash::make($request->input('password')),
            'is_admin' => 0,
        ]);

        // Fortify / Laravel のメール認証トリガ
        event(new Registered($user));

        Auth::login($user);

        return redirect()->route('verification.notice');
    }


}
