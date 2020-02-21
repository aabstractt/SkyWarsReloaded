<?php

namespace skywars;

use pocketmine\item\enchantment\Enchantment;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use skywars\arena\Arena;
use skywars\command\SkyWarsCommand;
use skywars\kit\KitManager;
use skywars\player\Configurator;

class SkyWars extends PluginBase {

    /** @var SkyWars */
    protected static $instance;

    /** @var ArenaManager */
    protected $arenaManager;

    /** @var LevelManager */
    protected $levelManager;

    /** @var SignManager */
    protected $signManager;

    /** @var KitManager */
    private $kitManager;

    /** @var Configurator[] */
    public $configurators = [];

    public function onEnable() {
        static::$instance = $this;

        @mkdir($this->getDataFolder());

        @mkdir(SkyWars::getInstance()->getDataFolder() . 'backup' . DIRECTORY_SEPARATOR);

        $config = new Config($this->getDataFolder() . 'config.yml', Config::YAML);

        if(!$config->exists('mysql')) {
            $config->set('mysql', [
                'host' => '127.0.0.1',
                'port' => 3306,
                'username' => 'root',
                'password' => 'password01',
                'dbname' => 'SkyWars'
            ]);
        }

        if(!$config->exists('coins')) {
            $config->set('coins', [
                'cause_void' => 5,

                'cause_attack' => 3,

                'cause_projectile' => 4,

                'cause_explosive' => 2,

                'cause_suffocation' => 1,

                'cause_lava' => 1,

                'cause_fire' => 1
            ]);
        }

        $config->save();

        $items = new Config($this->getDataFolder() . 'items.yml');

        $items->set('normal', [
            ['id' => 1, 'count' => 20],
            ['id' => 3, 'count' => 16],
            ['id' => 4, 'count' => 25],
            ['id' => 5, 'count' => 12],
            ['id' => 1, 'count' => 9],
            ['id' => 3, 'count' => 5],
            ['id' => 4, 'count' => 6],
            ['id' => 5, 'count' => 20],
            ['id' => 302, 'count' => 1, 'enchantments' => [['id' => Enchantment::TYPE_ARMOR_PROTECTION, 'level' => 1]]],
            ['id' => 303, 'count' => 1, 'enchantments' => [['id' => Enchantment::TYPE_ARMOR_FIRE_PROTECTION], 'level' => 1]],
            ['id' => 304, 'count' => 1, 'enchantments' => [['id' => Enchantment::TYPE_ARMOR_PROJECTILE_PROTECTION, 'level' => 1]]],
            ['id' => 305, 'count' => 1],
            ['id' => 306, 'count' => 1, 'enchantments' => [['id' => Enchantment::TYPE_ARMOR_PROTECTION, 'level' => 1]]],
            ['id' => 306, 'count' => 1, 'enchantments' => [['id' => Enchantment::TYPE_ARMOR_FIRE_PROTECTION], 'level' => 1]],
            ['id' => 306, 'count' => 1, 'enchantments' => [['id' => Enchantment::TYPE_ARMOR_PROJECTILE_PROTECTION, 'level' => 1]]],
            ['id' => 306, 'count' => 1],
            ['id' => 307, 'count' => 1, 'enchantments' => [['id' => Enchantment::TYPE_ARMOR_PROTECTION, 'level' => 1]]],
            ['id' => 307, 'count' => 1, 'enchantments' => [['id' => Enchantment::TYPE_ARMOR_FIRE_PROTECTION], 'level' => 1]],
            ['id' => 307, 'count' => 1, 'enchantments' => [['id' => Enchantment::TYPE_ARMOR_PROJECTILE_PROTECTION, 'level' => 1]]],
            ['id' => 307, 'count' => 1],
            ['id' => 308, 'count' => 1, 'enchantments' => [['id' => Enchantment::TYPE_ARMOR_PROTECTION, 'level' => 1]]],
            ['id' => 308, 'count' => 1, 'enchantments' => [['id' => Enchantment::TYPE_ARMOR_FIRE_PROTECTION], 'level' => 1]],
            ['id' => 308, 'count' => 1, 'enchantments' => [['id' => Enchantment::TYPE_ARMOR_PROJECTILE_PROTECTION, 'level' => 1]]],
            ['id' => 308, 'count' => 1],
            ['id' => 309, 'count' => 1, 'enchantments' => [['id' => Enchantment::TYPE_ARMOR_PROTECTION, 'level' => 1]]],
            ['id' => 309, 'count' => 1, 'enchantments' => [['id' => Enchantment::TYPE_ARMOR_FIRE_PROTECTION], 'level' => 1]],
            ['id' => 309, 'count' => 1, 'enchantments' => [['id' => Enchantment::TYPE_ARMOR_PROJECTILE_PROTECTION, 'level' => 1]]],
            ['id' => 309, 'count' => 1],
            ['id' => 310, 'count' => 1],
            ['id' => 311, 'count' => 1],
            ['id' => 312, 'count' => 1],
            ['id' => 313, 'count' => 1],
            ['id' => 267, 'count' => 1, 'enchantments' => [['id' => Enchantment::TYPE_WEAPON_SHARPNESS, 'level' => 1]]],
            ['id' => 267, 'count' => 1, 'enchantments' => [['id' => Enchantment::TYPE_WEAPON_SHARPNESS, 'level' => 2]]],
            ['id' => 267, 'count' => 1],
            ['id' => 276, 'count' => 1, 'enchantments' => [['id' => Enchantment::TYPE_WEAPON_SHARPNESS, 'level' => 1]]],
            ['id' => 276, 'count' => 1],
            ['id' => 272, 'count' => 1, 'enchantments' => [['id' => Enchantment::TYPE_WEAPON_SHARPNESS, 'level' => 1]]],
            ['id' => 272, 'count' => 1, 'enchantments' => [['id' => Enchantment::TYPE_WEAPON_SHARPNESS, 'level' => 2]]],
            ['id' => 272, 'count' => 1],
            ['id' => 261, 'count' => 1, 'enchantments' => [['id' => Enchantment::TYPE_BOW_POWER, 'level' => 1]]],
            ['id' => 261, 'count' => 1],
            ['id' => 262, 'count' => 15],
            ['id' => 262, 'count' => 7],
            ['id' => 322, 'count' => 1],
            ['id' => 344, 'count' => 8],
            ['id' => 344, 'count' => 16],
            ['id' => 345, 'count' => 1, 'name' => TextFormat::GOLD . 'Player tracker'],
            ['id' => 364, 'count' => 15],
            ['id' => 364, 'count' => 15],
            ['id' => 364, 'count' => 15],
            ['id' => 364, 'count' => 15]
        ]);

        $items->set('overpowered', [
            ['id' => 1, 'count' => 20],
            ['id' => 3, 'count' => 16],
            ['id' => 4, 'count' => 25],
            ['id' => 5, 'count' => 12],
            ['id' => 1, 'count' => 9],
            ['id' => 3, 'count' => 7],
            ['id' => 4, 'count' => 10],
            ['id' => 5, 'count' => 20],
            ['id' => 1, 'count' => 20],
            ['id' => 3, 'count' => 16],
            ['id' => 4, 'count' => 25],
            ['id' => 5, 'count' => 12],
            ['id' => 1, 'count' => 9],
            ['id' => 3, 'count' => 7],
            ['id' => 4, 'count' => 10],
            ['id' => 5, 'count' => 20],

            ['id' => 302, 'count' => 1, 'enchantments' => [['id' => Enchantment::TYPE_ARMOR_PROTECTION, 'level' => 1]]],
            ['id' => 303, 'count' => 1, 'enchantments' => [['id' => Enchantment::TYPE_ARMOR_FIRE_PROTECTION, 'level' => 1]]],
            ['id' => 304, 'count' => 1, 'enchantments' => [['id' => Enchantment::TYPE_ARMOR_PROJECTILE_PROTECTION, 'level' => 1]]],
            ['id' => 305, 'count' => 1],
            ['id' => 305, 'count' => 1, 'enchantments' => [['id' => Enchantment::TYPE_ARMOR_PROTECTION, 'level' => 2]]],
            ['id' => 305, 'count' => 1, 'enchantments' => [['id' => Enchantment::TYPE_ARMOR_FIRE_PROTECTION, 'level' => 2]]],
            ['id' => 305, 'count' => 1, 'enchantments' => [['id' => Enchantment::TYPE_ARMOR_PROJECTILE_PROTECTION, 'level' => 2]]],

            ['id' => 306, 'count' => 1, 'enchantments' => [['id' => Enchantment::TYPE_ARMOR_PROTECTION, 'level' => 1]]],
            ['id' => 306, 'count' => 1, 'enchantments' => [['id' => Enchantment::TYPE_ARMOR_FIRE_PROTECTION, 'level' => 1]]],
            ['id' => 306, 'count' => 1, 'enchantments' => [['id' => Enchantment::TYPE_ARMOR_PROJECTILE_PROTECTION, 'level' => 1]]],
            ['id' => 306, 'count' => 1],
            ['id' => 306, 'count' => 1, 'enchantments' => [['id' => Enchantment::TYPE_ARMOR_PROTECTION, 'level' => 2]]],
            ['id' => 306, 'count' => 1, 'enchantments' => [['id' => Enchantment::TYPE_ARMOR_FIRE_PROTECTION, 'level' => 2]]],
            ['id' => 306, 'count' => 1, 'enchantments' => [['id' => Enchantment::TYPE_ARMOR_PROJECTILE_PROTECTION, 'level' => 2]]],

            ['id' => 307, 'count' => 1, 'enchantments' => [['id' => Enchantment::TYPE_ARMOR_PROTECTION, 'level' => 1]]],
            ['id' => 307, 'count' => 1, 'enchantments' => [['id' => Enchantment::TYPE_ARMOR_FIRE_PROTECTION, 'level' => 1]]],
            ['id' => 307, 'count' => 1, 'enchantments' => [['id' => Enchantment::TYPE_ARMOR_PROJECTILE_PROTECTION, 'level' => 1]]],
            ['id' => 307, 'count' => 1, 'enchantments' => [['id' => Enchantment::TYPE_ARMOR_PROTECTION, 'level' => 2]]],
            ['id' => 307, 'count' => 1, 'enchantments' => [['id' => Enchantment::TYPE_ARMOR_FIRE_PROTECTION, 'level' => 2]]],
            ['id' => 307, 'count' => 1, 'enchantments' => [['id' => Enchantment::TYPE_ARMOR_PROJECTILE_PROTECTION, 'level' => 2]]],
            ['id' => 307, 'count' => 1],

            ['id' => 308, 'count' => 1, 'enchantments' => [['id' => Enchantment::TYPE_ARMOR_PROTECTION, 'level' => 1]]],
            ['id' => 308, 'count' => 1, 'enchantments' => [['id' => Enchantment::TYPE_ARMOR_FIRE_PROTECTION, 'level' => 1]]],
            ['id' => 308, 'count' => 1, 'enchantments' => [['id' => Enchantment::TYPE_ARMOR_PROJECTILE_PROTECTION, 'level' => 1]]],
            ['id' => 308, 'count' => 1, 'enchantments' => [['id' => Enchantment::TYPE_ARMOR_PROTECTION, 'level' => 1]]],
            ['id' => 308, 'count' => 1, 'enchantments' => [['id' => Enchantment::TYPE_ARMOR_FIRE_PROTECTION, 'level' => 1]]],
            ['id' => 308, 'count' => 1, 'enchantments' => [['id' => Enchantment::TYPE_ARMOR_PROJECTILE_PROTECTION, 'level' => 1]]],
            ['id' => 308, 'count' => 1],

            ['id' => 309, 'count' => 1, 'enchantments' => [['id' => Enchantment::TYPE_ARMOR_PROTECTION, 'level' => 1]]],
            ['id' => 309, 'count' => 1, 'enchantments' => [['id' => Enchantment::TYPE_ARMOR_PROTECTION, 'level' => 2]]],
            ['id' => 309, 'count' => 1, 'enchantments' => [['id' => Enchantment::TYPE_ARMOR_FIRE_PROTECTION, 'level' => 1]]],
            ['id' => 309, 'count' => 1, 'enchantments' => [['id' => Enchantment::TYPE_ARMOR_FIRE_PROTECTION, 'level' => 2]]],
            ['id' => 309, 'count' => 1, 'enchantments' => [['id' => Enchantment::TYPE_ARMOR_FIRE_PROTECTION, 'level' => 3]]],
            ['id' => 309, 'count' => 1, 'enchantments' => [['id' => Enchantment::TYPE_ARMOR_PROJECTILE_PROTECTION, 'level' => 1]]],

            ['id' => 309, 'count' => 1],
            ['id' => 309, 'count' => 1, 'enchantments' => [['id' => Enchantment::TYPE_ARMOR_PROTECTION, 'level' => 1]]],
            ['id' => 309, 'count' => 1, 'enchantments' => [['id' => Enchantment::TYPE_ARMOR_FIRE_PROTECTION, 'level' => 1]]],
            ['id' => 309, 'count' => 1, 'enchantments' => [['id' => Enchantment::TYPE_ARMOR_PROJECTILE_PROTECTION, 'level' => 1]]],
            ['id' => 309, 'count' => 1, 'enchantments' => [['id' => Enchantment::TYPE_ARMOR_PROTECTION, 'level' => 2]]],
            ['id' => 309, 'count' => 1, 'enchantments' => [['id' => Enchantment::TYPE_ARMOR_FIRE_PROTECTION, 'level' => 2]]],
            ['id' => 309, 'count' => 1, 'enchantments' => [['id' => Enchantment::TYPE_ARMOR_PROJECTILE_PROTECTION, 'level' => 2]]],

            ['id' => 310, 'count' => 1],
            ['id' => 310, 'count' => 1, 'enchantments' => [['id' => Enchantment::TYPE_ARMOR_PROTECTION, 'level' => 1]]],
            ['id' => 310, 'count' => 1, 'enchantments' => [['id' => Enchantment::TYPE_ARMOR_FIRE_PROTECTION, 'level' => 1]]],
            ['id' => 310, 'count' => 1, 'enchantments' => [['id' => Enchantment::TYPE_ARMOR_PROJECTILE_PROTECTION, 'level' => 1]]],
            ['id' => 310, 'count' => 1, 'enchantments' => [['id' => Enchantment::TYPE_ARMOR_PROTECTION, 'level' => 2]]],
            ['id' => 310, 'count' => 1, 'enchantments' => [['id' => Enchantment::TYPE_ARMOR_FIRE_PROTECTION, 'level' => 2]]],
            ['id' => 310, 'count' => 1, 'enchantments' => [['id' => Enchantment::TYPE_ARMOR_PROJECTILE_PROTECTION, 'level' => 2]]],

            ['id' => 311, 'count' => 1],
            ['id' => 311, 'count' => 1, 'enchantments' => [['id' => Enchantment::TYPE_ARMOR_PROTECTION, 'level' => 1]]],
            ['id' => 311, 'count' => 1, 'enchantments' => [['id' => Enchantment::TYPE_ARMOR_FIRE_PROTECTION, 'level' => 1]]],
            ['id' => 311, 'count' => 1, 'enchantments' => [['id' => Enchantment::TYPE_ARMOR_PROJECTILE_PROTECTION, 'level' => 1]]],
            ['id' => 311, 'count' => 1, 'enchantments' => [['id' => Enchantment::TYPE_ARMOR_PROTECTION, 'level' => 2]]],
            ['id' => 311, 'count' => 1, 'enchantments' => [['id' => Enchantment::TYPE_ARMOR_FIRE_PROTECTION, 'level' => 2]]],
            ['id' => 311, 'count' => 1, 'enchantments' => [['id' => Enchantment::TYPE_ARMOR_PROJECTILE_PROTECTION, 'level' => 2]]],

            ['id' => 312, 'count' => 1],
            ['id' => 312, 'count' => 1, 'enchantments' => [['id' => Enchantment::TYPE_ARMOR_PROTECTION, 'level' => 1]]],
            ['id' => 312, 'count' => 1, 'enchantments' => [['id' => Enchantment::TYPE_ARMOR_FIRE_PROTECTION, 'level' => 1]]],
            ['id' => 312, 'count' => 1, 'enchantments' => [['id' => Enchantment::TYPE_ARMOR_PROJECTILE_PROTECTION, 'level' => 1]]],
            ['id' => 312, 'count' => 1, 'enchantments' => [['id' => Enchantment::TYPE_ARMOR_PROTECTION, 'level' => 2]]],
            ['id' => 312, 'count' => 1, 'enchantments' => [['id' => Enchantment::TYPE_ARMOR_FIRE_PROTECTION, 'level' => 2]]],
            ['id' => 312, 'count' => 1, 'enchantments' => [['id' => Enchantment::TYPE_ARMOR_PROJECTILE_PROTECTION, 'level' => 2]]],

            ['id' => 313, 'count' => 1],
            ['id' => 313, 'count' => 1, 'enchantments' => [['id' => Enchantment::TYPE_ARMOR_PROTECTION, 'level' => 1]]],
            ['id' => 313, 'count' => 1, 'enchantments' => [['id' => Enchantment::TYPE_ARMOR_PROTECTION, 'level' => 2]]],
            ['id' => 313, 'count' => 1, 'enchantments' => [['id' => Enchantment::TYPE_ARMOR_FIRE_PROTECTION, 'level' => 1]]],
            ['id' => 313, 'count' => 1, 'enchantments' => [['id' => Enchantment::TYPE_ARMOR_FIRE_PROTECTION, 'level' => 2]]],
            ['id' => 313, 'count' => 1, 'enchantments' => [['id' => Enchantment::TYPE_ARMOR_FIRE_PROTECTION, 'level' => 3]]],
            ['id' => 313, 'count' => 1, 'enchantments' => [['id' => Enchantment::TYPE_ARMOR_PROJECTILE_PROTECTION, 'level' => 1]]],

            ['id' => 267, 'count' => 1, 'enchantments' => [['id' => Enchantment::TYPE_WEAPON_SHARPNESS, 'level' => 3]]],
            ['id' => 267, 'count' => 1, 'enchantments' => [['id' => Enchantment::TYPE_WEAPON_SHARPNESS, 'level' => 2]]],
            ['id' => 267, 'count' => 1, 'enchantments' => [['id' => Enchantment::TYPE_WEAPON_SHARPNESS, 'level' => 3]], ['id' => Enchantment::TYPE_WEAPON_FIRE_ASPECT, 'level' => 1]],
            ['id' => 267, 'count' => 1, 'enchantments' => [['id' => Enchantment::TYPE_WEAPON_SHARPNESS, 'level' => 2]], ['id' => Enchantment::TYPE_WEAPON_FIRE_ASPECT, 'level' => 1]],
            ['id' => 267, 'count' => 1, 'enchantments' => [['id' => Enchantment::TYPE_WEAPON_SHARPNESS, 'level' => 1]], ['id' => Enchantment::TYPE_WEAPON_FIRE_ASPECT, 'level' => 1]],
            ['id' => 267, 'count' => 1],

            ['id' => 276, 'count' => 1, 'enchantments' => [['id' => Enchantment::TYPE_WEAPON_SHARPNESS, 'level' => 3]]],
            ['id' => 276, 'count' => 1, 'enchantments' => [['id' => Enchantment::TYPE_WEAPON_SHARPNESS, 'level' => 2]]],
            ['id' => 276, 'count' => 1, 'enchantments' => [['id' => Enchantment::TYPE_WEAPON_SHARPNESS, 'level' => 1]]],
            ['id' => 276, 'count' => 1, 'enchantments' => [['id' => Enchantment::TYPE_WEAPON_SHARPNESS, 'level' => 2]], ['id' => Enchantment::TYPE_WEAPON_FIRE_ASPECT, 'level' => 1]],
            ['id' => 276, 'count' => 1, 'enchantments' => [['id' => Enchantment::TYPE_WEAPON_SHARPNESS, 'level' => 1]], ['id' => Enchantment::TYPE_WEAPON_FIRE_ASPECT, 'level' => 1]],
            ['id' => 276, 'count' => 1],

            ['id' => 261, 'count' => 1, 'enchantments' => [['id' => Enchantment::TYPE_BOW_POWER, 'level' => 1]]],
            ['id' => 261, 'count' => 1],
            ['id' => 262, 'count' => 15],
            ['id' => 262, 'count' => 7],
            ['id' => 322, 'count' => 1],
            ['id' => 344, 'count' => 8],
            ['id' => 344, 'count' => 16],
            ['id' => 345, 'count' => 1, 'name' => TextFormat::RESET . TextFormat::GOLD . 'Player tracker'],
            ['id' => 364, 'count' => 15],
            ['id' => 364, 'count' => 15],
            ['id' => 364, 'count' => 15],
            ['id' => 364, 'count' => 15]
        ]);
        $items->save();

        $this->levelManager = new LevelManager();

        $this->arenaManager = new ArenaManager();

        $this->signManager = new SignManager();

        $this->kitManager = new KitManager();

        new SkyWarsCommand();

        new EventListener();

        $this->saveResource('items.yml');

        $this->getLogger()->info('SkyWarsReloaded enabled.');
    }

    public function onDisable() {
        $config = new Config($this->getDataFolder() . 'remove.yml', Config::YAML);

        foreach($this->arenaManager->getArenas() as $arena) {
            if($arena instanceof Arena) {
                $data = $config->exists('names') ? $config->get('names') : [];

                $data[] = $arena->getName();

                $config->set('names', $data);

                $config->save();
            }
        }
    }

    /**
     * @return KitManager
     */
    public function getKitManager(): KitManager {
        return $this->kitManager;
    }

    /**
     * @return SignManager
     */
    public function getSignManager(): SignManager {
        return $this->signManager;
    }

    /**
     * @return LevelManager
     */
    public function getLevelManager(): LevelManager {
        return $this->levelManager;
    }

    /**
     * @return ArenaManager
     */
    public function getArenaManager(): ArenaManager {
        return $this->arenaManager;
    }

    /**
     * @return SkyWars
     */
    public static function getInstance() {
        return static::$instance;
    }
}