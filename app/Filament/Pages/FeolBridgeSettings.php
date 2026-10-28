<?php

namespace App\Filament\Pages;

use App\Support\Filament\AdminNavigation;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Arr;
use RuntimeException;

class FeolBridgeSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $slug = 'admin/feol-bridge-settings';

    protected string $view = 'filament.pages.feol-bridge-settings';

    public array $config = [];

    public ?string $partner_password = null;

    public ?string $crm_feol_token = null;

    public string $serviceStatus = 'unknown';

    public static function getNavigationLabel(): string
    {
        return 'Cấu hình Node-RED';
    }

    public static function getNavigationGroup(): ?string
    {
        return AdminNavigation::GROUP;
    }

    public static function getNavigationSort(): ?int
    {
        return 995;
    }

    public static function canAccess(): bool
    {
        $appHost = (string) parse_url((string) config('app.url'), PHP_URL_HOST);

        return ! str_starts_with($appHost, 'uat-')
            && (auth()->user()?->hasRole('Admin') ?? false);
    }

    public function getTitle(): string
    {
        return 'Cấu hình Node-RED';
    }

    public function mount(): void
    {
        $this->loadConfig();
    }

    public function loadConfig(): void
    {
        $result = $this->helper(['--read']);

        $this->config = array_merge([
            'CRM_UAT_URL' => '',
            'CRM_FEOL_TOKEN' => '********',
            'PARTNER_AUTH_URL' => '',
            'PARTNER_DATA_URL' => '',
            'PARTNER_USERNAME' => '',
            'PARTNER_PASSWORD' => '********',
            'PARTNER_CAMPAIGN' => 'FE - Cash Loan - Deeplink',
            'PARTNER_PAGE_SIZE' => '200',
            'PARTNER_MAX_PAGES' => '5',
        ], (array) ($result['config'] ?? []));

        $this->serviceStatus = (string) ($result['status'] ?? 'active');
        $this->partner_password = null;
        $this->crm_feol_token = null;
    }

    public function save(): void
    {
        $data = $this->validate([
            'config.CRM_UAT_URL' => ['required', 'url', 'max:255'],
            'config.PARTNER_AUTH_URL' => ['required', 'url', 'max:255'],
            'config.PARTNER_DATA_URL' => ['required', 'url', 'max:255'],
            'config.PARTNER_USERNAME' => ['required', 'string', 'max:120'],
            'config.PARTNER_CAMPAIGN' => ['required', 'string', 'max:180'],
            'config.PARTNER_PAGE_SIZE' => ['required', 'integer', 'min:50', 'max:500'],
            'config.PARTNER_MAX_PAGES' => ['required', 'integer', 'min:1', 'max:10'],
            'partner_password' => ['nullable', 'string', 'max:255'],
            'crm_feol_token' => ['nullable', 'string', 'max:255'],
        ], [], [
            'config.CRM_UAT_URL' => 'CRM nội bộ',
            'config.PARTNER_AUTH_URL' => 'Auth URL đối tác',
            'config.PARTNER_DATA_URL' => 'Data URL đối tác',
            'config.PARTNER_USERNAME' => 'Tài khoản đối tác',
            'config.PARTNER_CAMPAIGN' => 'Chiến dịch',
            'config.PARTNER_PAGE_SIZE' => 'Số dòng mỗi trang',
            'config.PARTNER_MAX_PAGES' => 'Số trang tối đa',
        ]);

        $config = Arr::only($data['config'], [
            'CRM_UAT_URL', 'PARTNER_AUTH_URL', 'PARTNER_DATA_URL', 'PARTNER_USERNAME',
            'PARTNER_CAMPAIGN', 'PARTNER_PAGE_SIZE', 'PARTNER_MAX_PAGES',
        ]);

        if (filled($this->partner_password)) {
            $config['PARTNER_PASSWORD'] = $this->partner_password;
        }

        if (filled($this->crm_feol_token)) {
            $config['CRM_FEOL_TOKEN'] = $this->crm_feol_token;
        }

        $result = $this->helper([], [
            'restart' => true,
            'config' => $config,
        ]);

        if (! ($result['ok'] ?? false)) {
            throw RuntimeException::fromThrowable(new RuntimeException((string) ($result['error'] ?? 'Không thể lưu cấu hình Node-RED.')));
        }

        $this->config = array_merge($this->config, (array) ($result['config'] ?? []));
        $this->serviceStatus = (string) ($result['status'] ?? 'unknown');
        $this->partner_password = null;
        $this->crm_feol_token = null;

        Notification::make()
            ->title('Đã lưu cấu hình Node-RED')
            ->body('Bridge đã restart. Trạng thái service: '.$this->serviceStatus)
            ->success()
            ->send();
    }

    private function helper(array $args = [], ?array $payload = null): array
    {
        $command = array_merge(['sudo', '/usr/local/sbin/3rdvn-feol-bridge-config'], $args);
        $descriptorSpec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($command, $descriptorSpec, $pipes, base_path());

        if (! is_resource($process)) {
            throw new RuntimeException('Không mở được helper cấu hình Node-RED.');
        }

        fwrite($pipes[0], $payload === null ? '' : json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        $code = proc_close($process);
        $result = json_decode((string) $stdout, true);

        if (! is_array($result)) {
            throw new RuntimeException(trim($stderr) ?: 'Helper Node-RED trả dữ liệu không hợp lệ.');
        }

        if ($code !== 0 && ! ($result['ok'] ?? false)) {
            throw new RuntimeException((string) ($result['error'] ?? trim($stderr) ?: 'Helper Node-RED lỗi.'));
        }

        return $result;
    }
}
