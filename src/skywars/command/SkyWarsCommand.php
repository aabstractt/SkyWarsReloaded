<?php

namespace skywars\command;

use advancedserver\ConnectionException;
use advancedserver\Main;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use skywars\arena\Arena;
use skywars\player\Configurator;
use skywars\SkyWars;

class SkyWarsCommand extends Command {

    /** @var array */
    public static $sign = [];

    /**
     * SkyWarsCommand constructor.
     */
    public function __construct() {
        parent::__construct('sw', 'SkyWars Command', null, ['swr', 'skywars']);

        Server::getInstance()->getCommandMap()->register($this->getName(), $this);

        $this->setPermission('sw.command');
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param string[] $args
     *
     * @return bool
     */
    public function execute(CommandSender $sender, $commandLabel, array $args): bool {
        if($this->testPermission($sender)) {
            if(count($args) < 1) {
                $sender->sendMessage(TextFormat::GREEN . 'SkyWars Reloaded plugin made by iTheTrollIdk, version 1.2.0');
                return false;
            }

            if(strtolower($args[0]) == 'levels') {
                if(!$sender->hasPermission('sw.command.levels')) {
                    $sender->sendMessage($this->getPermissionMessage());
                } else {
                    $sender->sendMessage(TextFormat::GREEN . 'Levels:');

                    $sender->sendMessage(SkyWars::getInstance()->getLevelManager()->toString());

                    return true;
                }
            }

            if(strtolower($args[0]) == 'arenas') {
                if(!$sender->hasPermission('sw.command.arenas')) {
                    $sender->sendMessage($this->getPermissionMessage());
                } else {
                    $sender->sendMessage(TextFormat::GREEN . 'Arenas:');

                    $sender->sendMessage(SkyWars::getInstance()->getArenaManager()->toString());

                    return true;
                }
            }

            if($sender instanceof Player) {
                switch(strtolower($args[0])) {

                    case 'create':
                        if(!$sender->hasPermission('sw.command.create')) {
                            $sender->sendMessage($this->getPermissionMessage());
                        } else if(!isset($args[1])) {
                            $sender->sendMessage(TextFormat::RED . 'Use /' . $commandLabel . ' create <world>');
                        } else if(SkyWars::getInstance()->getLevelManager()->exists($args[1])) {
                            $sender->sendMessage(TextFormat::RED . 'Level ' . $args[1] . ' already exists.');
                        } else if(!file_exists(Server::getInstance()->getDataPath() . '/worlds/' . $args[1])) {
                            $sender->sendMessage(TextFormat::RED . 'World folder not found...');
                        } else {
                            $configurator = new Configurator(['name' => $sender->getName(), 'slot' => 0, 'arena' => null], SkyWars::getInstance()->getLevelManager()->defaultLevelData);

                            $configurator->setFolderName($args[1]);

                            if($configurator->run()) {
                                $sender->teleport($configurator->getLevel()->getSafeSpawn());

                                $sender->sendMessage(TextFormat::GOLD . 'Execute command ' . TextFormat::YELLOW . '/sw cancel' . TextFormat::GOLD . ' to cancel.');
                            }
                        }
                        break;

                    case 'name':
                        if(!$sender->hasPermission('sw.command.name')) {
                            $sender->sendMessage($this->getPermissionMessage());
                        } else if(!isset(SkyWars::getInstance()->configurators[strtolower($sender->getName())])) {
                            $sender->sendMessage(TextFormat::RED . 'This command can only be used when you are an arena editor.');
                        } else if(!isset($args[1])) {
                            $sender->sendMessage(TextFormat::RED . 'Use /' . $commandLabel . ' name <name>');
                        } else {
                            $configurator = SkyWars::getInstance()->configurators[strtolower($sender->getName())];

                            if($configurator instanceof Configurator) {
                                $configurator->setCustomName($args[1]);

                                $sender->sendMessage(TextFormat::GOLD . 'The level name ' . TextFormat::YELLOW . $configurator->getFolderName() . TextFormat::GOLD . ' has been changed to ' . TextFormat::YELLOW . $configurator->getCustomName() . TextFormat::GOLD . '.');
                            }
                        }
                        break;

                    case 'slots':
                        if(!$sender->hasPermission('sw.command.slots')) {
                            $sender->sendMessage($this->getPermissionMessage());
                        } else if(!isset(SkyWars::getInstance()->configurators[strtolower($sender->getName())])) {
                            $sender->sendMessage(TextFormat::RED . 'This command can only be used when you are an arena editor.');
                        } else if(!isset($args[1])) {
                            $sender->sendMessage(TextFormat::RED . 'Use /' . $commandLabel . ' slots <slots>');
                        } else {
                            $configurator = SkyWars::getInstance()->configurators[strtolower($sender->getName())];

                            if($configurator instanceof Configurator) {
                                $configurator->setMaxSlots($args[1]);

                                $sender->sendMessage(TextFormat::GOLD . 'The maximum number of players at this level is ' . TextFormat::YELLOW . $args[1]);
                            }
                        }
                        break;

                    case 'spawn':
                        if(!$sender->hasPermission('sw.command.spawn')) {
                            $sender->sendMessage($this->getPermissionMessage());
                        } else if(!isset(SkyWars::getInstance()->configurators[strtolower($sender->getName())])) {
                            $sender->sendMessage(TextFormat::RED . 'This command can only be used when you are an arena editor.');
                        } else if(!isset($args[1])) {
                            $sender->sendMessage(TextFormat::RED . 'Use /' . $commandLabel . ' spawn <slot>');
                        } else {
                            $configurator = SkyWars::getInstance()->configurators[strtolower($sender->getName())];

                            if($configurator instanceof Configurator) {
                                if($configurator->getMaxSlots() <= 0) {
                                    $sender->sendMessage(TextFormat::RED . 'First you must place the maximum slot available for this level');
                                    return false;
                                } else if($sender->getLevel() !== $configurator->getLevel()) {
                                    $sender->sendMessage(TextFormat::RED . 'You cannot execute this command if you are at a different level');
                                } else if(intval($args[1]) > $configurator->getMaxSlots()) {
                                    $sender->sendMessage(TextFormat::RED . 'The maximum slot is ' . $configurator->getMaxSlots() . ' and you are trying to place a number greater than ' . $configurator->getMaxSlots());
                                } else {
                                    $configurator->setSpawnPosition(intval($args[1]), $configurator->asPosition());

                                    $sender->sendMessage(TextFormat::GREEN . 'Spawn ' . $args[1] . ' has been placed');

                                    if($configurator->getMaxSlots() == count($configurator->getSpawnsPosition())) {
                                        $sender->sendMessage(TextFormat::GREEN . 'All spawns have been registered correctly!');
                                    }
                                }
                            }
                        }
                        break;

                    case 'save':
                        if(!$sender->hasPermission('sw.command.save')) {
                            $sender->sendMessage($this->getPermissionMessage());
                        } else if(!isset(SkyWars::getInstance()->configurators[strtolower($sender->getName())])) {
                            $sender->sendMessage(TextFormat::RED . 'This command can only be used when you are an arena editor.');
                        } else {
                            $configurator = SkyWars::getInstance()->configurators[strtolower($sender->getName())];

                            if($configurator instanceof Configurator) {
                                $configurator->save();

                                $sender->sendMessage(TextFormat::GREEN . 'Level ' . $configurator->getFolderName() . ' (' . $configurator->getCustomName() . ') has been saved.');
                            }
                        }
                        break;

                    case 'sign':
                        if(!$sender->hasPermission('sw.command.sign')) {
                            $sender->sendMessage($this->getPermissionMessage());
                        } else {
                            self::$sign[strtolower($sender->getName())] = true;

                            $sender->sendMessage(TextFormat::GOLD . 'Touch the sign.');
                        }
                        break;

                    case 'start':
                        $arena = null;

                        if(!$sender->hasPermission('sw.command.start')) {
                            $sender->sendMessage($this->getPermissionMessage());
                        } else if(!isset($args[1])) {
                            $arena = SkyWars::getInstance()->getArenaManager()->getArenaByPlayer($sender->getName());
                        } else {
                            $arena = SkyWars::getInstance()->getArenaManager()->getArena($args[1]);
                        }

                        if(!$arena instanceof Arena) {
                            if(count($args) == 2) {
                                $sender->sendMessage(TextFormat::RED . 'Arena ' . $args[1] . ' not found.');
                            } else {
                                $sender->sendMessage(TextFormat::RED . 'Use /sw start <arena> or join arena.');
                            }
                        } else if($arena->getLobbyTime() < 16) {
                            $sender->sendMessage(TextFormat::RED . 'You cannot force the start of this game, only ' . $arena->getLobbyTime() . ' seconds left to start');
                        } else {
                            $arena->sendMessage(TextFormat::GREEN . $sender->getName() . ' forced start game.');

                            $arena->lobbytime = 15;
                        }
                        break;

                    default:
                        $sender->sendMessage(TextFormat::GREEN . 'SkyWars Reloaded plugin made by iTheTrollIdk, version 1.2.0');
                        break;
                }
            } else {
                $sender->sendMessage(TextFormat::RED . 'Run this command in-game!');
            }
        }
        return true;
    }
}