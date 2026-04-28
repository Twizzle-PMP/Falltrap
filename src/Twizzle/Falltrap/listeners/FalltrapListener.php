<?php

declare(strict_types=1);

namespace Twizzle\Falltrap\listeners;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\utils\TextFormat;
use pocketmine\player\Player;
use pocketmine\block\VanillaBlocks;
use pocketmine\block\utils\DyeColor;
use pocketmine\Server;
use Twizzle\Falltrap\Main;
use Twizzle\Falltrap\utils\SessionManager;
use Twizzle\Falltrap\tasks\BuildTask;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\type\InvMenuTypeIds;
use muqsit\invmenu\transaction\InvMenuTransaction;
use muqsit\invmenu\transaction\InvMenuTransactionResult;

class FalltrapListener implements Listener {

    private array $cooldowns = [];

    public function __construct(private Main $plugin) {}

    public function onInteract(PlayerInteractEvent $event): void {
        $player = $event->getPlayer();
        $item = $event->getItem();
        $name = $player->getName();

        if ($item->getNamedTag()->getTag("falltrap_tool") !== null) {
            $event->cancel();
            $block = $event->getBlock();

            if ($event->getAction() === PlayerInteractEvent::LEFT_CLICK_BLOCK) {
                SessionManager::setPos1($name, $block->getPosition());
                $player->sendMessage(TextFormat::colorize("&bCorner 1 set successfully."));
                return;
            }

            if ($event->getAction() === PlayerInteractEvent::RIGHT_CLICK_BLOCK) {
                if ($player->isSneaking()) {
                    $isOp = Server::getInstance()->isOp($player->getName());
                    
                    if (!$isOp && isset($this->cooldowns[$name]) && time() < $this->cooldowns[$name]) {
                        $remaining = $this->cooldowns[$name] - time();
                        $player->sendMessage(TextFormat::colorize("&cWait &f" . $remaining . "s &cto use this again!"));
                        return;
                    }

                    $session = SessionManager::getSession($name);
                    if (isset($session["pos1"]) && isset($session["pos2"])) {
                        $this->openMaterialMenu($player);
                    } else {
                        $player->sendMessage(TextFormat::colorize("&cError: Select both corners first."));
                    }
                } else {
                    SessionManager::setPos2($name, $block->getPosition());
                    $player->sendMessage(TextFormat::colorize("&bCorner 2 set successfully."));
                }
            }
        }
    }

    private function openMaterialMenu(Player $player): void {
        $menu = InvMenu::create(InvMenuTypeIds::TYPE_CHEST);
        $menu->setName(TextFormat::colorize("&8» &bFalltrap Materials &8«"));
        
        $inv = $menu->getInventory();
        $border = VanillaBlocks::STAINED_GLASS_PANE()->setColor(DyeColor::GRAY())->asItem()->setCustomName(" ");

        for ($i = 0; $i < 27; $i++) {
            $inv->setItem($i, $border);
        }

        $inv->setItem(10, VanillaBlocks::CRAFTING_TABLE()->asItem()->setCustomName(TextFormat::colorize("&bCrafting Table")));
        $inv->setItem(11, VanillaBlocks::FURNACE()->asItem()->setCustomName(TextFormat::colorize("&bFurnace")));

        $wools = [
            12 => [DyeColor::WHITE(), "&fWhite Wool"],
            13 => [DyeColor::LIME(), "&aLime Wool"],
            14 => [DyeColor::BLACK(), "&0Black Wool"],
            15 => [DyeColor::RED(), "&cRed Wool"],
            16 => [DyeColor::BLUE(), "&9Blue Wool"],
            19 => [DyeColor::ORANGE(), "&6Orange Wool"],
            20 => [DyeColor::YELLOW(), "&eYellow Wool"],
            21 => [DyeColor::PINK(), "&dPink Wool"],
            22 => [DyeColor::PURPLE(), "&5Purple Wool"],
            23 => [DyeColor::CYAN(), "&3Cyan Wool"],
            24 => [DyeColor::LIGHT_BLUE(), "&bLight Blue Wool"],
            25 => [DyeColor::GRAY(), "&8Gray Wool"]
        ];

        foreach ($wools as $slot => $data) {
            $inv->setItem($slot, VanillaBlocks::WOOL()->setColor($data[0])->asItem()->setCustomName(TextFormat::colorize("&r&3▐ " . $data[1])));
        }

        $menu->setListener(function(InvMenuTransaction $tr) use ($player): InvMenuTransactionResult {
            $item = $tr->getItemClicked();
            if ($item->getCustomName() === " ") return $tr->discard();

            $session = SessionManager::getSession($player->getName());
            if ($session !== null) {
                $player->getInventory()->setItemInHand(VanillaBlocks::AIR()->asItem());
                
                $time = $this->plugin->getConfig()->getNested("falltrap.cooldown", 60);
                $this->cooldowns[$player->getName()] = time() + (int)$time;

                $this->plugin->getScheduler()->scheduleRepeatingTask(
                    new BuildTask($player, $session["pos1"], $session["pos2"], $item->getBlock()), 
                    20
                );
                SessionManager::clear($player->getName());
            }
            
            $player->removeCurrentWindow();
            return $tr->discard();
        });
        $menu->send($player);
    }
}