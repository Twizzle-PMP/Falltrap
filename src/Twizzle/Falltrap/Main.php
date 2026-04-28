<?php

declare(strict_types=1);

namespace Twizzle\Falltrap;

use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use Twizzle\Falltrap\commands\FalltrapCommand;
use Twizzle\Falltrap\listeners\FalltrapListener;

class Main extends PluginBase {

    private Config $cooldownData;

    protected function onEnable(): void {
        $this->saveDefaultConfig();
        $this->cooldownData = new Config($this->getDataFolder() . "cooldowns.yml", Config::YAML);
        
        $this->getServer()->getCommandMap()->register("Falltrap", new FalltrapCommand($this));
        $this->getServer()->getPluginManager()->registerEvents(new FalltrapListener($this), $this);
    }

    public function getCooldownConfig(): Config {
        return $this->cooldownData;
    }
}