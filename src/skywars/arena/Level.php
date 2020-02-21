<?php


namespace skywars\arena;

use pocketmine\Server;
use skywars\SkyWars;
use skywars\position\SkyWarsPosition;

class Level {

    /** @var Arena */
    protected $arena;

    /** @var array */
    public $data = [];

    /**
     * Level constructor.
     * @param array $data
     */
    public function __construct(array $data){
        $this->data = $data;
    }

    /**
     * @return string
     */
    public function getCustomName(): string {
        return $this->data['customName'];
    }

    /**
     * @return string
     */
    public function getFolderName(): string {
        return $this->data['folderName'];
    }

    /**
     * @return int
     */
    public function getMaxSlots(): int {
        return $this->data['maxSlots'];
    }

    /**
     * @param int $slot
     * @return SkyWarsPosition
     */
    public function getSpawnPosition(int $slot): SkyWarsPosition {
        if(!($this->isSlot($slot))) {
            return null;
        }

        $data = $this->data['spawn'][$slot];

        $data['level'] = $this->getLevel();

        return SkyWarsPosition::fromArray($data);
    }

    /**
     * @param int $k
     * @return bool
     */
    public function isSlot(int $k): bool {
        return isset($this->data['spawn'][$k]);
    }

    /**
     * @param Arena $arena
     * @return Level
     */
    public function setArena(Arena $arena): Level {
        $this->arena = $arena;

        return $this;
    }

    /**
     * @return Arena|null
     */
    public function getArena(): Arena {
        return $this->arena;
    }

    /**
     * @return \pocketmine\level\Level|null
     */
    public function getLevel() {
        if(Server::getInstance()->getLevelByName(strval($this->arena->getName())) instanceof \pocketmine\level\Level) {
            return Server::getInstance()->getLevelByName(strval($this->arena->getName()));
        }
        return Server::getInstance()->getLevelByName($this->arena->getName());
    }

    /**
     * @param array $data
     */
    public function setLevelData(array $data){
        $this->data = $data;
    }

    /**
     * @return array
     */
    public function getLevelData(): array {
        return $this->data;
    }

    public function load(){
        if($this->arena == null) {
            return;
        }

        SkyWars::getInstance()->getLevelManager()->backup(SkyWars::getInstance()->getDataFolder() . '/backup/' . $this->getFolderName(), Server::getInstance()->getDataPath() . '/worlds/' . $this->arena->getName());

        Server::getInstance()->loadLevel($this->arena->getName());
    }

    public function close(){
        SkyWars::getInstance()->getArenaManager()->removeArena($this->arena);

        SkyWars::getInstance()->getLevelManager()->deleteDir(Server::getInstance()->getDataPath() . 'worlds/' . $this->getArena()->getName());
    }
}