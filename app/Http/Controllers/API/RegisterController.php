<?php

namespace App\Http\Controllers\API;

# use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Validator;
use Illuminate\Http\JsonResponse;

class RegisterController extends BaseController
{
    /**
     * Register api
     *
     * @return \Illuminate\Http\Response
     */
    public function register(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|min:3|max:50|regex:/^[a-zA-Z\s]+$/',
                'email' => 'required|email|max:100|unique:users,email',
                'password' => [
                    'required',
                    'string',
                    'min:8',                     // Minimum 8 characters
                    'regex:/[A-Z]/',             // At least one uppercase
                    'regex:/[0-9]/',             // At least one number
                    'regex:/[@$!%*?&]/'          // At least one special character
                ],
                'c_password' => 'required|same:password',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors());
            }

            $input = $request->all();
            $input['password'] = bcrypt($input['password']);
            $user = User::create($input);
            $success['token'] =  $user->createToken('MyApp')->plainTextToken;
            $success['name'] =  $user->name;

            return $this->sendResponse($success, 'User register successfully.');
        } catch (\Throwable $e) {
            // FULL ERROR DETAILS
            $errorDetail = [
                'error_message' => $e->getMessage(),
                'file'          => $e->getFile(),
                'line'          => $e->getLine(),
                'error_type'    => get_class($e),
            ];

            return $this->sendError('Exception Error', $errorDetail);
        }
    }

    /**
     * Login api
     *
     * @return \Illuminate\Http\Response
     */
    public function login(Request $request): JsonResponse
    {
        try {
            // Validation
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|max:100',
                'password' => 'required|string|min:6|max:20',
            ]);
            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors());
            }
            if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
                $user = Auth::user();
                $user->last_login_at = now();
                $user->save();
                $success['token'] =  $user->createToken('MyApp')->plainTextToken;
                $success['name'] =  $user->name;

                return $this->sendResponse($success, 'User login successfully.');
            } else {
                return $this->sendError('Unauthorised.', ['error' => 'Unauthorised']);
            }
        } catch (\Throwable $e) {
            // FULL ERROR DETAILS
            $errorDetail = [
                'error_message' => $e->getMessage(),
                'file'          => $e->getFile(),
                'line'          => $e->getLine(),
                'error_type'    => get_class($e),
            ];

            return $this->sendError('Exception Error', $errorDetail);
        }
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|exists:users,email',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors()->first());
            }

            $status = Password::sendResetLink(
                $request->only('email')
            );

            if ($status === Password::RESET_LINK_SENT) {
                return $this->sendResponse([], 'Password reset link sent to your email.');
            } else {
                return $this->sendError('Error', 'Unable to send reset link.');
            }
        } catch (\Throwable $e) {
            $errorDetail = [
                'error_message' => $e->getMessage(),
                'file'          => $e->getFile(),
                'line'          => $e->getLine(),
                'error_type'    => get_class($e),
            ];
            return $this->sendError('Throwable Error', $errorDetail);
        }
    }


    public function resetPassword(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|exists:users,email',
                'token' => 'required',
                'password' => [
                    'required',
                    'string',
                    'min:8',                     // Minimum 8 characters
                    'confirmed',                 // Requires password_confirmation
                    'regex:/[A-Z]/',             // At least one uppercase
                    'regex:/[0-9]/',             // At least one number
                    'regex:/[@$!%*?&]/'          // At least one special character
                ],
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors()->first());
            }

            $status = Password::reset(
                $request->only('email', 'password', 'password_confirmation', 'token'),
                function ($user, $password) {
                    $user->forceFill([
                        'password' => Hash::make($password)
                    ])->save();
                }
            );

            if ($status === Password::PASSWORD_RESET) {
                return $this->sendResponse([], 'Password reset successfully.');
            } else {
                return $this->sendError('Error', __($status));
            }
        } catch (\Throwable $e) {
            $errorDetail = [
                'error_message' => $e->getMessage(),
                'file'          => $e->getFile(),
                'line'          => $e->getLine(),
                'error_type'    => get_class($e),
            ];
            return $this->sendError('Throwable Error', $errorDetail);
        }
    }
}
