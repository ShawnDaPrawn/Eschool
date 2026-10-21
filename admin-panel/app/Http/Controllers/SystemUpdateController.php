<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use ZipArchive;

class SystemUpdateController extends Controller
{
    private $destinationPath;

    public function __construct()
    {
        $this->destinationPath = base_path().'/update/tmp/';
    }

    public function index()
    {
        if (! Auth::user()->hasRole('Super Admin')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }
        $system_version = Settings::where('type', 'system_version')->first();

        return view('system-update.index', compact('system_version'));
    }

    public function update(Request $request)
    {
        if (! Auth::user()->hasRole('Super Admin')) {
            $response = [
                'error' => true,
                'message' => trans('no_permission_message'),
            ];

            return response()->json($response);
        }
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:zip',
        ]);

        if ($validator->fails()) {
            $response = [
                'error' => true,
                'message' => $validator->errors()->first(),
            ];

            return response()->json($response);
        }
        try {
            if (! is_dir($this->destinationPath)) {
                mkdir($this->destinationPath, 0777, true);
            }

            // zip upload
            $zipfile = $request->file('file');
            $fileName = $zipfile->getClientOriginalName();
            $zipfile->move($this->destinationPath, $fileName);

            // $target_path = getcwd() . DIRECTORY_SEPARATOR;
            $target_path = base_path().DIRECTORY_SEPARATOR;

            $zip = new ZipArchive;
            $filePath = $this->destinationPath.'/'.$fileName;
            $zipStatus = $zip->open($filePath);
            if ($zipStatus) {
                $zip->extractTo($this->destinationPath);
                $zip->close();
                unlink($filePath);

                $ver_file = $this->destinationPath.'version_info.php';
                $source_path = $this->destinationPath.'source_code.zip';
                if (file_exists($ver_file) && file_exists($source_path)) {
                    $ver_file1 = $target_path.'version_info.php';
                    $source_path1 = $target_path.'source_code.zip';
                    if (rename($ver_file, $ver_file1) && rename($source_path, $source_path1)) {
                        $version_file = require_once $ver_file1;
                        $current_version = getSettings('system_version');
                        if ($current_version['system_version'] == $version_file['current_version']) {
                            $zip1 = new ZipArchive;
                            $zipFile1 = $zip1->open($source_path1);
                            if ($zipFile1 === true) {
                                $zip1->extractTo($target_path); // change this to the correct site path
                                $zip1->close();

                                Artisan::call('migrate --force');
                                Artisan::call('db:seed --class=InstallationSeeder --force');
                                Artisan::call('db:seed --class=EmailSeeder --force');

                                unlink($source_path1);
                                unlink($ver_file1);
                                Settings::where('type', 'system_version')->update([
                                    'message' => $version_file['update_version'],
                                ]);
                                $response = [
                                    'error' => false,
                                    'message' => trans('system_update_successfully'),
                                ];

                                return response()->json($response);
                            } else {
                                unlink($source_path1);
                                unlink($ver_file1);
                                $response = [
                                    'error' => true,
                                    'message' => trans('something_wrong_try_again'),
                                ];

                                return response()->json($response);
                            }
                        } elseif ($current_version['system_version'] == $version_file['update_version']) {
                            unlink($source_path1);
                            unlink($ver_file1);
                            $response = [
                                'error' => true,
                                'message' => trans('system_already_updated'),
                            ];

                            return response()->json($response);
                        } else {
                            unlink($source_path1);
                            unlink($ver_file1);
                            $response = [
                                'error' => true,
                                'message' => $current_version['system_version'].' '.trans('your_version_update_nearest'),
                            ];

                            return response()->json($response);
                        }
                    } else {
                        $response = [
                            'error' => true,
                            'message' => trans('invalid_zip_try_again'),
                            'data' => 1,
                        ];

                        return response()->json($response);
                    }
                } else {
                    $response = [
                        'error' => true,
                        'message' => trans('invalid_zip_try_again'),
                        'data' => 2,
                    ];

                    return response()->json($response);
                }
            } else {
                $response = [
                    'error' => true,
                    'message' => trans('something_wrong_try_again'),
                ];

                return response()->json($response);
            }
        } catch (\Throwable $e) {
            $response = [
                'error' => true,
                'message' => trans('error_occurred'),
                'data' => $e,
            ];
        }

        return response()->json($response);

    }
}
