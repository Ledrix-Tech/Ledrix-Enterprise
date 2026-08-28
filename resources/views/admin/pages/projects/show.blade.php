@extends('admin.layout.layout')

@section('title', 'Admin | '.$project->title)

@section('admin-content')
    @include('crm.projects._show', [
        'indexRoute'       => route('admin.projects.index'),
        'updateRoute'      => route('admin.projects.update', $project),
        'destroyRoute'     => route('admin.projects.destroy', $project),
        'storeTaskRoute'   => route('admin.projects.tasks.store', $project),
        'updateTaskRoute'  => fn ($task) => route('admin.projects.tasks.update', [$project, $task]),
        'destroyTaskRoute' => fn ($task) => route('admin.projects.tasks.destroy', [$project, $task]),
    ])
@endsection
