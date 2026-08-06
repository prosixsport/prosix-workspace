<?php

namespace App\Http\Controllers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TeamStoreOrderController extends Controller
{
    /**
     * Prosix TeamStore ke tamam orders.
     */
    public function index(): JsonResponse
    {
        try {
            $response = $this->prosixRequest()
                ->get('/api/crm/teamstore-orders');

            if ($response->failed()) {
                Log::error('TeamStore Orders fetch failed', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'TeamStore Orders load nahi ho sake.',
                    'data'    => [],
                ], $this->safeStatus($response->status()));
            }

            $responseData = $response->json();

            return response()->json([
                'success' => true,
                'data'    => $responseData['data']
                    ?? $responseData
                    ?? [],
            ]);
        } catch (ConnectionException $exception) {
            Log::error('TeamStore connection error', [
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Prosix TeamStore ke sath connection nahi ho saka.',
                'data'    => [],
            ], 503);
        } catch (\Throwable $exception) {
            Log::error('TeamStore Orders controller error', [
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'TeamStore Orders load karte waqt error aa gaya.',
                'data'    => [],
            ], 500);
        }
    }

    /**
     * Current CRM user ka TeamStore unread count.
     */
    public function unreadCount(): JsonResponse
    {
        try {
            $response = $this->prosixRequest()
                ->get('/api/crm/teamstore-orders/unread-count');

            if ($response->failed()) {
                Log::error('TeamStore unread count failed', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);

                return response()->json([
                    'count' => 0,
                ]);
            }

            return response()->json([
                'count' => (int) (
                    $response->json('count') ?? 0
                ),
            ]);
        } catch (\Throwable $exception) {
            Log::error('TeamStore unread count error', [
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'count' => 0,
            ]);
        }
    }

    /**
     * Current CRM user ke liye TeamStore order read mark karo.
     */
    public function markRead(int $id): JsonResponse
    {
        try {
            $response = $this->prosixRequest()
                ->post(
                    "/api/crm/teamstore-orders/{$id}/mark-read"
                );

            if ($response->failed()) {
                Log::error('TeamStore mark-read failed', [
                    'order_id' => $id,
                    'status'   => $response->status(),
                    'body'     => $response->body(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'TeamStore Order read mark nahi hua.',
                ], $this->safeStatus($response->status()));
            }

            return response()->json([
                'success' => true,
                'message' => 'TeamStore Order read mark ho gaya.',
            ]);
        } catch (\Throwable $exception) {
            Log::error('TeamStore mark-read error', [
                'order_id' => $id,
                'message'  => $exception->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'TeamStore Order read mark karte waqt error aa gaya.',
            ], 500);
        }
    }

    /**
     * Prosix API request client.
     *
     * Har request ke sath current CRM user ki
     * ID, name aur email Prosix ko bheji jati hai.
     */
    private function prosixRequest()
    {
        $user = auth()->user();

        return Http::baseUrl(
            rtrim(
                (string) config('services.prosix.url'),
                '/'
            )
        )
            ->withToken(
                (string) config(
                    'services.prosix.crm_token'
                )
            )
            ->withHeaders([
                'X-CRM-User-ID' =>
                    (string) $user->id,

                'X-CRM-User-Name' =>
                    (string) $user->name,

                'X-CRM-User-Email' =>
                    (string) $user->email,
            ])
            ->acceptJson()
            ->timeout(30)
            ->retry(2, 500);
    }

    /**
     * Upstream error status ko safe status mein convert karo.
     */
    private function safeStatus(int $status): int
    {
        return $status >= 400 && $status <= 599
            ? $status
            : 500;
    }
}
