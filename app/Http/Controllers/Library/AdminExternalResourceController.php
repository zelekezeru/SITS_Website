<?php

namespace App\Http\Controllers\Library;

use App\Http\Controllers\Controller;

use App\Models\ExternalResource;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Permission;
use App\Enums\Role;

class AdminExternalResourceController extends Controller
{
    public function index()
    {
        return Inertia::render('Library/Admin/Resources/Index', [
            'resources' => ExternalResource::orderBy('sort_order')->get(),
        ]);
    }

    public function create()
    {
        return Inertia::render('Library/Admin/Resources/Form', [
            'resource' => new ExternalResource(),
            'permissions' => Permission::pluck('name'),
            'roles' => [Role::SUPER_ADMIN->value, Role::CAMPUS_ADMIN->value, Role::LIBRARIAN->value, Role::INSTRUCTOR->value, Role::STAFF_USER->value, Role::STUDENT->value],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'required|url',
            'category' => 'nullable|string|max:255',
            'provider' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'logo_path' => 'nullable|string',
            'access_tier' => 'required|in:free,premium,restricted',
            'required_permission' => 'nullable|string',
            'allowed_roles' => 'nullable|array',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ]);

        ExternalResource::create($data);

        return redirect()->route('admin.resources.index')->with('success', 'Resource created.');
    }

    public function edit(ExternalResource $resource)
    {
        return Inertia::render('Library/Admin/Resources/Form', [
            'resource' => $resource,
            'permissions' => Permission::pluck('name'),
            'roles' => [Role::SUPER_ADMIN->value, Role::CAMPUS_ADMIN->value, Role::LIBRARIAN->value, Role::INSTRUCTOR->value, Role::STAFF_USER->value, Role::STUDENT->value],
        ]);
    }

    public function update(Request $request, ExternalResource $resource)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'required|url',
            'category' => 'nullable|string|max:255',
            'provider' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'logo_path' => 'nullable|string',
            'access_tier' => 'required|in:free,premium,restricted',
            'required_permission' => 'nullable|string',
            'allowed_roles' => 'nullable|array',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ]);

        $resource->update($data);

        return redirect()->route('admin.resources.index')->with('success', 'Resource updated.');
    }

    public function destroy(ExternalResource $resource)
    {
        $resource->delete();
        return back()->with('success', 'Resource deleted.');
    }
}
