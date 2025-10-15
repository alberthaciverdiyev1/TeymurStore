<?php

namespace Modules\Banner\Services;

use Modules\Banner\Http\Entities\Banner;
use Modules\Banner\Http\Resources\BannerResource;

class BannerService
{
    private Banner $model;

    function __construct(Banner $model)
    {
        $this->model = $model;
    }
    public function getAll($request): array|\Illuminate\Http\JsonResponse
    {
        $query = $this->model::query();
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }
        $banners = $query->get();

        return responseHelper('Banners retrieved successfully', 200, BannerResource::collection($banners));
    }

    public function add($request)
    {
        $params = $request->all();

        if (empty($params['image'])) {
            return responseHelper('Image is required', 400);
        }
        if (empty($params['type'])) {
            return responseHelper('Type is required', 400);
        }

        return handleTransaction(function () use ($params) {
            $this->model::create([
                'image' => $params['image'],
                'type' => $params['type'],
            ]);

            return responseHelper('Banner added successfully', 200);
        }, 'Error occurred while adding banner');
    }

    public function delete($id)
    {
        $banner = $this->model::find($id);
        if (!$banner) {
            return responseHelper('Banner not found', 404);
        }

        return handleTransaction(function () use ($banner) {
            $banner->delete();

            return responseHelper('Banner deleted successfully', 200);
        }, 'Error occurred while deleting banner');
    }

}
