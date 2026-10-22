<?php

namespace App\Http\Controllers;

use App\Models\SessionYear;
use App\Models\Settings;
use App\Models\Language;
use App\Services\ChatMessageCleanupService;
use App\Services\MailService;
use App\Services\SessionYearService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Throwable;

class SettingController extends Controller
{
    /** Firebase service account file, on the private local disk. */
    private const SERVICE_ACCOUNT_FILE = 'firebase/service_account.json';

    public function index()
    {
        if (! Auth::user()->can('setting-create')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }

        $settings = getSettings();
        $getDateFormat = getDateFormat();
        $getTimezoneList = getTimezoneList();
        $getTimeFormat = getTimeFormat();

         $session_year = SessionYear::orderBy('id', 'desc')->get();
       

         $language = Language::select('id', 'name')->orderBy('id', 'desc')->get();
        return view('settings.index', compact('settings', 'getDateFormat', 'getTimezoneList', 'getTimeFormat', 'session_year'));
    }

    public function update(Request $request)
    {
        if (! Auth::user()->can('setting-create')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }

        $request->validate([
            'school_name' => 'required|max:255',
            'school_email' => 'required|email',
            'school_phone' => 'required',
            'school_address' => 'required',
            'time_zone' => 'required',
            'theme_color' => 'required',
            'secondary_color' => 'required',
            'session_year' => 'required',
            'school_tagline' => 'required',
            'online_payment' => 'required|in:0,1',
            'facebook' => 'nullable',
            'instagram' => 'nullable',
            'linkedin' => 'nullable',
            'maplink' => 'nullable',
            'recaptcha_site_key' => 'required_if:recaptcha_status,1',
            'recaptcha_secret_key' => 'required_if:recaptcha_status,1',
            'recaptcha_status' => 'nullable',
            'logo1' => 'nullable|file|mimes:jpeg,jpg,png,gif,webp,svg',
            'logo2' => 'nullable|file|mimes:jpeg,jpg,png,gif,webp,svg',
            'favicon' => 'nullable|file|mimes:jpeg,jpg,png,gif,webp,svg,ico',
            'login_image' => 'nullable|file|mimes:jpeg,jpg,png,gif,webp,svg',
        ]);

        $settings = [
            'school_name',
            'school_email',
            'school_phone',
            'school_address',
            'time_zone',
            'date_formate',
            'time_formate',
            'theme_color',
            'session_year',
            'school_tagline',
            'online_payment',
            'secondary_color',
            'facebook',
            'instagram',
            'linkedin',
            'maplink',
            'recaptcha_site_key',
            'recaptcha_secret_key',
            'recaptcha_status',
        ];
        try {
            foreach ($settings as $row) {
                if (Settings::where('type', $row)->exists()) {
                    if ($row == 'session_year') {
                        app(SessionYearService::class)->makeCurrent(SessionYear::findOrFail($request->$row));
                    }

                    // removing the double unnecessary double quotes in school name
                    if ($row == 'school_name') {
                        $data = [
                            'message' => str_replace('"', '', $request->$row),
                        ];
                    } else {
                        $data = [
                            'message' => $request->input($row, ''),
                        ];
                    }
                    
                    Settings::where('type', $row)->update($data);
                        
                      

                    // Cache::forget('settings.all');
                    // Cache::forget('session_year.all');
                    // Cache::forget('session_year.default.id');
                    //  $setting = Settings::where('type', $row)->first();

                    // $setting->message = $data['message'];
                    // $setting->save();
                    
                } else {
                    $setting = new Settings;
                    $setting->type = $row;
                    $setting->message = $row == 'school_name' ? str_replace('"', '', $request->$row) : $request->input($row, '');
                    $setting->save();
                }
            }

            // for online payment data
            if (Settings::where('type', 'online_payment')->exists()) {
                $data = [
                    'message' => $request->online_payment,
                ];
                Settings::where('type', 'online_payment')->update($data);
            } else {
                $setting = new Settings;
                $setting->type = 'online_payment';
                $setting->message = $request->online_payment;
                $setting->save();
            }
            // end of online payment data

            if ($request->hasFile('logo1')) {
                if (Settings::where('type', 'logo1')->exists()) {
                    $get_id = Settings::select('message')->where('type', 'logo1')->pluck('message')->first();
                    if (Storage::disk('public')->exists($get_id)) {
                        Storage::disk('public')->delete($get_id);
                    }
                    $data = [
                        'message' => $request->file('logo1')->store('logo', 'public'),
                    ];
                    Settings::where('type', 'logo1')->update($data);
                } else {
                    $setting = new Settings;
                    $setting->type = 'logo1';
                    $setting->message = $request->file('logo1')->store('logo', 'public');
                    $setting->save();
                }
            }
            if ($request->hasFile('logo2')) {
                if (Settings::where('type', 'logo2')->exists()) {
                    $get_id = Settings::select('message')->where('type', 'logo2')->pluck('message')->first();
                    if (Storage::disk('public')->exists($get_id)) {
                        Storage::disk('public')->delete($get_id);
                    }
                    $data = [
                        'message' => $request->file('logo2')->store('logo', 'public'),
                    ];
                    Settings::where('type', 'logo2')->update($data);
                } else {
                    $setting = new Settings;
                    $setting->type = 'logo2';
                    $setting->message = $request->file('logo2')->store('logo', 'public');
                    $setting->save();
                }
            }
            if ($request->hasFile('favicon')) {
                if (Settings::where('type', 'favicon')->exists()) {
                    $get_id = Settings::select('message')->where('type', 'favicon')->pluck('message')->first();
                    if (Storage::disk('public')->exists($get_id)) {
                        Storage::disk('public')->delete($get_id);
                    }
                    $data = [
                        'message' => $request->file('favicon')->store('logo', 'public'),
                    ];
                    Settings::where('type', 'favicon')->update($data);
                } else {
                    $setting = new Settings;
                    $setting->type = 'favicon';
                    $setting->message = $request->file('favicon')->store('logo', 'public');
                    $setting->save();
                }
            }
            if ($request->hasFile('login_image')) {
                if (Settings::where('type', 'login_image')->exists()) {
                    $get_id = Settings::select('message')->where('type', 'login_image')->pluck('message')->first();
                    if (Storage::disk('public')->exists($get_id)) {
                        Storage::disk('public')->delete($get_id);
                    }
                    $data = [
                        'message' => $request->file('login_image')->store('logo', 'public'),
                    ];
                    Settings::where('type', 'login_image')->update($data);
                } else {
                    $setting = new Settings;
                    $setting->type = 'login_image';
                    // $setting->message = $request->file('login_image')->store('logo', 'public');
                    $setting->message = $row == 'school_name' ? str_replace('"', '', $request->input($row, '')) : $request->input($row, '');
                    $setting->save();
                }
            }

            $response = [
                'error' => false,
                'message' => trans('data_update_successfully'),
            ];
        } catch (Throwable $e) {
            report($e);
            $response = [
                'error' => true,
                'message' => trans('error_occurred'),
            ];
        }

        return response()->json($response);
    }

    public function fcm_index()
    {
        if (! Auth::user()->can('fcm-setting-create')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }

        $settings = getSettings();

        return view('settings.fcm_key', compact('settings'));
    }

    public function email_index()
    {
        if (! Auth::user()->can('email-setting-create')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }
        $settings = getSettings();

        return view('settings.email_configuration', compact('settings'));
    }

    public function email_update(Request $request)
    {
        if (! Auth::user()->can('email-setting-create')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }
        $request->validate([
            'mail_mailer' => 'required',
            'mail_host' => 'required',
            'mail_port' => 'required',
            'mail_username' => 'required',
            'mail_password' => 'required',
            'mail_encryption' => 'required',
            'mail_send_from' => 'required|email',
        ]);

        $settings = [
            'mail_mailer',
            'mail_host',
            'mail_port',
            'mail_username',
            'mail_password',
            'mail_encryption',
            'mail_send_from',
        ];

        try {
            foreach ($settings as $row) {
                if (Settings::where('type', $row)->exists()) {

                    $data = [
                        'message' => $request->$row,
                    ];
                    Settings::where('type', $row)->update($data);
                    // $setting = Settings::where('type', $row)->first();

                    // $setting->message = $data['message'];
                    // $setting->save();
                } else {
                    $setting = new Settings;
                    $setting->type = $row;
                    $setting->message = $request->$row;
                    $setting->save();
                }
                Settings::updateOrInsert(
                    ['type' => 'email_configration_verification'],
                    ['type' => 'email_configration_verification', 'message' => 0]
                );
            }
            $response = [
                'error' => false,
                'message' => trans('data_update_successfully'),
            ];
        } catch (Throwable $e) {
            report($e);
            $response = [
                'error' => true,
                'message' => trans('error_occurred'),
            ];
        }

        return response()->json($response);
    }

    public function verifyEmailConfigration(Request $request)
    {
        if (! Auth::user()->can('email-setting-create')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }
        $validator = Validator::make($request->all(), [
            'verify_email' => 'required|email',
        ]);
        if ($validator->fails()) {
            $response = [
                'error' => true,
                'message' => $validator->errors()->first(),
            ];

            return response()->json($response);
        }
        try {
            $data = [
                'email' => $request->verify_email,
            ];
            $admin_mail = config('mail.from.address');
            if (! filter_var($request->verify_email, FILTER_VALIDATE_EMAIL)) {
                $response = [
                    'error' => true,
                    'message' => trans('invalid_email'),
                ];

                return response()->json($response);
            }

            // Sent at once, not queued: the answer says whether the mail server accepted it.
            $status = MailService::sendNow('mail', $data, function ($message) use ($data, $admin_mail) {
                $message->to($data['email'])->subject('Connection Verified successfully');
                $message->from($admin_mail, 'Eschool Admin');
            });

            if ($status) {
                Settings::where('type', 'email_configration_verification')->update(['message' => 1]);

                $response = [
                    'error' => false,
                    'message' => trans('email_sent_successfully'),
                ];
            } else {
                // Sending failed (sendWithFallback returned falsy)
                $response = [
                    'error' => true,
                    'message' => trans('email_send_failed'),
                ];
            }
        } catch (Throwable $e) {
            report($e);
            $response = [
                'error' => true,
                'message' => trans('error_occurred'),
            ];
        }

        return response()->json($response);
    }

    public function privacy_policy_index()
    {
        if (! Auth::user()->can('privacy-policy')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }
        $settings = Settings::where('type', 'privacy_policy')->first();
        $type = 'privacy_policy';

        return view('settings.privacy_policy', compact('settings', 'type'));
    }

    public function contact_us_index()
    {
        if (! Auth::user()->can('contact-us')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }
        $settings = Settings::where('type', 'contact_us')->first();
        $type = 'contact_us';

        return view('settings.contact_us', compact('settings', 'type'));
    }

    public function about_us_index()
    {
        if (! Auth::user()->can('about-us')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }
        $settings = Settings::where('type', 'about_us')->first();
        $type = 'about_us';

        return view('settings.about_us', compact('settings', 'type'));
    }

    public function terms_condition_index()
    {
        if (! Auth::user()->can('terms-condition')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }
        $settings = Settings::where('type', 'terms_condition')->first();
        $type = 'terms_condition';

        return view('settings.terms_condition', compact('settings', 'type'));
    }

    public function setting_page_update(Request $request)
    {
        if (! Auth::user()->can('setting-create')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }
        $messageRequiredKeys = [
            'privacy_policy' => 'please_enter_privacy_policy',
            'contact_us' => 'please_enter_contact_us',
            'about_us' => 'please_enter_about_us',
            'terms_condition' => 'please_enter_terms_condition',
        ];
        $messages = [];
        if (isset($messageRequiredKeys[$request->type])) {
            $messages['message.required'] = trans($messageRequiredKeys[$request->type]);
        }
        // The school may leave its privacy notice or terms blank. An empty page
        // is not shown to parents. The other pages still need text.
        $allowsEmpty = in_array($request->input('type'), ['privacy_policy', 'terms_condition'], true);
        $validator = Validator::make($request->all(), [
            'type' => 'required',
            'message' => $allowsEmpty ? 'nullable|string' : 'required',
        ], $messages);
        if ($validator->fails()) {
            $response = [
                'error' => true,
                'message' => $validator->errors()->first(),
            ];

            return response()->json($response);
        }
        $type = $request->type;
        $message = (string) $request->input('message', '');
        $id = Settings::select('id')->where('type', $type)->pluck('id')->first();
        if (isset($id) && ! empty($id)) {
            $setting = Settings::find($id);
            $setting->message = $message;
            $setting->save();
            $response = [
                'error' => false,
                'message' => trans('data_update_successfully'),
            ];
        } else {
            $setting = new Settings;
            $setting->type = $type;
            $setting->message = $message;
            $setting->save();
            $response = [
                'error' => false,
                'message' => trans('data_store_successfully'),
            ];
        }

        return response()->json($response);
    }

    public function app_index()
    {
        if (! Auth::user()->can('setting-create')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }
        $settings = getSettings();

        return view('settings.app_settings', compact('settings'));
    }

    public function app_update(Request $request)
    {
        if (! Auth::user()->can('setting-create')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }
        $request->validate([
            'app_link' => 'required',
            'ios_app_link' => 'required',
            'app_version' => 'required',
            'ios_app_version' => 'required',
            'force_app_update' => 'required',
            'app_maintenance' => 'required',
            'teacher_app_link' => 'required',
            'teacher_ios_app_link' => 'required',
            'teacher_app_version' => 'required',
            'teacher_ios_app_version' => 'required',
            'teacher_force_app_update' => 'required',
            'teacher_app_maintenance' => 'required',
        ]);

        $settings = [
            'app_link',
            'ios_app_link',
            'app_version',
            'ios_app_version',
            'force_app_update',
            'app_maintenance',
            'teacher_app_link',
            'teacher_ios_app_link',
            'teacher_app_version',
            'teacher_ios_app_version',
            'teacher_force_app_update',
            'teacher_app_maintenance',
        ];

        try {

            foreach ($settings as $row) {
                if (Settings::where('type', $row)->exists()) {

                    $data = [
                        'message' => $request->$row,
                    ];
                    Settings::where('type', $row)->update($data);
                } else {
                    $setting = new Settings;
                    $setting->type = $row;
                    $setting->message = $request->$row;
                    $setting->save();
                }
            }

            $response = [
                'error' => false,
                'message' => trans('data_update_successfully'),
            ];
        } catch (Throwable $e) {
            report($e);
            $response = [
                'error' => true,
                'message' => trans('error_occurred'),
            ];
        }

        return response()->json($response);
    }

    public function notification_setting(Request $request)
    {
        if (! Auth::user()->can('setting-create')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }
        $request->validate([
            'sender_id' => 'required',
            'project_id' => 'required',
            // JSON uploads are often detected as text/plain, so the content is checked instead of the type.
            'service_account_file' => ['bail', 'nullable', 'file', 'max:64', function ($attribute, $value, $fail) {
                $json = $value instanceof \Illuminate\Http\UploadedFile
                    ? json_decode((string) @file_get_contents($value->getRealPath()), true)
                    : null;
                if (! is_array($json) || empty($json['private_key']) || empty($json['client_email'])) {
                    $fail(trans('only_json_file_allowed'));
                }
            }],
        ]);
        $settings = ['sender_id', 'project_id'];
        try {
            foreach ($settings as $row) {
                if (Settings::where('type', $row)->exists()) {
                    $data = [
                        'message' => $request->$row,
                    ];
                    Settings::where('type', $row)->update($data);
                } else {
                    $setting = new Settings;
                    $setting->type = $row;
                    $setting->message = $request->$row;
                    $setting->save();
                }
            }
            // The service account file holds a Google private key, so it is kept on
            // the private local disk (storage/app), never on the public disk.
            $currentFile = Settings::where('type', 'service_account_file')->value('message');
            $newFile = null;
            if ($request->hasFile('service_account_file')) {
                $newFile = $request->file('service_account_file')->storeAs(
                    dirname(self::SERVICE_ACCOUNT_FILE),
                    basename(self::SERVICE_ACCOUNT_FILE),
                    'local'
                );
            } elseif ($currentFile && ! Storage::disk('local')->exists($currentFile) && Storage::disk('public')->exists($currentFile)) {
                // Installs that uploaded it before: move the existing file off the public disk.
                if (Storage::disk('local')->put(self::SERVICE_ACCOUNT_FILE, Storage::disk('public')->get($currentFile))) {
                    $newFile = self::SERVICE_ACCOUNT_FILE;
                }
            }

            if ($newFile) {
                if ($currentFile && Storage::disk('public')->exists($currentFile)) {
                    Storage::disk('public')->delete($currentFile);
                }
                if ($currentFile && $currentFile !== $newFile && Storage::disk('local')->exists($currentFile)) {
                    Storage::disk('local')->delete($currentFile);
                }
                Settings::updateOrCreate(['type' => 'service_account_file'], ['message' => $newFile]);
            }

            $response = [
                'error' => false,
                'message' => trans('data_update_successfully'),
            ];
        } catch (\Throwable $e) {
            report($e);
            $response = [
                'error' => true,
                'message' => trans('error_occurred'),
            ];
        }

        return response()->json($response);
    }

    public function chat_setting_index()
    {
        if (! Auth::user()->can('chat-settings')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }
        $settings = getSettings();

        return view('settings.chat_setting', compact('settings'));
    }

    public function chat_setting_update(Request $request)
    {
        if (! Auth::user()->can('chat-settings')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }
        $request->validate([
            'max_files_or_images_in_one_message' => 'required|integer|min:1',
            // In MB, despite the name (the API converts it to bytes for the apps).
            'max_file_size_in_bytes' => 'required|numeric|min:0.1',
            'max_characters_in_text_message' => 'required|integer|min:1',
            'automatically_messages_removed_days' => 'required|integer|min:1',
        ]);
        $settings = ['max_files_or_images_in_one_message', 'max_file_size_in_bytes', 'max_characters_in_text_message', 'automatically_messages_removed_days'];
        try {
            foreach ($settings as $row) {
                if (Settings::where('type', $row)->exists()) {
                    $data = [
                        'message' => $request->$row,
                    ];
                    Settings::where('type', $row)->update($data);
                } else {
                    $setting = new Settings;
                    $setting->type = $row;
                    $setting->message = $request->$row;
                    $setting->save();
                }
            }
            $response = [
                'error' => false,
                'message' => trans('data_update_successfully'),
            ];
        } catch (\Throwable $e) {
            report($e);
            $response = [
                'error' => true,
                'message' => trans('error_occurred'),
            ];
        }

        return response()->json($response);
    }

    public function delete_chat_messages(Request $request, ChatMessageCleanupService $service)
    {
        if (! Auth::user()->can('chat-settings')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }
        $validator = Validator::make($request->all(), [
            'from_date' => 'required|date',
            'to_date' => 'required|date|after:from_date',
        ], [
            'to_date.after' => "The 'To Date' must be a date after the 'From Date'.",
        ]);

        if ($validator->fails()) {
            $response = [
                'error' => true,
                'message' => $validator->errors()->first(),
            ];

            return response()->json($response);
        }
        try {
            $service->deleteBetweenDates($request->from_date, $request->to_date);

            $response = [
                'error' => false,
                'message' => trans('data_delete_successfully'),
            ];
        } catch (\Throwable $e) {
            report($e);
            $response = [
                'error' => true,
                'message' => trans('error_occurred'),
            ];
        }

        return response()->json($response);
    }

    public function cron_job(ChatMessageCleanupService $service)
    {
        try {
            $service->deleteOlderThanConfiguredDays();

            $response = [
                'error' => false,
                'message' => trans('data_delete_successfully'),
            ];
        } catch (\Throwable $e) {
            report($e);
            $response = [
                'error' => true,
                'message' => trans('error_occurred'),
            ];
        }

        return response()->json($response);
    }
}
