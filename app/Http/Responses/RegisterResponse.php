<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;
use Symfony\Component\HttpFoundation\Response;

/**
 * @see LoginResponse
 */
class RegisterResponse implements RegisterResponseContract
{
    /**
     * @param  Request  $request
     * @return Response
     */
    public function toResponse($request)
    {
        $request->session()->forget('module_entry');

        return $request->wantsJson()
            ? new JsonResponse('', 201)
            : redirect()->route($request->user()?->role()?->value === 'employer' ? 'hrflow' : 'jobflow');
    }
}
