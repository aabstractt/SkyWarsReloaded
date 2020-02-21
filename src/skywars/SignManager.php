<?php

namespace skywars;

use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\Server;
use pocketmine\tile\Sign as pocketSign;
use skywars\arena\Arena;
use skywars\arena\Sign;
use skywars\task\SignTask;

class SignManager {

    /** @var array */
    protected $signs = [];

    /** @var array */
    public $data = [];

    public function load() {
        if(count($this->getSigns()) > 0) {
            return;
        }

        if(file_exists(SkyWars::getInstance()->getDataFolder() . 'signs.json')) {
            $this->data = json_decode(file_get_contents(SkyWars::getInstance()->getDataFolder() . 'signs.json'), true);
        }

        if(count($this->data) == 0) {
            return;
        }

        foreach($this->data as $k => $data) {
            Server::getInstance()->getDefaultLevel()->loadChunk($data['X'], $data['Z']);

            $data['id'] = $k;

            Server::getInstance()->getLogger()->info('olacomoestas');

            $tile = Server::getInstance()->getDefaultLevel()->getTile(new Vector3($data['X'], $data['Y'], $data['Z']));

            if($tile instanceof pocketSign) {
                new Sign($data, $tile);

                Server::getInstance()->getLogger()->info('añadiendo un cartel a la lista');
            } else {
                unset($this->data[$k]);

                file_put_contents(SkyWars::getInstance()->getDataFolder() . 'signs.json', json_encode($this->data, JSON_PRETTY_PRINT | JSON_BIGINT_AS_STRING), LOCK_EX);
            }
        }

        $size = count($this->getSignsAvailable());

        if($size > 0) {
            $i = 1;

            if($size > 0) {
                while($i <= $size) {
                    $sign = $this->getSign(array_rand($this->getSignsAvailable()));

                    if($sign instanceof Sign) {
                        $arena = SkyWars::getInstance()->getArenaManager()->createArena();

                        if($arena instanceof Arena) {
                            $arena->sign = $sign;

                            $sign->arena = $arena;
                        }
                    }

                    $i++;
                }
            }
        }

        new SignTask();
    }

    /**
     * @return Sign[]
     */
    public function getSignsAvailable(): array {
        $signs = [];

        foreach($this->signs as $sign) {
            if($sign instanceof Sign) {
                if(!$sign->hasArena()) {
                    $signs[$sign->getId()] = $sign;
                }
            }
        }

        return $signs;
    }

    /**
     * @param Sign $sign
     * @return Sign|null
     */
    public function addSign(Sign $sign): Sign {
        if(!isset($this->signs[$sign->getId()])) {
            $this->signs[$sign->getId()] = $sign;
            return $sign;
        }

        return null;
    }

    /**
     * @param int $id
     * @return Sign|null
     */
    public function getSign(int $id) {
        if(isset($this->signs[$id])) {
            return $this->signs[$id];
        }
        return null;
    }

    /**
     * @param Position $pos
     * @return Sign|null
     */
    public function getSignByPosition(Position $pos) {
        foreach($this->signs as $sign) {
            if($sign instanceof Sign) {
                if($sign->getSign() instanceof pocketSign) {
                    if(($sign->getSign()->x == $pos->x) and ($sign->getSign()->y == $pos->y) and ($sign->getSign()->z == $pos->z)) {
                        return $sign;
                    }
                }
            }
        }
        return null;
    }

    /**
     * @param Sign $sign
     */
    public function removeSign(Sign $sign) {
        if(isset($this->signs[$sign->getId()])) {
            unset($this->signs[$sign->getSign()->getId()]);
        }
    }

    /**
     * @return Sign[]
     */
    public function getSigns(): array {
        return $this->signs;
    }
}