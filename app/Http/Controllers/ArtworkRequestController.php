<?php

namespace App\Http\Controllers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class ArtworkRequestController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | CRM -> PROSIX.COM : ARTWORK REQUESTS
    |--------------------------------------------------------------------------
    */

    public function index(Request $request): JsonResponse
    {
        try {
            $response = $this->prosixRequest($request)
                ->get('/api/crm/artwork-requests');

            if ($response->failed()) {
                Log::error(
                    'Prosix Artwork Requests fetch failed',
                    [
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]
                );

                return response()->json([
                    'success' => false,
                    'message' => 'Artwork Requests load nahi ho sake.',
                    'data' => [],
                ], $this->safeStatus($response->status()));
            }

            $responseData = $response->json();

            return response()->json([
                'success' => true,
                'data' => $responseData['data'] ?? $responseData ?? [],
            ]);
        } catch (ConnectionException $exception) {
            Log::error(
                'Artwork Requests connection error',
                [
                    'message' => $exception->getMessage(),
                ]
            );

            return response()->json([
                'success' => false,
                'message' => 'Prosix.com ke sath connection nahi ho saka.',
                'data' => [],
            ], 503);
        } catch (\Throwable $exception) {
            Log::error(
                'Artwork Requests controller error',
                [
                    'message' => $exception->getMessage(),
                ]
            );

            return response()->json([
                'success' => false,
                'message' => 'Artwork Requests load karte waqt error aa gaya.',
                'data' => [],
            ], 500);
        }
    }

    public function unreadCount(Request $request): JsonResponse
    {
        try {
            $response = $this->prosixRequest($request)
                ->get('/api/crm/artwork-requests/unread-count');

            return response()->json([
                'count' => $response->successful()
                    ? (int) ($response->json('count') ?? 0)
                    : 0,
            ]);
        } catch (\Throwable $exception) {
            Log::error(
                'Artwork unread count error',
                [
                    'message' => $exception->getMessage(),
                ]
            );

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
                ->post("/api/crm/artwork-requests/{$id}/mark-read");

            return response()->json(
                $response->json(),
                $this->safeStatus($response->status())
            );
        } catch (\Throwable $exception) {
            Log::error(
                'Artwork mark-read error',
                [
                    'artwork_id' => $id,
                    'message' => $exception->getMessage(),
                ]
            );

            return response()->json([
                'success' => false,
                'message' => 'Artwork read status update failed.',
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE ARTWORK STATUS
    |--------------------------------------------------------------------------
    |
    | CRM frontend:
    | PATCH /api/artwork-requests/{id}/status
    |
    | Ye method same status Prosix.com ko forward karta hai:
    | PATCH /api/crm/artwork-requests/{id}/status
    |
    */

    public function updateStatus(
        Request $request,
        int $id
    ): JsonResponse {
        $validated = $request->validate([
            'status' => [
                'required',
                'string',
                Rule::in([
                    'pending',
                    'processing',
                    'completed',
                    'cancelled',
                ]),
            ],
        ]);

        try {
            $response = $this->prosixRequest($request)
                ->patch(
                    "/api/crm/artwork-requests/{$id}/status",
                    [
                        'status' => $validated['status'],
                    ]
                );

            if ($response->failed()) {
                Log::error(
                    'Prosix Artwork Request status update failed',
                    [
                        'artwork_id' => $id,
                        'status_value' => $validated['status'],
                        'response_status' => $response->status(),
                        'response_body' => $response->body(),
                    ]
                );

                return response()->json([
                    'success' => false,
                    'message' =>
                        $response->json('message')
                        ?? 'Prosix.com par artwork status update nahi ho saka.',
                    'data' => $response->json('data'),
                ], $this->safeStatus($response->status()));
            }

            $responseData = $response->json();

            return response()->json([
                'success' => true,
                'message' =>
                    $responseData['message']
                    ?? 'Artwork status successfully updated.',
                'data' =>
                    $responseData['data']
                    ?? $responseData,
            ]);
        } catch (ConnectionException $exception) {
            Log::error(
                'Artwork status connection error',
                [
                    'artwork_id' => $id,
                    'status_value' => $validated['status'],
                    'message' => $exception->getMessage(),
                ]
            );

            return response()->json([
                'success' => false,
                'message' =>
                    'Prosix.com ke sath connection nahi ho saka. Status update nahi hua.',
            ], 503);
        } catch (\Throwable $exception) {
            Log::error(
                'Artwork status update controller error',
                [
                    'artwork_id' => $id,
                    'status_value' => $validated['status'],
                    'message' => $exception->getMessage(),
                ]
            );

            return response()->json([
                'success' => false,
                'message' =>
                    'Artwork status update karte waqt error aa gaya.',
            ], 500);
        }
    }

    private function prosixRequest(Request $request)
    {
        $user = $request->user();

        return Http::baseUrl(
            rtrim(
                (string) config('services.prosix.url'),
                '/'
            )
        )
            ->withToken(
                (string) config('services.prosix.crm_token')
            )
            ->withHeaders([
                'X-CRM-User-ID' =>
                    (string) ($user?->id ?? ''),

                'X-CRM-User-Name' =>
                    (string) ($user?->name ?? ''),

                'X-CRM-User-Email' =>
                    (string) ($user?->email ?? ''),

                'X-CRM-User-Role' =>
                    (string) ($user?->role ?? ''),
            ])
            ->acceptJson()
            ->timeout(30)
            ->retry(2, 500);
    }

    private function safeStatus(int $status): int
    {
        return $status >= 200 && $status <= 599
            ? $status
            : 500;
    }
}
