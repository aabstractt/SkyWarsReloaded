<?php

namespace skywars\arena;

use pocketmine\block\Block;
use pocketmine\math\Vector3;
use pocketmine\scheduler\Task;
use pocketmine\Server;
use pocketmine\tile\Sign as pocketSign;
use pocketmine\tile\Tile;
use pocketmine\utils\TextFormat;
use skywars\SkyWars;
use UndefinedPropertyException;

class Sign {

    /** @var Task */
    public $task;

    /** @var pocketSign */
    protected $sign;

    /** @var array */
    public $data = [];

    /** @var Arena */
    public $arena = null;

    /**
     * Sign constructor.
     * @param array $data
     * @param Tile $sign
     */
    public function __construct(array $data, Tile $sign) {
        if(!$sign instanceof pocketSign) {
            $this->close(true);

            throw new UndefinedPropertyException('Receive bad operation');
        }

        $this->sign = $sign;

        if(isset($data['value'])) {
            $this->save();
        } else {
            $this->data = $data;
        }

        SkyWars::getInstance()->getSignManager()->addSign($this);
    }


    /**
     * @return int
     */
    public function getId(): int {
        return $this->data['id'];
    }

    public function save() {
        SkyWars::getInstance()->getSignManager()->data[] = ['X' => $this->sign->x, 'Y' => $this->sign->y, 'Z' => $this->sign->z];

        foreach(SkyWars::getInstance()->getSignManager()->data as $k => $v) {
            if($v['X'] == $this->sign->x and $v['Y'] == $this->sign->y and $v['Z'] == $this->sign->z) {
                $this->data = $v;

                $this->data['id'] = $k;
            }
        }

        file_put_contents(SkyWars::getInstance()->getDataFolder() . 'signs.json', json_encode(SkyWars::getInstance()->getSignManager()->data, JSON_PRETTY_PRINT | JSON_BIGINT_AS_STRING), LOCK_EX);
    }

    /**
     * @param bool $v
     */
    public function close(bool $v = false) {
        if($v) {
            unset(SkyWars::getInstance()->getSignManager()->data[$this->getId()]);

            file_put_contents(SkyWars::getInstance()->getDataFolder() . 'signs.json', json_encode(SkyWars::getInstance()->getSignManager()->data, JSON_PRETTY_PRINT | JSON_BIGINT_AS_STRING), LOCK_EX);
        }


        SkyWars::getInstance()->getSignManager()->removeSign($this);
    }

    /**
     * @return bool
     */
    public function hasArena(): bool {
        return $this->arena instanceof Arena;
    }

    /**
     * @return Arena|null
     */
    public function getArena() {
        return $this->arena;
    }

    /**
     * @return pocketSign|null
     */
    public function getSign() {
        return $this->sign;
    }

    /**
     * @param pocketSign $tile
     */
    public function setTile(pocketSign $tile) {
        $this->sign = $tile;
    }

    /**
     * @param Arena|null $arena
     */
    public function setArena($arena) {
        if($arena instanceof Arena) {
            $this->arena = $arena;
        } else {
            new class($this) extends Task {

                /** @var int */
                protected $time;

                /** @var Sign */
                protected $sign;

                /**
                 *  constructor.
                 * @param Sign $sign
                 */
                public function __construct(Sign $sign) {
                    $sign->task = $this;

                    $this->sign = $sign;

                    $this->time = rand(8, 10);

                    $this->setHandler(Server::getInstance()->getScheduler()->scheduleRepeatingTask($this, 20));
                }

                /**
                 * Actions to execute when run
                 *
                 * @param int $currentTick
                 *
                 * @return void
                 */
                public function onRun($currentTick) {
                    if($this->time > 0) {
                        $this->time--;
                    }

                    if($this->time == 6) {
                        if($this->sign->arena instanceof Arena) {
                            $this->sign->arena->sign = null;

                            $this->sign->arena = null;
                        }
                    }
                    if($this->time <= 0) {
                        if($this->sign instanceof Sign) {
                            $this->sign->task = null;

                            $arena = SkyWars::getInstance()->getArenaManager()->createArena();

                            if($arena instanceof Arena) {
                                $arena->sign = $this->sign;

                                $this->sign->arena = $arena;
                            }

                            $this->getHandler()->cancel();
                        }
                    }
                }
            };
        }
    }

    public function getStatusColor() {
        if($this->sign instanceof pocketSign) {
            if($this->hasArena()) {
                Server::getInstance()->getDefaultLevel()->loadChunk($this->getSign()->x, $this->getSign()->z);

                $this->sign->setText(TextFormat::BLACK . TextFormat::BOLD . 'SkyWars', $this->arena->getStatusColor(), $this->arena->getLevel()->getCustomName(), count($this->getArena()->getPlayers()) . '/' . $this->getArena()->getLevel()->getMaxSlots());
            } else {
                $this->sign->setText(TextFormat::DARK_PURPLE . '-------------', TextFormat::BLUE . 'SEARCHING', TextFormat::BLUE . 'FOR GAMES', TextFormat::DARK_PURPLE . '-------------');
            }
        }
    }

    public function getBlockStatus() {
        if($this->sign instanceof pocketSign) {
            $positions = [$this->sign->add(-1), $this->sign->add(+1), $this->sign->add(0, 0, -1), $this->sign->add(0, 0, +1)];

            $blocks = [20, 241, 236];

            $allow = false;

            foreach($positions as $pos) {
                if($pos instanceof Vector3) {
                    foreach($blocks as $block) {
                        if(Server::getInstance()->getDefaultLevel()->getBlock($pos)->getId() == $block) {
                            $allow = true;
                        }
                    }

                    if(!$allow) {
                        if(Server::getInstance()->getDefaultLevel()->getBlock($pos)->getId() == 241) {
                            $allow = true;
                        }
                        if(Server::getInstance()->getDefaultLevel()->getBlock($pos)->getId() == 20) {
                            $allow = true;
                        }
                    }

                    if($allow) {
                        if(Server::getInstance()->getDefaultLevel()->getBlock($pos)->getId() == 0) {
                            $allow = false;
                        }
                    }

                    if($allow) {
                        Server::getInstance()->getDefaultLevel()->setBlock($pos, ($this->hasArena() ? $this->arena->getStatusBlock() : Block::get(241)), true, true);
                    }
                }
            }
        }
    }
}