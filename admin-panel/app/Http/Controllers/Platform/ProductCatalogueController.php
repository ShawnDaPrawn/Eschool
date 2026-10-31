<?php

declare(strict_types=1);

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Services\Platform\Saas\PlatformPackages;
use App\Services\Platform\Saas\ProductCatalogue;
use Illuminate\Contracts\View\View;

/** The product modules, their readiness, the packages (SA.7) and what each includes. */
class ProductCatalogueController extends Controller
{
    public function __invoke(ProductCatalogue $catalogue, PlatformPackages $packages): View
    {
        return view('saas.packages', ['products' => $catalogue->products(), 'packages' => $catalogue->packages(), 'packageRows' => $packages->list()]);
    }
}
