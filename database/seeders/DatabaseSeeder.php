<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\Product;
use App\Models\Program;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $coach = User::factory()->create([
            'name' => 'Coach Adeola',
            'email' => 'coach@pocketcoach.test',
        ]);

        $tenant = Tenant::query()->create([
            'name' => 'Adeola Coaching',
            'slug' => 'adeola',
            'branding' => ['primary' => '#0d9488'],
        ]);

        TenantMembership::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $coach->id,
            'role' => 'owner',
        ]);

        $program = Program::query()->create([
            'tenant_id' => $tenant->id,
            'title' => 'Mindset Sprint',
            'slug' => 'mindset-sprint',
            'summary' => 'Short intro program.',
            'sort_order' => 1,
            'is_published' => true,
        ]);

        $course = Course::query()->create([
            'tenant_id' => $tenant->id,
            'program_id' => $program->id,
            'title' => 'Week 1 — Foundations',
            'slug' => 'week-1-foundations',
            'sort_order' => 1,
            'is_published' => true,
        ]);

        $module = Module::query()->create([
            'tenant_id' => $tenant->id,
            'course_id' => $course->id,
            'title' => 'Getting started',
            'slug' => 'getting-started',
            'sort_order' => 1,
            'is_published' => true,
        ]);

        Lesson::query()->create([
            'tenant_id' => $tenant->id,
            'module_id' => $module->id,
            'title' => 'Welcome',
            'slug' => 'welcome',
            'lesson_type' => 'text',
            'body' => 'Welcome to your coaching journey.',
            'sort_order' => 1,
            'is_published' => true,
        ]);

        Product::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Community access',
            'slug' => 'community',
            'type' => Product::TYPE_FREE,
            'currency' => 'NGN',
            'course_id' => $course->id,
            'is_active' => true,
        ]);

        Product::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Full course',
            'slug' => 'full-course',
            'type' => Product::TYPE_ONE_TIME,
            'amount_minor' => 2_500_000,
            'currency' => 'NGN',
            'course_id' => $course->id,
            'is_active' => true,
        ]);

        $learner = User::factory()->create([
            'name' => 'Learner Sam',
            'email' => 'learner@pocketcoach.test',
        ]);

        TenantMembership::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $learner->id,
            'role' => 'learner',
        ]);

        Enrollment::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $learner->id,
            'program_id' => $program->id,
            'course_id' => $course->id,
            'source' => 'seed',
            'status' => 'active',
        ]);
    }
}
