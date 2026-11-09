<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateMcpKey
{
    /**
     * Handle an incoming request for MCP Server endpoints.
     *
     * Validates credentials provided via:
     * - Header: Authorization: Bearer <key>
     * - Header: X-MCP-KEY: <key>
     * - Header: x-api-key: <key>
     * - Query Param: ?api_key=<key> or ?key=<key>
     */
    public function handle(Request $request, Closure $next): Response
    {
        $expectedKey = Setting::get('mcp_api_key') ?: config('mcp.api_key');

        if (! config('mcp.enabled', true)) {
            return response()->json([
                'jsonrpc' => '2.0',
                'id' => null,
                'error' => [
                    'code' => -32000,
                    'message' => 'MCP Server is currently disabled in system configuration.',
                ],
            ], 503);
        }

        $bearerToken = $request->bearerToken();
        $headerKey = $request->header('X-MCP-KEY') ?? $request->header('x-api-key');
        $queryKey = $request->query('api_key') ?? $request->query('key');

        $providedKey = $bearerToken ?: ($headerKey ?: $queryKey);

        if (! $providedKey || ! is_string($providedKey) || ! hash_equals((string) $expectedKey, $providedKey)) {
            return response()->json([
                'jsonrpc' => '2.0',
                'id' => null,
                'error' => [
                    'code' => -32000,
                    'message' => 'Unauthorized: Invalid or missing MCP credentials. Please provide valid Bearer token, X-MCP-KEY header, or ?api_key parameter.',
                ],
            ], 401)->header('WWW-Authenticate', 'Bearer realm="mcp", error="invalid_token"');
        }

        // Authenticate as owner/system user if not already authenticated
        if (! Auth::check()) {
            $user = User::where('role', 'owner')->first() ?? User::first();
            if ($user) {
                Auth::setUser($user);
            }
        }

        return $next($request);
    }
}
