<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\Document;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminCrudController extends Controller
{
    // ==========================================
    // SYSTEM SETTINGS CRUD
    // ==========================================

    public function updateSetting(Request $request)
    {
        $data = $request->validate([
            'key' => ['required', 'string', 'exists:settings,key'],
            'value' => ['required', 'string'],
        ]);

        $setting = Setting::where('key', $data['key'])->firstOrFail();
        $setting->update(['value' => $data['value']]);

        return redirect()->back()->with('success', "Setting updated successfully.");
    }

    public function updateSettingsBatch(Request $request)
    {
        $data = $request->validate([
            'settings' => ['required', 'array'],
            'settings.*.key' => ['required', 'string', 'exists:settings,key'],
            'settings.*.value' => ['required', 'string'],
        ]);

        $updates = [];
        foreach ($data['settings'] as $item) {
            $updates[$item['key']] = $item['value'];
        }

        // Validate blend weights total does not exceed 100%
        $blendKeys = ['weight_auto_score', 'weight_manager_score', 'weight_executive_score'];
        $blendTotal = 0;
        $hasBlendUpdate = false;
        foreach ($blendKeys as $key) {
            if (array_key_exists($key, $updates)) {
                $blendTotal += (float)$updates[$key];
                $hasBlendUpdate = true;
            } else {
                $setting = Setting::where('key', $key)->first();
                if ($setting) {
                    $blendTotal += (float)$setting->value;
                }
            }
        }
        if ($hasBlendUpdate && $blendTotal > 1.0001) {
            return redirect()->back()->with('error', "The total of evaluation blend weights cannot exceed 100%. Current total: " . round($blendTotal * 100, 2) . "%");
        }

        // Validate auto score sub-weights total does not exceed 100%
        $autoKeys = [
            'auto_score_weight_tasks',
            'auto_score_weight_deliverables',
            'auto_score_weight_kpis',
            'auto_score_weight_attendance'
        ];
        $autoTotal = 0;
        $hasAutoUpdate = false;
        foreach ($autoKeys as $key) {
            if (array_key_exists($key, $updates)) {
                $autoTotal += (float)$updates[$key];
                $hasAutoUpdate = true;
            } else {
                $setting = Setting::where('key', $key)->first();
                if ($setting) {
                    $autoTotal += (float)$setting->value;
                }
            }
        }
        if ($hasAutoUpdate && $autoTotal > 1.0001) {
            return redirect()->back()->with('error', "The total of auto-score sub-weights cannot exceed 100%. Current total: " . round($autoTotal * 100, 2) . "%");
        }

        DB::transaction(function () use ($updates) {
            foreach ($updates as $key => $value) {
                Setting::where('key', $key)->update(['value' => $value]);
            }
        });

        return redirect()->back()->with('success', "Settings updated successfully.");
    }

    // ==========================================
    // POLYMORPHIC SECURE DOCUMENTS CRUD
    // ==========================================

    public function storeDocument(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'documentable_type' => ['required', 'string'],
            'documentable_id' => ['required', 'integer'],
            'file_path' => ['nullable', 'string'],
            'file' => ['nullable', 'file', 'max:51200'], // max 50MB
        ]);

        if (!$request->hasFile('file') && !$request->filled('file_path')) {
            return redirect()->back()->withErrors(['file' => 'A file upload or web link is required.']);
        }

        $filePath = $data['file_path'];

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $filename = time() . '_' . str_replace(' ', '_', $file->getClientOriginalName());
            $file->move(public_path('uploads/docs'), $filename);
            $filePath = 'uploads/docs/' . $filename;
        }

        Document::create([
            'title' => $data['name'],
            'path' => $filePath,
            'documentable_type' => $data['documentable_type'],
            'documentable_id' => $data['documentable_id'],
            'uploaded_by' => auth()->id(),
        ]);

        return redirect()->back()->with('success', "Document uploaded and registered securely.");
    }

    public function destroyDocument(Document $document)
    {
        $document->delete();
        return redirect()->back()->with('success', "Document removed successfully.");
    }

    // ==========================================
    // USER ROLES & ACCESS MANAGEMENT
    // ==========================================

    public function updateUserRole(Request $request, User $user)
    {
        $request->validate([
            'role' => ['required', 'string', 'exists:roles,name'],
        ]);

        $user->syncRoles([$request->role]);

        return redirect()->back()->with('success', "User role updated successfully.");
    }

    public function toggleUserApproval(User $user)
    {
        if ($user->hasRole('President / Super Admin')) {
            return redirect()->back()->with('error', "The superadmin account cannot be deactivated.");
        }

        $approved = !$user->is_approved;

        if (!$approved) {
            $exists = \App\Models\DeactivationRequest::where('user_id', $user->id)
                ->where('status', 'pending')
                ->exists();

            if (!$exists) {
                \App\Models\DeactivationRequest::create([
                    'user_id' => $user->id,
                    'type' => 'deactivate',
                    'status' => 'pending',
                    'reason' => 'Requested by Administrator',
                ]);
            }

            return redirect()->back()->with('success', "Deactivation request has been queued for approval.");
        }

        $user->update([
            'is_approved' => true,
            'is_active' => true,
        ]);

        return redirect()->back()->with('success', "User account has been approved and activated.");
    }

    public function approveDeactivation(\App\Models\DeactivationRequest $request)
    {
        $user = $request->user;

        if ($user->hasRole('President / Super Admin')) {
            return redirect()->back()->with('error', "The superadmin account cannot be deactivated.");
        }

        $request->update(['status' => 'approved']);

        $user->update([
            'is_approved' => false,
            'is_active' => false,
        ]);

        return redirect()->back()->with('success', "Deactivation request has been approved. The account is now inactive.");
    }

    public function rejectDeactivation(\App\Models\DeactivationRequest $request)
    {
        $request->update(['status' => 'rejected']);

        return redirect()->back()->with('success', "Deactivation request has been rejected.");
    }
}
