<x-page.field :id="$prefix.'-start'" name="starts_on" type="date" :label="__('Starts on')" :value="$start" required />
<x-page.field :id="$prefix.'-end'" name="ends_on" type="date" :label="__('Ends on')" :value="$end" required />
<x-page.field :id="$prefix.'-reason'" name="reason" :label="__('Reason')" required maxlength="500" />
