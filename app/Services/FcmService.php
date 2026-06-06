<?php

namespace App\Services;

use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Support\Facades\Http;

class FcmService
{
    public static function send($token, $title, $body, $data = [])
    {
        if (!$token) return false;

        $keyPath = storage_path('app/firebase/firebase-key.json');

        $json = json_decode(file_get_contents($keyPath), true);
        $projectId = $json['project_id'];

        $credentials = new ServiceAccountCredentials(
            ['https://www.googleapis.com/auth/firebase.messaging'],
            $keyPath
        );

        $accessToken = $credentials->fetchAuthToken()['access_token'] ?? null;

        if (!$accessToken) return false;

        return Http::withToken($accessToken)->post(
            "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send",
            [
                'message' => [
                    'token' => $token,
                    'notification' => [
                        'title' => $title,
                        'body' => $body,
                    ],
                    'data' => array_map('strval', $data),
                ],
            ]
        )->successful();
    }
}
