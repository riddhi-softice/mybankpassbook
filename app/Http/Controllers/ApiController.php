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
        $state_id = trim($request->input('state_id')); // e.g., 2
        if(empty($state_id)){
            return $this->sendError("state id required");
        }

        $data = DB::table('state_wise_bank_holiday')
        ->where(function ($query) use ($state_id) {
            $query->where('states', 'like', $state_id) // exact match
                  ->orWhere('states', 'like', $state_id . ',%') // beginning
                  ->orWhere('states', 'like', '%,' . $state_id) // end
                  ->orWhere('states', 'like', '%,' . $state_id . ',%') // middle
                  ->orWhere('states', 'like', '%, ' . $state_id) // with space after comma
                  ->orWhere('states', 'like', '%, ' . $state_id . ',%') // middle with space
                  ->orWhere('states', 'like', $state_id . ', %') // beginning with space
                  ->orWhere('states', 'like', '%, ' . $state_id . ''); // end with space
        })
        ->select('holiday_id','holiday_reason','date','day')
        ->get();

        $encResponse = $this->encryptData($data);
        return $this->sendResponse($encResponse, 'Data fetched successfully!');
    }

    public function get_bank_holiday_date_Wise(Request $request)
    {
        $state = trim($request->input('state_name')); // e.g., 'Gujarat'
        // $todayDate = date('Y-m-d');
        $todayDate = "2025-02-03"; // For testing
        $formattedDate = date('d F Y', strtotime($todayDate));
        // dd($state,$formattedDate); 

        if(empty($state)){
            return $this->sendError("state name required");
        }

        $data = DB::table('state_wise_bank_holiday')
        ->where('date', $formattedDate)
        ->where(function ($query) use ($state) {
            $query->where('states', 'like', $state) // exact match
                  ->orWhere('states', 'like', $state . ',%') // beginning
                  ->orWhere('states', 'like', '%,' . $state) // end
                  ->orWhere('states', 'like', '%,' . $state . ',%') // middle
                  ->orWhere('states', 'like', '%, ' . $state) // with space after comma
                  ->orWhere('states', 'like', '%, ' . $state . ',%') // middle with space
                  ->orWhere('states', 'like', $state . ', %') // beginning with space
                  ->orWhere('states', 'like', '%, ' . $state . ''); // end with space
        })
        ->first();

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
        return $this->sendResponse($response, 'Data fetched successfully!');
    }

    public function get_state(Request $request)
    {
        // Cache::flush();
        try {
            $cacheKey = 'get_state';
            $data = Cache::remember($cacheKey, 1440, function () {
                return DB::table('states')
                    ->select('id','name as state_name')
                    ->get();
            });
            if ($data->isNotEmpty()) {
                $response = $this->encryptData($data);
                return $this->sendResponse($response, 'Data retrieved successfully.');
            } else {
                return $this->sendError("Oops! Data not found.");
            }
        } catch (\Exception $e) {
            return $this->sendError('An unexpected error occurred: ' . $e->getMessage());
        }
    }

    public function get_bank_holiday_old(Request $request)
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

    # ------------------------------    

    public function holidays()  
    {
        // dublicate date and reson data query
        // SELECT * FROM state_wise_bank_holiday WHERE (date, holiday_reason) IN ( SELECT date, holiday_reason FROM state_wise_bank_holiday GROUP BY date, holiday_reason HAVING COUNT(*) > 1 ) ORDER BY date, holiday_reason;


        //  https://economictimes.indiatimes.com/wealth/bankholidays/state-uttarakhand,month-apr.cms     
        
        // $stateName = 'Andaman & Nicobar';

        // // Insert into `states` table

        /* $holidays = [
            ['date' => '2025-01-11', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 9],
            ['date' => '2025-01-14', 'day' => 'Tuesday', 'holiday_reason' => 'Makara Sankranti', 'state_id' => 9],
            ['date' => '2025-01-25', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 9],
            ['date' => '2025-01-26', 'day' => 'Sunday', 'holiday_reason' => 'Republic Day', 'state_id' => 9],
            ['date' => '2025-02-08', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 9],
            ['date' => '2025-02-22', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 9],
            ['date' => '2025-02-26', 'day' => 'Wednesday', 'holiday_reason' => 'Maha Shivaratri', 'state_id' => 9],
            ['date' => '2025-03-08', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 9],
            ['date' => '2025-03-14', 'day' => 'Friday', 'holiday_reason' => 'Holi', 'state_id' => 9],
            ['date' => '2025-03-22', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 9],
            ['date' => '2025-03-30', 'day' => 'Sunday', 'holiday_reason' => 'Ugadi', 'state_id' => 9],
            ['date' => '2025-03-31', 'day' => 'Monday', 'holiday_reason' => 'Idul Fitr', 'state_id' => 9],
            ['date' => '2025-04-06', 'day' => 'Sunday', 'holiday_reason' => 'Ram Navami', 'state_id' => 9],
            ['date' => '2025-04-10', 'day' => 'Thursday', 'holiday_reason' => 'Mahavir Jayanti', 'state_id' => 9],
            ['date' => '2025-04-12', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 9],
            ['date' => '2025-04-14', 'day' => 'Monday', 'holiday_reason' => 'Dr Ambedkar Jayanti', 'state_id' => 9],
            ['date' => '2025-04-18', 'day' => 'Friday', 'holiday_reason' => 'Good Friday', 'state_id' => 9],
            ['date' => '2025-04-26', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 9],
            ['date' => '2025-04-29', 'day' => 'Tuesday', 'holiday_reason' => 'Maharshi Parasuram Jayanti', 'state_id' => 9],
            ['date' => '2025-05-10', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 9],
            ['date' => '2025-05-24', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 9],
            ['date' => '2025-06-07', 'day' => 'Saturday', 'holiday_reason' => 'Bakrid / Eid al Adha', 'state_id' => 9],
            ['date' => '2025-06-14', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 9],
            ['date' => '2025-06-28', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 9],
            ['date' => '2025-07-06', 'day' => 'Sunday', 'holiday_reason' => 'Muharram', 'state_id' => 9],
            ['date' => '2025-07-12', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 9],
            ['date' => '2025-07-26', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 9],
            ['date' => '2025-08-09', 'day' => 'Saturday', 'holiday_reason' => 'Raksha Bandhan', 'state_id' => 9],
            ['date' => '2025-08-15', 'day' => 'Friday', 'holiday_reason' => 'Independence Day', 'state_id' => 9],
            ['date' => '2025-08-16', 'day' => 'Saturday', 'holiday_reason' => 'Janmashtami', 'state_id' => 9],
            ['date' => '2025-08-16', 'day' => 'Saturday', 'holiday_reason' => 'Parsi New Year', 'state_id' => 9],
            ['date' => '2025-08-23', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 9],
            ['date' => '2025-08-27', 'day' => 'Wednesday', 'holiday_reason' => 'Ganesh Chaturthi', 'state_id' => 9],
            ['date' => '2025-09-05', 'day' => 'Friday', 'holiday_reason' => 'Eid e Milad', 'state_id' => 9],
            ['date' => '2025-09-13', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 9],
            ['date' => '2025-09-27', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 9],
            ['date' => '2025-10-02', 'day' => 'Thursday', 'holiday_reason' => 'Vijaya Dashami', 'state_id' => 9],
            ['date' => '2025-10-02', 'day' => 'Thursday', 'holiday_reason' => 'Gandhi Jayanti', 'state_id' => 9],
            ['date' => '2025-10-11', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 9],
            ['date' => '2025-10-21', 'day' => 'Tuesday', 'holiday_reason' => 'Diwali', 'state_id' => 9],
            ['date' => '2025-10-22', 'day' => 'Wednesday', 'holiday_reason' => 'Vikram Samvat New Year', 'state_id' => 9],
            ['date' => '2025-10-23', 'day' => 'Thursday', 'holiday_reason' => 'Bhai Dooj', 'state_id' => 9],
            ['date' => '2025-10-25', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 9],
            ['date' => '2025-10-31', 'day' => 'Friday', 'holiday_reason' => 'Sardar Vallabhbhai Patel Jayanti', 'state_id' => 9],
            ['date' => '2025-11-05', 'day' => 'Wednesday', 'holiday_reason' => 'Guru Nanak Jayanti', 'state_id' => 9],
            ['date' => '2025-11-08', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 9],
            ['date' => '2025-11-22', 'day' => 'Saturday', 'holiday_reason' => 'Fouth Saturday', 'state_id' => 9],
            ['date' => '2025-12-13', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 9],
            ['date' => '2025-12-25', 'day' => 'Thursday', 'holiday_reason' => 'Christmas Day', 'state_id' => 9],
            ['date' => '2025-12-27', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 9],
        ]; */

        /* $holidays = [
            ['date' => '2025-01-11', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 10],
            ['date' => '2025-01-25', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 10],
            ['date' => '2025-01-26', 'day' => 'Sunday', 'holiday_reason' => 'Republic Day', 'state_id' => 10],
            ['date' => '2025-02-08', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 10],
            ['date' => '2025-02-22', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 10],
            ['date' => '2025-02-26', 'day' => 'Wednesday', 'holiday_reason' => 'Maha Shivaratri', 'state_id' => 10],
            ['date' => '2025-03-08', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 10],
            ['date' => '2025-03-14', 'day' => 'Friday', 'holiday_reason' => 'Holi', 'state_id' => 10],
            ['date' => '2025-03-22', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 10],
            ['date' => '2025-03-31', 'day' => 'Monday', 'holiday_reason' => 'Id-ul Fitr', 'state_id' => 10],
            ['date' => '2025-04-06', 'day' => 'Sunday', 'holiday_reason' => 'Ram Navami', 'state_id' => 10],
            ['date' => '2025-04-10', 'day' => 'Thursday', 'holiday_reason' => 'Mahavir Jayanti', 'state_id' => 10],
            ['date' => '2025-04-12', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 10],
            ['date' => '2025-04-18', 'day' => 'Friday', 'holiday_reason' => 'Good Friday', 'state_id' => 10],
            ['date' => '2025-04-26', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 10],
            ['date' => '2025-05-10', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 10],
            ['date' => '2025-05-12', 'day' => 'Monday', 'holiday_reason' => 'Buddha Purnima', 'state_id' => 10],
            ['date' => '2025-05-24', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 10],
            ['date' => '2025-06-07', 'day' => 'Saturday', 'holiday_reason' => 'Bakrid / Eid al Adha', 'state_id' => 10],
            ['date' => '2025-06-14', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 10],
            ['date' => '2025-06-28', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 10],
            ['date' => '2025-07-06', 'day' => 'Sunday', 'holiday_reason' => 'Muharram', 'state_id' => 10],
            ['date' => '2025-07-12', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 10],
            ['date' => '2025-07-26', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 10],
            ['date' => '2025-08-09', 'day' => 'Saturday', 'holiday_reason' => 'Raksha Bandhan', 'state_id' => 10],
            ['date' => '2025-08-15', 'day' => 'Friday', 'holiday_reason' => 'Independence Day', 'state_id' => 10],
            ['date' => '2025-08-16', 'day' => 'Saturday', 'holiday_reason' => 'Janmashtami', 'state_id' => 10],
            ['date' => '2025-08-23', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 10],
            ['date' => '2025-09-05', 'day' => 'Friday', 'holiday_reason' => 'Eid e Milad', 'state_id' => 10],
            ['date' => '2025-09-13', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 10],
            ['date' => '2025-09-27', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 10],
            ['date' => '2025-10-02', 'day' => 'Thursday', 'holiday_reason' => 'Vijaya Dashami', 'state_id' => 10],
            ['date' => '2025-10-02', 'day' => 'Thursday', 'holiday_reason' => 'Gandhi Jayanti', 'state_id' => 10],
            ['date' => '2025-10-11', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 10],
            ['date' => '2025-10-21', 'day' => 'Tuesday', 'holiday_reason' => 'Diwali', 'state_id' => 10],
            ['date' => '2025-10-22', 'day' => 'Wednesday', 'holiday_reason' => 'Vikram Samvat New Year', 'state_id' => 10],
            ['date' => '2025-10-23', 'day' => 'Thursday', 'holiday_reason' => 'Bhai Dooj', 'state_id' => 10],
            ['date' => '2025-10-25', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 10],
            ['date' => '2025-11-05', 'day' => 'Wednesday', 'holiday_reason' => 'Guru Nanak Jayanti', 'state_id' => 10],
            ['date' => '2025-11-08', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 10],
            ['date' => '2025-11-22', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 10],
            ['date' => '2025-12-13', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 10],
            ['date' => '2025-12-25', 'day' => 'Thursday', 'holiday_reason' => 'Christmas Day', 'state_id' => 10],
            ['date' => '2025-12-27', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 10],
        ]; */

        /* $holidays = [
            ['date' => '2025-01-11', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 11],
            ['date' => '2025-01-25', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 11],
            ['date' => '2025-01-26', 'day' => 'Sunday', 'holiday_reason' => 'Republic Day', 'state_id' => 11],
            ['date' => '2025-02-08', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 11],
            ['date' => '2025-02-22', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 11],
            ['date' => '2025-02-26', 'day' => 'Wednesday', 'holiday_reason' => 'Maha Shivaratri', 'state_id' => 11],
            ['date' => '2025-03-08', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 11],
            ['date' => '2025-03-14', 'day' => 'Friday', 'holiday_reason' => 'Holi', 'state_id' => 11],
            ['date' => '2025-03-22', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 11],
            ['date' => '2025-03-31', 'day' => 'Monday', 'holiday_reason' => 'Idul Fitr', 'state_id' => 11],
            ['date' => '2025-04-12', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 11],
            ['date' => '2025-04-14', 'day' => 'Monday', 'holiday_reason' => 'Dr Ambedkar Jayanti', 'state_id' => 11],
            ['date' => '2025-04-18', 'day' => 'Friday', 'holiday_reason' => 'Good Friday', 'state_id' => 11],
            ['date' => '2025-04-26', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 11],
            ['date' => '2025-05-01', 'day' => 'Thursday', 'holiday_reason' => 'May Day', 'state_id' => 11],
            ['date' => '2025-05-10', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 11],
            ['date' => '2025-05-24', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 11],
            ['date' => '2025-06-14', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 11],
            ['date' => '2025-06-17', 'day' => 'Monday', 'holiday_reason' => 'Bakrid / Eid al Adha', 'state_id' => 11],
            ['date' => '2025-06-28', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 11],
            ['date' => '2025-07-12', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 11],
            ['date' => '2025-07-26', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 11],
            ['date' => '2025-08-09', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 11],
            ['date' => '2025-08-15', 'day' => 'Friday', 'holiday_reason' => 'Independence Day', 'state_id' => 11],
            ['date' => '2025-08-16', 'day' => 'Saturday', 'holiday_reason' => 'Janmashtami', 'state_id' => 11],
            ['date' => '2025-08-23', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 11],
            ['date' => '2025-08-27', 'day' => 'Wednesday', 'holiday_reason' => 'Ganesh Chaturthi', 'state_id' => 11],
            ['date' => '2025-08-28', 'day' => 'Thursday', 'holiday_reason' => 'Ganesh Chaturthi Holiday', 'state_id' => 11],
            ['date' => '2025-09-13', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 11],
            ['date' => '2025-09-27', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 11],
            ['date' => '2025-10-02', 'day' => 'Thursday', 'holiday_reason' => 'Gandhi Jayanti', 'state_id' => 11],
            ['date' => '2025-10-11', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 11],
            ['date' => '2025-10-21', 'day' => 'Tuesday', 'holiday_reason' => 'Diwali', 'state_id' => 11],
            ['date' => '2025-10-25', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 11],
            ['date' => '2025-11-08', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 11],
            ['date' => '2025-11-22', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 11],
            ['date' => '2025-12-03', 'day' => 'Wednesday', 'holiday_reason' => 'Feast of St Francis Xavier', 'state_id' => 11],
            ['date' => '2025-12-13', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 11],
            ['date' => '2025-12-19', 'day' => 'Thursday', 'holiday_reason' => 'Liberation Day', 'state_id' => 11],
            ['date' => '2025-12-25', 'day' => 'Thursday', 'holiday_reason' => 'Christmas Day', 'state_id' => 11],
            ['date' => '2025-12-27', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 11],
        ]; */

        /* $holidays = [
            ['date' => '2025-01-11', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 12],
            ['date' => '2025-01-14', 'day' => 'Tuesday', 'holiday_reason' => 'Makara Sankranti', 'state_id' => 12],
            ['date' => '2025-01-25', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 12],
            ['date' => '2025-01-26', 'day' => 'Sunday', 'holiday_reason' => 'Republic Day', 'state_id' => 12],
            ['date' => '2025-02-08', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 12],
            ['date' => '2025-02-22', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 12],
            ['date' => '2025-02-26', 'day' => 'Wednesday', 'holiday_reason' => 'Maha Shivaratri', 'state_id' => 12],
            ['date' => '2025-03-08', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 12],
            ['date' => '2025-03-14', 'day' => 'Friday', 'holiday_reason' => 'Holi', 'state_id' => 12],
            ['date' => '2025-03-22', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 12],
            ['date' => '2025-03-30', 'day' => 'Sunday', 'holiday_reason' => 'Ugadi', 'state_id' => 12],
            ['date' => '2025-03-31', 'day' => 'Monday', 'holiday_reason' => 'Idul Fitr', 'state_id' => 12],
            ['date' => '2025-04-06', 'day' => 'Sunday', 'holiday_reason' => 'Ram Navami', 'state_id' => 12],
            ['date' => '2025-04-10', 'day' => 'Thursday', 'holiday_reason' => 'Mahavir Jayanti', 'state_id' => 12],
            ['date' => '2025-04-12', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 12],
            ['date' => '2025-04-14', 'day' => 'Monday', 'holiday_reason' => 'Dr Ambedkar Jayanti', 'state_id' => 12],
            ['date' => '2025-04-18', 'day' => 'Friday', 'holiday_reason' => 'Good Friday', 'state_id' => 12],
            ['date' => '2025-04-26', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 12],
            ['date' => '2025-04-29', 'day' => 'Tuesday', 'holiday_reason' => 'Maharshi Parasuram Jayanti', 'state_id' => 12],
            ['date' => '2025-05-10', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 12],
            ['date' => '2025-05-24', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 12],
            ['date' => '2025-06-07', 'day' => 'Saturday', 'holiday_reason' => 'Bakrid / Eid al Adha', 'state_id' => 12],
            ['date' => '2025-06-14', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 12],
            ['date' => '2025-06-28', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 12],
            ['date' => '2025-07-06', 'day' => 'Sunday', 'holiday_reason' => 'Muharram', 'state_id' => 12],
            ['date' => '2025-07-12', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 12],
            ['date' => '2025-07-26', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 12],
            ['date' => '2025-08-09', 'day' => 'Saturday', 'holiday_reason' => 'Raksha Bandhan', 'state_id' => 12],
            ['date' => '2025-08-15', 'day' => 'Friday', 'holiday_reason' => 'Independence Day', 'state_id' => 12],
            ['date' => '2025-08-16', 'day' => 'Saturday', 'holiday_reason' => 'Janmashtami', 'state_id' => 12],
            ['date' => '2025-08-16', 'day' => 'Saturday', 'holiday_reason' => 'Parsi New Year', 'state_id' => 12],
            ['date' => '2025-08-23', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 12],
            ['date' => '2025-08-27', 'day' => 'Wednesday', 'holiday_reason' => 'Ganesh Chaturthi', 'state_id' => 12],
            ['date' => '2025-09-05', 'day' => 'Friday', 'holiday_reason' => 'Eid e Milad', 'state_id' => 12],
            ['date' => '2025-09-13', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 12],
            ['date' => '2025-09-27', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 12],
            ['date' => '2025-10-02', 'day' => 'Thursday', 'holiday_reason' => 'Vijaya Dashami', 'state_id' => 12],
            ['date' => '2025-10-02', 'day' => 'Thursday', 'holiday_reason' => 'Gandhi Jayanti', 'state_id' => 12],
            ['date' => '2025-10-11', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 12],
            ['date' => '2025-10-21', 'day' => 'Tuesday', 'holiday_reason' => 'Diwali', 'state_id' => 12],
            ['date' => '2025-10-22', 'day' => 'Wednesday', 'holiday_reason' => 'Vikram Samvat New Year', 'state_id' => 12],
            ['date' => '2025-10-23', 'day' => 'Thursday', 'holiday_reason' => 'Bhai Dooj', 'state_id' => 12],
            ['date' => '2025-10-25', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 12],
            ['date' => '2025-10-31', 'day' => 'Friday', 'holiday_reason' => 'Sardar Vallabhbhai Patel Jayanti', 'state_id' => 12],
            ['date' => '2025-11-05', 'day' => 'Wednesday', 'holiday_reason' => 'Guru Nanak Jayanti', 'state_id' => 12],
            ['date' => '2025-11-08', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 12],
            ['date' => '2025-11-22', 'day' => 'Saturday', 'holiday_reason' => 'Fouth Saturday', 'state_id' => 12],
            ['date' => '2025-12-13', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 12],
            ['date' => '2025-12-25', 'day' => 'Thursday', 'holiday_reason' => 'Christmas Day', 'state_id' => 12],
            ['date' => '2025-12-27', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 12],
        ]; */
        
        /*$holidays = [
            ['date' => '2025-01-06', 'day' => 'Monday', 'holiday_reason' => 'Guru Gobind Singh Jayanti', 'state_id' => 13],
            ['date' => '2025-01-11', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 13],
            ['date' => '2025-01-25', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 13],
            ['date' => '2025-01-26', 'day' => 'Sunday', 'holiday_reason' => 'Republic Day', 'state_id' => 13],
            ['date' => '2025-02-03', 'day' => 'Monday', 'holiday_reason' => 'Vasant Panchami', 'state_id' => 13],
            ['date' => '2025-02-08', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 13],
            ['date' => '2025-02-12', 'day' => 'Wednesday', 'holiday_reason' => 'Guru Ravidas Jayanti', 'state_id' => 13],
            ['date' => '2025-02-22', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 13],
            ['date' => '2025-02-26', 'day' => 'Wednesday', 'holiday_reason' => 'Maha Shivaratri', 'state_id' => 13],
            ['date' => '2025-03-08', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 13],
            ['date' => '2025-03-14', 'day' => 'Friday', 'holiday_reason' => 'Holi', 'state_id' => 13],
            ['date' => '2025-03-22', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 13],
            ['date' => '2025-03-23', 'day' => 'Sunday', 'holiday_reason' => "S. Bhagat Singh's Martyrdom Day", 'state_id' => 13],
            ['date' => '2025-03-31', 'day' => 'Monday', 'holiday_reason' => 'Idul Fitr', 'state_id' => 13],
            ['date' => '2025-04-06', 'day' => 'Sunday', 'holiday_reason' => 'Ram Navami', 'state_id' => 13],
            ['date' => '2025-04-10', 'day' => 'Thursday', 'holiday_reason' => 'Mahavir Jayanti', 'state_id' => 13],
            ['date' => '2025-04-12', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 13],
            ['date' => '2025-04-14', 'day' => 'Monday', 'holiday_reason' => 'Dr Ambedkar Jayanti', 'state_id' => 13],
            ['date' => '2025-04-26', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 13],
            ['date' => '2025-04-29', 'day' => 'Tuesday', 'holiday_reason' => 'Maharshi Parasuram Jayanti', 'state_id' => 13],
            ['date' => '2025-05-10', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 13],
            ['date' => '2025-05-24', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 13],
            ['date' => '2025-05-29', 'day' => 'Thursday', 'holiday_reason' => 'Maharana Pratap Jayanti', 'state_id' => 13],
            ['date' => '2025-06-07', 'day' => 'Saturday', 'holiday_reason' => 'Bakrid / Eid al Adha', 'state_id' => 13],
            ['date' => '2025-06-11', 'day' => 'Wednesday', 'holiday_reason' => 'Sant Guru Kabir Jayanti', 'state_id' => 13],
            ['date' => '2025-06-14', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 13],
            ['date' => '2025-06-28', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 13],
            ['date' => '2025-07-12', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 13],
            ['date' => '2025-07-26', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 13],
            ['date' => '2025-07-27', 'day' => 'Sunday', 'holiday_reason' => 'Haryali Teej', 'state_id' => 13],
            ['date' => '2025-07-31', 'day' => 'Thursday', 'holiday_reason' => "Shaheed Udham Singh's Martyrdom Day", 'state_id' => 13],
            ['date' => '2025-08-09', 'day' => 'Saturday', 'holiday_reason' => 'Raksha Bandhan', 'state_id' => 13],
            ['date' => '2025-08-15', 'day' => 'Friday', 'holiday_reason' => 'Independence Day', 'state_id' => 13],
            ['date' => '2025-08-16', 'day' => 'Saturday', 'holiday_reason' => 'Janmashtami', 'state_id' => 13],
            ['date' => '2025-08-23', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 13],
            ['date' => '2025-09-05', 'day' => 'Friday', 'holiday_reason' => 'Eid e Milad', 'state_id' => 13],
            ['date' => '2025-09-13', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 13],
            ['date' => '2025-09-22', 'day' => 'Monday', 'holiday_reason' => 'Maharaja Agrasen Jayanti', 'state_id' => 13],
            ['date' => '2025-09-23', 'day' => 'Tuesday', 'holiday_reason' => "Heroes' Martyrdom Day", 'state_id' => 13],
            ['date' => '2025-09-27', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 13],
            ['date' => '2025-10-02', 'day' => 'Thursday', 'holiday_reason' => 'Vijaya Dashami', 'state_id' => 13],
            ['date' => '2025-10-02', 'day' => 'Thursday', 'holiday_reason' => 'Gandhi Jayanti', 'state_id' => 13],
            ['date' => '2025-10-07', 'day' => 'Tuesday', 'holiday_reason' => 'Maharishi Valmiki Jayanti', 'state_id' => 13],
            ['date' => '2025-10-11', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 13],
            ['date' => '2025-10-21', 'day' => 'Tuesday', 'holiday_reason' => 'Diwali', 'state_id' => 13],
            ['date' => '2025-10-22', 'day' => 'Wednesday', 'holiday_reason' => 'Deepavali Holiday', 'state_id' => 13],
            ['date' => '2025-10-25', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 13],
            ['date' => '2025-11-01', 'day' => 'Saturday', 'holiday_reason' => 'Haryana Day', 'state_id' => 13],
            ['date' => '2025-11-05', 'day' => 'Wednesday', 'holiday_reason' => 'Guru Nanak Jayanti', 'state_id' => 13],
            ['date' => '2025-11-08', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 13],
            ['date' => '2025-11-22', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 13],
            ['date' => '2025-12-13', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 13],
            ['date' => '2025-12-25', 'day' => 'Thursday', 'holiday_reason' => 'Christmas Day', 'state_id' => 13],
            ['date' => '2025-12-26', 'day' => 'Friday', 'holiday_reason' => 'Shaheed Udham Singh Jayanti', 'state_id' => 13],
            ['date' => '2025-12-27', 'day' => 'Saturday', 'holiday_reason' => 'Guru Gobind Singh Jayanti', 'state_id' => 13],
        ]; */

        /* // march saturday issue
        $holidays = [
            ['date' => '2025-01-11', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 14],
            ['date' => '2025-01-25', 'day' => 'Saturday', 'holiday_reason' => 'State Day', 'state_id' => 14],
            ['date' => '2025-01-26', 'day' => 'Sunday', 'holiday_reason' => 'Republic Day', 'state_id' => 14],
            ['date' => '2025-02-08', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 14],
            ['date' => '2025-02-12', 'day' => 'Wednesday', 'holiday_reason' => 'Guru Ravidas Jayanti', 'state_id' => 14],
            ['date' => '2025-02-22', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 14],
            ['date' => '2025-02-26', 'day' => 'Wednesday', 'holiday_reason' => 'Maha Shivaratri', 'state_id' => 14],
            ['date' => '2025-03-08', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 14],
            ['date' => '2025-03-14', 'day' => 'Friday', 'holiday_reason' => 'Holi', 'state_id' => 14],
            ['date' => '2025-03-22', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 14],
            ['date' => '2025-03-31', 'day' => 'Monday', 'holiday_reason' => 'Idul Fitr', 'state_id' => 14],
            ['date' => '2025-04-06', 'day' => 'Sunday', 'holiday_reason' => 'Ram Navami', 'state_id' => 14],
            ['date' => '2025-04-12', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 14],
            ['date' => '2025-04-14', 'day' => 'Monday', 'holiday_reason' => 'Dr Ambedkar Jayanti', 'state_id' => 14],
            ['date' => '2025-04-15', 'day' => 'Tuesday', 'holiday_reason' => 'Himachal Day', 'state_id' => 14],
            ['date' => '2025-04-18', 'day' => 'Friday', 'holiday_reason' => 'Good Friday', 'state_id' => 14],
            ['date' => '2025-04-26', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 14],
            ['date' => '2025-04-29', 'day' => 'Tuesday', 'holiday_reason' => 'Maharshi Parasuram Jayanti', 'state_id' => 14],
            ['date' => '2025-05-10', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 14],
            ['date' => '2025-05-12', 'day' => 'Monday', 'holiday_reason' => 'Buddha Purnima', 'state_id' => 14],
            ['date' => '2025-05-24', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 14],
            ['date' => '2025-05-29', 'day' => 'Thursday', 'holiday_reason' => 'Maharana Pratap Jayanti', 'state_id' => 14],
            ['date' => '2025-06-07', 'day' => 'Saturday', 'holiday_reason' => 'Bakrid / Eid al Adha', 'state_id' => 14],
            ['date' => '2025-06-11', 'day' => 'Wednesday', 'holiday_reason' => 'Sant Guru Kabir Jayanti', 'state_id' => 14],
            ['date' => '2025-06-14', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 14],
            ['date' => '2025-06-28', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 14],
            ['date' => '2025-07-06', 'day' => 'Sunday', 'holiday_reason' => 'Muharram', 'state_id' => 14],
            ['date' => '2025-07-12', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 14],
            ['date' => '2025-07-26', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 14],
            ['date' => '2025-08-09', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 14],
            ['date' => '2025-08-15', 'day' => 'Friday', 'holiday_reason' => 'Independence Day', 'state_id' => 14],
            ['date' => '2025-08-16', 'day' => 'Saturday', 'holiday_reason' => 'Janmashtami', 'state_id' => 14],
            ['date' => '2025-08-23', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 14],
            ['date' => '2025-09-13', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 14],
            ['date' => '2025-09-27', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 14],
            ['date' => '2025-10-02', 'day' => 'Thursday', 'holiday_reason' => 'Vijaya Dashami', 'state_id' => 14],
            ['date' => '2025-10-02', 'day' => 'Thursday', 'holiday_reason' => 'Gandhi Jayanti', 'state_id' => 14],
            ['date' => '2025-10-07', 'day' => 'Tuesday', 'holiday_reason' => 'Maharishi Valmiki Jayanti', 'state_id' => 14],
            ['date' => '2025-10-11', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 14],
            ['date' => '2025-10-21', 'day' => 'Tuesday', 'holiday_reason' => 'Diwali', 'state_id' => 14],
            ['date' => '2025-10-25', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 14],
            ['date' => '2025-11-05', 'day' => 'Wednesday', 'holiday_reason' => 'Guru Nanak Jayanti', 'state_id' => 14],
            ['date' => '2025-11-08', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 14],
            ['date' => '2025-11-22', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 14],
            ['date' => '2025-12-13', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 14],
            ['date' => '2025-12-25', 'day' => 'Thursday', 'holiday_reason' => 'Christmas Day', 'state_id' => 14],
            ['date' => '2025-12-27', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 14],
        ]; */
        
        /* $holidays = [
            ['date' => '2025-01-06', 'day' => 'Monday', 'holiday_reason' => 'Guru Gobind Singh Jayanti', 'state_id' => 15],
            ['date' => '2025-01-11', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 15],
            ['date' => '2025-01-25', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 15],
            ['date' => '2025-01-26', 'day' => 'Sunday', 'holiday_reason' => 'Republic Day', 'state_id' => 15],
            ['date' => '2025-02-08', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 15],
            ['date' => '2025-02-22', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 15],
            ['date' => '2025-02-26', 'day' => 'Wednesday', 'holiday_reason' => 'Maha Shivratri', 'state_id' => 15],
            ['date' => '2025-03-08', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 15],
            ['date' => '2025-03-14', 'day' => 'Friday', 'holiday_reason' => 'Holi', 'state_id' => 15],
            ['date' => '2025-03-22', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 15],
            ['date' => '2025-03-28', 'day' => 'Friday', 'holiday_reason' => 'Jumat-ul-Wida', 'state_id' => 15],
            ['date' => '2025-03-28', 'day' => 'Friday', 'holiday_reason' => 'Shab-I-Qadr', 'state_id' => 15],
            ['date' => '2025-03-30', 'day' => 'Sunday', 'holiday_reason' => 'Ugadi', 'state_id' => 15],
            ['date' => '2025-03-31', 'day' => 'Monday', 'holiday_reason' => 'Id-ul-Fitr', 'state_id' => 15],
            ['date' => '2025-04-06', 'day' => 'Sunday', 'holiday_reason' => 'Ram Navami', 'state_id' => 15],
            ['date' => '2025-04-12', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 15],
            ['date' => '2025-04-13', 'day' => 'Sunday', 'holiday_reason' => 'Vaisakh', 'state_id' => 15],
            ['date' => '2025-04-14', 'day' => 'Monday', 'holiday_reason' => 'Dr. Ambedkar Jayanti', 'state_id' => 15],
            ['date' => '2025-04-26', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 15],
            ['date' => '2025-05-10', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 15],
            ['date' => '2025-05-12', 'day' => 'Monday', 'holiday_reason' => 'Buddha Purnima', 'state_id' => 15],
            ['date' => '2025-05-25', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 15],
            ['date' => '2025-06-07', 'day' => 'Saturday', 'holiday_reason' => 'Bakrid / Eid al Adha', 'state_id' => 15],
            ['date' => '2025-06-08', 'day' => 'Sunday', 'holiday_reason' => 'Bakrid / Eid al Adha Holiday', 'state_id' => 15],
            ['date' => '2025-06-12', 'day' => 'Thursday', 'holiday_reason' => 'Guru Hargobind Ji\'s Birthday', 'state_id' => 15],
            ['date' => '2025-06-14', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 15],
            ['date' => '2025-06-28', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 15],
            ['date' => '2025-07-06', 'day' => 'Sunday', 'holiday_reason' => 'Muharram', 'state_id' => 15],
            ['date' => '2025-07-12', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 15],
            ['date' => '2025-07-26', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 15],
            ['date' => '2025-08-09', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 15],
            ['date' => '2025-08-15', 'day' => 'Friday', 'holiday_reason' => 'Independence Day', 'state_id' => 15],
            ['date' => '2025-08-16', 'day' => 'Saturday', 'holiday_reason' => 'Janamashtami', 'state_id' => 15],
            ['date' => '2025-08-26', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 15],
            ['date' => '2025-09-05', 'day' => 'Friday', 'holiday_reason' => 'Eid-a-Milad', 'state_id' => 15],
            ['date' => '2025-09-12', 'day' => 'Friday', 'holiday_reason' => 'Friday following Eid-a-Milad', 'state_id' => 15],
            ['date' => '2025-09-13', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 15],
            ['date' => '2025-09-27', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 15],
            ['date' => '2025-10-02', 'day' => 'Thursday', 'holiday_reason' => 'Mahatma Gandhi Jayanti', 'state_id' => 15],
            ['date' => '2025-10-02', 'day' => 'Thursday', 'holiday_reason' => 'Vijaya Dashmi', 'state_id' => 15],
            ['date' => '2025-10-11', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 15],
            ['date' => '2025-10-21', 'day' => 'Tuesday', 'holiday_reason' => 'Diwali', 'state_id' => 15],
            ['date' => '2025-10-25', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 15],
            ['date' => '2025-11-05', 'day' => 'Wednesday', 'holiday_reason' => 'Guru Nanak Jayanti', 'state_id' => 15],
            ['date' => '2025-11-08', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 15],
            ['date' => '2025-11-22', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 15],
            ['date' => '2025-12-05', 'day' => 'Friday', 'holiday_reason' => 'Sheikh Muhammad Abdullah Jayanti', 'state_id' => 15],
            ['date' => '2025-12-13', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 15],
            ['date' => '2025-12-25', 'day' => 'Thursday', 'holiday_reason' => 'Christmas', 'state_id' => 15],
            ['date' => '2025-12-27', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 15],
        ]; */

        /* $holidays = [
            ['date' => '2025-01-11', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 16],
            ['date' => '2025-01-23', 'day' => 'Thursday', 'holiday_reason' => 'Netaji Subhas Chandra Bose Jayanti', 'state_id' => 16],
            ['date' => '2025-01-25', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 16],
            ['date' => '2025-01-26', 'day' => 'Sunday', 'holiday_reason' => 'Republic Day', 'state_id' => 16],
            ['date' => '2025-02-08', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 16],
            ['date' => '2025-02-22', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 16],
            ['date' => '2025-02-26', 'day' => 'Wednesday', 'holiday_reason' => 'Maha Shivratri', 'state_id' => 16],
            ['date' => '2025-03-08', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 16],
            ['date' => '2025-03-14', 'day' => 'Friday', 'holiday_reason' => 'Holi', 'state_id' => 16],
            ['date' => '2025-03-22', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 16],
            ['date' => '2025-03-31', 'day' => 'Monday', 'holiday_reason' => 'Id-ul-Fitr', 'state_id' => 16],
            ['date' => '2025-04-01', 'day' => 'Tuesday', 'holiday_reason' => 'Sarhul', 'state_id' => 16],
            ['date' => '2025-04-10', 'day' => 'Thursday', 'holiday_reason' => 'Mahavir Jayanti', 'state_id' => 16],
            ['date' => '2025-04-12', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 16],
            ['date' => '2025-04-14', 'day' => 'Monday', 'holiday_reason' => 'Dr. Ambedkar Jayanti', 'state_id' => 16],
            ['date' => '2025-04-18', 'day' => 'Friday', 'holiday_reason' => 'Good Friday', 'state_id' => 16],
            ['date' => '2025-04-26', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 16],
            ['date' => '2025-05-10', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 16],
            ['date' => '2025-05-12', 'day' => 'Thursday', 'holiday_reason' => 'Buddha Purnima', 'state_id' => 16],
            ['date' => '2025-05-25', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 16],
            ['date' => '2025-06-07', 'day' => 'Saturday', 'holiday_reason' => 'Bakrid / Eid al Adha', 'state_id' => 16],
            ['date' => '2025-06-14', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 16],
            ['date' => '2025-06-28', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 16],
            ['date' => '2025-07-06', 'day' => 'Sunday', 'holiday_reason' => 'Muharram', 'state_id' => 16],
            ['date' => '2025-07-12', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 16],
            ['date' => '2025-07-26', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 16],
            ['date' => '2025-08-09', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 16],
            ['date' => '2025-08-15', 'day' => 'Friday', 'holiday_reason' => 'Independence Day', 'state_id' => 16],
            ['date' => '2025-08-16', 'day' => 'Saturday', 'holiday_reason' => 'Janamashtami', 'state_id' => 16],
            ['date' => '2025-08-26', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 16],
            ['date' => '2025-09-05', 'day' => 'Friday', 'holiday_reason' => 'Eid-a-Milad', 'state_id' => 16],
            ['date' => '2025-09-13', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 16],
            ['date' => '2025-09-27', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 16],
            ['date' => '2025-09-30', 'day' => 'Tuesday', 'holiday_reason' => 'Maha Ashtami', 'state_id' => 16],
            ['date' => '2025-10-01', 'day' => 'Wednesday', 'holiday_reason' => 'Maha Navami', 'state_id' => 16],
            ['date' => '2025-10-02', 'day' => 'Thursday', 'holiday_reason' => 'Mahatma Gandhi Jayanti', 'state_id' => 16],
            ['date' => '2025-10-02', 'day' => 'Thursday', 'holiday_reason' => 'Vijaya Dashmi', 'state_id' => 16],
            ['date' => '2025-10-11', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 16],
            ['date' => '2025-10-21', 'day' => 'Tuesday', 'holiday_reason' => 'Diwali', 'state_id' => 16],
            ['date' => '2025-10-25', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 16],
            ['date' => '2025-10-27', 'day' => 'Monday', 'holiday_reason' => 'Chhath Puja', 'state_id' => 16],
            ['date' => '2025-11-05', 'day' => 'Wednesday', 'holiday_reason' => 'Guru Nanak Jayanti', 'state_id' => 16],
            ['date' => '2025-11-08', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 16],
            ['date' => '2025-11-15', 'day' => 'Saturday', 'holiday_reason' => 'Jharkhand Formation Day', 'state_id' => 16],
            ['date' => '2025-11-22', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 16],
            ['date' => '2025-12-13', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 16],
            ['date' => '2025-12-25', 'day' => 'Thursday', 'holiday_reason' => 'Christmas', 'state_id' => 16],
            ['date' => '2025-12-27', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 16],
        ]; */

        /* $holidays = [
            ['date' => '2025-01-11', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 17],
            ['date' => '2025-01-14', 'day' => 'Tuesday', 'holiday_reason' => 'Makara Sankranti', 'state_id' => 17],
            ['date' => '2025-01-25', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 17],
            ['date' => '2025-01-26', 'day' => 'Sunday', 'holiday_reason' => 'Republic Day', 'state_id' => 17],
            ['date' => '2025-02-08', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 17],
            ['date' => '2025-02-22', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 17],
            ['date' => '2025-02-26', 'day' => 'Wednesday', 'holiday_reason' => 'Maha Sivaratri', 'state_id' => 17],
            ['date' => '2025-03-08', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 17],
            ['date' => '2025-03-22', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 17],
            ['date' => '2025-03-30', 'day' => 'Sunday', 'holiday_reason' => 'Ugadi', 'state_id' => 17],
            ['date' => '2025-03-31', 'day' => 'Monday', 'holiday_reason' => 'Idul Fitr', 'state_id' => 17],
            ['date' => '2025-04-10', 'day' => 'Thursday', 'holiday_reason' => 'Mahavir Jayanthi', 'state_id' => 17],
            ['date' => '2025-04-12', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 17],
            ['date' => '2025-04-14', 'day' => 'Monday', 'holiday_reason' => 'Dr Ambedkar Jayanti', 'state_id' => 17],
            ['date' => '2025-04-18', 'day' => 'Friday', 'holiday_reason' => 'Good Friday', 'state_id' => 17],
            ['date' => '2025-04-26', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 17],
            ['date' => '2025-04-30', 'day' => 'Wednesday', 'holiday_reason' => 'Basava Jayanti', 'state_id' => 17],
            ['date' => '2025-05-01', 'day' => 'Thursday', 'holiday_reason' => 'May Day', 'state_id' => 17],
            ['date' => '2025-05-10', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 17],
            ['date' => '2025-05-24', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 17],
            ['date' => '2025-06-07', 'day' => 'Saturday', 'holiday_reason' => 'Bakrid / Eid al Adha', 'state_id' => 17],
            ['date' => '2025-06-14', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 17],
            ['date' => '2025-06-28', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 17],
            ['date' => '2025-07-06', 'day' => 'Sunday', 'holiday_reason' => 'Muharram', 'state_id' => 17],
            ['date' => '2025-07-12', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 17],
            ['date' => '2025-07-26', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 17],
            ['date' => '2025-08-09', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 17],
            ['date' => '2025-08-15', 'day' => 'Friday', 'holiday_reason' => 'Independence Day', 'state_id' => 17],
            ['date' => '2025-08-23', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 17],
            ['date' => '2025-08-27', 'day' => 'Wednesday', 'holiday_reason' => 'Ganesh Chaturthi', 'state_id' => 17],
            ['date' => '2025-09-05', 'day' => 'Friday', 'holiday_reason' => 'Eid e Milad', 'state_id' => 17],
            ['date' => '2025-09-13', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 17],
            ['date' => '2025-09-21', 'day' => 'Sunday', 'holiday_reason' => 'Mahalaya Amavasye', 'state_id' => 17],
            ['date' => '2025-09-27', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 17],
            ['date' => '2025-10-01', 'day' => 'Wednesday', 'holiday_reason' => 'Maha Navami', 'state_id' => 17],
            ['date' => '2025-10-02', 'day' => 'Thursday', 'holiday_reason' => 'Vijaya Dashami', 'state_id' => 17],
            ['date' => '2025-10-02', 'day' => 'Thursday', 'holiday_reason' => 'Mahatma Gandhi Jayanti', 'state_id' => 17],
            ['date' => '2025-10-07', 'day' => 'Tuesday', 'holiday_reason' => 'Maharishi Valmiki Jayanti', 'state_id' => 17],
            ['date' => '2025-10-11', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 17],
            ['date' => '2025-10-20', 'day' => 'Monday', 'holiday_reason' => 'Diwali', 'state_id' => 17],
            ['date' => '2025-10-21', 'day' => 'Tuesday', 'holiday_reason' => 'Diwali', 'state_id' => 17],
            ['date' => '2025-10-25', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 17],
            ['date' => '2025-11-01', 'day' => 'Saturday', 'holiday_reason' => 'Kannada Rajyotsava', 'state_id' => 17],
            ['date' => '2025-11-08', 'day' => 'Saturday', 'holiday_reason' => 'Kanakadasa Jayanti', 'state_id' => 17],
            ['date' => '2025-11-08', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 17],
            ['date' => '2025-11-22', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 17],
            ['date' => '2025-12-13', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 17],
            ['date' => '2025-12-25', 'day' => 'Thursday', 'holiday_reason' => 'Christmas Day', 'state_id' => 17],
            ['date' => '2025-12-27', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 17],
        ]; */
        
        /* $holidays = [
            ['date' => '2025-01-01', 'day' => 'Wednesday', 'holiday_reason' => "New Year's Day", 'state_id' => 18],
            ['date' => '2025-01-02', 'day' => 'Thursday', 'holiday_reason' => 'Mannam Jayanti', 'state_id' => 18],
            ['date' => '2025-01-11', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 18],
            ['date' => '2025-01-14', 'day' => 'Tuesday', 'holiday_reason' => 'Pongal', 'state_id' => 18],
            ['date' => '2025-01-25', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 18],
            ['date' => '2025-01-26', 'day' => 'Sunday', 'holiday_reason' => 'Republic Day', 'state_id' => 18],
            ['date' => '2025-02-08', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 18],
            ['date' => '2025-02-22', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 18],
            ['date' => '2025-02-26', 'day' => 'Wednesday', 'holiday_reason' => 'Maha Shivaratri', 'state_id' => 18],
            ['date' => '2025-03-08', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 18],
            ['date' => '2025-03-22', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 18],
            ['date' => '2025-03-31', 'day' => 'Monday', 'holiday_reason' => 'Idul Fitr', 'state_id' => 18],
            ['date' => '2025-04-12', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 18],
            ['date' => '2025-04-14', 'day' => 'Monday', 'holiday_reason' => 'Vishu', 'state_id' => 18],
            ['date' => '2025-04-18', 'day' => 'Friday', 'holiday_reason' => 'Good Friday', 'state_id' => 18],
            ['date' => '2025-04-20', 'day' => 'Sunday', 'holiday_reason' => 'Easter Sunday', 'state_id' => 18],
            ['date' => '2025-04-26', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 18],
            ['date' => '2025-05-01', 'day' => 'Thursday', 'holiday_reason' => 'May Day', 'state_id' => 18],
            ['date' => '2025-05-10', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 18],
            ['date' => '2025-05-24', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday', 'state_id' => 18],
            ['date' => '2025-06-07', 'day' => 'Saturday', 'holiday_reason' => 'Idul Adha', 'state_id' => 18],
            ['date' => '2025-06-14', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 18],
            ['date' => '2025-06-28', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 18],
            ['date' => '2025-07-12', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 18],
            ['date' => '2025-07-26', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 18],
            ['date' => '2025-08-15', 'day' => 'Friday', 'holiday_reason' => 'Independence Day', 'state_id' => 18],
            ['date' => '2025-08-28', 'day' => 'Thursday', 'holiday_reason' => 'Ayyankali Jayanti', 'state_id' => 18],
            ['date' => '2025-09-08', 'day' => 'Monday', 'holiday_reason' => 'Onam', 'state_id' => 18],
            ['date' => '2025-09-09', 'day' => 'Tuesday', 'holiday_reason' => 'Thiruvonam', 'state_id' => 18],
            ['date' => '2025-09-10', 'day' => 'Wednesday', 'holiday_reason' => '3rd Onam', 'state_id' => 18],
            ['date' => '2025-09-11', 'day' => 'Thursday', 'holiday_reason' => '4th Onam', 'state_id' => 18],
            ['date' => '2025-09-14', 'day' => 'Sunday', 'holiday_reason' => 'Janmasthami', 'state_id' => 18],
            ['date' => '2025-09-21', 'day' => 'Sunday', 'holiday_reason' => 'Sree Narayana Guru Jayanti', 'state_id' => 18],
            ['date' => '2025-09-25', 'day' => 'Thursday', 'holiday_reason' => 'Mahanavami', 'state_id' => 18],
            ['date' => '2025-09-26', 'day' => 'Friday', 'holiday_reason' => 'Vijaydashami', 'state_id' => 18],
            ['date' => '2025-10-02', 'day' => 'Thursday', 'holiday_reason' => 'Mahatma Gandhi Jayanti', 'state_id' => 18],
            ['date' => '2025-10-10', 'day' => 'Friday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 18], // Double-check: Friday marked as Second Saturday?
            ['date' => '2025-10-25', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 18],
            ['date' => '2025-11-01', 'day' => 'Saturday', 'holiday_reason' => 'Kerala Formation Day', 'state_id' => 18],
            ['date' => '2025-11-08', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 18],
            ['date' => '2025-11-22', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 18],
            ['date' => '2025-12-06', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 18],
            ['date' => '2025-12-25', 'day' => 'Thursday', 'holiday_reason' => 'Christmas Day', 'state_id' => 18],
            ['date' => '2025-12-27', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 18],
        ]; */

        /* $holidays = [
            ['date' => '2025-01-11', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 19],
            ['date' => '2025-01-14', 'day' => 'Tuesday', 'holiday_reason' => "Makar Sankranti, Magh Bihu, Pongal, Hazrat Ali's Birthday", 'state_id' => 19],
            ['date' => '2025-01-25', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 19],
            ['date' => '2025-01-26', 'day' => 'Sunday', 'holiday_reason' => 'Republic Day', 'state_id' => 19],
            ['date' => '2025-02-08', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 19],
            ['date' => '2025-02-22', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 19],
            ['date' => '2025-02-26', 'day' => 'Wednesday', 'holiday_reason' => 'Maha Shivaratri', 'state_id' => 19],
            ['date' => '2025-03-08', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 19],
            ['date' => '2025-03-14', 'day' => 'Friday', 'holiday_reason' => 'Holi', 'state_id' => 19],
            ['date' => '2025-03-22', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 19],
            ['date' => '2025-03-31', 'day' => 'Monday', 'holiday_reason' => 'Idul Fitr', 'state_id' => 19],
            ['date' => '2025-04-10', 'day' => 'Thursday', 'holiday_reason' => 'Mahavir Jayanti', 'state_id' => 19],
            ['date' => '2025-04-12', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 19],
            ['date' => '2025-04-18', 'day' => 'Friday', 'holiday_reason' => 'Good Friday', 'state_id' => 19],
            ['date' => '2025-04-26', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 19],
            ['date' => '2025-05-10', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 19],
            ['date' => '2025-05-12', 'day' => 'Monday', 'holiday_reason' => 'Budha Purnima', 'state_id' => 19],
            ['date' => '2025-05-24', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 19],
            ['date' => '2025-06-14', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 19],
            ['date' => '2025-06-21', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 19],
            ['date' => '2025-07-06', 'day' => 'Sunday', 'holiday_reason' => 'Muharram', 'state_id' => 19],
            ['date' => '2025-07-12', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 19],
            ['date' => '2025-07-26', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 19],
            ['date' => '2025-08-09', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 19],
            ['date' => '2025-08-15', 'day' => 'Friday', 'holiday_reason' => 'Independence Day', 'state_id' => 19],
            ['date' => '2025-08-16', 'day' => 'Saturday', 'holiday_reason' => 'Janmashtami / Parsi New Year', 'state_id' => 19],
            ['date' => '2025-08-23', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 19],
            ['date' => '2025-08-27', 'day' => 'Wednesday', 'holiday_reason' => 'Ganesh Chaturthi', 'state_id' => 19],
            ['date' => '2025-09-05', 'day' => 'Friday', 'holiday_reason' => 'Eid e Milad', 'state_id' => 19],
            ['date' => '2025-09-13', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 19],
            ['date' => '2025-09-27', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 19],
            ['date' => '2025-10-02', 'day' => 'Thursday', 'holiday_reason' => 'Vijaya Dashami / Gandhi Jayanti', 'state_id' => 19],
            ['date' => '2025-10-11', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 19],
            ['date' => '2025-10-20', 'day' => 'Monday', 'holiday_reason' => 'Diwali', 'state_id' => 19],
            ['date' => '2025-10-22', 'day' => 'Wednesday', 'holiday_reason' => 'Govardhan Puja', 'state_id' => 19],
            ['date' => '2025-10-25', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 19],
            ['date' => '2025-11-08', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 19],
            ['date' => '2025-11-15', 'day' => 'Saturday', 'holiday_reason' => 'Guru Nanak Jayanti', 'state_id' => 19],
            ['date' => '2025-11-22', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 19],
            ['date' => '2025-12-13', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 19],
            ['date' => '2025-12-25', 'day' => 'Thursday', 'holiday_reason' => 'Christmas Day', 'state_id' => 19],
            ['date' => '2025-12-27', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 19],
        ]; */      
        
        /* $holidays = [
            ['date' => '2025-01-11', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 20],
            ['date' => '2025-01-25', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 20],
            ['date' => '2025-01-26', 'day' => 'Sunday', 'holiday_reason' => 'Republic Day', 'state_id' => 20],
            ['date' => '2025-02-08', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 20],
            ['date' => '2025-02-22', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 20],
            ['date' => '2025-02-26', 'day' => 'Wednesday', 'holiday_reason' => 'Maha Shivaratri', 'state_id' => 20],
            ['date' => '2025-03-08', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 20],
            ['date' => '2025-03-14', 'day' => 'Friday', 'holiday_reason' => 'Holi', 'state_id' => 20],
            ['date' => '2025-03-22', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 20],
            ['date' => '2025-03-31', 'day' => 'Monday', 'holiday_reason' => 'Idul Fitr', 'state_id' => 20],
            ['date' => '2025-04-10', 'day' => 'Thursday', 'holiday_reason' => 'Mahavir Jayanti', 'state_id' => 20],
            ['date' => '2025-04-12', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 20],
            ['date' => '2025-04-18', 'day' => 'Friday', 'holiday_reason' => 'Good Friday', 'state_id' => 20],
            ['date' => '2025-04-26', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 20],
            ['date' => '2025-05-10', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 20],
            ['date' => '2025-05-12', 'day' => 'Monday', 'holiday_reason' => 'Buddha Purnima', 'state_id' => 20],
            ['date' => '2025-05-24', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 20],
            ['date' => '2025-06-14', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 20],
            ['date' => '2025-06-28', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 20],
            ['date' => '2025-07-06', 'day' => 'Sunday', 'holiday_reason' => 'Muharram', 'state_id' => 20],
            ['date' => '2025-07-12', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 20],
            ['date' => '2025-07-26', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 20],
            ['date' => '2025-08-09', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 20],
            ['date' => '2025-08-15', 'day' => 'Friday', 'holiday_reason' => 'Independence Day', 'state_id' => 20],
            ['date' => '2025-08-16', 'day' => 'Saturday', 'holiday_reason' => 'Janmashtami', 'state_id' => 20],
            ['date' => '2025-08-23', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 20],
            ['date' => '2025-09-13', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 20],
            ['date' => '2025-09-27', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 20],
            ['date' => '2025-10-02', 'day' => 'Thursday', 'holiday_reason' => 'Vijaya Dashami', 'state_id' => 20],
            ['date' => '2025-10-02', 'day' => 'Thursday', 'holiday_reason' => 'Gandhi Jayanti', 'state_id' => 20],
            ['date' => '2025-10-11', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 20],
            ['date' => '2025-10-21', 'day' => 'Tuesday', 'holiday_reason' => 'Diwali', 'state_id' => 20],
            ['date' => '2025-10-25', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 20],
            ['date' => '2025-11-05', 'day' => 'Wednesday', 'holiday_reason' => 'Guru Nanak Jayanti', 'state_id' => 20],
            ['date' => '2025-11-08', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 20],
            ['date' => '2025-11-22', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 20],
            ['date' => '2025-12-13', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 20],
            ['date' => '2025-12-25', 'day' => 'Thursday', 'holiday_reason' => 'Christmas Day', 'state_id' => 20],
            ['date' => '2025-12-27', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 20],
        ]; */

        /* $holidays = [
            ['date' => '2025-01-11', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 21],
            ['date' => '2025-01-25', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 21],
            ['date' => '2025-01-26', 'day' => 'Sunday', 'holiday_reason' => 'Republic Day', 'state_id' => 21],
            ['date' => '2025-02-08', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 21],
            ['date' => '2025-02-19', 'day' => 'Wednesday', 'holiday_reason' => 'Chhatrapati Shivaji Maharaj Jayanti', 'state_id' => 21],
            ['date' => '2025-02-22', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 21],
            ['date' => '2025-02-26', 'day' => 'Wednesday', 'holiday_reason' => 'Maha Shivaratri', 'state_id' => 21],
            ['date' => '2025-03-08', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 21],
            ['date' => '2025-03-14', 'day' => 'Friday', 'holiday_reason' => 'Holi', 'state_id' => 21],
            ['date' => '2025-03-22', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 21],
            ['date' => '2025-03-30', 'day' => 'Sunday', 'holiday_reason' => 'Gudi Padwa', 'state_id' => 21],
            ['date' => '2025-03-31', 'day' => 'Monday', 'holiday_reason' => 'Idul Fitr', 'state_id' => 21],
            ['date' => '2025-04-01', 'day' => 'Tuesday', 'holiday_reason' => 'Annual Accounts Closing (Bank Holiday)', 'state_id' => 21],
            ['date' => '2025-04-06', 'day' => 'Sunday', 'holiday_reason' => 'Ram Navami', 'state_id' => 21],
            ['date' => '2025-04-10', 'day' => 'Thursday', 'holiday_reason' => 'Mahavir Jayanti', 'state_id' => 21],
            ['date' => '2025-04-12', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 21],
            ['date' => '2025-04-14', 'day' => 'Monday', 'holiday_reason' => 'Dr. Ambedkar Jayanti', 'state_id' => 21],
            ['date' => '2025-04-18', 'day' => 'Friday', 'holiday_reason' => 'Good Friday', 'state_id' => 21],
            ['date' => '2025-04-26', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 21],
            ['date' => '2025-05-01', 'day' => 'Thursday', 'holiday_reason' => 'Maharashtra Day', 'state_id' => 21],
            ['date' => '2025-05-10', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 21],
            ['date' => '2025-05-12', 'day' => 'Monday', 'holiday_reason' => 'Buddha Purnima', 'state_id' => 21],
            ['date' => '2025-05-24', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 21],
            ['date' => '2025-06-07', 'day' => 'Saturday', 'holiday_reason' => 'Bakrid / Eid al-Adha', 'state_id' => 21],
            ['date' => '2025-06-14', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 21],
            ['date' => '2025-06-28', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 21],
            ['date' => '2025-07-06', 'day' => 'Sunday', 'holiday_reason' => 'Muharram', 'state_id' => 21],
            ['date' => '2025-07-12', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 21],
            ['date' => '2025-07-26', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 21],
            ['date' => '2025-08-09', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 21],
            ['date' => '2025-08-15', 'day' => 'Friday', 'holiday_reason' => 'Independence Day', 'state_id' => 21],
            ['date' => '2025-08-23', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 21],
            ['date' => '2025-08-27', 'day' => 'Wednesday', 'holiday_reason' => 'Ganesh Chaturthi', 'state_id' => 21],
            ['date' => '2025-09-05', 'day' => 'Friday', 'holiday_reason' => 'Eid e Milad', 'state_id' => 21],
            ['date' => '2025-09-13', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 21],
            ['date' => '2025-09-21', 'day' => 'Sunday', 'holiday_reason' => 'Mahalaya Amavasye', 'state_id' => 21],
            ['date' => '2025-09-27', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 21],
            ['date' => '2025-09-29', 'day' => 'Monday', 'holiday_reason' => 'Maha Saptami', 'state_id' => 21],
            ['date' => '2025-09-30', 'day' => 'Tuesday', 'holiday_reason' => 'Maha Ashtami', 'state_id' => 21],
            ['date' => '2025-10-01', 'day' => 'Wednesday', 'holiday_reason' => 'Maha Navami', 'state_id' => 21],
            ['date' => '2025-10-02', 'day' => 'Thursday', 'holiday_reason' => 'Gandhi Jayanti', 'state_id' => 21],
            ['date' => '2025-10-06', 'day' => 'Monday', 'holiday_reason' => 'Lakshmi Puja', 'state_id' => 21],
            ['date' => '2025-10-11', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 21],
            ['date' => '2025-10-21', 'day' => 'Tuesday', 'holiday_reason' => 'Diwali', 'state_id' => 21],
            ['date' => '2025-10-25', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 21],
            ['date' => '2025-11-05', 'day' => 'Wednesday', 'holiday_reason' => 'Karthika Purnima', 'state_id' => 21],
            ['date' => '2025-11-08', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 21],
            ['date' => '2025-11-22', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 21],
            ['date' => '2025-12-13', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 21],
            ['date' => '2025-12-25', 'day' => 'Thursday', 'holiday_reason' => 'Christmas Day', 'state_id' => 21],
            ['date' => '2025-12-27', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 21],
        ]; */

        /* $holidays = [
            ['date' => '2025-01-01', 'day' => 'Wednesday', 'holiday_reason' => "New Year's Day", 'state_id' => 22],
            ['date' => '2025-01-11', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 22],
            ['date' => '2025-01-12', 'day' => 'Sunday', 'holiday_reason' => 'Gaan-Ngai', 'state_id' => 22],
            ['date' => '2025-01-25', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 22],
            ['date' => '2025-01-26', 'day' => 'Sunday', 'holiday_reason' => 'Republic Day', 'state_id' => 22],
            ['date' => '2025-02-08', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 22],
            ['date' => '2025-02-15', 'day' => 'Saturday', 'holiday_reason' => 'Lui-Ngai-Ni', 'state_id' => 22],
            ['date' => '2025-02-22', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 22],
            ['date' => '2025-03-08', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 22],
            ['date' => '2025-03-14', 'day' => 'Friday', 'holiday_reason' => 'Yaosang', 'state_id' => 22],
            ['date' => '2025-03-15', 'day' => 'Saturday', 'holiday_reason' => 'Yaosang Secondnd Day', 'state_id' => 22],
            ['date' => '2025-03-22', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 22],
            ['date' => '2025-03-31', 'day' => 'Monday', 'holiday_reason' => 'Idul Fitr', 'state_id' => 22],
            ['date' => '2025-04-12', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 22],
            ['date' => '2025-04-14', 'day' => 'Monday', 'holiday_reason' => 'Cheiraoba', 'state_id' => 22],
            ['date' => '2025-04-18', 'day' => 'Friday', 'holiday_reason' => 'Good Friday', 'state_id' => 22],
            ['date' => '2025-04-26', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 22],
            ['date' => '2025-05-01', 'day' => 'Thursday', 'holiday_reason' => 'May Day', 'state_id' => 22],
            ['date' => '2025-05-10', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 22],
            ['date' => '2025-05-24', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 22],
            ['date' => '2025-06-07', 'day' => 'Saturday', 'holiday_reason' => 'Bakrid or Eid al Adha', 'state_id' => 22],
            ['date' => '2025-06-14', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 22],
            ['date' => '2025-06-27', 'day' => 'Friday', 'holiday_reason' => 'Ratha Yathra', 'state_id' => 22],
            ['date' => '2025-06-28', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 22],
            ['date' => '2025-07-12', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 22],
            ['date' => '2025-07-26', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 22],
            ['date' => '2025-08-09', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 22],
            ['date' => '2025-08-13', 'day' => 'Wednesday', 'holiday_reason' => "Patriots Day", 'state_id' => 22],
            ['date' => '2025-08-15', 'day' => 'Friday', 'holiday_reason' => 'Independence Day', 'state_id' => 22],
            ['date' => '2025-08-16', 'day' => 'Saturday', 'holiday_reason' => 'Janmashtami', 'state_id' => 22],
            ['date' => '2025-08-23', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 22],
            ['date' => '2025-09-13', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 22],
            ['date' => '2025-09-27', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 22],
            ['date' => '2025-09-30', 'day' => 'Tuesday', 'holiday_reason' => 'Maha Ashtami', 'state_id' => 22],
            ['date' => '2025-10-02', 'day' => 'Thursday', 'holiday_reason' => 'Gandhi Jayanti', 'state_id' => 22],
            ['date' => '2025-10-11', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 22],
            ['date' => '2025-10-20', 'day' => 'Monday', 'holiday_reason' => 'Diwali', 'state_id' => 22],
            ['date' => '2025-10-24', 'day' => 'Friday', 'holiday_reason' => 'Ningol Chakkouba', 'state_id' => 22],
            ['date' => '2025-10-25', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 22],
            ['date' => '2025-11-01', 'day' => 'Saturday', 'holiday_reason' => 'Kut', 'state_id' => 22],
            ['date' => '2025-11-08', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 22],
            ['date' => '2025-11-22', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 22],
            ['date' => '2025-12-13', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 22],
            ['date' => '2025-12-25', 'day' => 'Thursday', 'holiday_reason' => 'Christmas Day', 'state_id' => 22],
            ['date' => '2025-12-27', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 22],
            ['date' => '2025-12-31', 'day' => 'Wednesday', 'holiday_reason' => "New Year's Eve", 'state_id' => 22],
        ]; */
        
        /* $holidays = [
            ['date' => '2025-01-01', 'day' => 'Wednesday', 'holiday_reason' => "New Year's Day", 'state_id' => 23],
            ['date' => '2025-01-11', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 23],
            ['date' => '2025-01-25', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 23],
            ['date' => '2025-01-26', 'day' => 'Sunday', 'holiday_reason' => 'Republic Day', 'state_id' => 23],
            ['date' => '2025-02-08', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 23],
            ['date' => '2025-02-22', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 23],
            ['date' => '2025-03-08', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 23],
            ['date' => '2025-03-14', 'day' => 'Friday', 'holiday_reason' => 'Holi', 'state_id' => 23],
            ['date' => '2025-03-22', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 23],
            ['date' => '2025-03-31', 'day' => 'Monday', 'holiday_reason' => 'Idul Fitr', 'state_id' => 23],
            ['date' => '2025-04-12', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 23],
            ['date' => '2025-04-18', 'day' => 'Friday', 'holiday_reason' => 'Good Friday', 'state_id' => 23],
            ['date' => '2025-04-26', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 23],
            ['date' => '2025-05-10', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 23],
            ['date' => '2025-05-24', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 23],
            ['date' => '2025-06-07', 'day' => 'Saturday', 'holiday_reason' => 'Bakrid or Eid al Adha', 'state_id' => 23],
            ['date' => '2025-06-14', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 23],
            ['date' => '2025-06-28', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 23],
            ['date' => '2025-07-12', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 23],
            ['date' => '2025-07-14', 'day' => 'Monday', 'holiday_reason' => 'Behdeinkhlam Festival', 'state_id' => 23],
            ['date' => '2025-07-17', 'day' => 'Thursday', 'holiday_reason' => 'U Tirot Sing Day', 'state_id' => 23],
            ['date' => '2025-07-26', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 23],
            ['date' => '2025-08-09', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 23],
            ['date' => '2025-08-15', 'day' => 'Friday', 'holiday_reason' => 'Independence Day', 'state_id' => 23],
            ['date' => '2025-08-16', 'day' => 'Saturday', 'holiday_reason' => 'Janmashtami', 'state_id' => 23],
            ['date' => '2025-08-23', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 23],
            ['date' => '2025-09-13', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 23],
            ['date' => '2025-09-27', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 23],
            ['date' => '2025-10-01', 'day' => 'Wednesday', 'holiday_reason' => 'Maha Navami', 'state_id' => 23],
            ['date' => '2025-10-02', 'day' => 'Thursday', 'holiday_reason' => 'Vijaya Dashami', 'state_id' => 23],
            ['date' => '2025-10-02', 'day' => 'Thursday', 'holiday_reason' => 'Gandhi Jayanti', 'state_id' => 23],
            ['date' => '2025-10-11', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 23],
            ['date' => '2025-10-20', 'day' => 'Monday', 'holiday_reason' => 'Diwali', 'state_id' => 23],
            ['date' => '2025-10-25', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 23],
            ['date' => '2025-11-07', 'day' => 'Friday', 'holiday_reason' => 'Wangala Festival', 'state_id' => 23],
            ['date' => '2025-11-08', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 23],
            ['date' => '2025-11-22', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 23],
            ['date' => '2025-11-23', 'day' => 'Sunday', 'holiday_reason' => 'Seng Kut Snem', 'state_id' => 23],
            ['date' => '2025-12-12', 'day' => 'Friday', 'holiday_reason' => 'Pa Togan Nengminza Sangma', 'state_id' => 23],
            ['date' => '2025-12-13', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 23],
            ['date' => '2025-12-18', 'day' => 'Thursday', 'holiday_reason' => 'Death Anniversary of U SoSo Tham', 'state_id' => 23],
            ['date' => '2025-12-24', 'day' => 'Wednesday', 'holiday_reason' => 'Christmas Holiday', 'state_id' => 23],
            ['date' => '2025-12-25', 'day' => 'Thursday', 'holiday_reason' => 'Christmas Day', 'state_id' => 23],
            ['date' => '2025-12-26', 'day' => 'Friday', 'holiday_reason' => 'Christmas Holiday', 'state_id' => 23],
            ['date' => '2025-12-27', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 23],
            ['date' => '2025-12-30', 'day' => 'Tuesday', 'holiday_reason' => 'U Kiang Nangbah', 'state_id' => 23],
        ]; */

        /* $holidays = [
            ['date' => '2025-01-01', 'day' => 'Wednesday', 'holiday_reason' => "New Year’s Day", 'state_id' => 24],
            ['date' => '2025-01-02', 'day' => 'Thursday', 'holiday_reason' => "New Year Holiday", 'state_id' => 24],
            ['date' => '2025-01-11', 'day' => 'Saturday', 'holiday_reason' => "Missionary Day/Second Saturday Bank Holiday", 'state_id' => 24],
            ['date' => '2025-01-25', 'day' => 'Saturday', 'holiday_reason' => "Fourth Saturday Bank Holiday", 'state_id' => 24],
            ['date' => '2025-01-26', 'day' => 'Sunday', 'holiday_reason' => "Republic Day / Bank holiday", 'state_id' => 24],
            ['date' => '2025-02-08', 'day' => 'Saturday', 'holiday_reason' => "Second Saturday Bank Holiday", 'state_id' => 24],
            ['date' => '2025-02-20', 'day' => 'Thursday', 'holiday_reason' => "Mizoram State Day", 'state_id' => 24],
            ['date' => '2025-02-22', 'day' => 'Saturday', 'holiday_reason' => "Second Saturday Bank Holiday", 'state_id' => 24],
            ['date' => '2025-03-07', 'day' => 'Friday', 'holiday_reason' => "Chapchar Kut", 'state_id' => 24],
            ['date' => '2025-03-08', 'day' => 'Saturday', 'holiday_reason' => "Second Saturday Bank Holiday", 'state_id' => 24],
            ['date' => '2025-03-14', 'day' => 'Friday', 'holiday_reason' => "Holi", 'state_id' => 24],
            ['date' => '2025-03-22', 'day' => 'Saturday', 'holiday_reason' => "Fourth Saturday Bank Holiday", 'state_id' => 24],
            ['date' => '2025-03-31', 'day' => 'Monday', 'holiday_reason' => "Idul Fitr", 'state_id' => 24],
            ['date' => '2025-04-10', 'day' => 'Thursday', 'holiday_reason' => "Mahavir Jayanti", 'state_id' => 24],
            ['date' => '2025-04-12', 'day' => 'Saturday', 'holiday_reason' => "Second Saturday Bank Holiday", 'state_id' => 24],
            ['date' => '2025-04-18', 'day' => 'Friday', 'holiday_reason' => "Good Friday", 'state_id' => 24],
            ['date' => '2025-04-26', 'day' => 'Saturday', 'holiday_reason' => "Fourth Saturday Bank Holiday", 'state_id' => 24],
            ['date' => '2025-05-10', 'day' => 'Saturday', 'holiday_reason' => "Second Saturday Bank Holiday", 'state_id' => 24],
            ['date' => '2025-05-12', 'day' => 'Monday', 'holiday_reason' => "Buddha Purnima", 'state_id' => 24],
            ['date' => '2025-05-24', 'day' => 'Saturday', 'holiday_reason' => "Fourth Saturday Bank Holiday", 'state_id' => 24],
            ['date' => '2025-06-07', 'day' => 'Saturday', 'holiday_reason' => "Bakrid / Eid al Adha", 'state_id' => 24],
            ['date' => '2025-06-14', 'day' => 'Saturday', 'holiday_reason' => "Second Saturday Bank Holiday", 'state_id' => 24],
            ['date' => '2025-06-15', 'day' => 'Sunday', 'holiday_reason' => "YMA Day/ Sunday Bank Holiday", 'state_id' => 24],
            ['date' => '2025-06-28', 'day' => 'Saturday', 'holiday_reason' => "Fourth Saturday Bank Holiday", 'state_id' => 24],
            ['date' => '2025-06-30', 'day' => 'Monday', 'holiday_reason' => "Remna Ni", 'state_id' => 24],
            ['date' => '2025-07-06', 'day' => 'Sunday', 'holiday_reason' => "Muharram", 'state_id' => 24],
            ['date' => '2025-07-12', 'day' => 'Saturday', 'holiday_reason' => "Second Saturday Bank Holiday", 'state_id' => 24],
            ['date' => '2025-07-26', 'day' => 'Saturday', 'holiday_reason' => "Fourth Saturday Bank Holiday", 'state_id' => 24],
            ['date' => '2025-07-27', 'day' => 'Sunday', 'holiday_reason' => "Bank Holiday", 'state_id' => 24],
            ['date' => '2025-08-09', 'day' => 'Saturday', 'holiday_reason' => "Second Saturday Bank Holiday", 'state_id' => 24],
            ['date' => '2025-08-15', 'day' => 'Friday', 'holiday_reason' => "Independence Day", 'state_id' => 24],
            ['date' => '2025-08-23', 'day' => 'Saturday', 'holiday_reason' => "Fourth Saturday Bank Holiday", 'state_id' => 24],
            ['date' => '2025-09-05', 'day' => 'Friday', 'holiday_reason' => "Eid e Milad", 'state_id' => 24],
            ['date' => '2025-09-13', 'day' => 'Saturday', 'holiday_reason' => "Second Saturday Bank Holiday", 'state_id' => 24],
            ['date' => '2025-09-27', 'day' => 'Saturday', 'holiday_reason' => "Fourth Saturday Bank Holiday", 'state_id' => 24],
            ['date' => '2025-09-28', 'day' => 'Sunday', 'holiday_reason' => "Bank Holiday", 'state_id' => 24],
            ['date' => '2025-10-02', 'day' => 'Thursday', 'holiday_reason' => "Gandhi Jayanti", 'state_id' => 24],
            ['date' => '2025-10-11', 'day' => 'Saturday', 'holiday_reason' => "Second Saturday Bank Holiday", 'state_id' => 24],
            ['date' => '2025-10-21', 'day' => 'Tuesday', 'holiday_reason' => "Diwali", 'state_id' => 24],
            ['date' => '2025-10-25', 'day' => 'Saturday', 'holiday_reason' => "Fourth Saturday Bank Holiday", 'state_id' => 24],
            ['date' => '2025-10-26', 'day' => 'Sunday', 'holiday_reason' => "Bank Holiday", 'state_id' => 24],
            ['date' => '2025-11-05', 'day' => 'Wednesday', 'holiday_reason' => "Guru Nanak Jayanti", 'state_id' => 24],
            ['date' => '2025-11-08', 'day' => 'Saturday', 'holiday_reason' => "Second Saturday Bank Holiday", 'state_id' => 24],
            ['date' => '2025-11-22', 'day' => 'Saturday', 'holiday_reason' => "Fourth Saturday Bank Holiday", 'state_id' => 24],
            ['date' => '2025-11-23', 'day' => 'Sunday', 'holiday_reason' => "Bank Holiday", 'state_id' => 24],
            ['date' => '2025-12-13', 'day' => 'Saturday', 'holiday_reason' => "Second Saturday Bank Holiday", 'state_id' => 24],
            ['date' => '2025-12-24', 'day' => 'Wednesday', 'holiday_reason' => "Christmas Holiday", 'state_id' => 24],
            ['date' => '2025-12-25', 'day' => 'Thursday', 'holiday_reason' => "Christmas Day", 'state_id' => 24],
            ['date' => '2025-12-27', 'day' => 'Saturday', 'holiday_reason' => "Fourth Saturday Bank Holiday", 'state_id' => 24],
        ]; */

        /* $holidays = [
            ['date' => '2025-01-01', 'day' => 'Wednesday', 'holiday_reason' => "New Year Day", 'state_id' => 25],
            ['date' => '2025-01-11', 'day' => 'Saturday', 'holiday_reason' => "Second Saturday Bank Holiday", 'state_id' => 25],
            ['date' => '2025-01-12', 'day' => 'Sunday', 'holiday_reason' => "Bank Holiday", 'state_id' => 25],
            ['date' => '2025-01-25', 'day' => 'Saturday', 'holiday_reason' => "Fourth Saturday Bank Holiday", 'state_id' => 25],
            ['date' => '2025-01-26', 'day' => 'Sunday', 'holiday_reason' => "Republic day/ Bank Holiday", 'state_id' => 25],
            ['date' => '2025-02-08', 'day' => 'Saturday', 'holiday_reason' => "Second Saturday Bank Holiday", 'state_id' => 25],
            ['date' => '2025-02-09', 'day' => 'Sunday', 'holiday_reason' => "Bank Holiday", 'state_id' => 25],
            ['date' => '2025-02-22', 'day' => 'Saturday', 'holiday_reason' => "Fourth Saturday Bank Holiday", 'state_id' => 25],
            ['date' => '2025-02-23', 'day' => 'Sunday', 'holiday_reason' => "Bank Holiday", 'state_id' => 25],
            ['date' => '2025-03-08', 'day' => 'Saturday', 'holiday_reason' => "Second Saturday Bank Holiday", 'state_id' => 25],
            ['date' => '2025-03-09', 'day' => 'Sunday', 'holiday_reason' => "Bank Holiday", 'state_id' => 25],
            ['date' => '2025-03-14', 'day' => 'Friday', 'holiday_reason' => "Holi", 'state_id' => 25],
            ['date' => '2025-03-22', 'day' => 'Saturday', 'holiday_reason' => "Fourth Saturday Bank Holiday", 'state_id' => 25],
            ['date' => '2025-03-23', 'day' => 'Sunday', 'holiday_reason' => "Bank Holiday", 'state_id' => 25],
            ['date' => '2025-03-31', 'day' => 'Monday', 'holiday_reason' => "Idul Fitr", 'state_id' => 25],
            ['date' => '2025-04-12', 'day' => 'Saturday', 'holiday_reason' => "Second Saturday Bank Holiday", 'state_id' => 25],
            ['date' => '2025-04-13', 'day' => 'Sunday', 'holiday_reason' => "Bank Holiday", 'state_id' => 25],
            ['date' => '2025-04-18', 'day' => 'Friday', 'holiday_reason' => "Good Friday", 'state_id' => 25],
            ['date' => '2025-04-19', 'day' => 'Saturday', 'holiday_reason' => "Easter Saturday", 'state_id' => 25],
            ['date' => '2025-04-20', 'day' => 'Sunday', 'holiday_reason' => "Easter Sunday", 'state_id' => 25],
            ['date' => '2025-04-26', 'day' => 'Saturday', 'holiday_reason' => "Fourth Saturday Bank Holiday", 'state_id' => 25],
            ['date' => '2025-04-27', 'day' => 'Sunday', 'holiday_reason' => "Bank Holiday", 'state_id' => 25],
            ['date' => '2025-05-10', 'day' => 'Saturday', 'holiday_reason' => "Second Saturday Bank Holiday", 'state_id' => 25],
            ['date' => '2025-05-11', 'day' => 'Sunday', 'holiday_reason' => "Bank Holiday", 'state_id' => 25],
            ['date' => '2025-05-24', 'day' => 'Saturday', 'holiday_reason' => "Fourth Saturday Bank Holiday", 'state_id' => 25],
            ['date' => '2025-05-25', 'day' => 'Sunday', 'holiday_reason' => "Bank Holiday", 'state_id' => 25],
            ['date' => '2025-06-07', 'day' => 'Saturday', 'holiday_reason' => "Bakrid / Eid al Adha", 'state_id' => 25],
            ['date' => '2025-06-14', 'day' => 'Saturday', 'holiday_reason' => "Second Saturday Bank Holiday", 'state_id' => 25],
            ['date' => '2025-06-15', 'day' => 'Sunday', 'holiday_reason' => "Bank Holiday", 'state_id' => 25],
            ['date' => '2025-06-28', 'day' => 'Saturday', 'holiday_reason' => "Fourth Saturday Bank Holiday", 'state_id' => 25],
            ['date' => '2025-06-29', 'day' => 'Sunday', 'holiday_reason' => "Bank Holiday", 'state_id' => 25],
            ['date' => '2025-07-12', 'day' => 'Saturday', 'holiday_reason' => "Second Saturday Bank Holiday", 'state_id' => 25],
            ['date' => '2025-07-13', 'day' => 'Sunday', 'holiday_reason' => "Bank Holiday", 'state_id' => 25],
            ['date' => '2025-07-26', 'day' => 'Saturday', 'holiday_reason' => "Fourth Saturday Bank Holiday", 'state_id' => 25],
            ['date' => '2025-07-27', 'day' => 'Sunday', 'holiday_reason' => "Bank Holiday", 'state_id' => 25],
            ['date' => '2025-08-09', 'day' => 'Saturday', 'holiday_reason' => "Second Saturday Bank Holiday", 'state_id' => 25],
            ['date' => '2025-08-10', 'day' => 'Sunday', 'holiday_reason' => "Bank Holiday", 'state_id' => 25],
            ['date' => '2025-08-15', 'day' => 'Friday', 'holiday_reason' => "Independence Day", 'state_id' => 25],
            ['date' => '2025-08-16', 'day' => 'Saturday', 'holiday_reason' => "Janmashtami", 'state_id' => 25],
            ['date' => '2025-08-23', 'day' => 'Saturday', 'holiday_reason' => "Fourth Saturday Bank Holiday", 'state_id' => 25],
            ['date' => '2025-08-24', 'day' => 'Sunday', 'holiday_reason' => "Bank Holiday", 'state_id' => 25],
            ['date' => '2025-09-05', 'day' => 'Friday', 'holiday_reason' => "Eid e Milad", 'state_id' => 25],
            ['date' => '2025-09-13', 'day' => 'Saturday', 'holiday_reason' => "Second Saturday Bank Holiday", 'state_id' => 25],
            ['date' => '2025-09-14', 'day' => 'Sunday', 'holiday_reason' => "Bank Holiday", 'state_id' => 25],
            ['date' => '2025-09-27', 'day' => 'Saturday', 'holiday_reason' => "Fourth Saturday Bank Holiday", 'state_id' => 25],
            ['date' => '2025-09-28', 'day' => 'Sunday', 'holiday_reason' => "Bank Holiday", 'state_id' => 25],
            ['date' => '2025-10-01', 'day' => 'Wednesday', 'holiday_reason' => "Maha Navami", 'state_id' => 25],
            ['date' => '2025-10-02', 'day' => 'Thursday', 'holiday_reason' => "Gandhi Jayanti", 'state_id' => 25],
            ['date' => '2025-10-11', 'day' => 'Saturday', 'holiday_reason' => "Second Saturday Bank Holiday", 'state_id' => 25],
            ['date' => '2025-10-12', 'day' => 'Sunday', 'holiday_reason' => "Bank Holiday", 'state_id' => 25],
            ['date' => '2025-10-21', 'day' => 'Tuesday', 'holiday_reason' => "Diwali", 'state_id' => 25],
            ['date' => '2025-10-25', 'day' => 'Saturday', 'holiday_reason' => "Fourth Saturday Bank Holiday", 'state_id' => 25],
            ['date' => '2025-10-26', 'day' => 'Sunday', 'holiday_reason' => "Bank Holiday", 'state_id' => 25],
            ['date' => '2025-11-05', 'day' => 'Wednesday', 'holiday_reason' => "Guru Nanak Jayanti", 'state_id' => 25],
            ['date' => '2025-11-08', 'day' => 'Saturday', 'holiday_reason' => "Second Saturday Bank Holiday", 'state_id' => 25],
            ['date' => '2025-11-09', 'day' => 'Sunday', 'holiday_reason' => "Bank Holiday", 'state_id' => 25],
            ['date' => '2025-11-22', 'day' => 'Saturday', 'holiday_reason' => "Fourth Saturday Bank Holiday", 'state_id' => 25],
            ['date' => '2025-11-23', 'day' => 'Sunday', 'holiday_reason' => "Bank Holiday", 'state_id' => 25],
            ['date' => '2025-12-13', 'day' => 'Saturday', 'holiday_reason' => "Second Saturday Bank Holiday", 'state_id' => 25],
            ['date' => '2025-12-14', 'day' => 'Sunday', 'holiday_reason' => "Bank Holiday", 'state_id' => 25],
            ['date' => '2025-12-25', 'day' => 'Thursday', 'holiday_reason' => "Christmas", 'state_id' => 25],
            ['date' => '2025-12-27', 'day' => 'Saturday', 'holiday_reason' => "Fourth Saturday Bank Holiday", 'state_id' => 25],
            ['date' => '2025-12-28', 'day' => 'Sunday', 'holiday_reason' => "Bank Holiday", 'state_id' => 25],
        ]; */

        /* $holidays = [
            ['date' => '2025-01-11', 'day' => 'Saturday', 'holiday_reason' => "Second Saturday Bank Holiday", 'state_id' => 26],
            ['date' => '2025-01-14', 'day' => 'Tuesday', 'holiday_reason' => "Makara Sankranti", 'state_id' => 26],
            ['date' => '2025-01-23', 'day' => 'Thursday', 'holiday_reason' => "Netaji Subhas Chandra Bose Jayanti", 'state_id' => 26],
            ['date' => '2025-01-25', 'day' => 'Saturday', 'holiday_reason' => "Fourth Saturday Bank Holiday", 'state_id' => 26],
            ['date' => '2025-01-26', 'day' => 'Sunday', 'holiday_reason' => "Republic Day", 'state_id' => 26],
            ['date' => '2025-02-03', 'day' => 'Monday', 'holiday_reason' => "Vasant Panchami", 'state_id' => 26],
            ['date' => '2025-02-08', 'day' => 'Saturday', 'holiday_reason' => "Second Saturday Bank Holiday", 'state_id' => 26],
            ['date' => '2025-02-22', 'day' => 'Saturday', 'holiday_reason' => "Fourth Saturday Bank Holiday", 'state_id' => 26],
            ['date' => '2025-02-26', 'day' => 'Wednesday', 'holiday_reason' => "Maha Shivaratri", 'state_id' => 26],
            ['date' => '2025-03-05', 'day' => 'Wednesday', 'holiday_reason' => "Panchayatiraj Divas", 'state_id' => 26],
            ['date' => '2025-03-08', 'day' => 'Saturday', 'holiday_reason' => "Second Saturday Bank Holiday", 'state_id' => 26],
            ['date' => '2025-03-14', 'day' => 'Friday', 'holiday_reason' => "Holi", 'state_id' => 26],
            ['date' => '2025-03-22', 'day' => 'Saturday', 'holiday_reason' => "Fourth Saturday Bank Holiday", 'state_id' => 26],
            ['date' => '2025-03-31', 'day' => 'Monday', 'holiday_reason' => "Idul Fitr", 'state_id' => 26],
            ['date' => '2025-04-01', 'day' => 'Tuesday', 'holiday_reason' => "Odisha Day", 'state_id' => 26],
            ['date' => '2025-04-06', 'day' => 'Sunday', 'holiday_reason' => "Ram Navami", 'state_id' => 26],
            ['date' => '2025-04-12', 'day' => 'Saturday', 'holiday_reason' => "Second Saturday Bank Holiday", 'state_id' => 26],
            ['date' => '2025-04-13', 'day' => 'Sunday', 'holiday_reason' => "Maha Vishuba Sankranti", 'state_id' => 26],
            ['date' => '2025-04-14', 'day' => 'Monday', 'holiday_reason' => "Dr. Ambedkar Jayanti", 'state_id' => 26],
            ['date' => '2025-04-18', 'day' => 'Friday', 'holiday_reason' => "Good Friday", 'state_id' => 26],
            ['date' => '2025-04-26', 'day' => 'Saturday', 'holiday_reason' => "Fourth Saturday Bank Holiday", 'state_id' => 26],
            ['date' => '2025-05-10', 'day' => 'Saturday', 'holiday_reason' => "Second Saturday Bank Holiday", 'state_id' => 26],
            ['date' => '2025-05-24', 'day' => 'Saturday', 'holiday_reason' => "Fourth Saturday Bank Holiday", 'state_id' => 26],
            ['date' => '2025-06-07', 'day' => 'Saturday', 'holiday_reason' => "Bakrid / Eid al Adha", 'state_id' => 26],
            ['date' => '2025-06-14', 'day' => 'Saturday', 'holiday_reason' => "Pahili Raja", 'state_id' => 26],
            ['date' => '2025-06-15', 'day' => 'Sunday', 'holiday_reason' => "Raja Sankranti", 'state_id' => 26],
            ['date' => '2025-06-27', 'day' => 'Friday', 'holiday_reason' => "Ratha Yathra", 'state_id' => 26],
            ['date' => '2025-06-28', 'day' => 'Saturday', 'holiday_reason' => "Fourth Saturday Bank Holiday", 'state_id' => 26],
            ['date' => '2025-07-06', 'day' => 'Sunday', 'holiday_reason' => "Muharram", 'state_id' => 26],
            ['date' => '2025-07-12', 'day' => 'Saturday', 'holiday_reason' => "Second Saturday Bank Holiday", 'state_id' => 26],
            ['date' => '2025-07-26', 'day' => 'Saturday', 'holiday_reason' => "Fourth Saturday Bank Holiday", 'state_id' => 26],
            ['date' => '2025-08-08', 'day' => 'Friday', 'holiday_reason' => "Jhulan Purnima", 'state_id' => 26],
            ['date' => '2025-08-09', 'day' => 'Saturday', 'holiday_reason' => "Second Saturday Bank Holiday", 'state_id' => 26],
            ['date' => '2025-08-15', 'day' => 'Friday', 'holiday_reason' => "Independence Day", 'state_id' => 26],
            ['date' => '2025-08-16', 'day' => 'Saturday', 'holiday_reason' => "Janmashtami", 'state_id' => 26],
            ['date' => '2025-08-23', 'day' => 'Saturday', 'holiday_reason' => "Fourth Saturday Bank Holiday", 'state_id' => 26],
            ['date' => '2025-08-27', 'day' => 'Wednesday', 'holiday_reason' => "Ganesh Chaturthi", 'state_id' => 26],
            ['date' => '2025-08-28', 'day' => 'Thursday', 'holiday_reason' => "Nuakhai", 'state_id' => 26],
            ['date' => '2025-09-05', 'day' => 'Friday', 'holiday_reason' => "Eid e Milad", 'state_id' => 26],
            ['date' => '2025-09-13', 'day' => 'Saturday', 'holiday_reason' => "Second Saturday Bank Holiday", 'state_id' => 26],
            ['date' => '2025-09-21', 'day' => 'Sunday', 'holiday_reason' => "Mahalaya Amavasye", 'state_id' => 26],
            ['date' => '2025-09-27', 'day' => 'Saturday', 'holiday_reason' => "Fourth Saturday Bank Holiday", 'state_id' => 26],
            ['date' => '2025-09-29', 'day' => 'Monday', 'holiday_reason' => "Maha Saptami", 'state_id' => 26],
            ['date' => '2025-09-30', 'day' => 'Tuesday', 'holiday_reason' => "Maha Ashtami", 'state_id' => 26],
            ['date' => '2025-10-01', 'day' => 'Wednesday', 'holiday_reason' => "Maha Navami", 'state_id' => 26],
            ['date' => '2025-10-02', 'day' => 'Thursday', 'holiday_reason' => "Gandhi Jayanti", 'state_id' => 26],
            ['date' => '2025-10-06', 'day' => 'Monday', 'holiday_reason' => "Lakshmi Puja", 'state_id' => 26],
            ['date' => '2025-10-11', 'day' => 'Saturday', 'holiday_reason' => "Second Saturday Bank Holiday", 'state_id' => 26],
            ['date' => '2025-10-21', 'day' => 'Tuesday', 'holiday_reason' => "Diwali", 'state_id' => 26],
            ['date' => '2025-10-25', 'day' => 'Saturday', 'holiday_reason' => "Fourth Saturday Bank Holiday", 'state_id' => 26],
            ['date' => '2025-11-05', 'day' => 'Wednesday', 'holiday_reason' => "Karthika Purnima", 'state_id' => 26],
            ['date' => '2025-11-08', 'day' => 'Saturday', 'holiday_reason' => "Second Saturday Bank Holiday", 'state_id' => 26],
            ['date' => '2025-11-22', 'day' => 'Saturday', 'holiday_reason' => "Fourth Saturday Bank Holiday", 'state_id' => 26],
            ['date' => '2025-12-13', 'day' => 'Saturday', 'holiday_reason' => "Second Saturday Bank Holiday", 'state_id' => 26],
            ['date' => '2025-12-25', 'day' => 'Thursday', 'holiday_reason' => "Christmas Day", 'state_id' => 26],
            ['date' => '2025-12-27', 'day' => 'Saturday', 'holiday_reason' => "Fourth Saturday Bank Holiday", 'state_id' => 26]
        ]; */
        
        /* $holidays = [
            ['date' => '2025-01-01', 'day' => 'Monday', 'holiday_reason' => "New Year", 'state_id' => 27],
            ['date' => '2025-01-11', 'day' => 'Saturday', 'holiday_reason' => "Second Saturday Bank Holiday", 'state_id' => 27],
            ['date' => '2025-01-14', 'day' => 'Tuesday', 'holiday_reason' => "Pongal", 'state_id' => 27],
            ['date' => '2025-01-16', 'day' => 'Thursday', 'holiday_reason' => "Uzhavar Thirunal", 'state_id' => 27],
            ['date' => '2025-01-25', 'day' => 'Saturday', 'holiday_reason' => "Fourth Saturday Bank Holiday", 'state_id' => 27],
            ['date' => '2025-01-26', 'day' => 'Sunday', 'holiday_reason' => "Republic Day", 'state_id' => 27],
            ['date' => '2025-02-08', 'day' => 'Saturday', 'holiday_reason' => "Second Saturday Bank Holiday", 'state_id' => 27],
            ['date' => '2025-02-22', 'day' => 'Saturday', 'holiday_reason' => "Fourth Saturday Bank Holiday", 'state_id' => 27],
            ['date' => '2025-03-08', 'day' => 'Saturday', 'holiday_reason' => "Second Saturday Bank Holiday", 'state_id' => 27],
            ['date' => '2025-03-22', 'day' => 'Saturday', 'holiday_reason' => "Fourth Saturday Bank Holiday", 'state_id' => 27],
            ['date' => '2025-03-31', 'day' => 'Monday', 'holiday_reason' => "Idul Fitr", 'state_id' => 27],
            ['date' => '2025-04-12', 'day' => 'Saturday', 'holiday_reason' => "Second Saturday Bank Holiday", 'state_id' => 27],
            ['date' => '2025-04-14', 'day' => 'Monday', 'holiday_reason' => "Tamil New Year", 'state_id' => 27],
            ['date' => '2025-04-18', 'day' => 'Friday', 'holiday_reason' => "Good Friday", 'state_id' => 27],
            ['date' => '2025-04-26', 'day' => 'Saturday', 'holiday_reason' => "Fourth Saturday Bank Holiday", 'state_id' => 27],
            ['date' => '2025-05-01', 'day' => 'Thursday', 'holiday_reason' => "May Day", 'state_id' => 27],
            ['date' => '2025-05-10', 'day' => 'Saturday', 'holiday_reason' => "Second Saturday Bank Holiday", 'state_id' => 27],
            ['date' => '2025-05-24', 'day' => 'Saturday', 'holiday_reason' => "Fourth Saturday Bank Holiday", 'state_id' => 27],
            ['date' => '2025-06-07', 'day' => 'Saturday', 'holiday_reason' => "Bakri Id", 'state_id' => 27],
            ['date' => '2025-06-14', 'day' => 'Saturday', 'holiday_reason' => "Second Saturday Bank Holiday", 'state_id' => 27],
            ['date' => '2025-06-28', 'day' => 'Saturday', 'holiday_reason' => "Fourth Saturday Bank Holiday", 'state_id' => 27],
            ['date' => '2025-07-06', 'day' => 'Sunday', 'holiday_reason' => "Muharram", 'state_id' => 27],
            ['date' => '2025-07-12', 'day' => 'Saturday', 'holiday_reason' => "Second Saturday Bank Holiday", 'state_id' => 27],
            ['date' => '2025-07-26', 'day' => 'Saturday', 'holiday_reason' => "Fourth Saturday Bank Holiday", 'state_id' => 27],
            ['date' => '2025-08-09', 'day' => 'Saturday', 'holiday_reason' => "Second Saturday Bank Holiday", 'state_id' => 27],
            ['date' => '2025-08-15', 'day' => 'Friday', 'holiday_reason' => "Independence Day", 'state_id' => 27],
            ['date' => '2025-08-16', 'day' => 'Saturday', 'holiday_reason' => "Janmashtami", 'state_id' => 27],
            ['date' => '2025-08-23', 'day' => 'Saturday', 'holiday_reason' => "Fourth Saturday Bank Holiday", 'state_id' => 27],
            ['date' => '2025-08-27', 'day' => 'Wednesday', 'holiday_reason' => "Ganesh Chaturthi", 'state_id' => 27],
            ['date' => '2025-09-05', 'day' => 'Friday', 'holiday_reason' => "Eid-e-Milad", 'state_id' => 27],
            ['date' => '2025-09-13', 'day' => 'Saturday', 'holiday_reason' => "Second Saturday Bank Holiday", 'state_id' => 27],
            ['date' => '2025-09-27', 'day' => 'Saturday', 'holiday_reason' => "Fourth Saturday Bank Holiday", 'state_id' => 27],
            ['date' => '2025-10-01', 'day' => 'Wednesday', 'holiday_reason' => "Maha Navami", 'state_id' => 27],
            ['date' => '2025-10-02', 'day' => 'Thursday', 'holiday_reason' => "Vijaya Dashami", 'state_id' => 27],
            ['date' => '2025-10-02', 'day' => 'Thursday', 'holiday_reason' => "Gandhi Jayanti", 'state_id' => 27],
            ['date' => '2025-10-11', 'day' => 'Saturday', 'holiday_reason' => "Second Saturday Bank Holiday", 'state_id' => 27],
            ['date' => '2025-10-21', 'day' => 'Tuesday', 'holiday_reason' => "Diwali", 'state_id' => 27],
            ['date' => '2025-10-25', 'day' => 'Saturday', 'holiday_reason' => "Fourth Saturday Bank Holiday", 'state_id' => 27],
            ['date' => '2025-11-01', 'day' => 'Saturday', 'holiday_reason' => "Puducherry Liberation Day", 'state_id' => 27],
            ['date' => '2025-11-08', 'day' => 'Saturday', 'holiday_reason' => "Second Saturday Bank Holiday", 'state_id' => 27],
            ['date' => '2025-11-22', 'day' => 'Saturday', 'holiday_reason' => "Fourth Saturday Bank Holiday", 'state_id' => 27],
            ['date' => '2025-12-13', 'day' => 'Saturday', 'holiday_reason' => "Second Saturday Bank Holiday", 'state_id' => 27],
            ['date' => '2025-12-25', 'day' => 'Thursday', 'holiday_reason' => "Christmas Day", 'state_id' => 27],
            ['date' => '2025-12-27', 'day' => 'Saturday', 'holiday_reason' => "Fourth Saturday Bank Holiday", 'state_id' => 27]
        ]; */

        /* $holidays = [
            ['date' => '2025-01-06', 'day' => 'Monday', 'holiday_reason' => "Guru Gobind Singh Jayanti", 'state_id' => 28],
            ['date' => '2025-01-11', 'day' => 'Saturday', 'holiday_reason' => "Second Saturday Bank Holiday", 'state_id' => 28],
            ['date' => '2025-01-25', 'day' => 'Saturday', 'holiday_reason' => "Fourth Saturday Bank Holiday", 'state_id' => 28],
            ['date' => '2025-01-26', 'day' => 'Sunday', 'holiday_reason' => "Republic Day", 'state_id' => 28],
            ['date' => '2025-02-08', 'day' => 'Saturday', 'holiday_reason' => "Second Saturday Bank Holiday", 'state_id' => 28],
            ['date' => '2025-02-12', 'day' => 'Wednesday', 'holiday_reason' => "Guru Ravidas Jayanti", 'state_id' => 28],
            ['date' => '2025-02-22', 'day' => 'Saturday', 'holiday_reason' => "Fourth Saturday Bank Holiday", 'state_id' => 28],
            ['date' => '2025-02-26', 'day' => 'Wednesday', 'holiday_reason' => "Maha Shivaratri", 'state_id' => 28],
            ['date' => '2025-03-08', 'day' => 'Saturday', 'holiday_reason' => "Second Saturday Bank Holiday", 'state_id' => 28],
            ['date' => '2025-03-14', 'day' => 'Friday', 'holiday_reason' => "Holi", 'state_id' => 28],
            ['date' => '2025-03-22', 'day' => 'Saturday', 'holiday_reason' => "Fourth Saturday Bank Holiday", 'state_id' => 28],
            ['date' => '2025-03-31', 'day' => 'Monday', 'holiday_reason' => "Idul Fitr", 'state_id' => 28],
            ['date' => '2025-04-01', 'day' => 'Tuesday', 'holiday_reason' => "Annual Accounts Closing (Bank Holiday)", 'state_id' => 28],
            ['date' => '2025-04-06', 'day' => 'Sunday', 'holiday_reason' => "Ram Navami", 'state_id' => 28],
            ['date' => '2025-04-10', 'day' => 'Thursday', 'holiday_reason' => "Mahavir Jayanti", 'state_id' => 28],
            ['date' => '2025-04-12', 'day' => 'Saturday', 'holiday_reason' => "Second Saturday Bank Holiday", 'state_id' => 28],
            ['date' => '2025-04-13', 'day' => 'Sunday', 'holiday_reason' => "Vaisakhi", 'state_id' => 28],
            ['date' => '2025-04-14', 'day' => 'Monday', 'holiday_reason' => "Dr. B.R. Ambedkar Jayanti", 'state_id' => 28],
            ['date' => '2025-04-18', 'day' => 'Friday', 'holiday_reason' => "Good Friday", 'state_id' => 28],
            ['date' => '2025-04-26', 'day' => 'Saturday', 'holiday_reason' => "Fourth Saturday Bank Holiday", 'state_id' => 28],
            ['date' => '2025-05-01', 'day' => 'Thursday', 'holiday_reason' => "May Day", 'state_id' => 28],
            ['date' => '2025-05-10', 'day' => 'Saturday', 'holiday_reason' => "Second Saturday Bank Holiday", 'state_id' => 28],
            ['date' => '2025-05-24', 'day' => 'Saturday', 'holiday_reason' => "Fourth Saturday Bank Holiday", 'state_id' => 28],
            ['date' => '2025-06-07', 'day' => 'Saturday', 'holiday_reason' => "Id-ul-Zuha / Bakrid", 'state_id' => 28],
            ['date' => '2025-06-11', 'day' => 'Wednesday', 'holiday_reason' => "Kabir Jayanti", 'state_id' => 28],
            ['date' => '2025-06-14', 'day' => 'Saturday', 'holiday_reason' => "Second Saturday Bank Holiday", 'state_id' => 28],
            ['date' => '2025-06-28', 'day' => 'Saturday', 'holiday_reason' => "Fourth Saturday Bank Holiday", 'state_id' => 28],
            ['date' => '2025-07-06', 'day' => 'Sunday', 'holiday_reason' => "Muharram", 'state_id' => 28],
            ['date' => '2025-07-12', 'day' => 'Saturday', 'holiday_reason' => "Second Saturday Bank Holiday", 'state_id' => 28],
            ['date' => '2025-07-26', 'day' => 'Saturday', 'holiday_reason' => "Fourth Saturday Bank Holiday", 'state_id' => 28],
            ['date' => '2025-08-09', 'day' => 'Saturday', 'holiday_reason' => "Second Saturday Bank Holiday", 'state_id' => 28],
            ['date' => '2025-08-15', 'day' => 'Friday', 'holiday_reason' => "Independence Day", 'state_id' => 28],
            ['date' => '2025-08-16', 'day' => 'Saturday', 'holiday_reason' => "Janmashtami", 'state_id' => 28],
            ['date' => '2025-08-23', 'day' => 'Saturday', 'holiday_reason' => "Fourth Saturday Bank Holiday", 'state_id' => 28],
            ['date' => '2025-09-13', 'day' => 'Saturday', 'holiday_reason' => "Second Saturday Bank Holiday", 'state_id' => 28],
            ['date' => '2025-09-22', 'day' => 'Monday', 'holiday_reason' => "Agarsain Jayanti", 'state_id' => 28],
            ['date' => '2025-09-27', 'day' => 'Saturday', 'holiday_reason' => "Fourth Saturday Bank Holiday", 'state_id' => 28],
            ['date' => '2025-10-02', 'day' => 'Thursday', 'holiday_reason' => "Mahatma Gandhi's Birthday", 'state_id' => 28],
            ['date' => '2025-10-02', 'day' => 'Thursday', 'holiday_reason' => "Dussehra", 'state_id' => 28],
            ['date' => '2025-10-07', 'day' => 'Tuesday', 'holiday_reason' => "Maharishi Valmiki Jayanti", 'state_id' => 28],
            ['date' => '2025-10-11', 'day' => 'Saturday', 'holiday_reason' => "Second Saturday Bank Holiday", 'state_id' => 28],
            ['date' => '2025-10-20', 'day' => 'Monday', 'holiday_reason' => "Diwali", 'state_id' => 28],
            ['date' => '2025-10-22', 'day' => 'Wednesday', 'holiday_reason' => "Vishwakarma Day", 'state_id' => 28],
            ['date' => '2025-10-25', 'day' => 'Saturday', 'holiday_reason' => "Fourth Saturday Bank Holiday", 'state_id' => 28],
            ['date' => '2025-11-05', 'day' => 'Wednesday', 'holiday_reason' => "Guru Nanak Jayanti", 'state_id' => 28],
            ['date' => '2025-11-08', 'day' => 'Saturday', 'holiday_reason' => "Second Saturday Bank Holiday", 'state_id' => 28],
            ['date' => '2025-11-22', 'day' => 'Saturday', 'holiday_reason' => "Fourth Saturday Bank Holiday", 'state_id' => 28],
            ['date' => '2025-11-25', 'day' => 'Tuesday', 'holiday_reason' => "Martyrdom Day of Guru Teg Bahadur Ji", 'state_id' => 28],
            ['date' => '2025-12-13', 'day' => 'Saturday', 'holiday_reason' => "Second Saturday Bank Holiday", 'state_id' => 28],
            ['date' => '2025-12-25', 'day' => 'Thursday', 'holiday_reason' => "Christmas Day", 'state_id' => 28],
            ['date' => '2025-12-27', 'day' => 'Saturday', 'holiday_reason' => "Fourth Saturday Bank Holiday", 'state_id' => 28]
        ]; */

        /* $holidays = [
            ['date' => '2025-01-01', 'day' => 'Wednesday', 'holiday_reason' => "New Year's Day", 'state_id' => 29],
            ['date' => '2025-01-06', 'day' => 'Monday', 'holiday_reason' => "Guru Gobind Singh Jayanti", 'state_id' => 29],
            ['date' => '2025-01-11', 'day' => 'Saturday', 'holiday_reason' => "Second Saturday Bank Holiday", 'state_id' => 29],
            ['date' => '2025-01-25', 'day' => 'Saturday', 'holiday_reason' => "Fourth Saturday Bank Holiday", 'state_id' => 29],
            ['date' => '2025-01-26', 'day' => 'Sunday', 'holiday_reason' => "Republic Day", 'state_id' => 29],
            ['date' => '2025-02-04', 'day' => 'Tuesday', 'holiday_reason' => "Shree Devnarayan Jayanti", 'state_id' => 29],
            ['date' => '2025-02-08', 'day' => 'Saturday', 'holiday_reason' => "Second Saturday Bank Holiday", 'state_id' => 29],
            ['date' => '2025-02-22', 'day' => 'Saturday', 'holiday_reason' => "Fourth Saturday Bank Holiday", 'state_id' => 29],
            ['date' => '2025-02-26', 'day' => 'Wednesday', 'holiday_reason' => "Maha Shivaratri", 'state_id' => 29],
            ['date' => '2025-03-08', 'day' => 'Saturday', 'holiday_reason' => "Second Saturday Bank Holiday", 'state_id' => 29],
            ['date' => '2025-03-13', 'day' => 'Thursday', 'holiday_reason' => "Holika Dahan", 'state_id' => 29],
            ['date' => '2025-03-14', 'day' => 'Friday', 'holiday_reason' => "Dhulandi", 'state_id' => 29],
            ['date' => '2025-03-22', 'day' => 'Saturday', 'holiday_reason' => "Fourth Saturday Bank Holiday", 'state_id' => 29],
            ['date' => '2025-03-30', 'day' => 'Sunday', 'holiday_reason' => "Chetti Chand", 'state_id' => 29],
            ['date' => '2025-03-31', 'day' => 'Monday', 'holiday_reason' => "Id-ul-Fitr (End of Ramadan)", 'state_id' => 29],
            ['date' => '2025-04-01', 'day' => 'Tuesday', 'holiday_reason' => "Annual Accounts Closing (Bank Holiday)", 'state_id' => 29],
            ['date' => '2025-04-06', 'day' => 'Sunday', 'holiday_reason' => "Shri Ram Navami", 'state_id' => 29],
            ['date' => '2025-04-10', 'day' => 'Thursday', 'holiday_reason' => "Mahavir Jayanti", 'state_id' => 29],
            ['date' => '2025-04-11', 'day' => 'Friday', 'holiday_reason' => "Mahatma Jyotiba Phule Jayanti", 'state_id' => 29],
            ['date' => '2025-04-12', 'day' => 'Saturday', 'holiday_reason' => "Second Saturday Bank Holiday", 'state_id' => 29],
            ['date' => '2025-04-26', 'day' => 'Saturday', 'holiday_reason' => "Fourth Saturday Bank Holiday", 'state_id' => 29],
            ['date' => '2025-05-29', 'day' => 'Thursday', 'holiday_reason' => "Maharana Pratap Jayanti", 'state_id' => 29],
            ['date' => '2025-06-07', 'day' => 'Saturday', 'holiday_reason' => "Bakrid / Eid al-Adha", 'state_id' => 29],
            ['date' => '2025-06-14', 'day' => 'Saturday', 'holiday_reason' => "Second Saturday Bank Holiday", 'state_id' => 29],
            ['date' => '2025-06-28', 'day' => 'Saturday', 'holiday_reason' => "Fourth Saturday Bank Holiday", 'state_id' => 29],
            ['date' => '2025-07-06', 'day' => 'Sunday', 'holiday_reason' => "Muharram", 'state_id' => 29],
            ['date' => '2025-07-12', 'day' => 'Saturday', 'holiday_reason' => "Second Saturday Bank Holiday", 'state_id' => 29],
            ['date' => '2025-07-26', 'day' => 'Saturday', 'holiday_reason' => "Fourth Saturday Bank Holiday", 'state_id' => 29],
            ['date' => '2025-08-09', 'day' => 'Saturday', 'holiday_reason' => "Raksha Bandhan", 'state_id' => 29],
            ['date' => '2025-08-15', 'day' => 'Friday', 'holiday_reason' => "Independence Day", 'state_id' => 29],
            ['date' => '2025-08-16', 'day' => 'Saturday', 'holiday_reason' => "Janmashtami", 'state_id' => 29],
            ['date' => '2025-08-23', 'day' => 'Saturday', 'holiday_reason' => "Fourth Saturday Bank Holiday", 'state_id' => 29],
            ['date' => '2025-09-02', 'day' => 'Tuesday', 'holiday_reason' => "Ramdev Jayanti", 'state_id' => 29],
            ['date' => '2025-09-02', 'day' => 'Tuesday', 'holiday_reason' => "Teja Dashmi", 'state_id' => 29],
            ['date' => '2025-09-13', 'day' => 'Saturday', 'holiday_reason' => "Second Saturday Bank Holiday", 'state_id' => 29],
            ['date' => '2025-09-22', 'day' => 'Monday', 'holiday_reason' => "Ghatasthapana", 'state_id' => 29],
            ['date' => '2025-09-27', 'day' => 'Saturday', 'holiday_reason' => "Fourth Saturday Bank Holiday", 'state_id' => 29],
            ['date' => '2025-09-30', 'day' => 'Tuesday', 'holiday_reason' => "Maha Ashtami", 'state_id' => 29],
            ['date' => '2025-10-02', 'day' => 'Thursday', 'holiday_reason' => "Vijaya Dashami", 'state_id' => 29],
            ['date' => '2025-10-02', 'day' => 'Thursday', 'holiday_reason' => "Gandhi Jayanti", 'state_id' => 29],
            ['date' => '2025-10-11', 'day' => 'Saturday', 'holiday_reason' => "Second Saturday Bank Holiday", 'state_id' => 29],
            ['date' => '2025-10-20', 'day' => 'Monday', 'holiday_reason' => "Diwali", 'state_id' => 29],
            ['date' => '2025-10-21', 'day' => 'Tuesday', 'holiday_reason' => "Deepavali Holiday", 'state_id' => 29],
            ['date' => '2025-10-23', 'day' => 'Thursday', 'holiday_reason' => "Bhai Dooj", 'state_id' => 29],
            ['date' => '2025-10-25', 'day' => 'Saturday', 'holiday_reason' => "Fourth Saturday Bank Holiday", 'state_id' => 29],
            ['date' => '2025-11-05', 'day' => 'Wednesday', 'holiday_reason' => "Guru Nanak Jayanti", 'state_id' => 29],
            ['date' => '2025-11-08', 'day' => 'Saturday', 'holiday_reason' => "Second Saturday Bank Holiday", 'state_id' => 29],
            ['date' => '2025-11-22', 'day' => 'Saturday', 'holiday_reason' => "Fourth Saturday Bank Holiday", 'state_id' => 29],
            ['date' => '2025-12-13', 'day' => 'Saturday', 'holiday_reason' => "Second Saturday Bank Holiday", 'state_id' => 29],
            ['date' => '2025-12-25', 'day' => 'Thursday', 'holiday_reason' => "Christmas Day", 'state_id' => 29],
            ['date' => '2025-12-27', 'day' => 'Saturday', 'holiday_reason' => "Fourth Saturday Bank Holiday", 'state_id' => 29]
        ]; */

        /* $holidays = [
            ['date' => '2025-01-01', 'day' => 'Monday', 'holiday_reason' => "New Year", 'state_id' => 30],
            ['date' => '2025-01-11', 'day' => 'Saturday', 'holiday_reason' => "Second Saturday Bank Holiday", 'state_id' => 30],
            ['date' => '2025-01-14', 'day' => 'Tuesday', 'holiday_reason' => "Makar Sankranti", 'state_id' => 30],
            ['date' => '2025-01-25', 'day' => 'Saturday', 'holiday_reason' => "Fourth Saturday Bank Holiday", 'state_id' => 30],
            ['date' => '2025-01-26', 'day' => 'Sunday', 'holiday_reason' => "Republic Day", 'state_id' => 30],
            ['date' => '2025-01-30', 'day' => 'Thursday', 'holiday_reason' => "Sonam Losar", 'state_id' => 30],
            ['date' => '2025-02-08', 'day' => 'Saturday', 'holiday_reason' => "Second Saturday Bank Holiday", 'state_id' => 30],
            ['date' => '2025-02-22', 'day' => 'Saturday', 'holiday_reason' => "Fourth Saturday Bank Holiday", 'state_id' => 30],
            ['date' => '2025-03-08', 'day' => 'Saturday', 'holiday_reason' => "Second Saturday Bank Holiday", 'state_id' => 30],
            ['date' => '2025-03-14', 'day' => 'Friday', 'holiday_reason' => "Holi", 'state_id' => 30],
            ['date' => '2025-03-22', 'day' => 'Saturday', 'holiday_reason' => "Fourth Saturday Bank Holiday", 'state_id' => 30],
            ['date' => '2025-03-31', 'day' => 'Monday', 'holiday_reason' => "Idul Fitr", 'state_id' => 30],
            ['date' => '2025-04-06', 'day' => 'Sunday', 'holiday_reason' => "Ram Navami", 'state_id' => 30],
            ['date' => '2025-04-12', 'day' => 'Saturday', 'holiday_reason' => "Second Saturday Bank Holiday", 'state_id' => 30],
            ['date' => '2025-04-14', 'day' => 'Monday', 'holiday_reason' => "Dr. Ambedkar Jayanti", 'state_id' => 30],
            ['date' => '2025-04-18', 'day' => 'Friday', 'holiday_reason' => "Good Friday", 'state_id' => 30],
            ['date' => '2025-04-26', 'day' => 'Saturday', 'holiday_reason' => "Fourth Saturday Bank Holiday", 'state_id' => 30],
            ['date' => '2025-05-01', 'day' => 'Thursday', 'holiday_reason' => "May Day", 'state_id' => 30],
            ['date' => '2025-05-10', 'day' => 'Saturday', 'holiday_reason' => "Second Saturday Bank Holiday", 'state_id' => 30],
            ['date' => '2025-05-16', 'day' => 'Friday', 'holiday_reason' => "State Day", 'state_id' => 30],
            ['date' => '2025-05-24', 'day' => 'Saturday', 'holiday_reason' => "Fourth Saturday Bank Holiday", 'state_id' => 30],
            ['date' => '2025-06-14', 'day' => 'Saturday', 'holiday_reason' => "Second Saturday Bank Holiday", 'state_id' => 30],
            ['date' => '2025-06-28', 'day' => 'Saturday', 'holiday_reason' => "Fourth Saturday Bank Holiday", 'state_id' => 30],
            ['date' => '2025-07-12', 'day' => 'Saturday', 'holiday_reason' => "Second Saturday Bank Holiday", 'state_id' => 30],
            ['date' => '2025-07-13', 'day' => 'Sunday', 'holiday_reason' => "Bhanu Jayanti", 'state_id' => 30],
            ['date' => '2025-07-26', 'day' => 'Saturday', 'holiday_reason' => "Fourth Saturday Bank Holiday", 'state_id' => 30],
            ['date' => '2025-08-08', 'day' => 'Friday', 'holiday_reason' => "Tendong Lho Rum Faat", 'state_id' => 30],
            ['date' => '2025-08-09', 'day' => 'Saturday', 'holiday_reason' => "Second Saturday Bank Holiday", 'state_id' => 30],
            ['date' => '2025-08-15', 'day' => 'Friday', 'holiday_reason' => "Independence Day", 'state_id' => 30],
            ['date' => '2025-08-16', 'day' => 'Saturday', 'holiday_reason' => "Janmashtami", 'state_id' => 30],
            ['date' => '2025-08-23', 'day' => 'Saturday', 'holiday_reason' => "Fourth Saturday Bank Holiday", 'state_id' => 30],
            ['date' => '2025-08-26', 'day' => 'Tuesday', 'holiday_reason' => "Hartallika Teej", 'state_id' => 30],
            ['date' => '2025-09-07', 'day' => 'Sunday', 'holiday_reason' => "Indra Jatra", 'state_id' => 30],
            ['date' => '2025-09-13', 'day' => 'Saturday', 'holiday_reason' => "Second Saturday Bank Holiday", 'state_id' => 30],
            ['date' => '2025-09-27', 'day' => 'Saturday', 'holiday_reason' => "Fourth Saturday Bank Holiday", 'state_id' => 30],
            ['date' => '2025-09-30', 'day' => 'Tuesday', 'holiday_reason' => "Maha Ashtami", 'state_id' => 30],
            ['date' => '2025-10-01', 'day' => 'Wednesday', 'holiday_reason' => "Maha Navami", 'state_id' => 30],
            ['date' => '2025-10-02', 'day' => 'Thursday', 'holiday_reason' => "Vijaya Dashami", 'state_id' => 30],
            ['date' => '2025-10-02', 'day' => 'Thursday', 'holiday_reason' => "Gandhi Jayanti", 'state_id' => 30],
            ['date' => '2025-10-11', 'day' => 'Saturday', 'holiday_reason' => "Second Saturday Bank Holiday", 'state_id' => 30],
            ['date' => '2025-10-21', 'day' => 'Tuesday', 'holiday_reason' => "Diwali", 'state_id' => 30],
            ['date' => '2025-10-23', 'day' => 'Thursday', 'holiday_reason' => "Bhai Dooj", 'state_id' => 30],
            ['date' => '2025-10-25', 'day' => 'Saturday', 'holiday_reason' => "Fourth Saturday Bank Holiday", 'state_id' => 30],
            ['date' => '2025-11-08', 'day' => 'Saturday', 'holiday_reason' => "Second Saturday Bank Holiday", 'state_id' => 30],
            ['date' => '2025-11-11', 'day' => 'Tuesday', 'holiday_reason' => "Lahbab Duchen", 'state_id' => 30],
            ['date' => '2025-11-22', 'day' => 'Saturday', 'holiday_reason' => "Fourth Saturday Bank Holiday", 'state_id' => 30],
            ['date' => '2025-12-13', 'day' => 'Saturday', 'holiday_reason' => "Second Saturday Bank Holiday", 'state_id' => 30],
            ['date' => '2025-12-25', 'day' => 'Thursday', 'holiday_reason' => "Christmas Day", 'state_id' => 30],
            ['date' => '2025-12-27', 'day' => 'Saturday', 'holiday_reason' => "Fourth Saturday Bank Holiday", 'state_id' => 30],
            ['date' => '2025-12-30', 'day' => 'Tuesday', 'holiday_reason' => "Tamu Losar", 'state_id' => 30]
        ]; */
              
        /* $holidays = [
            ['date' => '2025-01-11', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 31],
            ['date' => '2025-01-14', 'day' => 'Tuesday', 'holiday_reason' => 'Pongal', 'state_id' => 31],
            ['date' => '2025-01-15', 'day' => 'Wednesday', 'holiday_reason' => 'Thiruvalluvar Day', 'state_id' => 31],
            ['date' => '2025-01-16', 'day' => 'Thursday', 'holiday_reason' => 'Uzhavar Thirunal', 'state_id' => 31],
            ['date' => '2025-01-25', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 31],
            ['date' => '2025-01-26', 'day' => 'Sunday', 'holiday_reason' => 'Republic Day', 'state_id' => 31],
            ['date' => '2025-02-08', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 31],
            ['date' => '2025-02-22', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 31],
            ['date' => '2025-02-26', 'day' => 'Wednesday', 'holiday_reason' => 'Maha Shivaratri', 'state_id' => 31],
            ['date' => '2025-03-08', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 31],
            ['date' => '2025-03-14', 'day' => 'Friday', 'holiday_reason' => 'Holi', 'state_id' => 31],
            ['date' => '2025-03-22', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 31],
            ['date' => '2025-03-31', 'day' => 'Monday', 'holiday_reason' => 'Annual Accounts Closing (Bank Holiday)', 'state_id' => 31],
            ['date' => '2025-04-01', 'day' => 'Tuesday', 'holiday_reason' => 'Bank Holiday (Accounts Closing)', 'state_id' => 31],
            ['date' => '2025-04-06', 'day' => 'Sunday', 'holiday_reason' => 'Ram Navami', 'state_id' => 31],
            ['date' => '2025-04-12', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 31],
            ['date' => '2025-04-14', 'day' => 'Monday', 'holiday_reason' => 'Tamil New Year / Dr. B.R. Ambedkar Jayanti', 'state_id' => 31],
            ['date' => '2025-04-18', 'day' => 'Friday', 'holiday_reason' => 'Good Friday', 'state_id' => 31],
            ['date' => '2025-04-26', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 31],
            ['date' => '2025-05-10', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 31],
            ['date' => '2025-05-24', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 31],
            ['date' => '2025-06-07', 'day' => 'Saturday', 'holiday_reason' => 'Bakrid / Eid al-Adha', 'state_id' => 31],
            ['date' => '2025-06-14', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 31],
            ['date' => '2025-06-28', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 31],
            ['date' => '2025-07-12', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 31],
            ['date' => '2025-07-26', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 31],
            ['date' => '2025-08-15', 'day' => 'Friday', 'holiday_reason' => 'Independence Day', 'state_id' => 31],
            ['date' => '2025-08-16', 'day' => 'Saturday', 'holiday_reason' => 'Krishna Jayanthi', 'state_id' => 31],
            ['date' => '2025-08-23', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 31],
            ['date' => '2025-09-13', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 31],
            ['date' => '2025-09-27', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 31],
            ['date' => '2025-10-02', 'day' => 'Thursday', 'holiday_reason' => 'Gandhi Jayanti', 'state_id' => 31],
            ['date' => '2025-10-11', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 31],
            ['date' => '2025-10-22', 'day' => 'Wednesday', 'holiday_reason' => 'Ayutha Pooja', 'state_id' => 31],
            ['date' => '2025-10-23', 'day' => 'Thursday', 'holiday_reason' => 'Vijaya Dashami', 'state_id' => 31],
            ['date' => '2025-10-25', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 31],
            ['date' => '2025-11-01', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 31],
            ['date' => '2025-11-22', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 31],
            ['date' => '2025-12-13', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 31],
            ['date' => '2025-12-25', 'day' => 'Thursday', 'holiday_reason' => 'Christmas Day', 'state_id' => 31],
            ['date' => '2025-12-27', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 31],
        ]; */

        /* $holidays = [
            ['date' => '2025-01-01', 'day' => 'Wednesday', 'holiday_reason' => "New Year's Day", 'state_id' => 32],
            ['date' => '2025-01-11', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 32],
            ['date' => '2025-01-13', 'day' => 'Monday', 'holiday_reason' => 'Bhogi', 'state_id' => 32],
            ['date' => '2025-01-14', 'day' => 'Tuesday', 'holiday_reason' => 'Sankranti / Pongal', 'state_id' => 32],
            ['date' => '2025-01-25', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 32],
            ['date' => '2025-01-26', 'day' => 'Sunday', 'holiday_reason' => 'Republic Day', 'state_id' => 32],
            ['date' => '2025-02-08', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 32],
            ['date' => '2025-02-22', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 32],
            ['date' => '2025-02-26', 'day' => 'Wednesday', 'holiday_reason' => 'Maha Shivaratri', 'state_id' => 32],
            ['date' => '2025-03-08', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 32],
            ['date' => '2025-03-22', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 32],
            ['date' => '2025-03-29', 'day' => 'Saturday', 'holiday_reason' => 'Ugadi', 'state_id' => 32],
            ['date' => '2025-04-01', 'day' => 'Tuesday', 'holiday_reason' => 'Annual Accounts Closing (Bank Holiday)', 'state_id' => 32],
            ['date' => '2025-04-05', 'day' => 'Saturday', 'holiday_reason' => "Babu Jagjivan Ram's Birthday", 'state_id' => 32],
            ['date' => '2025-04-06', 'day' => 'Sunday', 'holiday_reason' => 'Ram Navami', 'state_id' => 32],
            ['date' => '2025-04-10', 'day' => 'Thursday', 'holiday_reason' => 'Mahavir Jayanti', 'state_id' => 32],
            ['date' => '2025-04-12', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 32],
            ['date' => '2025-04-14', 'day' => 'Monday', 'holiday_reason' => "Dr. B.R. Ambedkar's Birthday", 'state_id' => 32],
            ['date' => '2025-04-18', 'day' => 'Friday', 'holiday_reason' => 'Good Friday', 'state_id' => 32],
            ['date' => '2025-04-26', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 32],
            ['date' => '2025-05-01', 'day' => 'Thursday', 'holiday_reason' => 'May Day', 'state_id' => 32],
            ['date' => '2025-05-10', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 32],
            ['date' => '2025-05-24', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 32],
            ['date' => '2025-06-07', 'day' => 'Saturday', 'holiday_reason' => 'Bakrid / Eid al-Adha', 'state_id' => 32],
            ['date' => '2025-06-14', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 32],
            ['date' => '2025-06-28', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 32],
            ['date' => '2025-07-06', 'day' => 'Sunday', 'holiday_reason' => 'Muharram', 'state_id' => 32],
            ['date' => '2025-07-12', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 32],
            ['date' => '2025-07-21', 'day' => 'Monday', 'holiday_reason' => 'Bonalu', 'state_id' => 32],
            ['date' => '2025-07-26', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 32],
            ['date' => '2025-08-09', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 32],
            ['date' => '2025-08-15', 'day' => 'Friday', 'holiday_reason' => 'Independence Day', 'state_id' => 32],
            ['date' => '2025-08-16', 'day' => 'Saturday', 'holiday_reason' => 'Janmashtami', 'state_id' => 32],
            ['date' => '2025-08-23', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 32],
            ['date' => '2025-08-27', 'day' => 'Wednesday', 'holiday_reason' => 'Ganesh Chaturthi', 'state_id' => 32],
            ['date' => '2025-09-05', 'day' => 'Friday', 'holiday_reason' => 'Eid-e-Milad', 'state_id' => 32],
            ['date' => '2025-09-13', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 32],
            ['date' => '2025-09-22', 'day' => 'Monday', 'holiday_reason' => 'First Day of Bathukamma', 'state_id' => 32],
            ['date' => '2025-09-27', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 32],
            ['date' => '2025-09-30', 'day' => 'Tuesday', 'holiday_reason' => 'Maha Ashtami', 'state_id' => 32],
            ['date' => '2025-10-02', 'day' => 'Thursday', 'holiday_reason' => 'Gandhi Jayanti / Vijaya Dashami', 'state_id' => 32],
            ['date' => '2025-10-11', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 32],
            ['date' => '2025-10-21', 'day' => 'Tuesday', 'holiday_reason' => 'Diwali', 'state_id' => 32],
            ['date' => '2025-10-25', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 32],
            ['date' => '2025-11-05', 'day' => 'Wednesday', 'holiday_reason' => 'Karthika Purnima / Guru Nanak Jayanti', 'state_id' => 32],
            ['date' => '2025-11-08', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 32],
            ['date' => '2025-11-22', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 32],
            ['date' => '2025-12-13', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 32],
            ['date' => '2025-12-25', 'day' => 'Thursday', 'holiday_reason' => 'Christmas Day', 'state_id' => 32],
            ['date' => '2025-12-27', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 32],
        ]; */

        /* $holidays = [
            ['date' => '2025-01-11', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 33],
            ['date' => '2025-01-23', 'day' => 'Thursday', 'holiday_reason' => 'Netaji Subhas Chandra Bose Jayanti', 'state_id' => 33],
            ['date' => '2025-01-25', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 33],
            ['date' => '2025-01-26', 'day' => 'Sunday', 'holiday_reason' => 'Republic Day', 'state_id' => 33],
            ['date' => '2025-02-03', 'day' => 'Monday', 'holiday_reason' => 'Vasant Panchami', 'state_id' => 33],
            ['date' => '2025-02-08', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 33],
            ['date' => '2025-02-22', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 33],
            ['date' => '2025-02-26', 'day' => 'Wednesday', 'holiday_reason' => 'Maha Shivaratri', 'state_id' => 33],
            ['date' => '2025-03-08', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 33],
            ['date' => '2025-03-14', 'day' => 'Friday', 'holiday_reason' => 'Holi', 'state_id' => 33],
            ['date' => '2025-03-22', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 33],
            ['date' => '2025-03-31', 'day' => 'Monday', 'holiday_reason' => 'Idul Fitr', 'state_id' => 33],
            ['date' => '2025-04-12', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 33],
            ['date' => '2025-04-14', 'day' => 'Monday', 'holiday_reason' => 'Biju Festival', 'state_id' => 33],
            ['date' => '2025-04-14', 'day' => 'Monday', 'holiday_reason' => 'Bengali New Year', 'state_id' => 33],
            ['date' => '2025-04-18', 'day' => 'Friday', 'holiday_reason' => 'Good Friday', 'state_id' => 33],
            ['date' => '2025-04-21', 'day' => 'Monday', 'holiday_reason' => 'Garia Puja', 'state_id' => 33],
            ['date' => '2025-04-26', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 33],
            ['date' => '2025-05-01', 'day' => 'Thursday', 'holiday_reason' => 'May Day', 'state_id' => 33],
            ['date' => '2025-05-08', 'day' => 'Thursday', 'holiday_reason' => 'Guru Rabindranath Jayanti', 'state_id' => 33],
            ['date' => '2025-05-10', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 33],
            ['date' => '2025-05-12', 'day' => 'Monday', 'holiday_reason' => 'Buddha Purnima', 'state_id' => 33],
            ['date' => '2025-05-24', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 33],
            ['date' => '2025-05-26', 'day' => 'Monday', 'holiday_reason' => 'Kazi Nazrul Islam Jayanti', 'state_id' => 33],
            ['date' => '2025-06-07', 'day' => 'Saturday', 'holiday_reason' => 'Bakrid / Eid al Adha', 'state_id' => 33],
            ['date' => '2025-06-14', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 33],
            ['date' => '2025-06-28', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 33],
            ['date' => '2025-07-03', 'day' => 'Thursday', 'holiday_reason' => 'Kharchi Puja', 'state_id' => 33],
            ['date' => '2025-07-06', 'day' => 'Sunday', 'holiday_reason' => 'Muharram', 'state_id' => 33],
            ['date' => '2025-07-12', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 33],
            ['date' => '2025-07-19', 'day' => 'Saturday', 'holiday_reason' => 'Ker Puja', 'state_id' => 33],
            ['date' => '2025-07-26', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 33],
            ['date' => '2025-08-09', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 33],
            ['date' => '2025-08-15', 'day' => 'Friday', 'holiday_reason' => 'Independence Day', 'state_id' => 33],
            ['date' => '2025-08-16', 'day' => 'Saturday', 'holiday_reason' => 'Janmashtami', 'state_id' => 33],
            ['date' => '2025-08-23', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 33],
            ['date' => '2025-09-05', 'day' => 'Friday', 'holiday_reason' => 'Eid e Milad', 'state_id' => 33],
            ['date' => '2025-09-13', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 33],
            ['date' => '2025-09-21', 'day' => 'Sunday', 'holiday_reason' => 'Mahalaya Amavasye', 'state_id' => 33],
            ['date' => '2025-09-27', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 33],
            ['date' => '2025-09-29', 'day' => 'Monday', 'holiday_reason' => 'Maha Saptami', 'state_id' => 33],
            ['date' => '2025-09-30', 'day' => 'Tuesday', 'holiday_reason' => 'Maha Ashtami', 'state_id' => 33],
            ['date' => '2025-10-01', 'day' => 'Wednesday', 'holiday_reason' => 'Maha Navami', 'state_id' => 33],
            ['date' => '2025-10-02', 'day' => 'Thursday', 'holiday_reason' => 'Vijaya Dashami', 'state_id' => 33],
            ['date' => '2025-10-02', 'day' => 'Thursday', 'holiday_reason' => 'Gandhi Jayanti', 'state_id' => 33],
            ['date' => '2025-10-06', 'day' => 'Monday', 'holiday_reason' => 'Lakshmi Puja', 'state_id' => 33],
            ['date' => '2025-10-11', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 33],
            ['date' => '2025-10-20', 'day' => 'Monday', 'holiday_reason' => 'Diwali', 'state_id' => 33],
            ['date' => '2025-10-25', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 33],
            ['date' => '2025-11-08', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 33],
            ['date' => '2025-11-22', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 33],
            ['date' => '2025-12-13', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 33],
            ['date' => '2025-12-25', 'day' => 'Thursday', 'holiday_reason' => 'Christmas Day', 'state_id' => 33],
            ['date' => '2025-12-27', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 33],
        ]; */

        /* $holidays = [
            ['date' => '2025-01-11', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 34],
            ['date' => '2025-01-25', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 34],
            ['date' => '2025-01-26', 'day' => 'Sunday', 'holiday_reason' => 'Republic Day', 'state_id' => 34],
            ['date' => '2025-02-08', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 34],
            ['date' => '2025-02-22', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 34],
            ['date' => '2025-02-26', 'day' => 'Wednesday', 'holiday_reason' => 'Maha Shivaratri', 'state_id' => 34],
            ['date' => '2025-03-08', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 34],
            ['date' => '2025-03-14', 'day' => 'Friday', 'holiday_reason' => 'Holi', 'state_id' => 34],
            ['date' => '2025-03-22', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 34],
            ['date' => '2025-03-31', 'day' => 'Monday', 'holiday_reason' => 'Idul Fitr', 'state_id' => 34],
            ['date' => '2025-04-06', 'day' => 'Sunday', 'holiday_reason' => 'Ram Navami', 'state_id' => 34],
            ['date' => '2025-04-12', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 34],
            ['date' => '2025-04-14', 'day' => 'Monday', 'holiday_reason' => 'Dr Ambedkar Jayanti', 'state_id' => 34],
            ['date' => '2025-04-18', 'day' => 'Friday', 'holiday_reason' => 'Good Friday', 'state_id' => 34],
            ['date' => '2025-04-26', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 34],
            ['date' => '2025-05-10', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 34],
            ['date' => '2025-05-12', 'day' => 'Monday', 'holiday_reason' => 'Buddha Purnima', 'state_id' => 34],
            ['date' => '2025-05-24', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 34],
            ['date' => '2025-06-07', 'day' => 'Saturday', 'holiday_reason' => 'Bakrid / Eid al Adha', 'state_id' => 34],
            ['date' => '2025-06-14', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 34],
            ['date' => '2025-06-28', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 34],
            ['date' => '2025-07-12', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 34],
            ['date' => '2025-07-26', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 34],
            ['date' => '2025-08-09', 'day' => 'Saturday', 'holiday_reason' => 'Raksha Bandhan/ Second Saturday Bank Holiday', 'state_id' => 34],
            ['date' => '2025-08-15', 'day' => 'Friday', 'holiday_reason' => 'Independence Day', 'state_id' => 34],
            ['date' => '2025-08-16', 'day' => 'Saturday', 'holiday_reason' => 'Janmashtami', 'state_id' => 34],
            ['date' => '2025-08-23', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 34],
            ['date' => '2025-09-05', 'day' => 'Friday', 'holiday_reason' => 'Eid e Milad', 'state_id' => 34],
            ['date' => '2025-09-13', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 34],
            ['date' => '2025-09-27', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 34],
            ['date' => '2025-10-02', 'day' => 'Thursday', 'holiday_reason' => 'Gandhi Jayanti', 'state_id' => 34],
            ['date' => '2025-10-11', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 34],
            ['date' => '2025-10-21', 'day' => 'Tuesday', 'holiday_reason' => 'Diwali', 'state_id' => 34],
            ['date' => '2025-10-22', 'day' => 'Wednesday', 'holiday_reason' => 'Deepavali Holiday', 'state_id' => 34],
            ['date' => '2025-10-23', 'day' => 'Thursday', 'holiday_reason' => 'Bhai Dooj', 'state_id' => 34],
            ['date' => '2025-10-25', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 34],
            ['date' => '2025-11-05', 'day' => 'Wednesday', 'holiday_reason' => 'Guru Nanak Jayanti', 'state_id' => 34],
            ['date' => '2025-11-08', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 34],
            ['date' => '2025-11-22', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 34],
            ['date' => '2025-12-13', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 34],
            ['date' => '2025-12-25', 'day' => 'Thursday', 'holiday_reason' => 'Christmas Day', 'state_id' => 34],
            ['date' => '2025-12-27', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 34],
        ]; */
        
        /* $holidays = [
            ['date' => '2025-01-11', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 35],
            ['date' => '2025-01-25', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 35],
            ['date' => '2025-01-26', 'day' => 'Sunday', 'holiday_reason' => 'Republic Day', 'state_id' => 35],
            ['date' => '2025-02-08', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 35],
            ['date' => '2025-02-22', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 35],
            ['date' => '2025-02-26', 'day' => 'Wednesday', 'holiday_reason' => 'Maha Shivaratri', 'state_id' => 35],
            ['date' => '2025-03-08', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 35],
            ['date' => '2025-03-14', 'day' => 'Friday', 'holiday_reason' => 'Holi', 'state_id' => 35],
            ['date' => '2025-03-22', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 35],
            ['date' => '2025-03-31', 'day' => 'Monday', 'holiday_reason' => 'Idul Fitr', 'state_id' => 35],
            ['date' => '2025-04-06', 'day' => 'Sunday', 'holiday_reason' => 'Ram Navami', 'state_id' => 35],
            ['date' => '2025-04-12', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 35],
            ['date' => '2025-04-14', 'day' => 'Monday', 'holiday_reason' => 'Dr Ambedkar Jayanti', 'state_id' => 35],
            ['date' => '2025-04-18', 'day' => 'Friday', 'holiday_reason' => 'Good Friday', 'state_id' => 35],
            ['date' => '2025-04-26', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 35],
            ['date' => '2025-05-10', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 35],
            ['date' => '2025-05-12', 'day' => 'Sunday', 'holiday_reason' => 'Buddha Purnima', 'state_id' => 35],
            ['date' => '2025-05-24', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 35],
            ['date' => '2025-06-07', 'day' => 'Saturday', 'holiday_reason' => 'Bakrid / Eid al Adha', 'state_id' => 35],
            ['date' => '2025-06-14', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 35],
            ['date' => '2025-06-28', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 35],
            ['date' => '2025-07-12', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 35],
            ['date' => '2025-07-26', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 35],
            ['date' => '2025-08-09', 'day' => 'Saturday', 'holiday_reason' => 'Raksha Bandhan/ Second Saturday Bank Holiday', 'state_id' => 35],
            ['date' => '2025-08-15', 'day' => 'Friday', 'holiday_reason' => 'Independence Day', 'state_id' => 35],
            ['date' => '2025-08-16', 'day' => 'Saturday', 'holiday_reason' => 'Janmashtami', 'state_id' => 35],
            ['date' => '2025-08-23', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 35],
            ['date' => '2025-09-05', 'day' => 'Friday', 'holiday_reason' => 'Eid e Milad', 'state_id' => 35],
            ['date' => '2025-09-13', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 35],
            ['date' => '2025-09-27', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 35],
            ['date' => '2025-10-02', 'day' => 'Thursday', 'holiday_reason' => 'Gandhi Jayanti', 'state_id' => 35],
            ['date' => '2025-10-11', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 35],
            ['date' => '2025-10-21', 'day' => 'Tuesday', 'holiday_reason' => 'Diwali', 'state_id' => 35],
            ['date' => '2025-10-22', 'day' => 'Wednesday', 'holiday_reason' => 'Deepavali Holiday', 'state_id' => 35],
            ['date' => '2025-10-23', 'day' => 'Thursday', 'holiday_reason' => 'Bhai Dooj', 'state_id' => 35],
            ['date' => '2025-10-25', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 35],
            ['date' => '2025-11-05', 'day' => 'Wednesday', 'holiday_reason' => 'Guru Nanak Jayanti', 'state_id' => 35],
            ['date' => '2025-11-08', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 35],
            ['date' => '2025-11-22', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 35],
            ['date' => '2025-12-13', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 35],
            ['date' => '2025-12-25', 'day' => 'Thursday', 'holiday_reason' => 'Christmas Day', 'state_id' => 35],
            ['date' => '2025-12-27', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 35],
        ]; */

        /* $holidays = [
            ['date' => '2025-01-11', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 36],
            ['date' => '2025-01-12', 'day' => 'Sunday', 'holiday_reason' => 'Swami Vivekananda Jayanti', 'state_id' => 36],
            ['date' => '2025-01-23', 'day' => 'Thursday', 'holiday_reason' => 'Netaji Subhas Chandra Bose Jayanti', 'state_id' => 36],
            ['date' => '2025-01-25', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 36],
            ['date' => '2025-01-26', 'day' => 'Sunday', 'holiday_reason' => 'Republic Day', 'state_id' => 36],
            ['date' => '2025-02-03', 'day' => 'Monday', 'holiday_reason' => 'Vasant Panchami', 'state_id' => 36],
            ['date' => '2025-02-08', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 36],
            ['date' => '2025-02-22', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 36],
            ['date' => '2025-03-08', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 36],
            ['date' => '2025-03-14', 'day' => 'Friday', 'holiday_reason' => 'Doljatra', 'state_id' => 36],
            ['date' => '2025-03-22', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 36],
            ['date' => '2025-03-31', 'day' => 'Monday', 'holiday_reason' => 'Idul Fitr', 'state_id' => 36],
            ['date' => '2025-04-12', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 36],
            ['date' => '2025-04-14', 'day' => 'Monday', 'holiday_reason' => 'Bengali New Year', 'state_id' => 36],
            ['date' => '2025-04-18', 'day' => 'Friday', 'holiday_reason' => 'Good Friday', 'state_id' => 36],
            ['date' => '2025-04-26', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 36],
            ['date' => '2025-05-01', 'day' => 'Thursday', 'holiday_reason' => 'May Day', 'state_id' => 36],
            ['date' => '2025-05-08', 'day' => 'Thursday', 'holiday_reason' => 'Guru Rabindranath Jayanti', 'state_id' => 36],
            ['date' => '2025-05-10', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 36],
            ['date' => '2025-05-12', 'day' => 'Monday', 'holiday_reason' => 'Buddha Purnima', 'state_id' => 36],
            ['date' => '2025-05-24', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 36],
            ['date' => '2025-06-07', 'day' => 'Saturday', 'holiday_reason' => 'Bakrid / Eid al Adha', 'state_id' => 36],
            ['date' => '2025-06-14', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 36],
            ['date' => '2025-06-28', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 36],
            ['date' => '2025-07-06', 'day' => 'Sunday', 'holiday_reason' => 'Muharram', 'state_id' => 36],
            ['date' => '2025-07-12', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 36],
            ['date' => '2025-07-26', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 36],
            ['date' => '2025-08-09', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 36],
            ['date' => '2025-08-15', 'day' => 'Friday', 'holiday_reason' => 'Independence Day', 'state_id' => 36],
            ['date' => '2025-08-23', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 36],
            ['date' => '2025-09-13', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 36],
            ['date' => '2025-09-21', 'day' => 'Sunday', 'holiday_reason' => 'Mahalaya Amavasye', 'state_id' => 36],
            ['date' => '2025-09-27', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 36],
            ['date' => '2025-09-29', 'day' => 'Monday', 'holiday_reason' => 'Maha Saptami', 'state_id' => 36],
            ['date' => '2025-09-30', 'day' => 'Tuesday', 'holiday_reason' => 'Maha Ashtami', 'state_id' => 36],
            ['date' => '2025-10-01', 'day' => 'Wednesday', 'holiday_reason' => 'Maha Navami', 'state_id' => 36],
            ['date' => '2025-10-02', 'day' => 'Thursday', 'holiday_reason' => 'Gandhi Jayanti', 'state_id' => 36],
            ['date' => '2025-10-06', 'day' => 'Monday', 'holiday_reason' => 'Lakshmi Puja', 'state_id' => 36],
            ['date' => '2025-10-11', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 36],
            ['date' => '2025-10-21', 'day' => 'Tuesday', 'holiday_reason' => 'Diwali', 'state_id' => 36],
            ['date' => '2025-10-25', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 36],
            ['date' => '2025-11-05', 'day' => 'Wednesday', 'holiday_reason' => 'Guru Nanak Jayanti', 'state_id' => 36],
            ['date' => '2025-11-08', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 36],
            ['date' => '2025-11-22', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 36],
            ['date' => '2025-12-13', 'day' => 'Saturday', 'holiday_reason' => 'Second Saturday Bank Holiday', 'state_id' => 36],
            ['date' => '2025-12-25', 'day' => 'Thursday', 'holiday_reason' => 'Christmas Day', 'state_id' => 36],
            ['date' => '2025-12-27', 'day' => 'Saturday', 'holiday_reason' => 'Fourth Saturday Bank Holiday', 'state_id' => 36],
        ]; */
        
        /* $stateId = DB::table('states')->insertGetId([
            'name' => "West Bengal",
            'created_at' => now(),
            'updated_at' => now(),
        ]); */

        foreach ($holidays as $holiday) {
            $existing = DB::table('state_wise_bank_holiday')
                ->where('date', $holiday['date'])
                ->where('holiday_reason', $holiday['holiday_reason'])
                ->first();
        
            if ($existing) {
                $existingStates = explode(',', $existing->states);
                $stateId = (string)$holiday['state_id'];
        
                if (!in_array($stateId, $existingStates)) {
                    // Add new state_id to states
                    $updatedStates = array_unique([...$existingStates, $stateId]);
                    sort($updatedStates); // optional: keep states in order
        
                    DB::table('state_wise_bank_holiday')
                        ->where('holiday_id', $existing->holiday_id)
                        ->update([
                            'states' => implode(',', $updatedStates),
                            'updated_at' => now(),
                        ]);
                }
            } else {
                // Insert new record
                DB::table('state_wise_bank_holiday')->insert([
                    'holiday_reason' => $holiday['holiday_reason'],
                    'day' => $holiday['day'],
                    'date' => $holiday['date'],
                    'states' => $holiday['state_id'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // // List of holidays Andaman & Nicobar
        // $holidays = [
        //     ['date' => '2025-01-11', 'day' => 'Saturday', 'reason' => 'Second Saturday Bank Holiday'],
        //     ['date' => '2025-01-25', 'day' => 'Saturday', 'reason' => 'Fourth Saturday Bank Holiday'],
        //     ['date' => '2025-01-26', 'day' => 'Sunday', 'reason' => 'Republic Day'],
        //     ['date' => '2025-02-08', 'day' => 'Saturday', 'reason' => 'Second Saturday Bank Holiday'],
        //     ['date' => '2025-02-22', 'day' => 'Saturday', 'reason' => 'Fourth Saturday Bank Holiday'],
        //     ['date' => '2025-03-08', 'day' => 'Saturday', 'reason' => 'Second Saturday Bank Holiday'],
        //     ['date' => '2025-03-14', 'day' => 'Friday', 'reason' => 'Holi'],
        //     ['date' => '2025-03-22', 'day' => 'Saturday', 'reason' => 'Fourth Saturday Bank Holiday'],
        //     ['date' => '2025-03-31', 'day' => 'Monday', 'reason' => 'Idul Fitr'],
        //     ['date' => '2025-04-06', 'day' => 'Sunday', 'reason' => 'Ram Navami'],
        //     ['date' => '2025-04-12', 'day' => 'Saturday', 'reason' => 'Second Saturday Bank Holiday'],
        //     ['date' => '2025-04-18', 'day' => 'Friday', 'reason' => 'Good Friday'],
        //     ['date' => '2025-04-26', 'day' => 'Saturday', 'reason' => 'Fourth Saturday Bank Holiday'],
        //     ['date' => '2025-05-10', 'day' => 'Saturday', 'reason' => 'Second Saturday Bank Holiday'],
        //     ['date' => '2025-05-12', 'day' => 'Monday', 'reason' => 'Buddha Purnima'],
        //     ['date' => '2025-05-24', 'day' => 'Saturday', 'reason' => 'Fourth Saturday Bank Holiday'],
        //     ['date' => '2025-06-07', 'day' => 'Saturday', 'reason' => 'Bakrid / Eid al Adha'],
        //     ['date' => '2025-06-14', 'day' => 'Saturday', 'reason' => 'Second Saturday Bank Holiday'],
        //     ['date' => '2025-06-28', 'day' => 'Saturday', 'reason' => 'Fourth Saturday Bank Holiday'],
        //     ['date' => '2025-07-06', 'day' => 'Sunday', 'reason' => 'Muharram'],
        //     ['date' => '2025-07-12', 'day' => 'Saturday', 'reason' => 'Second Saturday Bank Holiday'],
        //     ['date' => '2025-07-26', 'day' => 'Saturday', 'reason' => 'Fourth Saturday Bank Holiday'],
        //     ['date' => '2025-08-09', 'day' => 'Saturday', 'reason' => 'Second Saturday Bank Holiday'],
        //     ['date' => '2025-08-15', 'day' => 'Friday', 'reason' => 'Independence Day'],
        //     ['date' => '2025-08-16', 'day' => 'Saturday', 'reason' => 'Janmashtami'],
        //     ['date' => '2025-08-23', 'day' => 'Saturday', 'reason' => 'Fourth Saturday Bank Holiday'],
        //     ['date' => '2025-09-13', 'day' => 'Saturday', 'reason' => 'Second Saturday Bank Holiday'],
        //     ['date' => '2025-09-27', 'day' => 'Saturday', 'reason' => 'Fourth Saturday Bank Holiday'],
        //     ['date' => '2025-10-02', 'day' => 'Thursday', 'reason' => 'Gandhi Jayanti'],
        //     ['date' => '2025-10-11', 'day' => 'Saturday', 'reason' => 'Second Saturday Bank Holiday'],
        //     ['date' => '2025-10-21', 'day' => 'Tuesday', 'reason' => 'Diwali'],
        //     ['date' => '2025-10-25', 'day' => 'Saturday', 'reason' => 'Fourth Saturday Bank Holiday'],
        //     ['date' => '2025-11-05', 'day' => 'Wednesday', 'reason' => 'Guru Nanak Jayanti'],
        //     ['date' => '2025-11-08', 'day' => 'Saturday', 'reason' => 'Second Saturday Bank Holiday'],
        //     ['date' => '2025-11-22', 'day' => 'Saturday', 'reason' => 'Fourth Saturday Bank Holiday'],
        //     ['date' => '2025-12-13', 'day' => 'Saturday', 'reason' => 'Second Saturday Bank Holiday'],
        //     ['date' => '2025-12-25', 'day' => 'Thursday', 'reason' => 'Christmas Day'],
        //     ['date' => '2025-12-27', 'day' => 'Saturday', 'reason' => 'Fourth Saturday Bank Holiday'],
        // ];

        // // Insert holidays
        // foreach ($holidays as $holiday) {
        //     DB::table('state_wise_bank_holiday')->insert([
        //         'holiday_reason' => $holiday['reason'],
        //         'day' => $holiday['day'],
        //         'date' => $holiday['date'],
        //         'states' => $stateId,
        //         'created_at' => now(),
        //         'updated_at' => now(),
        //     ]);
        // }
        
        return "Data stored.";
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

    public function BankHolidayStoreState() 
    { 
        // Scrap data using chatgpt
        // https://cleartax.in/s/bank-holidays-list-2025 

        /* $data = [
            [
            
                'holiday_name' => 'New Year',
                'day' => 'Wednesday',
                'date' => '01 January 2025',
                'states' => 'Arunachal Pradesh, Assam, Goa, Manipur, Meghalaya, Mizoram, Nagaland, Odisha, Rajasthan, Sikkim, Tamil Nadu, Telangana',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
            
                'holiday_name' => 'New Year Holiday',
                'day' => 'Thursday',
                'date' => '02 January 2025',
                'states' => 'Mizoram',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
            
                'holiday_name' => 'Mannam Jayanthi',
                'day' => 'Thursday',
                'date' => '02 January 2025',
                'states' => 'Kerala',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
            
                'holiday_name' => 'Guru Gobind Singh Jayanti',
                'day' => 'Monday',
                'date' => '06 January 2025',
                'states' => 'Chandigarh',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
            
                'holiday_name' => 'Missionary Day',
                'day' => 'Saturday',
                'date' => '11 January 2025',
                'states' => 'Mizoram',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
            
                'holiday_name' => 'Imoinu Iratpa',
                'day' => 'Saturday',
                'date' => '11 January 2025',
                'states' => 'Manipur',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
            
                'holiday_name' => 'Makar Sankranti / Pongal / Magh Bihu',
                'day' => 'Tuesday',
                'date' => '14 January 2025',
                'states' => 'Andhra Pradesh, Gujarat, Karnataka, Odisha, Tamil Nadu, Telangana, West Bengal',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
            
                'holiday_name' => 'Thiruvalluvar Day',
                'day' => 'Wednesday',
                'date' => '15 January 2025',
                'states' => 'Tamil Nadu',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
            
                'holiday_name' => 'Uzhavar Thirunal',
                'day' => 'Thursday',
                'date' => '16 January 2025',
                'states' => 'Tamil Nadu',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
            
                'holiday_name' => 'Netaji Subhas Chandra Bose Jayanti',
                'day' => 'Thursday',
                'date' => '23 January 2025',
                'states' => 'Odisha, Tripura, West Bengal',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
            
                'holiday_name' => 'Republic Day',
                'day' => 'Sunday',
                'date' => '26 January 2025',
                'states' => 'All States',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]; */ 


        /* $data = [
            [
                'date' => '03 February 2025',
                'day' => 'Monday',
                'holiday_name' => 'Vasant Panchami',
                'states' => 'Haryana, Odisha, Punjab, Sikkim, Tamil Nadu, Tripura, West Bengal',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'date' => '10 February 2025',
                'day' => 'Monday',
                'holiday_name' => 'Losar',
                'states' => 'Sikkim',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'date' => '12 February 2025',
                'day' => 'Wednesday',
                'holiday_name' => 'Guru Ravidas Jayanti',
                'states' => 'Haryana, Himachal Pradesh, Madhya Pradesh, Mizoram, Punjab',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'date' => '14 February 2025',
                'day' => 'Friday',
                'holiday_name' => 'Vasanta Panchami',
                'states' => 'Odisha, West Bengal, Jammu and Kashmir, Delhi',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'date' => '14 February 2025',
                'day' => 'Friday',
                'holiday_name' => 'Saraswati Puja',
                'states' => 'Tripura, West Bengal',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'date' => '14 February 2025',
                'day' => 'Friday',
                'holiday_name' => 'Holi',
                'states' => 'Meghalaya, Nagaland',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'date' => '14 February 2025',
                'day' => 'Friday',
                'holiday_name' => 'Shab-E-Barat',
                'states' => 'Chattisgarh',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'date' => '15 February 2025',
                'day' => 'Saturday',
                'holiday_name' => 'Lui-Ngai-Ni',
                'states' => 'Manipur',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'date' => '19 February 2025',
                'day' => 'Wednesday',
                'holiday_name' => 'Chhatrapati Shivaji Maharaj Jayanti',
                'states' => 'Maharashtra',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'date' => '20 February 2025',
                'day' => 'Thursday',
                'holiday_name' => 'Statehood Day',
                'states' => 'Arunachal Pradesh',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'date' => '20 February 2025',
                'day' => 'Thursday',
                'holiday_name' => 'State Day',
                'states' => 'Mizoram',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'date' => '25 February 2025',
                'day' => 'Tuesday',
                'holiday_name' => 'Maha Shivaratri',
                'states' => 'Karnataka, Kerala',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'date' => '26 February 2025',
                'day' => 'Wednesday',
                'holiday_name' => 'Maha Shivaratri',
                'states' => 'Goa, Gujarat, Haryana, Himachal Pradesh, Jharkhand, Karnataka, Madhya Pradesh',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]; */

        /* $data = [
            [
                'holiday_name' => 'Chapchar Kut',
                'day' => 'Saturday',
                'date' => '01 March 2025',
                'states' => 'Mizoram',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Panchayati Raj Divas',
                'day' => 'Wednesday',
                'date' => '05 March 2025',
                'states' => 'Odisha, Punjab, Sikkim',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Chapchar Kut',
                'day' => 'Friday',
                'date' => '07 March 2025',
                'states' => 'Mizoram',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Mahashivratri',
                'day' => 'Saturday',
                'date' => '08 March 2025',
                'states' => 'Andhra Pradesh, Bihar, Uttar Pradesh, Rajasthan, Uttarakhand',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Holi',
                'day' => 'Friday',
                'date' => '14 March 2025',
                'states' => 'Arunachal Pradesh, Bihar, Chhattisgarh, Goa, Gujarat, Haryana, Himachal Pradesh, Jharkhand, Madhya Pradesh, Maharashtra, Meghalaya, Mizoram, Nagaland, Odisha, Punjab, Rajasthan, Sikkim, Tamil Nadu, Telangana, Tripura',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Yaosang',
                'day' => 'Friday',
                'date' => '14 March 2025',
                'states' => 'Manipur',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Yaosang 2nd Day',
                'day' => 'Friday',
                'date' => '14 March 2025',
                'states' => 'Manipur',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Doljatra',
                'day' => 'Friday',
                'date' => '14 March 2025',
                'states' => 'West Bengal',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Bihar Day',
                'day' => 'Saturday',
                'date' => '22 March 2025',
                'states' => 'Bihar',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => "Shaheed Bhagat Singh's Martyrdom Day",
                'day' => 'Sunday',
                'date' => '23 March 2025',
                'states' => 'Haryana',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Dol Jatra',
                'day' => 'Tuesday',
                'date' => '25 March 2025',
                'states' => 'Assam, West Bengal, Jammu and Kashmir, Delhi',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Dhulandi',
                'day' => 'Tuesday',
                'date' => '25 March 2025',
                'states' => 'Rajasthan',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Holi',
                'day' => 'Tuesday',
                'date' => '25 March 2025',
                'states' => 'Andhra Pradesh, Assam, Goa, Rajasthan, Uttar Pradesh, Uttarakhand',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Holi',
                'day' => 'Wednesday',
                'date' => '26 March 2025',
                'states' => 'Bihar, Odisha',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Yaosang 2nd Day',
                'day' => 'Wednesday',
                'date' => '26 March 2025',
                'states' => 'Manipur',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Holi',
                'day' => 'Thursday',
                'date' => '27 March 2025',
                'states' => 'Bihar',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Jamat-Ul-Vida',
                'day' => 'Friday',
                'date' => '28 March 2025',
                'states' => 'Chhattisgarh',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Good Friday',
                'day' => 'Saturday',
                'date' => '29 March 2025',
                'states' => 'Assam, Bihar, Goa, Jharkhand, Madhya Pradesh, Manipur, Mizoram, Nagaland, Sikkim, Tamil Nadu, Uttar Pradesh, Uttarakhand, West Bengal, Jammu and Kashmir, Delhi',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Ugadi',
                'day' => 'Sunday',
                'date' => '30 March 2025',
                'states' => 'Gujarat, Karnataka, Rajasthan, Telangana',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Gudi Padwa',
                'day' => 'Sunday',
                'date' => '30 March 2025',
                'states' => 'Gujarat, Maharashtra',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Telugu New Year',
                'day' => 'Sunday',
                'date' => '30 March 2025',
                'states' => 'Tamil Nadu',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Easter',
                'day' => 'Sunday',
                'date' => '30 March 2025',
                'states' => 'Kerala',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Idul Fitr',
                'day' => 'Monday',
                'date' => '31 March 2025',
                'states' => 'Multiple states across India',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]; */

        /* $data = [
            [
                'holiday_name' => 'Odisha Day',
                'day' => 'Tuesday',
                'date' => '01 April 2025',
                'states' => 'Odisha, Punjab',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Sarhul',
                'day' => 'Tuesday',
                'date' => '01 April 2025',
                'states' => 'Jharkhand',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Idul Fitr Holiday',
                'day' => 'Tuesday',
                'date' => '01 April 2025',
                'states' => 'Telangana',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => "Babu Jag Jivan Ram's Birthday",
                'day' => 'Saturday',
                'date' => '05 April 2025',
                'states' => 'Telangana, Andhra Pradesh',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Ram Navami',
                'day' => 'Sunday',
                'date' => '06 April 2025',
                'states' => 'Andhra Pradesh, Bihar, Chhattisgarh, Gujarat, Haryana, Himachal Pradesh, Madhya Pradesh, Maharashtra, Manipur, Odisha, Punjab, Rajasthan, Sikkim, Telangana, Uttarakhand, Uttar Pradesh',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Ugadi Festival',
                'day' => 'Wednesday',
                'date' => '09 April 2025',
                'states' => 'Telangana, Andhra Pradesh, Goa',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Gudi Padwa',
                'day' => 'Wednesday',
                'date' => '09 April 2025',
                'states' => 'Maharashtra',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => "Telugu New Year's Day",
                'day' => 'Wednesday',
                'date' => '09 April 2025',
                'states' => 'Tamil Nadu',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Mahavir Jayanti',
                'day' => 'Thursday',
                'date' => '10 April 2025',
                'states' => 'Chhattisgarh, Haryana, Jharkhand, Karnataka, Kerala, Madhya Pradesh, Maharashtra, Mizoram, Punjab, Rajasthan, Tamil Nadu',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Idul Fitr',
                'day' => 'Thursday',
                'date' => '10 April 2025',
                'states' => 'Assam, Rajasthan, Uttar Pradesh, Uttarakhand, West Bengal, Jammu and Kashmir, Delhi',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Id-Ul-Fitr',
                'day' => 'Friday',
                'date' => '11 April 2025',
                'states' => 'Goa, Telangana, Tripura, Uttar Pradesh, West Bengal, Jammu and Kashmir, Delhi',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Khutub-E-Ramzan',
                'day' => 'Friday',
                'date' => '11 April 2025',
                'states' => 'Karnataka',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Bohag Bihu',
                'day' => 'Sunday',
                'date' => '13 April 2025',
                'states' => 'Assam',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Cheiraoba',
                'day' => 'Sunday',
                'date' => '13 April 2025',
                'states' => 'Manipur',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Maha Vishuba Sankranti',
                'day' => 'Sunday',
                'date' => '13 April 2025',
                'states' => 'Odisha, Punjab',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Rongali Bihu',
                'day' => 'Monday',
                'date' => '14 April 2025',
                'states' => 'Assam',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Dr B R Ambedkar Jayanti',
                'day' => 'Monday',
                'date' => '14 April 2025',
                'states' => 'Andhra Pradesh, Bihar, Goa, Jharkhand, Maharashtra, Sikkim, Tamil Nadu, Telangana, Uttarakhand, Uttar Pradesh, Chhattisgarh, Gujarat, Haryana, Himachal Pradesh, Karnataka, Madhya Pradesh, Odisha, Punjab, Rajasthan',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Bihu',
                'day' => 'Monday',
                'date' => '14 April 2025',
                'states' => 'Arunachal Pradesh',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Cheiraoba',
                'day' => 'Monday',
                'date' => '14 April 2025',
                'states' => 'Manipur',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Vaisakhi',
                'day' => 'Monday',
                'date' => '14 April 2025',
                'states' => 'Punjab, Haryana, Himachal Pradesh',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Puthandu',
                'day' => 'Monday',
                'date' => '14 April 2025',
                'states' => 'Tamil Nadu',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Biju Festival',
                'day' => 'Monday',
                'date' => '14 April 2025',
                'states' => 'Tripura',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Bohag Bihu',
                'day' => 'Monday',
                'date' => '14 April 2025',
                'states' => 'Assam',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Biju Festival',
                'day' => 'Tuesday',
                'date' => '15 April 2025',
                'states' => 'Tripura',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Himachal Day',
                'day' => 'Tuesday',
                'date' => '15 April 2025',
                'states' => 'Himachal Pradesh',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Bohag Bihu',
                'day' => 'Tuesday',
                'date' => '15 April 2025',
                'states' => 'Assam',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Ramzan Holiday',
                'day' => 'Wednesday',
                'date' => '16 April 2025',
                'states' => 'Tripura',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Bohag Bihu',
                'day' => 'Wednesday',
                'date' => '16 April 2025',
                'states' => 'Assam',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Basava Jayanti',
                'day' => 'Wednesday',
                'date' => '30 April 2025',
                'states' => 'Karnataka, Kerala',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]; */


        /* $data = [
            [
                'holiday_name' => 'May Day',
                'day' => 'Thursday',
                'date' => '01 May 2025',
                'states' => 'Assam, Bihar, Goa, Gujarat, Karnataka, Kerala, Manipur, Tamil Nadu, Telangana, Tripura, West Bengal, Jammu and Kashmir, Delhi',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Maharashtra Day',
                'day' => 'Thursday',
                'date' => '01 May 2025',
                'states' => 'Maharashtra',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Guru Rabindranath Jayanti',
                'day' => 'Thursday',
                'date' => '08 May 2025',
                'states' => 'West Bengal, Tripura, Jammu and Kashmir, Delhi',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Buddha Purnima',
                'day' => 'Monday',
                'date' => '12 May 2025',
                'states' => 'Arunachal Pradesh, Chhattisgarh, Gujarat, Jharkhand, Madhya Pradesh, Maharashtra, Manipur, Mizoram, Odisha, Sikkim, Tamil Nadu, Tripura, Uttarakhand, West Bengal',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'State Day',
                'day' => 'Friday',
                'date' => '16 May 2025',
                'states' => 'Sikkim',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Buddha Poornima',
                'day' => 'Friday',
                'date' => '23 May 2025',
                'states' => 'Assam, Mizoram, Uttar Pradesh, Uttarakhand, West Bengal, Jammu and Kashmir, Delhi',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Kazi Nazrul Islam Jayanti',
                'day' => 'Monday',
                'date' => '26 May 2025',
                'states' => 'Tripura',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Maharana Pratap Jayanti',
                'day' => 'Thursday',
                'date' => '29 May 2025',
                'states' => 'Haryana, Himachal Pradesh, Rajasthan',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]; */


        /* $data = [
            [
                'holiday_name' => 'Bakrid / Eid al Adha',
                'day' => 'Sunday',
                'date' => '07 June 2025',
                'states' => 'Andhra Pradesh, Arunachal Pradesh, Bihar, Chhattisgarh, Gujarat, Haryana, Himachal Pradesh, Jharkhand, Karnataka, Madhya Pradesh, Maharashtra, Manipur, Meghalaya, Mizoram, Nagaland, Odisha, Punjab, Rajasthan, Sikkim, Tamil Nadu, Telangana, Tripura',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Sant Guru Kabir Jayanti',
                'day' => 'Wednesday',
                'date' => '11 June 2025',
                'states' => 'Chattisgarh, Haryana, Punjab, Himachal Pradesh',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Pahili Raja',
                'day' => 'Saturday',
                'date' => '14 June 2025',
                'states' => 'Odisha, Punjab',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Yma Day',
                'day' => 'Sunday',
                'date' => '15 June 2025',
                'states' => 'Mizoram',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Pahili Raja',
                'day' => 'Sunday',
                'date' => '15 June 2025',
                'states' => 'Odisha, Punjab',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Raja Sankranti',
                'day' => 'Monday',
                'date' => '16 June 2025',
                'states' => 'Odisha, Sikkim',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Eid-Ul Zuha',
                'day' => 'Tuesday',
                'date' => '17 June 2025',
                'states' => 'Assam, Chattisgarh, Goa, Gujarat, Mizoram, Punjab, Tripura, Tamil Nadu, Uttar Pradesh, Uttarakhand, West Bengal, Jammu and Kashmir, Delhi',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Eid al-Adha',
                'day' => 'Tuesday',
                'date' => '24 June 2025',
                'states' => 'Kerala',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Ratha Yatra',
                'day' => 'Friday',
                'date' => '27 June 2025',
                'states' => 'Manipur, Odisha, Punjab, Sikkim',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Remna Ni',
                'day' => 'Monday',
                'date' => '30 June 2025',
                'states' => 'Mizoram',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]; */


        /* $data = [
            [
                'holiday_name' => 'Behdingkhlam',
                'day' => 'Thursday',
                'date' => '03 July 2025',
                'states' => 'Meghalaya',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Kharchi Puja',
                'day' => 'Thursday',
                'date' => '03 July 2025',
                'states' => 'Tripura',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'MHIP day',
                'day' => 'Sunday',
                'date' => '06 July 2025',
                'states' => 'Mizoram',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Muharram',
                'day' => 'Sunday',
                'date' => '06 July 2025',
                'states' => 'Andhra Pradesh, Himachal Pradesh, Jharkhand, Karnataka, Kerala, Madhya Pradesh, Maharashtra, Manipur, Mizoram, Odisha, Punjab, Rajasthan, Sikkim, Tamil Nadu, Telangana, Tripura',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Ratha Yatra',
                'day' => 'Tuesday',
                'date' => '08 July 2025',
                'states' => 'Manipur',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Jhulan Purnima',
                'day' => 'Tuesday',
                'date' => '08 July 2025',
                'states' => 'Punjab',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Bhanu Jayanti',
                'day' => 'Sunday',
                'date' => '13 July 2025',
                'states' => 'Sikkim',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'U Tirot Sing Day',
                'day' => 'Thursday',
                'date' => '17 July 2025',
                'states' => 'Meghalaya',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Muharram',
                'day' => 'Thursday',
                'date' => '17 July 2025',
                'states' => 'Rajasthan, West Bengal, Jammu and Kashmir, Delhi',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Ker Puja',
                'day' => 'Saturday',
                'date' => '19 July 2025',
                'states' => 'Tripura',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Bonalu',
                'day' => 'Monday',
                'date' => '21 July 2025',
                'states' => 'Telangana',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Karkidaka Vavu',
                'day' => 'Friday',
                'date' => '25 July 2025',
                'states' => 'Kerala',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Haryali Teej',
                'day' => 'Sunday',
                'date' => '27 July 2025',
                'states' => 'Haryana',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Bonalu',
                'day' => 'Tuesday',
                'date' => '29 July 2025',
                'states' => 'Telangana',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Shaheed Udham Singh Martyrdom Day',
                'day' => 'Thursday',
                'date' => '31 July 2025',
                'states' => 'Haryana',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]; */

        /* $data = [
            [
                'holiday_name' => 'Ker Puja',
                'day' => 'Sunday',
                'date' => '03 August 2025',
                'states' => 'Tripura',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Tendong Lho Rum Faat',
                'day' => 'Friday',
                'date' => '08 August 2025',
                'states' => 'Sikkim, Odisha',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Raksha Bandhan',
                'day' => 'Saturday',
                'date' => '09 August 2025',
                'states' => 'Chattisgarh, Haryana, Madhya Pradesh, Rajasthan, Uttarakhand, Uttar Pradesh',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Patriots Day',
                'day' => 'Wednesday',
                'date' => '13 August 2025',
                'states' => 'Manipur',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Independence Day',
                'day' => 'Friday',
                'date' => '15 August 2025',
                'states' => 'Andhra Pradesh, Arunachal Pradesh, Assam, Bihar, Chattisgarh, Goa, Gujarat, Haryana, Himachal Pradesh, Jharkhand, Karnataka, Kerala, Madhya Pradesh, Maharashtra, Manipur, Meghalaya, Mizoram, Nagaland, Odisha, Punjab, Rajasthan, Sikkim, Tamil Nadu, Telangana, Tripura, Uttarakhand, Uttar Pradesh, West Bengal, Jammu and Kashmir, Delhi',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Parsi New Year (Shahenshahi)',
                'day' => 'Friday',
                'date' => '15 August 2025',
                'states' => 'Maharashtra',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Janmashtami',
                'day' => 'Saturday',
                'date' => '16 August 2025',
                'states' => 'Andhra Pradesh, Bihar, Chattisgarh, Goa, Haryana, Himachal Pradesh, Jharkhand, Madhya Pradesh, Manipur, Meghalaya, Mizoram, Nagaland, Odisha, Punjab, Rajasthan, Sikkim, Tamil Nadu, Telangana, Tripura',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Parsi New Year',
                'day' => 'Saturday',
                'date' => '16 August 2025',
                'states' => 'Gujarat, Maharashtra',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Rakshabandhan',
                'day' => 'Tuesday',
                'date' => '19 August 2025',
                'states' => 'Gujarat, Himachal Pradesh, Rajasthan, Uttar Pradesh, Uttarakhand',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Shri Krishna Astami',
                'day' => 'Tuesday',
                'date' => '26 August 2025',
                'states' => 'Gujarat, Sikkim, Rajasthan, Uttar Pradesh, Uttarakhand',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Hartalika Teej',
                'day' => 'Tuesday',
                'date' => '26 August 2025',
                'states' => 'Chattisgarh, Sikkim',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Ganesh Chaturthi',
                'day' => 'Tuesday',
                'date' => '26 August 2025',
                'states' => 'Karnataka, Kerala',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Ganesh Chaturthi',
                'day' => 'Wednesday',
                'date' => '27 August 2025',
                'states' => 'Andhra Pradesh, Goa, Gujarat, Maharashtra, Odisha, Punjab, Sikkim, Tamil Nadu, Telangana',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Ganesh Chaturthi',
                'day' => 'Thursday',
                'date' => '28 August 2025',
                'states' => 'Goa, Gujarat',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Nuakhai',
                'day' => 'Thursday',
                'date' => '28 August 2025',
                'states' => 'Odisha, Punjab, Sikkim',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]; */

        /* $data = [
            [
                'holiday_name' => 'Teja Dashmi',
                'day' => 'Tuesday',
                'date' => '02 September 2024',
                'states' => 'Rajasthan',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Tithi of Srimanta Shankardev',
                'day' => 'Thursday',
                'date' => '04 September 2024',
                'states' => 'Assam',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Tithi of Srimanta Shankardev',
                'day' => 'Friday',
                'date' => '05 September 2024',
                'states' => 'Assam',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Eid e Milad',
                'day' => 'Friday',
                'date' => '05 September 2025',
                'states' => 'Andhra Pradesh, Haryana, Jharkhand, Karnataka, Madhya Pradesh, Kerala, Maharashtra, Mizoram, Nagaland, Odisha, Punjab, Rajasthan, Sikkim, Tamil Nadu, Telangana, Tripura, Uttarakhand',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Vinayak Chaturthi',
                'day' => 'Sunday',
                'date' => '07 September 2025',
                'states' => 'Goa, Maharashtra, Odisha, Tamil Nadu, Telangana',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Indra Jatra',
                'day' => 'Sunday',
                'date' => '07 September 2025',
                'states' => 'Sikkim',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Ganesh Chaturthi',
                'day' => 'Monday',
                'date' => '08 September 2025',
                'states' => 'Goa',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Ram Dev Jayanti/ Teja Dashmi',
                'day' => 'Saturday',
                'date' => '13 September 2025',
                'states' => 'Rajasthan',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Karma Puja',
                'day' => 'Sunday',
                'date' => '14 September 2025',
                'states' => 'Jharkhand',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Milad-Un-Nabi',
                'day' => 'Tuesday',
                'date' => '16 September 2025',
                'states' => 'Andhra Pradesh, Manipur',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Id-E-Milad',
                'day' => 'Tuesday',
                'date' => '16 September 2025',
                'states' => 'Gujarat, Uttarakhand, Uttar Pradesh',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Onam',
                'day' => 'Tuesday',
                'date' => '16 September 2025',
                'states' => 'Kerala',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Mahalaya Amavasye',
                'day' => 'Sunday',
                'date' => '21 September 2025',
                'states' => 'Karnataka, Kerala, Odisha, Punjab, Sikkim, Tripura, West Bengal',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Maharaja Agrasen Jayanti',
                'day' => 'Monday',
                'date' => '22 September 2025',
                'states' => 'Haryana',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Ghatasthapana',
                'day' => 'Monday',
                'date' => '22 September 2025',
                'states' => 'Rajasthan',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'First Day of Bathukamma',
                'day' => 'Monday',
                'date' => '22 September 2025',
                'states' => 'Telangana',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Shaheedi Diwas',
                'day' => 'Tuesday',
                'date' => '23 September 2025',
                'states' => 'Haryana',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Heroes\' Martyrdom Day',
                'day' => 'Tuesday',
                'date' => '23 September 2025',
                'states' => 'Haryana',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Maha Saptami',
                'day' => 'Monday',
                'date' => '29 September 2025',
                'states' => 'Assam, Odisha, Punjab, Sikkim, Tripura, West Bengal',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Maha Ashtami',
                'day' => 'Tuesday',
                'date' => '30 September 2025',
                'states' => 'Andhra Pradesh, Assam, Jharkhand, Manipur, Odisha, Punjab, Rajasthan, Sikkim, Telangana, Tripura',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]; */

        /* $data = [
            [
                'holiday_name' => 'Maha Navami',
                'day' => 'Wednesday',
                'date' => '01 October 2025',
                'states' => 'Bihar, Jharkhand, Karnataka, Kerala, Meghalaya, Nagaland, Odisha, Sikkim, Tamil Nadu, Telangana, Tripura, West Bengal',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Mahatma Gandhi Jayanthi',
                'day' => 'Thursday',
                'date' => '02 October 2025',
                'states' => 'Andhra Pradesh, Arunachal Pradesh, Assam, Bihar, Chattisgarh, Goa, Gujarat, Haryana, Himachal Pradesh, Jharkhand, Karnataka, Kerala, Madhya Pradesh, Maharashtra, Manipur, Meghalaya, Mizoram, Nagaland, Punjab, Rajasthan, Sikkim, Tamil Nadu, Telangana, Tripura, Uttarakhand, Uttar Pradesh, West Bengal, Jammu and Kashmir, Delhi',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Mahalaya',
                'day' => 'Thursday',
                'date' => '02 October 2025',
                'states' => 'West Bengal',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Vijaya Dashami',
                'day' => 'Thursday',
                'date' => '02 October 2025',
                'states' => 'Bihar, Karnataka, Kerala, Maharashtra, Meghalaya',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Maharaja Agrasen Jayanti',
                'day' => 'Friday',
                'date' => '03 October 2025',
                'states' => 'Haryana',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Ghatasthapana',
                'day' => 'Friday',
                'date' => '03 October 2025',
                'states' => 'Rajasthan',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Lakshmi Puja',
                'day' => 'Monday',
                'date' => '06 October 2025',
                'states' => 'Odisha, Punjab, Sikkim, Tripura, West Bengal',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Maharishi Valmiki Jayanti',
                'day' => 'Tuesday',
                'date' => '07 October 2025',
                'states' => 'Haryana, Himachal Pradesh, Karnataka, Kerala, Madhya Pradesh, Punjab',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Maha Saptami',
                'day' => 'Friday',
                'date' => '10 October 2025',
                'states' => 'Assam, Tripura, West Bengal, Jammu and Kashmir, Delhi',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Durga Puja',
                'day' => 'Saturday',
                'date' => '11 October 2025',
                'states' => 'Manipur, Sikkim',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Dussera (Maha Ashtami)',
                'day' => 'Saturday',
                'date' => '11 October 2025',
                'states' => 'Assam, Rajasthan',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Ayudha Pooja',
                'day' => 'Saturday',
                'date' => '11 October 2025',
                'states' => 'Tamil Nadu',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Vijaya Dashami',
                'day' => 'Saturday',
                'date' => '11 October 2025',
                'states' => 'Gujarat',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Wangala Festival',
                'day' => 'Saturday',
                'date' => '11 October 2025',
                'states' => 'Meghalaya',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Maha Navami',
                'day' => 'Saturday',
                'date' => '11 October 2025',
                'states' => 'Tamil Nadu',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Durga Puja',
                'day' => 'Sunday',
                'date' => '12 October 2025',
                'states' => 'Assam, Bihar, West Bengal',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Dussehra/Vijaya Dashmi',
                'day' => 'Sunday',
                'date' => '12 October 2025',
                'states' => 'Chattisgarh, Goa, Gujarat, Madhya Pradesh, Uttar Pradesh, Rajasthan, Tamil Nadu',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Vijaya Dashami',
                'day' => 'Sunday',
                'date' => '12 October 2025',
                'states' => 'Chattisgarh, Goa',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Janmostav of Srimanta Shankardev',
                'day' => 'Monday',
                'date' => '13 October 2025',
                'states' => 'Assam',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Vijaya Dashami',
                'day' => 'Monday',
                'date' => '13 October 2025',
                'states' => 'Goa, Tamil Nadu, Uttar Pradesh, Uttarakhand, West Bengal, Jammu and Kashmir, Delhi',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Laxmi Puja',
                'day' => 'Thursday',
                'date' => '16 October 2025',
                'states' => 'Tripura, West Bengal, Jammu and Kashmir, Delhi',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Maharishi Valmiki Jayanti',
                'day' => 'Friday',
                'date' => '17 October 2025',
                'states' => 'Himachal Pradesh, Karnataka',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Kati Bihu',
                'day' => 'Friday',
                'date' => '17 October 2025',
                'states' => 'Assam',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Karva Chauth',
                'day' => 'Monday',
                'date' => '20 October 2025',
                'states' => 'Himachal Pradesh',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Diwali',
                'day' => 'Monday',
                'date' => '20 October 2025',
                'states' => 'Arunachal Pradesh, Assam, Bihar, Chattisgarh, Gujarat, Karnataka, Maharashtra, Meghalaya, Nagaland',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Naraka Chaturdasi',
                'day' => 'Monday',
                'date' => '20 October 2025',
                'states' => 'Karnataka, Kerala',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Diwali',
                'day' => 'Monday',
                'date' => '20 October 2025',
                'states' => 'Kerala',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Diwali',
                'day' => 'Tuesday',
                'date' => '21 October 2025',
                'states' => 'Andhra Pradesh, Bihar, Goa, Gujarat, Haryana, Himachal Pradesh, Jharkhand, Karnataka, Madhya Pradesh, Maharashtra, Manipur, Mizoram, Nagaland, Odisha, Punjab, Sikkim, Tamil Nadu, Telangana, Tripura',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Diwali',
                'day' => 'Wednesday',
                'date' => '22 October 2025',
                'states' => 'Haryana, Karnataka, Maharashtra, Rajasthan, Uttarakhand, Uttar Pradesh',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Vikram Samvat New Year',
                'day' => 'Wednesday',
                'date' => '22 October 2025',
                'states' => 'Gujarat',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Bhai Dooj',
                'day' => 'Thursday',
                'date' => '23 October 2025',
                'states' => 'Gujarat, Sikkim, Uttarakhand, Uttar Pradesh',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Ningol Chakkouba',
                'day' => 'Friday',
                'date' => '24 October 2025',
                'states' => 'Manipur',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Chhath Puja',
                'day' => 'Monday',
                'date' => '27 October 2025',
                'states' => 'Bihar, Jharkhand',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Chhath Puja',
                'day' => 'Tuesday',
                'date' => '28 October 2025',
                'states' => 'Bihar',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Diwali',
                'day' => 'Friday',
                'date' => '31 October 2025',
                'states' => 'Assam, Goa, Punjab, Uttarakhand, Uttar Pradesh, West Bengal, Jammu and Kashmir, Delhi',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => "Sardar Vallabhbhai Patel's Birthday",
                'day' => 'Friday',
                'date' => '31 October 2025',
                'states' => 'Gujarat',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Narak Chaturdasi',
                'day' => 'Friday',
                'date' => '31 October 2025',
                'states' => 'Odisha',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Kali Puja',
                'day' => 'Friday',
                'date' => '31 October 2025',
                'states' => 'West Bengal',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]; */

        /* $data = [
            [
                'holiday_name' => 'Kannada Rajyothsava',
                'day' => 'Saturday',
                'date' => '01 November 2025',
                'states' => 'Karnataka, Kerala',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Diwali',
                'day' => 'Saturday',
                'date' => '01 November 2025',
                'states' => 'Assam, Sikkim, Rajasthan, Uttar Pradesh, Uttarakhand, West Bengal, Jammu and Kashmir, Delhi',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Haryana Day',
                'day' => 'Saturday',
                'date' => '01 November 2025',
                'states' => 'Haryana',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Diwali Amavasaya (Laxmi Pujan)',
                'day' => 'Saturday',
                'date' => '01 November 2025',
                'states' => 'Maharashtra',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Kut',
                'day' => 'Saturday',
                'date' => '01 November 2025',
                'states' => 'Manipur',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Vikram Savant, New Year Day',
                'day' => 'Sunday',
                'date' => '02 November 2025',
                'states' => 'Gujarat',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Diwali (Bali Pratipada)',
                'day' => 'Sunday',
                'date' => '02 November 2025',
                'states' => 'Maharashtra',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Gowardhan Puja',
                'day' => 'Sunday',
                'date' => '02 November 2025',
                'states' => 'Uttarakhand, Uttar Pradesh',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Ningol Chakkouba',
                'day' => 'Monday',
                'date' => '03 November 2025',
                'states' => 'Manipur',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Diwali',
                'day' => 'Monday',
                'date' => '03 November 2025',
                'states' => 'Sikkim',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Bhai Duj',
                'day' => 'Monday',
                'date' => '03 November 2025',
                'states' => 'Uttar Pradesh, Rajasthan, Uttarakhand',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Guru Nanak Jayanti',
                'day' => 'Wednesday',
                'date' => '05 November 2025',
                'states' => 'Arunachal Pradesh, Chattisgarh, Gujarat, Haryana, Himachal Pradesh, Jharkhand, Madhya Pradesh, Maharashtra, Manipur, Mizoram, Nagaland, Punjab, Rajasthan, Tamil Nadu, Tripura, Uttarakhand, Uttar Pradesh',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Karthika Purnima',
                'day' => 'Wednesday',
                'date' => '05 November 2025',
                'states' => 'Odisha, Punjab, Sikkim',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Chhat Puja',
                'day' => 'Friday',
                'date' => '07 November 2025',
                'states' => 'Assam, West Bengal',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Wangala Festival',
                'day' => 'Friday',
                'date' => '07 November 2025',
                'states' => 'Meghalaya',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Chhat Puja',
                'day' => 'Saturday',
                'date' => '08 November 2025',
                'states' => 'Bihar',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Kanakadasa Jayanti',
                'day' => 'Saturday',
                'date' => '08 November 2025',
                'states' => 'Karnataka, Kerala',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Lhabab Duchen',
                'day' => 'Tuesday',
                'date' => '11 November 2025',
                'states' => 'Sikkim',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Egas bagval',
                'day' => 'Wednesday',
                'date' => '12 November 2025',
                'states' => 'Uttarakhand',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Guru Nanak Jayanti',
                'day' => 'Saturday',
                'date' => '15 November 2025',
                'states' => 'Assam, Rajasthan, Uttar Pradesh, Uttarakhand, West Bengal, Jammu and Kashmir, Delhi',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Garia Puja',
                'day' => 'Thursday',
                'date' => '20 November 2025',
                'states' => 'Tripura',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Seng Kut Snem',
                'day' => 'Sunday',
                'date' => '23 November 2025',
                'states' => 'Meghalaya',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]; */

        /* $data = [
            [
                'holiday_name' => 'Indigenous Faith Day',
                'day' => 'Monday',
                'date' => '01 December 2025',
                'states' => 'Arunachal Pradesh',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Feast of St. Francis Xavier',
                'day' => 'Wednesday',
                'date' => '03 December 2025',
                'states' => 'Goa',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Pa Togan Nengminja',
                'day' => 'Friday',
                'date' => '12 December 2025',
                'states' => 'Meghalaya',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Guru Ghasidas Jayanti',
                'day' => 'Thursday',
                'date' => '18 December 2025',
                'states' => 'Chattisgarh',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Death Anniversary of U SoSo Tham',
                'day' => 'Thursday',
                'date' => '18 December 2025',
                'states' => 'Meghalaya',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Goa Liberation Day',
                'day' => 'Friday',
                'date' => '19 December 2025',
                'states' => 'Goa',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Christmas',
                'day' => 'Wednesday',
                'date' => '24 December 2025',
                'states' => 'Meghalaya, Mizoram',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Christmas',
                'day' => 'Thursday',
                'date' => '25 December 2025',
                'states' => 'Andhra Pradesh, Arunachal Pradesh, Assam, Bihar, Chattisgarh, Goa, Gujarat, Haryana, Himachal Pradesh, Jharkhand, Karnataka, Kerala, Madhya Pradesh, Maharashtra, Manipur, Meghalaya, Mizoram, Nagaland, Odisha, Punjab, Rajasthan, Sikkim, Tamil Nadu, Telangana, Tripura, Uttarakhand, Uttar Pradesh, West Bengal, Jammu and Kashmir, Delhi',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Christmas',
                'day' => 'Friday',
                'date' => '26 December 2025',
                'states' => 'Meghalaya, Mizoram, Telangana',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Shaheed Udham Singh Jayanti',
                'day' => 'Friday',
                'date' => '26 December 2025',
                'states' => 'Haryana',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Guru Gobind Singh Jayanti',
                'day' => 'Saturday',
                'date' => '27 December 2025',
                'states' => 'Haryana, Punjab, Himachal Pradesh',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'U Kiang Nangbah',
                'day' => 'Tuesday',
                'date' => '30 December 2025',
                'states' => 'Meghalaya',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'Tamu Losar',
                'day' => 'Wednesday',
                'date' => '30 December 2025',
                'states' => 'Sikkim',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'holiday_name' => 'New Year\'s Day',
                'day' => 'Wednesday',
                'date' => '31 December 2025',
                'states' => 'Mizoram, Manipur',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]; */

        DB::table('state_wise_bank_holiday')->insert($data);

        return "Data Store successfully.";

        /* $states = [
            'Andhra Pradesh', 'Arunachal Pradesh', 'Assam', 'Bihar', 'Chattisgarh', 'Goa', 'Gujarat', 'Haryana',
            'Himachal Pradesh', 'Jharkhand', 'Karnataka', 'Kerala', 'Madhya Pradesh', 'Maharashtra', 'Manipur',
            'Meghalaya', 'Mizoram', 'Nagaland', 'Odisha', 'Punjab', 'Rajasthan', 'Sikkim', 'Tamil Nadu',
            'Telangana', 'Tripura', 'Uttarakhand', 'Uttar Pradesh', 'West Bengal', 'Jammu and Kashmir', 'Delhi',
        ];

        $data = [];

        foreach ($states as $state) {
            $data[] = [
                'name' => $state,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('states')->insert($data); */

    }

    public function holiday_notification()
    {
        $notificationSendData = [
            'notification_title' => "Bank Alert! Today is a Bank Holiday!",
            'notification_description' => "Banks are closed today as per the calendar.",
            'notification_image' => asset('public/assets/img/logo.png'),
        ];
        \Log::info("Notification sent Completed.");

        $send_notification = ApplicationNotification::sendOneSignalNotification($notificationSendData);
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


