<?php
namespace app\Enums;
enum Role : int{
    case SuperAdministrator = 1;

    case User = 2;
    case moderator = 3;
    case MarketingManager = 4;

    case  Customer = 5;

    /**
     * @param int $value
     * @return self|null
     *
     */
    public static function fromValue(int $value): ?self
    {

        return  match ($value){
            1 => self::SuperAdministrator,
            2 => self::User,
            3 => self::moderator,
            4 => self::MarketingManager,
            5 => self::Customer,
            default => null,


        };
    }

    /**
     * @param int $key
     * @return self|null
     *
     */

    public static function fromKey(string $key): ?self

    {
        return match ($key){
            'SuperAdministrator' => self::SuperAdministrator,
            'User'=> self::User,
            'moderator'=>self::moderator,
            'MarketingManager' => self::MarketingManager,
            'Customer' => self::Customer,
             default => null,

        };

    }


};

