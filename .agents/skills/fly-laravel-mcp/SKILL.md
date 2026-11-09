---
name: fly-laravel-mcp
description: >-
  Deploys, configures, and maintains Laravel applications running Model Context Protocol (MCP) on Fly.io with persistent SQLite or PostgreSQL. Use when the user asks to deploy to Fly.io, configure Fly.io persistent volumes, debug 405 Method Not Allowed errors on MCP routes, troubleshoot container startup crashes, audit Fly.io secrets, or test data persistence across restarts.
license: MIT
metadata:
  author: antigravity
---

# Fly.io Laravel & MCP Operations Runbook

This skill provides step-by-step runbooks for developing, deploying, and maintaining production Laravel applications with Model Context Protocol (MCP) servers on Fly.io.

---

## 1. Pre-Deployment Invariants Checklist

Before running `fly deploy`, verify these critical invariants:

1. **Production Dependencies**:
   - `laravel/mcp` MUST be in `"require"` in `composer.json` (NOT `"require-dev"`).
   - Verify with: `grep -A 20 '"require":' composer.json | grep laravel/mcp`.
   - Reason: Docker builds using `composer install --no-dev`. If `laravel/mcp` is in dev, POST routes will return **405 Method Not Allowed**.

2. **Volume Mount Safety**:
   - In `fly.toml`, ensure `destination` does NOT mask source code:
     ```toml
     [mounts]
       source = 'pos_data'
       destination = '/data'
     ```
   - Never mount over `/var/www/html/database` because that conceals `database/seeders/` and `database/migrations/`, causing startup crash.

3. **Database Path & Secrets Synchronization**:
   - `DB_DATABASE` must point to `/data/database.sqlite`.
   - Fly Secrets override `fly.toml [env]`. Always check:
     `fly secrets list -a <app-name>`
   - Ensure the secret is synchronized:
     `fly secrets set DB_DATABASE=/data/database.sqlite -a <app-name>`

4. **Single-Machine Mode for SQLite**:
   - SQLite files cannot be shared across multiple machines.
   - Destroy redundant stopped machines:
     `fly machines list -a <app-name>`
     `fly machines destroy <redundant-id> -a <app-name> --force`
   - In `fly.toml`:
     ```toml
     [http_service]
       min_machines_running = 1
       auto_stop_machines = 'off'
     ```

---

## 2. Standard Deployment Workflow

```powershell
# 1. Commit and push source changes
git add .
git commit -m "feat/fix: <description>"
git push origin main

# 2. Deploy to Fly.io
fly deploy

# 3. Check machine status
fly status -a <app-name>

# 4. If machine is stopped after deploy, start it:
fly machine start <machine-id> -a <app-name>
```

---

## 3. Remote Operations & Debugging

### Running Artisan Commands
Execute commands inside the running container without rebuilding images:

```powershell
# Run database migrations with seeder
fly ssh console -C "php artisan migrate --force --seed" -a <app-name>

# Run Artisan Tinker code directly
fly ssh console -C "php artisan tinker --execute='echo config(\"database.connections.sqlite.database\");'" -a <app-name>

# Interactive SSH terminal
fly ssh console -a <app-name>
```

### Checking Real-time Logs
```powershell
# Tail live container logs
fly logs -a <app-name>

# Check startup crash logs without tailing
fly logs -a <app-name> --no-tail
```

---

## 4. MCP Server Verification & Testing

### 1. Test Info Endpoint
```powershell
Invoke-RestMethod -Uri "https://<app-name>.fly.dev/mcp/info" -Method GET
```
Expected: HTTP 200 with JSON listing all capabilities, tools, resources, and prompts.

### 2. Test Initialize Handshake (JSON-RPC 2.0)
```powershell
$headers = @{
    "Content-Type" = "application/json"
    "Authorization" = "Bearer <MCP_API_KEY>"
}
$body = @{
    jsonrpc = "2.0"; id = 1; method = "initialize"
    params = @{ protocolVersion = "2024-11-05"; capabilities = @{}; clientInfo = @{ name = "TestClient"; version = "1.0" } }
} | ConvertTo-Json -Depth 5

Invoke-RestMethod -Uri "https://<app-name>.fly.dev/mcp/pos" -Method POST -Headers $headers -Body $body
```

### 3. Verify Data Persistence Across Machine Restarts
Always run this persistence test after any storage configuration change:
1. Call `tools/call` on `manage-menu-tool` to create a record.
2. Restart the machine:
   `fly machine restart <machine-id> -a <app-name>`
3. Wait 3 seconds, then call `action: 'list'` on `manage-menu-tool`.
4. Ensure the created record is still present. If count is 0, verify `PRAGMA database_list` to see if SQLite was written to ephemeral storage.

---

## 5. Bulk/Batch Operations Best Practices

When handling 10+ records, avoid sequential single-record API calls. Use batch endpoints:

* **Catalog Import**: Call `bulk-import-catalog-tool` with `categories`, `ingredients`, and `menus` arrays in a single request.
* **Batch Menus**: Call `manage-menu-tool` with `action: 'bulk_create'` and `menus: [...]`.
* **Batch Shopping**: Call `manage-stock-tool` with `action: 'bulk_purchase'` and `purchases: [...]`.
* **Batch Opname**: Call `manage-stock-tool` with `action: 'bulk_opname'` and `adjustments: [...]`.
* **Universal Bulk Insert**: Call `universal-database-tool` with `action: 'bulk_insert'` and `table: '<table>'`, `data: [[...], [...]]`.
