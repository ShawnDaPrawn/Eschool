<?php

declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;

/**
 * The product catalogue (config/product_modules.php) covers every switchable
 * module exactly once, and the pilot package is built from it.
 */
class ProductModulesTest extends TestCase
{
    public function test_every_technical_module_is_in_exactly_one_product_module(): void
    {
        $technical = array_keys((array) config('modules'));
        $owners = [];
        foreach ((array) config('product_modules') as $key => $product) {
            $this->assertContains($product['readiness'], [1, 2, 3, 4, 5], $key);
            $this->assertTrue($product['price_cents'] === null || (is_int($product['price_cents']) && $product['price_cents'] >= 0), $key.' price is integer cents or not set');
            $this->assertNotSame('', trim($product['label'].$product['description']));
            foreach ($product['modules'] as $module) {
                $this->assertContains($module, $technical, "{$key}: {$module} is a module ModuleGate knows");
                $owners[$module][] = $key;
            }
        }
        $this->assertCount(10, (array) config('product_modules'));
        foreach ($technical as $module) {
            $this->assertCount(1, $owners[$module] ?? [], "{$module} belongs to exactly one product module");
        }
        $this->assertTrue(config('product_modules.core.always_included'));
    }

    public function test_the_pilot_package_holds_every_product_module_and_core_is_in_every_package(): void
    {
        $all = array_merge(...array_values(array_map(fn ($product) => $product['modules'], (array) config('product_modules'))));
        $pilot = (array) config('school_packages.pilot');
        sort($all);
        sort($pilot);
        $this->assertSame($all, $pilot);
        foreach ((array) config('school_packages') as $package => $modules) {
            $this->assertSame([], array_values(array_diff(config('product_modules.core.modules'), $modules)), "{$package} includes Core");
        }
    }
}
