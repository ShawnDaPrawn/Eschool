<?php

declare(strict_types=1);

namespace App\Services\Panel;

use App\Models\SessionYear;

/**
 * Which school year the admin panel is showing.
 *
 * The current year is the school's (the `session_year` setting, what the apps
 * show). The selected year is what this panel session looks at: the year
 * switcher in the header stores it in the session through
 * SessionYearController::setSessionYearToSessionStorage. Until someone picks
 * another year, the selected year is the current one. Any other year is shown
 * read-only (see the EnforceReadOnlyYears middleware).
 */
class SessionYearContext
{
    private bool $loaded = false;

    private ?SessionYear $current = null;

    private ?SessionYear $selected = null;

    public function currentId(): ?int
    {
        return $this->current()?->id;
    }

    public function selectedId(): ?int
    {
        return $this->selected()?->id;
    }

    public function current(): ?SessionYear
    {
        $this->load();

        return $this->current;
    }

    /** The year the panel shows; the current year when none (or a deleted one) was picked. */
    public function selected(): ?SessionYear
    {
        $this->load();

        return $this->selected ?? $this->current;
    }

    /** True while the panel shows a year other than the school's current one. */
    public function isReadOnly(): bool
    {
        $current = $this->currentId();
        $selected = $this->selectedId();

        return $current !== null && $selected !== null && $current !== $selected;
    }

    /** Whether the selected year comes after the current one (a year being prepared). */
    public function selectedIsUpcoming(): bool
    {
        $current = $this->current();
        $selected = $this->selected();

        return $this->isReadOnly() && $current && $selected && $selected->start_date > $current->start_date;
    }

    private function load(): void
    {
        if ($this->loaded) {
            return;
        }
        $this->loaded = true;

        $currentId = getSettings('session_year')['session_year'] ?? null;
        $this->current = $currentId ? SessionYear::find((int) $currentId) : null;

        $selectedId = request()->hasSession() ? request()->session()->get('session_year') : null;
        if ($selectedId && (int) $selectedId !== $this->current?->id) {
            $this->selected = SessionYear::find((int) $selectedId);
        }
    }
}
