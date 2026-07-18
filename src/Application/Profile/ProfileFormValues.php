<?php

declare(strict_types=1);

namespace App\Application\Profile;

use App\Domain\User\User;

/**
 * @psalm-type ProfileFormValuesArray = array{
 *     surname: string,
 *     name: string,
 *     patronymic: string,
 *     company_name: string,
 *     description: string,
 *     phone: string,
 *     gender: string
 * }
 */
final readonly class ProfileFormValues
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

    public static function fromUser(User $user): self
    {
        return new self(
            surname: $user->getSurname(),
            name: $user->getName(),
            patronymic: $user->getPatronymic(),
            companyName: $user->getCompanyName(),
            description: $user->getDescription(),
            phone: $user->getPhone(),
            gender: $user->getGender()?->value ?? '',
        );
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            surname: (string) ($data['surname'] ?? ''),
            name: (string) ($data['name'] ?? ''),
            patronymic: (string) ($data['patronymic'] ?? ''),
            companyName: (string) ($data['company_name'] ?? ''),
            description: (string) ($data['description'] ?? ''),
            phone: (string) ($data['phone'] ?? ''),
            gender: (string) ($data['gender'] ?? ''),
        );
    }

    /** @return ProfileFormValuesArray */
    public function toArray(): array
    {
        return [
            'surname' => $this->surname,
            'name' => $this->name,
            'patronymic' => $this->patronymic,
            'company_name' => $this->companyName,
            'description' => $this->description,
            'phone' => $this->phone,
            'gender' => $this->gender,
        ];
    }
}
