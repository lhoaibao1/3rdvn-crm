<?php

namespace App\Services;

use App\Models\UiSetting;
use App\Models\User;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class StalwartMailService
{
    private const USING = [
        'urn:ietf:params:jmap:core',
        'urn:stalwart:jmap',
    ];

    private const MAIL_USING = [
        'urn:ietf:params:jmap:core',
        'urn:ietf:params:jmap:mail',
        'urn:ietf:params:jmap:submission',
        'urn:stalwart:jmap',
    ];

    public function scheduleCredentialSync(User $user, string $password): void
    {
        if ($password === '' || ! $user->exists) {
            return;
        }

        $userId = $user->getKey();

        app()->terminating(function () use ($userId, $password): void {
            $freshUser = User::query()->find($userId);

            if (! $freshUser instanceof User) {
                return;
            }

            app(self::class)->syncCredentials($freshUser, $password);
        });
    }

    public function scheduleProfileSync(User $user): void
    {
        if (! $user->exists || blank($user->mail_account_id)) {
            return;
        }

        $userId = $user->getKey();

        app()->terminating(function () use ($userId): void {
            $freshUser = User::query()->find($userId);

            if ($freshUser instanceof User) {
                app(self::class)->syncProfile($freshUser);
            }
        });
    }

    public function recentMessages(User $user, string $mailboxRole, int $limit = 30): array
    {
        if (blank($user->mail_account_id)) {
            return [];
        }

        $mailboxes = $this->request('Mailbox/get', [
            'accountId' => $user->mail_account_id,
            'properties' => ['id', 'role'],
        ], self::MAIL_USING);

        $mailboxId = collect($mailboxes['list'] ?? [])
            ->firstWhere('role', $mailboxRole)['id'] ?? null;

        if (blank($mailboxId)) {
            return [];
        }

        $query = $this->request('Email/query', [
            'accountId' => $user->mail_account_id,
            'filter' => ['inMailbox' => $mailboxId],
            'sort' => [['property' => 'receivedAt', 'isAscending' => false]],
            'limit' => max(1, min($limit, 100)),
        ], self::MAIL_USING);

        $ids = $query['ids'] ?? [];

        if ($ids === []) {
            return [];
        }

        $emails = $this->request('Email/get', [
            'accountId' => $user->mail_account_id,
            'ids' => $ids,
            'properties' => [
                'id', 'subject', 'from', 'to', 'cc', 'receivedAt', 'sentAt',
                'preview', 'keywords',
            ],
        ], self::MAIL_USING);

        return collect($emails['list'] ?? [])
            ->sortByDesc(fn (array $email): string => (string) ($email['receivedAt'] ?? $email['sentAt'] ?? ''))
            ->values()
            ->all();
    }

    public function recentSubmissions(User $user, int $limit = 30): array
    {
        if (blank($user->mail_account_id)) {
            return [];
        }

        $query = $this->request('EmailSubmission/query', [
            'accountId' => $user->mail_account_id,
            'limit' => 100,
        ], self::MAIL_USING);
        $submissionIds = $query['ids'] ?? [];

        if ($submissionIds === []) {
            return [];
        }

        $submissions = $this->request('EmailSubmission/get', [
            'accountId' => $user->mail_account_id,
            'ids' => $submissionIds,
            'properties' => [
                'id', 'emailId', 'threadId', 'sendAt', 'undoStatus',
                'deliveryStatus', 'dsnBlobIds',
            ],
        ], self::MAIL_USING);
        $submissionList = $submissions['list'] ?? [];
        $emailIds = collect($submissionList)
            ->pluck('emailId')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($emailIds === []) {
            return [];
        }

        $emails = $this->request('Email/get', [
            'accountId' => $user->mail_account_id,
            'ids' => $emailIds,
            'properties' => [
                'id', 'subject', 'from', 'to', 'cc', 'receivedAt', 'sentAt',
                'preview', 'keywords',
            ],
        ], self::MAIL_USING);
        $emailsById = collect($emails['list'] ?? [])->keyBy('id');

        return collect($submissionList)
            ->map(function (array $submission) use ($emailsById): array {
                $email = $emailsById->get($submission['emailId'] ?? '') ?? [];

                return array_merge($email, $submission);
            })
            ->sortByDesc(fn (array $submission): string => (string) ($submission['sendAt'] ?? ''))
            ->take(max(1, min($limit, 100)))
            ->values()
            ->all();
    }

    public function syncProfile(User $user): bool
    {
        if (blank($user->mail_account_id)) {
            return false;
        }

        try {
            $result = $this->request('x:Account/set', [
                'accountId' => $this->managementAccountId(),
                'update' => [
                    $user->mail_account_id => [
                        'description' => $this->profileDescription($user),
                        'locale' => 'vi_VN',
                        'timeZone' => 'Asia/Ho_Chi_Minh',
                    ],
                ],
            ]);

            return array_key_exists($user->mail_account_id, $result['updated'] ?? [])
                && $this->syncSignature($user);
        } catch (Throwable $exception) {
            report($exception);

            return false;
        }
    }

    public function syncSignature(User $user): bool
    {
        if (blank($user->mail_account_id) || blank($user->mail_address)) {
            return false;
        }

        try {
            $identities = $this->request('Identity/get', [
                'accountId' => $user->mail_account_id,
            ], self::MAIL_USING);
            $identityId = data_get($identities, 'list.0.id');

            if (blank($identityId)) {
                return false;
            }

            $result = $this->request('Identity/set', [
                'accountId' => $user->mail_account_id,
                'update' => [
                    $identityId => [
                        'name' => $user->name,
                        'textSignature' => $this->textSignature($user),
                        'htmlSignature' => $this->htmlSignature($user),
                    ],
                ],
            ], self::MAIL_USING);

            return array_key_exists($identityId, $result['updated'] ?? []);
        } catch (Throwable $exception) {
            report($exception);

            return false;
        }
    }

    public function ensureMailbox(User $user): User
    {
        if (in_array($user->employment_status, [User::STATUS_DEACTIVE, User::STATUS_DELETED, 'inactive', 'resigned'], true)) {
            throw new RuntimeException('Tài khoản không hoạt động.');
        }

        if (blank($user->username)) {
            throw new RuntimeException('Người dùng chưa có username.');
        }

        return Cache::lock('mail-provision:'.$user->getKey(), 30)->block(10, function () use ($user): User {
            $freshUser = User::query()->findOrFail($user->getKey());

            if (filled($freshUser->mail_account_id) && filled($freshUser->mail_address)) {
                $this->syncProfile($freshUser);

                return $freshUser;
            }

            $mailbox = $this->provision(
                $freshUser,
                (string) $freshUser->username,
                Str::random(64),
                (int) ($freshUser->mail_quota_mb ?: 2048),
            );

            $freshUser->forceFill([
                'mail_address' => $mailbox['address'],
                'mail_account_id' => $mailbox['id'],
                'mail_status' => User::MAIL_STATUS_ACTIVE,
                'mail_quota_mb' => (int) ($freshUser->mail_quota_mb ?: 2048),
                'mail_provisioned_at' => now(),
            ])->saveQuietly();

            $this->syncSignature($freshUser);

            return $freshUser;
        });
    }

    public function syncCredentials(User $user, string $password): bool
    {
        try {
            if (in_array($user->employment_status, [User::STATUS_DEACTIVE, User::STATUS_DELETED, 'inactive', 'resigned'], true)) {
                return false;
            }

            if (blank($user->username)) {
                throw new RuntimeException('Người dùng chưa có username.');
            }

            if (blank($user->mail_account_id)) {
                $mailbox = $this->provision($user, $user->username, $password, (int) ($user->mail_quota_mb ?: 2048));
                $user->forceFill([
                    'mail_address' => $mailbox['address'],
                    'mail_account_id' => $mailbox['id'],
                    'mail_status' => User::MAIL_STATUS_ACTIVE,
                    'mail_quota_mb' => (int) ($user->mail_quota_mb ?: 2048),
                    'mail_provisioned_at' => now(),
                ])->saveQuietly();

                $this->syncSignature($user);

                return true;
            }

            $this->resetPassword($user, $password);
            $user->forceFill([
                'mail_status' => User::MAIL_STATUS_ACTIVE,
            ])->saveQuietly();

            return true;
        } catch (Throwable $exception) {
            report($exception);

            return false;
        }
    }

    public function provision(User $user, string $localPart, string $password, int $quotaMb): array
    {
        $localPart = strtolower(trim($localPart));

        if (! preg_match('/^[a-z0-9](?:[a-z0-9._-]{0,62}[a-z0-9])?$/', $localPart)) {
            throw new RuntimeException('Tên hộp thư không hợp lệ.');
        }

        $result = $this->request('x:Account/set', [
            'accountId' => $this->managementAccountId(),
            'create' => [
                'mailbox' => [
                    '@type' => 'User',
                    'name' => $localPart,
                    'domainId' => $this->domainId(),
                    'description' => $this->profileDescription($user),
                    'credentials' => (object) [
                        '0' => (object) [
                            '@type' => 'Password',
                            'secret' => $password,
                        ],
                    ],
                    'memberGroupIds' => (object) [],
                    'roles' => ['@type' => 'User'],
                    'quotas' => [
                        'maxDiskQuota' => max(256, $quotaMb) * 1024 * 1024,
                    ],
                    'aliases' => (object) [],
                    'encryptionAtRest' => ['@type' => 'Disabled'],
                    'locale' => 'vi_VN',
                    'timeZone' => 'Asia/Ho_Chi_Minh',
                ],
            ],
        ]);

        $created = $result['created']['mailbox'] ?? null;

        if (! is_array($created) || blank($created['id'] ?? null)) {
            throw new RuntimeException($this->setError($result, 'notCreated', 'mailbox', 'Không thể cấp hộp thư.'));
        }

        return [
            'id' => (string) $created['id'],
            'address' => $localPart.'@'.$this->domain(),
        ];
    }

    public function resetPassword(User $user, string $password): void
    {
        $this->updateCredential($user, (object) [
            '@type' => 'Password',
            'secret' => $password,
        ]);
    }

    public function suspend(User $user): void
    {
        $this->updateCredential($user, null);
    }

    public function activate(User $user, string $password): void
    {
        $this->resetPassword($user, $password);
    }

    private function updateCredential(User $user, ?object $credential): void
    {
        if (blank($user->mail_account_id)) {
            throw new RuntimeException('Người dùng chưa có hộp thư.');
        }

        $result = $this->request('x:Account/set', [
            'accountId' => $this->managementAccountId(),
            'update' => [
                $user->mail_account_id => [
                    'credentials/0' => $credential,
                ],
            ],
        ]);

        if (! array_key_exists($user->mail_account_id, $result['updated'] ?? [])) {
            throw new RuntimeException($this->setError(
                $result,
                'notUpdated',
                $user->mail_account_id,
                'Không thể cập nhật hộp thư.',
            ));
        }
    }

    public function configureOutboundRelay(UiSetting $settings): void
    {
        if (! $settings->smtp_enabled) {
            return;
        }

        if (
            blank($settings->smtp_host)
            || blank($settings->smtp_port)
            || blank($settings->smtp_username)
        ) {
            throw new RuntimeException('SMTP relay cần đủ Host, Port và Username.');
        }

        $accountId = $this->managementAccountId();
        $routes = $this->managementRequest('x:MtaRoute/get', [
            'accountId' => $accountId,
        ]);
        $relay = collect($routes['list'] ?? [])
            ->first(fn (array $route): bool => ($route['@type'] ?? null) === 'Relay');

        if (! is_array($relay)) {
            if (blank($settings->smtp_password)) {
                throw new RuntimeException('SMTP Password là bắt buộc khi tạo relay mới.');
            }

            $routeName = 'crm-relay';
            $result = $this->managementRequest('x:MtaRoute/set', [
                'accountId' => $accountId,
                'create' => [
                    'relay' => $this->relayAttributes($settings, $routeName, true, true),
                ],
            ]);

            if (! is_array($result['created']['relay'] ?? null)) {
                throw new RuntimeException($this->setError($result, 'notCreated', 'relay', 'Không thể tạo SMTP relay.'));
            }

            $strategy = $this->managementRequest('x:MtaOutboundStrategy/get', [
                'accountId' => $accountId,
                'ids' => ['singleton'],
            ]);
            $current = $strategy['list'][0] ?? [];
            $routeStrategy = is_array($current['route'] ?? null) ? $current['route'] : ['match' => []];
            $routeStrategy['else'] = "'{$routeName}'";

            $updatedStrategy = $this->managementRequest('x:MtaOutboundStrategy/set', [
                'accountId' => $accountId,
                'update' => [
                    'singleton' => ['route' => $routeStrategy],
                ],
            ]);

            if (! array_key_exists('singleton', $updatedStrategy['updated'] ?? [])) {
                throw new RuntimeException('Không thể kích hoạt SMTP relay.');
            }

            return;
        }

        $routeId = (string) ($relay['id'] ?? '');
        $routeName = (string) ($relay['name'] ?? 'crm-relay');

        if ($routeId === '') {
            throw new RuntimeException('SMTP relay hiện tại không có mã cấu hình.');
        }

        $result = $this->managementRequest('x:MtaRoute/set', [
            'accountId' => $accountId,
            'update' => [
                $routeId => $this->relayAttributes(
                    $settings,
                    $routeName,
                    filled($settings->smtp_password),
                ),
            ],
        ]);

        if (! array_key_exists($routeId, $result['updated'] ?? [])) {
            throw new RuntimeException($this->setError(
                $result,
                'notUpdated',
                $routeId,
                'Không thể cập nhật SMTP relay.',
            ));
        }
    }

    private function relayAttributes(
        UiSetting $settings,
        string $name,
        bool $includeSecret,
        bool $creating = false,
    ): array {
        $attributes = [
            'description' => 'SMTP relay được quản lý từ 3RDVN CRM',
            'address' => trim((string) $settings->smtp_host),
            'port' => (int) $settings->smtp_port,
            'protocol' => 'smtp',
            'allowInvalidCerts' => false,
            'implicitTls' => $settings->smtp_encryption === 'ssl',
            'authUsername' => trim((string) $settings->smtp_username),
        ];

        if ($creating) {
            $attributes = ['@type' => 'Relay', 'name' => $name] + $attributes;
        }

        if ($includeSecret) {
            $attributes['authSecret'] = [
                '@type' => 'Value',
                'secret' => (string) $settings->smtp_password,
            ];
        }

        return $attributes;
    }

    private function managementRequest(string $method, array $arguments): array
    {
        $url = (string) config('services.stalwart.jmap_url');
        $username = (string) config('services.stalwart.admin_email');
        $password = (string) config('services.stalwart.admin_password');

        if ($url === '' || $username === '' || $password === '') {
            throw new RuntimeException('Chưa cấu hình tài khoản quản trị máy chủ mail.');
        }

        $payload = json_encode([
            'using' => self::USING,
            'methodCalls' => [[$method, $arguments, 'crm']],
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);

        $response = Http::acceptJson()
            ->withBasicAuth($username, $password)
            ->withBody($payload, 'application/json')
            ->timeout(15)
            ->connectTimeout(3)
            ->retry(2, 250, null, false)
            ->post($url);

        $this->ensureSuccessfulResponse($response);

        $methodResponse = $response->json('methodResponses.0');

        if (! is_array($methodResponse) || ($methodResponse[0] ?? null) === 'error') {
            $message = is_array($methodResponse[1] ?? null)
                ? ($methodResponse[1]['description'] ?? $methodResponse[1]['type'] ?? null)
                : null;

            throw new RuntimeException($message ?: 'Máy chủ mail từ chối cập nhật SMTP relay.');
        }

        return is_array($methodResponse[1] ?? null) ? $methodResponse[1] : [];
    }

    private function request(string $method, array $arguments, array $using = self::USING): array
    {
        $url = (string) config('services.stalwart.jmap_url');
        $apiKey = (string) config('services.stalwart.api_key');

        if ($url === '' || $apiKey === '') {
            throw new RuntimeException('Chưa cấu hình kết nối máy chủ mail.');
        }

        $payload = json_encode([
            'using' => $using,
            'methodCalls' => [[$method, $arguments, 'crm']],
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);

        $response = Http::acceptJson()
            ->withToken($apiKey)
            ->withBody($payload, 'application/json')
            ->timeout(12)
            ->connectTimeout(3)
            ->retry(2, 250, null, false)
            ->post($url);

        $this->ensureSuccessfulResponse($response);

        $methodResponse = $response->json('methodResponses.0');

        if (! is_array($methodResponse) || ($methodResponse[0] ?? null) === 'error') {
            $message = is_array($methodResponse[1] ?? null)
                ? ($methodResponse[1]['description'] ?? $methodResponse[1]['type'] ?? null)
                : null;

            throw new RuntimeException($message ?: 'Máy chủ mail trả về phản hồi không hợp lệ.');
        }

        return is_array($methodResponse[1] ?? null) ? $methodResponse[1] : [];
    }

    private function ensureSuccessfulResponse(Response $response): void
    {
        if ($response->successful()) {
            return;
        }

        throw new RuntimeException('Không thể kết nối máy chủ mail (HTTP '.$response->status().').');
    }

    private function setError(array $result, string $bucket, string $key, string $fallback): string
    {
        $error = $result[$bucket][$key] ?? null;

        return is_array($error)
            ? (string) ($error['description'] ?? $error['type'] ?? $fallback)
            : $fallback;
    }

    private function profileDescription(User $user): string
    {
        $organization = collect([$user->company_name, $user->branch_name])
            ->filter()
            ->unique()
            ->implode(' / ');

        return Str::limit(collect([
            $user->name,
            $user->uid,
            $user->employee_code,
            $user->getRoleNames()->first(),
            $user->position,
            $user->department,
            $organization,
        ])->filter()->unique()->implode(' · '), 255, '');
    }

    private function textSignature(User $user): string
    {
        return implode("\n", [
            'Thank you & Best regards,',
            '',
            '--',
            '',
            Str::upper($user->name),
            $this->signaturePosition($user),
            '',
            'THIRD-PARTY FINANCIAL SERVICES PARTNERS (3RD-VN)',
            '',
            'ADD     39 Street No. 12, Cityland Park Hills Residential Area, Ward 10, Go Vap District, Ho Chi Minh City, Vietnam',
            'TEL     (028) 8888 03979 - Ext 381',
            'MOB     '.$this->signaturePhone($user),
            'WEB     https://3rdvn.io.vn/',
            'MAIL    '.$user->mail_address,
            '',
            'IMPORTANT NOTICE: The information in this email (and any attachments) is confidential. If you are not the intended recipient, you must not use or disseminate the information. If you have received this email in error, please immediately notify me by "Reply" command and permanently delete the original and any copies or printouts thereof. Although this email and any attachments are believed to be free of any virus or other defect that might affect any computer system into which it is received and opened, it is the responsibility of the recipient to ensure that it is virus free and no responsibility is accepted by Third-Party Financial Services Partners (3rd-vn) or its subsidiaries or affiliates either jointly or severally, for any loss or damage arising in any way from its use.',
        ]);
    }

    private function htmlSignature(User $user): string
    {
        $name = e(Str::upper($user->name));
        $position = e($this->signaturePosition($user));
        $phone = e($this->signaturePhone($user));
        $mail = e((string) $user->mail_address);

        return <<<HTML
<div>
  <p><em>Thank you &amp; Best regards,</em><br><em>--</em></p>
  <p><strong>{$name}</strong> (Mr.)<br><strong>{$position}</strong></p>
  <p><strong>THIRD-PARTY FINANCIAL SERVICES PARTNERS (3RD-VN)</strong></p>
  <table cellpadding="0" cellspacing="0">
    <tr><td><strong>ADD&nbsp;&nbsp;&nbsp;&nbsp;</strong></td><td>39 Street No. 12, Cityland Park Hills Residential Area, Ward 10, Go Vap District, Ho Chi Minh City, Vietnam</td></tr>
    <tr><td><strong>TEL</strong></td><td>(028) 8888 03979 - Ext 381</td></tr>
    <tr><td><strong>MOB</strong></td><td>{$phone}</td></tr>
    <tr><td><strong>WEB</strong></td><td><a href="https://3rdvn.io.vn/">3RDVN</a></td></tr>
    <tr><td><strong>MAIL</strong></td><td><a href="mailto:{$mail}">{$mail}</a></td></tr>
  </table>
  <p><strong><em>IMPORTANT NOTICE:</em></strong> <em>The information in this email (and any attachments) is confidential. If you are not the intended recipient, you must not use or disseminate the information. If you have received this email in error, please immediately notify me by "Reply" command and permanently delete the original and any copies or printouts thereof. Although this email and any attachments are believed to be free of any virus or other defect that might affect any computer system into which it is received and opened, it is the responsibility of the recipient to ensure that it is virus free and no responsibility is accepted by Third-Party Financial Services Partners (3rd-vn) or its subsidiaries or affiliates either jointly or severally, for any loss or damage arising in any way from its use.</em></p>
</div>
HTML;
    }

    private function signaturePosition(User $user): string
    {
        return (string) ($user->position ?: $user->getRoleNames()->first() ?: '-');
    }

    private function signaturePhone(User $user): string
    {
        $digits = preg_replace('/\D+/', '', (string) $user->phone) ?: '';
        $digits = Str::startsWith($digits, '84') ? substr($digits, 2) : ltrim($digits, '0');

        if (strlen($digits) === 9) {
            return '(+84) '.substr($digits, 0, 3).' '.substr($digits, 3, 3).' '.substr($digits, 6, 3);
        }

        return filled($user->phone) ? (string) $user->phone : '-';
    }

    private function managementAccountId(): string
    {
        return (string) config('services.stalwart.account_id', 'b');
    }

    private function domainId(): string
    {
        return (string) config('services.stalwart.domain_id', 'b');
    }

    private function domain(): string
    {
        return (string) config('services.stalwart.domain', '3rdvn.io.vn');
    }
}
