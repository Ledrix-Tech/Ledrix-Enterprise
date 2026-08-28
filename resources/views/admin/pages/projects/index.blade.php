@extends('admin.layout.layout')

@section('title', 'Admin | Projects')

@section('admin-content')
    @include('crm.projects._index', [
        'indexRoute' => route('admin.projects.index'),
        'storeRoute' => route('admin.projects.store'),
        'showRoute'  => fn ($project) => route('admin.projects.show', $project),
    ])
@endsection
