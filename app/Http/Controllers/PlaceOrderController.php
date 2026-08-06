<?php

namespace App\Http\Controllers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PlaceOrderController extends Controller
{
    /**
     * Prosix.com se tamam Place Orders hasil karo.
     */
    public function index(): JsonResponse
    {
        try {
            $response = $this->prosixRequest()
                ->get('/api/crm/place-orders');

            if ($response->failed()) {
                Log::error('Prosix Place Orders fetch failed', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Place Orders load nahi ho sake.',
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
            Log::error('Prosix Place Orders connection error', [
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Prosix.com ke sath connection nahi ho saka.',
                'data'    => [],
            ], 503);
        } catch (\Throwable $exception) {
            Log::error('Place Orders controller error', [
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Place Orders load karte waqt error aa gaya.',
                'data'    => [],
            ], 500);
        }
    }

    /**
     * Current CRM member ka unread count.
     */
    public function unreadCount(): JsonResponse
    {
        try {
            $response = $this->prosixRequest()
                ->get('/api/crm/place-orders/unread-count');

            if ($response->failed()) {
                Log::error('Place Orders unread count failed', [
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
            Log::error('Place Orders unread count error', [
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'count' => 0,
            ]);
        }
    }

    /**
     * Current CRM member ke liye exact order read mark karo.
     */
    public function markRead(int $id): JsonResponse
    {
        try {
            $response = $this->prosixRequest()
                ->post(
                    "/api/crm/place-orders/{$id}/mark-read"
                );

            if ($response->failed()) {
                Log::error('Place Order mark-read failed', [
                    'order_id' => $id,
                    'status'   => $response->status(),
                    'body'     => $response->body(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Place Order read mark nahi hua.',
                ], $this->safeStatus($response->status()));
            }

            return response()->json([
                'success' => true,
                'message' => 'Place Order read mark ho gaya.',
            ]);
        } catch (\Throwable $exception) {
            Log::error('Place Order mark-read error', [
                'order_id' => $id,
                'message'  => $exception->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Place Order read mark karte waqt error aa gaya.',
            ], 500);
        }
    }

    /**
     * Prosix API request client.
     *
     * Har request ke sath current CRM member ki identity jati hai.
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
     * Invalid upstream status ko safe response status mein convert karo.
     */
    private function safeStatus(int $status): int
    {
        return $status >= 400 && $status <= 599
            ? $status
            : 500;
    }
}
