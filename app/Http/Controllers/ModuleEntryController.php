<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ModuleEntryController extends Controller
{
    public function home(Request $request): RedirectResponse|Response
    {
        $role = $request->user()?->role();

        if (! $role) {
            return Inertia::render('Welcome');
        }

        return redirect()->route($role === UserRole::Employer ? 'hrflow' : 'jobflow');
    }

    public function jobflow(Request $request): RedirectResponse|Response
    {
        return $this->enter($request, 'jobflow', UserRole::Candidate);
    }

    public function hrflow(Request $request): RedirectResponse|Response
    {
        return $this->enter($request, 'hrflow', UserRole::Employer);
    }

    public function switch(Request $request, string $module, string $action): RedirectResponse
    {
        abort_unless(in_array($module, ['jobflow', 'hrflow'], true), 404);
        abort_unless(in_array($action, ['login', 'register'], true), 404);

        $role = $module === 'hrflow' ? UserRole::Employer : UserRole::Candidate;

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        $request->session()->put('module_entry', $module);

        return $action === 'register'
            ? redirect()->route('register', ['type' => $role->value])
            : redirect()->route('login', ['type' => $role->value]);
    }

    private function enter(Request $request, string $module, UserRole $role): RedirectResponse|Response
    {
        $userRole = $request->user()?->role();

        if (! $userRole) {
            return redirect()->guest(route('register', ['type' => $role->value]));
        }

        if ($userRole === $role) {
            return redirect()->to($role->home());
        }

        return Inertia::render('auth/ModuleEntry', [
            'moduleName' => $module === 'hrflow' ? 'HRFlow' : 'JobFlow',
            'targetRole' => $role->value,
            'loginUrl' => route('module-entry.switch', [$module, 'login']),
            'registerUrl' => route('module-entry.switch', [$module, 'register']),
            'currentRole' => $userRole->value,
            'returnUrl' => route($userRole === UserRole::Employer ? 'hrflow' : 'jobflow'),
            'showRegistration' => $request->query('intent') !== 'login',
            'status' => $request->session()->get('module_error'),
        ]);
    }
}
