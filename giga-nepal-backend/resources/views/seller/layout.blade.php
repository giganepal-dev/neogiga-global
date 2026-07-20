@extends('portal.shell')
@php($portal = [
    'slug' => 'seller',
    'name' => 'Seller Portal',
    'nav' => [
        ['icon' => 'dashboard', 'label' => 'Dashboard', 'href' => '/seller', 'pattern' => 'seller'],
        ['icon' => 'products', 'label' => 'My Products', 'href' => '/seller/products', 'pattern' => 'seller/products*'],
        ['icon' => 'orders', 'label' => 'My Orders', 'href' => '/seller/orders', 'pattern' => 'seller/orders*'],
    ],
])
