<?php

declare(strict_types=1);

namespace Twizzle\Falltrap\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\item\VanillaItems;
use pocketmine\Server;
use Twizzle\Falltrap\Main;

class FalltrapCommand extends Command {

    public function __construct(private Main $plugin) {
        parent::__construct("falltrap", "Get the falltrap selector tool", "/falltrap", ["ft"]);
        $this->setPermission("falltrap.command");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void {
        if (!$sender instanceof Player) {
            $sender->sendMessage(TextFormat::colorize("&cUse this command in-game."));
            return;
        }

        $name = $sender->getName();
        $isOp = Server::getInstance()->isOp($name);

        if (!$isOp) {
            $lastUse = $this->plugin->getCooldownConfig()->get($name, 0);
            $twoWeeks = 14 * 24 * 60 * 60;
            $currentTime = time();

            if ($currentTime < ($lastUse + $twoWeeks)) {
                $remainingTime = ($lastUse + $twoWeeks) - $currentTime;
                $days = floor($remainingTime / 86400);
                $hours = floor(($remainingTime % 86400) / 3600);
                $minutes = floor(($remainingTime % 3600) / 60);

                $sender->sendMessage(TextFormat::colorize("&cYou must wait &f" . $days . "d " . $hours . "h " . $minutes . "m &cto use this again!"));
                return;
            }
        }

        $item = VanillaItems::GOLDEN_AXE();
        $item->setCustomName(TextFormat::colorize("&r&bFalltrap Selector"));
        $item->getNamedTag()->setString("falltrap_tool", "true");

        if ($sender->getInventory()->canAddItem($item)) {
            $sender->getInventory()->addItem($item);
            $sender->sendMessage(TextFormat::colorize("&bYou have received the Falltrap Selector."));
            
            if (!$isOp) {
                $this->plugin->getCooldownConfig()->set($name, time());
                $this->plugin->getCooldownConfig()->save();
            }
        } else {
            $sender->sendMessage(TextFormat::colorize("&cYour inventory is full!"));
        }
    }
}
