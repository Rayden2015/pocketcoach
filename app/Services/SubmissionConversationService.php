<?php

namespace App\Services;

use App\Enums\TenantRole;
use App\Models\LessonProgress;
use App\Models\ReflectionResponse;
use App\Models\SubmissionConversationMessage;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use App\Notifications\SubmissionConversationMessageNotification;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class SubmissionConversationService
{
    public function tenantForSubject(ReflectionResponse|LessonProgress $subject): Tenant
    {
        if ($subject instanceof LessonProgress) {
            return Tenant::query()->findOrFail($subject->tenant_id);
        }

        $subject->loadMissing('reflectionPrompt');

        return Tenant::query()->findOrFail($subject->reflectionPrompt->tenant_id);
    }

    public function canView(User $user, Tenant $tenant, ReflectionResponse|LessonProgress $subject): bool
    {
        if ($subject instanceof LessonProgress && $subject->tenant_id !== $tenant->id) {
            return false;
        }
        if ($subject instanceof ReflectionResponse) {
            $subject->loadMissing('reflectionPrompt');
            if ($subject->reflectionPrompt->tenant_id !== $tenant->id) {
                return false;
            }
        }

        return $this->isStaff($user, $tenant) || $this->ownsSubmission($user, $subject);
    }

    public function canPost(User $user, Tenant $tenant, ReflectionResponse|LessonProgress $subject): bool
    {
        return $this->canView($user, $tenant, $subject);
    }

    public function isStaff(User $user, Tenant $tenant): bool
    {
        $m = TenantMembership::query()
            ->where('tenant_id', $tenant->id)
            ->where('user_id', $user->id)
            ->first();

        return $m !== null && in_array($m->role, TenantRole::staffValues(), true);
    }

    public function ownsSubmission(User $user, ReflectionResponse|LessonProgress $subject): bool
    {
        return (int) $subject->user_id === (int) $user->id;
    }

    /**
     * @return Collection<int, SubmissionConversationMessage>
     */
    public function messagesFor(ReflectionResponse|LessonProgress $subject): Collection
    {
        return $subject->conversationMessages()
            ->with(['user:id,name,email'])
            ->orderBy('created_at')
            ->get();
    }

    public function validateParent(ReflectionResponse|LessonProgress $subject, ?int $parentId): ?SubmissionConversationMessage
    {
        if ($parentId === null) {
            return null;
        }

        if ($subject instanceof ReflectionResponse) {
            $subject->loadMissing('reflectionPrompt');
            $tenantId = $subject->reflectionPrompt->tenant_id;
        } else {
            $tenantId = $subject->tenant_id;
        }

        return SubmissionConversationMessage::query()
            ->whereKey($parentId)
            ->where('tenant_id', $tenantId)
            ->where('subject_type', $subject->getMorphClass())
            ->where('subject_id', $subject->getKey())
            ->first();
    }

    public function createMessage(
        Tenant $tenant,
        ReflectionResponse|LessonProgress $subject,
        User $author,
        string $body,
        ?int $parentId,
    ): SubmissionConversationMessage {
        $parent = $this->validateParent($subject, $parentId);
        if ($parentId !== null && $parent === null) {
            throw ValidationException::withMessages(['parent_id' => 'Invalid reply target.']);
        }

        if ($subject instanceof ReflectionResponse) {
            $subject->loadMissing('reflectionPrompt');
            $tenantId = $subject->reflectionPrompt->tenant_id;
        } else {
            $tenantId = $subject->tenant_id;
        }

        $message = SubmissionConversationMessage::query()->create([
            'tenant_id' => $tenantId,
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->getKey(),
            'user_id' => $author->id,
            'parent_id' => $parent?->id,
            'body' => $body,
        ]);

        $message->load('user:id,name,email');

        $this->notifyParticipants($tenant, $message, $subject, $author);

        return $message;
    }

    public function notifyParticipants(
        Tenant $tenant,
        SubmissionConversationMessage $message,
        ReflectionResponse|LessonProgress $subject,
        User $author,
    ): void {
        $message->loadMissing('user:id,name');

        if ($this->isStaff($author, $tenant)) {
            $owner = User::query()->find($subject->user_id);
            if ($owner !== null && (int) $owner->id !== (int) $author->id) {
                $owner->notify(new SubmissionConversationMessageNotification($tenant, $message, $subject));
            }

            return;
        }

        $staffIds = TenantMembership::query()
            ->where('tenant_id', $tenant->id)
            ->whereIn('role', TenantRole::staffValues())
            ->pluck('user_id');

        foreach ($staffIds as $staffUserId) {
            if ((int) $staffUserId === (int) $author->id) {
                continue;
            }
            User::query()->find($staffUserId)?->notify(
                new SubmissionConversationMessageNotification($tenant, $message, $subject)
            );
        }
    }
}
