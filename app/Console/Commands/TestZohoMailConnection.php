<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TestZohoMailConnection extends Command
{
    protected $signature = 'zoho:test-connection';
    protected $description = 'Test Zoho Mail API connection and get account ID';

    public function handle()
    {
        $this->info('Testing Zoho Mail API connection...');
        $this->newLine();

        // Check credentials
        $clientId = config('services.zoho_mail.client_id');
        $clientSecret = config('services.zoho_mail.client_secret');
        $refreshToken = config('services.zoho_mail.refresh_token');

        if (!$clientId || !$clientSecret || !$refreshToken) {
            $this->error('Missing Zoho Mail credentials in .env file');
            $this->newLine();
            $this->info('Required variables:');
            $this->line('- ZOHO_MAIL_CLIENT_ID');
            $this->line('- ZOHO_MAIL_CLIENT_SECRET');
            $this->line('- ZOHO_MAIL_REFRESH_TOKEN');
            $this->newLine();
            $this->info('Visit /oauth/zoho/authorize to obtain tokens');
            return 1;
        }

        $this->info('✓ Credentials found');

        // Get access token
        $this->info('Getting access token from refresh token...');
        $accessToken = $this->getAccessToken($clientId, $clientSecret, $refreshToken);

        if (!$accessToken) {
            $this->error('Failed to obtain access token');
            return 1;
        }

        $this->info('✓ Access token obtained');

        // Get accounts
        $this->info('Fetching Zoho Mail accounts...');
        $accounts = $this->getAccounts($accessToken);

        if (empty($accounts)) {
            $this->error('Failed to fetch accounts');
            return 1;
        }

        $this->info('✓ Accounts retrieved');
        $this->newLine();

        // Display accounts
        foreach ($accounts as $index => $account) {
            $this->line("Account #" . ($index + 1));
            $this->line("  Account ID: {$account['accountId']}");
            $this->line("  Email: {$account['primaryEmailAddress']}");
            $this->line("  Name: {$account['accountName']}");
            $this->newLine();
        }

        if (count($accounts) > 0) {
            $accountId = $accounts[0]['accountId'];
            $this->info('Add this to your .env file:');
            $this->line("ZOHO_MAIL_ACCOUNT_ID={$accountId}");
            $this->newLine();
        }

        $this->info('✓ Connection test successful!');
        return 0;
    }

    private function getAccessToken($clientId, $clientSecret, $refreshToken)
    {
        $url = "https://accounts.zoho.com/oauth/v2/token";
        $params = [
            'refresh_token' => $refreshToken,
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'grant_type' => 'refresh_token',
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            $this->error("HTTP Error: $httpCode");
            $this->line($result);
            return null;
        }

        $response = json_decode($result, true);
        return $response['access_token'] ?? null;
    }

    private function getAccounts($accessToken)
    {
        $url = "https://mail.zoho.com/api/accounts";

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Zoho-oauthtoken ' . $accessToken,
            'Accept: application/json',
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            $this->error("HTTP Error: $httpCode");
            $this->line($result);
            return [];
        }

        $response = json_decode($result, true);
        return $response['data'] ?? [];
    }
}

