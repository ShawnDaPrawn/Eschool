<input type="hidden" name="expected_account_id" value="{{ auth()->id() }}">
<input type="hidden" name="expected_school_id" value="{{ app(\App\Services\Platform\Tenancy\CurrentSchool::class)->id() }}">
