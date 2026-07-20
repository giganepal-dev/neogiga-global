@extends('portal.shell')
@php($portal = [
    'slug' => 'distributor',
    'name' => 'Distributor Portal',
    'nav' => [
        ['icon' => 'dashboard', 'label' => 'Dashboard', 'href' => '/distributor', 'pattern' => 'distributor'],
        ['icon' => 'user', 'label' => 'Profile', 'href' => '/distributor/profile', 'pattern' => 'distributor/profile*'],
        ['icon' => 'products', 'label' => 'Products', 'href' => '/distributor/products', 'pattern' => 'distributor/products*'],
        ['icon' => 'orders', 'label' => 'Orders', 'href' => '/distributor/orders', 'pattern' => 'distributor/orders*'],
    ],
])
