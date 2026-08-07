<?php

namespace App\Http\Controllers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TeamStoreOrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $response = $this->prosixRequest($request)
                ->get('/api/crm/teamstore-orders');

            if ($response->failed()) {
                Log::error('TeamStore Orders fetch failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'TeamStore Orders load nahi ho sake.',
                    'data' => [],
                ], $this->safeStatus($response->status()));
            }

            $responseData = $response->json();

            return response()->json([
                'success' => true,
                'data' => $responseData['data']
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
                'data' => [],
            ], 503);
        } catch (\Throwable $exception) {
            Log::error('TeamStore Orders controller error', [
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'TeamStore Orders load karte waqt error aa gaya.',
                'data' => [],
            ], 500);
        }
    }

    public function unreadCount(Request $request): JsonResponse
    {
        try {
            $response = $this->prosixRequest($request)
                ->get('/api/crm/teamstore-orders/unread-count');

            if ($response->failed()) {
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

    public function markRead(
        Request $request,
        int $id
    ): JsonResponse {
        try {
            $response = $this->prosixRequest($request)
                ->post(
                    "/api/crm/teamstore-orders/{$id}/mark-read"
                );

            if ($response->failed()) {
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
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'TeamStore Order read mark karte waqt error aa gaya.',
            ], 500);
        }
    }

    /*
     * CRM -> Prosix original TeamStore order update.
     * Status Prosix DB mein save hoga.
     * Prosix API status change par customer email bhejegi.
     * Remark internal rahega.
     */
    public function update(
        Request $request,
        int $id
    ): JsonResponse {
        $validated = $request->validate([
            'status' => 'sometimes|required|string|max:100',
            'remark' => 'sometimes|nullable|string|max:5000',
        ]);

        if (
            !array_key_exists('status', $validated) &&
            !array_key_exists('remark', $validated)
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Status ya remark required hai.',
            ], 422);
        }

        try {
            $response = $this->prosixRequest($request)
                ->put(
                    "/api/crm/teamstore-orders/{$id}",
                    $validated
                );

            if ($response->failed()) {
                Log::error('TeamStore update failed', [
                    'order_id' => $id,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' =>
                        $response->json('message')
                        ?? 'TeamStore Order update nahi hua.',
                ], $this->safeStatus($response->status()));
            }

            return response()->json(
                $response->json()
            );
        } catch (ConnectionException $exception) {
            Log::error('TeamStore update connection error', [
                'order_id' => $id,
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' =>
                    'Prosix.com ke sath connection nahi ho saka.',
            ], 503);
        } catch (\Throwable $exception) {
            Log::error('TeamStore update error', [
                'order_id' => $id,
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' =>
                    'TeamStore Order update karte waqt error aa gaya.',
            ], 500);
        }
    }

    private function prosixRequest(Request $request)
    {
        $user = $request->user();

        return Http::baseUrl(
            rtrim(config('services.prosix.url'), '/')
        )
            ->withToken(
                config('services.prosix.crm_token')
            )
            ->withHeaders([
                'X-CRM-User-ID' =>
                    (string) ($user?->id ?? ''),
                'X-CRM-User-Name' =>
                    (string) ($user?->name ?? ''),
                'X-CRM-User-Email' =>
                    (string) ($user?->email ?? ''),
            ])
            ->acceptJson()
            ->timeout(30)
            ->retry(2, 500);
    }

    private function safeStatus(int $status): int
    {
        return $status >= 400 && $status <= 599
            ? $status
            : 500;
    }
}
