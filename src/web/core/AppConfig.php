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

    public function getAppImpressUrl(): string
    {
        return $this->get_str_value('AppImpressUrl', '');
    }

    private function get_str_value(string $key, string $default = ''): string
    {
        if (is_array($this->config) && array_key_exists($key, $this->config)) {
            return (string)$this->config[$key];
        }
        return $default;
    }
}
