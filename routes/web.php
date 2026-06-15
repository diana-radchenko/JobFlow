<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InterviewPreparationController;
use App\Http\Controllers\InterviewSessionController;
use App\Http\Controllers\JobSelectionController;
use App\Http\Controllers\RequestTrackerController;
use App\Http\Controllers\ResumeEditorController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::get('request-tracker', [RequestTrackerController::class, 'show'])->name('request-tracker');
    Route::delete('request-tracker/applications/{userWorkJobApplication}', [RequestTrackerController::class, 'destroy'])->name('request-tracker.applications.destroy');

    Route::get('job-selection', [JobSelectionController::class, 'jobSelection'])->name('job-selection');
    Route::get('job-selection/{job}', [JobSelectionController::class, 'show'])->name('job-selection.show');
    Route::post('job-selection/{job}/apply', [JobSelectionController::class, 'apply'])->name('job-selection.apply');

    // Resume Editor Routes
    Route::get('resume-editor', [ResumeEditorController::class, 'show'])->name('resume-editor');

    // Personal Info
    Route::post('resume-editor/personal-info', [ResumeEditorController::class, 'updatePersonalInfo'])->name('resume-editor.personal-info.update');

    // Work Experience
    Route::post('resume-editor/work-experience', [ResumeEditorController::class, 'storeWorkExperience'])->name('resume-editor.work-experience.store');
    Route::put('resume-editor/work-experience/{workExperience}', [ResumeEditorController::class, 'updateWorkExperience'])->name('resume-editor.work-experience.update');
    Route::delete('resume-editor/work-experience/{workExperience}', [ResumeEditorController::class, 'destroyWorkExperience'])->name('resume-editor.work-experience.destroy');

    // Education
    Route::post('resume-editor/education', [ResumeEditorController::class, 'storeEducation'])->name('resume-editor.education.store');
    Route::put('resume-editor/education/{education}', [ResumeEditorController::class, 'updateEducation'])->name('resume-editor.education.update');
    Route::delete('resume-editor/education/{education}', [ResumeEditorController::class, 'destroyEducation'])->name('resume-editor.education.destroy');

    // Skills
    Route::post('resume-editor/skill', [ResumeEditorController::class, 'storeSkill'])->name('resume-editor.skill.store');
    Route::put('resume-editor/skill/{skill}', [ResumeEditorController::class, 'updateSkill'])->name('resume-editor.skill.update');
    Route::delete('resume-editor/skill/{skill}', [ResumeEditorController::class, 'destroySkill'])->name('resume-editor.skill.destroy');

    // Projects
    Route::post('resume-editor/project', [ResumeEditorController::class, 'storeProject'])->name('resume-editor.project.store');
    Route::put('resume-editor/project/{project}', [ResumeEditorController::class, 'updateProject'])->name('resume-editor.project.update');
    Route::delete('resume-editor/project/{project}', [ResumeEditorController::class, 'destroyProject'])->name('resume-editor.project.destroy');

    // Additional Info
    Route::post('resume-editor/additional-info', [ResumeEditorController::class, 'updateAdditionalInfo'])->name('resume-editor.additional-info.update');

    // Summary
    Route::get('resume-editor/summary', [ResumeEditorController::class, 'showSummary'])->name('resume-editor.summary');

    Route::get('interview-preparation', InterviewPreparationController::class)->name('interview-preparation');
    Route::get('salary', function () {
        return Inertia::render('Salary');
    })->name('salary');
    Route::post('interview-sessions', [InterviewSessionController::class, 'store'])->name('interview-session.store');
    Route::get('interview-sessions/{session}', [InterviewSessionController::class, 'show'])->name('interview-session.show');
    Route::post('interview-sessions/{session}/message', [InterviewSessionController::class, 'message'])->name('interview-session.message');
    Route::post('interview-sessions/{session}/audio', [InterviewSessionController::class, 'audio'])->name('interview-session.audio');
    Route::post('interview-sessions/{session}/complete', [InterviewSessionController::class, 'complete'])->name('interview-session.complete');
});

require __DIR__.'/settings.php';
