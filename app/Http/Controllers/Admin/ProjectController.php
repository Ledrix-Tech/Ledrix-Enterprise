<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\Seller;
use App\Services\Tenant\TenantLimitService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProjectController extends Controller
{
    public function index()
    {
        $projects = $this->visibleQuery()
            ->with(['order', 'lead', 'projectManager', 'frontSeller', 'tasks'])
            ->latest()
            ->paginate(20);

        $orders = Order::query()->with(['lead.client'])->latest()->limit(100)->get();
        $sellers = Seller::query()->orderBy('name')->get();

        return view($this->viewPage('index'), [
            'projects' => $projects,
            'orders'   => $orders,
            'sellers'  => $sellers,
            'canCreate'=> $this->canCreate(),
        ]);
    }

    public function store(Request $request, TenantLimitService $limits)
    {
        abort_unless($this->canCreate(), 403);

        $validated = $request->validate([
            'title'            => ['required', 'string', 'max:255'],
            'order_id'         => ['required', 'exists:orders,id'],
            'owner_seller_id'  => ['required', 'exists:sellers,id'],
            'front_seller_id'  => ['nullable', 'exists:sellers,id'],
            'status'           => ['required', Rule::in(['pending', 'in_progress', 'completed', 'cancelled'])],
            'start_date'       => ['nullable', 'date'],
            'due_date'         => ['nullable', 'date', 'after_or_equal:start_date'],
            'description'      => ['nullable', 'string', 'max:5000'],
        ]);

        $order = Order::query()->findOrFail($validated['order_id']);
        $limits->assertCanCreateProject((int) $order->tenant_id);

        $project = Project::query()->create([
            'title'           => $validated['title'],
            'order_id'        => $order->id,
            'lead_id'         => $order->lead_id,
            'owner_seller_id' => $validated['owner_seller_id'],
            'front_seller_id' => $validated['front_seller_id'] ?: ($order->front_seller_id ?: $validated['owner_seller_id']),
            'status'          => $validated['status'],
            'start_date'      => $validated['start_date'] ?? now()->toDateString(),
            'due_date'        => $validated['due_date'] ?? null,
            'description'     => $validated['description'] ?? null,
            'pm_assigned_at'  => now(),
        ]);

        return redirect($this->routeTo('show', $project))
            ->with('success', 'Project created.');
    }

    public function show(int $id)
    {
        $project = $this->visibleQuery()
            ->with(['order.lead.client', 'lead', 'projectManager', 'frontSeller', 'tasks.assignedSeller'])
            ->findOrFail($id);

        $sellers = Seller::query()->orderBy('name')->get();

        return view($this->viewPage('show'), [
            'project'   => $project,
            'sellers'   => $sellers,
            'canMutate' => $this->canMutate($project),
        ]);
    }

    public function update(Request $request, int $id)
    {
        $project = $this->visibleQuery()->findOrFail($id);
        abort_unless($this->canMutate($project), 403);

        $validated = $request->validate([
            'title'            => ['required', 'string', 'max:255'],
            'owner_seller_id'  => ['required', 'exists:sellers,id'],
            'front_seller_id'  => ['nullable', 'exists:sellers,id'],
            'status'           => ['required', Rule::in(['pending', 'in_progress', 'completed', 'cancelled'])],
            'start_date'       => ['nullable', 'date'],
            'due_date'         => ['nullable', 'date'],
            'description'      => ['nullable', 'string', 'max:5000'],
        ]);

        $validated['front_seller_id'] = $validated['front_seller_id'] ?: $validated['owner_seller_id'];
        $project->update($validated);

        return back()->with('success', 'Project updated.');
    }

    public function destroy(int $id)
    {
        $project = $this->visibleQuery()->findOrFail($id);
        abort_unless($this->canMutate($project), 403);
        $project->delete();

        return redirect($this->routeTo('index'))
            ->with('success', 'Project deleted.');
    }

    public function storeTask(Request $request, int $id)
    {
        $project = $this->visibleQuery()->findOrFail($id);
        abort_unless($this->canMutate($project), 403);

        $validated = $request->validate([
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'assigned_to' => ['nullable', 'exists:sellers,id'],
            'status'      => ['required', Rule::in(['pending', 'in_progress', 'completed', 'blocked'])],
            'priority'    => ['required', Rule::in(['low', 'medium', 'high', 'urgent'])],
            'due_date'    => ['nullable', 'date'],
        ]);

        $project->tasks()->create($validated);

        return back()->with('success', 'Task added.');
    }

    public function updateTask(Request $request, int $id, int $task)
    {
        $project = $this->visibleQuery()->findOrFail($id);
        $row = ProjectTask::query()->where('project_id', $project->id)->findOrFail($task);

        $canStatusOnly = auth('seller')->check()
            && (int) $row->assigned_to === (int) auth('seller')->id();

        abort_unless($this->canMutate($project) || $canStatusOnly, 403);

        $rules = [
            'status' => ['required', Rule::in(['pending', 'in_progress', 'completed', 'blocked'])],
        ];

        if ($this->canMutate($project)) {
            $rules = array_merge($rules, [
                'title'       => ['required', 'string', 'max:255'],
                'description' => ['nullable', 'string', 'max:5000'],
                'assigned_to' => ['nullable', 'exists:sellers,id'],
                'priority'    => ['required', Rule::in(['low', 'medium', 'high', 'urgent'])],
                'due_date'    => ['nullable', 'date'],
            ]);
        }

        $row->update($request->validate($rules));

        return back()->with('success', 'Task updated.');
    }

    public function destroyTask(int $id, int $task)
    {
        $project = $this->visibleQuery()->findOrFail($id);
        abort_unless($this->canMutate($project), 403);

        ProjectTask::query()->where('project_id', $project->id)->findOrFail($task)->delete();

        return back()->with('success', 'Task removed.');
    }

    private function visibleQuery()
    {
        $query = Project::query();

        if (auth('seller')->check()) {
            $id = (int) auth('seller')->id();
            $query->where(function ($q) use ($id) {
                $q->where('owner_seller_id', $id)->orWhere('front_seller_id', $id);
            });
        }

        return $query;
    }

    private function canCreate(): bool
    {
        if (auth('admin')->check()) {
            return true;
        }

        $seller = auth('seller')->user();

        return $seller && $seller->is_seller === 'project_manager';
    }

    private function canMutate(Project $project): bool
    {
        if (auth('admin')->check()) {
            return true;
        }

        $seller = auth('seller')->user();

        return $seller && (int) $project->owner_seller_id === (int) $seller->id;
    }

    private function isSeller(): bool
    {
        return auth('seller')->check();
    }

    private function viewPage(string $page): string
    {
        return $this->isSeller()
            ? 'sellers.pages.projects.'.$page
            : 'admin.pages.projects.'.$page;
    }

    private function routeTo(string $name, mixed $params = []): string
    {
        $prefix = $this->isSeller() ? 'seller.projects.' : 'admin.projects.';

        return route($prefix.$name, $params);
    }
}
