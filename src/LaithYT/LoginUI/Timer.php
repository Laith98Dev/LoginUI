<?php

namespace LaithYT\LoginUI;

use pocketmine\scheduler\Task;
use pocketmine\utils\TextFormat as TF;
use pocketmine\Player;

class Timer extends Task{
	
	/** @var Main */
	private $plugin;
	
	private $sender;
	
	public function __construct(Main $p, $sender){
		$this->plugin = $p;
		$this->sender = $sender;
	}
	
	public function onRun(int $currentTick) : void{
		if($this->sender instanceof Player){
			$player = $this->sender;
			if(!$this->plugin->isRegistered($player)){
				$player->kick(TF::RED . 'Login timed out');
			}
		}
	}
}