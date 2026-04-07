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
use Illuminate\Database\Seeder;

/**
 * Idempotent demo data: two coach spaces, multiple programs, courses, modules, lessons, products, sample enrollments.
 */
class PocketCoachDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedSuperAdmin();
        $this->seedAdeolaTenant();
        $this->seedNorthstarTenant();
    }

    private function seedSuperAdmin(): void
    {
        $u = User::query()->firstOrCreate(
            ['email' => 'super@pocketcoach.test'],
            [
                'name' => 'Platform Super',
                'password' => 'password',
                'email_verified_at' => now(),
                'is_super_admin' => true,
            ],
        );
        $u->forceFill(['is_super_admin' => true])->save();
    }

    private function seedAdeolaTenant(): void
    {
        $coach = $this->user('coach@pocketcoach.test', 'Coach Adeola');

        $tenant = Tenant::query()->firstOrCreate(
            ['slug' => 'adeola'],
            [
                'name' => 'Adeola Coaching',
                'status' => Tenant::STATUS_ACTIVE,
                'branding' => ['primary' => '#0d9488', 'accent' => '#f59e0b'],
            ],
        );

        $this->membership($tenant, $coach, 'owner');

        $programMindset = $this->program($tenant, 'mindset-sprint', 'Mindset Sprint', 'A two-week reset for clarity and follow-through.', 1);

        $courseWeek1 = $this->course($tenant, $programMindset, 'week-1-foundations', 'Week 1 — Foundations', 'Orient, breathe, and set your first commitments.', 1);

        $modGs = $this->module($tenant, $courseWeek1, 'getting-started', 'Getting started', 1);
        $this->lesson($tenant, $modGs, 'welcome', 'Welcome', 1, <<<'MD'
Welcome to your coaching journey.

In this sprint you will build small daily practices that compound. Start by noticing **what matters most** this week—one theme is enough.
MD);
        $this->lesson($tenant, $modGs, 'how-this-works', 'How this sprint works', 2, <<<'MD'
Each lesson is short on purpose. Your job is to **apply**, not perfect.

- Complete lessons in order when you can; resume anytime from **Continue**.
- Use **Feedback & notes** to capture reflections—they are yours to revisit.
MD);

        $modDaily = $this->module($tenant, $courseWeek1, 'daily-practices', 'Daily practices', 2);
        $this->lesson($tenant, $modDaily, 'morning-intention', 'Morning intention (5 minutes)', 1, <<<'MD'
Before screens, write **one sentence**: *Today I will show up for…*

Keep the bar low. Consistency beats intensity.
MD);
        $this->lesson($tenant, $modDaily, 'evening-review', 'Evening review', 2, <<<'MD'
Three prompts:

1. What went well?
2. What did I learn?
3. What is one tiny tweak for tomorrow?

End with a breath: in for 4, hold 4, out for 6—twice.
MD);

        $courseWeek2 = $this->course($tenant, $programMindset, 'week-2-momentum', 'Week 2 — Momentum', 'Deepen focus, energy, and accountability.', 2);

        $modDeep = $this->module($tenant, $courseWeek2, 'deep-work', 'Deep work & energy', 1);
        $this->lesson($tenant, $modDeep, 'focus-blocks', 'Focus blocks', 1, <<<'MD'
Schedule **two 25-minute blocks** this week for your priority. Phone away, door closed or signal on.

After each block, note: *Did I start on time? What distracted me?*
MD);
        $this->lesson($tenant, $modDeep, 'energy-management', 'Energy management', 2, <<<'MD'
Map your week: when do you naturally have **creative** vs **admin** energy?

Match task types to those windows. Protect one high-energy slot for your hardest work.
MD);

        $modAcct = $this->module($tenant, $courseWeek2, 'accountability', 'Accountability', 2);
        $this->lesson($tenant, $modAcct, 'check-in-ritual', 'Check-in ritual', 1, <<<'MD'
Choose one **accountability partner** or journal slot mid-week.

Share: commitment, obstacle, next step. Keep it to ten minutes.

This lesson pairs well with **evening review** from Week 1.
MD);

        $this->productFree($tenant, 'community', 'Community access', $courseWeek1);
        $this->productPaid($tenant, 'full-course', 'Full Week 1 — paid unlock', $courseWeek1, 2_500_000);

        $programHabits = $this->program($tenant, 'habits-lab', 'Habits Lab', 'Practical loops: cues, routines, and environment design.', 2);

        $courseAtomic = $this->course($tenant, $programHabits, 'atomic-starters', 'Atomic starters', 'Build tiny habits that stick.', 1);
        $modCue = $this->module($tenant, $courseAtomic, 'cue-and-craving', 'Cue & craving', 1);
        $this->lesson($tenant, $modCue, 'find-your-cue', 'Find your cue', 1, <<<'MD'
List three habits you want. For each, ask: **What happens immediately before?**

That moment is your cue. Make it visible (sticky note, app reminder, stacked habit).
MD);
        $this->lesson($tenant, $modCue, 'reward-without-guilt', 'Reward without guilt', 2, <<<'MD'
Pair the habit with a **small reward** in the first two weeks—music, tea, two minutes of quiet.

Rewards are training wheels, not bribery. Adjust as the loop becomes automatic.
MD);

        $modStack = $this->module($tenant, $courseAtomic, 'stacking-and-space', 'Stacking & space', 2);
        $this->lesson($tenant, $modStack, 'habit-stacking', 'Habit stacking', 1, <<<'MD'
After **[existing habit]**, I will **[new habit]** for **[time or count]**.

Example: *After I pour coffee, I will journal one line.*

Stack only **one** new habit at a time.
MD);
        $this->lesson($tenant, $modStack, 'environment-design', 'Environment design', 2, <<<'MD'
Friction is a feature. Remove friction for good habits; add friction for habits you are phasing out.

Rearrange one surface tonight (desk, drawer, phone home screen) to **vote for** your future self.
MD);

        $courseReview = $this->course($tenant, $programHabits, 'review-reset', 'Review & reset', 'Weekly rhythm for honest resets.', 2);
        $modWeekly = $this->module($tenant, $courseReview, 'weekly-loop', 'Weekly loop', 1);
        $this->lesson($tenant, $modWeekly, 'reflect-four-questions', 'Reflect — four questions', 1, <<<'MD'
1. Wins?
2. Misses (no shame—data only)?
3. One constraint to remove?
4. One experiment for next week?

Keep answers **short**; patterns emerge over time.
MD);
        $this->lesson($tenant, $modWeekly, 'plan-the-next-week', 'Plan the next week', 2, <<<'MD'
Choose **three outcomes** max. Block time or attach to cues.

Say no once this week to protect those outcomes.
MD);

        $this->productFree($tenant, 'habits-starters-free', 'Habits Lab — starters (free)', $courseAtomic);

        $learner = $this->user('learner@pocketcoach.test', 'Learner Sam');
        $this->membership($tenant, $learner, 'learner');

        $this->enrollment($tenant, $learner, $programMindset, $courseWeek1);
        $this->enrollment($tenant, $learner, $programHabits, $courseAtomic);
    }

    private function seedNorthstarTenant(): void
    {
        $coach = $this->user('kwesi@pocketcoach.test', 'Coach Kwesi');

        $tenant = Tenant::query()->firstOrCreate(
            ['slug' => 'northstar'],
            [
                'name' => 'Northstar Leadership Studio',
                'status' => Tenant::STATUS_ACTIVE,
                'branding' => ['primary' => '#1d4ed8', 'accent' => '#a855f7'],
            ],
        );

        $this->membership($tenant, $coach, 'owner');

        $program = $this->program($tenant, 'leadership-basics', 'Leadership basics', 'Self-awareness and dialogue for new leads.', 1);

        $courseSelf = $this->course($tenant, $program, 'self-awareness', 'Self-awareness for leads', 'Values, strengths, and blind spots.', 1);
        $modFound = $this->module($tenant, $courseSelf, 'foundations', 'Foundations', 1);
        $this->lesson($tenant, $modFound, 'values-audit', 'Values audit', 1, <<<'MD'
Draft **five value words** that describe how you want *others* to experience your leadership.

For each, one behaviour you will **start**, **stop**, or **continue** this month.
MD);
        $this->lesson($tenant, $modFound, 'strengths-and-overuse', 'Strengths & overuse', 2, <<<'MD'
Your superpower **overplayed** becomes a liability (e.g. decisiveness → steamrolling).

Ask two trusted peers: *When am I most helpful—and when do I get in my own way?*
MD);

        $courseTeam = $this->course($tenant, $program, 'team-dialogue', 'Team dialogue', 'Listening and navigating tension.', 2);
        $modListen = $this->module($tenant, $courseTeam, 'listening', 'Listening', 1);
        $this->lesson($tenant, $modListen, 'active-listening', 'Active listening', 1, <<<'MD'
In your next 1:1: **mirror** their last sentence once, then ask an open question.

Avoid fixing in the first five minutes—signal *I am with you*.
MD);
        $this->lesson($tenant, $modListen, 'difficult-conversations', 'Difficult conversations', 2, <<<'MD'
Use **OBS** (observation, impact, request): what you saw, why it matters, what you need next.

Assume positive intent; verify facts before the talk.
MD);

        $modCadence = $this->module($tenant, $courseTeam, 'cadence', 'Cadence', 2);
        $this->lesson($tenant, $modCadence, 'one-on-one-agenda', '1:1 agenda template', 1, <<<'MD'
Share agenda **24h** early: wins, blockers, career, feedback both ways.

End with: *What one thing would make next week easier?*
MD);

        $this->productFree($tenant, 'northstar-self-free', 'Self-awareness (community)', $courseSelf);
        $this->productPaid($tenant, 'northstar-bundle', 'Leadership bundle', $courseTeam, 3_900_000);

        $learner = User::query()->where('email', 'learner@pocketcoach.test')->first();
        if ($learner !== null) {
            $this->membership($tenant, $learner, 'learner');
            $this->enrollment($tenant, $learner, $program, $courseSelf);
        }

        $demoLearner = $this->user('amina@pocketcoach.test', 'Amina Demo');
        $this->membership($tenant, $demoLearner, 'learner');
        $this->enrollment($tenant, $demoLearner, $program, $courseSelf);
        $this->enrollment($tenant, $demoLearner, $program, $courseTeam);
    }

    private function user(string $email, string $name): User
    {
        return User::query()->firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => 'password',
                'email_verified_at' => now(),
            ],
        );
    }

    private function membership(Tenant $tenant, User $user, string $role): void
    {
        TenantMembership::query()->firstOrCreate(
            [
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
            ],
            ['role' => $role],
        );
    }

    private function program(Tenant $tenant, string $slug, string $title, string $summary, int $sort): Program
    {
        return Program::query()->firstOrCreate(
            [
                'tenant_id' => $tenant->id,
                'slug' => $slug,
            ],
            [
                'title' => $title,
                'summary' => $summary,
                'sort_order' => $sort,
                'is_published' => true,
            ],
        );
    }

    private function course(Tenant $tenant, Program $program, string $slug, string $title, ?string $summary, int $sort): Course
    {
        return Course::query()->firstOrCreate(
            [
                'program_id' => $program->id,
                'slug' => $slug,
            ],
            [
                'tenant_id' => $tenant->id,
                'title' => $title,
                'summary' => $summary,
                'sort_order' => $sort,
                'is_published' => true,
            ],
        );
    }

    private function module(Tenant $tenant, Course $course, string $slug, string $title, int $sort): Module
    {
        return Module::query()->firstOrCreate(
            [
                'course_id' => $course->id,
                'slug' => $slug,
            ],
            [
                'tenant_id' => $tenant->id,
                'title' => $title,
                'sort_order' => $sort,
                'is_published' => true,
            ],
        );
    }

    private function lesson(Tenant $tenant, Module $module, string $slug, string $title, int $sort, string $body): Lesson
    {
        return Lesson::query()->firstOrCreate(
            [
                'module_id' => $module->id,
                'slug' => $slug,
            ],
            [
                'tenant_id' => $tenant->id,
                'title' => $title,
                'lesson_type' => 'text',
                'body' => $body,
                'sort_order' => $sort,
                'is_published' => true,
            ],
        );
    }

    private function productFree(Tenant $tenant, string $slug, string $name, Course $course): Product
    {
        return Product::query()->firstOrCreate(
            [
                'tenant_id' => $tenant->id,
                'slug' => $slug,
            ],
            [
                'name' => $name,
                'type' => Product::TYPE_FREE,
                'currency' => 'NGN',
                'course_id' => $course->id,
                'is_active' => true,
            ],
        );
    }

    private function productPaid(Tenant $tenant, string $slug, string $name, Course $course, int $amountMinor): Product
    {
        return Product::query()->firstOrCreate(
            [
                'tenant_id' => $tenant->id,
                'slug' => $slug,
            ],
            [
                'name' => $name,
                'type' => Product::TYPE_ONE_TIME,
                'amount_minor' => $amountMinor,
                'currency' => 'NGN',
                'course_id' => $course->id,
                'is_active' => true,
            ],
        );
    }

    private function enrollment(Tenant $tenant, User $user, Program $program, Course $course): void
    {
        Enrollment::query()->firstOrCreate(
            [
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'course_id' => $course->id,
            ],
            [
                'program_id' => $program->id,
                'source' => 'seed',
                'status' => 'active',
            ],
        );
    }
}
