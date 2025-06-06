<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ApplicationNotification;
use App\Models\app_notification;
use Illuminate\Support\Facades\Storage;

use App\Models\NotificationSent;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;


class AccountNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'account:notification';


    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        date_default_timezone_set('Asia/Kolkata');
        $currentTime = date('H:i');

        $accountNotifications = [
            [
                'title' => 'New Transaction Alert',
                'description' => 'A transaction of {{amount}} has been debited to your account. Tap to view details.'
            ],
            [
                'title' => 'Balance Updated',
                'description' => 'Your account balance has been updated. Current balance: {{amount}}.'
            ],
            [
                'title' => 'Low Balance Alert',
                'description' => 'Your account balance is below {{amount}}. Tap to review recent transactions.'
            ],
            [
                'title' => 'Your Monthly Statement is Ready',
                'description' => 'Your account statement for [previous month] is now available. Tap to view or download.'
            ],
            [
                'title' => 'Account Credited',
                'description' => 'An amount of {{amount}} has been credited to your account. Tap to view details.'
            ],
            [
                'title' => 'Account Debited',
                'description' => 'An amount of {{amount}} has been debited from your account. Tap to view details.'
            ],
            [
                'title' => 'Bill Payment Due',
                'description' => 'Your payment for electricity bill is due on (current date to next day). Tap to pay your bill.'
            ],
            [
                'title' => 'Fixed Deposit Maturing Soon',
                'description' => 'Your fixed deposit of {{amount}} is maturing on (current date to next 5 days). Tap to view details.'
            ],
            [
                'title' => 'New Passbook Entry Added',
                'description' => 'A new entry has been added to your passbook for your account ending in xxxx.'
            ],
            [
                'title' => 'Transfer Successful',
                'description' => 'Your transfer of {{amount}} was successful. Tap to view the receipt.'
            ],
            [
                'title' => 'Transaction Failed',
                'description' => 'Your transaction of {{amount}} was unsuccessful. Please try again or contact support.'
            ],
            [
                'title' => 'ATM Withdrawal Alert',
                'description' => 'An amount of {{amount}} was withdrawn from your account via ATM. Tap to view the transaction.'
            ],
            [
                'title' => 'Password Changed Successfully',
                'description' => 'Your account password was changed successfully. If you didn’t make this change, contact support immediately.'
            ],
            [
                'title' => 'Account Blocked',
                'description' => 'Your account has been temporarily blocked due to suspicious activity. Tap for details.'
            ],
            [
                'title' => 'New Feature Available',
                'description' => 'Explore the new statement feature in your passbook app. Tap to learn more.'
            ],
            [
                'title' => 'Suspicious Activity Detected',
                'description' => 'We noticed unusual activity in your account. Tap for more information.'
            ],
            [
                'title' => 'New Device Login Detected',
                'description' => 'Your account was accessed from a new device. Tap to verify or secure your account.'
            ]
        ];

        $previousMonth = Carbon::now('Asia/Kolkata')->subMonth()->format('F');
        $currentDate = Carbon::now('Asia/Kolkata')->format('d F Y'); // e.g., 24 September 2024
        $nextDay = Carbon::now('Asia/Kolkata')->addDay()->format('d F Y'); // e.g., 25 September 2024
        $nextFiveDays = Carbon::now('Asia/Kolkata')->addDays(5)->format('d F Y'); // e.g., 29 September 2024

        foreach ($accountNotifications as &$notification) {
            if (strpos($notification['description'], '{{amount}}') !== false) {
                $randomAmount = rand(1000, 500000);
                $notification['description'] = str_replace('{{amount}}', $randomAmount, $notification['description']);
            }

            if (strpos($notification['description'], '[previous month]') !== false) {
                $notification['description'] = str_replace('[previous month]', $previousMonth, $notification['description']);
            }

            if (strpos($notification['description'], '(current date to next day)') !== false) {
                // Set the date for 'Bill Payment Due'
                $notification['description'] = str_replace('(current date to next day)', "$currentDate to $nextDay", $notification['description']);
            }

            if (strpos($notification['description'], '(current date to next 5 days)') !== false) {
                // Set the date and amount for 'Fixed Deposit Maturing Soon'
                $notification['description'] = str_replace(
                    ['{{amount}}', '(current date to next 5 days)'],
                    [$randomAmount, "$currentDate to $nextFiveDays"],
                    $notification['description']
                );
            }
        }

        $today = Carbon::today();
        $sentToday = NotificationSent::whereDate('sent_on', $today)->pluck('notification_title')->toArray();
        $availableNotifications = array_filter($accountNotifications, function ($notification) use ($sentToday) {
            return !in_array($notification['title'], $sentToday);
        });
        if (empty($availableNotifications)) {
            return;
        }
        $randomNotification = $availableNotifications[array_rand($availableNotifications)];

        $defaultImage = 'public/img/logo.png';
        $notificationSendData['notification_title'] = $randomNotification['title'];
        $notificationSendData['notification_description'] = $randomNotification['description'];
        $notificationSendData['notification_image'] = asset($defaultImage);
        $send_notification = ApplicationNotification::sendOneSignalNotificationSchedule($notificationSendData);

        NotificationSent::create([
            'notification_title' => $randomNotification['title'],
            'sent_on' => $today
        ]);

        // NotificationSent::whereDate('sent_on','!=',$today)->delete();

         Cache::flush(); 
    }
}
