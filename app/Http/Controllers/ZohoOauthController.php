<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ZohoOauthController extends Controller
{
    /**
     * Handle Zoho OAuth2 callback and exchange code for tokens
     */
    public function callback(Request $request)
    {
        $code = $request->input('code');
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
        $result = curl_exec($ch);
        curl_close($ch);
        $response = json_decode($result, true);
        if (isset($response['error'])) {
            Log::error('Zoho OAuth error', $response);
            return response()->json(['error' => $response['error']], 400);
        }
        // Show tokens for manual copy or save
        return response()->json([
            'access_token' => $response['access_token'] ?? null,
            'refresh_token' => $response['refresh_token'] ?? null,
            'expires_in' => $response['expires_in'] ?? null,
            'scope' => $response['scope'] ?? null,
            'api_domain' => $response['api_domain'] ?? null,
            'token_type' => $response['token_type'] ?? null,
        ]);
    }
}

