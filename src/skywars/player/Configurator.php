<?php


namespace skywars\player;

use pocketmine\level\Level as pocketLevel;
use pocketmine\level\Position;
use pocketmine\Server;
use skywars\arena\Level;
use skywars\SkyWars;
use skywars\position\SkyWarsPosition;

class Configurator extends PlayerBase {

    /** @var array */
    private $dataLevel;

    /**
     * Configurator constructor.
     * @param array $data
     * @param array $dataLevel
     */
    public function __construct(array $data, array $dataLevel){
        parent::__construct($data);
        $this->dataLevel = $dataLevel;
        SkyWars::getInstance()->configurators[strtolower($this->getName())] = $this;
    }


    /**
     * @return bool
     */
    public function run(): bool {
        if(!Server::getInstance()->isLevelLoaded($this->getFolderName())) {
            Server::getInstance()->loadLevel($this->getFolderName());
        }

        if(!$this->getLevel() instanceof pocketLevel){
            Server::getInstance()->getLogger()->error('Error loading level.');

            return false;
        }

        return true;
    }

    /**
     * @param string $folder
     */
    public function setFolderName(string $folder){
        $this->dataLevel['folderName'] = $folder;
    }

    /**
     * @param string $name
     */
    public function setCustomName(string $name){
        $this->dataLevel['customName'] = $name;
    }

    /**
     * @param int $slots
     */
    public function setMaxSlots(int $slots){
        $this->dataLevel['maxSlots'] = $slots;
    }

    /**
     * @param int $slot
     * @param Position $pos
     */
    public function setSpawnPosition(int $slot, Position $pos){
        $this->dataLevel['spawn'][$slot] = SkyWarsPosition::toArray($pos);
    }

    /**
     * @return string|null
     */
    public function getCustomName(): string {
        return $this->dataLevel['customName'];
    }

    /**
     * @return string|null
     */
    public function getFolderName(): string {
        return $this->dataLevel['folderName'];
    }

    /**
     * @return pocketLevel|null
     */
    public function getLevel(): pocketLevel {
        return Server::getInstance()->getLevelByName($this->getFolderName());
    }

    /**
     * @return int
     */
    public function getMaxSlots(): int {
        return $this->dataLevel['maxSlots'];
    }

    /**
     * @return array
     */
    public function getSpawnsPosition(): array {
        return $this->dataLevel['spawn'];
    }

    public function save(){
        SkyWars::getInstance()->getLevelManager()->backup(Server::getInstance()->getDataPath() . 'worlds/' . $this->getFolderName(), SkyWars::getInstance()->getDataFolder() . 'backup/' . $this->getFolderName());

        SkyWars::getInstance()->getLevelManager()->add(new Level($this->dataLevel));

        SkyWars::getInstance()->getLevelManager()->save();

        unset(SkyWars::getInstance()->configurators[strtolower($this->getName())]);
    }

    public function defaultValues(){
        $this->getInventory()->clearAll();
    }
}