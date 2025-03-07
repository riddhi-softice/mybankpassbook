<?php

// app/Helpers/FCMHelper.php
namespace App\Helpers;

use Google_Client;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class FCMHelper
{
    private $projectId;
    private $serviceAccountPath;

    public function __construct()
    {
        // $this->projectId = env('FIREBASE_PROJECT_ID');
        $this->projectId = 'all-bank-balance-check-93c19';  # KIRTAN BHAI FIREBASE ACCOUNT
        $this->serviceAccountPath = storage_path('app/firebase/serviceAccountKey.json');
    }

    public function getAccessToken()
    {
        $client = new Google_Client();
        $client->setAuthConfig($this->serviceAccountPath);
        $client->addScope('https://www.googleapis.com/auth/firebase.messaging');

        $token = $client->fetchAccessTokenWithAssertion();
        return $token['access_token'];
    }

    public function sendNotification(array $deviceToken, $title, $body,$imageUrl)
    {
        $accessToken = $this->getAccessToken();
        $client = new Client();
        $url = "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send";

        $responses = [];
        foreach ($deviceToken as $token) {
            $message = [
                'message' => [
                    'token' => $token,
                    'notification' => [
                        'title' => $title,
                        'body' => $body,
                        'image' => $imageUrl,
                    ],
                ],
            ];
            // Log::info("FCM URL: $url");
            // Log::info("FCM Message: " . json_encode($message));
            try {
                $response = $client->post($url, [
                    'headers' => [
                        'Authorization' => "Bearer $accessToken",
                        'Content-Type' => 'application/json',
                    ],
                    'json' => $message,
                ]);
                $responses[] = $response->getBody()->getContents();
            } catch (\Exception $e) {
                // Log::error('FCM Notification Error: ' . $e->getMessage());
                $responses[] = ['error' => $e->getMessage()];
            }
        }
        // Log::info("FCM Message sent");
        return $responses;
    }

    /* public function sendNotification($deviceToken, $title, $body)
    {
        // dd($this->projectId,$this->serviceAccountPath);
        $accessToken = $this->getAccessToken();
        $client = new Client();
        $url = "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send";

        $message = [
            'message' => [
                'token' => $deviceToken,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
            ],
        ];

        $response = $client->post($url, [
            'headers' => [
                'Authorization' => "Bearer $accessToken",
                'Content-Type' => 'application/json',
            ],
            'json' => $message,
        ]);

        return $response->getBody()->getContents();
    } */

}
