<?php

declare(strict_types=1);

namespace App\Application\Profile\UpdateProfile;

use App\Application\Profile\ProfileFormValues;
use App\Shared\ArrayHelper;

final readonly class UpdateProfileCommand
{
    public function __construct(
        public string $surname,
        public string $name,
        public string $patronymic,
        public string $companyName,
        public string $description,
        public string $phone,
        public string $gender,
        public ?bool $isConsentPersonalData = null,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            surname: ArrayHelper::toString($data['surname'] ?? null),
            name: ArrayHelper::toString($data['name'] ?? null),
            patronymic: ArrayHelper::toString($data['patronymic'] ?? null),
            companyName: ArrayHelper::toString($data['company_name'] ?? null),
            description: ArrayHelper::toString($data['description'] ?? null),
            phone: ArrayHelper::toString($data['phone'] ?? null),
            gender: ArrayHelper::toString($data['gender'] ?? null),
            isConsentPersonalData: array_key_exists('is_consent_personal_data', $data)
                ? self::toBool($data['is_consent_personal_data'])
                : null,
        );
    }

    public function toFormValues(): ProfileFormValues
    {
        return new ProfileFormValues(
            surname: $this->surname,
            name: $this->name,
            patronymic: $this->patronymic,
            companyName: $this->companyName,
            description: $this->description,
            phone: $this->phone,
            gender: $this->gender,
        );
    }

    private static function toBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            return $value === 1;
        }

        if (is_string($value)) {
            return in_array(strtolower($value), ['1', 'true', 'yes', 'on'], true);
        }

        return false;
    }
}
