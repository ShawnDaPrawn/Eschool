<input type="hidden" name="session_year_id" value="{{ $year->id }}">
<x-page.field :id="$prefix.'-name'" name="name" :label="__('Term name')" :value="$row['name'] ?? ''" required maxlength="191" />
<x-page.field :id="$prefix.'-start'" name="starts_on" type="date" :label="__('Starts on')" :value="$row['starts_on'] ?? ''" required />
<x-page.field :id="$prefix.'-end'" name="ends_on" type="date" :label="__('Ends on')" :value="$row['ends_on'] ?? ''" required />
<x-page.field :id="$prefix.'-from'" name="grade_from" type="select" :label="__('From grade')" :value="$row['grade_from'] ?? 0" :options="$grades" required />
<x-page.field :id="$prefix.'-to'" name="grade_to" type="select" :label="__('To grade')" :value="$row['grade_to'] ?? 12" :options="$grades" required />
<x-page.field :id="$prefix.'-lock'" name="marks_lock_on" type="date" :label="__('Marks lock date (optional)')" :value="$row['marks_lock_on'] ?? ''" />
<x-page.field :id="$prefix.'-reports'" name="reports_on" type="date" :label="__('Report date (optional)')" :value="$row['reports_on'] ?? ''" />
