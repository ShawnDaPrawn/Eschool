<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fees;

use App\Http\Controllers\Controller;
use App\Models\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * Admin panel: fee settings: currency, how compulsory fees are paid, whether students can pay
 * online, and the payment gateway (Stripe; Razorpay, Paystack and Flutterwave were removed).
 */
class ConfigController extends Controller
{
    public function feesConfigIndex()
    {
        if (! Auth::user()->can('fees-config')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }
        $settings = getSettings();
        $domain = request()->getSchemeAndHttpHost();

        return view('fees.fees_config', compact('settings', 'domain'));
    }

    public function feesConfigUpdate(Request $request)
    {
        if (! Auth::user()->can('fees-config')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }
        // An enabled gateway's currency is sent to it as an ISO 4217 code.
        $currency = 'regex:/^[A-Za-z]{3}$/';
        $validator = Validator::make($request->all(), [
            'stripe_status' => 'required',
            'stripe_publishable_key' => 'required_if:stripe_status,1|nullable',
            'stripe_secret_key' => 'required_if:stripe_status,1|nullable',
            'stripe_webhook_secret' => 'required_if:stripe_status,1|nullable',
            'stripe_webhook_url' => 'required_if:stripe_status,1|nullable',
            'stripe_currency_code' => ['exclude_unless:stripe_status,1', 'required', $currency],
            'currency_code' => 'required',
            'currency_symbol' => 'required',
            'compulsory_fee_payment_mode' => 'required',
            'is_student_can_pay_fees' => 'required',

        ]);
        if ($validator->fails()) {
            $response = [
                'error' => true,
                'message' => $validator->errors()->first(),
            ];

            return response()->json($response);
        }
        try {

            // stripe_status
            if (Settings::where('type', 'stripe_status')->exists()) {
                $data = [
                    'message' => $request->stripe_status,
                ];
                Settings::where('type', 'stripe_status')->update($data);
            } else {
                $setting = new Settings;
                $setting->type = 'stripe_status';
                $setting->message = $request->stripe_status;
                $setting->save();
            }

            if ($request->stripe_status) {

                // stripe_publishable_key
                if (Settings::where('type', 'stripe_publishable_key')->exists()) {
                    $data = [
                        'message' => trim($request->stripe_publishable_key),
                    ];
                    Settings::where('type', 'stripe_publishable_key')->update($data);
                } else {
                    $setting = new Settings;
                    $setting->type = 'stripe_publishable_key';
                    $setting->message = trim($request->stripe_publishable_key);
                    $setting->save();
                }

                // stripe_secret_key
                if (Settings::where('type', 'stripe_secret_key')->exists()) {
                    $data = [
                        'message' => trim($request->stripe_secret_key),
                    ];
                    Settings::where('type', 'stripe_secret_key')->update($data);
                } else {
                    $setting = new Settings;
                    $setting->type = 'stripe_secret_key';
                    $setting->message = trim($request->stripe_secret_key);
                    $setting->save();
                }

                // stripe_webhook_secret
                if (Settings::where('type', 'stripe_webhook_secret')->exists()) {
                    $data = [
                        'message' => trim($request->stripe_webhook_secret),
                    ];
                    Settings::where('type', 'stripe_webhook_secret')->update($data);
                } else {
                    $setting = new Settings;
                    $setting->type = 'stripe_webhook_secret';
                    $setting->message = trim($request->stripe_webhook_secret);
                    $setting->save();
                }

                // stripe_webhook_url
                if (Settings::where('type', 'stripe_webhook_url')->exists()) {
                    $data = [
                        'message' => trim($request->stripe_webhook_url),
                    ];
                    Settings::where('type', 'stripe_webhook_url')->update($data);
                } else {
                    $setting = new Settings;
                    $setting->type = 'stripe_webhook_url';
                    $setting->message = trim($request->stripe_webhook_url);
                    $setting->save();
                }

                // stripe_currency_code
                if (Settings::where('type', 'stripe_currency_code')->exists()) {
                    $data = [
                        'message' => trim($request->stripe_currency_code),
                    ];
                    Settings::where('type', 'stripe_currency_code')->update($data);
                } else {
                    $setting = new Settings;
                    $setting->type = 'stripe_currency_code';
                    $setting->message = trim($request->stripe_currency_code);
                    $setting->save();
                }
            }

            // currency_code
            if (Settings::where('type', 'currency_code')->exists()) {
                $data = [
                    'message' => $request->currency_code,
                ];
                Settings::where('type', 'currency_code')->update($data);
            } else {
                $setting = new Settings;
                $setting->type = 'currency_code';
                $setting->message = $request->currency_code;
                $setting->save();
            }

            // currency_symbol
            if (Settings::where('type', 'currency_symbol')->exists()) {
                $data = [
                    'message' => $request->currency_symbol,
                ];
                Settings::where('type', 'currency_symbol')->update($data);
            } else {
                $setting = new Settings;
                $setting->type = 'currency_symbol';
                $setting->message = $request->currency_symbol;
                $setting->save();
            }

            // currency_symbol
            if (Settings::where('type', 'compulsory_fee_payment_mode')->exists()) {
                $data = [
                    'message' => $request->compulsory_fee_payment_mode,
                ];
                Settings::where('type', 'compulsory_fee_payment_mode')->update($data);
            } else {
                $setting = new Settings;
                $setting->type = 'compulsory_fee_payment_mode';
                $setting->message = $request->compulsory_fee_payment_mode;
                $setting->save();
            }

            // currency_symbol
            if (Settings::where('type', 'is_student_can_pay_fees')->exists()) {
                $data = [
                    'message' => $request->is_student_can_pay_fees,
                ];
                Settings::where('type', 'is_student_can_pay_fees')->update($data);
            } else {
                $setting = new Settings;
                $setting->type = 'is_student_can_pay_fees';
                $setting->message = $request->is_student_can_pay_fees;
                $setting->save();
            }

            // Handle installment fees configuration
            if (isset($request->enable_fees_installment)) {
                if (Settings::where('type', 'enable_fees_installment')->exists()) {
                    $data = [
                        'message' => $request->enable_fees_installment,
                    ];
                    Settings::where('type', 'enable_fees_installment')->update($data);
                } else {
                    $setting = new Settings;
                    $setting->type = 'enable_fees_installment';
                    $setting->message = $request->enable_fees_installment;
                    $setting->save();
                }
            }

            if (isset($request->default_due_charges)) {
                if (Settings::where('type', 'default_due_charges')->exists()) {
                    $data = [
                        'message' => $request->default_due_charges,
                    ];
                    Settings::where('type', 'default_due_charges')->update($data);
                } else {
                    $setting = new Settings;
                    $setting->type = 'default_due_charges';
                    $setting->message = $request->default_due_charges;
                    $setting->save();
                }
            }

            $response = [
                'error' => false,
                'message' => trans('data_update_successfully'),
            ];
        } catch (\Throwable $e) {
            $response = [
                'error' => true,
                'message' => trans('error_occurred'),
            ];
        }

        return response()->json($response);
    }
}
