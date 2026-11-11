<?php

namespace App\Http\Middleware;

use App\Support\Features;
use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Usage: ->middleware('feature:exams'). Runs after the tenant middleware. */
class RequireFeature
{
    public function __construct(private TenantContext $context) {}

    public function handle(Request $request, Closure $next, string $feature): Response
    {
        if (! $this->context->get()?->hasFeature($feature)) {
            $name = Features::all()[$feature]['name'] ?? $feature;
            abort(403, "$name is not switched on for this account.");
        }

        return $next($request);
    }
}
