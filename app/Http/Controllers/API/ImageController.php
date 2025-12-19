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
    // public function imageList(Request $request): JsonResponse
    // {
    //     try {
    //         $validator = Validator::make($request->all(), [
    //             'page'       => 'nullable|integer|min:1',
    //             'per_page'   => 'nullable|integer|min:1|max:100',
    //             'status'     => 'nullable|in:Approved,Pending,Rejected',
    //             'category_id'   => 'nullable|integer|min:0',
    //             'user_id'    => 'nullable|integer',
    //             'search'     => 'nullable|string|max:100',
    //             'sort_by'    => 'nullable|string',
    //             'sort_order' => 'nullable|in:asc,desc',
    //         ]);

    //         if ($validator->fails()) {
    //             return $this->sendError('Validation Error.', $validator->errors());
    //         }

    //         $perPage   = $request->per_page ?? 10;
    //         $sortOrder = $request->sort_order ?? 'desc';

    //         /*
    //     |--------------------------------------------------------------------------
    //     | Allowed fields
    //     |--------------------------------------------------------------------------
    //     */
    //         $searchableFields = [
    //             'title',
    //             'tags',
    //             'status',
    //             'user_id',
    //             'category_id',
    //         ];

    //         $sortableFields = [
    //             'id',
    //             'title',
    //             'tags',
    //             'price',
    //             'status',
    //             'user_id',
    //             'category_id',
    //             'created_at',
    //         ];

    //         $sortBy = in_array($request->sort_by, $sortableFields)
    //             ? $request->sort_by
    //             : 'created_at';

    //         $query = Image::query();

    //         /*
    //     |--------------------------------------------------------------------------
    //     | Exact Filters
    //     |--------------------------------------------------------------------------
    //     */
    //         foreach ($request->only($searchableFields) as $field => $value) {
    //             if ($value !== null && $value !== '') {
    //                 if ($field === 'category_id' && $value == 0) {
    //                     $query->where(function ($q) {
    //                         $q->whereNull('category_id')
    //                             ->orWhere('category_id', 0);
    //                     });
    //                 } else {
    //                     $query->where($field, $value);
    //                 }
    //             }
    //         }

    //         /*
    //     |--------------------------------------------------------------------------
    //     | Global Search
    //     |--------------------------------------------------------------------------
    //     */
    //         if ($request->filled('search')) {
    //             $search = trim($request->search);

    //             $query->where(function ($q) use ($search) {
    //                 $q->where('title', 'LIKE', "%{$search}%")
    //                     ->orWhere('tags', 'LIKE', "%{$search}%")
    //                     ->orWhere('desc', 'LIKE', "%{$search}%")
    //                     ->orWhere('alt', 'LIKE', "%{$search}%");
    //             });
    //         }

    //         /*
    //     |--------------------------------------------------------------------------
    //     | Sorting & Pagination
    //     |--------------------------------------------------------------------------
    //     */
    //         $images = $query
    //             ->orderBy($sortBy, $sortOrder)
    //             ->paginate($perPage);

    //         /*
    //     |--------------------------------------------------------------------------
    //     | Append Image URL
    //     |--------------------------------------------------------------------------
    //     */
    //         $images->getCollection()->transform(function ($image) {
    //             $image->image_url = $image->image
    //                 ? asset('storage/' . $image->image)
    //                 : null;
    //             return $image;
    //         });

    //         return $this->sendResponse($images, 'Image list fetched successfully.');
    //     } catch (\Throwable $e) {
    //         return $this->sendError('Exception Error', [
    //             'error_message' => $e->getMessage(),
    //             'file'         => $e->getFile(),
    //             'line'         => $e->getLine(),
    //             'error_type'   => get_class($e),
    //         ]);
    //     }
    // }
    public function imageList(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'page'        => 'nullable|integer|min:1',
                'per_page'    => 'nullable|integer|min:1|max:100',
                'status'      => 'nullable|in:Approved,Pending,Rejected',
                'category_id' => 'nullable|integer|min:0',
                'user_id'     => 'nullable|integer',
                'search'      => 'nullable|string|max:100',
                'sort_by'     => 'nullable|string',
                'sort_order'  => 'nullable|in:asc,desc',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors());
            }

            $perPage   = $request->per_page ?? 10;
            $sortOrder = $request->sort_order ?? 'desc';

            $searchableFields = [
                'status',
                'user_id',
                'category_id',
            ];

            $sortableFields = [
                'id',
                'title',
                'price',
                'status',
                'created_at',
            ];

            $sortBy = in_array($request->sort_by, $sortableFields)
                ? $request->sort_by
                : 'created_at';

            /*
        |--------------------------------------------------------------------------
        | Query with SELECT (IMPORTANT)
        |--------------------------------------------------------------------------
        */
            $query = Image::select([
                'id',
                'image',
                'title',
                'tags',
                'price',
                'status',
            ]);

            /*
        |--------------------------------------------------------------------------
        | Exact Filters
        |--------------------------------------------------------------------------
        */
            foreach ($request->only($searchableFields) as $field => $value) {
                if ($value !== null && $value !== '') {
                    if ($field === 'category_id' && $value == 0) {
                        $query->where(function ($q) {
                            $q->whereNull('category_id')
                                ->orWhere('category_id', 0);
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
                        ->orWhere('tags', 'LIKE', "%{$search}%")
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
                'tags'    => 'nullable',
                'price'    => 'required|numeric',
                'category_id' => 'nullable|integer',
                'status'   => 'nullable|in:Approved,Pending,Rejected',
                'alt'      => 'nullable|string',
                'desc'     => 'nullable|string',
                'user_id'  => 'required|integer',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors());
            }

            // Upload image
            //$path = $request->file('image')->store('images', 'public');
            $imageFile = $request->file('image');
            $filename  = time() . '_' . $imageFile->getClientOriginalName();
            $imageFile->move(public_path('images'), $filename);

            $path = 'images/' . $filename;

            $image = Image::create([
                'image'    => $path,
                'title'    => $request->title,
                'tags'    => $request->tags ?? '',
                'price'    => $request->price,
                'desc'     => $request->desc ?? '',
                'category_id' => $request->category_id ?? '',
                'status'   => $request->status ?? 'Pending',
                'alt'      => $request->alt ?? '',
                'user_id'  => $request->user_id,
            ]);

            $responseData = [
                'image' => $image->image,
                'title' => $image->title,
                'tags'  => $image->tags,
                'price' => $image->price,
            ];

            return $this->sendResponse($responseData, 'Image uploaded successfully.');
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
                'tags'    => 'nullable',
                'price'    => 'nullable|numeric',
                'category_id' => 'nullable|integer',
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
                //Storage::disk('public')->delete($image->image);
                //$image->image = $request->file('image')->store('images', 'public');
                $imageFile = $request->file('image');
                $filename  = time() . '_' . $imageFile->getClientOriginalName();
                $imageFile->move(public_path('images'), $filename);

                $image->image = 'images/' . $filename;
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

            if ($request->filled('category_id')) {
                $image->category_id = $request->category_id;
            }

            $image->status = $request->status ?? 'Pending';
            $image->alt    = $request->alt ?? '';
            $image->tags    = $request->tags ?? '';

            $image->save();

            $responseData = [
                'image' => $image->image,
                'title' => $image->title,
                'tags'  => $image->tags,
                'price' => $image->price,
            ];

            return $this->sendResponse($responseData, 'Image updated successfully.');
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
