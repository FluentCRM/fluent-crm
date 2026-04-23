<?php

namespace FluentCrm\App\Services\ExternalIntegrations\FluentCart;

use FluentCart\Api\Taxonomy;
use FluentCart\App\Helpers\Status;
use FluentCart\App\Models\Coupon;
use FluentCart\App\Models\Product;
use FluentCart\App\Models\ProductVariation;
use FluentCart\Framework\Database\Orm\Collection;
use FluentCart\Framework\Support\Str;
use FluentCart\App\Models\Customer;

class CartHelper
{
    public static function getFluentCartProducts($items, $search, $ids = [])
    {
        $search = (string)$search;
        $ids = is_array($ids) ? $ids : [];

        try {
            $productQuery = Product::query()->published();
            if ($search) {
                $productQuery->where('post_title', 'like', '%' . $search . '%');
            }

            $queried = $productQuery
                ->orderBy('post_title')
                ->limit(50)
                ->get(['ID', 'post_title']);

            $options = [];
            $pushedIds = [];
            foreach ($queried as $product) {
                $options[] = [
                    'id'    => $product->ID,
                    'title' => $product->ID . '# ' . $product->post_title,
                ];
                $pushedIds[] = $product->ID;
            }

            if ($ids) {
                $remaining = array_diff($ids, $pushedIds);
                if ($remaining) {
                    $extraProducts = Product::query()->published()->whereIn('ID', $remaining)->get(['ID', 'post_title']);
                    foreach ($extraProducts as $product) {
                        $options[] = [
                            'id'    => $product->ID,
                            'title' => $product->ID . '# ' . $product->post_title,
                        ];
                    }
                }
            }

            return $options;
        } catch (\Exception $e) {
            error_log($e->getMessage());
            return [];
        }
    }

    public static function getFluentCartCoupons($items, $search, $ids)
    {
        try {
            $coupons = Coupon::all(['id', 'title'])
                ->map(function ($coupon) {
                    return [
                        'id'    => $coupon->id,
                        'title' => $coupon->title,
                    ];
                })
                ->toArray();
        } catch (\Exception $e) {
            error_log($e->getMessage());
            $coupons = [];
        }

        return $coupons;
    }

    public static function getFluentCartProductCategories($items, $search, $ids)
    {
        try {
            $taxonomies = Taxonomy::getTaxonomies();

            $taxonomies = Collection::make($taxonomies)
                ->map(function ($taxonomy) {
                    return [
                        'name'  => $taxonomy,
                        'label' => Str::headline($taxonomy),
                        'terms' => Taxonomy::getFormattedTerms($taxonomy),
                    ];
                });

            $categories = Collection::make($taxonomies['product-categories']['terms'])
                ->map(function ($term) {
                    return [
                        'id'    => $term['value'],
                        'title' => $term['label'],
                    ];
                })
                ->toArray();
        } catch (\Exception $e) {
            error_log($e->getMessage());
            $categories = [];
        }

        return $categories;
    }

    public static function getProductCategoriesByIds($ids)
    {
        try {
            $products = Product::with('wp_terms')->whereIn('id', $ids)->get();

            $categories = $products->flatMap(function ($product) {
                return $product->wp_terms->pluck('term_taxonomy_id');
            })->unique()->values()->toArray();
        } catch (\Exception $e) {
            error_log($e->getMessage());
            $categories = [];
        }

        return $categories;
    }

    public static function getFluentCartSubscriptionProducts($items, $search, $ids)
    {
        $search = (string)$search;
        $ids = is_array($ids) ? $ids : [];

        try {
            $variationQuery = ProductVariation::query()
                ->where('payment_type', 'subscription')
                ->where('item_status', 'active');

            if ($search) {
                $variationQuery->where('variation_title', 'like', '%' . $search . '%');
            }

            $productIds = $variationQuery->pluck('post_id')->unique()->slice(0, 50)->values();

            $pushedIds = $productIds->toArray();
            if ($ids) {
                $appendIds = array_diff($ids, $pushedIds);
                if ($appendIds) {
                    $productIds = $productIds->merge($appendIds);
                }
            }

            if ($productIds->isEmpty()) {
                return [];
            }

            $products = Product::query()
                ->published()
                ->whereIn('ID', $productIds->toArray())
                ->orderBy('post_title')
                ->get(['ID', 'post_title']);

            $formatted = [];
            foreach ($products as $product) {
                $formatted[] = [
                    'id'    => $product->ID,
                    'title' => $product->ID . '# ' . $product->post_title,
                ];
            }
            return $formatted;
        } catch (\Exception $e) {
            error_log($e->getMessage());
            return [];
        }
    }

    public static function prepareSubsciberData($customer)
    {
        if(!is_object($customer)) {
            $customer = (object) $customer;
        }
        return [
            'email' => $customer->email,
            'first_name' => $customer->first_name,
            'last_name' => $customer->last_name,
            'full_name' => $customer->first_name . ' ' . $customer->last_name,
            'user_id' => $customer->user_id,
            'postal_code' => $customer->postcode,
            'country' => $customer->country,
            'state' => $customer->state,
            'city' => $customer->city,
            'phone' => $customer->phone,
        ];
    }

    public static function getCustomersByProductIds($productIds, $offset = 0, $limit = 100)
    {
        $customers = [];
        try {

            $customers = Customer::query()->whereHas('success_order_items', function ($q) use ($productIds) {
                $q->whereIn('post_id', $productIds);
            })->offset($offset)->limit($limit)->get();

        } catch (\Exception $e) {
            error_log($e->getMessage());
        }

        return $customers;
    }

    public static function getPurchasedProductsByCustomerId($customerId)
    {
        $productIds = [];
        try {
            $orderIds = fluentCrmDb()->table('fct_orders')
                ->where('customer_id', $customerId)
                ->pluck('id');

            $productIds = fluentCrmDb()->table('fct_order_items')
                ->whereIn('order_id', $orderIds)
                ->pluck('post_id');
        } catch (\Exception $e) {
            error_log($e->getMessage());
        }

        return $productIds;
    }
}
