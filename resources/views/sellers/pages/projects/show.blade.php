@extends('sellers.layout.layout')

@section('title', 'Seller | '.$project->title)

@section('sellers-content')
    @include('crm.projects._show', [
        'indexRoute'       => route('seller.projects.index'),
        'updateRoute'      => route('seller.projects.update', $project),
        'destroyRoute'     => route('seller.projects.destroy', $project),
        'storeTaskRoute'   => route('seller.projects.tasks.store', $project),
        'updateTaskRoute'  => fn ($task) => route('seller.projects.tasks.update', [$project, $task]),
        'destroyTaskRoute' => fn ($task) => route('seller.projects.tasks.destroy', [$project, $task]),
    ])
@endsection
