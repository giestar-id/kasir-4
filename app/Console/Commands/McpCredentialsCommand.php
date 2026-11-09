<?php

namespace App\Console\Commands;

use App\Models\Setting;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class McpCredentialsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mcp:credentials {--generate : Generate a new secure MCP API Key and save to .env}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display or regenerate MCP Server Base URL and credentials for AI clients';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $envPath = base_path('.env');

        if ($this->option('generate')) {
            $newKey = 'pos_sec_'.Str::random(32);

            Setting::set('mcp_api_key', $newKey);

            if (file_exists($envPath)) {
                $envContent = file_get_contents($envPath);
                if (preg_match('/^MCP_API_KEY=.*/m', $envContent)) {
                    $envContent = preg_replace('/^MCP_API_KEY=.*/m', "MCP_API_KEY={$newKey}", $envContent);
                } else {
                    $envContent .= "\nMCP_API_KEY={$newKey}\n";
                }
                @file_put_contents($envPath, $envContent);
                $this->info("🔑 Kunci MCP baru berhasil dibuat dan disimpan ke database & .env: {$newKey}");
            } else {
                $this->info("🔑 Kunci MCP baru berhasil dibuat dan disimpan ke database: {$newKey}");
            }

            config(['mcp.api_key' => $newKey]);
        }

        $baseUrl = config('mcp.base_url', url('/mcp/pos'));
        $apiKey = Setting::get('mcp_api_key') ?: config('mcp.api_key');
        $apiKey = config('mcp.api_key');
        $status = config('mcp.enabled', true) ? 'AKTIF (ONLINE)' : 'NONAKTIF';

        $this->newLine();
        $this->line('<fg=cyan;options=bold>===============================================================</>');
        $this->line('<fg=white;options=bold>       POS KULINER - MCP SERVER CREDENTIALS & BASE URL         </>');
        $this->line('<fg=cyan;options=bold>===============================================================</>');
        $this->newLine();

        $this->line(" <fg=yellow;options=bold>Status Server</>    : <fg=green;options=bold>{$status}</>");
        $this->line(" <fg=yellow;options=bold>Base URL</>         : <fg=white;options=bold>{$baseUrl}</>");
        $this->line(" <fg=yellow;options=bold>MCP API Key</>      : <fg=green;options=bold>{$apiKey}</>");
        $this->line(' <fg=yellow;options=bold>Header Auth</>      : <fg=white>Authorization: Bearer '.$apiKey.'</>');
        $this->line(' <fg=yellow;options=bold>Alternative</>      : <fg=white>X-MCP-KEY: '.$apiKey.'</>');
        $this->line(" <fg=yellow;options=bold>Query Auth</>       : <fg=white>?api_key={$apiKey}</>");

        $this->newLine();
        $this->line('<fg=cyan>---------------------------------------------------------------</>');
        $this->line('<fg=white;options=bold>📋 CONTOH KONFIGURASI CLIENT AI</>');
        $this->line('<fg=cyan>---------------------------------------------------------------</>');
        $this->newLine();

        // 1. Cursor config
        $this->line('<fg=yellow;options=bold>1. Cursor (.cursor/mcp.json) [HTTP Remote]:</>');
        $cursorJson = json_encode([
            'mcpServers' => [
                'pos-laravel' => [
                    'url' => $baseUrl,
                    'headers' => [
                        'Authorization' => "Bearer {$apiKey}",
                        'X-MCP-KEY' => $apiKey,
                    ],
                ],
            ],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $this->line($cursorJson);
        $this->newLine();

        // 2. Claude Desktop config (stdio)
        $this->line('<fg=yellow;options=bold>2. Claude Desktop (claude_desktop_config.json) [Local CLI]:</>');
        $claudeJson = json_encode([
            'mcpServers' => [
                'pos-laravel' => [
                    'command' => 'php',
                    'args' => ['artisan', 'mcp:start', 'pos'],
                    'cwd' => base_path(),
                ],
            ],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $this->line($claudeJson);
        $this->newLine();

        // 3. cURL Test Command
        $this->line('<fg=yellow;options=bold>3. Uji Coba Cepat via cURL / PowerShell:</>');
        $this->line("<fg=gray>curl -X POST \"{$baseUrl}\" -H \"Authorization: Bearer {$apiKey}\" -H \"Content-Type: application/json\" -d '{\"jsonrpc\":\"2.0\",\"id\":1,\"method\":\"tools/list\",\"params\":{}}'</>");
        $this->newLine();

        return self::SUCCESS;
    }
}
