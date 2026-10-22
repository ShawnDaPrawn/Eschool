<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ClassSection;
use App\Models\SessionYear;
use App\Models\Settings;
use App\Services\IdCardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

/**
 * Admin panel: ID card layout settings and ID card generation.
 */
class IdCardController extends Controller
{
    public function generateIdCardIndex()
    {
        if (! Auth::user()->can('generate-id-card')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return response()->json($response);
        }
        $class_section = collect();
        $session_years = SessionYear::orderBy('id', 'ASC')->get();

        $is_teacher = Auth::user()->hasRole('Teacher');

        if (! $is_teacher) {
            $class_section = ClassSection::with('class', 'section', 'class.medium', 'class.streams')->get();
        }

        // For teachers, class sections will be loaded via AJAX based on selected session year
        return view('students.generate_id', compact('class_section', 'session_years', 'is_teacher'));
    }

    public function idCardSettingIndex()
    {
        if (! Auth::user()->can('id-card-setting')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return response()->json($response);
        }

        $settings = getSettings();
        $settings['student_id_card_fields'] = explode(',', $settings['student_id_card_fields'] ?? '');
        $settings['staff_id_card_fields'] = explode(',', $settings['staff_id_card_fields'] ?? '');

        return view('students.id_card_settings', compact('settings'));
    }

    public function updateIdCardSetting(Request $request)
    {
        if (! Auth::user()->can('setting-create')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }
        $request->validate([
            'header_color' => 'required',
            'footer_color' => 'required',
            'header_footer_text_color' => 'required',
            'layout_type' => 'required',
            'profile_image_style' => 'required',
            // CR80 (bank-card) bounds, orientation-aware: the long side is 70–100 mm, the short side
            // 45–65 mm, aspect 1.45–1.65. Landscape => width > height; portrait => height > width.
            'card_width' => ['required', 'numeric', function ($attribute, $value, $fail) use ($request) {
                $w = (float) $value;
                $h = (float) $request->card_height;
                if ($w <= 0 || $h <= 0) {
                    $fail(trans('card_size_validation'));

                    return;
                }
                $isHorizontal = $request->layout_type === 'horizontal';
                $rangeOk = $isHorizontal
                    ? ($w >= 70 && $w <= 100 && $h >= 45 && $h <= 65 && $w > $h)
                    : ($w >= 45 && $w <= 65 && $h >= 70 && $h <= 100 && $h > $w);
                $ratio = max($w, $h) / min($w, $h);
                if (! $rangeOk || $ratio < 1.45 || $ratio > 1.65) {
                    $fail(trans('card_size_validation'));
                }
            }],
            'card_height' => 'required|numeric',
            'student_id_card_fields' => 'required',
            'staff_id_card_fields' => 'required',
            'background_image' => 'nullable|image|max:2048',
            'background_transparency' => 'nullable|numeric|min:0|max:100',
            'background_size' => 'nullable|numeric|min:20|max:100',
            'signature' => 'nullable',
        ], [
            'student_id_card_fields.required' => 'Please Select at least one field.',
            'staff_id_card_fields.required' => 'Please Select at least one field.',
        ]);

        $settings = [
            'header_color',
            'footer_color',
            'header_footer_text_color',
            'layout_type',
            'profile_image_style',
            'card_width',
            'card_height',
            'student_id_card_fields',
            'staff_id_card_fields',
            'background_transparency',
            'background_size',
        ];
        // Field-set rows are stored as comma-separated strings; everything else is a plain scalar.
        $fieldSetRows = ['student_id_card_fields', 'staff_id_card_fields'];
        try {
            foreach ($settings as $row) {
                if (Settings::where('type', $row)->exists()) {

                    // removing the double unnecessary double quotes in school name
                    if (in_array($row, $fieldSetRows)) {
                        $data = [
                            'message' => implode(',', $request->$row),
                        ];
                    } else {
                        $data = [
                            'message' => $request->$row,
                        ];
                    }
                    Settings::where('type', $row)->update($data);
                } else {
                    $setting = new Settings;
                    $setting->type = $row;
                    $setting->message = in_array($row, $fieldSetRows) ? implode(',', $request->$row) : $request->$row;
                    $setting->save();
                }

                if ($request->hasFile('background_image')) {
                    if (Settings::where('type', 'background_image')->exists()) {
                        $get_id = Settings::select('message')->where('type', 'background_image')->pluck('message')->first();
                        if (Storage::disk('public')->exists($get_id)) {
                            Storage::disk('public')->delete($get_id);
                        }
                        $data = [
                            'message' => $request->file('background_image')->store('Idcard', 'public'),
                        ];
                        Settings::where('type', 'background_image')->update($data);
                    } else {
                        $setting = new Settings;
                        $setting->type = 'background_image';
                        $setting->message = $request->file('background_image')->store('Idcard', 'public');
                        $setting->save();
                    }
                }

                if ($request->hasFile('signature')) {
                    if (Settings::where('type', 'signature')->exists()) {
                        $get_id = Settings::select('message')->where('type', 'signature')->pluck('message')->first();
                        if (Storage::disk('public')->exists($get_id)) {
                            Storage::disk('public')->delete($get_id);
                        }
                        $data = [
                            'message' => $request->file('signature')->store('Idcard', 'public'),
                        ];
                        Settings::where('type', 'signature')->update($data);
                    } else {
                        $setting = new Settings;
                        $setting->type = 'signature';
                        $setting->message = $request->file('signature')->store('Idcard', 'public');
                        $setting->save();
                    }
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

    public function deleteImage(Request $request)
    {
        if (! Auth::user()->can('setting-create')) {
            return response()->json(['error' => true, 'message' => trans('no_permission_message')]);
        }
        // Only the ID card images can be removed here, never other settings rows.
        if (! in_array($request->type, ['background_image', 'signature'], true)) {
            return response()->json(['error' => true, 'message' => trans('error_occurred')]);
        }
        try {
            $setting = Settings::where('type', $request->type)->firstOrFail();

            if (Storage::disk('public')->exists($setting->getRawOriginal('message'))) {
                Storage::disk('public')->delete($setting->getRawOriginal('message'));
            }
            $setting->delete();

            $response = [
                'error' => false,
                'message' => trans('data_delete_successfully'),
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

    public function generateIdCard(Request $request, IdCardService $idCardService)
    {
        if (! Auth::user()->can('generate-id-card')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }
        $ids = explode(',', $request->user_id);

        try {
            return $idCardService->buildPdf($ids)->stream('id_card.pdf');
        } catch (RuntimeException $e) {
            return redirect()->route('id_card_setting.index')->with('error', $e->getMessage());
        }
    }
}
