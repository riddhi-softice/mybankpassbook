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

    public function BankHolidayStoreState() 
    { 
        // Scrap data using chatgpt
        // https://cleartax.in/s/bank-holidays-list-2025 

//        $data = [
//     [
//      
//         'holiday_name' => 'New Year',
//         'day' => 'Wednesday',
//         'date' => '01 Jan 2025',
//         'states' => 'Arunachal Pradesh, Assam, Goa, Manipur, Meghalaya, Mizoram, Nagaland, Odisha, Rajasthan, Sikkim, Tamil Nadu, Telangana',
//         'created_at' => now(),
//         'updated_at' => now(),
//     ],
//     [
//      
//         'holiday_name' => 'New Year Holiday',
//         'day' => 'Thursday',
//         'date' => '02 Jan 2025',
//         'states' => 'Mizoram',
//         'created_at' => now(),
//         'updated_at' => now(),
//     ],
//     [
//      
//         'holiday_name' => 'Mannam Jayanthi',
//         'day' => 'Thursday',
//         'date' => '02 Jan 2025',
//         'states' => 'Kerala',
//         'created_at' => now(),
//         'updated_at' => now(),
//     ],
//     [
//      
//         'holiday_name' => 'Guru Gobind Singh Jayanti',
//         'day' => 'Monday',
//         'date' => '06 Jan 2025',
//         'states' => 'Chandigarh',
//         'created_at' => now(),
//         'updated_at' => now(),
//     ],
//     [
//      
//         'holiday_name' => 'Missionary Day',
//         'day' => 'Saturday',
//         'date' => '11 Jan 2025',
//         'states' => 'Mizoram',
//         'created_at' => now(),
//         'updated_at' => now(),
//     ],
//     [
//      
//         'holiday_name' => 'Imoinu Iratpa',
//         'day' => 'Saturday',
//         'date' => '11 Jan 2025',
//         'states' => 'Manipur',
//         'created_at' => now(),
//         'updated_at' => now(),
//     ],
//     [
//      
//         'holiday_name' => 'Makar Sankranti / Pongal / Magh Bihu',
//         'day' => 'Tuesday',
//         'date' => '14 Jan 2025',
//         'states' => 'Andhra Pradesh, Gujarat, Karnataka, Odisha, Tamil Nadu, Telangana, West Bengal',
//         'created_at' => now(),
//         'updated_at' => now(),
//     ],
//     [
//      
//         'holiday_name' => 'Thiruvalluvar Day',
//         'day' => 'Wednesday',
//         'date' => '15 Jan 2025',
//         'states' => 'Tamil Nadu',
//         'created_at' => now(),
//         'updated_at' => now(),
//     ],
//     [
//      
//         'holiday_name' => 'Uzhavar Thirunal',
//         'day' => 'Thursday',
//         'date' => '16 Jan 2025',
//         'states' => 'Tamil Nadu',
//         'created_at' => now(),
//         'updated_at' => now(),
//     ],
//     [
//      
//         'holiday_name' => 'Netaji Subhas Chandra Bose Jayanti',
//         'day' => 'Thursday',
//         'date' => '23 Jan 2025',
//         'states' => 'Odisha, Tripura, West Bengal',
//         'created_at' => now(),
//         'updated_at' => now(),
//     ],
//     [
//      
//         'holiday_name' => 'Republic Day',
//         'day' => 'Sunday',
//         'date' => '26 Jan 2025',
//         'states' => 'All States',
//         'created_at' => now(),
//         'updated_at' => now(),
//     ],
// ];


/*$data = [
    [
        'date' => '03 Feb 2025',
        'day' => 'Monday',
        'holiday_name' => 'Vasant Panchami',
        'states' => 'Haryana, Odisha, Punjab, Sikkim, Tamil Nadu, Tripura, West Bengal',
        'created_at' => now(),
        'updated_at' => now(),
    ],
    [
        'date' => '10 Feb 2025',
        'day' => 'Monday',
        'holiday_name' => 'Losar',
        'states' => 'Sikkim',
        'created_at' => now(),
        'updated_at' => now(),
    ],
    [
        'date' => '12 Feb 2025',
        'day' => 'Wednesday',
        'holiday_name' => 'Guru Ravidas Jayanti',
        'states' => 'Haryana, Himachal Pradesh, Madhya Pradesh, Mizoram, Punjab',
        'created_at' => now(),
        'updated_at' => now(),
    ],
    [
        'date' => '14 Feb 2025',
        'day' => 'Friday',
        'holiday_name' => 'Vasanta Panchami',
        'states' => 'Odisha, West Bengal, Jammu and Kashmir, Delhi',
        'created_at' => now(),
        'updated_at' => now(),
    ],
    [
        'date' => '14 Feb 2025',
        'day' => 'Friday',
        'holiday_name' => 'Saraswati Puja',
        'states' => 'Tripura, West Bengal',
        'created_at' => now(),
        'updated_at' => now(),
    ],
    [
        'date' => '14 Feb 2025',
        'day' => 'Friday',
        'holiday_name' => 'Holi',
        'states' => 'Meghalaya, Nagaland',
        'created_at' => now(),
        'updated_at' => now(),
    ],
    [
        'date' => '14 Feb 2025',
        'day' => 'Friday',
        'holiday_name' => 'Shab-E-Barat',
        'states' => 'Chattisgarh',
        'created_at' => now(),
        'updated_at' => now(),
    ],
    [
        'date' => '15 Feb 2025',
        'day' => 'Saturday',
        'holiday_name' => 'Lui-Ngai-Ni',
        'states' => 'Manipur',
        'created_at' => now(),
        'updated_at' => now(),
    ],
    [
        'date' => '19 Feb 2025',
        'day' => 'Wednesday',
        'holiday_name' => 'Chhatrapati Shivaji Maharaj Jayanti',
        'states' => 'Maharashtra',
        'created_at' => now(),
        'updated_at' => now(),
    ],
    [
        'date' => '20 Feb 2025',
        'day' => 'Thursday',
        'holiday_name' => 'Statehood Day',
        'states' => 'Arunachal Pradesh',
        'created_at' => now(),
        'updated_at' => now(),
    ],
    [
        'date' => '20 Feb 2025',
        'day' => 'Thursday',
        'holiday_name' => 'State Day',
        'states' => 'Mizoram',
        'created_at' => now(),
        'updated_at' => now(),
    ],
    [
        'date' => '25 Feb 2025',
        'day' => 'Tuesday',
        'holiday_name' => 'Maha Shivaratri',
        'states' => 'Karnataka, Kerala',
        'created_at' => now(),
        'updated_at' => now(),
    ],
    [
        'date' => '26 Feb 2025',
        'day' => 'Wednesday',
        'holiday_name' => 'Maha Shivaratri',
        'states' => 'Goa, Gujarat, Haryana, Himachal Pradesh, Jharkhand, Karnataka, Madhya Pradesh',
        'created_at' => now(),
        'updated_at' => now(),
    ],
]; */


/*$data = [
    [
        'holiday_name' => 'Chapchar Kut',
        'day' => 'Saturday',
        'date' => '01 Mar 2025',
        'states' => 'Mizoram',
        'created_at' => now(),
        'updated_at' => now(),
    ],
    [
        'holiday_name' => 'Panchayati Raj Divas',
        'day' => 'Wednesday',
        'date' => '05 Mar 2025',
        'states' => 'Odisha, Punjab, Sikkim',
        'created_at' => now(),
        'updated_at' => now(),
    ],
    [
        'holiday_name' => 'Chapchar Kut',
        'day' => 'Friday',
        'date' => '07 Mar 2025',
        'states' => 'Mizoram',
        'created_at' => now(),
        'updated_at' => now(),
    ],
    [
        'holiday_name' => 'Mahashivratri',
        'day' => 'Saturday',
        'date' => '08 Mar 2025',
        'states' => 'Andhra Pradesh, Bihar, Uttar Pradesh, Rajasthan, Uttarakhand',
        'created_at' => now(),
        'updated_at' => now(),
    ],
    [
        'holiday_name' => 'Holi',
        'day' => 'Friday',
        'date' => '14 Mar 2025',
        'states' => 'Arunachal Pradesh, Bihar, Chhattisgarh, Goa, Gujarat, Haryana, Himachal Pradesh, Jharkhand, Madhya Pradesh, Maharashtra, Meghalaya, Mizoram, Nagaland, Odisha, Punjab, Rajasthan, Sikkim, Tamil Nadu, Telangana, Tripura',
        'created_at' => now(),
        'updated_at' => now(),
    ],
    [
        'holiday_name' => 'Yaosang',
        'day' => 'Friday',
        'date' => '14 Mar 2025',
        'states' => 'Manipur',
        'created_at' => now(),
        'updated_at' => now(),
    ],
    [
        'holiday_name' => 'Yaosang 2nd Day',
        'day' => 'Friday',
        'date' => '14 Mar 2025',
        'states' => 'Manipur',
        'created_at' => now(),
        'updated_at' => now(),
    ],
    [
        'holiday_name' => 'Doljatra',
        'day' => 'Friday',
        'date' => '14 Mar 2025',
        'states' => 'West Bengal',
        'created_at' => now(),
        'updated_at' => now(),
    ],
    [
        'holiday_name' => 'Bihar Day',
        'day' => 'Saturday',
        'date' => '22 Mar 2025',
        'states' => 'Bihar',
        'created_at' => now(),
        'updated_at' => now(),
    ],
    [
        'holiday_name' => "Shaheed Bhagat Singh's Martyrdom Day",
        'day' => 'Sunday',
        'date' => '23 Mar 2025',
        'states' => 'Haryana',
        'created_at' => now(),
        'updated_at' => now(),
    ],
    [
        'holiday_name' => 'Dol Jatra',
        'day' => 'Tuesday',
        'date' => '25 Mar 2025',
        'states' => 'Assam, West Bengal, Jammu and Kashmir, Delhi',
        'created_at' => now(),
        'updated_at' => now(),
    ],
    [
        'holiday_name' => 'Dhulandi',
        'day' => 'Tuesday',
        'date' => '25 Mar 2025',
        'states' => 'Rajasthan',
        'created_at' => now(),
        'updated_at' => now(),
    ],
    [
        'holiday_name' => 'Holi',
        'day' => 'Tuesday',
        'date' => '25 Mar 2025',
        'states' => 'Andhra Pradesh, Assam, Goa, Rajasthan, Uttar Pradesh, Uttarakhand',
        'created_at' => now(),
        'updated_at' => now(),
    ],
    [
        'holiday_name' => 'Holi',
        'day' => 'Wednesday',
        'date' => '26 Mar 2025',
        'states' => 'Bihar, Odisha',
        'created_at' => now(),
        'updated_at' => now(),
    ],
    [
        'holiday_name' => 'Yaosang 2nd Day',
        'day' => 'Wednesday',
        'date' => '26 Mar 2025',
        'states' => 'Manipur',
        'created_at' => now(),
        'updated_at' => now(),
    ],
    [
        'holiday_name' => 'Holi',
        'day' => 'Thursday',
        'date' => '27 Mar 2025',
        'states' => 'Bihar',
        'created_at' => now(),
        'updated_at' => now(),
    ],
    [
        'holiday_name' => 'Jamat-Ul-Vida',
        'day' => 'Friday',
        'date' => '28 Mar 2025',
        'states' => 'Chhattisgarh',
        'created_at' => now(),
        'updated_at' => now(),
    ],
    [
        'holiday_name' => 'Good Friday',
        'day' => 'Saturday',
        'date' => '29 Mar 2025',
        'states' => 'Assam, Bihar, Goa, Jharkhand, Madhya Pradesh, Manipur, Mizoram, Nagaland, Sikkim, Tamil Nadu, Uttar Pradesh, Uttarakhand, West Bengal, Jammu and Kashmir, Delhi',
        'created_at' => now(),
        'updated_at' => now(),
    ],
    [
        'holiday_name' => 'Ugadi',
        'day' => 'Sunday',
        'date' => '30 Mar 2025',
        'states' => 'Gujarat, Karnataka, Rajasthan, Telangana',
        'created_at' => now(),
        'updated_at' => now(),
    ],
    [
        'holiday_name' => 'Gudi Padwa',
        'day' => 'Sunday',
        'date' => '30 Mar 2025',
        'states' => 'Gujarat, Maharashtra',
        'created_at' => now(),
        'updated_at' => now(),
    ],
    [
        'holiday_name' => 'Telugu New Year',
        'day' => 'Sunday',
        'date' => '30 Mar 2025',
        'states' => 'Tamil Nadu',
        'created_at' => now(),
        'updated_at' => now(),
    ],
    [
        'holiday_name' => 'Easter',
        'day' => 'Sunday',
        'date' => '30 Mar 2025',
        'states' => 'Kerala',
        'created_at' => now(),
        'updated_at' => now(),
    ],
    [
        'holiday_name' => 'Idul Fitr',
        'day' => 'Monday',
        'date' => '31 Mar 2025',
        'states' => 'Multiple states across India',
        'created_at' => now(),
        'updated_at' => now(),
    ],
]; */

/* $data = [
    [
        'holiday_name' => 'Odisha Day',
        'day' => 'Tuesday',
        'date' => '01 Apr 2025',
        'states' => 'Odisha, Punjab',
        'created_at' => now(),
        'updated_at' => now(),
    ],
    [
        'holiday_name' => 'Sarhul',
        'day' => 'Tuesday',
        'date' => '01 Apr 2025',
        'states' => 'Jharkhand',
        'created_at' => now(),
        'updated_at' => now(),
    ],
    [
        'holiday_name' => 'Idul Fitr Holiday',
        'day' => 'Tuesday',
        'date' => '01 Apr 2025',
        'states' => 'Telangana',
        'created_at' => now(),
        'updated_at' => now(),
    ],
    [
        'holiday_name' => "Babu Jag Jivan Ram's Birthday",
        'day' => 'Saturday',
        'date' => '05 Apr 2025',
        'states' => 'Telangana, Andhra Pradesh',
        'created_at' => now(),
        'updated_at' => now(),
    ],
    [
        'holiday_name' => 'Ram Navami',
        'day' => 'Sunday',
        'date' => '06 Apr 2025',
        'states' => 'Andhra Pradesh, Bihar, Chhattisgarh, Gujarat, Haryana, Himachal Pradesh, Madhya Pradesh, Maharashtra, Manipur, Odisha, Punjab, Rajasthan, Sikkim, Telangana, Uttarakhand, Uttar Pradesh',
        'created_at' => now(),
        'updated_at' => now(),
    ],
    [
        'holiday_name' => 'Ugadi Festival',
        'day' => 'Wednesday',
        'date' => '09 Apr 2025',
        'states' => 'Telangana, Andhra Pradesh, Goa',
        'created_at' => now(),
        'updated_at' => now(),
    ],
    [
        'holiday_name' => 'Gudi Padwa',
        'day' => 'Wednesday',
        'date' => '09 Apr 2025',
        'states' => 'Maharashtra',
        'created_at' => now(),
        'updated_at' => now(),
    ],
    [
        'holiday_name' => "Telugu New Year's Day",
        'day' => 'Wednesday',
        'date' => '09 Apr 2025',
        'states' => 'Tamil Nadu',
        'created_at' => now(),
        'updated_at' => now(),
    ],
    [
        'holiday_name' => 'Mahavir Jayanti',
        'day' => 'Thursday',
        'date' => '10 Apr 2025',
        'states' => 'Chhattisgarh, Haryana, Jharkhand, Karnataka, Kerala, Madhya Pradesh, Maharashtra, Mizoram, Punjab, Rajasthan, Tamil Nadu',
        'created_at' => now(),
        'updated_at' => now(),
    ],
    [
        'holiday_name' => 'Idul Fitr',
        'day' => 'Thursday',
        'date' => '10 Apr 2025',
        'states' => 'Assam, Rajasthan, Uttar Pradesh, Uttarakhand, West Bengal, Jammu and Kashmir, Delhi',
        'created_at' => now(),
        'updated_at' => now(),
    ],
    [
        'holiday_name' => 'Id-Ul-Fitr',
        'day' => 'Friday',
        'date' => '11 Apr 2025',
        'states' => 'Goa, Telangana, Tripura, Uttar Pradesh, West Bengal, Jammu and Kashmir, Delhi',
        'created_at' => now(),
        'updated_at' => now(),
    ],
    [
        'holiday_name' => 'Khutub-E-Ramzan',
        'day' => 'Friday',
        'date' => '11 Apr 2025',
        'states' => 'Karnataka',
        'created_at' => now(),
        'updated_at' => now(),
    ],
    [
        'holiday_name' => 'Bohag Bihu',
        'day' => 'Sunday',
        'date' => '13 Apr 2025',
        'states' => 'Assam',
        'created_at' => now(),
        'updated_at' => now(),
    ],
    [
        'holiday_name' => 'Cheiraoba',
        'day' => 'Sunday',
        'date' => '13 Apr 2025',
        'states' => 'Manipur',
        'created_at' => now(),
        'updated_at' => now(),
    ],
    [
        'holiday_name' => 'Maha Vishuba Sankranti',
        'day' => 'Sunday',
        'date' => '13 Apr 2025',
        'states' => 'Odisha, Punjab',
        'created_at' => now(),
        'updated_at' => now(),
    ],
    [
        'holiday_name' => 'Rongali Bihu',
        'day' => 'Monday',
        'date' => '14 Apr 2025',
        'states' => 'Assam',
        'created_at' => now(),
        'updated_at' => now(),
    ],
    [
        'holiday_name' => 'Dr B R Ambedkar Jayanti',
        'day' => 'Monday',
        'date' => '14 Apr 2025',
        'states' => 'Andhra Pradesh, Bihar, Goa, Jharkhand, Maharashtra, Sikkim, Tamil Nadu, Telangana, Uttarakhand, Uttar Pradesh, Chhattisgarh, Gujarat, Haryana, Himachal Pradesh, Karnataka, Madhya Pradesh, Odisha, Punjab, Rajasthan',
        'created_at' => now(),
        'updated_at' => now(),
    ],
    [
        'holiday_name' => 'Bihu',
        'day' => 'Monday',
        'date' => '14 Apr 2025',
        'states' => 'Arunachal Pradesh',
        'created_at' => now(),
        'updated_at' => now(),
    ],
    [
        'holiday_name' => 'Cheiraoba',
        'day' => 'Monday',
        'date' => '14 Apr 2025',
        'states' => 'Manipur',
        'created_at' => now(),
        'updated_at' => now(),
    ],
    [
        'holiday_name' => 'Vaisakhi',
        'day' => 'Monday',
        'date' => '14 Apr 2025',
        'states' => 'Punjab, Haryana, Himachal Pradesh',
        'created_at' => now(),
        'updated_at' => now(),
    ],
    [
        'holiday_name' => 'Puthandu',
        'day' => 'Monday',
        'date' => '14 Apr 2025',
        'states' => 'Tamil Nadu',
        'created_at' => now(),
        'updated_at' => now(),
    ],
    [
        'holiday_name' => 'Biju Festival',
        'day' => 'Monday',
        'date' => '14 Apr 2025',
        'states' => 'Tripura',
        'created_at' => now(),
        'updated_at' => now(),
    ],
    [
        'holiday_name' => 'Bohag Bihu',
        'day' => 'Monday',
        'date' => '14 Apr 2025',
        'states' => 'Assam',
        'created_at' => now(),
        'updated_at' => now(),
    ],
    [
        'holiday_name' => 'Biju Festival',
        'day' => 'Tuesday',
        'date' => '15 Apr 2025',
        'states' => 'Tripura',
        'created_at' => now(),
        'updated_at' => now(),
    ],
    [
        'holiday_name' => 'Himachal Day',
        'day' => 'Tuesday',
        'date' => '15 Apr 2025',
        'states' => 'Himachal Pradesh',
        'created_at' => now(),
        'updated_at' => now(),
    ],
    [
        'holiday_name' => 'Bohag Bihu',
        'day' => 'Tuesday',
        'date' => '15 Apr 2025',
        'states' => 'Assam',
        'created_at' => now(),
        'updated_at' => now(),
    ],
    [
        'holiday_name' => 'Ramzan Holiday',
        'day' => 'Wednesday',
        'date' => '16 Apr 2025',
        'states' => 'Tripura',
        'created_at' => now(),
        'updated_at' => now(),
    ],
    [
        'holiday_name' => 'Bohag Bihu',
        'day' => 'Wednesday',
        'date' => '16 Apr 2025',
        'states' => 'Assam',
        'created_at' => now(),
        'updated_at' => now(),
    ],
    [
        'holiday_name' => 'Basava Jayanti',
        'day' => 'Wednesday',
        'date' => '30 Apr 2025',
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

DB::table('state_wise_bank_holiday')->insert($data);


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


