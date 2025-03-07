<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\ApplicationNotification;
use DB;

class SendArticleNotifications extends Command
{
    protected $signature = 'notify:articles';
    protected $description = 'Send article notifications to users';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()  # ONE SIGNAL NOTIFICATION
    {
        //  \Log::info("Cron: before article notification sent");
        $response = Http::get('https://apps.videoapps.club/common_news/list_articles.php');
        $articles = $response->json();

        date_default_timezone_set('Asia/Kolkata');
        $currentTime = date('H:i');
        if ($currentTime >= '00:00' && $currentTime < '09:00') {
            $article = $articles['articles'][0];   # 9 AM
        } elseif ($currentTime >= '09:00' && $currentTime < '21:00') {
                $article = $articles['articles'][1];   # 9 PM
        }else{
                $article = $articles['articles'][2];
        }

        if (!is_null($article)) {

            $imageUrl = ($article['image_url'] == null) ? asset('img/No-Image-Placeholder.svg') : asset($article['image_url']);
            $notificationSendData['notification_title'] =$article['title'];
            $notificationSendData['notification_description'] = $article['title'];
            $notificationSendData['notification_image'] = $imageUrl;

            $send_notification = ApplicationNotification::sendOneSignalNotificationSchedule($notificationSendData);
            // \Log::info("Cron: article notification sent");
        }

    }

}
