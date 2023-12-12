<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index($flag)
    {
        $query = User::select('email', 'name', 'status');

        if ($flag == 1) {
            $query->where('status', 1);
        } else if ($flag == 0) {
            // $query->where('status', 0);
        } else {
            return response()->json([
                'message' => 'Invalid parameter passed, it can be either 1 or 0',
                'status' => 0,
            ], 400);
        }

        $users = $query->get();

        if (count($users) > 0) {
            // user exists
            $response = [
                'message' => count($users) . ' users found',
                'status' => 1,
                'data' => $users
            ];
        } else {
            // user not exists
            $response = [
                'message' => count($users) . ' users found',
                'status' => 0,
                'data' => $users
            ];
        }
        return response()->json($response, 200);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'min:8', 'confirmed'],
            'password_confirmation' => ['required']
        ]);
        if ($validator->fails()) {
            return response()->json($validator->messages(), 400);
        } else {
            $data = [
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password)
            ];
            DB::beginTransaction();
            try {
                $user = User::create($data);
                DB::commit();
            } catch (\Exception $e) {
                // DB::rollBack();
                p($e->getMessage());
                $user = null;
            }
            if ($user != null) {
                // okay
                return response()->json([
                    'message' => "User Registerd Successfully"
                ], 200);
            } else {
                // not okay
                return response()->json([
                    'message' => "Internal Server Error"
                ], 500);
            }
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $user = User::find($id);
        if (is_null($user)) {
            $response = [
                'message' => 'User Not Found',
                'status' => 0,
            ];
        } else {
            $response = [
                'message' => 'User Found',
                'status' => 1,
                'data' => $user
            ];
        }
        return response()->json($response, 200);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $user = User::find($id);
        if (is_null($user)) {
            return response()->json([
                'message' => 'User Does not Exists',
                'status' => 0
            ], 404);
        } else {
            DB::beginTransaction();
            try {
                $user->name = $request['name'];
                $user->email = $request['email'];
                $user->contact = $request['contact'];
                $user->pincode = $request['pincode'];
                $user->address = $request['address'];
                $user->save();
                DB::commit();
            } catch (\Exception $err) {
                DB::rollBack();
                $user = null;
            }
            if (is_null($user)) {
                return response()->json([
                    'message' => 'Internal Server Error',
                    'status' => 0,
                    'error_msg' => $err->getMessage()
                ], 500);
            } else {
                return response()->json([
                    'message' => 'Data Updated Successfuly',
                    'status' => 1
                ], 200);
            }
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = User::find($id);
        if (is_null($user)) {
            $response = [
                'message' => "User Doesn't exists",
                'status' => 0
            ];
            $respCode = 404;
        } else {
            DB::beginTransaction();
            try {
                $user->delete();
                DB::commit();
                $response = [
                    'message' => 'User Delete Successfuly',
                    'status' => 1
                ];
                $respCode = 200;
            } catch (\Exception $err) {
                DB::rollBack();
                $response = [
                    'message' => 'Internal Server Error',
                    'status' => 0
                ];
                $respCode = 500;
            }
        }
        return response()->json($response, $respCode);
    }

    public function changePassword(Request $request, $id)
    {
        $user = User::find($id);
        p($user);
        exit;
        if (is_null($user)) {
            return response()->json([
                'status' => 0,
                'message' => 'User Does Not Exists'
            ], 404);
        } else {
            if ($user->password == $request['old_password']) {
                if ($request['new_password'] == $request['configm_password']) {
                    // change
                    DB::beginTransaction();
                    try {
                        $user->password = $request['new_password'];
                        $user->save();
                        DB::commit();
                    } catch (\Exception $err) {
                        DB::rollBack();
                        $user = null;
                    }
                    if (is_null($user)) {
                        return response()->json([
                            'status' => 0,
                            'message' => 'Internal Server Error',
                            'error_msg' => $err->getMessage()
                        ], 500);
                    } else {
                        return response()->json([
                            'status' => 1,
                            'message' => 'Password Updated Successfuly'
                        ], 200);
                    }
                } else {
                    return response()->json([
                        'status' => 0,
                        'message' => 'New Password and Old Password Does Not Match'
                    ], 404);
                }
            } else {
                return response()->json([
                    'status' => 0,
                    'message' => 'Old Password Does Not Match'
                ], 404);
            }
        }
    }
}
