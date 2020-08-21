<?php

namespace LaithYT\LoginUI;

use pocketmine\event\player\{
	PlayerDropItemEvent,
	PlayerMoveEvent,
	PlayerQuitEvent,
	PlayerJoinEvent,
	PlayerChatEvent,
	PlayerPreLoginEvent,
	PlayerCommandPreprocessEvent
	};

use pocketmine\utils\TextFormat as TF;
use pocketmine\event\Listener;
use pocketmine\{Player, Server};

class EventListener implements Listener {
	
	/** @var Main  */
	private $plugin;
	
	public function __construct(Main $plugin){
		$this->plugin = $plugin;
	}
	
	public function onPreLogin(PlayerPreLoginEvent $event){
		$player = $event->getPlayer();
		if($this->plugin->isRegistered($player)){
			$this->plugin->unRegister($player);
		}
		if(isset($this->plugin->errors[$player->getRawUniqueId()])){
			unset($this->plugin->errors[$player->getRawUniqueId()]);
		}
	}
	
	public function onJoin(PlayerJoinEvent $event){
		$player = $event->getPlayer();
		if(!$this->plugin->isRegistered($player)){
			$player->sendMessage($this->plugin->getFromConfig("Join.Message"));
			$this->plugin->setTimer($player);
		}
	}
	
	public function onMove(PlayerMoveEvent $event){
		$player = $event->getPlayer();
		if(!$this->plugin->isRegistered($player)){
			$event->setCancelled();
		}
	}
	
	public function onChat(PlayerChatEvent $event){
		$player = $event->getPlayer();
		$msg = $event->getMessage();
		if(!$this->plugin->isRegistered($player)){
			$event->setCancelled();
		}
	}
	
	public function onCommand(PlayerCommandPreprocessEvent $event){
		$player = $event->getPlayer();
		$command = $event->getMessage();
		$cmd = explode(" ", $command);
		if(!$this->plugin->isRegistered($player)){
			if($cmd[0] !== "/login"){
				$player->sendMessage($this->plugin->getFromConfig("Command.Preprocess"));
				$event->setCancelled();
			}
		}
	}
	
	public function onQuit(PlayerQuitEvent $event){
		$player = $event->getPlayer();
		if($this->plugin->isRegistered($player)){
			$this->plugin->unRegister($player);
		}
		if(isset($this->plugin->errors[$player->getRawUniqueId()])){
			unset($this->plugin->errors[$player->getRawUniqueId()]);
		}
	}
}
