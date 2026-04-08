<?php

use App\Http\Controllers\Api\V1\Admin\CourseController as AdminCourseController;
use App\Http\Controllers\Api\V1\Admin\LessonController as AdminLessonController;
use App\Http\Controllers\Api\V1\Admin\ModuleController as AdminModuleController;
use App\Http\Controllers\Api\V1\Admin\ProgramController as AdminProgramController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\EnrollmentController;
use App\Http\Controllers\Api\V1\Learner\CatalogController;
use App\Http\Controllers\Api\V1\Learner\ContinueLearningController;
use App\Http\Controllers\Api\V1\Learner\LearnerCourseController;
use App\Http\Controllers\Api\V1\Learner\LearnerReflectionController;
use App\Http\Controllers\Api\V1\Learner\LearningSummaryController;
use App\Http\Controllers\Api\V1\Learner\LessonProgressController;
use App\Http\Controllers\Api\V1\PaystackPaymentController;
use App\Http\Controllers\Api\V1\TaskBoardWebhookController;
use App\Http\Controllers\Api\V1\TenantBrandingController;
use App\Http\Controllers\Api\V1\TenantJoinController;
use App\Http\Controllers\Api\V1\UserNotificationController;
use App\Http\Controllers\Api\Webhooks\PaystackWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/webhooks/paystack', [PaystackWebhookController::class, 'handle']);

Route::prefix('v1')->group(function (): void {
    Route::post('/integrations/qa-tasks', [TaskBoardWebhookController::class, 'store'])
        ->middleware('task_board.webhook')
        ->name('api.v1.integrations.qa-tasks');

    Route::get('/tenants/{tenant}/branding', [TenantBrandingController::class, 'show']);

    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/auth/google', [AuthController::class, 'google']);

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::get('/notifications', [UserNotificationController::class, 'index']);
        Route::get('/notifications/unread-count', [UserNotificationController::class, 'unreadCount']);

        Route::get('/tenants/{tenant}/catalog', [CatalogController::class, 'index']);
        Route::get('/tenants/{tenant}/continue', [ContinueLearningController::class, 'show']);
        Route::get('/tenants/{tenant}/learning-summary', [LearningSummaryController::class, 'index']);
        Route::post('/tenants/{tenant}/join', [TenantJoinController::class, 'store']);
        Route::get('/tenants/{tenant}/courses/{course}', [LearnerCourseController::class, 'show']);
        Route::put('/tenants/{tenant}/lessons/{lesson}/progress', [LessonProgressController::class, 'upsert']);

        Route::get('/tenants/{tenant}/reflection-prompts/latest', [LearnerReflectionController::class, 'latest']);
        Route::get('/tenants/{tenant}/reflection-prompts/{reflection_prompt}', [LearnerReflectionController::class, 'show']);
        Route::post('/tenants/{tenant}/reflection-prompts/{reflection_prompt}/view', [LearnerReflectionController::class, 'recordView']);
        Route::put('/tenants/{tenant}/reflection-prompts/{reflection_prompt}/response', [LearnerReflectionController::class, 'upsertResponse']);

        Route::post('/tenants/{tenant}/enrollments/free', [EnrollmentController::class, 'free']);
        Route::post('/tenants/{tenant}/payments/paystack/initialize', [PaystackPaymentController::class, 'initialize']);

        Route::middleware('tenant.staff')->prefix('tenants/{tenant}/admin')->group(function (): void {
            Route::apiResource('programs', AdminProgramController::class);
            Route::apiResource('courses', AdminCourseController::class);
            Route::apiResource('modules', AdminModuleController::class);
            Route::apiResource('lessons', AdminLessonController::class);
        });
    });
});
