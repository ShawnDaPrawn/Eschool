@if($row['proration']??null)
<details><summary>{{ __('Enrolment charge calculation') }}</summary>
<dl><dt>{{ __('Original annual plan rate') }}</dt><dd>{{ \App\Services\Platform\Ledger\Money::ofCents($row['annual_cents'],$row['currency'])->format() }}</dd>
<dt>{{ __('Charged period') }}</dt><dd>{{ $row['period_start'] }} — {{ $row['period_end'] }}</dd>
<dt>{{ __('Join rule') }}</dt><dd>{{ __(['daily'=>'Daily proportion','calendar_month'=>'Calendar-month proportion','full_year'=>'Full annual charge'][$row['proration']['join_basis']]) }}</dd>
<dt>{{ __('Leaver rule') }}</dt><dd>{{ __(['daily'=>'Daily proportion','calendar_month'=>'Calendar-month proportion','no_credit'=>'Charge through the end of the school year'][$row['proration']['leaver_basis']]) }}</dd></dl>
<p>{{ __('This amount uses the reviewed school rule and inclusive enrolment dates, rounded once to cents.') }}</p>
</details>
@endif
