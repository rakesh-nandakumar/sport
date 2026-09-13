<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case PayAtVenue = 'pay_at_venue';
    case BankTransfer = 'bank_transfer';
    case Card = 'card';
    case Koko = 'koko';
    case MintPay = 'mintpay';
    case PayEasy = 'payeasy';

    public function label(): string
    {
        return match ($this) {
            self::PayAtVenue => 'Pay at Venue',
            self::BankTransfer => 'Bank Transfer',
            self::Card => 'Debit / Credit Card',
            self::Koko => 'Koko',
            self::MintPay => 'Mint Pay',
            self::PayEasy => 'PayEasy',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::PayAtVenue => 'Reserve now and pay in cash when you arrive. Lowest priority: a paid booking for the same slot can replace it.',
            self::BankTransfer => 'Transfer the total to the venue\'s bank account and upload your slip. Locked in once the vendor verifies it.',
            self::Card => 'Pay instantly with Visa or Mastercard via secure gateway. Confirms your slot immediately.',
            self::Koko => 'Split your payment into three interest-free instalments with Koko.',
            self::MintPay => 'Buy now, pay later in instalments with Mint Pay.',
            self::PayEasy => 'Pay through PayEasy wallet or bank app.',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::PayAtVenue => 'fa-solid fa-store',
            self::BankTransfer => 'fa-solid fa-building-columns',
            self::Card => 'fa-regular fa-credit-card',
            self::Koko => 'fa-solid fa-k',
            self::MintPay => 'fa-solid fa-leaf',
            self::PayEasy => 'fa-solid fa-wallet',
        };
    }

    /**
     * Higher wins when two bookings compete for the same slot.
     * Direct (pay at venue) is the weakest; online gateways are the strongest.
     */
    public function priority(): int
    {
        return match ($this) {
            self::PayAtVenue => 1,
            self::BankTransfer => 2,
            self::Card, self::Koko, self::MintPay, self::PayEasy => 3,
        };
    }

    public function isAvailable(): bool
    {
        return in_array($this, [self::PayAtVenue, self::BankTransfer], true);
    }

    public function isOnlineGateway(): bool
    {
        return $this->priority() === 3;
    }

    /** @return list<self> */
    public static function available(): array
    {
        return array_values(array_filter(self::cases(), fn (self $m) => $m->isAvailable()));
    }
}
