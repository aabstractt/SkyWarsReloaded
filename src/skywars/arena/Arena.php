<?php

namespace skywars\arena;

use advancedserver\API;
use advancedserver\Player as pocketPlayer;
use Exception;
use pocketmine\block\Block;
use pocketmine\inventory\ChestInventory;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\Item;
use pocketmine\scheduler\Task;
use pocketmine\Server;
use pocketmine\tile\Chest;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use skywars\player\Player;
use skywars\SkyWars;
use skywars\task\GameTask;

class Arena {

    /** @var int */
    const LOBBY = 0, IN_GAME = 1, RESTARTING = 2;

    /** @var int */
    const NORMAL_ID = 0,
        OVERPOWERED_ID = 1;

    /** @var string */
    public $name;

    /** @var Task */
    public $handler = null;

    /** @var Level */
    protected $level;

    /** @var array */
    public $players = [];

    /** @var array */
    protected $slots = [];

    /** @var int */
    protected $status = Arena::LOBBY;

    /** @var int */
    public $lobbytime = 60;

    /** @var int */
    protected $gametime = 0;

    /** @var int */
    protected $endtime = 15;

    /** @var Sign */
    public $sign = null;

    /** @var array */
    public $chestVotes = [
        0 => [],
        1 => []
    ];

    /**
     * Arena constructor.
     * @param string $name
     * @param Level $level
     */
    public function __construct(string $name, Level $level) {
        $this->name = $name;

        $this->level = $level;

        $level->setArena($this)->load();

        for($i = 1; $i <= $level->getMaxSlots(); $i++) {
            $this->slots[] = $i;
        }
    }

    /**
     * @return string
     */
    public function getName(): string {
        return $this->name;
    }

    /**
     * @return Level
     */
    public function getLevel(): Level {
        return $this->level;
    }

    /**
     * @return bool
     */
    public function isFull(): bool {
        return count($this->getEveryone()) >= $this->level->getMaxSlots();
    }

    /**
     * @return string
     */
    public function getStatusColor(): string {
        if(($this->getLobbyTime() < 16 and $this->getStatus() == Arena::LOBBY) and !$this->isFull()){
            return TextFormat::GOLD . 'Starting';
        } else if($this->status == Arena::LOBBY) {
            return TextFormat::GREEN . 'Waiting';
        } else if($this->status == Arena::IN_GAME) {
            return TextFormat::RED . 'In-Game';
        } else if($this->isFull()) {
            return TextFormat::DARK_PURPLE . 'Full';
        }
        return TextFormat::DARK_AQUA . 'Restarting';
    }

    /**
     * @return Block
     */
    public function getStatusBlock(): Block {
        if(($this->getLobbyTime() < 16 and $this->getStatus() == Arena::LOBBY) and !$this->isFull()) {
            return Block::get(241, 1);
        } else if($this->status == Arena::LOBBY) {
            return Block::get(241, 5);
        } else if($this->status == Arena::IN_GAME) {
            return Block::get(241, 14);
        } else if($this->isFull()) {
            return Block::get(241, 10);
        } else if($this->status == Arena::RESTARTING) {
            return Block::get(241, 3);
        }

        return Block::get(241);
    }

    /**
     * @param Player $player
     * @throws Exception
     */
    public function join(Player $player) {
        $player->sendMessage(TextFormat::GREEN . 'Game found, sending you to ' . TextFormat::YELLOW . $this->getName() . TextFormat::GREEN . '.');

        $player->setSlot($this->getSlot());

        $player->getInstance()->teleport($this->level->getLevel()->getSafeSpawn());

        $player->getInstance()->teleport($this->getLevel()->getSpawnPosition($player->getSlot())->add(0.2, 0, 0.2));

        $player->defaultValues();

        if(!isset($this->getEveryone()[strtolower($player->getInstance()->getName())])) {
            $this->players[strtolower($player->getInstance()->getName())] = $player;
        }

        if(count($this->getEveryone()) == 1) {
            $this->run();
        }

        $message = 'Welcome to the game!';

        $submessage = 'Starting in ' . ($this->lobbytime >= 61 ? '60' : $this->lobbytime) . ' second(s)';

        $lensub = strlen($submessage);

        $lenmessage = strlen($message);

        $player->sendMessage(TextFormat::BLUE . str_repeat('-', ($lenmessage > $lensub ? $lenmessage : $lensub) + 2) . "\n" . TextFormat::YELLOW . $message . "\n" . TextFormat::YELLOW . $submessage . "\n" . TextFormat::BLUE . str_repeat('-', ($lenmessage > $lensub ? $lenmessage : $lensub) + 2));

        foreach($this->getEveryone() as $p) {
            if($p instanceof Player and $player->getInstance() instanceof pocketPlayer) {
                $p->sendMessage(TextFormat::GRAY . $player->getRank()->getDisplayName() . TextFormat::YELLOW . ' has joined. (' . TextFormat::AQUA . count($this->getEveryone()) . TextFormat::YELLOW . '/' . TextFormat::AQUA . $this->getLevel()->getMaxSlots() . TextFormat::YELLOW . ')!');
            }
        }

        API::addBossToPlayer($player->getInstance(), 920, TextFormat::YELLOW . TextFormat::BOLD . 'YOU ARE PLAYING ' . TextFormat::AQUA . 'SKYWARS' . TextFormat::YELLOW . ' ON CUBEHERO.US');

        API::setPercentage(100, 920);

        if($player->getInstance()->getParticleEffectName() != null) {
            Server::getInstance()->dispatchCommand($player->getInstance(), 'particles remove ' . $player->getName());
        }
    }

    /**
     * @param string $name
     */
    public function quit(string $name) {
        $player = $this->get($name);

        $this->remove($name);

        if($player instanceof Player and $player->getInstance() instanceof pocketPlayer) {
            if($this->status == self::LOBBY) {
                $this->slots[] = $player->getSlot();

                $this->removeVote($player->getName());

                $this->sendMessage(TextFormat::GRAY . $player->getRank()->getDisplayName() . TextFormat::YELLOW . ' has quit. (' . TextFormat::AQUA . (count($this->getEveryone()) - 1) . TextFormat::YELLOW . '/' . TextFormat::AQUA . $this->getLevel()->getMaxSlots() . TextFormat::YELLOW . ')!');
            } else if($this->status == self::IN_GAME) {
                if(!$player->isSpectating()) {
                    $killer = $player->getKiller();

                    if($killer instanceof Player) {
                        $this->sendMessage($player->getRank()->getDisplayName() . TextFormat::YELLOW . ' was slain by ' . TextFormat::AQUA . $killer->getRank()->getDisplayName());
                    } else {
                        $this->sendMessage($player->getRank()->getDisplayName() . TextFormat::YELLOW . ' was slain.');
                    }

                    $assistance = $player->getAssistance();

                    if($assistance instanceof Player and $assistance->getInstance() instanceof pocketPlayer) {
                        $assistance->sendMessage(TextFormat::WHITE . 'You have assisted killing ' . $player->getRank()->getDisplayName() . '!');
                    }

                    foreach($player->getInstance()->getDrops() as $drop) {
                        $this->getLevel()->getLevel()->dropItem($player->asPosition(), $drop);
                    }
                }
            }
            API::removeBoss([$player->getInstance()], 920);
        }
    }

    /**
     * @param string $name
     * @return Player|null
     */
    public function get(string $name) {
        if(isset($this->getEveryone()[strtolower($name)])) {
            return $this->getEveryone()[strtolower($name)];
        }
        return null;
    }

    /**
     * @param string $name
     */
    public function remove(string $name) {
        if(isset($this->getEveryone()[strtolower($name)])) {
            unset($this->players[strtolower($name)]);

            echo 'eliminando a ' . $name . PHP_EOL;

            if(isset($this->players[strtolower($name)])) {
                echo 'sigue en el array ¿what?' . PHP_EOL;
            }
        }
    }

    /**
     * @param string $message
     */
    public function sendMessage(string $message) {
        foreach($this->getEveryone() as $player) {
            $player->sendMessage($message);
        }
    }

    /**
     * @return Player[]
     */
    public function getPlayers(): array {
        $players = [];
        foreach($this->getEveryone() as $player) {
            if($player instanceof Player and $player->getInstance() instanceof pocketPlayer) {
                if(!$player->isSpectating()) {
                    if($player->getInstance()->getLevel() === $this->level->getLevel()) {
                        $players[strtolower($player->getName())] = $player;
                    } else {
                        unset($this->players[strtolower($player->getInstance()->getName())]);
                    }
                }
            }
        }
        return $players;
    }

    /**
     * @return Player[]
     */
    public function getSpectators(): array {
        $players = [];
        foreach($this->getEveryone() as $player) {
            if($player instanceof Player and $player->getInstance() instanceof pocketPlayer) {
                if($player->isSpectating()) {
                    if($player->getInstance()->getLevel() === $this->level->getLevel()) {
                        $players[strtolower($player->getName())] = $player;
                    } else {
                        unset($this->players[strtolower($player->getInstance()->getName())]);
                    }
                }
            }
        }
        return $players;
    }

    /**
     * @return Player[]
     */
    public function getEveryone(): array {
        $players = [];
        foreach($this->players as $player) {
            if($player instanceof Player and $player->getInstance() instanceof pocketPlayer) {
                if($player->getInstance()->getLevel() === $this->level->getLevel()) {
                    $players[strtolower($player->getName())] = $player;
                } else {
                    unset($this->players[strtolower($player->getInstance()->getName())]);
                }
            }
        }

        $this->players = $players;

        return $players;
    }

    /**
     * @return int
     */
    public function getSlot(): int {
        /*for($i = 1; $i <= $this->getLevel()->getMaxSlots(); $i++) {
            if(!isset($this->slots[$i]) and $player->getSlot() == 0) {
                $player->setSlot($i);

                $this->slots[$i] = $player;

                break;
            }
        }*/

        return array_shift($this->slots);
    }

    /**
     * @return bool
     */
    public function run(): bool {
        $this->handler = new GameTask($this);
        return $this->handler == null;
    }

    /**
     * @param int $v
     */
    public function setStatus(int $v) {
        $this->status = $v;
    }

    /**
     * @return int
     */
    public function getStatus(): int {
        return $this->status;
    }

    /**
     * @param int $v
     */
    public function setLobbyTime(int $v) {
        $this->lobbytime = $v;
    }

    /**
     * @return int
     */
    public function getLobbyTime(): int {
        return $this->lobbytime;
    }

    /**
     * @param int $v
     */
    public function setGameTime(int $v) {
        $this->gametime = $v;
    }

    /**
     * @return int
     */
    public function getGameTime(): int {
        return $this->gametime;
    }

    /**
     * @param int $v
     */
    public function setEndTime(int $v) {
        $this->endtime = $v;
    }

    /**
     * @return int
     */
    public function getEndTime(): int {
        return $this->endtime;
    }

    /**
     * @param int $int
     * @return string
     */
    public function timeString(int $int): string {
        $m = floor($int / 60);

        $s = floor($int % 60);

        return (($m < 10 ? "0" : "") . $m . ":" . ($s < 10 ? "0" : "") . $s);
    }

    public function tick() {
        foreach($this->getEveryone() as $player) {
            if($player instanceof Player and $player->getInstance() instanceof pocketPlayer) {
                if($this->status == Arena::LOBBY) {
                    if(count($this->getEveryone()) > 1) {
                        $player->getInstance()->sendTip(TextFormat::YELLOW . 'The game starts in ' . TextFormat::RED . $this->timeString($this->lobbytime) . TextFormat::YELLOW . ' seconds');
                    } else {
                        $player->getInstance()->sendTip(TextFormat::YELLOW . 'More players are needed');
                    }
                } else if($this->status == Arena::IN_GAME) {
                    $player->getInstance()->sendTip(TextFormat::YELLOW . 'There are ' . TextFormat::RED . count($this->getPlayers()) . TextFormat::YELLOW . ' players remaining!' . TextFormat::GRAY . ' - ' . TextFormat::GOLD . $this->timeString($this->gametime));
                }
            }
        }
        if($this->status == Arena::LOBBY) {
            if($this->level->getLevel() == null) {
                $this->setStatus(Arena::RESTARTING);
            }

            if(count($this->getEveryone()) > 1) {
                $this->setLobbyTime($this->getLobbyTime() - 1);
            } else if(count($this->getEveryone()) < 2 and $this->getLobbyTime() != 61) {
                $this->setLobbyTime(61);
            } else if(count($this->getEveryone()) == 0 and $this->handler instanceof GameTask) {
                $this->handler->cancel();

                $this->handler = null;
            }
            if($this->isFull() and $this->getLobbyTime() > 15) {
                $this->sendMessage(TextFormat::GOLD . '¡Server is now ' . TextFormat::BOLD . 'FULL' . TextFormat::RESET . TextFormat::GOLD . '! ¡Starting game in ' . TextFormat::YELLOW . '15 seconds' . TextFormat::GOLD . '!');

                $this->lobbytime = 15;
            }
            if($this->lobbytime == 30 || $this->lobbytime == 20 || $this->lobbytime == 10 || ($this->lobbytime > 0 and $this->lobbytime < 6)) {
                foreach($this->getEveryone() as $player) {
                    if($player instanceof Player and $player->getInstance() instanceof pocketPlayer) {
                        $player->getInstance()->setTitle($this->lobbytime, 'The game starts in', 3);
                    }
                }

                $this->sendMessage(TextFormat::YELLOW . 'The game starts in ' . TextFormat::RED . $this->lobbytime . TextFormat::YELLOW . ' seconds!');
            }
            if($this->lobbytime == 0) {
                $this->setStatus(Arena::IN_GAME);
                foreach($this->getEveryone() as $player) {
                    if($player instanceof Player and $player->getInstance() instanceof pocketPlayer) {
                        $player->getInventory()->clearAll();
                    }
                }

                $this->refill();

                $this->chestVotes = [];

                $this->sendMessage(TextFormat::GREEN . 'The game has started.');
            }
        }
        if($this->status == Arena::IN_GAME) {
            $this->setGameTime(($this->getGameTime() + 1));

            if($this->getGameTime() == 5) {
                if($this->sign instanceof Sign) {
                    $this->sign->setArena(null);
                }

                $this->sendMessage(TextFormat::YELLOW . '¡PVP IS NOW AVAILABLE!');
            }

            if(count($this->getEveryone()) == 0 or count($this->getPlayers()) == 0) {
                $this->setStatus(Arena::RESTARTING);
            }

            if(count($this->getPlayers()) == 1) {
                $player = array_values($this->getPlayers())[0];

                if($player instanceof Player) {
                    $this->sendMessage(TextFormat::GREEN . TextFormat::BOLD . "=======================================" . TextFormat::RESET . "\n\n" . TextFormat::GRAY . "Winner - " . $player->getRank()->getDisplayName() . TextFormat::RESET . "\n\n" . TextFormat::GREEN . TextFormat::BOLD . "=======================================");

                    Server::getInstance()->broadcastMessage($player->getRank()->getDisplayName() . TextFormat::GOLD . ' won in the arena ' . TextFormat::YELLOW . $this->level->getCustomName() . TextFormat::GOLD . ' of SkyWars');
                }

                $this->setStatus(Arena::RESTARTING);
            }
        }
        if($this->status == Arena::RESTARTING) {
            $this->setEndTime(($this->getEndTime() - 1));

            if($this->getEndTime() == 10) {
                if($this->sign instanceof Sign) {
                    $this->sign->setArena(null);
                }

                foreach($this->getLevel()->getLevel()->getPlayers() as $player) {
                    try {
                        Server::getInstance()->dispatchCommand($player, 'hub');
                    } catch(Exception $e) {
                        Server::getInstance()->getLogger()->logException($e);
                    }
                }

                foreach($this->getEveryone() as $player) {
                    if($player instanceof Player and $player->getInstance() instanceof pocketPlayer) {
                        API::removeBoss([$player->getInstance()], 920);
                        try {
                            Server::getInstance()->dispatchCommand($player->getInstance(), 'hub');
                        } catch(Exception $e) {
                            Server::getInstance()->getLogger()->logException($e);
                        }
                    }
                }
            } else if($this->getEndTime() == 7) {
                Server::getInstance()->unloadLevel($this->level->getLevel());
            }

            if($this->getEndTime() <= 0) {
                $this->getLevel()->close();
            }
        }
    }

    public function refill() {
        $config = new Config(SkyWars::getInstance()->getDataFolder() . "items.yml", Config::YAML);

        if(count($this->chestVotes[0]) == 0 and count($this->chestVotes[1]) == 0) {
            $this->sendMessage(TextFormat::YELLOW . 'Selected ' . TextFormat::GREEN . TextFormat::BOLD . 'Normal Chest Items' . TextFormat::RESET . TextFormat::YELLOW . '! ' . TextFormat::GRAY . 'Selected due to no votes.');

            $data = $config->get('normal');
        } else if(count($this->chestVotes[Arena::OVERPOWERED_ID]) > count($this->chestVotes[Arena::NORMAL_ID])) {
            $this->sendMessage(TextFormat::YELLOW . 'Selected ' . TextFormat::GREEN . TextFormat::BOLD . 'Overpowered Chest Items' . TextFormat::RESET . TextFormat::YELLOW . '!');

            $data = $config->get('overpowered');
        } else {
            $this->sendMessage(TextFormat::YELLOW . 'Selected ' . TextFormat::GREEN . TextFormat::BOLD . 'Normal Chest Items' . TextFormat::RESET . TextFormat::YELLOW . '!');

            $data = $config->get('normal');
        }

        foreach($this->level->getLevel()->getTiles() as $chest) {
            if($chest instanceof Chest) {
                $chest->getInventory()->clearAll();

                if($chest->getInventory() instanceof ChestInventory) {
                    for($i = 0; $i <= 26; $i++) {
                        if(rand(1, 3) == 1) {
                            $v = $data[array_rand($data)];

                            $item = Item::get($v['id'], (isset($v['meta']) ? $v['meta'] : 0), (isset($v['count']) ? $v['count'] : rand(10, 25)));

                            if(isset($v['enchantments'])) {
                                $enchantments = $v['enchantments'];

                                foreach($enchantments as $k => $v) {
                                    if(is_string($v['id'])) {
                                        $enchantment = Enchantment::getEffectByName($v['id']);
                                    } else {
                                        $enchantment = Enchantment::getEnchantment($v['id']);

                                    }

                                    if($enchantment instanceof Enchantment) {
                                        if(isset($v['level'])) $enchantment->setLevel($v['level']);

                                        $item->addEnchantment($enchantment);
                                    }
                                }
                            }

                            if(isset($v['name'])) {
                                $item->setCustomName($v['name']);
                            }

                            $chest->getInventory()->setItem($i, $item);
                        }
                    }
                }
            }
        }
    }

    /**
     * @param int $type
     * @param string $name
     * @return bool
     */
    public function addChestVote(int $type, string $name): bool {
        if($this->isAlreadyVote($type, $name)) {
            return false;
        }

        $this->removeVote($name);

        $this->chestVotes[$type][] = strtolower($name);

        return true;
    }

    /**
     * @param int $type
     * @param string $name
     * @return bool
     */
    public function isAlreadyVote(int $type, string $name): bool {
        if(!isset($this->chestVotes[$type])) {
            return false;
        }

        foreach($this->chestVotes[$type] as $v) {
            if(strtolower($name) == $v) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param string $name
     */
    public function removeVote(string $name) {
        foreach($this->chestVotes as $index => $data) {
            foreach($data as $k => $v) {
                if($v === strtolower($name)) {
                    unset($this->chestVotes[$index][$k]);
                }
            }
        }
    }
}