<?php

namespace App\Http\Middleware;

use Closure;

class CrossOriginResourceSharing
{
    /**
     * Handle an cross-site http request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        // CORS reference to https://developer.mozilla.org/en-US/docs/Web/HTTP/Access_control_CORS
        $headers = [
            'Access-Control-Allow-Headers' => 'Accept, Authorization, Content-Type',
            'Access-Control-Allow-Methods' => 'POST, GET, OPTIONS, DELETE, PUT',
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Max-Age' => '86400',
        ];

        if ($request->method() === 'OPTIONS') {
            return \Response::make('', 200, $headers);
        }

        foreach ($headers as $k => $v) {
            $response->headers->set($k, $v);
        }

        return $response;
    }
}
