<?php


namespace skywars;

use advancedserver\API;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use skywars\arena\Arena;
use skywars\arena\Level;

class LevelManager {

    /** @var array */
    public $defaultLevelData = ['folderName' => null, 'customName' => null, 'maxSlots' => 2, 'backup' => false, 'ranked' => false, 'type' => null];

    /** @var Level[] */
    protected $levels = [];

    /**
     * LevelManager constructor.
     */
    public function __construct() {
        if(!is_dir(SkyWars::getInstance()->getDataFolder() . 'backup')) {
            mkdir(SkyWars::getInstance()->getDataFolder() . 'backup');
        }

        $this->load();
    }

    public function load() {
        if(file_exists(SkyWars::getInstance()->getDataFolder() . 'levels.json')) {
            foreach(API::getJsonContents(SkyWars::getInstance()->getDataFolder() . 'levels.json') as $content) {
                $this->add(new Level($content));
            }
        }
    }

    public function add(Level $level): Level {
        $this->levels[strtolower($level->getFolderName())] = $level;

        return $level;
    }

    /**
     * @param string $folderName
     * @return Level|null
     */
    public function get(string $folderName): ?Level {
        return $this->levels[strtolower($folderName)] ?? null;
    }

    /**
     * @param string $folderName
     */
    public function delete(string $folderName) {
        if($this->exists($folderName)) {
            foreach(SkyWars::getInstance()->getArenaManager()->getArenasByLevel($this->get($folderName)) as $arena) {
                $arena->getLevel()->close();
            }

            unset($this->levels[strtolower($folderName)]);

            $this->deleteDir(SkyWars::getInstance()->getDataFolder() . 'backup' . DIRECTORY_SEPARATOR . $folderName);
        }

        $this->save();
    }

    public function save() {
        $data = [];

        foreach($this->getAll() as $level) {
            $data[$level->getFolderName()] = $level->data;
        }

        file_put_contents(SkyWars::getInstance()->getDataFolder() . 'levels.json', json_encode($data, JSON_PRETTY_PRINT | JSON_BIGINT_AS_STRING), LOCK_EX);
    }

    /**
     * @param string $folderName
     * @return bool
     */
    public function exists(string $folderName): bool {
        return isset($this->levels[strtolower($folderName)]);
    }

    /**
     * @return Level[]
     */
    public function getAll(): array {
        return $this->levels;
    }

    /**
     * @return Level|null
     */
    public function getLevelForArena() {
        $levels = $this->getLevelsAvailable();

        if(count($levels) < 1) {
            Server::getInstance()->getLogger()->error(TextFormat::RED . 'Levels not found');
            return null;
        }

        $level = $this->get(array_rand($levels));

        if(!$level instanceof Level) {
            return null;
        }

        return new Level($level->getLevelData());
    }

    /**
     * @return array
     */
    public function getLevelsAvailable(): array {
        $levels = [];
        foreach($this->levels as $level) {
            if(count(SkyWars::getInstance()->getArenaManager()->getArenasByLevel($level)) < (count($this->levels) > 2 ? 4 : 3)) $levels[strtolower($level->getFolderName())] = $level;
        }
        return $levels;
    }

    /**
     * @param string $name
     * @return bool $name
     */
    public function hasBackup(string $name): bool {
        $scandir = scandir(SkyWars::getInstance()->getDataFolder() . 'backup/');

        foreach($scandir as $file) {
            if($file !== '..' && $file !== '.') {
                if($file === $name) return true;
            }
        }

        return false;
    }

    /**
     * @param string $dirPath
     */
    public function deleteDir(string $dirPath) {
        if(substr($dirPath, strlen($dirPath) - 1, 1) != '/') {
            $dirPath .= '/';
        }

        $files = glob($dirPath . '*', GLOB_MARK);

        foreach($files as $file) {
            if(is_dir($file)) {
                $this->deleteDir($file);
            } else {
                @unlink($file);
            }
        }

        @rmdir($dirPath);
    }

    /**
     * @param string $src
     * @param string $dst
     */
    public function backup(string $src, string $dst) {
        $dir = opendir($src);

        @mkdir($dst);

        while(false !== ($file = readdir($dir))) {
            if(($file != '.') && ($file != '..')) {
                if(is_dir($src . '/' . $file)) {
                    $this->backup($src . '/' . $file, $dst . '/' . $file);
                } else {
                    copy($src . '/' . $file, $dst . '/' . $file);
                }
            }
        }

        closedir($dir);
    }

    /**
     * @return string
     */
    public function toString(): string {
        $names = [];

        if(count($this->levels) <= 0) return TextFormat::RED . 'Not found';

        foreach($this->levels as $level) {
            if($level instanceof Level) {
                $names[] = TextFormat::GOLD . '- ' . $level->getCustomName();
            }
        }

        return implode("\n", $names);
    }
}