<?php

namespace skywars\task;

use pocketmine\scheduler\Task;
use pocketmine\Server;
use skywars\arena\Arena;

class GameTask extends Task {

    /** @var Arena */
    protected $arena;

    /**
     * GameTask constructor.
     * @param Arena $arena
     */
    public function __construct(Arena $arena){
        $this->arena = $arena;

        $this->setHandler(Server::getInstance()->getScheduler()->scheduleRepeatingTask($this, 20));
    }

    /**
     * Actions to execute when run
     *
     * @param int $currentTick
     *
     * @return void
     */
    public function onRun($currentTick){
        $this->arena->tick();
    }

    public function cancel(){
        $this->getHandler()->cancel();

        $this->arena->handler = null;
    }
}