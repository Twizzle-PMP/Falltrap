<?php

declare(strict_types=1);

namespace Twizzle\Falltrap\utils;

use pocketmine\world\Position;

class SessionManager {

    private static array $sessions = [];

    public static function setPos1(string $name, Position $pos): void {
        self::$sessions[$name]["pos1"] = $pos;
    }

    public static function setPos2(string $name, Position $pos): void {
        self::$sessions[$name]["pos2"] = $pos;
    }

    public static function getSession(string $name): ?array {
        return self::$sessions[$name] ?? null;
    }

    public static function clear(string $name): void {
        unset(self::$sessions[$name]);
    }
}