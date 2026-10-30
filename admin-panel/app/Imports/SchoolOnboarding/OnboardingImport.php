<?php

declare(strict_types=1);

namespace App\Imports\SchoolOnboarding;

use App\Models\User;

/** One kind of onboarding import: its columns, its checks and its writes. */
interface OnboardingImport
{
    /** The kind's key in URLs and forms. */
    public function key(): string;

    /** The school permission needed to import this kind. */
    public function permission(): string;

    /**
     * The values a row can carry: field => label, whether required, and the
     * header names that are recognised for it (compared without case or spaces).
     *
     * @return array<string, array{label: string, required: bool, aliases: list<string>}>
     */
    public function fields(): array;

    /**
     * Checks every row without writing: status create, exists, transfer or
     * error, with notes for people, and the data commit() needs.
     *
     * @param  array<string, string>  $mapping  field => header
     * @param  array<string, mixed>  $options
     * @return list<array{line: int, label: string, status: string, notes: list<string>, data?: array<string, mixed>}>
     */
    public function plan(CsvSheet $sheet, array $mapping, array $options): array;

    /**
     * Writes the rows plan() marked "create". Runs inside the import's transaction.
     *
     * @param  list<array<string, mixed>>  $rows
     * @param  array<string, mixed>  $options
     * @return array<string, int>
     */
    public function commit(User $actor, array $rows, array $options): array;
}
