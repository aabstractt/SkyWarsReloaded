<?php

namespace skywars\task;

use pocketmine\level\Level;
use pocketmine\scheduler\Task;
use pocketmine\Server;
use pocketmine\tile\Sign as pocketSign;
use skywars\arena\Sign;
use skywars\SkyWars;

class SignTask extends Task {

    /**
     * SignTask constructor.
     */
    public function __construct(){
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
        foreach(Server::getInstance()->getDefaultLevel()->getTiles() as $tile) {
            if($tile instanceof pocketSign) {
                $sign = SkyWars::getInstance()->getSignManager()->getSignByPosition($tile);

                if($sign instanceof Sign) {

                    if(!$sign->getSign()->level instanceof Level) {
                        $sign->setTile($tile);
                    }

                    $sign->getStatusColor();

                    $sign->getBlockStatus();

                    $tile->spawnToAll();
                }
            }
        }
    }
}