<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Validator;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class UserController extends BaseController
{
    public function updateProfile(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return $this->sendError('Unauthorised.', ['error' => 'User not logged in']);
            }

            $validator = Validator::make($request->all(), [
                'email'    => 'nullable|email|unique:users,email,' . $user->id,
                'username' => 'nullable|string|max:50|unique:users,username,' . $user->id,
                'phone'    => 'nullable|string|max:20',
                'bio'      => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors());
            }

            // UPDATE ONLY FIELDS PASSED IN REQUEST
            $user->update([
                'email'    => $request->email ?? $user->email,
                'username' => $request->username ?? $user->username,
                'phone'    => $request->phone ?? $user->phone,
                'bio'      => $request->bio ?? $user->bio,
            ]);

            return $this->sendResponse(
                [
                    'name'     => $user->name,
                    'username' => $user->username,
                    'phone'    => $user->phone,
                    'bio'      => $user->bio,
                    'email'    => $user->email
                ],
                'Profile updated successfully'
            );
        } catch (\Throwable $e) {

            // FULL ERROR DETAILS
            $errorDetail = [
                'error_message' => $e->getMessage(),
                'file'          => $e->getFile(),
                'line'          => $e->getLine(),
                'error_type'    => get_class($e),
            ];

            return $this->sendError('Throwable Error', $errorDetail);
        }
    }

    public function changePassword(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return $this->sendError('Unauthorised.', ['error' => 'User not logged in']);
            }

            $validator = Validator::make($request->all(), [
                'current_password' => 'required|string|min:6',
                'new_password'     => [
                    'required',
                    'string',
                    'min:8',
                    'confirmed',
                    'regex:/[A-Z]/',     // Uppercase
                    'regex:/[0-9]/',     // Number
                    'regex:/[@$!%*?&]/'  // Special character
                ], // expects new_password_confirmation
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors());
            }

            // Check if current password matches
            if (!\Hash::check($request->current_password, $user->password)) {
                return $this->sendError('Error', ['current_password' => 'Current password is incorrect.']);
            }

            // Update password
            $user->password = \Hash::make($request->new_password);
            $user->save();

            return $this->sendResponse([], 'Password changed successfully.');
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


    public function deleteUser(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'id' => 'required|exists:users,id',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors());
            }

            $user = User::find($request->id);

            if (!$user) {
                return $this->sendError('User not found.');
            }

            $user->delete(); // Soft delete

            return $this->sendResponse([], 'User deleted successfully.');
        } catch (\Throwable $e) {
            return $this->sendError('Throwable Error', [
                'error_message' => $e->getMessage(),
                'file'          => $e->getFile(),
                'line'          => $e->getLine(),
                'error_type'    => get_class($e),
            ]);
        }
    }
    public function restoreUser(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'id' => 'required|exists:users,id',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors());
            }

            $user = User::onlyTrashed()->find($request->id);

            if (!$user) {
                return $this->sendError('User not found or not deleted.');
            }

            $user->restore();

            return $this->sendResponse([], 'User restored successfully.');
        } catch (\Throwable $e) {
            return $this->sendError('Throwable Error', [
                'error_message' => $e->getMessage(),
                'file'          => $e->getFile(),
                'line'          => $e->getLine(),
                'error_type'    => get_class($e),
            ]);
        }
    }
    public function forceDeleteUser(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'id' => 'required|exists:users,id',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors());
            }

            $user = User::onlyTrashed()->find($request->id);

            if (!$user) {
                return $this->sendError('User not found or not deleted.');
            }

            $user->forceDelete();

            return $this->sendResponse([], 'User permanently deleted.');
        } catch (\Throwable $e) {
            return $this->sendError('Throwable Error', [
                'error_message' => $e->getMessage(),
                'file'          => $e->getFile(),
                'line'          => $e->getLine(),
                'error_type'    => get_class($e),
            ]);
        }
    }





    public function userList(Request $request): JsonResponse
    {
        try {
            // ?role=admin&subscription=premium&status=active&deleted=all&sort_by=email&sort_order=asc&page=1&per_page=20

            //$query = User::query();
            $query = User::select('id', 'name', 'email', 'username', 'phone', 'bio', 'subscription', 'status');
            $query->where('id', '!=', Auth::id());
            // Deleted filter
            if ($request->deleted == '1') {
                $query->onlyTrashed();
            } elseif ($request->deleted == 'all') {
                $query->withTrashed();
            }

            // Dynamic filters
            if ($request->filled('role')) {
                $query->where('role', $request->role);
            }

            if ($request->filled('subscription')) {
                $query->where('subscription', $request->subscription);
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%");
                });
            }

            // ---------------------------
            // OPTIMIZED SORT SYSTEM
            // ---------------------------
            $sortable = [
                'id',
                'name',
                'email',
                'username',
                'role',
                'subscription',
                'status',
                'created_at'
            ];

            $sortBy    = $request->get('sort_by', 'id');
            $sortOrder = $request->get('sort_order', 'desc');

            if (!in_array($sortBy, $sortable)) {
                $sortBy = 'id';
            }

            if (!in_array($sortOrder, ['asc', 'desc'])) {
                $sortOrder = 'desc';
            }

            $query->orderBy($sortBy, $sortOrder);

            // ---------------------------
            // PAGINATION
            // ---------------------------
            $perPage = $request->get('per_page', 10); // default 10 items
            $users = $query->paginate($perPage);

            $users->getCollection()->transform(function ($user) {
                return $user->makeHidden(['created_at', 'updated_at', 'deleted_at', 'role', 'email_verified_at']);
            });

            return $this->sendResponse($users, 'User list fetched successfully');
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
    public function userStats(): JsonResponse
    {
        try {

            $totalUsers = User::count();

            $activeUsers = User::where('status', 'active')->count();

            $subscribedUsers = User::where('subscription', '!=', 'free')->count();

            // New users registered in current month
            $newUsers = User::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count();

            // Count of deleted (soft-deleted) users
            $deletedUsers = User::onlyTrashed()->count();

            // Calculate percentages safely
            $percent = fn($count) => $totalUsers > 0 ? round(($count / $totalUsers) * 100, 2) : 0;

            return $this->sendResponse([
                'total_users'       => $totalUsers,
                'active_users'      => $activeUsers,
                'active_users_pct'  => $percent($activeUsers),
                'subscribed_users'  => $subscribedUsers,
                'subscribed_users_pct' => $percent($subscribedUsers),
                'new_users_month'   => $newUsers,
                'new_users_month_pct' => $percent($newUsers),
                //'deleted_users'     => $deletedUsers,
                //'deleted_users_pct' => $percent($deletedUsers),
            ], 'User statistics fetched successfully.');
        } catch (\Throwable $e) {

            return $this->sendError('Throwable Error', [
                'error_message' => $e->getMessage(),
                'file'          => $e->getFile(),
                'line'          => $e->getLine(),
                'error_type'    => get_class($e),
            ]);
        }
    }

    public function updateStatus(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'id'           => 'required|exists:users,id',
                'status'       => 'nullable|in:active,inactive,blocked,suspended',
                'subscription' => 'nullable|in:free,premium,pro',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors());
            }

            $user = User::find($request->id);

            if (!$user) {
                return $this->sendError('User not found.');
            }

            $updatedFields = [];

            if ($request->has('status')) {
                $user->status = $request->status;
                $updatedFields['status'] = $request->status;
            }

            if ($request->has('subscription')) {
                $user->subscription = $request->subscription;
                $updatedFields['subscription'] = $request->subscription;
            }

            if (empty($updatedFields)) {
                return $this->sendError('No fields to update.');
            }

            $user->save();

            // Always include ID for reference
            $updatedFields['id'] = $user->id;

            return $this->sendResponse($updatedFields, 'User updated successfully.');
        } catch (\Throwable $e) {
            return $this->sendError('Throwable Error', [
                'error_message' => $e->getMessage(),
                'file'          => $e->getFile(),
                'line'          => $e->getLine(),
                'error_type'    => get_class($e),
            ]);
        }
    }
}
