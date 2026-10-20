    <div class="row">

        {{-- Basic Information --}}
        <div class="col-md-6 mb-4">
            <div class="report-section-title">{{ __('basic_information') }}</div>

            <div class="report-info-row">
                <span class="report-info-label">{{ __('admission_date') }}</span>
                <span class="report-info-value">{{ $student->admission_date->format('d-m-Y') }}</span>
            </div>

            <div class="report-info-row">
                <span class="report-info-label">{{ __('blood_group') }}</span>
                <span class="report-info-value">{{ $student->blood_group }}</span>
            </div>
        </div>

        {{-- Guardian Information --}}
        <div class="col-md-6 mb-4">
            <div class="report-section-title">{{ __('parent_guardian_info') }}</div>

            <div class="report-info-row">
                <span class="report-info-label">{{ __('name') }}</span>
                <span class="report-info-value">{{ $student->guardian?->first_name }}</span>
            </div>

            <div class="report-info-row">
                <span class="report-info-label">{{ __('email') }}</span>
                <span class="report-info-value">{{ $student->guardian?->email }}</span>
            </div>

            <div class="report-info-row">
                <span class="report-info-label">{{ __('mobile') }}</span>
                <span class="report-info-value">{{ $student->guardian?->mobile }}</span>
            </div>
        </div>

        {{-- Address Information --}}
        <div class="col-md-6">
            <div class="report-section-title">{{ __('address_information') }}</div>

            <div class="report-info-row">
                <span class="report-info-label">{{ __('current_address') }}</span>
                <span class="report-info-value">{{ $student->user?->current_address }}</span>
            </div>

            <div class="report-info-row">
                <span class="report-info-label">{{ __('permanent_address') }}</span>
                <span class="report-info-value">{{ $student->user?->permanent_address }}</span>
            </div>
        </div>

    </div>
