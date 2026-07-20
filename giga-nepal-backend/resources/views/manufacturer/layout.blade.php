@extends('portal.shell')
@php($portal = [
    'slug' => 'manufacturer',
    'name' => 'Manufacturer Portal',
    'nav' => [
        ['icon' => 'dashboard', 'label' => 'Dashboard', 'href' => '/manufacturer', 'pattern' => 'manufacturer'],
        ['icon' => 'products', 'label' => 'Global Inventory', 'href' => '/manufacturer/inventory', 'pattern' => 'manufacturer/inventory*'],
        ['icon' => 'orders', 'label' => 'Regional Allocations', 'href' => '/manufacturer/allocations', 'pattern' => 'manufacturer/allocations*'],
        ['icon' => 'products', 'label' => 'Products', 'href' => '/manufacturer/products', 'pattern' => 'manufacturer/products*'],
        ['icon' => 'user', 'label' => 'Profile', 'href' => '/manufacturer/profile', 'pattern' => 'manufacturer/profile*'],
    ],
])
