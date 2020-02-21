<?php

namespace skywars\kit;

use pocketmine\inventory\PlayerInventory;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\Item;
use skywars\player\Player;

class Kit {

    /** @var array */
    public $data;

    /**
     * Kit constructor.
     * @param array $data
     */
    public function __construct(array $data) {
        $this->data = $data;
    }

    /**
     * @param int $cost
     */
    public function setCost(int $cost) {
        $this->data['cost'] = $cost;
    }

    /**
     * @param string $name
     */
    public function setName(string $name) {
        $this->data['name'] = $name;
    }

    /**
     * @param Player $player
     * @return bool
     */
    public function canBuyIt(Player $player): bool {
        return $player->getCoins() >= $this->getCost();
    }

    /**
     * @return int
     */
    public function getCost(): int {
        return $this->data['cost'];
    }

    /**
     * @param Player $player
     * @return bool
     */
    public function isAuthorized(Player $player) {
        foreach($player->getKits() as $kit) {
            if($kit->getName() == $this->getName()) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return string
     */
    public function getName(): string {
        return $this->data['name'];
    }

    /**
     * @param PlayerInventory $inv
     */
    public function setArmorInventory(PlayerInventory $inv) {
        $contents = [];

        foreach($inv->getArmorContents() as $index => $armorContent) {
            $data = [[$armorContent->getId(), $armorContent->getDamage()]];

            if($armorContent->hasEnchantments()) {
                $enchantments = [];

                foreach($armorContent->getEnchantments() as $enchantment) {
                    $enchantments[] = [$enchantment->getId(), $enchantment->getLevel()];
                }

                $data[1] = $enchantments;
            }
            $contents[$index] = $data;
        }

        $this->data['inventory']['armor'] = $contents;
    }

    /**
     * @param PlayerInventory $inv
     */
    public function setInventory(PlayerInventory $inv) {
        $contents = [];

        foreach($inv->getContents() as $index => $content) {
            if($index < 36) {
                $data = [[$content->getId(), $content->getDamage()]];

                if($content->hasEnchantments()) {
                    $enchantments = [];

                    foreach($content->getEnchantments() as $enchantment) {
                        $enchantments[] = [$enchantment->getId(), $enchantment->getLevel()];
                    }
                    $data[1] = $enchantments;
                }
                $contents[$index] = $data;
            }
        }

        $this->data['inventory']['contents'] = $contents;
    }

    /**
     * @return bool
     */
    public function isAvailable(): bool {
        return $this->data['available'];
    }

    /**
     * @param Player $player
     */
    public function giveInventory(Player $player) {
        $player->getInventory()->clearAll();

        foreach($this->data['inventory']['contents'] as $content) {
            foreach($content as $k => $v) {
                if(isset($v[0])) {
                    $item = Item::get($v[0][0], $v[0][1], $v[0][2]);

                    if(isset($v[1])) {
                        $item->addEnchantment((Enchantment::getEnchantment($v[1][0]))->setLevel($v[1][1]));
                    }

                    $player->getInventory()->setItem($k, $item);
                }
            }
        }
        foreach($this->data['inventory']['armor'] as $content) {
            foreach($content as $k => $v) {
                if(isset($v[0])) {
                    $item = Item::get($v[0][0], $v[0][1], isset($v[0][2]) ? $v[0][2] : 1);

                    if(isset($v[1])) {
                        $item->addEnchantment((Enchantment::getEnchantment($v[1][0]))->setLevel($v[1][1]));
                    }

                    $player->getInventory()->setItem($k, $item);
                }
            }
        }

        $player->getInstance()->sendContents();
    }
}