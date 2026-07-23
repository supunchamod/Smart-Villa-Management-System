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
     * Fetches the session status - auto-starting the session on WAHA's
     * side if it's missing or stopped, via WhatsAppService::getSessionStatus()
     * - and, only while a QR scan is actually needed, the QR code as a
     * ready-to-render Base64 data URI straight from
     * WhatsAppService::getQrCode(). This is what both index() and
     * status() poll, so the page never gets stuck showing "STOPPED": it
     * either shows the QR right away or picks it up on the next poll a
     * few seconds later.
     */
    private function currentState(): array
    {
        $status = $this->whatsapp->getSessionStatus() ?? 'STOPPED';
        $qr = $status === 'SCAN_QR_CODE' ? $this->whatsapp->getQrCode() : null;

        return [
            'status' => $status,
            'qr' => $qr,
        ];
    }
}
