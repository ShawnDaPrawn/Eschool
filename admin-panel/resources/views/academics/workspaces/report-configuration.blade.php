    @if($configuration['can_design'])
        <section class="workspace-section"><h2>{{ __('Report designs and comment banks') }}</h2>
            <form method="GET" action="{{ route('academic-reports.index') }}" class="report-fields" aria-label="{{ __('Choose report design') }}">
                <input type="hidden" name="view" value="configuration"><input type="hidden" name="session_year_id" value="{{ $configuration['session_year_id'] }}">
                <x-page.field :restore-old-input="false" name="template_id" type="select" :label="__('Design to edit')" :options="array_column($configuration['templates'], 'name', 'id')" :placeholder="__('New design')" :value="$template['id'] ?? ''" />
                <button type="submit" class="m3-button m3-button--outlined">{{ __('Open design') }}</button>
            </form>
            <form method="POST" action="{{ route('academic-reports.templates') }}" aria-label="{{ __('Save report design') }}" class="report-form">@csrf
                <input type="hidden" name="operation_id" value="{{ (string) \Illuminate\Support\Str::uuid() }}"><input type="hidden" name="view" value="configuration"><input type="hidden" name="session_year_id" value="{{ $configuration['session_year_id'] }}">
                @if($template)<input type="hidden" name="id" value="{{ $template['id'] }}"><input type="hidden" name="revision" value="{{ $template['revision'] }}">@endif
                <x-page.field :restore-old-input="false" name="name" :id="'report-design-name'" :label="__('Design name')" :value="$template['name'] ?? ''" required maxlength="191" />
                <x-page.field :restore-old-input="false" name="design[heading]" :label="__('Report heading')" :value="$template['design']['heading'] ?? __('School report')" required maxlength="191" />
                <x-page.field :restore-old-input="false" name="design[introduction]" type="textarea" :label="__('Introduction')" :value="$template['design']['introduction'] ?? ''" maxlength="3000" />
                <x-page.field :restore-old-input="false" name="design[footer]" type="textarea" :label="__('Footer')" :value="$template['design']['footer'] ?? ''" maxlength="3000" />
                <x-page.field :restore-old-input="false" name="design[show_crest]" type="select" :label="__('Include school crest')" :options="[1 => __('Yes'), 0 => __('No')]" :value="(int) ($template['design']['show_crest'] ?? true)" required />
                <fieldset><legend>{{ __('Printed section order') }}</legend><p>{{ __('Choose each section once. Subject results are required.') }}</p>
                    @php $sectionNames = ['subjects' => __('Subject results'), 'attendance' => __('Attendance'), 'subject_comments' => __('Subject comments'), 'homeroom_comment' => __('Homeroom comment')]; $sections = $template['design']['sections'] ?? array_keys($sectionNames); @endphp
                    @foreach(range(0, 3) as $index)<x-page.field :restore-old-input="false" :name="'design[sections]['.$index.']'" type="select" :label="__('Section').' '.($index + 1)" :options="$sectionNames" :placeholder="__('Omit section')" :value="$sections[$index] ?? ''" />@endforeach
                </fieldset>
                <button type="submit" class="m3-button m3-button--filled">{{ __('Save design') }}</button>
            </form>
            <form method="GET" action="{{ route('academic-reports.index') }}" class="report-fields" aria-label="{{ __('Choose comment bank') }}">
                <input type="hidden" name="view" value="configuration"><input type="hidden" name="session_year_id" value="{{ $configuration['session_year_id'] }}"><x-page.field :restore-old-input="false" name="bank_id" type="select" :label="__('Bank entry to edit')" :options="array_column($configuration['comment_banks'], 'label', 'id')" :placeholder="__('New bank entry')" :value="$bank['id'] ?? ''" /><button type="submit" class="m3-button m3-button--outlined">{{ __('Open bank entry') }}</button>
            </form>
            <form method="POST" action="{{ route('academic-reports.banks') }}" aria-label="{{ __('Save comment bank') }}" class="report-form">@csrf
                <input type="hidden" name="operation_id" value="{{ (string) \Illuminate\Support\Str::uuid() }}"><input type="hidden" name="view" value="configuration"><input type="hidden" name="session_year_id" value="{{ $configuration['session_year_id'] }}">
                @if($bank)<input type="hidden" name="id" value="{{ $bank['id'] }}"><input type="hidden" name="revision" value="{{ $bank['revision'] }}">@endif
                <p>{{ __('Keep reusable comments generic. Do not put learner details in a bank.') }}</p>
                <x-page.field :restore-old-input="false" name="scope" type="select" :label="__('Comment scope')" :options="['homeroom' => __('Homeroom'), 'subject' => __('Subject')]" :value="$bank['scope'] ?? 'homeroom'" required />
                <x-page.field :restore-old-input="false" name="subject_id" type="select" :id="'report-bank-subject'" :label="__('Subject for subject comments')" :options="array_column($configuration['subjects'], 'name', 'id')" :placeholder="__('None for homeroom')" :value="$bank['subject_id'] ?? ''" />
                <x-page.field :restore-old-input="false" name="label" :label="__('Bank label')" :value="$bank['label'] ?? ''" required maxlength="191" /><x-page.field :restore-old-input="false" name="text" type="textarea" :id="'report-bank-text'" :label="__('Reusable comment')" :value="$bank['text'] ?? ''" required maxlength="3000" />
                <x-page.field :restore-old-input="false" name="active" type="select" :label="__('Available for comments')" :options="[1 => __('Yes'), 0 => __('No')]" :value="(int) ($bank['active'] ?? true)" required />
                <button type="submit" class="m3-button m3-button--filled">{{ __('Save bank entry') }}</button>
            </form>
        </section>
    @endif
