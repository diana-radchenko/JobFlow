<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InterviewPreparationController;
use App\Http\Controllers\InterviewSessionController;
use App\Http\Controllers\JobSelectionController;
use App\Http\Controllers\RequestTrackerController;
use App\Http\Controllers\ResumeAnalysisController;
use App\Http\Controllers\ResumeAssistantController;
use App\Http\Controllers\ResumeController;
use App\Http\Controllers\ResumeEditorController;
use App\Http\Controllers\ResumeScoreController;
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

    // Resumes (list/create/rename/delete/duplicate)
    Route::get('resumes', [ResumeController::class, 'index'])->name('resumes.index');
    Route::post('resumes', [ResumeController::class, 'store'])->name('resumes.store');
    Route::put('resumes/{resume}', [ResumeController::class, 'update'])->name('resumes.update');
    Route::delete('resumes/{resume}', [ResumeController::class, 'destroy'])->name('resumes.destroy');
    Route::post('resumes/{resume}/duplicate', [ResumeController::class, 'duplicate'])->name('resumes.duplicate');

    // AI Resume Scoring (dashboard "Score Resume" action)
    Route::post('resume-score', [ResumeScoreController::class, 'store'])->name('resume-score.store');

    // AI Resume Analysis (resume editor summary "Analyze Resume" action)
    Route::post('resume-analysis', [ResumeAnalysisController::class, 'store'])->name('resume-analysis.store');

    // Resume Editor Routes
    Route::get('resume-editor/{resume}', [ResumeEditorController::class, 'show'])->name('resume-editor.show');
    Route::get('resume-editor/{resume}/summary', [ResumeEditorController::class, 'showSummary'])->name('resume-editor.summary');
    Route::get('resume-editor/{resume}/assistant', [ResumeEditorController::class, 'showAssistant'])->name('resume-editor.assistant');

    // Personal Info (shared across all of a user's resumes)
    Route::post('resume-editor/personal-info', [ResumeEditorController::class, 'updatePersonalInfo'])->name('resume-editor.personal-info.update');

    // Work Experience
    Route::post('resume-editor/{resume}/work-experience', [ResumeEditorController::class, 'storeWorkExperience'])->name('resume-editor.work-experience.store');
    Route::put('resume-editor/{resume}/work-experience/{workExperience}', [ResumeEditorController::class, 'updateWorkExperience'])->name('resume-editor.work-experience.update');
    Route::delete('resume-editor/{resume}/work-experience/{workExperience}', [ResumeEditorController::class, 'destroyWorkExperience'])->name('resume-editor.work-experience.destroy');

    // Education
    Route::post('resume-editor/{resume}/education', [ResumeEditorController::class, 'storeEducation'])->name('resume-editor.education.store');
    Route::put('resume-editor/{resume}/education/{education}', [ResumeEditorController::class, 'updateEducation'])->name('resume-editor.education.update');
    Route::delete('resume-editor/{resume}/education/{education}', [ResumeEditorController::class, 'destroyEducation'])->name('resume-editor.education.destroy');

    // Skills
    Route::post('resume-editor/{resume}/skill', [ResumeEditorController::class, 'storeSkill'])->name('resume-editor.skill.store');
    Route::put('resume-editor/{resume}/skill/{skill}', [ResumeEditorController::class, 'updateSkill'])->name('resume-editor.skill.update');
    Route::delete('resume-editor/{resume}/skill/{skill}', [ResumeEditorController::class, 'destroySkill'])->name('resume-editor.skill.destroy');

    // Projects
    Route::post('resume-editor/{resume}/project', [ResumeEditorController::class, 'storeProject'])->name('resume-editor.project.store');
    Route::put('resume-editor/{resume}/project/{project}', [ResumeEditorController::class, 'updateProject'])->name('resume-editor.project.update');
    Route::delete('resume-editor/{resume}/project/{project}', [ResumeEditorController::class, 'destroyProject'])->name('resume-editor.project.destroy');

    // Additional Info (per resume; new resumes start with a copy of the most recent one)
    Route::post('resume-editor/{resume}/additional-info', [ResumeEditorController::class, 'updateAdditionalInfo'])->name('resume-editor.additional-info.update');

    // AI Assistant chat (builds resume data via conversation)
    Route::post('resume-editor/{resume}/assistant/message', [ResumeAssistantController::class, 'message'])->name('resume-editor.assistant.message');

    // Include/exclude & reorder pool items within a resume
    Route::post('resume-editor/{resume}/items/{type}/{item}/toggle', [ResumeEditorController::class, 'toggleItem'])->name('resume-editor.items.toggle');
    Route::post('resume-editor/{resume}/items/{type}/reorder', [ResumeEditorController::class, 'reorderItems'])->name('resume-editor.items.reorder');

    Route::get('interview-preparation', InterviewPreparationController::class)->name('interview-preparation');
    Route::get('salary', function () {
        return Inertia::render('Salary');
    })->name('salary');
    Route::get('development', function () {
        return Inertia::render('Development');
    })->name('development');
    Route::post('interview-sessions', [InterviewSessionController::class, 'store'])->name('interview-session.store');
    Route::get('interview-sessions/{session}', [InterviewSessionController::class, 'show'])->name('interview-session.show');
    Route::post('interview-sessions/{session}/message', [InterviewSessionController::class, 'message'])->name('interview-session.message');
    Route::post('interview-sessions/{session}/audio', [InterviewSessionController::class, 'audio'])->name('interview-session.audio');
    Route::post('interview-sessions/{session}/transcribe', [InterviewSessionController::class, 'transcribe'])->name('interview-session.transcribe');
    Route::post('interview-sessions/{session}/complete', [InterviewSessionController::class, 'complete'])->name('interview-session.complete');
});

require __DIR__.'/settings.php';
