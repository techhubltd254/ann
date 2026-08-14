<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseEnrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CourseController extends Controller
{
    public function index()
    {
        $courses = Course::where('is_published', true)->paginate(12);
        return view('lms.index', compact('courses'));
    }

    public function show(Course $course)
    {
        $enrolled = Auth::check() ? CourseEnrollment::where('user_id', Auth::id())->where('course_id', $course->id)->exists() : false;
        return view('lms.show', compact('course', 'enrolled'));
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
        return view('lms.my-courses', compact('enrollments'));
    }
}

class SafetyController extends Controller
{
    public function alerts()
    {
        $alerts = \App\Models\SafetyAlert::with('county')->where('is_active', true)->where(function ($q) {
            $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
        })->latest()->get();
        return view('safety.alerts', compact('alerts'));
    }

    public function reportForm()
    {
        $counties = \App\Models\County::orderBy('name')->get();
        return view('safety.report', compact('counties'));
    }

    public function submitReport(Request $request)
    {
        $data = $request->validate([
            'type' => 'required|string|max:50',
            'description' => 'required|string|max:2000',
            'county_id' => 'nullable|exists:counties,id',
            'location' => 'nullable|string|max:255',
        ]);
        \App\Models\IncidentReport::create($data + ['user_id' => Auth::id()]);
        return redirect()->route('safety.alerts')->with('success', 'Report submitted. Authorities have been notified.');
    }
}