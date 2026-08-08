<?php

namespace App\Http\Controllers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PlaceOrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $response = $this->prosixRequest($request)
                ->get('/api/crm/place-orders');

            if ($response->failed()) {
                Log::error('Prosix Place Orders fetch failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Place Orders load nahi ho sake.',
                    'data' => [],
                ], $this->safeStatus($response->status()));
            }

            $data = $response->json();

            return response()->json([
                'success' => true,
                'data' => $data['data'] ?? $data ?? [],
            ]);
        } catch (ConnectionException $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Prosix.com connection nahi ho saka.',
                'data' => [],
            ], 503);
        } catch (\Throwable $exception) {
            Log::error('CRM Place Orders fetch error', [
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Place Orders load karte waqt error aa gaya.',
                'data' => [],
            ], 500);
        }
    }

    public function unreadCount(Request $request): JsonResponse
    {
        try {
            $response = $this->prosixRequest($request)
                ->get('/api/crm/place-orders/unread-count');

            return response()->json([
                'count' => $response->successful()
                    ? (int) ($response->json('count') ?? 0)
                    : 0,
            ]);
        } catch (\Throwable $exception) {
            return response()->json(['count' => 0]);
        }
    }

    public function markRead(Request $request, int $id): JsonResponse
    {
        try {
            $response = $this->prosixRequest($request)
                ->post("/api/crm/place-orders/{$id}/mark-read");

            return response()->json(
                $response->json(),
                $this->safeStatus($response->status())
            );
        } catch (\Throwable $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Place Order read mark nahi hua.',
            ], 500);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $role = strtolower((string) ($user?->role ?? ''));

        $canEditStatus = in_array(
            $role,
            ['super_admin', 'admin', 'member', 'designer'],
            true
        );

        $canEditRemark = in_array(
            $role,
            ['super_admin', 'admin'],
            true
        );

        $validated = $request->validate([
            'status' => 'sometimes|required|string|max:100',
            'remark' => 'sometimes|nullable|string|max:5000',
        ]);

        if (array_key_exists('status', $validated) && !$canEditStatus) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to change status.',
            ], 403);
        }

        if (array_key_exists('remark', $validated) && !$canEditRemark) {
            return response()->json([
                'success' => false,
                'message' => 'Only admin can edit remarks.',
            ], 403);
        }

        if (!$validated) {
            return response()->json([
                'success' => false,
                'message' => 'No update data received.',
            ], 422);
        }

        try {
            $response = $this->prosixRequest($request)
                ->put(
                    "/api/crm/place-orders/{$id}",
                    $validated
                );

            return response()->json(
                $response->json(),
                $this->safeStatus($response->status())
            );
        } catch (ConnectionException $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Prosix.com connection nahi ho saka.',
            ], 503);
        } catch (\Throwable $exception) {
            Log::error('CRM Place Order update error', [
                'order_id' => $id,
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Place Order update failed.',
            ], 500);
        }
    }

    public function statuses(Request $request): JsonResponse
    {
        try {
            $response = $this->prosixRequest($request)
                ->get('/api/crm/place-orders/statuses');

            return response()->json(
                $response->json(),
                $this->safeStatus($response->status())
            );
        } catch (\Throwable $exception) {
            return response()->json([
                'success' => false,
                'data' => [],
            ], 500);
        }
    }

    public function storeStatus(Request $request): JsonResponse
    {
        $this->ensureStatusPermission($request);

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'color' => [
                'required',
                'regex:/^#[0-9A-Fa-f]{6}$/',
            ],
        ]);

        return $this->forwardStatusWrite(
            $request,
            'post',
            '/api/crm/place-orders/statuses',
            $validated
        );
    }

    public function updateStatusDefinition(
        Request $request,
        int $id
    ): JsonResponse {
        $this->ensureStatusPermission($request);

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'color' => [
                'required',
                'regex:/^#[0-9A-Fa-f]{6}$/',
            ],
        ]);

        return $this->forwardStatusWrite(
            $request,
            'put',
            "/api/crm/place-orders/statuses/{$id}",
            $validated
        );
    }

    public function destroyStatus(
        Request $request,
        int $id
    ): JsonResponse {
        $this->ensureStatusPermission($request);

        try {
            $response = $this->prosixRequest($request)
                ->delete(
                    "/api/crm/place-orders/statuses/{$id}"
                );

            return response()->json(
                $response->json(),
                $this->safeStatus($response->status())
            );
        } catch (\Throwable $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Status delete failed.',
            ], 500);
        }
    }

    private function ensureStatusPermission(Request $request): void
    {
        $role = strtolower(
            (string) ($request->user()?->role ?? '')
        );

        abort_unless(
            in_array(
                $role,
                ['super_admin', 'admin', 'member', 'designer'],
                true
            ),
            403,
            'You do not have permission to manage statuses.'
        );
    }

    private function forwardStatusWrite(
        Request $request,
        string $method,
        string $uri,
        array $payload
    ): JsonResponse {
        try {
            $client = $this->prosixRequest($request);

            $response = $method === 'post'
                ? $client->post($uri, $payload)
                : $client->put($uri, $payload);

            return response()->json(
                $response->json(),
                $this->safeStatus($response->status())
            );
        } catch (\Throwable $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Status save failed.',
            ], 500);
        }
    }

    private function prosixRequest(Request $request)
    {
        $user = $request->user();

        return Http::baseUrl(
            rtrim(config('services.prosix.url'), '/')
        )
            ->withToken(config('services.prosix.crm_token'))
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
