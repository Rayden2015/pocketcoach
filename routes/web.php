<?php

use App\Http\Controllers\Api\V1\UserNotificationController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\SpaceGateController;
use App\Http\Controllers\Auth\TenantRegisteredUserController;
use App\Http\Controllers\Auth\TenantSessionController;
use App\Http\Controllers\Platform\TenantAdminController;
use App\Http\Controllers\Web\Coach\CourseController as CoachCourseController;
use App\Http\Controllers\Web\Coach\LearnerSubmissionController as CoachLearnerSubmissionController;
use App\Http\Controllers\Web\Coach\LessonController as CoachLessonController;
use App\Http\Controllers\Web\Coach\ModuleController as CoachModuleController;
use App\Http\Controllers\Web\Coach\ProgramController as CoachProgramController;
use App\Http\Controllers\Web\Coach\ReflectionPromptController as CoachReflectionPromptController;
use App\Http\Controllers\Web\CourseSearchController;
use App\Http\Controllers\Web\CreateSpaceController;
use App\Http\Controllers\Web\ExtraSpaceController;
use App\Http\Controllers\Web\HomeController;
use App\Http\Controllers\Web\LearnCatalogController;
use App\Http\Controllers\Web\LearnContinueController;
use App\Http\Controllers\Web\LearnCourseController;
use App\Http\Controllers\Web\LearnEnrollmentController;
use App\Http\Controllers\Web\LearnLessonController;
use App\Http\Controllers\Web\LearnLessonProgressController;
use App\Http\Controllers\Web\LearnReflectionController;
use App\Http\Controllers\Web\LearnSpaceJoinController;
use App\Http\Controllers\Web\MyLearningController;
use App\Http\Controllers\Web\ProfileController;
use App\Http\Controllers\Web\PublicCatalogController;
use App\Http\Controllers\Web\PublicCatalogTrackController;
use App\Http\Controllers\Web\SpaceHubController;
use App\Http\Controllers\Web\SubmissionConversationController;
use App\Models\Tenant;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store']);
    Route::get('/join-help', [SpaceGateController::class, 'show'])->name('join-help');
    Route::get('/create-space', [CreateSpaceController::class, 'create'])->name('create-space');
    Route::post('/create-space', [CreateSpaceController::class, 'store']);

    Route::get('auth/google', [GoogleAuthController::class, 'redirect'])->name('auth.google.redirect');
    Route::get('auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');
});

Route::middleware('auth')->group(function (): void {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('/my-learning', [MyLearningController::class, 'index'])->name('my-learning');

    Route::get('/search', [CourseSearchController::class, 'index'])->name('search.courses');

    Route::get('/my-coaching', [SpaceHubController::class, 'index'])->name('my-coaching');
    Route::permanentRedirect('/dashboard', '/my-coaching');

    Route::get('/spaces/new', [ExtraSpaceController::class, 'create'])->name('spaces.create');
    Route::post('/spaces', [ExtraSpaceController::class, 'store'])->name('spaces.store');

    Route::get('/profile', [ProfileController::class, 'show'])->name('profile');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::get('/notifications', [UserNotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/unread-count', [UserNotificationController::class, 'unreadCount'])->name('notifications.unread-count');
    Route::post('/notifications/read-all', [UserNotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
    Route::patch('/notifications/{id}', [UserNotificationController::class, 'markAsRead'])->name('notifications.read');

    Route::middleware('super_admin')->prefix('platform')->name('platform.')->group(function (): void {
        Route::resource('tenants', TenantAdminController::class)->only(['index', 'create', 'store', 'edit', 'update'])
            ->parameters(['tenants' => 'adminTenant']);
    });
});

/*
| Path-based tenancy: https://app.test/{slug}/register, /{slug}/catalog, /{slug}/learn/..., /{slug}/coach/...
| Reserved slugs: config/tenancy.reserved_slugs
*/
Route::prefix('{tenant:slug}')->group(function (): void {
    Route::get('/', fn (Tenant $tenant) => redirect()->route('public.catalog', $tenant))->name('space.welcome');

    Route::get('/catalog', [PublicCatalogController::class, 'show'])->name('public.catalog');

    Route::middleware('guest')->group(function (): void {
        Route::get('/register', [TenantRegisteredUserController::class, 'create'])->name('space.register');
        Route::post('/register', [TenantRegisteredUserController::class, 'store']);
        Route::get('/login', [TenantSessionController::class, 'create'])->name('space.login');
        Route::post('/login', [TenantSessionController::class, 'store']);
    });

    Route::middleware('auth')->group(function (): void {
        Route::post('/join', [LearnSpaceJoinController::class, 'store'])->name('space.join');
        Route::post('/catalog/track', [PublicCatalogTrackController::class, 'store'])->name('public.catalog.track');

        Route::prefix('learn')->name('learn.')->group(function (): void {
            Route::get('/', fn (Tenant $tenant) => redirect()->route('learn.catalog', $tenant))->name('home');
            Route::get('/catalog', [LearnCatalogController::class, 'index'])->name('catalog');
            Route::get('/continue', [LearnContinueController::class, 'show'])->name('continue');
            Route::post('/courses/{course}/enroll', [LearnEnrollmentController::class, 'store'])->name('course.enroll');
            Route::get('/courses/{course}', [LearnCourseController::class, 'show'])->name('course');
            Route::get('/lessons/{lesson}', [LearnLessonController::class, 'show'])->name('lesson');
            Route::post('/lessons/{lesson}/progress', [LearnLessonProgressController::class, 'update'])->name('lesson.progress');
            Route::get('/reflections/{reflection_prompt}', [LearnReflectionController::class, 'show'])->name('reflections.show');
            Route::post('/reflections/{reflection_prompt}/response', [LearnReflectionController::class, 'updateResponse'])->name('reflections.response');
        });

        Route::get('/submission-conversations/reflection/{reflectionResponse}', [SubmissionConversationController::class, 'showReflection'])
            ->name('submission-conversations.reflection.show');
        Route::post('/submission-conversations/reflection/{reflectionResponse}', [SubmissionConversationController::class, 'storeReflection'])
            ->name('submission-conversations.reflection.message');
        Route::get('/submission-conversations/lesson/{lessonProgress}', [SubmissionConversationController::class, 'showLesson'])
            ->name('submission-conversations.lesson.show');
        Route::post('/submission-conversations/lesson/{lessonProgress}', [SubmissionConversationController::class, 'storeLesson'])
            ->name('submission-conversations.lesson.message');

        Route::middleware('tenant.staff')->prefix('coach')->name('coach.')->group(function (): void {
            Route::get('/', fn (Tenant $tenant) => redirect()->route('coach.programs.index', $tenant))->name('home');

            Route::resource('programs', CoachProgramController::class)->except(['show']);

            Route::get('standalone-courses', [CoachCourseController::class, 'standaloneIndex'])->name('courses.standalone.index');
            Route::get('standalone-courses/create', [CoachCourseController::class, 'standaloneCreate'])->name('courses.standalone.create');

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

            Route::get('learner-submissions', [CoachLearnerSubmissionController::class, 'index'])
                ->name('learner-submissions.index');
            Route::get('reflections/submissions', function (Tenant $tenant) {
                return redirect()->route('coach.learner-submissions.index', ['tenant' => $tenant, 'tab' => 'reflections']);
            })->name('reflections.submissions.index');
            Route::resource('reflections', CoachReflectionPromptController::class)
                ->parameters(['reflection' => 'reflection_prompt']);
        });
    });
});
