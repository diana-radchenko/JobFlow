<?php

use App\Enums\UserRole;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Employer\ApplicationController as EmployerApplicationController;
use App\Http\Controllers\Employer\JobController as EmployerJobController;
use App\Http\Controllers\InterviewPreparationController;
use App\Http\Controllers\InterviewSessionController;
use App\Http\Controllers\JobSelectionController;
use App\Http\Controllers\RequestTrackerController;
use App\Http\Controllers\ResumeAnalysisController;
use App\Http\Controllers\ResumeAssistantController;
use App\Http\Controllers\ResumeController;
use App\Http\Controllers\ResumeEditorController;
use App\Http\Controllers\ResumeSalaryAnalysisController;
use App\Http\Controllers\ResumeScoreController;
use App\Models\WorkJob;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'verified', 'role:'.UserRole::Employer->value])
    ->prefix('employer')
    ->name('employer.')
    ->scopeBindings()
    ->group(function () {
        Route::get('jobs', [EmployerJobController::class, 'index'])->name('jobs.index');
        Route::get('jobs/create', [EmployerJobController::class, 'create'])->name('jobs.create');
        Route::post('jobs', [EmployerJobController::class, 'store'])->name('jobs.store');
        Route::get('jobs/{job}', [EmployerJobController::class, 'show'])->name('jobs.show');
        Route::get('jobs/{job}/edit', [EmployerJobController::class, 'edit'])->name('jobs.edit');
        Route::put('jobs/{job}', [EmployerJobController::class, 'update'])->name('jobs.update');
        Route::delete('jobs/{job}', [EmployerJobController::class, 'destroy'])->name('jobs.destroy');

        // scopeBindings keeps {application} constrained to the parent {job}.
        Route::get('jobs/{job}/applications/{application}', [EmployerApplicationController::class, 'show'])->name('applications.show');
        Route::patch('jobs/{job}/applications/{application}', [EmployerApplicationController::class, 'update'])->name('applications.update');
    });

Route::middleware(['auth', 'verified', 'role:'.UserRole::Candidate->value])->group(function () {
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

    // AI Resume Salary Analysis (salary page "Let AI review your resume" card)
    Route::post('resume-salary-analysis', [ResumeSalaryAnalysisController::class, 'store'])->name('resume-salary-analysis.store');

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

    // Volunteer & Community
    Route::post('resume-editor/{resume}/volunteer-experience', [ResumeEditorController::class, 'storeVolunteerExperience'])->name('resume-editor.volunteer-experience.store');
    Route::put('resume-editor/{resume}/volunteer-experience/{volunteerExperience}', [ResumeEditorController::class, 'updateVolunteerExperience'])->name('resume-editor.volunteer-experience.update');
    Route::delete('resume-editor/{resume}/volunteer-experience/{volunteerExperience}', [ResumeEditorController::class, 'destroyVolunteerExperience'])->name('resume-editor.volunteer-experience.destroy');

    // Leadership & Extracurricular
    Route::post('resume-editor/{resume}/leadership-activity', [ResumeEditorController::class, 'storeLeadershipActivity'])->name('resume-editor.leadership-activity.store');
    Route::put('resume-editor/{resume}/leadership-activity/{leadershipActivity}', [ResumeEditorController::class, 'updateLeadershipActivity'])->name('resume-editor.leadership-activity.update');
    Route::delete('resume-editor/{resume}/leadership-activity/{leadershipActivity}', [ResumeEditorController::class, 'destroyLeadershipActivity'])->name('resume-editor.leadership-activity.destroy');

    // Publications
    Route::post('resume-editor/{resume}/publication', [ResumeEditorController::class, 'storePublication'])->name('resume-editor.publication.store');
    Route::put('resume-editor/{resume}/publication/{publication}', [ResumeEditorController::class, 'updatePublication'])->name('resume-editor.publication.update');
    Route::delete('resume-editor/{resume}/publication/{publication}', [ResumeEditorController::class, 'destroyPublication'])->name('resume-editor.publication.destroy');

    // Awards & Honors
    Route::post('resume-editor/{resume}/award-honor', [ResumeEditorController::class, 'storeAwardHonor'])->name('resume-editor.award-honor.store');
    Route::put('resume-editor/{resume}/award-honor/{awardHonor}', [ResumeEditorController::class, 'updateAwardHonor'])->name('resume-editor.award-honor.update');
    Route::delete('resume-editor/{resume}/award-honor/{awardHonor}', [ResumeEditorController::class, 'destroyAwardHonor'])->name('resume-editor.award-honor.destroy');

    // Languages
    Route::post('resume-editor/{resume}/language', [ResumeEditorController::class, 'storeLanguage'])->name('resume-editor.language.store');
    Route::put('resume-editor/{resume}/language/{language}', [ResumeEditorController::class, 'updateLanguage'])->name('resume-editor.language.update');
    Route::delete('resume-editor/{resume}/language/{language}', [ResumeEditorController::class, 'destroyLanguage'])->name('resume-editor.language.destroy');

    // Additional Info (per resume; new resumes start with a copy of the most recent one)
    Route::post('resume-editor/{resume}/additional-info', [ResumeEditorController::class, 'updateAdditionalInfo'])->name('resume-editor.additional-info.update');

    // AI Assistant chat (builds resume data via conversation)
    Route::post('resume-editor/{resume}/assistant/message', [ResumeAssistantController::class, 'message'])->name('resume-editor.assistant.message');

    // Include/exclude & reorder pool items within a resume
    Route::post('resume-editor/{resume}/items/{type}/{item}/toggle', [ResumeEditorController::class, 'toggleItem'])->name('resume-editor.items.toggle');
    Route::post('resume-editor/{resume}/items/{type}/reorder', [ResumeEditorController::class, 'reorderItems'])->name('resume-editor.items.reorder');

    Route::get('interview-preparation', InterviewPreparationController::class)->name('interview-preparation');
    Route::get('salary', function () {
        return Inertia::render('Salary', [
            'resumes' => auth()->user()->resumes()->orderByDesc('updated_at')->get(['id', 'title']),
            'jobs' => WorkJob::query()
                ->whereNotNull('salary_start')
                ->whereNotNull('salary_end')
                ->orderByDesc('updated_at')
                ->get(['id', 'title', 'company', 'salary_start', 'salary_end']),
        ]);
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

