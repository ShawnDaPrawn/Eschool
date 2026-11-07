<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\Reading;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reading\ReadingApiRequest;
use App\Services\Reading\ReadingLibrary;
use App\Services\ResponseService;
use Illuminate\Http\JsonResponse;

/** The learner's reading library (engineering/specs/learning/reading-library.md). */
class ReadingController extends Controller
{
    public function shelves(ReadingApiRequest $request, ReadingLibrary $library): JsonResponse
    {
        return ResponseService::successResponse('data_fetch_successfully', $library->shelves($request->learner()));
    }

    public function book(ReadingApiRequest $request, ReadingLibrary $library, int $book): JsonResponse
    {
        return ResponseService::successResponse('data_fetch_successfully', $library->book($request->learner(), $book));
    }

    public function page(ReadingApiRequest $request, ReadingLibrary $library, int $book, int $page): JsonResponse
    {
        return ResponseService::successResponse('data_fetch_successfully', $library->page($request->learner(), $book, $page));
    }

    public function progress(ReadingApiRequest $request, ReadingLibrary $library, int $book): JsonResponse
    {
        return ResponseService::successResponse('data_update_successfully', $library->progress($request->learner(), $book, $request->validated()));
    }

    public function questions(ReadingApiRequest $request, ReadingLibrary $library, int $book): JsonResponse
    {
        return ResponseService::successResponse('data_fetch_successfully', $library->questions($request->learner(), $book));
    }

    public function answer(ReadingApiRequest $request, ReadingLibrary $library, int $question): JsonResponse
    {
        return ResponseService::successResponse('data_store_successfully', $library->answer($request->learner(), $question, (int) $request->validated('choice')));
    }
}
