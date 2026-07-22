@extends('portal.shell')
@php($vendor = $vendor ?? $v ?? null)
@php($portal = [
    'slug' => 'seller',
    'name' => 'Seller Portal',
    'nav' => [
        ['icon' => 'dashboard', 'label' => 'Dashboard', 'href' => '/seller', 'pattern' => 'seller'],
        ['icon' => 'products', 'label' => 'My Products', 'href' => '/seller/products', 'pattern' => 'seller/products*'],
        ['icon' => 'orders', 'label' => 'My Orders', 'href' => '/seller/orders', 'pattern' => 'seller/orders*'],
        ['icon' => 'products', 'label' => 'Inventory', 'href' => '/seller/inventory', 'pattern' => 'seller/inventory*'],
        ['icon' => 'orders', 'label' => 'Payouts', 'href' => '/seller/payouts', 'pattern' => 'seller/payouts*'],
        ['icon' => 'user', 'label' => 'Support', 'href' => '/seller/support', 'pattern' => 'seller/support*'],
        ['icon' => 'user', 'label' => 'Profile', 'href' => '/seller/profile', 'pattern' => 'seller/profile*'],
    ],
])
