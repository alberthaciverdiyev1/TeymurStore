<?php

use Illuminate\Database\Eloquent\Builder;

if (!function_exists('rangeFilter')) {
    /**
     * Apply an exact, between, or min/max filter on a query.
     *
     * @param Builder $query
     * @param string $column
     * @param array $params
     * @return Builder
     */
    function rangeFilter(Builder $query, string $column, array $params): Builder
    {
        $exact = $params[$column] ?? null;
        $min = $params[$column . '_min'] ?? null;
        $max = $params[$column . '_max'] ?? null;

        if (!empty($exact) && empty($min) && empty($max)) {
            $query->where($column, $exact);
        } else if (!empty($min) && !empty($max)) {
            $query->whereBetween($column, [$min, $max]);
        } else {
            if (!empty($min)) {
                $query->where($column, '>=', $min);
            }
            if (!empty($max)) {
                $query->where($column, '<=', $max);
            }
        }

        return $query;
    }


}
if (!function_exists('rangeDateFilter')) {
    /**
     * @param Builder $query
     * @param string $column
     * @param array $params
     * @return Builder
     */
    function rangeDateFilter(Builder $query, string $column, array $params): Builder
    {
        $min = $params[$column . '_min'] ?? null;
        $max = $params[$column . '_max'] ?? null;

        if ($min && !$max) {
            $max = now();
        } elseif (!$min && $max) {
            $min = '1970-01-01';
        } elseif (!$min && !$max) {
            return $query;
        }

        return $query->whereBetween($column, [$min, $max]);
    }
}
if (!function_exists('orderBy')) {
    function orderBy(Builder $query, array $params): Builder
    {
        if (!empty($params['order_by']) && !empty($params['order_type'])) {
            $query->orderBy($params['order_by'], $params['order_type']);
        } else if (!empty($params['order_by']) && empty($params['order_type'])) {
            $query->orderBy($params['order_by'], 'desc');
        } else {
            $query->orderBy('updated_at', 'desc');
        }

        return $query;
    }
}


if (!function_exists('filterLike')) {
    function filterLike(Builder $query, array|string $columns, array $params): Builder
    {
        if (is_array($columns)) {
            foreach ($columns as $column) {
                if (!empty($params[$column])) {
                    $query->where($column, 'like', '%' . $params[$column] . '%');
                }
            }
        } elseif (is_string($columns) && !empty($params[$columns])) {
            $query->where($columns, 'like', '%' . $params[$columns] . '%');
        }

        return $query;
    }
}
