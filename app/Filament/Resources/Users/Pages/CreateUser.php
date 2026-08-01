<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\Schemas\UserForm;
use App\Filament\Resources\Users\UserResource;
use App\Services\StalwartMailService;
use App\Support\RoleHierarchy;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Validation\ValidationException;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected static bool $canCreateAnother = false;

    private ?string $plainPassword = null;

    public function getTitle(): string
    {
        return 'Tạo người dùng';
    }

    public function getBreadcrumb(): string
    {
        return 'Tạo';
    }

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()
            ->label('Tạo người dùng')
            ->icon(Heroicon::OutlinedCheck);
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
