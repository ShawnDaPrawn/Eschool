<?php

declare(strict_types=1);

namespace App\Services\Platform\Saas;

/**
 * The product modules NavigateEd sells (config/product_modules.php) and which
 * package includes which (PlatformPackages, the current module set of each).
 * Specification: engineering/specs/platform/product-catalogue.md.
 */
class ProductCatalogue
{
    public const READINESS = [1 => 'Designed', 2 => 'Partly built', 3 => 'Built, unproven', 4 => 'Verified', 5 => 'Production grade'];

    /** Only these readiness levels may be offered to paying schools. */
    public const SELLABLE = [4, 5];

    public function __construct(private readonly PlatformPackages $packages) {}

    /** @return list<array<string, mixed>> */
    public function products(): array
    {
        $packages = [];
        foreach ($this->packages->keys() as $package) {
            $packages[$package] = $this->packages->technicalModules($package);
        }
        $rows = [];
        foreach ((array) config('product_modules') as $key => $product) {
            $included = [];
            foreach ($packages as $package => $modules) {
                $missing = array_diff($product['modules'], $modules);
                $included[$package] = $missing === [] ? 'all' : (count($missing) < count($product['modules']) ? 'some' : 'none');
            }
            $rows[] = ['key' => $key, 'label' => $product['label'], 'description' => $product['description'], 'always_included' => (bool) $product['always_included'],
                'readiness' => (int) $product['readiness'], 'readiness_label' => self::READINESS[(int) $product['readiness']] ?? '',
                'sellable' => in_array((int) $product['readiness'], self::SELLABLE, true),
                'modules' => array_map(fn (string $module) => (string) config('modules.'.$module.'.name', $module), $product['modules']), 'packages' => $included];
        }

        return $rows;
    }

    /** @return list<string> */
    public function packages(): array
    {
        return $this->packages->keys();
    }
}
