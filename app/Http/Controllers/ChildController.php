<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChildRequest;
use App\Models\Child;
use App\Policies\ChildPolicy;
use App\Support\DashboardNav;
use App\Support\Grades;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/** A parent adds, edits and removes their own children. */
class ChildController extends Controller
{
    public function create(Request $request)
    {
        if (Gate::denies('create', Child::class)) {
            return $this->toDashboard('You can add up to '.ChildPolicy::MAX_CHILDREN.' children.');
        }

        return $this->form($request, new Child);
    }

    public function store(ChildRequest $request)
    {
        Gate::authorize('create', Child::class);

        $child = $request->user()->children()->create($request->validated());

        return $this->toDashboard("{$child->name} has been added.");
    }

    public function edit(Request $request, Child $child)
    {
        Gate::authorize('update', $child);

        return $this->form($request, $child);
    }

    public function update(ChildRequest $request, Child $child)
    {
        Gate::authorize('update', $child);

        $child->update($request->validated());

        return $this->toDashboard("{$child->name}'s details are saved.");
    }

    public function destroy(Child $child)
    {
        Gate::authorize('delete', $child);

        $child->delete();

        return $this->toDashboard("{$child->name} has been removed.");
    }

    private function form(Request $request, Child $child)
    {
        return view('dashboard.child-form', [
            'child' => $child,
            'grades' => Grades::ALL,
            'nav' => DashboardNav::for('parent'),
            'user' => ['name' => $request->user()->name, 'role' => $request->user()->role->label()],
        ]);
    }

    private function toDashboard(string $message)
    {
        return redirect()->route('dashboard.parent')->with('notice', $message);
    }
}
