<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Language;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use Throwable;

class LanguageController extends Controller
{
    /**
     * Language codes name the translation file (resources/lang/{code}.json), so
     * they are limited to letters/digits with an optional region or variant
     * suffix such as "en", "pt-BR" or "eng-rtl".
     */
    private const CODE_RULE = 'regex:/^[A-Za-z]{2,3}([-_][A-Za-z0-9]{2,8})?$/';

    public function index()
    {
        if (! Auth::user()->can('language-create')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }

        return view('settings.language_setting');
    }

    public function download($id)
    {
        if (! Auth::user()->can('language-list')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }

        $language = Language::find($id);
        if (! $language) {
            return redirect()->back()->withErrors(['message' => trans('error_occurred')]);
        }

        $filePath = base_path('resources/lang/'.basename((string) $language->file));
        if (! File::exists($filePath)) {
            return redirect()->back()->withErrors(['message' => trans('error_occurred')]);
        }

        return response()->download($filePath, $language->code.'.json', ['Content-Type: application/json']);
    }

    public function language_sample()
    {
        if (! Auth::user()->can('language-create')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }
        $filePath = base_path('resources/lang/en.json');
        $headers = ['Content-Type: application/json'];
        $fileName = 'language.json';
        if (File::exists(base_path('resources/lang/en.json'))) {
            return response()->download($filePath, $fileName, $headers);
        } else {
            $response = [
                'error' => true,
                'message' => trans('error_occurred'),
            ];

            return response()->json($response);
        }
    }

    public function store(Request $request)
    {
        if (! Auth::user()->can('language-create')) {
            $response = [
                'error' => true,
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }

        $request->validate([
            'name' => 'required',
            'code' => ['required', 'string', 'max:12', self::CODE_RULE, 'unique:languages,code'],
            'file' => 'required|mimes:json',
        ]);

        try {
            $language = new Language;
            $language->name = $request->name;
            $language->code = $request->code;
            $language->status = 0;
            if (isset($request->rtl)) {
                $language->is_rtl = $request->rtl;
            } else {
                $language->is_rtl = 0;
            }
            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $filename = $request->code.'.json';
                $file->move(base_path('resources/lang/'), $filename);
                $language->file = $filename;
            }
            $language->save();
            


            $response = [
                'error' => false,
                'message' => trans('data_store_successfully'),
            ];
        } catch (Throwable $e) {
            Log::error($e);
            $response = [
                'error' => true,
                'message' => trans('error_occurred'),
            ];
        }

        return response()->json($response);
    }

    public function show()
    {
        if (! Auth::user()->can('language-list')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }
        $offset = 0;
        $limit = 10;
        $sort = 'id';
        $order = 'DESC';

        if (isset($_GET['offset'])) {
            $offset = $_GET['offset'];
        }
        if (isset($_GET['limit'])) {
            $limit = $_GET['limit'];
        }

        if (isset($_GET['sort'])) {
            $sort = $_GET['sort'];
        }
        if (isset($_GET['order'])) {
            $order = $_GET['order'];
        }

        $sql = Language::where('id', '!=', 0);
        if (isset($_GET['search']) && ! empty($_GET['search'])) {
            $search = $_GET['search'];
            $sql->where('id', 'LIKE', "%$search%")
                ->orwhere('name', 'LIKE', "%$search%")
                ->orwhere('code', 'LIKE', "%$search%")
                ->orwhere('status', 'LIKE', "%$search%");
        }
        $total = $sql->count();

        $sql->orderBy($sort, $order)->skip($offset)->take($limit);
        $res = $sql->get();

        $bulkData = [];
        $bulkData['total'] = $total;
        $rows = [];
        $tempRow = [];
        $no = 1;
        foreach ($res as $row) {
            $operate = '<a class="btn btn-xs btn-gradient-info btn-rounded btn-icon" href="'.url('language-download', $row->id).'" title="'.trans('download').'"><i class="fa fa-download"></i></a>&nbsp;&nbsp;';
            // English ships as the system fallback language — never expose a /edit delete control for it.
            if ($row->code !== 'en') {
                $operate .= '<a class="btn btn-xs btn-gradient-primary btn-rounded btn-icon editdata" data-id='.$row->id.' title="Edit" data-toggle="modal" data-target="#editModal"><i class="fa fa-edit"></i></a>&nbsp;&nbsp;';
                $operate .= '<a class="btn btn-xs btn-gradient-danger btn-rounded btn-icon deletedata" data-id='.$row->id.'" data-url="'.url('language', $row->id).'" title="Delete"><i class="fa fa-trash"></i></a>';
            }

            $tempRow['id'] = $row->id;
            $tempRow['no'] = $no++;
            $tempRow['name'] = $row->name;
            $tempRow['code'] = $row->code;
            $tempRow['rtl'] = $row->is_rtl;
            $tempRow['status'] = $row->status;
            $tempRow['operate'] = $operate;
            $rows[] = $tempRow;
        }

        $bulkData['rows'] = $rows;

        return response()->json($bulkData);
    }

    public function update(Request $request)
    {

        // dd($request->all(), isset($request->rtl), $request->rtl);
        if (! Auth::user()->can('language-edit')) {
            $response = [
                'error' => true,
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }
        $request->validate([
            'id' => 'required|integer|exists:languages,id',
            'name' => 'required',
            'code' => ['required', 'string', 'max:12', self::CODE_RULE, 'unique:languages,code,'.(int) $request->id],
        ]);

        try {
            $language = Language::find($request->id);
            $language->name = $request->name;
            $language->code = $request->code;
            if ($request->hasFile('file')) {
                $request->validate([
                    'file' => 'required|mimes:json',
                ]);
                $oldFile = basename((string) $language->file);
                if ($oldFile !== '' && File::exists(base_path('resources/lang/').$oldFile)) {
                    File::delete(base_path('resources/lang/').$oldFile);
                }
                $file = $request->file('file');
                $filename = $request->code.'.json';
                $file->move(base_path('resources/lang/'), $filename);
                $language->file = $filename;
            }

            $request->has('rtl') ? $language->is_rtl = 1 : $language->is_rtl = 0;
            $language->save();
           
            $response = [
                'error' => false,
                'message' => trans('data_update_successfully'),
            ];
        } catch (Throwable $e) {
            Log::error($e);
            $response = [
                'error' => true,
                'message' => trans('error_occurred'),
            ];
        }

        return response()->json($response);
    }

    public function destroy($id)
    {
        if (! Auth::user()->can('language-delete')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }
        try {
            $language = Language::find($id);
            // English is the fallback used by readLanguageFile() and the language sample download — refuse to delete it.
            if ($language && $language->code === 'en') {
                return response()->json([
                    'error' => true,
                    'message' => trans('error_occurred'),
                ]);
            }
            $language->delete();
           

            $response = [
                'error' => false,
                'message' => trans('data_delete_successfully'),
            ];
        } catch (Throwable $e) {
            $response = [
                'error' => true,
                'message' => trans('error_occurred'),
            ];
        }

        return response()->json($response);
    }

    public function set_language(Request $request)
    {
        $language = Language::where('code', $request->lang)->first();

        if (! empty($language)) {
            Session::put('locale', $request->lang);
            Session::put('language', (object) $language->toArray());
            Session::save();
            app()->setLocale($language->code);
        }

        // app()->setLocale(Session::get('locale'));
        return redirect()->back();
    }
}
