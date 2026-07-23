<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\WhatsAppService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class WhatsAppSettingsController extends Controller
{
    public function __construct(private readonly WhatsAppService $whatsapp)
    {
    }

    /**
     * Render the WhatsApp settings page with the session's current status
     * (and QR code, if a scan is needed) so the page shows something
     * meaningful before the client-side poller takes over.
     */
    public function index(): View
    {
        return view('admin.settings.whatsapp', $this->currentState());
    }

    /**
     * Polled by the settings page every few seconds so the status badge
     * and QR code stay in sync with the WAHA session without a reload.
     */
    public function status(): JsonResponse
    {
        return response()->json($this->currentState());
    }

    /**
     * Log out the WAHA session, unlinking the connected phone number.
     */
    public function disconnect(): RedirectResponse
    {
        if (! $this->whatsapp->logoutSession()) {
            return back()->with('error', 'Failed to disconnect WhatsApp. Please check the logs and try again.');
        }

        return back()->with('status', 'WhatsApp has been disconnected successfully.');
    }

    /**
     * Fetches the session status and, only while a QR scan is actually
     * needed, the QR code image - there's no reason to ask WAHA for a QR
     * when the session is already connected or stopped outright.
     */
    private function currentState(): array
    {
        $session = $this->whatsapp->getSessionStatus();
        $status = $session['status'] ?? 'STOPPED';

        return [
            'status' => $status,
            'qr' => $status === 'SCAN_QR_CODE' ? $this->qrDataUri() : null,
        ];
    }

    /**
     * Normalizes the QR value returned by WhatsAppService::getQrCode()
     * (a bare Base64 string) into a data URI the <img> tag can render
     * directly, without assuming the service already did so.
     */
    private function qrDataUri(): ?string
    {
        $qr = $this->whatsapp->getQrCode();

        if (! $qr) {
            return null;
        }

        return str_starts_with($qr, 'data:') ? $qr : "data:image/png;base64,{$qr}";
    }
}
