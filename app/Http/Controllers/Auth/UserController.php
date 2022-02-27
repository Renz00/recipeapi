<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Crypt;

class UserController extends Controller
{
    public function login(Request $request){

        //returns an error message rejected
        $result = $request->validate([
            'username' => 'required|max:200',
            'password' => 'required|min:8'
        ]);

        if (Auth::attempt(['username' => $request->username, 'password' => $request->password])) {

            $user = User::where('username', $request->username)->first();

            $token = $user->createToken('user-token')->plainTextToken; //create a token for sanctum authorization

            $user_response = [
                'id' => $user->id,
                'username' => $user->username,
                'firstname' => $user->firstname,
                'lastname' => $user->lastname,
                'role_id' => $user->role_id
            ];

            $response = [
                'user' => $user_response,
                'token' => $token
            ];
        
            return response($response, 201);
        }
        return "Error";
    }

    public function btest(){
        $data = [
            'message' => 'hello'
        ];
    //    DashboardEvent::dispatch($data);
    }

    public function checkUnique(Request $request){

        if ($request->type == 'update'){
            $validate = User::where('username', $request->username)->where('username', '<>', null)->where('id', '<>', $request->id)->get();
        }
        else {
            $validate = User::where('username', $request->username)->get();
        }
        if (count($validate) > 0){ //username exists
            return "exists";
        }
        else {
            return "unique";
        }
      
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //returns an error message rejected
        $result = $request->validate([
            'username' => 'required|max:200',
            'firstname' => 'required|max:200',
            'lastname' => 'required|max:200',
            'email' => 'required|email:rfc,dns',
            'password' => 'required|min:8'
        ]);

        $user = new User;
        $user->username = $request->username;
        $user->firstname = $request->firstname;
        $user->lastname = $request->lastname;
        $user->role_id = 1;
        $user->email = $request->email;
        $user->password = Hash::make($request->password);
        $user->image = 'noimage.png';

        $result = $user->save();

        if ($result == 1){

            if (Auth::attempt(['username' => $request->username, 'password' => $request->password])) {
    
                $token = $user->createToken('user-token')->plainTextToken; //create a token for sanctum authorization
                
                $user_response = [
                    'id' => $user->id,
                    'username' => $user->username,
                    'firstname' => $user->firstname,
                    'lastname' => $user->lastname,
                    'role_id' => $user->role_id
                ];

                $response = [
                    'user' => $user_response,
                    'token' => $token
                ];
    
                return response($response, 201);
            }
        }
        else {
            return 'Error';
        }
    }

    public function logout(Request $request){
        auth()->user()->tokens()->delete();
        return 'success';
    }

}
