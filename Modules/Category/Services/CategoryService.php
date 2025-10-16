<?php

namespace Modules\Category\Services;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
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

        return responseHelper('Categories retrieved successfully.',200, CategoryResource::collection($data));

    }
//    public function listWithProducts($request): JsonResponse
//    {
//        $params = $request->all();
//
//        $query = $this->model
//            ->with([
//                'products' => function ($q) {
//                    $q->orderByDesc('sales_count')->limit(10);
//                },
//                'children.products' => function ($q) {
//                    $q->orderByDesc('sales_count')->limit(10);
//                }
//            ])
//            ->withCount('children')
//            ->orderByDesc('children_count');
//
//        $query = filterLike($query, ['name', 'description'], $params);
//
//        $data = $query->get();
//
//        return responseHelper(
//            'Categories retrieved successfully.',
//            200,
//            CategoryResource::collection($data)
//        );
//    }
    public function listWithProducts($request): JsonResponse
    {
        $params = $request->all();

        $query = $this->model
            ->with([
                'products' => function ($q) {
                    $q->with(['colors', 'sizes', 'images', 'category', 'brand'])
                        ->withAvg('reviews', 'rate')
                        ->withCount('reviews')
                        ->orderByDesc('sales_count')
                        ->limit(10);
                },
                'children'
            ])
            ->withCount('children')
            ->orderByDesc('children_count');

        $query = filterLike($query, ['name', 'description'], $params);

        $data = $query->get();

        $data->each(function ($category) {
            $category->products->transform(function ($product) {
                $product->rate = $product->reviews_avg_rate ? round($product->reviews_avg_rate, 2) : 0;
                $product->rate_count = $product->reviews_count;
                $product->is_favorite = Auth::check()
                    ? $product->favoritedBy()->where('user_id', Auth::id())->exists()
                    : false;
                return $product;
            });
        });

        return responseHelper(
            'Categories with products retrieved successfully.',
            200,
            CategoryResource::collection($data)
        );
    }



    /**
     * @param int $id
     * @return JsonResponse
     */
    public function details(int $id): JsonResponse
    {
        try {
            $category = $this->model->with('children')->findOrFail($id);

            return responseHelper('Category details retrieved successfully.',200, CategoryResource::make($category));

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return responseHelper('Category not found.',403, []);
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
            function () use ($validated, $id) {
                $category = $this->model->findOrFail($id);
                $category->update($validated);
                return $category->refresh();
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
