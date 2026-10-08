<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Lms\Course;
use Illuminate\Support\Facades\Auth;
use App\Models\Lms\CourseEnrollment;

class CourseController extends Controller
{
    public function index()
    {
        $courses = Course::where('is_published', true)->paginate(12);
        return view('experience.pages.lms.index', compact('courses'));
    }

    public function show(Course $course)
    {
        $enrolled = Auth::check() ? CourseEnrollment::where('user_id', Auth::id())->where('course_id', $course->id)->exists() : false;
        return view('experience.pages.lms.show', compact('course', 'enrolled'));
    }

    public function enroll(Course $course)
    {
        if (!Auth::check()) return redirect()->route('login');
        CourseEnrollment::firstOrCreate(['user_id' => Auth::id(), 'course_id' => $course->id]);
        return redirect()->route('lms.show', $course->id)->with('success', 'Enrolled successfully!');
    }

    public function myCourses()
    {
        $enrollments = CourseEnrollment::with('course')->where('user_id', Auth::id())->latest()->get();
        return view('experience.pages.lms.my-courses', compact('enrollments'));
    }
}
