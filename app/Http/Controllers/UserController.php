<?php

namespace App\Http\Controllers;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function profile()
{
    $user = Auth::user();
    return response()->json(['user' => $user]);
}
public function deleteProfile($id, Request $request){
    $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'Item not found',  'status_code' => 402]);
        }
        $user->delete();
    $request->user()->tokens()->delete();
    return response(['message' => 'Logout Successfull',  'status_code' => 200,]);
}
public function login(Request $request)
{
    try {
        $request->validate([
            'email' => 'email|required',
            'password' => 'required',
        ]);

        $credentials = request(['email', 'password']);

        if (!Auth::attempt($credentials)) {
            return response()->json([
                'status_code' => 401,
                'message' => 'Unauthorized',

            ]);
        }

        $user =  User::where('email', $request->email)->first();

        if (!Hash::check($request->password, $user->password, [])) {
            return response()->json([
                'status_code' => 402,
                'message' => 'Password Match',

            ]);
        }

        $tokenResult = $user->createToken('authToken')->plainTextToken;
        return response()->json([
            'status_code' => 200,
            'message' => 'Login Successfull',
            'token' => $tokenResult,
            'token_type' => 'Bearer',
        ]);
    } catch (Exception $error) {
        return response()->json([
            'status_code' => 500,
            'message' => 'Error in login',
            'error' => $error,
        ]);
    }
}
public function register(Request $request)
{

    try {

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'password_confirmation' => 'required',
            'firebase_uid' => 'nullable|string',
            'fcm_token' => 'nullable|string',

        ]);

        if ($validator->fails()) {
            return response([
                'error' => $validator->errors()->all()
            ], 402);
        }

        $request['password'] = Hash::make($request['password']);
        $request['password_confirmation'] = Hash::make($request['password_confirmation']);
        $request['remember_token'] = Str::random(10);
        $request['file_profile'] = null;
        $user = User::create($request->toArray());
        if ($request->exists('firebase_uid')) {
            $user->firebase_uid = $request->firebase_uid;
            $user->save();
        }

        if ($request->exists('fcm_token')) {
            $user->fcm_token = $request->fcm_token;
            $user->save();
        }


        return response()->json([
            'status_code' => 200,
            'message' => 'Registration Successfull',
        ]);
    } catch (Exception $error) {
        return response()->json([
            'status_code' => 402,
            'message' => 'Error in Registration',
            'error' => $error,
        ]);
    }
}
public function logout(Request $request)
{
    $request->user()->tokens()->delete();
    return response(['message' => 'Logout Successfull']);
}
public function getActiveUser(Request $request)
{
    return response()->json([
        "user" => $request->user()
    ], 200);
}
}
