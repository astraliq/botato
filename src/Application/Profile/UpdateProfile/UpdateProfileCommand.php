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
}
