<?php

namespace skywars;

use advancedserver\event\PlayerLobbyEvent;
use advancedserver\event\RequestGameTypeEvent;
use advancedserver\form\element\ElementButton;
use advancedserver\form\response\event\PlayerFormRespondedEvent;
use advancedserver\form\response\FormResponseSimple;
use advancedserver\form\window\FormWindowSimple;
use advancedserver\Main;
use advancedserver\Player as pocketPlayer;
use Exception;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\item\Item;
use pocketmine\network\protocol\PlayerActionPacket;
use pocketmine\Server;
use pocketmine\tile\Sign as pocketSign;
use pocketmine\utils\TextFormat;
use skywars\arena\Arena;
use skywars\arena\Sign;
use skywars\command\SkyWarsCommand;
use skywars\player\Player;
use skywars\player\PlayerBase;

class EventListener implements Listener {

    /**
     * EventListener constructor.
     */
    public function __construct() {
        Server::getInstance()->getPluginManager()->registerEvents($this, SkyWars::getInstance());
    }

    /**
     * @param PlayerJoinEvent $ev
     */
    public function onJoin(PlayerJoinEvent $ev) {
        if($ev->getPlayer() instanceof pocketPlayer) {
            if(count(SkyWars::getInstance()->getSignManager()->getSigns()) <= 0) {
                SkyWars::getInstance()->getSignManager()->load();
            }
        }
    }

    /**
     * @param PlayerInteractEvent $ev
     * @throws Exception
     */
    public function onInteract(PlayerInteractEvent $ev) {
        $player = $ev->getPlayer();

        $tile = $player->getLevel()->getTile($ev->getBlock());

        if($tile instanceof pocketSign) {
            $sign = SkyWars::getInstance()->getSignManager()->getSignByPosition($ev->getBlock());

            if($sign instanceof Sign) {
                if($sign->hasArena() and ($sign->getArena()->getStatus() == Arena::LOBBY and !$sign->getArena()->isFull())) {
                    new Player(['name' => $player->getName(), 'arena' => $sign->getArena(), 'kills' => 0, 'slot' => 0, 'spectating' => false]);
                }
            } else if(isset(SkyWarsCommand::$sign[strtolower($player->getName())])) {
                $sign = new Sign(['value' => false], $tile);

                $sign->setArena(null);

                $player->sendMessage(TextFormat::GOLD . 'Sign registered.');

                unset(SkyWarsCommand::$sign[strtolower($player->getName())]);
            }
        }
    }

    /**
     * @param PlayerQuitEvent $ev
     */
    public function onQuit(PlayerQuitEvent $ev) {
        $arena = SkyWars::getInstance()->getArenaManager()->getArenaByPlayer($ev->getPlayer()->getName());

        if($arena instanceof Arena) {
            $arena->quit($ev->getPlayer()->getName());
        }
    }

    /**
     * @param PlayerLobbyEvent $ev
     */
    public function onLobby(PlayerLobbyEvent $ev) {
        $arena = SkyWars::getInstance()->getArenaManager()->getArenaByPlayer($ev->getPlayer()->getName());

        if($arena instanceof Arena) {
            $arena->quit($ev->getPlayer()->getName());
        } else {
            echo $ev->getPlayer()->getName() . ' no tiene arena, pero esta en el nivel ' . $ev->getPlayer()->getLevel()->getFolderName() . PHP_EOL;
        }
    }

    public function onRequestGameType(RequestGameTypeEvent $ev) {
        $arena = SkyWars::getInstance()->getArenaManager()->getArenaByPlayer($ev->getPlayer()->getName());

        if($arena instanceof Arena) {
            $ev->setGameTypeName('SkyWars', $arena->getName());
        }
    }

    /**
     * @param BlockBreakEvent $ev
     */
    public function onBreak(BlockBreakEvent $ev) {
        $player = $ev->getPlayer();

        if($player->getLevel() === Server::getInstance()->getDefaultLevel()) {
            if(Main::getInstance()->canBeBuild($player->getName())) {
                $tile = $player->getLevel()->getTile($ev->getBlock());

                if($tile instanceof pocketSign) {
                    $sign = SkyWars::getInstance()->getSignManager()->getSignByPosition($tile);

                    if($sign instanceof Sign) {
                        if($sign->hasArena()) {
                            $sign->getArena()->getLevel()->close();

                            $sign->arena = null;
                        }

                        $sign->close(true);
                    }
                }
            }
        }

        $arena = SkyWars::getInstance()->getArenaManager()->getArenaByPlayer($player->getName());

        if($arena instanceof Arena) {
            if($arena->getStatus() == Arena::LOBBY) {
                $ev->setCancelled(true);
            }
        }
    }

    /**
     * @param BlockPlaceEvent $ev
     */
    public function onPlace(BlockPlaceEvent $ev) {
        $player = $ev->getPlayer();

        if($player instanceof pocketPlayer) {
            $arena = SkyWars::getInstance()->getArenaManager()->getArenaByPlayer($player->getName());

            if($arena instanceof Arena) {
                if($arena->getStatus() == Arena::LOBBY and $player->getLevel()->getFolderName() == $arena->getName()) {
                    $ev->setCancelled(true);
                }
            }
        }
    }

    /**
     * @param PlayerMoveEvent $ev
     */
    public function onMove(PlayerMoveEvent $ev) {
        $arena = SkyWars::getInstance()->getArenaManager()->getArenaByPlayer($ev->getPlayer()->getName());

        if($arena instanceof Arena) {
            if($arena->getStatus() == Arena::LOBBY) {
                $to = clone $ev->getFrom();

                $to->yaw = $ev->getTo()->yaw;

                $to->pitch = $ev->getTo()->pitch;

                $ev->setTo($to);
            }
        }
    }


    /**
     * @param EntityDamageEvent $ev
     */
    public function onDamage(EntityDamageEvent $ev) {
        $entity = $ev->getEntity();

        if($entity instanceof pocketPlayer) {
            $arena = SkyWars::getInstance()->getArenaManager()->getArenaByPlayer($entity->getName());

            if($arena instanceof Arena) {
                if($arena->getStatus() == Arena::LOBBY or ($arena->getStatus() == Arena::IN_GAME and $arena->getGameTime() < 6)) {
                    $ev->setCancelled();
                }

                if($arena->getStatus() == Arena::IN_GAME) {
                    $player = $arena->get($entity->getName());

                    if($player instanceof Player) {
                        if($ev instanceof EntityDamageByEntityEvent) {
                            $target = $arena->get($ev->getDamager()->getName());

                            if($target instanceof Player) {
                                $player->attack($target->getName());
                            }
                        }
                        if(($ev->getFinalDamage() + 1.4) >= $player->getInstance()->getHealth() and !$player->isSpectating()) {
                            $ev->setCancelled();

                            $entity->setHealth($entity->getMaxHealth());

                            $player->convertSpectator();

                            foreach($entity->getDrops() as $drop) {
                                $entity->getLevel()->dropItem($player->asPosition(), $drop);
                            }

                            $entity->teleport($arena->getLevel()->getSpawnPosition($player->getSlot()));

                            $entity->setGamemode($entity::SPECTATOR);

                            $entity->knockBack($entity, 0, ($entity->x - ($entity->x + 0.5)), ($entity->z - ($entity->z + 0.5)), (1 / 0xa));

                            $entity->getInventory()->clearAll();

                            $entity->getInventory()->setHeldItemSlot(4);

                            $entity->getInventory()->setHeldItemIndex(4);

                            $entity->sendContents();

                            $entity->getInventory()->setItem(8, (Item::get(Item::BED))->setCustomName(TextFormat::RESET . TextFormat::RED . TextFormat::BOLD . 'Leave'));

                            $entity->getInventory()->setItem(0, (Item::get(Item::COMPASS))->setCustomName(TextFormat::RESET . TextFormat::GREEN . TextFormat::BOLD . 'Player teleporter'));

                            $entity->getInventory()->setItem(7, (Item::get(Item::PAPER))->setCustomName(TextFormat::RESET . TextFormat::AQUA . TextFormat::BOLD . 'Play Again'));

                            switch($ev->getCause()) {
                                case EntityDamageByEntityEvent::CAUSE_ENTITY_ATTACK:
                                    $player->attack($ev->getDamager()->getName());

                                    $killer = $player->getKiller();

                                    if($killer instanceof PlayerBase) {
                                        $killer->addKill();

                                        $arena->sendMessage($entity->getRank()->getDisplayName() . TextFormat::YELLOW . ' was slain by ' . $killer->getInstance()->getRank()->getDisplayName());
                                    }
                                    break;

                                case EntityDamageEvent::CAUSE_FIRE:
                                case EntityDamageEvent::CAUSE_FIRE_TICK:
                                    $killer = $player->getKiller();

                                    if($killer instanceof PlayerBase) {
                                        $killer->addKill();

                                        $arena->sendMessage($entity->getRank()->getDisplayName() . TextFormat::YELLOW . ' was burn by ' . $killer->getInstance()->getRank()->getDisplayName());
                                    } else {
                                        $arena->sendMessage($entity->getRank()->getDisplayName() . TextFormat::YELLOW . ' was burn.');
                                    }
                                    break;

                                case EntityDamageEvent::CAUSE_LAVA:
                                    $killer = $player->getKiller();

                                    if($killer instanceof PlayerBase) {
                                        $killer->addKill();

                                        $arena->sendMessage($entity->getRank()->getDisplayName() . TextFormat::YELLOW . ' was slain by ' . $killer->getInstance()->getRank()->getDisplayName());
                                    } else {
                                        $arena->sendMessage($entity->getRank()->getDisplayName() . TextFormat::YELLOW . ' tried to swim in lava.');
                                    }
                                    break;

                                case EntityDamageEvent::CAUSE_FALL:
                                    $killer = $player->getKiller();

                                    if($killer instanceof PlayerBase) {
                                        $killer->addKill();

                                        $arena->sendMessage($entity->getRank()->getDisplayName() . TextFormat::YELLOW . ' was slain by ' . $killer->getInstance()->getRank()->getDisplayName());
                                    } else {
                                        $arena->sendMessage($entity->getRank()->getDisplayName() . TextFormat::YELLOW . ' hit the ground too hard.');
                                    }
                                    break;

                                case EntityDamageEvent::CAUSE_SUFFOCATION:
                                    $killer = $player->getKiller();

                                    if($killer instanceof PlayerBase) {
                                        $killer->addKill();

                                        $arena->sendMessage($entity->getRank()->getDisplayName() . TextFormat::YELLOW . ' was slain by ' . $killer->getInstance()->getRank()->getDisplayName());
                                    } else {
                                        $arena->sendMessage($entity->getRank()->getDisplayName() . TextFormat::YELLOW . ' was suffocated.');
                                    }
                                    break;

                                case EntityDamageEvent::CAUSE_PROJECTILE:
                                    $player->attack($ev->getDamager()->getName());

                                    $killer = $player->getKiller();

                                    if($killer instanceof PlayerBase) {
                                        $killer->addKill();

                                        $arena->sendMessage($entity->getRank()->getDisplayName() . TextFormat::YELLOW . ' was shot by ' . $killer->getInstance()->getRank()->getDisplayName());
                                    }
                                    break;

                                case EntityDamageEvent::CAUSE_VOID:
                                    $killer = $player->getKiller();

                                    if($killer instanceof PlayerBase) {
                                        $killer->addKill();

                                        $arena->sendMessage($entity->getRank()->getDisplayName() . TextFormat::YELLOW . ' was thrown into the void by ' . $killer->getInstance()->getRank()->getDisplayName());
                                    } else {
                                        $arena->sendMessage($entity->getRank()->getDisplayName() . TextFormat::YELLOW . ' fell into the void.');
                                    }
                                    break;

                                default:
                                    $arena->sendMessage($entity->getRank()->getDisplayName() . TextFormat::YELLOW . ' died.');
                                    break;
                            }
                            $assistance = $player->getAssistance();

                            if($assistance instanceof PlayerBase) {
                                $assistance->sendMessage(TextFormat::WHITE . 'You have assisted killing ' . $entity->getRank()->getDisplayName() . '!');
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * @param PlayerDropItemEvent $ev
     */
    public function onDropItem(PlayerDropItemEvent $ev) {
        $arena = SkyWars::getInstance()->getArenaManager()->getArenaByPlayer($ev->getPlayer()->getName());

        if($arena instanceof Arena) {
            if($arena->getStatus() == Arena::LOBBY or $arena->get($ev->getPlayer()->getName())->isSpectating()) {
                $ev->setCancelled();
            }
        }
    }

    /**
     * @param DataPacketReceiveEvent $ev
     * @throws Exception
     */
    public function onDataPacketReceive(DataPacketReceiveEvent $ev) {
        $player = $ev->getPlayer();

        $pk = $ev->getPacket();

        if($player instanceof pocketPlayer) {
            if($pk instanceof PlayerActionPacket) {
                if($pk->action == 25) {
                    $arena = SkyWars::getInstance()->getArenaManager()->getArenaByPlayer($player->getName());

                    if($arena instanceof Arena) {
                        $item = $player->getInventory()->getItemInHand();

                        $target = $arena->get($player->getName());

                        if($target instanceof Player) {
                            if($item->getId() == Item::BED and TextFormat::clean($item->getCustomName()) == 'Leave') {
                                Server::getInstance()->dispatchCommand($player, 'hub');
                            } else if($item->getId() == Item::EMPTY_MAP and TextFormat::clean($item->getCustomName()) == 'Voting') {
                                $form = new FormWindowSimple(8934, TextFormat::DARK_RED . TextFormat::BOLD . 'Voting', 'Vote for type of chest in this game');

                                $form->addButton(new ElementButton(TextFormat::GREEN . "Normal Chest Items\n" . TextFormat::YELLOW . count($arena->chestVotes[Arena::NORMAL_ID]) . ' votes', 'Normal'));

                                $form->addButton(new ElementButton(TextFormat::GREEN . "Overpowered Chest Items\n" . TextFormat::YELLOW . count($arena->chestVotes[Arena::OVERPOWERED_ID]) . ' votes', 'Overpowered'));

                                $player->showModal($form);
                            } else if($item->getId() == Item::COMPASS and TextFormat::clean($item->getCustomName()) == 'Player teleporter') {
                                $form = new FormWindowSimple(85, 'Player teleporter', TextFormat::WHITE . 'Click on a name to teleport!');

                                foreach($target->getArena()->getPlayers() as $p) {
                                    $form->addButton(new ElementButton($p->getName() . "\n" . TextFormat::GRAY . 'Select to teleport', $p->getName()));
                                }

                                $player->showModal($form);
                            } else if($item->getId() == Item::FILLED_MAP and (TextFormat::clean($item->getCustomName()) == 'Kit Selector')) {
                                $form = new FormWindowSimple(455, TextFormat::DARK_RED . TextFormat::BOLD . 'Kit Selector', 'Select you kit for this game');

                                foreach(SkyWars::getInstance()->getKitManager()->kits as $kit) {
                                    if($kit->isAuthorized($target)) {
                                        $form->addButton(new ElementButton(TextFormat::YELLOW . $kit->getName() . "\n" . TextFormat::GREEN . 'Click to select', $kit->getName()));
                                    } else {
                                        $form->addButton(new ElementButton(TextFormat::YELLOW . $kit->getName() . "\n" . TextFormat::RED . 'Click to buy', $kit->getName()));
                                    }
                                }

                                $player->showModal($form);
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * @param PlayerFormRespondedEvent $ev
     */
    public function onFormResponded(PlayerFormRespondedEvent $ev) {
        if($ev->isClosed()) {
            return;
        }

        $response = $ev->getForm()->getResponse();

        if($response instanceof FormResponseSimple) {
            $player = $ev->getPlayer();

            if($ev->getFormId() == 85) {
                $arena = SkyWars::getInstance()->getArenaManager()->getArenaByPlayer($player->getName());

                if($arena instanceof Arena) {
                    $target = $arena->get($player->getName());

                    if($target instanceof Player) {
                        if($response->getClickedButton()->hasDefinition()) {
                            $responded = $arena->get(TextFormat::clean($response->getClickedButton()->definition));

                            if($responded instanceof Player and $target->getInstance() instanceof pocketPlayer) {
                                $target->getInstance()->teleport($responded->asPosition());

                                $player->sendMessage(TextFormat::GREEN . 'You were teleported to: ' . $responded->getRank()->getDisplayName());
                            } else {
                                $player->sendMessage(TextFormat::RED . 'An error has occurred.');
                            }
                        }
                    }
                }
            } else if($ev->getFormId() == 8934) {
                $arena = SkyWars::getInstance()->getArenaManager()->getArenaByPlayer($player->getName());

                if($arena instanceof Arena) {
                    $target = $arena->get($player->getName());

                    if($target instanceof Player) {
                        if($response->getClickedButton()->definition == 'Normal') {
                            if($arena->addChestVote(Arena::NORMAL_ID, $target->getName())) {
                                $arena->sendMessage($target->getRank()->getDisplayName() . TextFormat::YELLOW . ' has voted for ' . TextFormat::GREEN . TextFormat::BOLD . 'Normal! ' . TextFormat::RESET . TextFormat::GRAY . count($arena->chestVotes[Arena::NORMAL_ID]) . ' votes.');
                            }
                        } else if($response->getClickedButton()->definition == 'Overpowered'){
                            if($arena->addChestVote(Arena::OVERPOWERED_ID, $target->getName())) {
                                $arena->sendMessage($target->getRank()->getDisplayName() . TextFormat::YELLOW . ' has voted for ' . TextFormat::GREEN . TextFormat::BOLD . 'Overpowered! ' . TextFormat::RESET . TextFormat::GRAY . count($arena->chestVotes[Arena::OVERPOWERED_ID]) . ' votes.');
                            }
                        }
                    }
                }
            }
        }
    }
}