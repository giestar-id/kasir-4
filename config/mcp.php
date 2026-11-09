<?php

return [
    /*
    |--------------------------------------------------------------------------
    | MCP Server Enablement
    |--------------------------------------------------------------------------
    */
    'enabled' => env('MCP_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | MCP Server API Key & Credentials
    |--------------------------------------------------------------------------
    |
    | When connecting external AI agents (Claude Desktop, Cursor, Antigravity,
    | custom LLM bots) over HTTP/SSE, this key acts as the authentication token.
    | Sent via:
    |   - Header: Authorization: Bearer <key>
    |   - Header: X-MCP-KEY: <key>
    |   - Query : ?api_key=<key>
    |
    */
    'api_key' => env('MCP_API_KEY', 'pos_sec_9f7a8b3c1d2e4f5a6b7c8d9e0f1a2b3c'),

    /*
    |--------------------------------------------------------------------------
    | MCP Server Base URL
    |--------------------------------------------------------------------------
    |
    | The default public or local URL where the MCP endpoint is hosted.
    | Example: http://localhost:8000/mcp/pos
    |
    */
    'base_url' => env('MCP_BASE_URL', rtrim(env('APP_URL', 'http://localhost:8000'), '/').'/mcp/pos'),

    /*
    |--------------------------------------------------------------------------
    | Server Metadata
    |--------------------------------------------------------------------------
    */
    'server_name' => 'POS Kuliner MCP Server',
    'version' => '1.1.0',
];
