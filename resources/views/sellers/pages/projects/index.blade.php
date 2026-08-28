@extends('sellers.layout.layout')

@section('title', 'Seller | Projects')

@section('sellers-content')
    @include('crm.projects._index', [
        'indexRoute' => route('seller.projects.index'),
        'storeRoute' => route('seller.projects.store'),
        'showRoute'  => fn ($project) => route('seller.projects.show', $project),
    ])
@endsection
