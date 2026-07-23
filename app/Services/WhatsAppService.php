<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected string $baseUrl;

    protected ?string $apiKey;

    protected string $session;

    protected int $timeout;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('waha.base_url'), '/');
        $this->apiKey = config('waha.api_key');
        $this->session = config('waha.session', 'default');
        $this->timeout = (int) config('waha.timeout', 30);
    }

    /**
     * Get the current WhatsApp session status (WORKING, SCAN_QR_CODE,
     * STARTING, etc). If the session doesn't exist yet (404) or has been
     * left STOPPED, it's started automatically so callers never have to
     * handle a "no session" state themselves - they either get a real
     * status back or null if WAHA itself is unreachable.
     */
    public function getSessionStatus(): ?string
    {
        $response = $this->client()->get("/api/sessions/{$this->session}");

        $needsStart = $response->status() === 404
            || ($response->successful() && $response->json('status') === 'STOPPED');

        if ($needsStart) {
            $this->startSession();

            sleep(2);

            $response = $this->client()->get("/api/sessions/{$this->session}");
        }

        if ($response->failed()) {
            Log::error('WAHA: failed to fetch session status', [
                'session' => $this->session,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        }

        return $response->json('status');
    }

    /**
     * Retrieve the session's QR code as a ready-to-render Base64 data URI.
     * Ensures the session actually exists and isn't stopped first (via
     * getSessionStatus(), which auto-starts it if needed) since WAHA has
     * nothing to return a QR code for otherwise.
     *
     * This hits /api/{session}/auth/qr - the auth endpoints live directly
     * under /api/{session}/..., unlike session management itself
     * (/api/sessions/{session}) - with an explicit x-api-key header
     * (falling back to WAHA's own "secret" quickstart default when none
     * is configured) and Accept: image/png so the NOWEB engine returns
     * the QR as a raw binary image rather than wrapping it in JSON.
     */
    public function getQrCode(): ?string
    {
        $status = $this->getSessionStatus();

        if ($status === null) {
            return null;
        }

        if ($status === 'WORKING') {
            // Already connected - there's no QR code to scan.
            return null;
        }

        $response = Http::baseUrl($this->baseUrl)
            ->timeout($this->timeout)
            ->withHeaders([
                'x-api-key' => $this->apiKey ?: 'secret',
                'Accept' => 'image/png',
            ])
            ->get("/api/{$this->session}/auth/qr");

        if ($response->status() !== 200) {
            Log::error('WAHA: failed to fetch QR code', [
                'session' => $this->session,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        }

        return 'data:image/png;base64,'.base64_encode($response->body());
    }

    /**
     * Creates (or restarts) the named session on the WAHA instance.
     */
    private function startSession(): bool
    {
        $response = $this->client()->post('/api/sessions/start', [
            'name' => $this->session,
        ]);

        if ($response->failed()) {
            Log::error('WAHA: failed to start session', [
                'session' => $this->session,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;
        }

        return true;
    }

    /**
     * Log out the current session, disconnecting the linked device.
     */
    public function logoutSession(): bool
    {
        $response = $this->client()->post("/api/sessions/{$this->session}/logout");

        if ($response->failed()) {
            Log::error('WAHA: failed to logout session', [
                'session' => $this->session,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;
        }

        return true;
    }

    /**
     * Send a plain UTF-8 text message, preserving emojis.
     */
    public function sendTextMessage(string $phone, string $message): bool
    {
        $chatId = $this->formatPhoneNumber($phone);

        $response = $this->client()->post('/api/sendText', [
            'session' => $this->session,
            'chatId' => $chatId,
            'text' => $message,
        ]);

        if ($response->failed()) {
            Log::error('WAHA: failed to send text message', [
                'session' => $this->session,
                'chatId' => $chatId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;
        }

        return true;
    }

    /**
     * Send a local document (e.g. PDF) by embedding it as a Base64 payload,
     * avoiding the need for a publicly reachable file URL.
     */
    public function sendDocument(string $phone, string $filePath, string $fileName, string $caption = ''): bool
    {
        if (! is_file($filePath) || ! is_readable($filePath)) {
            Log::error('WAHA: document file not found or unreadable', [
                'filePath' => $filePath,
            ]);

            return false;
        }

        $chatId = $this->formatPhoneNumber($phone);
        $mimeType = mime_content_type($filePath) ?: 'application/octet-stream';
        $base64 = base64_encode(file_get_contents($filePath));

        $response = $this->client()->post('/api/sendDocument', [
            'session' => $this->session,
            'chatId' => $chatId,
            'caption' => $caption,
            'file' => [
                'mimetype' => $mimeType,
                'filename' => $fileName,
                'data' => $base64,
            ],
        ]);

        if ($response->failed()) {
            Log::error('WAHA: failed to send document', [
                'session' => $this->session,
                'chatId' => $chatId,
                'fileName' => $fileName,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;
        }

        return true;
    }

    /**
     * Format a raw phone number into WAHA's chatId format
     * (94XXXXXXXXX@c.us):
     *   - trims whitespace and strips a leading '+'
     *   - strips everything else that isn't a digit (spaces, dashes,
     *     parentheses, etc.)
     *   - a local Sri Lankan number starting with '0' (e.g. 0781655306)
     *     has the '0' replaced with the '94' country code
     *   - anything else not already carrying the '94' country code gets
     *     it prepended (e.g. 781655306 -> 94781655306)
     */
    public function formatPhoneNumber(string $phone): string
    {
        $phone = trim($phone);

        if (str_starts_with($phone, '+')) {
            $phone = substr($phone, 1);
        }

        $digits = preg_replace('/\D/', '', $phone);

        if (str_starts_with($digits, '0')) {
            $digits = '94'.substr($digits, 1);
        } elseif (! str_starts_with($digits, '94')) {
            $digits = '94'.$digits;
        }

        return "{$digits}@c.us";
    }

    /**
     * Build a preconfigured HTTP client for the WAHA instance's JSON
     * endpoints.
     */
    protected function client(): PendingRequest
    {
        $client = Http::baseUrl($this->baseUrl)
            ->timeout($this->timeout)
            ->acceptJson();

        if (! empty($this->apiKey)) {
            $client = $client->withHeaders(['X-Api-Key' => $this->apiKey]);
        }

        return $client;
    }
}
