<?php

namespace App\Http\Middleware;

use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Scopes the request to the signed-in user's client and blocks suspended clients. */
class ResolveTenant
{
    public function __construct(private TenantContext $context) {}

    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $request->user()?->tenant;
        abort_if($tenant === null, 403, 'This account is not linked to a class or institute.');
        abort_if($tenant->isSuspended(), 403, 'This account is suspended. Please contact NexusDigitalLabs.');

        $this->context->set($tenant->loadMissing('features'));

        return $next($request);
    }
}
