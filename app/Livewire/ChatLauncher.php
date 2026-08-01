<?php

namespace App\Livewire;

use App\Filament\Resources\Applications\ApplicationResource;
use App\Filament\Resources\CbpApplications\CbpApplicationResource;
use App\Filament\Resources\Leads\LeadResource;
use App\Filament\Resources\LotteFinanceApplications\LotteFinanceApplicationResource;
use App\Filament\Resources\SaleProfiles\SaleProfileResource;
use App\Models\Application;
use App\Models\Lead;
use App\Models\SaleProfile;
use App\Models\User;
use App\Support\Permissions\RecordVisibility;
use App\Support\Permissions\SalesProjectAccess;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Component;
use Wirechat\Wirechat\Facades\Wirechat as WirechatFacade;
use Wirechat\Wirechat\PanelRegistry;

class ChatLauncher extends Component
{
    public string $search = '';

    public int $unreadCount = 0;

    public bool $isChatModule = false;

    public function mount(): void
    {
        app(PanelRegistry::class)->setCurrent('chats');
        $this->unreadCount = $this->currentUnreadCount();
        $this->isChatModule = request()->routeIs('filament.admin.pages.chat')
            || str_starts_with(request()->path(), 'tro-chuyen');
    }

    #[On('refresh-chats')]
    public function refreshUnreadCount(): void
    {
        $newCount = $this->currentUnreadCount();

        if ($newCount > $this->unreadCount) {
            $this->dispatch('chat-unread-updated', count: $newCount);
        }

        $this->unreadCount = $newCount;

        if ($this->normalizedSearchNeedle() !== null) {
            $this->skipRender();
        }
    }

    public function openConversation(int $userId): void
    {
        $user = auth()->user();
        abort_unless($user?->canCreateChats(), 403);

        $target = User::query()
            ->whereKeyNot($user->getKey())
            ->where(fn (Builder $query): Builder => $query
                ->whereNull('employment_status')
                ->orWhereNotIn('employment_status', $this->blockedStatuses()))
            ->findOrFail($userId);

        $conversation = $user->createConversationWith($target);
        abort_if($conversation === null, 422, 'Không thể tạo cuộc trò chuyện.');

        $this->reset('search');
        $this->dispatch('chat-conversation-opened');
        $this->dispatch('open-chat', conversation: $conversation->getKey());
    }

    public function openExistingConversation(int $conversationId): void
    {
        $user = auth()->user();
        abort_unless($user?->canCreateChats(), 403);

        $conversation = $user->conversations()->whereKey($conversationId)->firstOrFail();

        $this->dispatch('chat-conversation-opened');
        $this->dispatch('open-chat', conversation: $conversation->getKey());
    }

    public function render(): View
    {
        $needle = $this->normalizedSearchNeedle();

        return view('livewire.chat-launcher', [
            'results' => $this->searchResults($needle),
            'searchReady' => $needle !== null,
            'conversations' => $needle === null ? $this->recentConversations() : [],
        ]);
    }

    private function searchResults(?string $needle): array
    {
        if ($needle === null) {
            return [];
        }

        $pattern = '%'.addcslashes($needle, '\\%_').'%';

        return [
            ...$this->searchUsers($pattern),
            ...$this->searchLeads($pattern),
            ...$this->searchApplications($pattern),
            ...$this->searchSaleProfiles($pattern),
        ];
    }

    private function searchUsers(string $pattern): array
    {
        return User::query()
            ->whereKeyNot(auth()->id())
            ->where(fn (Builder $query): Builder => $query
                ->whereNull('employment_status')
                ->orWhereNotIn('employment_status', $this->blockedStatuses()))
            ->where(function (Builder $query) use ($pattern): void {
                foreach ([
                    'name',
                    'uid',
                    'employee_code',
                    'email',
                    'phone',
                    'department',
                    'company_name',
                    'sales_channel',
                    'position',
                    'branch_name',
                ] as $field) {
                    $query->orWhere($field, 'ilike', $pattern);
                }
            })
            ->orderBy('name')
            ->limit(6)
            ->get()
            ->map(fn (User $user): array => [
                'id' => $user->getKey(),
                'type' => 'user',
                'category' => 'Người dùng',
                'name' => $user->name ?: 'Người dùng',
                'uid' => $user->uid,
                'details' => array_values(array_filter([
                    ['label' => 'Employee', 'value' => $user->employee_code],
                    ['label' => 'Chức vụ', 'value' => $user->position],
                    ['label' => 'Phòng ban', 'value' => $user->department],
                    ['label' => 'Đối tác', 'value' => $user->company_name],
                    ['label' => 'Kênh', 'value' => $user->sales_channel],
                    ['label' => 'SĐT', 'value' => $user->phone],
                    ['label' => 'Email', 'value' => $user->email, 'wide' => true],
                ], fn (array $detail): bool => filled($detail['value']))),
                'avatar' => $user->wirechat_avatar_url,
                'initials' => $this->initials($user->name ?: 'Người dùng'),
                'url' => null,
            ])
            ->all();
    }

    private function searchLeads(string $pattern): array
    {
        return LeadResource::getEloquentQuery()
            ->with(['salesProject', 'application'])
            ->where(function (Builder $query) use ($pattern): void {
                $query
                    ->where('lead_code', 'ilike', $pattern)
                    ->orWhere('lead_name', 'ilike', $pattern)
                    ->orWhere('phone', 'ilike', $pattern)
                    ->orWhere('email', 'ilike', $pattern)
                    ->orWhere('payload->fields->identity_number', 'ilike', $pattern)
                    ->orWhereHas('salesProject', fn (Builder $project): Builder => $project
                        ->where('name', 'ilike', $pattern)
                        ->orWhere('slug', 'ilike', $pattern))
                    ->orWhereHas('application', fn (Builder $application): Builder => $application
                        ->where('application_code', 'ilike', $pattern)
                        ->orWhere('status', 'ilike', $pattern));
            })
            ->latest('updated_at')
            ->limit(6)
            ->get()
            ->map(fn (Lead $lead): array => [
                'id' => $lead->getKey(),
                'type' => 'lead',
                'category' => 'Lead',
                'name' => $lead->lead_name ?: $lead->lead_code,
                'uid' => $lead->lead_code,
                'details' => array_values(array_filter([
                    ['label' => 'Dự án', 'value' => $lead->salesProject?->name],
                    ['label' => 'Mã hồ sơ', 'value' => $lead->application?->application_code],
                    ['label' => 'SĐT', 'value' => $lead->phone],
                    ['label' => 'CCCD', 'value' => data_get($lead->payload, 'fields.identity_number')],
                    ['label' => 'Trạng thái Lead', 'value' => $lead->status],
                    ['label' => 'Trạng thái Application', 'value' => $this->applicationStatus($lead->application?->status), 'wide' => true],
                ], fn (array $detail): bool => filled($detail['value']))),
                'avatar' => null,
                'initials' => 'LD',
                'url' => LeadResource::getUrl('view', ['record' => $lead]),
            ])
            ->all();
    }

    private function searchApplications(string $pattern): array
    {
        $user = auth()->user();

        if (! $user?->can('application.view')) {
            return [];
        }

        $query = Application::query()
            ->with(['salesProject'])
            ->where(function (Builder $query) use ($pattern): void {
                $query
                    ->where('application_code', 'ilike', $pattern)
                    ->orWhere('applicant_name', 'ilike', $pattern)
                    ->orWhere('phone', 'ilike', $pattern)
                    ->orWhere('identity_number', 'ilike', $pattern)
                    ->orWhere('status', 'ilike', $pattern)
                    ->orWhereHas('salesProject', fn (Builder $project): Builder => $project
                        ->where('name', 'ilike', $pattern)
                        ->orWhere('slug', 'ilike', $pattern));
            });

        if (! $user->hasRole('Admin')) {
            $slugs = SalesProjectAccess::userProjectSlugs($user);

            if ($slugs === []) {
                return [];
            }

            $query->whereHas('salesProject', fn (Builder $project): Builder => $project->whereIn('slug', $slugs));
        }

        return RecordVisibility::applyUserScope($query, $user, 'assigned_sale_id', 'assignedSale')
            ->latest('updated_at')
            ->limit(6)
            ->get()
            ->map(fn (Application $application): array => [
                'id' => $application->getKey(),
                'type' => 'application',
                'category' => 'Dự án',
                'name' => $application->applicant_name ?: $application->application_code,
                'uid' => $application->application_code,
                'details' => array_values(array_filter([
                    ['label' => 'Dự án', 'value' => $application->salesProject?->name],
                    ['label' => 'SĐT', 'value' => $application->phone],
                    ['label' => 'CCCD', 'value' => $application->identity_number],
                    ['label' => 'Trạng thái', 'value' => $this->applicationStatus($application->status), 'wide' => true],
                ], fn (array $detail): bool => filled($detail['value']))),
                'avatar' => null,
                'initials' => 'DA',
                'url' => $this->applicationUrl($application),
            ])
            ->filter(fn (array $result): bool => filled($result['url']))
            ->values()
            ->all();
    }

    private function applicationStatus(?string $status): string
    {
        return match ($status) {
            'approved' => 'Đã phê duyệt',
            'pending_approval' => 'Chờ phê duyệt',
            'processing' => 'Đang xử lý',
            'rejected' => 'Từ chối',
            null, '' => 'Chưa chuyển Application',
            default => $status,
        };
    }

    private function applicationUrl(Application $application): ?string
    {
        $resource = match ($application->salesProject?->slug) {
            'acl-mix' => ApplicationResource::class,
            'cbp' => CbpApplicationResource::class,
            'lotte-finance' => LotteFinanceApplicationResource::class,
            default => null,
        };

        return $resource ? $resource::getUrl('view', ['record' => $application]) : null;
    }

    private function searchSaleProfiles(string $pattern): array
    {
        if (! auth()->user()?->can('profile.view')) {
            return [];
        }

        return SaleProfileResource::getEloquentQuery()
            ->where(function (Builder $query) use ($pattern): void {
                $query
                    ->where('customer_name', 'ilike', $pattern)
                    ->orWhere('phone', 'ilike', $pattern)
                    ->orWhere('email', 'ilike', $pattern)
                    ->orWhere('identity_number', 'ilike', $pattern)
                    ->orWhereHas('sourceLead', fn (Builder $lead): Builder => $lead->where('lead_code', 'ilike', $pattern));
            })
            ->latest('updated_at')
            ->limit(6)
            ->get()
            ->map(fn (SaleProfile $profile): array => [
                'id' => $profile->getKey(),
                'type' => 'profile',
                'category' => 'Hồ sơ',
                'name' => $profile->customer_name ?: 'Hồ sơ #'.$profile->getKey(),
                'uid' => $profile->sourceLead?->lead_code ?: 'HS-'.$profile->getKey(),
                'details' => array_values(array_filter([
                    ['label' => 'SĐT', 'value' => $profile->phone],
                    ['label' => 'CCCD', 'value' => $profile->identity_number],
                    ['label' => 'Sản phẩm', 'value' => $profile->product_interest],
                    ['label' => 'Trạng thái', 'value' => $profile->status, 'wide' => true],
                ], fn (array $detail): bool => filled($detail['value']))),
                'avatar' => null,
                'initials' => 'HS',
                'url' => SaleProfileResource::getUrl('view', ['record' => $profile]),
            ])
            ->all();
    }

    private function recentConversations(): array
    {
        $user = auth()->user();

        if (! $user) {
            return [];
        }

        $conversationClass = WirechatFacade::conversationModelClass();
        $conversationTable = (new $conversationClass)->getTable();

        return $user->conversations()
            ->with(['lastMessage', 'participants.participantable', 'group.cover'])
            ->orderByDesc($conversationTable.'.updated_at')
            ->limit(15)
            ->get()
            ->map(function ($conversation) use ($user): array {
                $ownParticipant = $conversation->participants->first(fn ($participant): bool => (string) $participant->participantable_id === (string) $user->getKey()
                    && $participant->participantable_type === $user->getMorphClass());
                $peer = $conversation->participants
                    ->first(fn ($participant): bool => $ownParticipant?->getKey() !== $participant->getKey())
                    ?->participantable;
                $lastMessage = $conversation->lastMessage;
                $isGroup = $conversation->isGroup();
                $title = $isGroup
                    ? ($conversation->group?->name ?: 'Nhóm trò chuyện')
                    : ($peer?->wirechat_name ?: $peer?->name ?: 'Người dùng');
                $avatar = $isGroup
                    ? $conversation->group?->cover_url
                    : $peer?->wirechat_avatar_url;
                $isUnread = $lastMessage
                    && $ownParticipant
                    && (string) $lastMessage->participant_id !== (string) $ownParticipant->getKey()
                    && (! $ownParticipant->conversation_read_at
                        || $lastMessage->created_at->gt($ownParticipant->conversation_read_at));

                return [
                    'id' => $conversation->getKey(),
                    'title' => $title,
                    'preview' => filled($lastMessage?->body)
                        ? Str::limit(strip_tags((string) $lastMessage->body), 72)
                        : 'Bắt đầu cuộc trò chuyện',
                    'time' => $lastMessage?->created_at?->locale('vi')->diffForHumans(short: true),
                    'avatar' => $avatar,
                    'initials' => $this->initials($title),
                    'unread' => (bool) $isUnread,
                ];
            })
            ->all();
    }

    private function normalizedSearchNeedle(): ?string
    {
        $needle = mb_substr(trim($this->search), 0, 80);

        if ($needle === '') {
            return null;
        }

        $normalized = preg_replace(
            '/^(uid|employee(?:\s*code)?|mã\s*nhân\s*viên|sđt|sdt|số\s*điện\s*thoại|email)\s*[:#-]?\s*/iu',
            '',
            $needle,
        );

        if ($normalized !== null && $normalized !== $needle) {
            $needle = trim($normalized);
        }

        return mb_strlen($needle) >= 2 ? $needle : null;
    }

    private function currentUnreadCount(): int
    {
        return (int) (auth()->user()?->getUnreadCount() ?? 0);
    }

    private function blockedStatuses(): array
    {
        return ['inactive', User::STATUS_DEACTIVE, 'resigned', User::STATUS_DELETED];
    }

    private function initials(string $name): string
    {
        $parts = preg_split('/\s+/u', trim($name), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if ($parts === []) {
            return 'ND';
        }

        $first = mb_substr($parts[0], 0, 1);
        $last = count($parts) > 1 ? mb_substr($parts[array_key_last($parts)], 0, 1) : '';

        return mb_strtoupper($first.$last);
    }
}
