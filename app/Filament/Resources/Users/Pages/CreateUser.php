<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Filament\Resources\Users\Schemas\UserForm;
use App\Services\StalwartMailService;
use App\Support\RoleHierarchy;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Validation\ValidationException;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;


    private ?string $plainPassword = null;
    public function getTitle(): string
    {
        return 'Tạo người dùng';
    }

    public function getBreadcrumb(): string
    {
        return 'Tạo';
    }

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                Action::make('createUser')
                    ->icon(Heroicon::OutlinedCheck)
                    ->label('Tạo người dùng')
                    ->action(fn () => $this->create()),
                Action::make('cancel')
                    ->icon(Heroicon::OutlinedXMark)
                    ->label('Hủy')
                    ->color('gray')
                    ->url(UserResource::getUrl('index')),
            ])
                ->button()
                ->label('Hành động')
                ->icon(Heroicon::EllipsisHorizontal),
        ];
    }

    protected function getFormActions(): array
    {
        return [];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->plainPassword = filled($data['password'] ?? null) ? (string) $data['password'] : null;
        $data = UserForm::normalizeDateFields($data);
        $role = $this->form->getRawState()['roles'] ?? null;
        $data = UserForm::normalizeTeamAssignment($data, $role);

        if (! RoleHierarchy::canAssignRole(auth()->user(), $role)) {
            throw ValidationException::withMessages([
                'roles' => 'Bạn không được phép tạo người dùng với vai trò này.',
            ]);
        }

        $data = RoleHierarchy::normalizeManagerFields($data, auth()->user(), $role);
        RoleHierarchy::validateManagerFields($data, auth()->user(), $role);

        return UserResource::normalizeSalesCodes($data);
    }

    protected function afterCreate(): void
    {
        if (filled($this->plainPassword)) {
            app(StalwartMailService::class)
                ->scheduleCredentialSync($this->getRecord(), $this->plainPassword);
        }
    }
}
