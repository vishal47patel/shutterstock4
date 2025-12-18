<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\Image;
use Illuminate\Container\Attributes\Storage;
use Validator;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

class ImageController extends BaseController
{
    public function imageList(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'page'       => 'nullable|integer|min:1',
                'per_page'   => 'nullable|integer|min:1|max:100',
                'status'     => 'nullable|in:Approved,Pending,Rejected',
                'category'   => 'nullable|integer|min:0',
                'user_id'    => 'nullable|integer',
                'search'     => 'nullable|string|max:100',
                'sort_by'    => 'nullable|string',
                'sort_order' => 'nullable|in:asc,desc',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors());
            }

            $perPage   = $request->per_page ?? 10;
            $sortOrder = $request->sort_order ?? 'desc';

            /*
        |--------------------------------------------------------------------------
        | Allowed fields
        |--------------------------------------------------------------------------
        */
            $searchableFields = [
                'title',
                'status',
                'user_id',
                'category',
            ];

            $sortableFields = [
                'id',
                'title',
                'price',
                'status',
                'user_id',
                'category',
                'created_at',
            ];

            $sortBy = in_array($request->sort_by, $sortableFields)
                ? $request->sort_by
                : 'created_at';

            $query = Image::query();

            /*
        |--------------------------------------------------------------------------
        | Exact Filters
        |--------------------------------------------------------------------------
        */
            foreach ($request->only($searchableFields) as $field => $value) {
                if ($value !== null && $value !== '') {
                    if ($field === 'category' && $value == 0) {
                        $query->where(function ($q) {
                            $q->whereNull('category')
                                ->orWhere('category', 0);
                        });
                    } else {
                        $query->where($field, $value);
                    }
                }
            }

            /*
        |--------------------------------------------------------------------------
        | Global Search
        |--------------------------------------------------------------------------
        */
            if ($request->filled('search')) {
                $search = trim($request->search);

                $query->where(function ($q) use ($search) {
                    $q->where('title', 'LIKE', "%{$search}%")
                        ->orWhere('desc', 'LIKE', "%{$search}%")
                        ->orWhere('alt', 'LIKE', "%{$search}%");
                });
            }

            /*
        |--------------------------------------------------------------------------
        | Sorting & Pagination
        |--------------------------------------------------------------------------
        */
            $images = $query
                ->orderBy($sortBy, $sortOrder)
                ->paginate($perPage);

            /*
        |--------------------------------------------------------------------------
        | Append Image URL
        |--------------------------------------------------------------------------
        */
            $images->getCollection()->transform(function ($image) {
                $image->image_url = $image->image
                    ? asset('storage/' . $image->image)
                    : null;
                return $image;
            });

            return $this->sendResponse($images, 'Image list fetched successfully.');
        } catch (\Throwable $e) {
            return $this->sendError('Exception Error', [
                'error_message' => $e->getMessage(),
                'file'         => $e->getFile(),
                'line'         => $e->getLine(),
                'error_type'   => get_class($e),
            ]);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'image'    => 'required|image|mimes:jpg,jpeg,png|max:2048',
                'title'    => 'required|string|max:150',
                'price'    => 'required|numeric',
                'category' => 'required|integer',
                'status'   => 'nullable|in:Approved,Pending,Rejected',
                'alt'      => 'nullable|string',
                'desc'     => 'nullable|string',
                'user_id'  => 'required|integer',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors());
            }

            // Upload image
            $path = $request->file('image')->store('images', 'public');

            $image = Image::create([
                'image'    => $path,
                'title'    => $request->title,
                'price'    => $request->price,
                'desc'     => $request->desc ?? '',
                'category' => $request->category ?? '',
                'status'   => $request->status ?? 'Pending',
                'alt'      => $request->alt ?? '',
                'user_id'  => $request->user_id,
            ]);

            return $this->sendResponse($image, 'Image uploaded successfully.');
        } catch (\Throwable $e) {
            return $this->sendError('Exception Error', [
                'error_message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'error_type' => get_class($e),
            ]);
        }
    }

    public function update(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'id'       => 'required|integer|exists:images,id',
                'image'    => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
                'title'    => 'nullable|string|max:150',
                'price'    => 'nullable|numeric',
                'category' => 'nullable|integer',
                'status'   => 'nullable|in:Approved,Pending,Rejected',
                'alt'      => 'nullable|string',
                'desc'     => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors());
            }

            $image = Image::findOrFail($request->id);

            // Replace image if received
            if ($request->hasFile('image')) {
                Storage::disk('public')->delete($image->image);
                $image->image = $request->file('image')->store('images', 'public');
            }

            // Update only received fields
            if ($request->filled('title')) {
                $image->title = $request->title;
            }

            if ($request->filled('price')) {
                $image->price = $request->price;
            }

            if ($request->has('desc')) {
                $image->desc = $request->desc ?? '';
            }

            if ($request->filled('category')) {
                $image->category = $request->category;
            }

            $image->status = $request->status ?? 'Pending';
            $image->alt    = $request->alt ?? '';

            $image->save();

            return $this->sendResponse($image, 'Image updated successfully.');
        } catch (\Throwable $e) {
            return $this->sendError('Exception Error', [
                'error_message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'error_type' => get_class($e),
            ]);
        }
    }

    public function destroy(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'id' => 'required|integer|exists:images,id',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors());
            }

            $image = Image::findOrFail($request->id);
            $image->delete();

            return $this->sendResponse([], 'Image deleted successfully.');
        } catch (\Throwable $e) {
            return $this->sendError('Exception Error', [
                'error_message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        }
    }
}
