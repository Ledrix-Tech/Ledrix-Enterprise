<?php

namespace App\Http\Controllers\API\Client;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Project;
use App\Support\ClientPortalAuthorization;

class ProjectController extends Controller
{
    public function index()
    {
        $client = ClientPortalAuthorization::client();

        $projects = Project::query()
            ->where(function ($query) use ($client) {
                $query->whereHas('order', fn ($q) => $q->where('client_id', $client->id))
                    ->orWhereHas('lead', fn ($q) => $q->where('client_id', $client->id));
            })
            ->with(['order:id,client_id,service_name,status,paid_at', 'projectManager:id,name', 'tasks'])
            ->latest()
            ->paginate(20);

        $awaitingOrders = Order::query()
            ->where('client_id', $client->id)
            ->whereIn('status', ['paid', 'in_progress', 'revision', 'completed'])
            ->whereDoesntHave('projects')
            ->latest('id')
            ->limit(20)
            ->get(['id', 'service_name', 'status', 'paid_at']);

        return view('clients.pages.projects.index', compact('client', 'projects', 'awaitingOrders'));
    }

    public function show(int $id)
    {
        $project = Project::query()
            ->with([
                'order:id,client_id,service_name,status,paid_at,created_at',
                'lead:id,client_id',
                'projectManager:id,name',
                'tasks' => fn ($q) => $q->orderByRaw("CASE status WHEN 'in_progress' THEN 1 WHEN 'blocked' THEN 2 WHEN 'pending' THEN 3 ELSE 4 END")->orderBy('due_date'),
            ])
            ->findOrFail($id);

        ClientPortalAuthorization::assertOwnsProject($project);

        return view('clients.pages.projects.show', [
            'client'  => ClientPortalAuthorization::client(),
            'project' => $project,
        ]);
    }
}
