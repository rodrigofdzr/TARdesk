<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Ticket;
use App\Models\TicketReply;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class EmailToTicketService
{
    /**
     * Procesa un email entrante y lo convierte en ticket o respuesta
     */
    public function processIncomingEmail(array $emailData): ?Ticket
    {
        // Filtro de emails automáticos/no deseados
        $ignoredEmails = ['noreply@zoho.com'];
        $ignoredSubjects = ['ZohoMail - New login activity'];
        if (in_array($emailData['from_email'], $ignoredEmails) || in_array($emailData['subject'], $ignoredSubjects)) {
            Log::info('Email ignorado por filtro automático', $emailData);
            return null;
        }

        try {
            Log::info('Procesando email entrante', [
                'from_email' => $emailData['from_email'] ?? null,
                'subject' => $emailData['subject'] ?? null,
                'message_id' => $emailData['message_id'] ?? null,
                'attachments_count' => isset($emailData['attachments']) ? count($emailData['attachments']) : 0
            ]);
            // Guardar el payload del email recibido para depuración
            $payloadPath = storage_path('logs/email_payload_' . date('Ymd_His') . '.json');
            file_put_contents($payloadPath, json_encode($emailData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $messageId = $emailData['message_id'] ?? null;
            $inReplyTo = $emailData['in_reply_to'] ?? null;
            $references = $emailData['references'] ?? [];
            $subject = $emailData['subject'] ?? 'Sin asunto';
            $fromEmail = $emailData['from_email'] ?? null;
            $fromName = $emailData['from_name'] ?? null;
            $body = $emailData['body'] ?? '';
            $htmlBody = $emailData['html_body'] ?? '';
            $attachments = $emailData['attachments'] ?? [];

            if (!$fromEmail) {
                Log::warning('Email sin remitente válido', $emailData);
                return null;
            }

            // Buscar thread existente usando threading headers
            $existingTicket = $this->findExistingThread($inReplyTo, $references, $subject);

            if ($existingTicket) {
                // Es una respuesta a un ticket existente
                return $this->createTicketReply($existingTicket, $emailData);
            } else {
                // Es un nuevo ticket
                return $this->createNewTicketFromEmail($emailData);
            }

        } catch (\Exception $e) {
            Log::error('Error procesando email a ticket', [
                'error' => $e->getMessage(),
                'email_data' => $emailData
            ]);
            return null;
        }
    }

    /**
     * Busca un ticket existente basado en threading headers
     */
    private function findExistingThread(?string $inReplyTo, array $references, string $subject): ?Ticket
    {
        // Buscar por ticket_number en el subject
        if (preg_match('/\[TK-(\d{4}-\d{6})\]/', $subject, $matches)) {
            $ticket = Ticket::where('ticket_number', 'TK-' . $matches[1])->first();
            if ($ticket) {
                return $ticket;
            }
        }

        // 1. Buscar por In-Reply-To header
        if ($inReplyTo) {
            $ticket = Ticket::where('email_message_id', $inReplyTo)->first();
            if ($ticket) {
                return $ticket;
            }

            // También buscar en las respuestas
            $reply = TicketReply::where('email_message_id', $inReplyTo)->first();
            if ($reply) {
                return $reply->ticket;
            }
        }

        // 2. Buscar por References header
        if (!empty($references)) {
            foreach ($references as $reference) {
                $ticket = Ticket::where('email_message_id', $reference)->first();
                if ($ticket) {
                    return $ticket;
                }

                $reply = TicketReply::where('email_message_id', $reference)->first();
                if ($reply) {
                    return $reply->ticket;
                }
            }
        }

        // 3. Buscar por email thread ID en el subject
        $threadId = $this->extractThreadIdFromSubject($subject);
        if ($threadId) {
            return Ticket::where('email_thread_id', $threadId)->first();
        }

        // 4. Buscar por patrón de Re: en el subject
        $cleanSubject = $this->cleanSubject($subject);
        return Ticket::where('subject', 'LIKE', "%{$cleanSubject}%")
            ->orderBy('created_at', 'desc')
            ->first();
    }

    /**
     * Crea un nuevo ticket desde un email
     */
    private function createNewTicketFromEmail(array $emailData): Ticket
    {
        $customer = $this->findOrCreateCustomer($emailData['from_email'], $emailData['from_name'] ?? null);

        // Determinar categoría basada en palabras clave del asunto
        $category = $this->detectCategoryFromSubject($emailData['subject'] ?? '');

        // Determinar prioridad basada en palabras clave
        $priority = $this->detectPriorityFromContent($emailData['subject'] ?? '', $emailData['body'] ?? '');

        // Extraer número de reservación si existe
        $reservationNumber = $this->extractReservationNumber($emailData['subject'] ?? '', $emailData['body'] ?? '');

        // Generar thread ID único para este email
        $threadId = 'email_' . Str::uuid();

        $attachments = $emailData['attachments'] ?? [];
        Log::info('Adjuntos recibidos en webhook', ['count' => count($attachments), 'message_id' => $emailData['message_id'] ?? null]);
        if (empty($attachments) && !empty($emailData['message_id'])) {
            Log::info('Intentando descargar adjuntos desde Zoho API', ['message_id' => $emailData['message_id']]);
            $attachments = $this->fetchAttachmentsFromZohoApi($emailData['message_id']);
            Log::info('Adjuntos descargados desde Zoho API', ['count' => count($attachments)]);
        }
        $attachments = $this->processAndStoreAttachments($attachments);
        Log::info('Adjuntos procesados y guardados', ['count' => count($attachments), 'ticket_attachments' => $attachments]);
        $ticket = Ticket::create([
            'customer_id' => $customer->id,
            'created_by' => $this->getSystemUserId(),
            'subject' => $this->cleanSubject($emailData['subject'] ?? 'Email sin asunto'),
            'description' => $this->cleanEmailBody($emailData['body_html'] ?? ''),
            'category' => $category,
            'priority' => $priority,
            'status' => 'open',
            'source' => 'email',
            'reservation_number' => $reservationNumber,
            'email_message_id' => $emailData['message_id'] ?? null,
            'email_thread_id' => $threadId,
            'metadata' => [
                'original_email' => $emailData,
                'attachments' => $attachments
            ]
        ]);

        Log::info('Nuevo ticket creado desde email', [
            'ticket_id' => $ticket->id,
            'ticket_number' => $ticket->ticket_number,
            'customer_email' => $emailData['from_email']
        ]);

        return $ticket;
    }

    /**
     * Crea una respuesta a un ticket existente
     */
    private function createTicketReply(Ticket $ticket, array $emailData): Ticket
    {
        // Buscar si el email es de un usuario del sistema o del cliente
        $user = User::where('email', $emailData['from_email'])->first();
        $isFromCustomer = !$user;
        $cleanedMessage = $this->cleanEmailBody($emailData['body_html'] ?? '');
        // Filtrar mensajes automáticos del sistema
        if (strpos($cleanedMessage, 'TARdesk - Atención al Cliente Respuesta a su ticket de soporte') !== false) {
            Log::info('Mensaje automático detectado, no se crea respuesta', [
                'ticket_id' => $ticket->id,
                'ticket_number' => $ticket->ticket_number,
                'message' => $cleanedMessage
            ]);
            return $ticket;
        }
        $attachments = $emailData['attachments'] ?? [];
        Log::info('Adjuntos recibidos en webhook (reply)', ['count' => count($attachments), 'message_id' => $emailData['message_id'] ?? null]);
        if (empty($attachments) && !empty($emailData['message_id'])) {
            Log::info('Intentando descargar adjuntos desde Zoho API (reply)', ['message_id' => $emailData['message_id']]);
            $attachments = $this->fetchAttachmentsFromZohoApi($emailData['message_id']);
            Log::info('Adjuntos descargados desde Zoho API (reply)', ['count' => count($attachments)]);
        }
        $attachments = $this->processAndStoreAttachments($attachments);
        Log::info('Adjuntos procesados y guardados (reply)', ['count' => count($attachments), 'ticket_attachments' => $attachments]);
        TicketReply::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user ? $user->id : $ticket->customer->id,
            'message' => $cleanedMessage,
            'type' => 'reply',
            'is_customer_visible' => true,
            'email_message_id' => $emailData['message_id'] ?? null,
            'attachments' => $attachments
        ]);

        // Si es del cliente y el ticket estaba cerrado, reabrirlo
        if ($isFromCustomer && in_array($ticket->status, ['resolved', 'closed'])) {
            $ticket->update(['status' => 'open']);
        }

        Log::info('Respuesta agregada a ticket existente', [
            'ticket_id' => $ticket->id,
            'ticket_number' => $ticket->ticket_number,
            'from_customer' => $isFromCustomer
        ]);

        return $ticket;
    }

    /**
     * Procesa y guarda los adjuntos recibidos en el emailData['attachments']
     * Espera que cada adjunto tenga: filename, content (base64), mime/type
     * Devuelve un array con la info de los archivos guardados
     */
    private function processAndStoreAttachments(array $attachments): array
    {
        $saved = [];
        foreach ($attachments as $attachment) {
            if (!isset($attachment['filename'], $attachment['content'])) {
                Log::warning('Adjunto inválido, faltan campos', ['attachment' => $attachment]);
                continue;
            }
            $filename = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $attachment['filename']);
            $path = 'ticket_attachments/' . $filename;
            $content = base64_decode($attachment['content']);
            try {
                \Storage::disk('public')->put($path, $content);
                Log::info('Adjunto guardado en storage', ['filename' => $filename, 'path' => $path, 'size' => strlen($content)]);
            } catch (\Exception $e) {
                Log::error('Error guardando adjunto en storage', ['filename' => $filename, 'error' => $e->getMessage()]);
                continue;
            }
            $saved[] = [
                'filename' => $attachment['filename'],
                'stored_as' => $filename,
                'path' => $path,
                'mime' => $attachment['mime'] ?? null,
                'size' => strlen($content),
            ];
        }
        return $saved;
    }

    /**
     * Busca o crea un cliente basado en el email
     */
    private function findOrCreateCustomer(string $email, ?string $name = null): Customer
    {
        $customer = Customer::where('email', $email)->first();

        if (!$customer) {
            $nameParts = $this->parseEmailName($name ?: $email);

            $customer = Customer::create([
                'first_name' => $nameParts['first_name'],
                'last_name' => $nameParts['last_name'],
                'email' => $email,
                'status' => 'active'
            ]);

            Log::info('Nuevo cliente creado desde email', [
                'customer_id' => $customer->id,
                'email' => $email
            ]);
        }

        return $customer;
    }

    /**
     * Detecta la categoría basada en palabras clave del asunto
     */
    private function detectCategoryFromSubject(string $subject): string
    {
        $subject = strtolower($subject);

        $categoryKeywords = [
            'booking' => ['reserva', 'reservación', 'booking', 'book', 'vuelo'],
            'cancellation' => ['cancelar', 'cancelación', 'cancel', 'anular'],
            'refund' => ['reembolso', 'refund', 'devolución', 'dinero'],
            'baggage' => ['equipaje', 'baggage', 'maleta', 'perdido'],
            'flight_change' => ['cambio', 'change', 'modificar', 'fecha'],
            'complaint' => ['reclamo', 'complaint', 'problema', 'queja', 'malo']
        ];

        foreach ($categoryKeywords as $category => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($subject, $keyword) !== false) {
                    return $category;
                }
            }
        }

        return 'general';
    }

    /**
     * Detecta la prioridad basada en contenido
     */
    private function detectPriorityFromContent(string $subject, string $body): string
    {
        $content = strtolower($subject . ' ' . $body);

        $urgentKeywords = ['urgente', 'urgent', 'emergency', 'emergencia', 'inmediato'];
        $highKeywords = ['importante', 'important', 'pronto', 'soon', 'alta'];

        foreach ($urgentKeywords as $keyword) {
            if (strpos($content, $keyword) !== false) {
                return 'urgent';
            }
        }

        foreach ($highKeywords as $keyword) {
            if (strpos($content, $keyword) !== false) {
                return 'high';
            }
        }

        return 'normal';
    }

    /**
     * Extrae número de reservación del contenido
     */
    private function extractReservationNumber(string $subject, string $body): ?string
    {
        $content = $subject . ' ' . $body;

        // Patrones comunes para números de reservación
        $patterns = [
            '/(?:reserva|reservation|booking|vuelo|flight)[:\s#]*([A-Z0-9]{5,8})/i',
            '/(?:ref|reference)[:\s#]*([A-Z0-9]{5,8})/i',
            '/\b([A-Z]{2,3}[0-9]{3,6})\b/',
            '/\b([0-9]{3}[A-Z]{2,3})\b/'
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $content, $matches)) {
                return strtoupper($matches[1]);
            }
        }

        return null;
    }

    /**
     * Limpia el asunto del email
     */
    private function cleanSubject(string $subject): string
    {
        // Remover prefijos Re:, Fwd:, etc.
        $subject = preg_replace('/^(Re|RE|Fwd|FWD|Fw|FW):\s*/i', '', $subject);
        $subject = trim($subject);

        return $subject ?: 'Email sin asunto';
    }

    /**
     * Limpia el cuerpo del email
     */
    private function cleanEmailBody(string $body): string
    {
        // Eliminar bloques <style>...</style>
        $body = preg_replace('/<style[^>]*>.*?<\/style>/is', '', $body);
        // Eliminar etiquetas <meta>
        $body = preg_replace('/<meta[^>]*>/is', '', $body);
        // Remover HTML
        $body = strip_tags($body);

        // Remover firmas comunes
        $body = preg_replace('/^--\s*$.*/ms', '', $body);
        $body = preg_replace('/^Enviado desde mi .*/m', '', $body);

        // Eliminar bloques de encabezados de correo (De:, Enviado:, Para:, Asunto:)
        $headerBlockPattern = '/(De:.*?Asunto:.*?\n)/is';
        if (preg_match($headerBlockPattern, $body, $matches, PREG_OFFSET_CAPTURE)) {
            $body = substr($body, 0, $matches[0][1]);
        }

        // Eliminar líneas de citado y encabezados individuales
        $lines = explode("\n", $body);
        $cleanLines = [];
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if (
                $trimmed === '' ||
                preg_match('/^>/',$trimmed) ||
                preg_match('/^On .*wrote:/i', $trimmed) ||
                preg_match('/^De:/i', $trimmed) ||
                preg_match('/^Enviado:/i', $trimmed) ||
                preg_match('/^Para:/i', $trimmed) ||
                preg_match('/^Asunto:/i', $trimmed)
            ) {
                continue;
            }
            $cleanLines[] = $line;
        }
        return trim(implode("\n", $cleanLines));
    }

    /**
     * Extrae thread ID del asunto si existe
     */
    private function extractThreadIdFromSubject(string $subject): ?string
    {
        if (preg_match('/\[TK-(\d{4}-\d{6})\]/', $subject, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Parsea el nombre del email
     */
    private function parseEmailName(string $emailOrName): array
    {
        if (strpos($emailOrName, '@') !== false) {
            // Es un email, extraer nombre de la parte local
            $localPart = explode('@', $emailOrName)[0];
            $nameParts = explode('.', $localPart);

            return [
                'first_name' => ucfirst($nameParts[0] ?? 'Cliente'),
                'last_name' => ucfirst($nameParts[1] ?? 'Email')
            ];
        } else {
            // Es un nombre
            $parts = explode(' ', $emailOrName, 2);
            return [
                'first_name' => $parts[0] ?? 'Cliente',
                'last_name' => $parts[1] ?? 'Email'
            ];
        }
    }

    /**
     * Obtiene el ID del usuario del sistema para tickets automáticos
     */
    private function getSystemUserId(): int
    {
        // Buscar un usuario de call center activo para asignar como creador
        $systemUser = User::where('role', 'call_center')
            ->where('is_active', true)
            ->first();

        if (!$systemUser) {
            // Fallback a cualquier usuario activo
            $systemUser = User::where('is_active', true)->first();
        }

        if (!$systemUser) {
            // Si no existe ningún usuario, crear uno por defecto
            $systemUser = User::create([
                'name' => 'System User',
                'email' => 'system@tardesk.com',
                'password' => bcrypt('password'),
                'role' => 'call_center',
                'is_active' => true,
            ]);
        }

        return $systemUser->id;
    }

    private function fetchAttachmentsFromZohoApi(?string $messageId): array
    {
        if (!$messageId) {
            Log::warning('No message_id provided for attachment fetch');
            return [];
        }

        $config = config('services.zoho_mail');
        $clientId = $config['client_id'] ?? null;
        $clientSecret = $config['client_secret'] ?? null;
        $refreshToken = $config['refresh_token'] ?? null;

        if (!$clientId || !$clientSecret || !$refreshToken) {
            Log::warning('Zoho Mail API credentials not configured', [
                'has_client_id' => !empty($clientId),
                'has_client_secret' => !empty($clientSecret),
                'has_refresh_token' => !empty($refreshToken)
            ]);
            return [];
        }

        // Get fresh access token
        $accessToken = $this->getZohoAccessTokenFromRefreshToken();
        if (!$accessToken) {
            Log::error('Failed to obtain Zoho access token');
            return [];
        }

        $apiBase = $config['api_base'] ?? 'https://mail.zoho.com/api';

        // Get accountId (cache it if needed)
        $accountId = $config['account_id'] ?? null;
        if (!$accountId) {
            $accountId = $this->getZohoAccountId($apiBase, $accessToken);
            if (!$accountId) {
                Log::error('Failed to obtain Zoho account ID');
                return [];
            }
            Log::info('Zoho account ID obtained', ['account_id' => $accountId]);
        }

        // Get attachment info using the correct endpoint
        $attachmentInfoUrl = "{$apiBase}/accounts/{$accountId}/messages/{$messageId}/attachmentinfo";
        Log::info('Fetching attachment info', ['url' => $attachmentInfoUrl]);

        $attachmentInfo = $this->zohoApiGet($attachmentInfoUrl, $accessToken);

        if (empty($attachmentInfo['data'])) {
            Log::info('No attachments found for message', ['message_id' => $messageId]);
            return [];
        }

        Log::info('Attachment info received', [
            'message_id' => $messageId,
            'count' => count($attachmentInfo['data']),
            'attachments' => array_map(function($att) {
                return [
                    'name' => $att['attachmentName'] ?? $att['fileName'] ?? 'unknown',
                    'size' => $att['size'] ?? 0,
                    'type' => $att['contentType'] ?? 'unknown'
                ];
            }, $attachmentInfo['data'])
        ]);

        $attachments = [];
        foreach ($attachmentInfo['data'] as $att) {
            // Skip inline images if you want, or process them too
            $isInline = ($att['isInline'] ?? false) || ($att['disposition'] ?? '') === 'inline';

            $attachmentPath = $att['attachmentPath'] ?? null;
            $attachmentName = $att['attachmentName'] ?? $att['fileName'] ?? 'attachment';

            if (!$attachmentPath) {
                Log::warning('Attachment without path', ['attachment' => $att]);
                continue;
            }

            // Download attachment content using attachmentPath
            $downloadUrl = "{$apiBase}/accounts/{$accountId}/{$attachmentPath}";
            Log::info('Downloading attachment', ['url' => $downloadUrl, 'name' => $attachmentName]);

            $attData = $this->zohoApiGet($downloadUrl, $accessToken, true);

            if ($attData) {
                $attachments[] = [
                    'filename' => $attachmentName,
                    'content' => base64_encode($attData),
                    'mime' => $att['contentType'] ?? 'application/octet-stream',
                    'size' => strlen($attData),
                    'disposition' => $isInline ? 'inline' : 'attachment',
                    'cid' => $att['contentId'] ?? null,
                ];
                Log::info('Attachment downloaded successfully', [
                    'filename' => $attachmentName,
                    'size' => strlen($attData)
                ]);
            } else {
                Log::error('Failed to download attachment content', [
                    'url' => $downloadUrl,
                    'name' => $attachmentName
                ]);
            }
        }

        return $attachments;
    }

    private function getZohoAccountId(string $apiBase, string $accessToken): ?string
    {
        $accountsUrl = "{$apiBase}/accounts";
        $accountsResp = $this->zohoApiGet($accountsUrl, $accessToken);

        Log::info('Zoho Mail API accounts response', [
            'url' => $accountsUrl,
            'response' => $accountsResp
        ]);

        if (!empty($accountsResp['data'][0]['accountId'])) {
            return $accountsResp['data'][0]['accountId'];
        }

        return null;
    }

    private function getZohoAccessTokenFromRefreshToken(): ?string
    {
        $config = config('services.zoho_mail');
        $clientId = $config['client_id'];
        $clientSecret = $config['client_secret'];
        $refreshToken = $config['refresh_token'];

        if (!$clientId || !$clientSecret || !$refreshToken) {
            Log::error('Missing Zoho OAuth credentials', [
                'has_client_id' => !empty($clientId),
                'has_client_secret' => !empty($clientSecret),
                'has_refresh_token' => !empty($refreshToken)
            ]);
            return null;
        }

        $url = "https://accounts.zoho.com/oauth/v2/token";
        $params = [
            'refresh_token' => $refreshToken,
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'grant_type' => 'refresh_token',
        ];

        try {
            $response = $this->httpPostForm($url, $params);

            if (isset($response['access_token'])) {
                Log::info('Zoho access token refreshed successfully', [
                    'expires_in' => $response['expires_in'] ?? null
                ]);
                return $response['access_token'];
            } else {
                Log::error('Failed to refresh Zoho access token', [
                    'response' => $response
                ]);
                return null;
            }
        } catch (\Exception $e) {
            Log::error('Exception refreshing Zoho access token', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    private function zohoApiGet($url, $accessToken, $raw = false)
    {
        $headers = [
            'Authorization: Zoho-oauthtoken ' . $accessToken,
            'Accept: application/json',
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            Log::error('Zoho API cURL error', [
                'url' => $url,
                'error' => $curlError
            ]);
            return $raw ? null : [];
        }

        if ($httpCode !== 200) {
            Log::error('Zoho API HTTP error', [
                'url' => $url,
                'http_code' => $httpCode,
                'response' => substr($result, 0, 500)
            ]);
            return $raw ? null : [];
        }

        if ($raw) {
            return $result;
        }

        $decoded = json_decode($result, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('Zoho API invalid JSON', [
                'url' => $url,
                'json_error' => json_last_error_msg(),
                'response' => substr($result, 0, 500)
            ]);
            return [];
        }

        return $decoded;
    }

    private function httpPostForm($url, $params)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

        $result = curl_exec($ch);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            Log::error('HTTP POST cURL error', [
                'url' => $url,
                'error' => $curlError
            ]);
            return [];
        }

        $decoded = json_decode($result, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('HTTP POST invalid JSON response', [
                'url' => $url,
                'json_error' => json_last_error_msg(),
                'response' => substr($result, 0, 500)
            ]);
            return [];
        }

        return $decoded;
    }
}
