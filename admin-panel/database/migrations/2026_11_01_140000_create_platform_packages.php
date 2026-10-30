<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Packages and prices in the database (Action Plan SA.7), edited by HQ at
 * /saas/packages instead of being hard-coded in config/school_packages.php.
 *
 * - platform_packages: each package, its status (draft, active, retired),
 *   trial days and the number of its current module set (modules_version).
 * - platform_package_modules: the product modules (config/product_modules.php)
 *   in each version of a package's module set. A version that schools can be
 *   on is never changed; changing an active package's modules adds a version.
 * - platform_prices: effective-dated prices per learner per month in integer
 *   cents, excluding VAT: a package's base price, an add-on price per product
 *   module, and a discount off the base. Never edited once in force.
 * - schools.package_version: the module set version applied to the school;
 *   null is version 1, so every existing school keeps exactly what it has.
 *
 * Seeded from config/school_packages.php as it stands, so nothing changes for
 * any school. engineering/specs/platform/package-editor.md.
 */
return new class extends Migration
{
    /** Frozen copy of the seeded package names (the keys come from the config). */
    private const NAMES = [
        'legacy' => ['Legacy (upgraded seed school)', 'Every module, for the school that ran before packages existed.'],
        'core' => ['Core', 'Everything except Finance.'],
        'standard' => ['Standard', 'Every product module.'],
        'pilot' => ['Design-partner pilot', 'Every product module, for the design-partner pilot (Term 3 and 4 2027).'],
    ];

    public function up(): void
    {
        Schema::create('platform_packages', function (Blueprint $t) {
            $t->id();
            $t->string('package_key', 32)->unique();
            $t->string('name', 120);
            $t->string('description', 1000)->nullable();
            $t->string('status', 12)->default('draft');
            $t->unsignedSmallInteger('trial_days')->default(0);
            $t->unsignedSmallInteger('modules_version')->default(1);
            $t->timestamp('activated_at')->nullable();
            $t->timestamp('retired_at')->nullable();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });
        Schema::create('platform_package_modules', function (Blueprint $t) {
            $t->id();
            $t->foreignId('package_id')->constrained('platform_packages')->restrictOnDelete();
            $t->unsignedSmallInteger('version');
            $t->string('product_module', 32);
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('created_at')->useCurrent();
            $t->unique(['package_id', 'version', 'product_module'], 'platform_package_modules_unique');
        });
        Schema::create('platform_prices', function (Blueprint $t) {
            $t->id();
            $t->foreignId('package_id')->constrained('platform_packages')->restrictOnDelete();
            // base: the package per learner per month; module: an add-on product module; discount: off the base.
            $t->string('kind', 12);
            $t->string('product_module', 32)->nullable();
            $t->unsignedInteger('amount_cents');
            $t->boolean('vat_excluded')->default(true);
            $t->date('effective_from');
            $t->date('effective_to')->nullable();
            $t->string('note', 191)->nullable();
            // The open-ended price this one ended the day before it starts; reopened if this one is withdrawn.
            $t->unsignedBigInteger('closes_price_id')->nullable();
            $t->foreign('closes_price_id')->references('id')->on('platform_prices')->restrictOnDelete();
            $t->timestamp('withdrawn_at')->nullable();
            $t->foreignId('withdrawn_by')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->index(['package_id', 'kind', 'product_module', 'effective_from'], 'platform_prices_series_index');
        });
        Schema::table('schools', function (Blueprint $t) {
            $t->unsignedSmallInteger('package_version')->nullable()->after('package_key');
        });

        $products = (array) config('product_modules');
        foreach ((array) config('school_packages') as $key => $modules) {
            $included = [];
            foreach ($products as $product => $definition) {
                $missing = array_diff($definition['modules'], $modules);
                if ($missing === []) {
                    $included[] = $product;
                } elseif (count($missing) < count($definition['modules'])) {
                    throw new RuntimeException("Package {$key} holds part of product module {$product}; a package is built from whole product modules.");
                }
            }
            $covered = array_merge(...array_map(fn (string $product): array => $products[$product]['modules'], $included ?: ['core']));
            if (array_diff($modules, $covered) !== []) {
                throw new RuntimeException("Package {$key} holds a module that no product module includes.");
            }
            [$name, $description] = self::NAMES[$key] ?? [ucfirst((string) $key), null];
            $id = DB::table('platform_packages')->insertGetId([
                'package_key' => $key, 'name' => $name, 'description' => $description, 'status' => 'active',
                'trial_days' => 0, 'modules_version' => 1, 'activated_at' => now(), 'created_at' => now(), 'updated_at' => now(),
            ]);
            foreach ($included as $product) {
                DB::table('platform_package_modules')->insert(['package_id' => $id, 'version' => 1, 'product_module' => $product, 'created_at' => now()]);
            }
        }
    }

    public function down(): void
    {
        // Only the seed may go: anything HQ added or changed is history.
        $added = DB::table('platform_prices')->exists()
            || DB::table('platform_package_modules')->where('version', '>', 1)->exists()
            || DB::table('platform_packages')->whereNotIn('package_key', array_keys((array) config('school_packages')))->exists()
            || DB::table('schools')->whereNotNull('package_version')->exists()
            || $this->seedChanged();
        if ($added) {
            throw new RuntimeException('Restore a tested backup to retain the package and price history.');
        }
        Schema::table('schools', function (Blueprint $t) {
            $t->dropColumn('package_version');
        });
        Schema::dropIfExists('platform_prices');
        Schema::dropIfExists('platform_package_modules');
        Schema::dropIfExists('platform_packages');
    }

    /**
     * Whether any seeded package differs from what up() wrote: a renamed,
     * re-described, retired or re-timed package, a new module version, or
     * any save after seeding (the editor always moves updated_at).
     */
    private function seedChanged(): bool
    {
        foreach (DB::table('platform_packages')->get() as $row) {
            [$name, $description] = self::NAMES[$row->package_key] ?? [ucfirst((string) $row->package_key), null];
            $seeded = [$row->name === $name, $row->description === $description, $row->status === 'active',
                (int) $row->trial_days === 0, (int) $row->modules_version === 1, $row->retired_at === null,
                $row->created_by === null, (string) $row->updated_at === (string) $row->created_at];
            if (in_array(false, $seeded, true)) {
                return true;
            }
        }

        return false;
    }
};
