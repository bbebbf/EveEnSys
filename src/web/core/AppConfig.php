<?php
declare(strict_types=1);

class AppConfig
{
    private mixed $config = null;
    private ?AppLogo $appLogo = null;

    private const APP_VERSION = '1.0';

    public function __construct() {
        $_appConfigFile = dirname(APP_ROOT) . '/_config/app-config.json';
        if (file_exists($_appConfigFile)) {
            $this->config = json_decode(file_get_contents($_appConfigFile), true);
        }
    }

    public function getAppTitleShort(): string
    {
        return $this->get_str_value('AppTitleShort', 'No App Title');
    }

    public function getAppTitleLong(): string
    {
        return $this->get_str_value('AppTitleLong', 'No App Title');
    }

    public function getAppVersion(): string
    {
        return self::APP_VERSION;
    }

    public function getAppImpressUrl(): string
    {
        return $this->get_str_value('AppImpressUrl', '');
    }

    public function getOperatorName(): string
    {
        return $this->get_str_value('OperatorName', '');
    }

    public function getOperatorStreet(): string
    {
        return $this->get_str_value('OperatorStreet', '');
    }

    public function getOperatorCity(): string
    {
        return $this->get_str_value('OperatorCity', '');
    }

    public function getOperatorResponsible(): string
    {
        return $this->get_str_value('OperatorResponsible', '');
    }

    public function getOperatorEmail(): string
    {
        return $this->get_str_value('OperatorEmail', '');
    }

    public function getKioskSlideDurationMs(): int
    {
        if (is_array($this->config) && array_key_exists('KioskSlideDurationSec', $this->config)) {
            return max(1, (int)$this->config['KioskSlideDurationSec']) * 1000;
        }
        return 8000;
    }

    public function getDelayedStartMinutes(): int
    {
        if (is_array($this->config) && array_key_exists('DelayedStartMinutes', $this->config)) {
            return (int)$this->config['DelayedStartMinutes'];
        }
        return 0;
    }

    public function getDelayedCurrentDateTime(): DateTime
    {
        $delayMinutes = $this->getDelayedStartMinutes();
        $currentDatetime = new DateTime();
        if ($delayMinutes > 0) {
            $currentDatetime = $currentDatetime->sub(new DateInterval('PT' . $delayMinutes . 'M'));
        }
        elseif ($delayMinutes < 0) {
            $currentDatetime = $currentDatetime->add(new DateInterval('PT' . abs($delayMinutes) . 'M'));
        }
        return $currentDatetime;
    }

    public function getTimezone(): string
    {
        return $this->get_str_value('Timezone', 'Europe/Berlin');
    }

    public function getEventDateRangeFrom(): ?DateTime
    {
        return $this->parse_datetime('EventDateRangeFrom');
    }

    public function getEventDateRangeTo(): ?DateTime
    {
        return $this->parse_datetime('EventDateRangeTo');
    }

    public function getNewEventsDaysOld(): int
    {
        if (is_array($this->config) && array_key_exists('NewEventsDaysOld', $this->config)) {
            return max(1, (int)$this->config['NewEventsDaysOld']);
        }
        return 3;
    }

    public function getMaintenanceBanner(): string
    {
        return $this->get_str_value('MaintenanceBanner', '');
    }

    public function getSearchbarStartsAtItemCount(): int
    {
        if (is_array($this->config) && array_key_exists('SearchbarStartsAtItemCount', $this->config)) {
            return max(0, (int)$this->config['SearchbarStartsAtItemCount']);
        }
        return 1000000;
    }

    public function getEnrollmentPeriodFrom(): ?DateTime
    {
        return $this->parse_datetime('EnrollmentPeriodFrom');
    }

    public function getEnrollmentPeriodToExcluded(): ?DateTime
    {
        return $this->parse_datetime('EnrollmentPeriodToExcluded');
    }

    public function isEnrollmentWindowOpen(): array
    {
        $from       = $this->getEnrollmentPeriodFrom();
        $toExcluded = $this->getEnrollmentPeriodToExcluded();

        if ($from === null && $toExcluded === null) {
            return ['open' => true];
        }

        $now = new DateTime();
        if ($from !== null && $now < $from) {
            return [
                'open' => false,
                'message' => 'Anmeldungen sind ab dem ' . $from->format('Y-m-d H:i') . ' möglich.'
            ];
        }
        if ($toExcluded !== null && $now >= $toExcluded) {
            return [
                'open' => false,
                'message' => 'Anmeldungen sind derzeit nicht möglich.'
            ];
        }
        return ['open' => true];
    }

    public function isNewEventApprovalRequired(): bool
    {
        if (is_array($this->config) && array_key_exists('NewEventApprovalRequired', $this->config)) {
            return (bool)$this->config['NewEventApprovalRequired'];
        }
        return false;
    }

    public function getNavbarColor(): string
    {
        return $this->get_str_value('NavbarColor', '#343a40');
    }

    public function getAppLogo(): AppLogo
    {
        if ($this->appLogo === null) {
            $this->appLogo = new AppLogo($this->get_str_value('AppLogoHeight', ''));
        }
        return $this->appLogo;
    }

    private function parse_datetime(string $key): ?DateTime
    {
        $value = $this->get_str_value($key);
        if ($value === '') {
            return null;
        }
        $dt = DateTime::createFromFormat('Y-m-d\TH:i:s', $value)
           ?: DateTime::createFromFormat('Y-m-d\TH:i', $value)
           ?: DateTime::createFromFormat('Y-m-d', $value);
        return $dt !== false ? $dt : null;
    }

    private function get_str_value(string $key, string $default = ''): string
    {
        if (is_array($this->config) && array_key_exists($key, $this->config)) {
            return (string)$this->config[$key];
        }
        return $default;
    }
}
