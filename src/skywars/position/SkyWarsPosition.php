<?php

namespace skywars\position;

use pocketmine\level\Position;

class SkyWarsPosition extends Position {

    /**
     * @param Position $pos
     * @return array
     */
    public static function toArray(Position $pos): array {
        return ['X' => $pos->getX(), 'Y' => $pos->getY(), 'Z' => $pos->getZ()];
    }

    /**
     * @param array $data
     * @return SkyWarsPosition
     */
    public static function fromArray(array $data): Position {
        return new SkyWarsPosition($data['X'], $data['Y'], $data['Z'], $data['level']);
    }
}