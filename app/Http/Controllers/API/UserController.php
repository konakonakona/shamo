<?php

namespace App\Http\Controllers\API;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Fortify\Rules\Password;

class UserController extends Controller
{
    public function register(Request $request)
    {
        try {
            $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'username' => ['required', 'string', 'max:255', 'unique:users'],
                'email' => ['required', 'string', 'max:255', 'unique:users'],
                'phone' => ['nullable', 'string', 'max:255'],
                'password' => ['required', 'string', new Password],
            ]);

            User::create([
                'name' => $request->name,
                'username' => $request->username,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => Hash::make(($request->password)),
            ]);

            $user = User::where('email', $request->email)->first();

            $tokenResult = $user->createToken('authToken')->plainTextToken;

            return ResponseFormatter::success([
                'access_token' => $tokenResult,
                'token_type' => 'Bearer',
                'user' => $user
            ], 'User Registered');
        } catch (Exception $error) {
            return ResponseFormatter::error([
                'message' => 'Something went wrong',
                'error' => $error
            ], 'Authentication Failed', 500);
        }   
    }

    public function login(Request $request)
    {
        try {
            $request->validate([
                'email' => 'email|required',
                'password' => 'required'
            ]);

            $credentials = request(['email', 'password']);
            if(!Auth::attempt($credentials))
            {
                return ResponseFormatter::error([
                    'message' => 'Unauthorized'
                ], 'Login Failed', 500);
            }

            $user = User::where('email', $request->email)->first();
            
            if(!Hash::check($request->password, $user->password, []))
            {
                throw new \Exception('Invalid Credentials');
            }

            $tokenResult = $user->createToken('authToken')->plainTextToken;

            return ResponseFormatter::success([
                'access_token' => $tokenResult,
                'token_type' => 'Bearer',
                'user' => $user
            ], 'Login Success');
        } catch (Exception $error) {
            return ResponseFormatter::error([
                'message' => 'Something went wrong',
                'error' => $error
            ], 'Login Failed', 500);
        }
    }

    public function fetch(Request $request)
    {
        return ResponseFormatter::success(
            $request->user(), 
            'Data user berhasil diambil'
        );
    }

    public function updateProfile(Request $request)
    {
        $data = $request->all();

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:users'],
            'email' => ['required', 'string', 'max:255', 'unique:users'],
            'phone' => ['nullable', 'string', 'max:255'],
        ]);

        $user = Auth::user();
        $user->update($data);

        return ResponseFormatter::success(
            $user, 
            'Data user berhasil diubah'
        );
    }

    public function logout(Request $request)
    {
        $token = $request->user()->currentAccessToken()->delete();

        return ResponseFormatter::success(
            $token, 
            'Berhasil Logout'
        );
    }
}
