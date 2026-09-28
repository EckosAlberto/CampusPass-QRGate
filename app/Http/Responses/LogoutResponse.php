<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;


class LogoutResponse implements LogoutResponseContract
{
    public function toResponse($request)
    {
        Inertia::clearHistory();

        return $request->wantsJson()
            ? new JsonResponse('', 204)
            : redirect()->route('home');
    }
}
