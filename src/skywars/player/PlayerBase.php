<?php

namespace skywars\player;

use advancedserver\Player as pocketPlayer;
use pocketmine\inventory\PlayerInventory;
use pocketmine\level\Position;
use pocketmine\Server;
use skywars\arena\Arena;
use UndefinedPropertyException;

abstract class PlayerBase {

    /** @var array */
    protected $data;

    /**
     * PlayerBase constructor.
     * @param array $data
     */
    public function __construct(array $data) {
        $this->data = $data;
    }

    public abstract function defaultValues();

    /**
     * @return array
     */
    public function getData(): array {
        return $this->data;
    }

    /**
     * @return string
     */
    public function getName(): string {
        return $this->data['name'];
    }

    /**
     * @return pocketPlayer|null
     */
    public function getInstance(): pocketPlayer {
        $target = Server::getInstance()->getPlayer($this->getName());
        if($target instanceof pocketPlayer) {
            return $target;
        }

        foreach(Server::getInstance()->getOnlinePlayers() as $target) {
            if($target instanceof pocketPlayer) {
                if($target->getName() == $this->getName()) {
                    return $target;
                }
            }
        }

        $this->getArena()->remove($this->getInstance()->getName());

        echo $this->getName() . 'no es un jugador' . PHP_EOL;
        return null;
    }

    public function addKill() {
        $this->data['kills']++;
    }

    /**
     * @param int $slot
     */
    public function setSlot(int $slot) {
        if(!$this->getArena()->getLevel()->isSlot($slot)) {
            throw new UndefinedPropertyException('Slot ' . $slot . ' not available in ' . $this->getArena()->getName() . ' arena.');
        }
        $this->data['slot'] = $slot;
    }

    /**
     * @return int
     */
    public function getSlot(): int {
        return $this->data['slot'];
    }

    /**
     * @return bool
     */
    public function isSpectating(): bool {
        return $this->data['spectating'];
    }

    public function convertSpectator() {
        $this->data['spectating'] = true;
    }

    /**
     * @return Arena
     */
    public function getArena(): Arena {
        return $this->data['arena'];
    }

    /**
     * @return int
     */
    public function getKills(): int {
        return $this->data['kills'];
    }

    /**
     * @param string $message
     */
    public function sendMessage(string $message) {
        $this->getInstance()->sendMessage($message);
    }

    /**
     * @param int $v
     */
    public function setGamemode(int $v) {
        $this->getInstance()->setGamemode($v);
    }

    /**
     * @return PlayerInventory
     */
    public function getInventory(): PlayerInventory {
        return $this->getInstance()->getInventory();
    }

    /**
     * @return Position
     */
    public function asPosition(): Position {
        return new Position($this->getInstance()->x, $this->getInstance()->y, $this->getInstance()->z, $this->getInstance()->getLevel());
    }
}