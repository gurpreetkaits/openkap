<?php

namespace App\Repositories;

use App\Models\Setting;
use Illuminate\Support\Collection;

class SettingRepository
{
    public function __construct(
        private Setting $model
    ) {}

    public function findByKey(string $key): ?Setting
    {
        return $this->model->where('key', $key)->first();
    }

    public function getValue(string $key, mixed $default = null): mixed
    {
        return Setting::getValue($key, $default);
    }

    public function setValue(string $key, mixed $value, string $type = 'string', string $group = 'general', ?string $description = null): Setting
    {
        return Setting::setValue($key, $value, $type, $group, $description);
    }

    public function getByGroup(string $group): Collection
    {
        return $this->model->where('group', $group)->get();
    }

    public function getAll(): Collection
    {
        return $this->model->all();
    }

    public function update(Setting $setting, array $data): Setting
    {
        $setting->update($data);
        Setting::clearCache();

        return $setting->fresh();
    }

    public function delete(Setting $setting): bool
    {
        Setting::clearCache();

        return $setting->delete();
    }
}
