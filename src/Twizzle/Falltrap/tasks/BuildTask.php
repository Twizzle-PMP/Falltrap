<?php

declare(strict_types=1);

namespace Twizzle\Falltrap\tasks;

use pocketmine\scheduler\Task;
use pocketmine\player\Player;
use pocketmine\world\Position;
use pocketmine\utils\TextFormat;
use pocketmine\world\particle\FloatingTextParticle;
use pocketmine\world\particle\DustParticle;
use pocketmine\color\Color;
use pocketmine\block\Block;
use pocketmine\block\VanillaBlocks;
use pocketmine\entity\Human;
use pocketmine\entity\Location;
use pocketmine\item\VanillaItems;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\AnimatePacket;

class BuildTask extends Task {

    private FloatingTextParticle $hologram;
    private Position $center;
    private ?Human $npc = null;
    private int $currentY;
    private int $minY = 2;
    private int $maxY;
    private int $minX;
    private int $maxX;
    private int $minZ;
    private int $maxZ;

    public function __construct(
        private Player $player,
        private Position $p1,
        private Position $p2,
        private Block $mat
    ) {
        $this->minX = (int)min($p1->x, $p2->x);
        $this->maxX = (int)max($p1->x, $p2->x);
        $this->maxY = (int)max($p1->y, $p2->y);
        $this->minZ = (int)min($p1->z, $p2->z);
        $this->maxZ = (int)max($p1->z, $p2->z);
        
        $this->currentY = $this->maxY;

        $midX = ($this->minX + $this->maxX) / 2 + 0.5;
        $midZ = ($this->minZ + $this->maxZ) / 2 + 0.5;
        $this->center = new Position($midX, (float)($this->maxY + 2), $midZ, $p1->getWorld());
        
        $this->hologram = new FloatingTextParticle("", TextFormat::colorize("&bConstruction Started..."));
        $this->center->getWorld()->addParticle($this->center, $this->hologram);

        $this->spawnMinerNPC();
    }

    private function spawnMinerNPC(): void {
        $location = new Location($this->center->x, $this->center->y - 1.5, $this->center->z, $this->center->getWorld(), 0, 45);
        $this->npc = new Human($location, $this->player->getSkin());
        $this->npc->setNameTagVisible(false);
        $this->npc->setCanSaveWithChunk(false);
        $this->npc->setHasGravity(false);
        
        $inventory = $this->npc->getInventory();
        $inventory->setItem(0, VanillaItems::DIAMOND_PICKAXE());
        $inventory->setHeldItemIndex(0);
        
        $this->npc->spawnToAll();
    }

    public function onRun(): void {
        if (!$this->player->isOnline()) {
            $this->stop();
            return;
        }

        $world = $this->center->getWorld();
        
        for ($x = $this->minX; $x <= $this->maxX; $x++) {
            for ($z = $this->minZ; $z <= $this->maxZ; $z++) {
                if ($x === $this->minX || $x === $this->maxX || $z === $this->minZ || $z === $this->maxZ) {
                    $world->setBlockAt($x, $this->currentY, $z, $this->mat);
                } else {
                    $world->setBlockAt($x, $this->currentY, $z, VanillaBlocks::AIR());
                }
                
                if (mt_rand(0, 5) === 1) {
                    $world->addParticle(new Position((float)$x, (float)$this->currentY, (float)$z, $world), new DustParticle(new Color(200, 200, 200)));
                }
            }
        }

        $percentage = (int)round((($this->maxY - $this->currentY) / max(1, ($this->maxY - $this->minY))) * 100);
        $this->hologram->setText(TextFormat::colorize("&fProgress: &b" . $percentage . "% &7(&fLayer: " . $this->currentY . "&7)"));
        $world->addParticle($this->center, $this->hologram);

        if ($this->npc !== null && !$this->npc->isClosed()) {
            $this->npc->setMotion(new Vector3(0, 0, 0));
            $this->npc->teleport(new Location($this->center->x, (float)($this->currentY + 0.5), $this->center->z, $world, 0, 60));
            
            $packet = new AnimatePacket();
            $packet->actorRuntimeId = $this->npc->getId();
            $packet->action = AnimatePacket::ACTION_SWING_ARM;
            foreach ($this->npc->getViewers() as $viewer) {
                $viewer->getNetworkSession()->sendDataPacket($packet);
            }
        }

        $this->currentY--;

        if ($this->currentY < $this->minY) {
            $this->finish();
        }
    }

    private function finish(): void {
        if ($this->npc !== null) $this->npc->close();
        $this->hologram->setInvisible(true);
        $this->center->getWorld()->addParticle($this->center, $this->hologram);
        $this->player->sendMessage(TextFormat::colorize("&bFalltrap generated successfully."));
        $this->getHandler()->cancel();
    }

    private function stop(): void {
        if ($this->npc !== null) $this->npc->close();
        $this->hologram->setInvisible(true);
        $this->center->getWorld()->addParticle($this->center, $this->hologram);
        $this->getHandler()->cancel();
    }
}