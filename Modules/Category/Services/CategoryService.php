<?php

namespace Modules\Category\Services;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Modules\Category\Http\Entities\Category;
use Modules\Category\Http\Transformers\CategoryResource;

class CategoryService
{
    private Category $model;

    /**
     * @param Category $model
     */
    function __construct(Category $model)
    {
        $this->model = $model;
    }

    /**
     * @param $request
     * @return JsonResponse
     */
    public function list($request): JsonResponse
    {
        $params = $request->all();
        $query = $this->model->query();

        $query = filterLike($query, ['name', 'description'], $params);
        $data = $query->orderBy('id', 'desc')->get();

        return response()->json([
            'success' => 200,
            'message' => __('Categories retrieved successfully.'),
            'data' => CategoryResource::collection($data),
        ]);
    }

    /**
     * @param int $id
     * @return JsonResponse
     */
    public function details(int $id): JsonResponse
    {
        try {
            $category = $this->model->with('children')->findOrFail($id);

            return response()->json([
                'success' => 200,
                'message' => __('Category details retrieved successfully.'),
                'data' => CategoryResource::make($category),
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => 404,
                'message' => __('Category not found.'),
                'data' => [],
            ]);
        }
    }

    /**
     * @param $request
     * @return JsonResponse
     */
    public function add($request): JsonResponse
    {
        $validated = $request->validated();

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '_' . $image->getClientOriginalName();

            if (!Storage::disk('public')->exists('categories')) {
                Storage::disk('public')->makeDirectory('categories', 0755, true);
            }

            $image->storeAs('categories', $imageName, 'public');

            $validated['image'] = 'categories/' . $imageName;
        }

        return handleTransaction(
            fn() => $this->model->create($validated),
            'Category added successfully.',
            CategoryResource::class
        );
    }

    /**
     * @param $request
     * @param int $id
     * @return JsonResponse
     */
    public function update($request, int $id): JsonResponse
    {
        $validated = $request->validated();

        return handleTransaction(
            function () use ($validated, $id) {
                $category = $this->model->findOrFail($id);
                $category->update($validated);
                return $category;
            },
            'Category updated successfully.',
            CategoryResource::class
        );
    }


    /**
     * @param int $id
     * @return JsonResponse
     */
    public function delete(int $id): JsonResponse
    {
        return handleTransaction(
            function () use ($id) {
                $category = $this->model->findOrFail($id);
                $category->delete();
                return $category;
            },
            'Category deleted successfully.'
        );
    }

}
