<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>{{ $settings['school_name'] ?? 'Staff' }} ID Card</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

    @php
        // ---- Pixel mirror of the live preview (.idc-card), scaled to the physical card size ----
        // Identical scaling/layout maths to the student card (students/id_card_template.blade.php);
        // only the data rows differ. dompdf has no flexbox/grid -> tables.
        $headerColor = $settings['header_color'] ?? '#07a5a5';
        $footerColor = $settings['footer_color'] ?? '#56cc99';
        $textColor = $settings['header_footer_text_color'] ?? '#ffffff';
        $isHorizontal = ($settings['layout_type'] ?? 'vertical') === 'horizontal';
        $dateFormat = $settings['date_formate'] ?? 'd-m-Y';

        $cardWmm = (float) ($settings['card_width'] ?? 85.6);
        $cardHpx = (float) rtrim((string) ($settings['card_height'] ?? ''), 'px');
        $cardHmm = $cardHpx > 0 ? $cardHpx / 3.7795275591 : 53.98;
        $refW = $isHorizontal ? 140 : 95;
        $refH = $isHorizontal ? 100 : 155;
        $s = min($cardWmm / $refW, $cardHmm / $refH);
        if (! is_finite($s) || $s <= 0) {
            $s = 1;
        }
        $px = fn ($n) => round($n * $s, 3) . 'px';

        $photoRadius = ($settings['profile_image_style'] ?? 'round') === 'squre' ? $px(12) : '999px';

        $bgSize = (int) ($settings['background_size'] ?? 80);
        $bgOpacity = (100 - (int) ($settings['background_transparency'] ?? 80)) / 100;

        $logoPath = $isHorizontal
            ? (($settings['logo2'] ?? '') ?: ($settings['logo1'] ?? ''))
            : ($settings['logo1'] ?? '');
        $logoSrc = $logoPath ? public_path('storage/') . $logoPath : public_path('assets/logo.svg');
    @endphp

    <style>
        @page {
            margin: 0;
        }

        * {
            font-family: 'DejaVu Sans', sans-serif;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0 !important;
            padding: 0;
        }

        .idc-card {
            position: relative;
            width: 100%;
        }

        .idc-break {
            page-break-after: always;
        }

        /* ---- School-level overlays (same for every staff) — fixed => repeated on every page ---- */
        .idc-bg-wrap {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: {{ $bgSize }}%;
            opacity: {{ $bgOpacity }};
            z-index: 0;
        }

        .idc-bg-wrap img {
            width: 100%;
            height: auto;
        }

        .idc-sign {
            position: fixed;
            right: {{ $px(12) }};
            bottom: {{ $isHorizontal ? $px(26) : $px(30) }};
            text-align: center;
            z-index: 2;
        }

        .idc-sign img {
            height: {{ $px(34) }};
            max-width: {{ $px(92) }};
        }

        .idc-sign .idc-sign-lbl {
            display: block;
            font-size: {{ $px(9) }};
            font-weight: bold;
        }

        .idc-footer {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            background: {{ $footerColor }};
            color: {{ $textColor }};
            font-size: {{ $px(10) }};
            text-align: right;
            letter-spacing: {{ $px(0.6) }};
            padding: {{ $px(5) }} 0;
            z-index: 1;
        }

        .idc-footer .idc-footer-text {
            padding: 0 {{ $px(12) }};
        }

        /* ---- Shared photo (object-fit:cover via background; black border, scaled 120px) ---- */
        .idc-photo {
            width: {{ $px(120) }};
            height: {{ $px(120) }};
            border: {{ $px(1.9) }} solid #1c1c1c;
            padding: {{ $px(2) }};
            border-radius: {{ $photoRadius }};
            background-color: #e9ecef;
            background-size: cover;
            background-position: center center;
            background-repeat: no-repeat;
            display: inline-block;
        }

        /* ---- Shared info rows (preview .idc-row) ---- */
        table.idc-fields {
            width: 100%;
            text-align: left;
            border-collapse: collapse;
        }

        table.idc-fields td {
            font-size: {{ $px(11) }};
            line-height: 1.5;
            padding: {{ $px(2) }} 0;
            vertical-align: top;
        }

        td.idc-flabel {
            font-weight: bold;
            color: #1d2433;
            width: {{ $isHorizontal ? $px(118) : $px(96) }};
            padding-right: {{ $px(6) }};
            white-space: nowrap;
        }

        td.idc-fval {
            color: #333;
        }

        /* ---- VERTICAL (portrait) ---- */
        .idc-vhead {
            position: relative;
            z-index: 1;
            background: {{ $headerColor }};
            color: {{ $textColor }};
            text-align: center;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: {{ $px(0.6) }};
            font-size: {{ $px(13) }};
            line-height: 1.2;
            padding: {{ $px(10) }} {{ $px(12) }};
        }

        .idc-vlogo {
            position: relative;
            z-index: 1;
            text-align: center;
            padding: {{ $px(8) }} 0 {{ $px(4) }};
        }

        .idc-vlogo img {
            height: {{ $px(34) }};
            background: #fff;
            border-radius: {{ $px(6) }};
            padding: {{ $px(2) }};
        }

        .idc-vbody {
            position: relative;
            z-index: 1;
            padding: {{ $px(8) }} {{ $px(16) }} {{ $px(10) }};
            text-align: center;
        }

        .idc-vbody .idc-photo {
            margin-bottom: {{ $px(10) }};
        }

        .idc-vname {
            font-size: {{ $px(16) }};
            text-align: center;
            font-weight: bold;
            color: {{ $headerColor }};
            margin: {{ $px(2) }} 0 {{ $px(10) }};
        }

        /* ---- HORIZONTAL (landscape) ---- */
        table.idc-hhead {
            position: relative;
            z-index: 1;
            width: 100%;
            background: {{ $headerColor }};
            color: {{ $textColor }};
            border-collapse: collapse;
        }

        table.idc-hhead td {
            padding: {{ $px(9) }} {{ $px(14) }};
            vertical-align: middle;
        }

        table.idc-hhead .idc-logocell img {
            height: {{ $px(34) }};
            background: #fff;
            border-radius: {{ $px(5) }};
            padding: {{ $px(2) }};
        }

        table.idc-hhead .idc-namecell {
            text-align: right;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: {{ $px(0.6) }};
            font-size: {{ $px(13) }};
            line-height: 1.15;
        }

        table.idc-hbody {
            position: relative;
            z-index: 1;
            width: 100%;
            border-collapse: collapse;
        }

        table.idc-hbody>tbody>tr>td {
            vertical-align: top;
            padding: {{ $px(14) }} {{ $px(16) }};
        }

        td.idc-photocol {
            width: {{ $px(132) }};
            text-align: center;
            padding-right: 0 !important;
        }

        .idc-hname {
            font-size: {{ $px(18) }};
            text-align: left;
            font-weight: bold;
            color: {{ $headerColor }};
            letter-spacing: {{ $px(0.2) }};
            margin: 0 0 {{ $px(8) }};
        }
    </style>
</head>

<body>
    {{-- School-level overlays: declared once, dompdf repeats fixed elements on every (one-per-staff) page. --}}
    @if ($settings['background_image'] ?? '')
        <div class="idc-bg-wrap"><img src="{{ public_path('storage/') . $settings['background_image'] }}" alt="">
        </div>
    @endif
    @if ($settings['signature'] ?? '')
        <div class="idc-sign">
            <img src="{{ public_path('storage/') . $settings['signature'] }}" alt="">
            {{-- Hardcoded (not translated) so the printed caption matches the always-LTR preview --}}
            <span class="idc-sign-lbl">Signature</span>
        </div>
    @endif
    <div class="idc-footer"><span class="idc-footer-text">{{ $settings['school_address'] ?? '' }}</span></div>

    @foreach ($staffs as $staff)
        @php
            // Cover-cropped photo (preview uses object-fit:cover). User::getImageAttribute returns a full
            // URL, so read the raw column for the on-disk path (same pattern as the student template).
            $staffImage = $staff->getRawOriginal('image')
                ? public_path('storage/') . $staff->getRawOriginal('image')
                : public_path('storage/dummy_logo.jpg');

            // [label, value] rows, gated by the selected staff fields. Labels are fixed English strings
            // so the printed card reads identically regardless of admin locale (matches student card).
            $fields = $settings['staff_id_card_fields'];
            $rows = [];
            if (in_array('designation', $fields)) {
                $rows[] = ['Designation', $staff->roles->pluck('name')->implode(', ')];
            }
            if (in_array('qualification', $fields)) {
                // Only teachers carry a qualification; generic staff have no teacher row -> blank.
                $rows[] = ['Qualification', $staff->teacher?->qualification ?? ''];
            }
            if (in_array('email', $fields)) {
                $rows[] = ['Email', $staff->email ?? ''];
            }
            if (in_array('mobile', $fields)) {
                $rows[] = ['Mobile', $staff->mobile ?? ''];
            }
            if (in_array('dob', $fields)) {
                $rows[] = ['Date of Birth', $staff->dob ? date($dateFormat, strtotime($staff->dob)) : ''];
            }
            if (in_array('gender', $fields)) {
                $rows[] = ['Gender', $staff->gender ?? ''];
            }
            if (in_array('address', $fields)) {
                $rows[] = ['Address', $staff->current_address ?? ''];
            }

            $showName = in_array('staff_name', $fields);
            $staffName = $staff->first_name . ' ' . $staff->last_name;
        @endphp

        <div class="idc-card @unless ($loop->last) idc-break @endunless">
            @if ($isHorizontal)
                {{-- HORIZONTAL: logo-left / name-right header; photo-left / info-right body --}}
                <table class="idc-hhead">
                    <tr>
                        <td class="idc-logocell"><img src="{{ $logoSrc }}" alt=""></td>
                        <td class="idc-namecell">{{ $settings['school_name'] ?? '' }}</td>
                    </tr>
                </table>
                <table class="idc-hbody">
                    <tr>
                        <td class="idc-photocol">
                            <div class="idc-photo" style="background-image: url('{{ $staffImage }}');"></div>
                        </td>
                        <td>
                            @if ($showName)
                                <div class="idc-hname">{{ $staffName }}</div>
                            @endif
                            <table class="idc-fields">
                                @foreach ($rows as $row)
                                    <tr>
                                        <td class="idc-flabel">{{ $row[0] }} :</td>
                                        <td class="idc-fval" style="text-transform: capitalize">{{ $row[1] }}
                                        </td>
                                    </tr>
                                @endforeach
                            </table>
                        </td>
                    </tr>
                </table>
            @else
                {{-- VERTICAL: school-name band ABOVE logo, then photo / name / fields --}}
                <div class="idc-vhead">{{ $settings['school_name'] ?? '' }}</div>
                <div class="idc-vlogo"><img src="{{ $logoSrc }}" alt=""></div>
                <div class="idc-vbody">
                    <div class="idc-photo" style="background-image: url('{{ $staffImage }}');"></div>
                    @if ($showName)
                        <div class="idc-vname">{{ $staffName }}</div>
                    @endif
                    <table class="idc-fields">
                        @foreach ($rows as $row)
                            <tr>
                                <td class="idc-flabel">{{ $row[0] }} :</td>
                                <td class="idc-fval" style="text-transform: capitalize">{{ $row[1] }}</td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            @endif
        </div>
    @endforeach
</body>

</html>
