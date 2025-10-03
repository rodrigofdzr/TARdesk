<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ZohoOauthController extends Controller
{
    /**
     * Redirect to Zoho OAuth authorization page
     */
    public function authorize()
    {
        $clientId = config('services.zoho_mail.client_id');

        if (!$clientId) {
            return response()->json([
                'error' => 'ZOHO_MAIL_CLIENT_ID not configured in .env file'
            ], 500);
        }

        $redirectUri = url('/oauth/zoho/callback');
        $scope = 'ZohoMail.messages.ALL,ZohoMail.accounts.READ';
        $accessType = 'offline'; // To get refresh token

        $authUrl = 'https://accounts.zoho.com/oauth/v2/auth?' . http_build_query([
            'scope' => $scope,
            'client_id' => $clientId,
            'response_type' => 'code',
            'access_type' => $accessType,
            'redirect_uri' => $redirectUri,
            'prompt' => 'consent', // Force to show consent screen
        ]);

        Log::info('Redirecting to Zoho OAuth authorization', [
            'auth_url' => $authUrl,
            'redirect_uri' => $redirectUri,
            'scope' => $scope
        ]);

        return redirect($authUrl);
    }

    /**
     * Handle Zoho OAuth2 callback and exchange code for tokens
     */
    public function callback(Request $request)
    {
        $code = $request->input('code');
        $error = $request->input('error');

        if ($error) {
            Log::error('Zoho OAuth authorization error', ['error' => $error, 'error_description' => $request->input('error_description')]);
            return response()->json(['error' => 'Authorization failed: ' . $error], 400);
        }

        if (!$code) {
            return response()->json(['error' => 'No authorization code received'], 400);
        }

        $clientId = config('services.zoho_mail.client_id');
        $clientSecret = config('services.zoho_mail.client_secret');
        $redirectUri = url('/oauth/zoho/callback');
        $tokenUrl = 'https://accounts.zoho.com/oauth/v2/token';

        $params = [
            'grant_type' => 'authorization_code',
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri' => $redirectUri,
            'code' => $code,
        ];

        $ch = curl_init($tokenUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            Log::error('Zoho OAuth cURL error', ['error' => $curlError]);
            return response()->json(['error' => 'Connection failed: ' . $curlError], 500);
        }

        if ($httpCode !== 200) {
            Log::error('Zoho OAuth HTTP error', ['http_code' => $httpCode, 'response' => $result]);
            return response()->json(['error' => 'HTTP error: ' . $httpCode], $httpCode);
        }

        $response = json_decode($result, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('Zoho OAuth invalid JSON response', ['response' => $result]);
            return response()->json(['error' => 'Invalid response format'], 500);
        }

        if (isset($response['error'])) {
            Log::error('Zoho OAuth API error', $response);
            return response()->json(['error' => $response['error']], 400);
        }

        // Log successful token exchange
        Log::info('Zoho OAuth tokens obtained successfully', [
            'scope' => $response['scope'] ?? null,
            'expires_in' => $response['expires_in'] ?? null,
            'has_refresh_token' => isset($response['refresh_token']),
            'api_domain' => $response['api_domain'] ?? null
        ]);

        // Show tokens for manual copy or save
        return response()->json([
            'success' => true,
            'message' => 'Tokens obtained successfully. Copy these values to your .env file.',
            'access_token' => $response['access_token'] ?? null,
            'refresh_token' => $response['refresh_token'] ?? null,
            'expires_in' => $response['expires_in'] ?? null,
            'scope' => $response['scope'] ?? null,
            'api_domain' => $response['api_domain'] ?? null,
            'token_type' => $response['token_type'] ?? null,
        ]);
    }
}
