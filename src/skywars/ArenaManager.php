<?php

namespace skywars;

use pocketmine\scheduler\Task;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use skywars\arena\Arena;
use skywars\arena\Level;

class ArenaManager {

    /** @var Arena[] */
    protected $arenas = [];

    /**
     * ArenaManager constructor.
     */
    public function __construct() {
        $config = new Config(SkyWars::getInstance()->getDataFolder() . 'remove.yml', Config::YAML);

        if($config->exists('names')) {
            foreach($config->get('names') as $name) {
                SkyWars::getInstance()->getLevelManager()->deleteDir(Server::getInstance()->getDataPath() . '/worlds/' . $name);
            }

            $config->remove('names');

            $config->save();
        }

        unlink(SkyWars::getInstance()->getDataFolder() . 'remove.yml');
    }

    /**
     * @return Arena
     */
    public function createArena() {
        $level = SkyWars::getInstance()->getLevelManager()->getLevelForArena();

        if($level instanceof Level) {
            $arena = new Arena($this->id(), $level);

            $this->arenas[strtolower($arena->name)] = $arena;

            return $arena;
        }

        return null;
    }

    /**
     * @return string
     */
    public function toString(): string {
        $names = [];

        if(count($this->arenas) <= 0) {
            return TextFormat::RED . 'Not found';
        }

        foreach($this->arenas as $arena) {
            $names[] = TextFormat::GOLD . '- ' . $arena->getName() . ' (' . TextFormat::YELLOW . $arena->getLevel()->getCustomName() . TextFormat::GOLD . ')';
        }

        return implode("\n", $names);
    }

    /**
     * @return Arena[]
     */
    public function getArenas(): array {
        return $this->arenas;
    }

    /**
     * @param Level $level
     * @return Arena[]
     */
    public function getArenasByLevel(Level $level): array {
        $arenas = [];

        foreach($this->arenas as $arena) {
            if($arena instanceof Arena) {
                if($arena->getLevel()->getFolderName() == $level->getFolderName()) $arenas[$arena->getName()] = $arena;
            }
        }

        return $arenas;
    }

    /**
     * @param string $name
     * @return Arena
     */
    public function getArena(string $name) {
        return $this->arenas[$name] ?? null;
    }

    /**
     * @param Arena $arena
     */
    public function removeArena(Arena $arena) {
        if($arena->handler instanceof Task) {
            $arena->handler->getHandler()->cancel();

            $arena->handler = null;
        }

        if(isset($this->arenas[strtolower($arena->getName())])) {
            unset($this->arenas[strtolower($arena->getName())]);
        }
    }

    /**
     * @param string $name
     * @return Arena|null
     */
    public function getArenaByPlayer(string $name) {
        foreach($this->arenas as $arena) {
            if($arena instanceof Arena) {
                if(isset($arena->getEveryone()[strtolower($name)])) {
                    return $arena;
                }
            }
        }
        return null;
    }

    /**
     * @return string
     */
    public function id(): string {
        $ks = 'abcdefghijklmnopqrstuvwxyz';

        $id = 'g' . rand(1, 10) . $ks[rand(0, (strlen($ks) - 1))] . '0' . rand(1, 100) . 'r';

        foreach($this->getArenas() as $arena) {
            if($arena->getName() == $id) return $this->id();
        }

        return $id;
    }
}