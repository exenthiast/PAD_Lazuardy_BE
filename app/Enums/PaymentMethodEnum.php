<?php

namespace App\Enums;

enum PaymentMethodEnum: string
{
    case BANK_BCA = 'bca';
    case BANK_MANDIRI = 'mandiri';
    case BANK_BNI = 'bni';
    case BANK_BRI = 'bri';
    case BANK_BPR = 'bpr';
    case BANK_BPD = 'bpd';
    case BANK_QRIS = 'qris';

    public function displayName() : string 
    {
        return match($this) 
        {
            self::BANK_BCA => 'BCA',
            self::BANK_MANDIRI => 'Mandiri',
            self::BANK_BNI => 'BNI',
            self::BANK_BRI => 'BRI',
            self::BANK_BPR => 'BPR',
            self::BANK_BPD => 'BPD',
            self::BANK_QRIS => 'QRIS',
        };
    }

    public static function list() : array 
    {
        return array_map(fn($case) => $case->value, self::cases());
    }

    public static function displayList() : array 
    {
        return array_map(fn($case) => $case->displayName(), self::cases());
    }
}
