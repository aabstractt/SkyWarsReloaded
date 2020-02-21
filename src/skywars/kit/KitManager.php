<?php

namespace skywars\kit;

use pocketmine\utils\TextFormat;
use skywars\player\Player;
use skywars\SkyWars;

class KitManager {

    /** @var Kit[] */
    public $kits = [];

    public function __construct() {
        $data = [];

        if(file_exists(SkyWars::getInstance()->getDataFolder() . 'kits.json')) {
            $data = json_decode(file_get_contents(SkyWars::getInstance()->getDataFolder() . 'kits.json'), true);
        }

        foreach($data as $value) {
            $this->addKit(new Kit($value));
        }
    }

    /**
     * @param string $kitName
     * @return bool
     */
    public function isKit(string $kitName): bool {
        return isset($this->kits[strtolower($kitName)]);
    }

    /**
     * @param Kit $kit
     */
    public function addKit(Kit $kit) {
        $this->kits[strtolower($kit->getName())] = $kit;
    }

    public function save() {
        $data = file_exists(SkyWars::getInstance()->getDataFolder() . 'kits.json') ? json_decode(file_get_contents(SkyWars::getInstance()->getDataFolder() . 'kits.json'), true) : [];

        foreach($this->kits as $kit) {
            if(!isset($data[$kit->getName()])) {
                $data[$kit->getName()] = $kit->data;
            }
        }

        file_put_contents(SkyWars::getInstance()->getDataFolder() . 'kits.json', json_encode($data, JSON_PRETTY_PRINT | JSON_BIGINT_AS_STRING), LOCK_EX);
    }

    /**
     * @param Player $player
     * @return Kit[]
     */
    public function getKitsFromDatabaseByIPlayer(Player $player): array {
        $prepare = SkyWars::getInstance()->getConnection()->setTable('kits')->select(['*'], "WHERE username = '{$player->getInstance()->getName()}'");

        print_r($prepare->fetch_assoc());

        $kits = [];

        if($prepare->fetch_assoc() == null) {
            $player->sendMessage(TextFormat::RED . 'You don\'t have kits.');

            return [];
        }
        foreach($prepare->fetch_assoc() as $data) {
            if(!$this->isKit($data['kitName'])) {
                echo 'no es kit bro' . PHP_EOL;
                break;
            }

            $kits[strtolower($this->getKitByName($data['kitName'])->getName())] = $this->getKitByName($data['kitName']);
        }

        print_r($kits);
        return $kits;
    }

    /**
     * @param string $kitName
     * @return Kit
     */
    public function getKitByName(string $kitName): Kit {
        return $this->kits[strtolower($kitName)];
    }
}