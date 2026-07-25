<?php

declare(strict_types=1);

namespace App\Domain\Plan;

final class Plan
{
    public function __construct(
        private readonly string $id,
        private readonly string $code,
        private readonly string $name,
        private readonly string $description,
        private readonly int $sortOrder,
        private readonly bool $isPopular,
        private readonly int $priceMonthlyMinor,
        private readonly int $maxDialogsPerMonth,
        private readonly int $maxMessagesPerDialog,
        private readonly ?int $maxChannels,
        private readonly int $maxKnowledgeBases,
        private readonly string $buttonLabel,
        private readonly bool $isActive,
        private readonly bool $deleted,
    ) {}

    public function getId(): string
    {
        return $this->id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function isPopular(): bool
    {
        return $this->isPopular;
    }

    public function getPriceMonthlyMinor(): int
    {
        return $this->priceMonthlyMinor;
    }

    /** Цена в рублях для отображения (без копеек). */
    public function getPriceMonthlyRubles(): int
    {
        return (int) round($this->priceMonthlyMinor / 100);
    }

    public function getMaxDialogsPerMonth(): int
    {
        return $this->maxDialogsPerMonth;
    }

    public function getMaxMessagesPerDialog(): int
    {
        return $this->maxMessagesPerDialog;
    }

    public function getMaxChannels(): ?int
    {
        return $this->maxChannels;
    }

    public function getMaxKnowledgeBases(): int
    {
        return $this->maxKnowledgeBases;
    }

    public function getButtonLabel(): string
    {
        if (!$this->isActive) {
            return 'Скоро';
        }

        return $this->buttonLabel;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function isDeleted(): bool
    {
        return $this->deleted;
    }

    public function hasUnlimitedChannels(): bool
    {
        return $this->maxChannels === null;
    }

    public function getDialogsFeatureLabel(): string
    {
        $count = number_format($this->maxDialogsPerMonth, 0, '', ' ');

        return $count . ' диалогов в месяц';
    }

    public function getMaxMessagesPerDialogFeatureLabel(): string
    {
        return 'До ' . $this->maxMessagesPerDialog . ' '
            . self::pluralRu($this->maxMessagesPerDialog, 'сообщение', 'сообщения', 'сообщений')
            . ' в диалоге';
    }

    public function getChannelsFeatureLabel(): string
    {
        if ($this->hasUnlimitedChannels()) {
            return 'Без лимита каналов';
        }

        $count = (int) $this->maxChannels;

        return $count . ' ' . self::pluralRu($count, 'канал', 'канала', 'каналов');
    }

    public function getKnowledgeBasesFeatureLabel(): string
    {
        return $this->maxKnowledgeBases . ' '
            . self::pluralRu($this->maxKnowledgeBases, 'база знаний', 'базы знаний', 'баз знаний');
    }

    private static function pluralRu(int $count, string $one, string $few, string $many): string
    {
        $mod100 = $count % 100;
        $mod10 = $count % 10;

        if ($mod100 >= 11 && $mod100 <= 14) {
            return $many;
        }

        if ($mod10 === 1) {
            return $one;
        }

        if ($mod10 >= 2 && $mod10 <= 4) {
            return $few;
        }

        return $many;
    }
}
