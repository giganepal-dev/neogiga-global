@extends('portal.shell')
@php($portal = [
    'slug' => 'reseller',
    'name' => 'Reseller Portal',
    'nav' => [
        ['icon' => 'dashboard', 'label' => 'Dashboard', 'href' => '/reseller', 'pattern' => 'reseller'],
        ['icon' => 'user', 'label' => 'Profile', 'href' => '/reseller/profile', 'pattern' => 'reseller/profile*'],
        ['icon' => 'products', 'label' => 'Products', 'href' => '/reseller/products', 'pattern' => 'reseller/products*'],
        ['icon' => 'orders', 'label' => 'Orders', 'href' => '/reseller/orders', 'pattern' => 'reseller/orders*'],
    ],
])
