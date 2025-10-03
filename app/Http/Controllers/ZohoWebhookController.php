<?php

namespace App\Http\Controllers;

use App\Services\EmailToTicketService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Routing\Controller as BaseController;

class ZohoWebhookController extends BaseController
{
    private EmailToTicketService $emailService;

    public function __construct(EmailToTicketService $emailService)
    {
        $this->emailService = $emailService;
    }

    /**
     * Handle incoming Zoho Mail webhook requests.
     * Verifies signature (if configured) and forwards payload to EmailToTicketService.
     */
    public function handle(Request $request): JsonResponse
    {
        $raw = $request->getContent();

        // If Zoho calls the webhook endpoint to validate configuration it may send an empty POST.
        // Zoho considers the webhook configured only if the endpoint returns HTTP 200 for that initial POST.
        // Return 200 for empty bodies (initial verification) and log it. This is safe because it's only used
        // during webhook setup; normal payloads will be processed below and still require signature verification
        // if a secret is configured.
        if (trim($raw) === '') {
            Log::info('Zoho webhook initial verification request received (empty body). Responding 200 to confirm configuration.');
            return response()->json(['ok' => true, 'message' => 'Webhook endpoint reachable (empty payload)'], 200);
        }

        $secret = config('services.zoho_mail.webhook_secret');

        // If a secret is configured, require verification
        if ($secret) {
            $signatureHeader = $this->getSignatureHeader($request);

            if (!$signatureHeader) {
                Log::warning('Zoho webhook signature header missing');
                return response()->json(['error' => 'Unauthorized - signature missing'], 401);
            }

            if (!$this->verifySignature($raw, $secret, $signatureHeader)) {
                Log::warning('Zoho webhook signature verification failed', ['headers' => $request->headers->all()]);
                return response()->json(['error' => 'Unauthorized - invalid signature'], 401);
            }
        } else {
            Log::warning('Zoho webhook secret not configured (ZOHO_MAIL_WEBHOOK_SECRET). Accepting request without verification.');
        }

        // Parse payload (Zoho sends JSON). Fall back to request->all() if not JSON.
        $payload = json_decode($raw, true);
        if (!is_array($payload)) {
            $payload = $request->all();
        }

        if (empty($payload)) {
            Log::warning('Zoho webhook received with empty payload');
            return response()->json(['error' => 'Bad Request - empty payload'], 400);
        }

        // Map Zoho payload to our internal emailData shape
        $emailData = $this->mapZohoPayloadToEmailData($payload);

        if (!$emailData || empty($emailData['from_email'])) {
            Log::warning('Zoho webhook payload could not be mapped to email data', ['payload' => $payload]);
            return response()->json(['error' => 'Bad Request - cannot map payload'], 400);
        }

        try {
            $ticket = $this->emailService->processIncomingEmail($emailData);

            if ($ticket) {
                return response()->json(['ok' => true, 'ticket_id' => $ticket->id ?? null], 200);
            }

            return response()->json(['ok' => false], 200);
        } catch (\Exception $e) {
            Log::error('Error processing Zoho webhook', ['error' => $e->getMessage(), 'payload' => $payload]);
            return response()->json(['error' => 'Internal Server Error'], 500);
        }
    }

    private function getSignatureHeader(Request $request): ?string
    {
        // Try several header names Zoho might use
        $candidates = [
            'x-zoho-signature',
            'x-zoho-mail-signature',
            'x-zoho-mail-webhook-signature',
            'x-zoho-signature-sha256',
            'zoho-signature',
            'x-signature',
        ];

        foreach ($candidates as $name) {
            if ($request->headers->has($name)) {
                return $request->headers->get($name);
            }
        }

        return null;
    }

    /**
     * Verify signature using HMAC-SHA256. Try base64 and hex encodings for compatibility.
     */
    private function verifySignature(string $raw, string $secret, string $signatureHeader): bool
    {
        // Compute HMAC-SHA256 raw binary and base64/hex representations
        $hmacBinary = hash_hmac('sha256', $raw, $secret, true);
        $expectedBase64 = base64_encode($hmacBinary);
        $expectedHex = hash_hmac('sha256', $raw, $secret, false);

        // Also try URL-safe base64 (replace +/ with -_)
        $expectedBase64Url = strtr($expectedBase64, '+/', '-_');

        // Normalize header value
        $sig = trim($signatureHeader);

        if (hash_equals($expectedBase64, $sig) || hash_equals($expectedHex, $sig) || hash_equals($expectedBase64Url, $sig)) {
            return true;
        }

        // Some providers sign specific parts, try signing the parsed body if JSON with 'data' key
        $decoded = json_decode($raw, true);
        if (is_array($decoded) && isset($decoded['data'])) {
            $dataRaw = json_encode($decoded['data'], JSON_UNESCAPED_SLASHES);
            $hmacBinary2 = hash_hmac('sha256', $dataRaw, $secret, true);
            if (hash_equals(base64_encode($hmacBinary2), $sig) || hash_equals(hash_hmac('sha256', $dataRaw, $secret, false), $sig)) {
                return true;
            }
        }

        return false;
    }

    private function mapZohoPayloadToEmailData(array $payload): array
    {
        // Zoho webhook sends data directly in the payload root or inside 'payload' key
        // Based on actual Zoho webhook format observed
        $container = $payload['payload'] ?? $payload;

        // Map Zoho fields to internal format
        // Try both the direct field names and nested variants
        $emailData = [
            'from_email'    => $container['from_email'] ?? $container['fromAddress'] ?? null,
            'to_email'      => $container['to_email'] ?? $container['toAddress'] ?? null,
            'subject'       => $container['subject'] ?? null,
            'body_html'     => $container['body_html'] ?? $container['html'] ?? $container['content'] ?? null,
            'body'          => $container['body'] ?? $container['text'] ?? null,
            'message_id'    => $container['message_id'] ?? $container['messageIdString'] ?? $container['messageId'] ?? null,
            'from_name'     => $container['from_name'] ?? $container['sender_name'] ?? $container['sender'] ?? null,
            'sender_name'   => $container['sender_name'] ?? $container['sender'] ?? null,
            'received_time' => $container['received_time'] ?? $container['receivedTime'] ?? null,
            'in_reply_to'   => $container['in_reply_to'] ?? $container['inReplyTo'] ?? null,
            'references'    => $container['references'] ?? [],
            'attachments'   => $container['attachments'] ?? [],
        ];

        // Ensure references is an array
        if (!is_array($emailData['references'])) {
            $emailData['references'] = $emailData['references'] ? [$emailData['references']] : [];
        }

        // Ensure attachments is an array
        if (!is_array($emailData['attachments'])) {
            $emailData['attachments'] = [];
        }

        // Log the mapped data for debugging
        Log::info('Zoho webhook payload mapped', [
            'has_from_email' => !empty($emailData['from_email']),
            'has_subject' => !empty($emailData['subject']),
            'has_message_id' => !empty($emailData['message_id']),
            'has_body_html' => !empty($emailData['body_html']),
            'attachments_count' => count($emailData['attachments']),
            'from_email' => $emailData['from_email'] ?? 'missing'
        ]);

        // Log missing critical fields for debugging
        if (empty($emailData['from_email']) || empty($emailData['subject'])) {
            Log::warning('Zoho webhook mapping: missing critical fields', [
                'container_keys' => array_keys($container),
                'mapped_from_email' => $emailData['from_email'] ?? 'null',
                'mapped_subject' => $emailData['subject'] ?? 'null'
            ]);
        }

        return $emailData;
    }
}
