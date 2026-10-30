<?php

declare(strict_types=1);

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Services\Platform\Saas\ProductCatalogue;
use Illuminate\Contracts\View\View;

/** The product modules, their readiness and prices, and what each package includes. */
class ProductCatalogueController extends Controller
{
    public function __invoke(ProductCatalogue $catalogue): View
    {
        return view('saas.packages', ['products' => $catalogue->products(), 'packages' => $catalogue->packages()]);
    }
}
