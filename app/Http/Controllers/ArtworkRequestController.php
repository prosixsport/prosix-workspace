<?php

namespace App\Http\Controllers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ArtworkRequestController extends Controller
{
    /**
     * Prosix.com se tamam Artwork Requests hasil karo.
     */
    public function index(): JsonResponse
    {
        try {
            $response = $this->prosixRequest()
                ->get('/api/crm/artwork-requests');

            if ($response->failed()) {
                Log::error('Artwork Requests fetch failed', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Artwork Requests load nahi ho sake.',
                    'data'    => [],
                ], $this->safeStatus($response->status()));
            }

            $responseData = $response->json();

            return response()->json([
                'success' => true,
                'data'    => $responseData['data'] ?? $responseData ?? [],
            ]);
        } catch (ConnectionException $exception) {
            Log::error('Artwork Requests connection error', [
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Prosix.com ke sath connection nahi ho saka.',
                'data'    => [],
            ], 503);
        } catch (\Throwable $exception) {
            Log::error('Artwork Requests controller error', [
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Artwork Requests load karte waqt error aa gaya.',
                'data'    => [],
            ], 500);
        }
    }

    /**
     * Artwork Requests unread count.
     */
    public function unreadCount(): JsonResponse
    {
        try {
            $response = $this->prosixRequest()
                ->get('/api/crm/artwork-requests/unread-count');

            if ($response->failed()) {
                return response()->json([
                    'count' => 0,
                ]);
            }

            return response()->json([
                'count' => (int) ($response->json('count') ?? 0),
            ]);
        } catch (\Throwable $exception) {
            Log::error('Artwork unread count error', [
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'count' => 0,
            ]);
        }
    }

    /**
     * Artwork Request ko read mark karo.
     */
    public function markRead(int $id): JsonResponse
    {
        try {
            $response = $this->prosixRequest()
                ->post("/api/crm/artwork-requests/{$id}/mark-read");

            if ($response->failed()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Artwork Request read mark nahi hua.',
                ], $this->safeStatus($response->status()));
            }

            return response()->json([
                'success' => true,
                'message' => 'Artwork Request read mark ho gaya.',
            ]);
        } catch (\Throwable $exception) {
            Log::error('Artwork mark-read error', [
                'request_id' => $id,
                'message'    => $exception->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Artwork Request read mark karte waqt error aa gaya.',
            ], 500);
        }
    }

    private function prosixRequest()
    {
        return Http::baseUrl(
            rtrim(config('services.prosix.url'), '/')
        )
            ->withToken(config('services.prosix.crm_token'))
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
