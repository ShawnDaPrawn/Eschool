<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Holiday;
use App\Models\LeaveMaster;
use App\Models\MultipleEvent;
use App\Models\SessionYear;
use App\Models\Slider;
use App\Models\Term;
use App\Models\User;
use App\Services\Academics\TermBreaks;
use App\Services\GalleryService;
use App\Services\Platform\Identity\AppTokens;
use App\Services\Platform\Identity\PortalSession;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Laravel\Sanctum\PersonalAccessToken;
use Throwable;

class ApiController extends Controller
{
    /**
     * Logout user and revoke access token.
     */
    public function logout(Request $request)
    {
        try {
            // Signs out of this app only; the account's other apps stay signed in.
            // A web portal's session ends; the phone apps stay signed in.
            PortalSession::signOut($request, $request->user());

            return ResponseService::successResponse('Logout Successfully done.');
        } catch (Throwable $e) {
            return ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    /**
     * Get the current session year's holidays, like the events list.
     */
    public function getHolidays()
    {
        try {
            $data = Holiday::where('session_year_id', getSettings('session_year')['session_year'])->orderBy('date')->get();

            return ResponseService::successResponse('Holidays Fetched Successfully.', $data);
        } catch (Throwable $e) {
            return ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    /**
     * Get sliders for mobile app.
     */
    public function getSliders()
    {
        try {
            $data = Slider::whereIn('type', [1, 3])->get();

            return ResponseService::successResponse('Sliders Fetched Successfully.', $data);
        } catch (Throwable $e) {
            return ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    /**
     * Get current session year.
     */
    public function getCurrentSessionYear()
    {
        try {
            $session_year = getSettings('session_year');
            $session_year_id = $session_year['session_year'];
            $data = SessionYear::find($session_year_id);

            return ResponseService::successResponse('Session Year Fetched Successfully.', $data);
        } catch (Throwable $e) {
            return ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    /**
     * Get all session years.
     */
    public function getSessionYear()
    {
        try {
            $data = SessionYear::where('id', '!=', 0)
                ->orderBy('id', 'DESC')
                ->get();

            return ResponseService::successResponse('Session Years Fetched Successfully.', $data);
        } catch (Throwable $e) {
            return ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function getSettings(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:privacy_policy,contact_us,about_us,terms_condition,app_settings,fees_settings',
        ]);

        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->first());
        }
        try {
            $settings = getSettings();
            $sessionYearData = SessionYear::find($settings['session_year'] ?? '');

            $currentTerm = Term::where('session_year_id', $sessionYearData?->id)->forGrade(null)->get()->first(function ($term) {
                return $term->current;
            });

            $term_breaks = $sessionYearData === null ? [] : app(TermBreaks::class)->forYear($sessionYearData->id);
            $term_breaks = $term_breaks === [] ? null : $term_breaks;

            if ($request->type == 'app_settings') {
                $session_year = $settings['session_year'] ?? '';
                $holiday_days = LeaveMaster::where('session_year_id', $session_year)->pluck('holiday_days')->first();
                $calender = ! empty($session_year) ? $sessionYearData : null;

                $data['app_link'] = $settings['app_link'] ?? '';
                $data['ios_app_link'] = $settings['ios_app_link'] ?? '';
                $data['app_version'] = $settings['app_version'] ?? '';
                $data['ios_app_version'] = $settings['ios_app_version'] ?? '';
                $data['force_app_update'] = $settings['force_app_update'] ?? '';
                $data['app_maintenance'] = $settings['app_maintenance'] ?? '';
                $data['session_year'] = $calender;
                $data['school_name'] = $settings['school_name'] ?? '';
                $data['school_tagline'] = $settings['school_tagline'] ?? '';
                $data['teacher_app_link'] = $settings['teacher_app_link'] ?? '';
                $data['teacher_ios_app_link'] = $settings['teacher_ios_app_link'] ?? '';
                $data['teacher_app_version'] = $settings['teacher_app_version'] ?? '';
                $data['teacher_ios_app_version'] = $settings['teacher_ios_app_version'] ?? '';
                $data['teacher_force_app_update'] = $settings['teacher_force_app_update'] ?? '';
                $data['teacher_app_maintenance'] = $settings['teacher_app_maintenance'] ?? '';
                $data['online_payment'] = $settings['online_payment'] ?? '1';
                $data['is_demo'] = config('app.demo_mode');

                // Keep the v1 field but never instruct installed clients to
                // restrict learning. The stored setting controls reminders.
                $data['compulsory_fee_payment_mode'] = '0';
                $data['is_student_can_pay_fees'] = $settings['is_student_can_pay_fees'] ?? '';

                if (isset($settings['max_file_size_in_bytes'])) {
                    $max_file_size_in_bytes = $settings['max_file_size_in_bytes'] * 1000000;
                }

                $data['chat_settings'] = [
                    'max_files_or_images_in_one_message' => $settings['max_files_or_images_in_one_message'] ?? 10,
                    'max_file_size_in_bytes' => $max_file_size_in_bytes ?? 10000000,
                    'max_characters_in_text_message' => $settings['max_characters_in_text_message'] ?? 500,
                    'automatically_messages_removed_days' => $settings['automatically_messages_removed_days'] ?? 30,
                ];

                $data['holiday_days'] = $holiday_days ?? '';

                $data['payment_options']['currency_code'] = $settings['currency_code'] ?? '';
                $data['payment_options']['currency_symbol'] = $settings['currency_symbol'] ?? '';
                if (isset($settings['fees_due_date'])) {
                    $date = date('Y-m-d', strtotime($settings['fees_due_date']));
                    $data['payment_options']['fees_due_date'] = $date ?? '';
                    $data['payment_options']['fees_due_charges'] = $settings['fees_due_charges'] ?? '';
                }

                // Stripe is the only gateway. Razorpay, Paystack and Flutterwave were
                // removed: like a gateway the school has switched off, they are left
                // out, so installed apps stop offering them. Public keys only: secret
                // keys and webhook secrets never leave the server.
                if (isset($settings['stripe_status']) && $settings['stripe_status']) {
                    $data['payment_options']['stripe'] = [
                        'stripe_status' => $settings['stripe_status'] ?? '',
                        'stripe_publishable_key' => $settings['stripe_publishable_key'] ?? '',
                        'stripe_currency_code' => $settings['stripe_currency_code'] ?? $settings['currency_code'],
                    ];
                }

                if (isset($settings['online_exam_terms_condition']) && ! empty($settings['online_exam_terms_condition'])) {
                    $data['online_exam_terms_condition'] = htmlspecialchars_decode($settings['online_exam_terms_condition']);
                } else {
                    $data['online_exam_terms_condition'] = '';
                }

                $data['current_term'] = $currentTerm ?? null;
                $data['term_breaks'] = $term_breaks;
            } else {
                $data = $settings[$request->type] ?? '';
            }

            return ResponseService::successResponse('Data Fetched Successfully', $data);
        } catch (\Exception $e) {
            return ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    /**
     * Handle forgot password functionality.
     */
    protected function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->first());
        }

        try {
            try {
                Password::sendResetLink($request->only('email'));
            } catch (Throwable $e) {
                // Only reached for real accounts (e.g. mail not configured): logged,
                // but not shown, or the error would give the account away.
                ResponseService::logErrorResponse($e);
            }

            // Same answer whether or not the email belongs to an account (or was
            // throttled), so the endpoint can't be used to find registered emails.
            return ResponseService::successResponse('If an account exists for this email, a password reset link has been sent.');
        } catch (Throwable $e) {
            return ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    /**
     * Handle password change functionality.
     */
    protected function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required',
            'new_password' => ['required', PasswordRule::defaults()],
            'new_confirm_password' => 'required|same:new_password',
        ]);

        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->first());
        }

        try {
            $user = $request->user();

            if (! Hash::check($request->input('current_password'), $user->password)) {
                return ResponseService::errorResponse('Current password is incorrect.');
            }

            $user->update([
                'password' => Hash::make($request->input('new_password')),
            ]);

            // Sign out every other device and app: their tokens stop working.
            // The device that made the change stays signed in.
            $currentToken = $user->currentAccessToken();
            AppTokens::revokeAll($user, $currentToken instanceof PersonalAccessToken ? $currentToken : null);

            return ResponseService::successResponse('Password changed successfully.');
        } catch (Throwable $e) {
            return ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    /**
     * Get events data.
     */
    public function getEvents()
    {
        try {
            $session_year_id = getSettings('session_year')['session_year'];
            $session_year = DB::table('session_years')
                ->select('id', 'name', 'start_date', 'end_date')
                ->where('id', $session_year_id)
                ->first();

            if (! $session_year) {
                throw new \Exception('Session year not found.');
            }

            // $events = Event::whereBetween('start_date', [$session_year->start_date, $session_year->end_date])
            //     ->orderBy('start_date', 'asc')
            //     ->get();

            // $multipleEvents = MultipleEvent::whereBetween('date', [$session_year->start_date, $session_year->end_date])
            //     ->orderBy('date', 'asc')
            //     ->get();

            // new code |
            // has_day_schedule comes from one subquery, not a query per event.
            $events = Event::where('session_year_id', $session_year->id)
                ->withExists('multipleEvent')
                ->orderBy('start_date', 'asc')
                ->get();

            $multipleEvents = MultipleEvent::with('events')
                ->whereHas('events', function ($query) use ($session_year) {
                    $query->where('session_year_id', $session_year->id);
                })
                ->orderBy('date', 'asc')
                ->get();

            $allEvents = collect();

            // Add regular events with has_day_schedule flag
            foreach ($events as $event) {
                $hasSchedule = $event->multiple_event_exists;

                $allEvents->push([
                    'id' => $event->id,
                    'title' => $event->title,
                    'type' => $event->type,
                    'start_date' => $event->start_date,
                    'end_date' => $event->end_date,
                    'start_time' => $event->start_time,
                    'end_time' => $event->end_time,
                    'image' => $event->image,
                    'description' => $event->description,
                    'has_day_schedule' => $hasSchedule ? 1 : 0,
                ]);
            }

            // Add each MultipleEvent row (child events)
            foreach ($multipleEvents as $m) {
                $allEvents->push([
                    'id' => $m->id,
                    'event_id' => $m->event_id,
                    'title' => $m->name,
                    'date' => $m->date,
                    'start_time' => $m->start_time,
                    'end_time' => $m->end_time,
                    'description' => $m->description,
                    'has_day_schedule' => 0,    // child itself doesn’t hold a schedule
                ]);
            }

            return ResponseService::successResponse(
                'Events Fetched Successfully.',
                $allEvents->values()->toArray()
            );
        } catch (Throwable $e) {
            return ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function getEventsDetails(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'event_id' => 'required|integer',
        ]);
        if ($validator->fails()) {
            $response = [
                'error' => true,
                'message' => $validator->errors()->first(),
                'code' => 102,
            ];

            return response()->json($response);
        }
        try {

            $data = MultipleEvent::where('event_id', $request->event_id)->get();

            $response = [
                'error' => false,
                'message' => 'Events Details Fetched Successfully',
                'data' => $data,
                'code' => 200,
            ];
        } catch (Throwable $e) {
            $response = [
                'error' => true,
                'message' => trans('error_occurred'),
                'code' => 103,
            ];
        }

        return response()->json($response);
    }

    public function getGalleryAlbums(Request $request, GalleryService $galleryService)
    {
        try {
            $albums = $galleryService->paginateAlbums(
                $this->perPage($request),
                max((int) $request->input('page', 1), 1)
            );

            return response()->json([
                'error' => false,
                'data' => [
                    'data' => $albums->items(),
                    'current_page' => $albums->currentPage(),
                    'last_page' => $albums->lastPage(),
                ],
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'error' => true,
                'message' => trans('error_occurred'),
                'code' => 103,
            ]);
        }
    }

    public function getGalleryImages(Request $request, GalleryService $galleryService)
    {
        if (! $request->album_id) {
            return response()->json([
                'error' => true,
                'message' => 'album_id is required',
                'code' => 422,
            ]);
        }

        try {
            return response()->json([
                'error' => false,
                'data' => $galleryService->getAlbumImages((int) $request->album_id),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'error' => true,
                'message' => trans('error_occurred'),
                'code' => 103,
            ]);
        }
    }

    public function getGalleryVideos(Request $request, GalleryService $galleryService)
    {
        try {
            $videos = $galleryService->paginateVideos(
                $this->perPage($request),
                max((int) $request->input('page', 1), 1)
            );

            return response()->json([
                'error' => false,
                'data' => [
                    'data' => $videos->items(),
                    'current_page' => $videos->currentPage(),
                    'last_page' => $videos->lastPage(),
                ],
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'error' => true,
                'message' => trans('error_occurred'),
                'code' => 103,
            ]);
        }
    }

    public function getTerms()
    {
        try {
            $session_year_id = getSettings('session_year')['session_year'];
            $terms = Term::where('session_year_id', $session_year_id)
                ->orderBy('start_date', 'asc')
                ->get();

            return ResponseService::successResponse('Terms Fetched Successfully.', $terms);
        } catch (Throwable $e) {
            return ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    /** Page size from the request, between 1 and 100 (default 10). */
    private function perPage(Request $request): int
    {
        return min(max((int) $request->input('per_page', 10), 1), 100);
    }
}
