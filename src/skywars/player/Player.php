<?php

namespace skywars\player;

use advancedserver\ConnectionException;
use skywars\kit\Kit;
use advancedserver\Main;
use advancedserver\permission\Rank;
use pocketmine\item\Item;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use skywars\SkyWars;

class Player extends PlayerBase {

    /** @var array */
    protected $killer = [];

    /** @var array */
    protected $assistance = [];

    /** @var int */
    private $coins = 0;

    /** @var Kit */
    private $kit;

    /**
     * Player constructor.
     * @param array $data
     * @throws \Exception
     */
    public function __construct(array $data){
        parent::__construct($data);

        $connection = SkyWars::getInstance()->getConnection();

        if($connection->setTable('kits')->select(['*'], "WHERE username = '{$this->getName()}'")->fetch_assoc() == null) {
            $connection->setTable('kits')->insert(['username' => $this->getName(), 'kitName' => 'default']);
        }

        if($connection->setTable('players')->select(['*'], "WHERE username = '{$this->getName()}'")->fetch_assoc() == null) {
            $connection->setTable('players')->insert(['username' => $this->getName(), 'kit' => 'default', 'cage' => 'default']);
        }

        $data = $connection->setTable('players')->select(['*'], "WHERE username = '{$this->getName()}'")->fetch_assoc();

        $this->coins = $data['coins'];

        if(SkyWars::getInstance()->getKitManager()->isKit($data['kit'])) {
            $this->setKit(SkyWars::getInstance()->getKitManager()->getKitByName($data['kit']));
        }

        $this->getArena()->join($this);
    }

    /**
     * @return PlayerBase|null
     */
    public function getKiller(){
        $data = $this->killer;

        return isset($data['time']) ? (time() - $data['time']) > 10 ? null : $this->getArena()->get($data['name']) : null;
    }

    /**
     * @return PlayerBase|null
     */
    public function getAssistance(){
        $data = $this->assistance;

        return isset($data['time']) ? (time() - $data['time']) > 15 ? null : $this->getArena()->get($data['name']) : null;
    }

    /**
     * @param string $name
     */
    public function attack(string $name){
        if(isset($this->killer['name'])){
            if(strtolower($this->killer['name']) != strtolower($name) and $this->getKiller() != null) {
                $this->assistance = $this->killer;
            }
        }

        $this->killer = ['name' => $name, 'time' => time()];
    }

    public function defaultValues(){
        $this->setGamemode(0);

        $this->getInventory()->setHeldItemSlot(4);

        $this->getInventory()->setHeldItemIndex(4);

        $this->getInstance()->sendContents();

        $this->getInstance()->setAllowFlight(false);

        $this->getInventory()->clearAll();

        $this->getInventory()->setItem(0, Item::get(Item::EMPTY_MAP)->setCustomName(TextFormat::RESET . TextFormat::GREEN . 'Voting'));

        $this->getInventory()->setItem(1, Item::get(Item::FILLED_MAP)->setCustomName(TextFormat::RESET . TextFormat::YELLOW . 'Kit Selector'));
    }

    /**
     * @return Kit|null
     */
    public function getKit() {
        return $this->kit;
    }

    /**
     * @return Kit[]
     */
    public function getKits(): array {
        return SkyWars::getInstance()->getKitManager()->getKitsFromDatabaseByIPlayer($this);
    }

    /**
     * @param Kit $kit
     */
    public function setKit(Kit $kit){
        $this->kit = $kit;

        try {
            SkyWars::getInstance()->getConnection()->setTable('players')->update(['kitName' => $kit->getName()]);
        } catch(ConnectionException $e) {
            Server::getInstance()->getLogger()->logException($e);

            Main::getInstance()->alertDevelopment($e->getMessage());
        }
    }

    /**
     * @param Kit $kit
     */
    public function addKitBuy(Kit $kit){
        SkyWars::getInstance()->getConnection()->setTable('kits')->insert(['username' => $this->getName(), 'kitName' => $kit->getName()]);

        $this->decreaseCoins($kit->getCost());
    }

    /**
     * @return Rank
     */
    public function getRank(): Rank {
        return $this->getInstance()->getRank();
    }

    /**
     * @return int
     */
    public function getCoins(): int {
        return $this->coins;
    }

    /**
     * @param int $coins
     */
    public function decreaseCoins(int $coins) {
        $this->coins -= $coins;
    }

    /**
     * @param int $coins
     */
    public function increaseCoins(int $coins) {
        $this->coins += $coins;
    }
}