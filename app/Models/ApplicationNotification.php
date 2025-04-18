<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use GuzzleHttp\Client;

class ApplicationNotification extends Model
{
    public static function sendOneSignalNotificationSchedule($notificationData) {
        # old web
        // $appId = "d1880f77-f3ab-4887-bb53-b271ca70f9ab";
        // $apiKey = "Yjk2Njg3NjAtY2YxNS00ZmM5LWE2OWItNDViNjUyYTdhNWMy";

        $appId ='923ca9ac-fb12-475f-bb8f-46c6465d2a9f';
        $apiKey = 'ZWU1NjE3MWYtZjM2OS00YjllLTg3N2MtYzk3M2E0NzFhMjEw';
        $notification_title = $notificationData['notification_title'];
        $notification_message = $notificationData['notification_description'];
        $notification_image = $notificationData['notification_image'];
        // $player_ids = $notificationData['player_ids']; # Array of specific player IDs
        # Chunk the player IDs into smaller batches to send notifications in chunks
        // $chunks = array_chunk($player_ids, 200); # Chunk size of 200 IDs per request

        $client = new Client();
        // foreach ($chunks as $chunk) {
            $response = $client->post("https://onesignal.com/api/v1/notifications", [
                'headers' => [
                    'Authorization' => 'Basic ' . $apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'app_id' => $appId,
                    'contents' => ['en' => $notification_message],
                    'headings' => ['en' => $notification_title],
                    'big_picture' => $notification_image,
                    'large_icon' => $notification_image,
                    'chrome_web_image' => $notification_image,
                    // 'include_player_ids' => $player_ids,
                    'included_segments' => ['All'],
                 
                    // 'sticky' => true,
                    // 'buttons' => [ // Add action buttons
                    //     [
                    //         'id' => 'button1',
                    //         'text' => 'Option 1',
                    //         //'icon' => 'ic_option1' // Optional: Add a button icon
                    //     ],
                    //     [
                    //         'id' => 'button2',
                    //         'text' => 'Option 2',
                    //       // 'icon' => 'ic_option2' // Optional: Add another button icon
                    //     ],
                    // ],
                    
                ],
            ]);
        // }
    }
    
    
    public static function sendOneSignalNotification($notificationData) {  # to all users

        $appId ='923ca9ac-fb12-475f-bb8f-46c6465d2a9f';
        $apiKey = 'ZWU1NjE3MWYtZjM2OS00YjllLTg3N2MtYzk3M2E0NzFhMjEw';

        $notification_title = $notificationData['notification_title'];
        $notification_url = "mybankpassbook.com";
        $notification_image = $notificationData['notification_image'];
        $notification_message = $notificationData['notification_description'];

        $client = new Client();
        $response = $client->post("https://onesignal.com/api/v1/notifications", [
            'headers' => [
                'Authorization' => 'Basic ' . $apiKey,
            ],
            'json' => [
                'app_id' => $appId,
                'contents' => ['en' => $notification_message],
                'headings' => ['en' => $notification_title],
                'url' => $notification_url,
                'big_picture' => $notification_image,
                'large_icon' => $notification_image,
                'chrome_web_image' => $notification_image, //'https://picsum.photos/600'
                //  'included_segments' => ['All'],
                'include_player_ids' => ['9f81e78a-b67f-4432-af05-0b6246bcc1d6'],
            ],
        ]);
        return $response;
    }
}
