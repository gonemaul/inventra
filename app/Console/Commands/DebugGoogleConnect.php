<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class DebugGoogleConnect extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'debug:google-connect';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test DNS resolution and connectivity to Google APIs from within PHP/Laravel environment';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $targetHost = 'oauth2.googleapis.com';
        $targetUrl = 'https://oauth2.googleapis.com/token'; // Endpoint umum

        $this->info("========================================");
        $this->info(" Starting Connectivity Debugger");
        $this->info(" Target: {$targetHost}");
        $this->info("========================================");

        // 1. Test PHP Native DNS Resolution
        $this->info("\n[1] Testing PHP DNS Resolution (gethostbynamel)...");
        
        $startTime = microtime(true);
        $ips = gethostbynamel($targetHost);
        $duration = round((microtime(true) - $startTime) * 1000, 2);

        if ($ips && is_array($ips)) {
            $this->info("✅ DNS Resolved in {$duration}ms:");
            foreach ($ips as $ip) {
                $this->line("   - {$ip}");
            }
        } else {
            $this->error("❌ PHP DNS Resolution FAILED.");
            $this->warn("   Note: This means PHP cannot turn '{$targetHost}' into an IP address.");
            $this->warn("   Check your server's /etc/resolv.conf or PHP configuration.");
        }

        // 2. Test Guzzle Connection (Default)
        $this->info("\n[2] Testing Guzzle HTTP Connection (Default Settings)...");
        $this->testGuzzleConnection($targetUrl);

        // 3. Test Guzzle Connection (Forced IPv4)
        $this->info("\n[3] Testing Guzzle HTTP Connection (Forced IPv4)...");
        $this->testGuzzleConnection($targetUrl, ['force_ip_resolve' => 'v4']);

        $this->info("\n========================================");
        $this->info(" Debugging Complete");
        $this->info("========================================");
    }

    private function testGuzzleConnection($url, $options = [])
    {
        $client = new Client(array_merge([
            'timeout' => 10,
            'connect_timeout' => 5,
            'http_errors' => false, // Don't throw for 4xx/5xx, logic handled below
            'verify' => true,      // Verify SSL by default
        ], $options));

        $extraLabel = isset($options['force_ip_resolve']) ? "(Force {$options['force_ip_resolve']})" : "";

        try {
            $this->line("   Connecting to {$url} {$extraLabel}...");
            $startTime = microtime(true);
            
            $response = $client->get($url); // Using GET for simple connectivity check
            
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            $statusCode = $response->getStatusCode();

            if ($statusCode >= 200 && $statusCode < 500) {
                 // 400/404/405 is fine, it means we connected to the server successfully
                $this->info("✅ Connection SUCCEEDED in {$duration}ms (Status: {$statusCode})");
            } else {
                $this->warn("⚠️  Connected, but received Server Error (Status: {$statusCode})");
            }

        } catch (RequestException $e) {
            $this->error("❌ Connection FAILED");
            $this->line("   Error Message: " . $e->getMessage());
            
            if ($e->hasResponse()) {
                $this->line("   Response: " . $e->getResponse()->getStatusCode());
            }
        } catch (\Exception $e) {
            $this->error("❌ Critical Error: " . $e->getMessage());
        }
    }
}
