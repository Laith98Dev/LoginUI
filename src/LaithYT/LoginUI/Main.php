<?php

namespace LaithYT\LoginUI;

use pocketmine\plugin\PluginBase;

use pocketmine\event\player\{
	PlayerDropItemEvent,
	PlayerMoveEvent,
	PlayerQuitEvent,
	PlayerJoinEvent,
	PlayerCommandPreprocessEvent
	};

use pocketmine\{Player, Server};

use pocketmine\utils\{Config, TextFormat as TF};

use pocketmine\command\{CommandSender, Command};

use jojoe77777\FormAPI;
use jojoe77777\FormAPI\SimpleForm;
use jojoe77777\FormAPI\CustomForm;

class Main extends PluginBase {
	
	/** @var array         */
	public $Registered = [];
	
	/** @var array         */
	public $errors = [];
	
	public function onEnable(){
		@mkdir($this->getDataFolder());
		$this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
		if(!is_file($this->getDataFolder() . "config.yml")){
			$this->saveResource("config.yml");
		}
		
		if(!is_file($this->getDataFolder() . "Password.yml")){
			$this->saveResource("Password.yml");
		}
	}

	public function onCommand(CommandSender $sender, Command $command, $label, array $args) : bool{
		if($command->getName() === "login"){
			if($sender instanceof Player){
				if(isset($args[0])){
					$pass = $args[0];
					if(!$this->isRegistered($sender)){
						if($this->checkPassword($sender, $pass)){
							$this->Register($sender);
						}
					}
				} else {
					if(!$this->isRegistered($sender)){
						$this->MainForm($sender);
					}
				}
			} else {
				$sender->sendMessage(TF::RED . "Cannot use the command here!");//TODO: if player not in server
			}
		}
		return true;
	}
	
	public function getFromConfig(string $item){
		$cfg = new Config($this->getDataFolder() . "config.yml", Config::YAML);
		$i = $cfg->get($item);
		if($i){
			return $i;
		}
		return null;
	}
	
	public function setTimer($player){
		if($this->getFromConfig("timed.out")){
			$time = $this->getFromConfig("time.kick");
			$this->getScheduler()->scheduleDelayedTask(new \LaithYT\LoginUI\Timer($this, $player), $time);//TODO: kick player when time ended
		}
	}
	
	public function MainForm($player){
		$form = new SimpleForm(function (Player $player, int $data = null){
			if($data === null){
				return true;
			}
			switch($data){
				case 0:
					if(!$this->haveAccount($player)){
						$player->sendMessage(TF::RED . "you not registered, please register");
						return true;
					}
					if(isset($this->errors[$player->getRawUniqueId()])){
						unset($this->errors[$player->getRawUniqueId()]);
					}
					$this->LoginForm($player);
				break;
				
				case 1:
					if(isset($this->errors[$player->getRawUniqueId()])){
						unset($this->errors[$player->getRawUniqueId()]);
					}
					$this->RegisterForm($player);
				break;
				
				case 2:
					if(!$this->haveAccount($player)){
						$player->sendMessage(TF::RED . "you not registered, please register");
						return true;
					}
					if(isset($this->errors[$player->getRawUniqueId()])){
						unset($this->errors[$player->getRawUniqueId()]);
					}
					$this->ChangePasForm1($player);
					
				break;
			}
		});
			
		$form->setTitle("LoginUI");
		$form->addButton("Login");
		$form->addButton("Register");
		$form->addButton("Change Password");
		$form->sendToPlayer($player);
		return $form;
	}
	
	public function ChangePasForm1($player){
		$form = new CustomForm(function (Player $player, array $data = null){
			if($data === null){
				return true;
			}
			
			if($data[0] !== null){
				$pass = $data[0];
				if($this->checkPassword($player, $pass)){
					if(isset($this->errors[$player->getRawUniqueId()])){
						unset($this->errors[$player->getRawUniqueId()]);
					}
					$this->ChangePasForm2($player);
				} else {
					$this->errors[$player->getRawUniqueId()] = TF::RED . "Invalid password!";
					$this->ChangePasForm1($player);
				}
			}
		});
			
		$form->setTitle("Login");
		if(isset($this->errors[$player->getRawUniqueId()])){
			$hold = $this->errors[$player->getRawUniqueId()];
			$form->addInput("Current Password", $hold);
		} else {
			$form->addInput("Current Password");
		}
		$form->sendToPlayer($player);
		return $form;
	}
	
	public function ChangePasForm2($player){
		$form = new CustomForm(function (Player $player, array $data = null){
			if($data === null){
				return true;
			}
			
			if($data[0] !== null){
				$pass = $data[0];
				$c = strlen($pass);
				if($c < 4 || $c > 10){
					$this->errors[$player->getRawUniqueId()] = TF::RED . "password must contain 4-10";
					$this->ChangePasForm2($player);
					return false;
				}
				$cfg = new Config($this->getDataFolder() . "Password.yml", Config::YAML);
				$cfg->set($player->getName(), $pass);
				$cfg->save();
				$player->addTitle(TF::YELLOW . "Sucessfully", TF::GREEN . "Changed Password!");
				$this->Register($player);
			}
		});
			
		$form->setTitle("Login");
		if(isset($this->errors[$player->getRawUniqueId()])){
			$hold = $this->errors[$player->getRawUniqueId()];
			$form->addInput("New Password", $hold);
		} else {
			$form->addInput("New Password");
		}
		$form->sendToPlayer($player);
		return $form;
	}
	
	public function LoginForm($player){
		$form = new CustomForm(function (Player $player, array $data = null){
			if($data === null){
				return true;
			}
			
			if($data[0] !== null){
				$pass = $data[0];
				if($this->checkPassword($player, $pass)){
					$this->Register($player);
				} else {
					$this->errors[$player->getRawUniqueId()] = TF::RED . "Invalid password!";
					$this->LoginForm($player);
				}
			}
		});
			
		$form->setTitle("Login");
		if(isset($this->errors[$player->getRawUniqueId()])){
			$hold = $this->errors[$player->getRawUniqueId()];
			$form->addInput("Password", $hold);
		} else {
			$form->addInput("Password");
		}
		$form->sendToPlayer($player);
		return $form;
	}
	
	public function RegisterForm($player){
		$form = new CustomForm(function (Player $player, array $data = null){
			if($data === null){
				return true;
			}
			
			if($data[0] !== null){
				$pass = $data[0];
				if(!$this->haveAccount($player)){
					$this->newAccount($player, $pass);
				} else {
					$player->addTitle(TF::RED . "You Already", TF::GREEN . "Registered");
				}
			}
		});
			
		$form->setTitle("Register");
		if(isset($this->errors[$player->getRawUniqueId()])){
			$hold = $this->errors[$player->getRawUniqueId()];
			$form->addInput("Password", $hold);
		} else {
			$form->addInput("Password");
		}
		$form->sendToPlayer($player);
		return $form;
	}
	
	public function checkPassword($player, string $pass): bool{
		$cfg = new Config($this->getDataFolder() . "Password.yml", Config::YAML);
		$all = $cfg->getAll();
		foreach($all as $user => $pas){
			if($user == $player->getName()){
				if($pas == $pass){
					return true;
				}
			}
		}
		return false;
	}
	
	public function isRegistered($player): bool{
		return isset($this->Registered[$player->getRawUniqueId()]) ? true : false;
	}
	
	public function haveAccount($player): bool{
		$cfg = new Config($this->getDataFolder() . "Password.yml", Config::YAML);
		$pass = $cfg->get($player->getName());
		if($pass){
			return true;
		}
		return false;
	}
	
	public function newAccount($player, string $pass): bool{
		$cfg = new Config($this->getDataFolder() . "Password.yml", Config::YAML);
		if($cfg->get($player->getName())){
			return false;
		}
		$c = strlen($pass);
		if($c < 4 || $c > 10){
			$this->errors[$player->getRawUniqueId()] = TF::RED . "password must contain 4-10";
			$this->RegisterForm($player);
			return false;
		}
		$cfg->set($player->getName(), $pass);
		$cfg->save();
		$this->Register($player);
		return true;
	}
	
	public function Register($player): bool{
		if($this->isRegistered($player))return false;
		$this->Registered[$player->getRawUniqueId()] = "Y";
		if(isset($this->errors[$player->getRawUniqueId()])){
			unset($this->errors[$player->getRawUniqueId()]);
		}
		$player->addTitle(TF::YELLOW . "Sucessfully", TF::GREEN . "Logined!");
		return true;
	}
	
	public function unRegister($player): bool{
		if($this->isRegistered($player)){
			unset($this->Registered[$player->getRawUniqueId()]);
			return true;
		}
		return false;
	}
}
