<?php

namespace App\Http\Responses;

use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Symfony\Component\HttpFoundation\Response;

/**
 * Fortify's redirect target is static config, so employers would land on the
 * candidate-only /resumes. Resolve the destination from the user's role instead.
 */
class LoginResponse implements LoginResponseContract
{
    /**
     * @param  Request  $request
     * @return Response
     */
    public function toResponse($request)
    {
        $module = $request->session()->pull('module_entry');
        $expectedRole = $module === 'hrflow' ? 'employer' : ($module === 'jobflow' ? 'candidate' : null);

        if ($expectedRole && $request->user()?->role()?->value !== $expectedRole) {
            return redirect()->route($module)->with(
                'module_error',
                "This account belongs to the {$request->user()?->role()?->value} workspace. Sign in with a {$expectedRole} account or create one.",
            );
        }

        return $request->wantsJson()
            ? response()->json(['two_factor' => false])
            : redirect()->route($request->user()?->role()?->value === 'employer' ? 'hrflow' : 'jobflow');
    }
}
