<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Session;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * The current language's labels for the admin panel's JavaScript, as
     * window.languageLabels. Falls back to English when the language has no
     * file (for example a languages row whose upload was removed).
     */
    public function readLanguageFile(): Response
    {
        $code = Session::get('language')->code ?? 'en';
        $file = resource_path('lang/'.basename((string) $code).'.json');
        if (! File::exists($file)) {
            $file = resource_path('lang/en.json');
        }

        return response('window.languageLabels = '.File::get($file), 200, ['Content-Type' => 'text/javascript']);
    }
}
