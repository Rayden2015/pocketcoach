<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Web\Coach\CourseController as CoachCourseController;
use App\Http\Controllers\Web\Coach\LessonController as CoachLessonController;
use App\Http\Controllers\Web\Coach\ModuleController as CoachModuleController;
use App\Http\Controllers\Web\Coach\ProgramController as CoachProgramController;
use App\Http\Controllers\Web\LearnCatalogController;
use App\Http\Controllers\Web\LearnContinueController;
use App\Http\Controllers\Web\LearnCourseController;
use App\Http\Controllers\Web\LearnLessonController;
use App\Http\Controllers\Web\LearnLessonProgressController;
use App\Http\Controllers\Web\PublicCatalogController;
use App\Http\Controllers\Web\SpaceHubController;
use App\Models\Tenant;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');

Route::get('/spaces/{tenant:slug}/catalog', [PublicCatalogController::class, 'show'])->name('public.catalog');

Route::middleware('guest')->group(function (): void {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);
    Route::get('register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('register', [RegisteredUserController::class, 'store']);

    Route::get('auth/google', [GoogleAuthController::class, 'redirect'])->name('auth.google.redirect');
    Route::get('auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');
});

Route::middleware('auth')->group(function (): void {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('/dashboard', [SpaceHubController::class, 'index'])->name('dashboard');

    Route::prefix('learn/{tenant:slug}')->name('learn.')->group(function (): void {
        Route::get('/', fn (Tenant $tenant) => redirect()->route('learn.catalog', $tenant))->name('home');
        Route::get('/catalog', [LearnCatalogController::class, 'index'])->name('catalog');
        Route::get('/continue', [LearnContinueController::class, 'show'])->name('continue');
        Route::get('/courses/{course}', [LearnCourseController::class, 'show'])->name('course');
        Route::get('/lessons/{lesson}', [LearnLessonController::class, 'show'])->name('lesson');
        Route::post('/lessons/{lesson}/progress', [LearnLessonProgressController::class, 'update'])->name('lesson.progress');
    });

    Route::middleware('tenant.staff')->prefix('coach/{tenant:slug}')->name('coach.')->group(function (): void {
        Route::get('/', fn (Tenant $tenant) => redirect()->route('coach.programs.index', $tenant))->name('home');

        Route::resource('programs', CoachProgramController::class)->except(['show']);

        Route::get('courses', [CoachCourseController::class, 'index'])->name('courses.index');
        Route::get('courses/create', [CoachCourseController::class, 'create'])->name('courses.create');
        Route::post('courses', [CoachCourseController::class, 'store'])->name('courses.store');
        Route::get('courses/{course}/edit', [CoachCourseController::class, 'edit'])->name('courses.edit');
        Route::put('courses/{course}', [CoachCourseController::class, 'update'])->name('courses.update');
        Route::delete('courses/{course}', [CoachCourseController::class, 'destroy'])->name('courses.destroy');

        Route::get('modules', [CoachModuleController::class, 'index'])->name('modules.index');
        Route::get('modules/create', [CoachModuleController::class, 'create'])->name('modules.create');
        Route::post('modules', [CoachModuleController::class, 'store'])->name('modules.store');
        Route::get('modules/{module}/edit', [CoachModuleController::class, 'edit'])->name('modules.edit');
        Route::put('modules/{module}', [CoachModuleController::class, 'update'])->name('modules.update');
        Route::delete('modules/{module}', [CoachModuleController::class, 'destroy'])->name('modules.destroy');

        Route::get('lessons', [CoachLessonController::class, 'index'])->name('lessons.index');
        Route::get('lessons/create', [CoachLessonController::class, 'create'])->name('lessons.create');
        Route::post('lessons', [CoachLessonController::class, 'store'])->name('lessons.store');
        Route::get('lessons/{lesson}/edit', [CoachLessonController::class, 'edit'])->name('lessons.edit');
        Route::put('lessons/{lesson}', [CoachLessonController::class, 'update'])->name('lessons.update');
        Route::delete('lessons/{lesson}', [CoachLessonController::class, 'destroy'])->name('lessons.destroy');
    });
});
