<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\BaseController as BaseController;
use App\Models\User;
use App\Models\ApplicationNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Cache;

define('PACKAGE_NAME', 'com.allbankpassbook.balanchecker');
define('PRODUCT_ID', 'com.allbankpassbook.balanchecker.weekly');
define('PURCHASE_KEY', 'com.allbankpassbook.balanchecker.weekly');
define('NEWS_API_KEY', 'b35767cdaa804a5fa735ed9a96c7f782');

class ApiController extends BaseController
{    
    public function noti_test()
    {
        $notificationSendData = [
            'notification_title' => "hello",
            'notification_description' => "Welcome Test!",
            'notification_image' => "https://images.pexels.com/photos/674010/pexels-photo-674010.jpeg",
        ];
        $send_notification = ApplicationNotification::sendOneSignalNotification($notificationSendData);
        dd($send_notification);
    }
        
    public function user_login(Request $request)
    {
        $data = DB::table('common_settings')->where('setting_key','=','app_install_count')->pluck('setting_value')->first();
        $count = $data + 1;
        DB::table('common_settings')->where('setting_key','=','app_install_count')->update(['setting_value'=>$count]);
        
        $user = [
            "id"=> 2,
            "device_token" => "abc",
            "remember_token" => "bc81f85211924c7c68f15d21767df32a2e8653d1e9dc950b198d46812f9a5ffa",
            "updated_at" => "2024-10-21T05:24:33.000000Z",
            "created_at" => "2024-10-21T05:24:33.000000Z",
           
        ];
        $response = $this->encryptData($user);
        return $this->sendResponse($response, 'Data get Successfully!');
    }

    public function get_common_setting(Request $request)
    {
        // Cache the settings to reduce DB hits
        $cacheKey = 'common_settings';
        
        // Cache for 60 minutes (or adjust as needed)
        $settings = Cache::remember($cacheKey, 60, function() {
            return DB::table('common_settings')->get();
        });
        
        $formattedSettings = [];
        foreach ($settings as $setting) {
            $values = explode(',', $setting->setting_value);
            foreach ($values as $value) {
                $formattedSettings[$setting->setting_key][] = $value;
            }
        }
        
        $response = $this->encryptData($formattedSettings);

        return $this->sendResponse($response, 'Data retrieved successfully!');
    }

    public function get_article(Request $request)
    {
         // Cache::flush(); 
         
        $todayDate = date('d M, Y');
        $perPage = 8;
        $page = !empty($request->page_no) ? $request->page_no : 1;

        // Cache the articles for 30 minutes
        $cacheKey = 'articles_page_' . $page;
        $cachedArticles = Cache::remember($cacheKey, 30, function() {
            $response = Http::get('https://apps.videoapps.club/common_news/new_list_articles.php');
            return $response->json();
        });

        $getData = $cachedArticles['articles'];
        $getArticles = array_slice($getData, ($page - 1) * $perPage, $perPage);

        $latestArticles = [];
        $blank_image = asset('storage/app/public/news_images/placholder.jpg');

        foreach ($getArticles as $key => $article) {
            $publishedAt = $article['published_at'] ?? null;
            $img_url = $article['image_url'] ?? $blank_image;

            if ($publishedAt && strtotime($publishedAt)) {
                $content = $article['content'];
                $first_sentence = preg_split('/[.|\r]/', $content)[0] ?? '';

                $latestArticles[] = [
                    'title' => $article['title'] ?? 'Data not found',
                    'description' => $article['description'] ?? 'Data not found',
                    'content' => $first_sentence,
                    'author' => $article['author'],
                    'url' => $img_url,
                    'publishedAt' => $todayDate,
                    'detail_url' => $article['url'],
                    'source_name' => $article['source_name'],
                ];
            }
        }

        // Pagination details
        $last = count($getData) / $perPage;
        $paginationDetails = [
            'total_record' => count($getData),
            'per_page' => (int) $perPage,
            'current_page' => (int) $page,
            'last_page' => ceil($last),
        ];

        $responseData['pagination'] = $paginationDetails;
        $responseData['article_data'] = $latestArticles;
        $response = $this->encryptData($responseData);

        return $this->sendResponse($response, 'Data retrieved successfully.');
    }
    
    public function validateInAppPurchase(Request $request)
    {
        $purchase_json = $request->input('purchase_json');
        $purchaseData = json_decode($purchase_json, true);

        $orderId = $purchaseData['orderId'] ?? null;
        $packageName = $purchaseData['packageName'] ?? null;
        $purchaseState = $purchaseData['purchaseState'] ?? null;

        if (strpos($orderId, 'GPA') !== 0) {
            return response()->json(['success' => false, 'message' => 'Order ID is invalid'], 400);
        }
        $package_name = PACKAGE_NAME;
        if($package_name != $packageName){
            return response()->json(['success' => false, 'message' => "Invalid package name"], 400);
        }
        if($purchaseState == 1){
            return response()->json(['success' => false, 'message' => "Payment Cancel"], 400);
        }
        if($purchaseState == 2){
            return response()->json(['success' => false, 'message' => "Payment Pending"], 400);
        }

        if($purchaseState == 0 && strpos($orderId, 'GPA') === 0 && $package_name == $packageName){
            return response()->json(['success' => true, 'message' => "Payment Successful"], 200);
        }

        return response()->json(['success' => false, 'message' => "Something went wrong"]);
    }

    public function get_bank_holiday(Request $request)
    {
        $todayDate = date('Y-m-d');
        // $todayDate = "2025-01-11"; // For testing

        $data = DB::table('bank_holiday')->where('date', $todayDate)->first();

        if (is_null($data)) {
            $response = [
                'holiday_status'   => false,
                'holiday_reason'   => '',
                'bank_time'        => '9:30AM - 3:30PM',
                'date'             => date('d M Y', strtotime($todayDate)),
                'day'              => date('l', strtotime($todayDate)),
            ];
        } else {
            $response = [
                'holiday_status'   => true,
                'holiday_reason'   => $data->holiday_reason ?? '',
                'bank_time'        => '',
                'date'             => date('d M Y', strtotime($data->date)),
                'day'              => $data->day ?? date('l', strtotime($data->date)),
            ];
        }

        $encResponse = $this->encryptData($response);
        return $this->sendResponse($encResponse, 'Data fetched successfully!');
    }

    public function BankHolidayStore() 
    { 
        // Scrap data using chatgpt
        // https://www.bankbazaar.com/indian-holiday/bank-holidays.html  // this apis data
        // https://timesofindia.indiatimes.com/business/bank-holidays/uttar-pradesh  

        $data = [
            ["date" => "2025-01-11", "day" => "Saturday", "holiday_reason" => "2nd Saturday" ],
            ["date" => "2025-01-25", "day" => "Saturday", "holiday_reason" => "4th Saturday" ],
            ["date" => "2025-01-26", "day" => "Sunday", "holiday_reason" => "Republic Day" ],
            ["date" => "2025-02-08", "day" => "Saturday", "holiday_reason" => "2nd Saturday" ],
            ["date" => "2025-02-22", "day" => "Saturday", "holiday_reason" => "4th Saturday" ],
            ["date" => "2025-02-26", "day" => "Wednesday", "holiday_reason" => "Maha Shivaratri" ],
            ["date" => "2025-03-08", "day" => "Saturday", "holiday_reason" => "2nd Saturday" ],
            ["date" => "2025-03-14", "day" => "Friday", "holiday_reason" => "Holi" ],
            ["date" => "2025-03-22", "day" => "Saturday", "holiday_reason" => "4th Saturday" ],
            ["date" => "2025-03-30", "day" => "Sunday", "holiday_reason" => "Ugadi" ],
            ["date" => "2025-04-12", "day" => "Saturday", "holiday_reason" => "2nd Saturday" ],
            ["date" => "2025-04-13", "day" => "Sunday", "holiday_reason" => "Vaisakhi" ],
            ["date" => "2025-04-14", "day" => "Monday", "holiday_reason" => "Ambedkar Jayanti" ],
            ["date" => "2025-04-18", "day" => "Friday", "holiday_reason" => "Good Friday" ],
            ["date" => "2025-04-26", "day" => "Saturday", "holiday_reason" => "4th Saturday" ],
            ["date" => "2025-05-01", "day" => "Thursday", "holiday_reason" => "May Day" ],
            ["date" => "2025-05-10", "day" => "Saturday", "holiday_reason" => "2nd Saturday" ],
            ["date" => "2025-05-24", "day" => "Saturday", "holiday_reason" => "4th Saturday" ],
            ["date" => "2025-06-06", "day" => "Friday", "holiday_reason" => "Bakrid/Eid al-Adha" ],
            ["date" => "2025-06-14", "day" => "Saturday", "holiday_reason" => "2nd Saturday" ],
            ["date" => "2025-06-28", "day" => "Saturday", "holiday_reason" => "4th Saturday" ],
            ["date" => "2025-07-12", "day" => "Saturday", "holiday_reason" => "2nd Saturday" ],
            ["date" => "2025-07-26", "day" => "Saturday", "holiday_reason" => "4th Saturday" ],
            ["date" => "2025-08-09", "day" => "Saturday", "holiday_reason" => "2nd Saturday" ],
            ["date" => "2025-08-15", "day" => "Friday", "holiday_reason" => "Independence Day" ],
            ["date" => "2025-08-15", "day" => "Friday", "holiday_reason" => "Janmashtami" ],
            ["date" => "2025-08-23", "day" => "Saturday", "holiday_reason" => "4th Saturday" ],
            ["date" => "2025-09-13", "day" => "Saturday", "holiday_reason" => "2nd Saturday" ],
            ["date" => "2025-09-27", "day" => "Saturday", "holiday_reason" => "4th Saturday" ],
            ["date" => "2025-10-02", "day" => "Thursday", "holiday_reason" => "Gandhi Jayanti" ],
            ["date" => "2025-10-11", "day" => "Saturday", "holiday_reason" => "2nd Saturday" ],
            ["date" => "2025-10-20", "day" => "Monday",   "holiday_reason" => "Diwali" ],
            ["date" => "2025-10-25", "day" => "Saturday", "holiday_reason" => "4th Saturday" ],
            ["date" => "2025-11-08", "day" => "Saturday", "holiday_reason" => "2nd Saturday" ],
            ["date" => "2025-11-22", "day" => "Saturday", "holiday_reason" => "4th Saturday" ],
            ["date" => "2025-12-13", "day" => "Saturday", "holiday_reason" => "2nd Saturday" ],
            ["date" => "2025-12-25", "day" => "Thursday", "holiday_reason" => "Christmas Day" ],
            ["date" => "2025-12-27", "day" => "Saturday", "holiday_reason" => "4th Saturday"]
        ];

        DB::table('bank_holiday')->insert($data);
        return "Data Store successfully.";
    }

    #######################################################
    
    public function test_notification_article(Request $request)
    {
        $response = Http::get('https://apps.videoapps.club/common_news/list_articles.php');
        $articles = $response->json();

        date_default_timezone_set('Asia/Kolkata');
        $currentTime = date('H:i');
        if ($currentTime >= '00:00' && $currentTime < '10:00') {
            $article = $articles['articles'][0];   # 10 AM
        } elseif ($currentTime >= '10:00' && $currentTime < '13:00') {
            $article = $articles['articles'][1];   # 1 PM
        } elseif ($currentTime >= '13:00' && $currentTime < '18:00') {
            $article = $articles['articles'][2];   # 6 PM
        } elseif ($currentTime >= '18:00' && $currentTime < '21:00') {
                $article = $articles['articles'][3];   # 9 PM
        }else{
                $article = $articles['articles'][4];
        }

        if (!is_null($article)) {

            $imageUrl = ($article['image_url'] == null) ? asset('img/No-Image-Placeholder.svg') : asset($article['image_url']);
            $notificationSendData['notification_title'] =$article['title'];
            $notificationSendData['notification_description'] = $article['title'];
            $notificationSendData['notification_image'] = $imageUrl;

            $send_notification = ApplicationNotification::sendOneSignalNotificationSchedule($notificationSendData);
            // \Log::info("Cron: article notification sent");
            dd($send_notification);
        }
    } 

    public function user_login_old(Request $request)
    {
        try {
            $request->validate([
                'device_token' => 'required|string|max:255',
            ]);

            $user = User::where('device_token', $request->device_token)->first();
            if ($user) {
                $token = $this->generateRandomToken();
                $user->update(['remember_token' => $token]);

                $response = $this->encryptData($user);
                return $this->sendResponse($response, 'User login successfully.');
            } else {
                $input = $request->only('device_token');
                $input['remember_token'] = $this->generateRandomToken();
                $user = User::create($input);

                $response = $this->encryptData($user);
                return $this->sendResponse($response, 'User sign-up successfully.');
            }
        } catch (ValidationException $e) {
            $errors = $e->validator->errors()->first();
            return $this->sendError($errors);
        } catch (\Exception $e) {
            return $this->sendError('An unexpected error occurred: ' . $e->getMessage());
        }
    }

    public function get_article_old(Request $request)
    {
        $todayDate = date('d M, Y');
        $perPage = 8;
        $page = 1;
        if(!empty($request->page_no)){
            $page = $request->page_no;
        }

        $response = Http::get('https://apps.videoapps.club/common_news/list_articles.php');
        $articles = $response->json();
        $getData = $articles['articles'];
        $getArticles = array_slice($getData, ($page - 1) * $perPage, $perPage);

        $latestArticles = [];
        $blank_image = asset('storage/app/public/news_images/placholder.jpg');

        foreach ($getArticles as $key=>$article) {

            $publishedAt = isset($article['published_at']) ? $article['published_at'] : null;
            $img_url = isset($article['image_url']) ? $article['image_url'] : $blank_image;

            if ($publishedAt && strtotime($publishedAt) && $img_url ) {

                $content = $article['content'];
                if (preg_match('/\./', $content)) {
                    $words = explode(".", $content);
                    $first_sentence = $words[0];
                }else{
                    $words = explode("\r", $content);
                    $first_sentence = $words[0];
                }

                $desc =  $article['description'] ? $article['description'] : 'Data not found';
                $title =  $article['title'] ? $article['title'] : 'Data not found';

                $latestArticles[] = [
                    'title' => $article['title'],
                    'description' => $article['description'],
                    'content' => $first_sentence, 
                    'author' => $article['author'],
                    'url' => $img_url,  // image url
                    'publishedAt' => $todayDate,
                    'detail_url' => $article['url'],
                    'source_name' => $article['source_name'],
                ];
            }
        }

        $last = count($getData) / $perPage;
        $paginationDetails = [
            'total_record' => count($getData),
            'per_page' => (int)$perPage,
            'current_page' => (int)$page,
            'last_page' => ceil($last),
        ];
        
        $responseData['pagination'] = $paginationDetails;
        $responseData['article_data'] = $latestArticles;
        $response = $this->encryptData($responseData);

        return $this->sendResponse($response, 'Data get successfully.');
    }

    public function get_common_setting_old(Request $request)
    {
        $settings = DB::table('common_settings')->get();
        $formattedSettings = [];
        foreach ($settings as $setting) {

            $values = explode(',', $setting->setting_value);
            foreach ($values as $value) {
                $formattedSettings[$setting->setting_key][] = $value;
            }
        }
        $response = $this->encryptData($formattedSettings);

        return $this->sendResponse($response, 'Data get Successfully!');
    }

    // public function validateInAppPurchase(Request $request)
    // {
    //     $purchaseToken = $request->purchase_token;
    //     // // $purchaseToken = "kakibhdenlghbmbdpphekded.AO-J1Ows0u2HxC5a_6_3EG7ICTAFKzVrdb2bWDxf9nXPIgWjiC_z4LrTkyxXj0w0vBsgS1-snc9qLsPk7gS90aT6mLkrZf2PrI5sLRXpXY8pdo7fQ-sGzVOW40xump9ueTG1WEQwC9tL";
    //     // // $purchaseToken = "jioghhhlhjnnglbcacigfmpp.AO-J1Ow55tGU8iYvB6v2u73EqzoAXp8bk7LIGYjP1Iuc4yloR3aoepvQk5MWIPSH_fWcLFx8EoXwmLKPPq5R4BFx6bB9E-5mVBlG_hPDQLrOgHkbhQedZ3Cf9MTvK5oqkdK48jINnfSC";
    //     // $purchaseToken = "dfliagnopipmicoogcmhiipb.AO-J1OxiKEOXjnFYzSZ4X6rvJF-GlDbRF7oemZ8q8zvx8rFrFTOnQyLtlsAkyAhzcKYp8zsmEahOMLwllQ_kLMLNbZzK-77WKrLcsuKi_t5kS1woQ5DMAZqcN7kMBORBaJ3zfxulX6uN";

    //     $isVerified = $this->verifyWithGooglePlay($purchaseToken);
    //     if ($isVerified) {
    //         return response()->json(['status' => 'success']);
    //     } else {
    //         return response()->json(['status' => 'error', 'message' => 'Invalid purchase'], 400);
    //     }
    // }

    // private function verifyWithGooglePlay($purchaseToken)
    // {
    //     $packageName = PACKAGE_NAME;
    //     $productId = PRODUCT_ID;
    //     $apiKey = PURCHASE_KEY;
    //     $response = Http::get("https://www.googleapis.com/androidpublisher/v3/applications/$packageName/purchases/products/$productId/tokens/$purchaseToken");
    //     // $response = Http::get("https://www.googleapis.com/androidpublisher/v3/applications/$packageName/purchases/products/$productId/tokens/$purchaseToken?key=$apiKey");
    //     dd($response->json());
    //     if ($response->successful()) {
    //         $data = $response->json();
    //         return isset($data['purchaseState']) && $data['purchaseState'] == 0;
    //     } else {
    //         // \Log::error('Failed to verify purchase with Google Play Developer API: ' . $response->body());
    //         return false;
    //     }
    // }

}


