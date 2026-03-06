<?php
declare(strict_types=1);

class AppConfig
{
    private mixed $config = null;

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

    public function getAppImpressUrl(): string
    {
        return $this->get_str_value('AppImpressUrl', '');
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
            $currentDatetime = $currentDatetime->add(new DateInterval('PT' . $delayMinutes . 'M'));
        }
        elseif ($delayMinutes < 0) {
            $currentDatetime = $currentDatetime->sub(new DateInterval('PT' . abs($delayMinutes) . 'M'));
        }
        return $currentDatetime;
    }

    public function getTimezone(): string
    {
        return $this->get_str_value('Timezone', 'Europe/Berlin');
    }

    public function getEventDateRangeFrom(): ?DateTime
    {
        $value = $this->get_str_value('EventDateRangeFrom');
        if ($value === '') {
            return null;
        }
        $dt = DateTime::createFromFormat('Y-m-d\TH:i', $value);
        return $dt !== false ? $dt : null;
    }

    public function getEventDateRangeTo(): ?DateTime
    {
        $value = $this->get_str_value('EventDateRangeTo');
        if ($value === '') {
            return null;
        }
        $dt = DateTime::createFromFormat('Y-m-d\TH:i', $value);
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
