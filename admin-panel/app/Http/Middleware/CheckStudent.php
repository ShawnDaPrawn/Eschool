<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\ClassSection;
use App\Models\ClassTeacher;
use App\Models\Students;
use App\Models\Teacher;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CheckStudent
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response|RedirectResponse)  $next
     * @return Response|RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $user_id = $request->user()->id;
        $teacher_id = Teacher::where('user_id', $user_id)->pluck('id')->first();
        $class_section_ids = ClassTeacher::where('class_teacher_id', $teacher_id)->pluck('class_section_id');
        // $class_section_id = ClassSection::where('class_teacher_id',$teacher_id)->pluck('id')->first();
        $student_class_section_id = Students::where('id', $request->student_id)->pluck('class_section_id')->first();

        if (! $class_section_ids->contains($student_class_section_id)) {
            return response()->json([
                'error' => true,
                'message' => 'Invalid Student ID Passed.',
                'code' => 105,
            ]);
        }

        return $next($request);
    }
}
