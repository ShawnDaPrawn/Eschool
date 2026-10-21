<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fees;

use App\Http\Controllers\Controller;
use App\Models\Settings;
use App\Models\Students;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * Admin panel: fee settings: currency, how compulsory fees are paid, whether students can pay
 * online, and the payment gateways (Razorpay, Stripe, Paystack, Flutterwave).
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
        $validator = Validator::make($request->all(), [
            'razorpay_status' => 'required',
            'razorpay_secret_key' => 'required_if:razorpay_status,1|nullable',
            'razorpay_api_key' => 'required_if:razorpay_status,1|nullable',
            'razorpay_webhook_secret' => 'required_if:razorpay_status,1|nullable',
            'razorpay_webhook_url' => 'required_if:razorpay_status,1|nullable',
            'razorpay_currency_code' => 'required_if:razorpay_status,1|nullable',
            'stripe_status' => 'required',
            'stripe_publishable_key' => 'required_if:stripe_status,1|nullable',
            'stripe_secret_key' => 'required_if:stripe_status,1|nullable',
            'stripe_webhook_secret' => 'required_if:stripe_status,1|nullable',
            'stripe_webhook_url' => 'required_if:stripe_status,1|nullable',
            'stripe_currency_code' => 'required_if:stripe_status,1|nullable',
            'paystack_status' => 'required',
            'paystack_public_key' => 'required_if:paystack_status,1|nullable',
            'paystack_secret_key' => 'required_if:paystack_status,1|nullable',
            'paystack_webhook_url' => 'required_if:paystack_status,1|nullable',
            'paystack_currency_code' => 'required_if:paystack_status,1|nullable',
            'flutterwave_status' => 'required',
            'flutterwave_public_key' => 'required_if:flutterwave_status,1|nullable',
            'flutterwave_secret_key' => 'required_if:flutterwave_status,1|nullable',
            // Webhooks are refused without it, so payments would never be confirmed.
            'flutterwave_hash_key' => 'required_if:flutterwave_status,1|nullable',
            'flutterwave_webhook_url' => 'required_if:flutterwave_status,1|nullable',
            'flutterwave_currency_code' => 'required_if:flutterwave_status,1|nullable',
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

            // razorpay_status
            if (Settings::where('type', 'razorpay_status')->exists()) {
                $data = [
                    'message' => $request->razorpay_status,
                ];
                Settings::where('type', 'razorpay_status')->update($data);
            } else {
                $setting = new Settings;
                $setting->type = 'razorpay_status';
                $setting->message = $request->razorpay_status;
                $setting->save();
            }

            if ($request->razorpay_status) {
                // razorpay_secret_key
                if (Settings::where('type', 'razorpay_secret_key')->exists()) {
                    $data = [
                        'message' => trim($request->razorpay_secret_key),
                    ];
                    Settings::where('type', 'razorpay_secret_key')->update($data);
                } else {
                    $setting = new Settings;
                    $setting->type = 'razorpay_secret_key';
                    $setting->message = trim($request->razorpay_secret_key);
                    $setting->save();
                }

                // razorpay_api_key
                if (Settings::where('type', 'razorpay_api_key')->exists()) {
                    $data = [
                        'message' => trim($request->razorpay_api_key),
                    ];
                    Settings::where('type', 'razorpay_api_key')->update($data);
                } else {
                    $setting = new Settings;
                    $setting->type = 'razorpay_api_key';
                    $setting->message = trim($request->razorpay_api_key);
                    $setting->save();
                }

                // razorpay_webhook_secret
                if (Settings::where('type', 'razorpay_webhook_secret')->exists()) {
                    $data = [
                        'message' => trim($request->razorpay_webhook_secret),
                    ];
                    Settings::where('type', 'razorpay_webhook_secret')->update($data);
                } else {
                    $setting = new Settings;
                    $setting->type = 'razorpay_webhook_secret';
                    $setting->message = trim($request->razorpay_webhook_secret);
                    $setting->save();
                }

                // razorpay_webhook_url
                if (Settings::where('type', 'razorpay_webhook_url')->exists()) {
                    $data = [
                        'message' => trim($request->razorpay_webhook_url),
                    ];
                    Settings::where('type', 'razorpay_webhook_url')->update($data);
                } else {
                    $setting = new Settings;
                    $setting->type = 'razorpay_webhook_url';
                    $setting->message = trim($request->razorpay_webhook_url);
                    $setting->save();
                }

                // razorpay_webhook_url
                if (Settings::where('type', 'razorpay_currency_code')->exists()) {
                    $data = [
                        'message' => trim($request->razorpay_currency_code),
                    ];
                    Settings::where('type', 'razorpay_currency_code')->update($data);
                } else {
                    $setting = new Settings;
                    $setting->type = 'razorpay_currency_code';
                    $setting->message = trim($request->razorpay_currency_code);
                    $setting->save();
                }
            }

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

                // razorpay_webhook_url
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

            // paystack_status
            if (Settings::where('type', 'paystack_status')->exists()) {
                $data = [
                    'message' => $request->paystack_status,
                ];
                Settings::where('type', 'paystack_status')->update($data);
            } else {
                $setting = new Settings;
                $setting->type = 'paystack_status';
                $setting->message = $request->paystack_status;
                $setting->save();
            }

            if ($request->paystack_status) {

                // paystack_public_key
                if (Settings::where('type', 'paystack_public_key')->exists()) {
                    $data = [
                        'message' => trim($request->paystack_public_key),
                    ];
                    Settings::where('type', 'paystack_public_key')->update($data);
                } else {
                    $setting = new Settings;
                    $setting->type = 'paystack_public_key';
                    $setting->message = trim($request->paystack_public_key);
                    $setting->save();
                }

                // paystack_secret_key
                if (Settings::where('type', 'paystack_secret_key')->exists()) {
                    $data = [
                        'message' => trim($request->paystack_secret_key),
                    ];
                    Settings::where('type', 'paystack_secret_key')->update($data);
                } else {
                    $setting = new Settings;
                    $setting->type = 'paystack_secret_key';
                    $setting->message = trim($request->paystack_secret_key);
                    $setting->save();
                }

                // paystack_webhook_url
                if (Settings::where('type', 'paystack_webhook_url')->exists()) {
                    $data = [
                        'message' => trim($request->paystack_webhook_url),
                    ];
                    Settings::where('type', 'paystack_webhook_url')->update($data);
                } else {
                    $setting = new Settings;
                    $setting->type = 'paystack_webhook_url';
                    $setting->message = trim($request->paystack_webhook_url);
                    $setting->save();
                }

                // paystack_webhook_url
                if (Settings::where('type', 'paystack_payment_url')->exists()) {
                    $data = [
                        'message' => trim($request->paystack_payment_url),
                    ];
                    Settings::where('type', 'paystack_payment_url')->update($data);
                } else {
                    $setting = new Settings;
                    $setting->type = 'paystack_payment_url';
                    $setting->message = trim($request->paystack_payment_url);
                    $setting->save();
                }

                // paystack_currency_code
                if (Settings::where('type', 'paystack_currency_code')->exists()) {
                    $data = [
                        'message' => trim($request->paystack_currency_code),
                    ];
                    Settings::where('type', 'paystack_currency_code')->update($data);
                } else {
                    $setting = new Settings;
                    $setting->type = 'paystack_currency_code';
                    $setting->message = trim($request->paystack_currency_code);
                    $setting->save();
                }
            }

            // flutterwave_status
            if (Settings::where('type', 'flutterwave_status')->exists()) {
                $data = [
                    'message' => $request->flutterwave_status,
                ];
                Settings::where('type', 'flutterwave_status')->update($data);
            } else {
                $setting = new Settings;
                $setting->type = 'flutterwave_status';
                $setting->message = $request->flutterwave_status;
                $setting->save();
            }

            if ($request->flutterwave_status) {

                // flutterwave_public_key
                if (Settings::where('type', 'flutterwave_public_key')->exists()) {
                    $data = [
                        'message' => trim($request->flutterwave_public_key),
                    ];
                    Settings::where('type', 'flutterwave_public_key')->update($data);
                } else {
                    $setting = new Settings;
                    $setting->type = 'flutterwave_public_key';
                    $setting->message = trim($request->flutterwave_public_key);
                    $setting->save();
                }

                // flutterwave_secret_key
                if (Settings::where('type', 'flutterwave_secret_key')->exists()) {
                    $data = [
                        'message' => trim($request->flutterwave_secret_key),
                    ];
                    Settings::where('type', 'flutterwave_secret_key')->update($data);
                } else {
                    $setting = new Settings;
                    $setting->type = 'flutterwave_secret_key';
                    $setting->message = trim($request->flutterwave_secret_key);
                    $setting->save();
                }

                // flutterwave_hash_key (the webhook secret hash)
                if (Settings::where('type', 'flutterwave_hash_key')->exists()) {
                    $data = [
                        'message' => trim($request->flutterwave_hash_key),
                    ];
                    Settings::where('type', 'flutterwave_hash_key')->update($data);
                } else {
                    $setting = new Settings;
                    $setting->type = 'flutterwave_hash_key';
                    $setting->message = trim($request->flutterwave_hash_key);
                    $setting->save();
                }

                // flutterwave_webhook_url
                if (Settings::where('type', 'flutterwave_webhook_url')->exists()) {
                    $data = [
                        'message' => trim($request->flutterwave_webhook_url),
                    ];
                    Settings::where('type', 'flutterwave_webhook_url')->update($data);
                } else {
                    $setting = new Settings;
                    $setting->type = 'flutterwave_webhook_url';
                    $setting->message = trim($request->flutterwave_webhook_url);
                    $setting->save();
                }

                // flutterwave_currency_code
                if (Settings::where('type', 'flutterwave_currency_code')->exists()) {
                    $data = [
                        'message' => trim($request->flutterwave_currency_code),
                    ];
                    Settings::where('type', 'flutterwave_currency_code')->update($data);
                } else {
                    $setting = new Settings;
                    $setting->type = 'flutterwave_currency_code';
                    $setting->message = trim($request->flutterwave_currency_code);
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
