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
        // Zoho may send different shapes; attempt to find the message container
        $container = $payload['message'] ?? $payload['mail'] ?? $payload['data'] ?? $payload;

        // If data is an array with a single element 'message' inside, unwrap
        if (isset($container['message']) && is_array($container['message'])) {
            $container = $container['message'];
        }

        // Helper to read keys case-insensitively
        $get = function ($keys) use ($container) {
            foreach ((array) $keys as $k) {
                if (is_array($container) && array_key_exists($k, $container) && $container[$k] !== null) {
                    return $container[$k];
                }

                // try lowercase key
                $lk = strtolower($k);
                foreach ($container as $ck => $cv) {
                    if (strtolower($ck) === $lk) {
                        return $cv;
                    }
                }
            }

            return null;
        };

        $subject = $get(['subject', 'Subject']);
        $messageId = $get(['message_id', 'Message-ID', 'messageId', 'msg_id']);
        $inReplyTo = $get(['in_reply_to', 'In-Reply-To', 'inReplyTo']);
        $references = $get(['references', 'References']);
        if (is_string($references)) {
            $references = preg_split('/\s+/', trim($references));
        }

        $from = $get(['from', 'From', 'sender']);
        $fromEmail = null;
        $fromName = null;

        if (is_array($from)) {
            // Zoho may provide {"email":"","name":""}
            $fromEmail = $from['email'] ?? $from['mail'] ?? $from['address'] ?? null;
            $fromName = $from['name'] ?? $from['displayName'] ?? null;
        } elseif (is_string($from)) {
            // Try parse "Name <email@host>"
            if (preg_match('/<([^>]+)>/', $from, $m)) {
                $fromEmail = $m[1];
                $fromName = trim(str_replace("<{$fromEmail}>", '', $from));
            } elseif (filter_var($from, FILTER_VALIDATE_EMAIL)) {
                $fromEmail = $from;
            }
        }

        // Fallbacks
        $body = $get(['body', 'content', 'plainText', 'text_body', 'message_body']) ?? '';
        $htmlBody = $get(['html', 'htmlBody', 'html_content']) ?? '';
        $date = $get(['date', 'received_at', 'timestamp']) ?? null;

        return [
            'message_id' => $messageId,
            'in_reply_to' => $inReplyTo,
            'references' => (array) ($references ?? []),
            'subject' => $subject ?? 'Sin asunto',
            'from_email' => $fromEmail,
            'from_name' => $fromName,
            'body' => is_string($body) ? $body : (string) json_encode($body),
            'html_body' => is_string($htmlBody) ? $htmlBody : null,
            'attachments' => [],
            'date' => $date,
            // Raw payload for debugging/metadata
            'raw_payload' => $payload,
        ];
    }
}

