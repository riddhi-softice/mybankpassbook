<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Models\ApplicationNotification;
use App\Models\app_notification;
use Illuminate\Support\Facades\Storage;


class InvoiceNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoice:notification';

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
        $invoiceNotifications = [
            [
                'title' => 'Invoice Created Successfully',
                'description' => 'Your invoice for XXX is ready. Tap to view or share the invoice.'
            ],
            [
                'title' => 'Invoice Sent',
                'description' => 'Invoice #12345 has been sent to [Customer Name]. Awaiting payment.'
            ],
            [
                'title' => 'Payment Received',
                'description' => 'You have received payment for Invoice #12345 from XXX.'
            ],
            [
                'title' => 'Payment Reminder',
                'description' => 'Payment for Invoice #12345 is due tomorrow. Send a reminder to XYZ.'
            ],
            [
                'title' => 'Invoice Overdue',
                'description' => 'Invoice #12345 is overdue. Follow up with ABC to settle the payment.'
            ],
            [
                'title' => 'Invoice Draft Saved',
                'description' => 'Your draft for Invoice #12345 has been saved. Tap to continue editing.'
            ]
        ];

        // Pick a random notification from the invoiceNotifications array
        $randomNotification = $invoiceNotifications[array_rand($invoiceNotifications)];

        $defaultImage = 'public/img/logo.png';

        // Set notification data
        $notificationSendData['notification_title'] = $randomNotification['title'];
        $notificationSendData['notification_description'] = $randomNotification['description'];
        $notificationSendData['notification_image'] = asset($defaultImage);

        // Send the notification via OneSignal or any other service
        $send_notification = ApplicationNotification::sendOneSignalNotificationSchedule($notificationSendData);
    }
}
