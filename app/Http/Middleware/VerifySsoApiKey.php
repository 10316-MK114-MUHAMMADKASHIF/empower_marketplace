<?php

namespace App\Http\Middleware;

use App\Models\SsoPartner;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifySsoApiKey
{
    /**
     * Resolves the calling partner from the X-Sso-Api-Key header and attaches it to the request —
     * the partner's identity always comes from which key was presented, never from anything the
     * caller states about itself, so one partner's key can never be used to claim to be another.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $key = $request->header('X-Sso-Api-Key', '');

        if (! $key) {
            abort(401, 'Missing API key.');
        }

        $partner = SsoPartner::where('api_key_hash', hash('sha256', $key))
            ->where('is_active', true)
            ->first();

        if (! $partner) {
            abort(401, 'Invalid API key.');
        }

        $request->attributes->set('sso_partner', $partner);

        return $next($request);
    }
}
