<?php
namespace makeplus;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;

use pocketmine\Player;
use pocketmine\Server;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;

class makeplus extends PluginBase implements Listener{
	
	function onEnable(){
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}
	
	public function onCommand(CommandSender $sender, Command $command, $label, array $args) {
		switch(strtolower($label)){
			case "makeserverplus":
			case "makeplus":
			if(!$sender->isOP()) return true;
				$server = $sender->getServer();
		$pharPath = Server::getInstance()->getPluginPath()."GenisysPro" . DIRECTORY_SEPARATOR . $server->getName() . "_" . $server->getPocketMineVersion() . "_" . date("Y-m-d") . ".phar";//. DIRECTORY_SEPARATOR . 
		if(file_exists($pharPath)){
			$sender->sendMessage("Pharファイルは既に存在しているため、上書きします...");
			@unlink($pharPath);
		}
		$phar = new \Phar($pharPath,0);
		/*$phar->setMetadata([
			"name" => $server->getName(),
			"version" => $server->getPocketMineVersion(),
			"api" => $server->getApiVersion(),
			"minecraft" => $server->getVersion(),
			"protocol" => ProtocolInfo::CURRENT_PROTOCOL,//api系の競合を起こす
			"creationDate" => time()
		]);*/
		$phar->setStub('<?php define("pocketmine\\\\PATH", "phar://". __FILE__ ."/"); require_once("phar://". __FILE__ ."/src/pocketmine/PocketMine.php");  __HALT_COMPILER();');
		$phar->setSignatureAlgorithm(\Phar::SHA1);
		$phar->startBuffering();
		$files = [];
		$filePath = substr(\pocketmine\PATH, 0, 7) === "phar://" ? \pocketmine\PATH : realpath(\pocketmine\PATH) . "/";
		$filePath = rtrim(str_replace('\\', '/', $filePath), '/') . '/';
		if(is_dir($filePath . ".git")){
			// Add some Git files as they are required in getting GIT_COMMIT
			foreach(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($filePath . ".git")) as $file){
				$path = ltrim(str_replace(['\\', $filePath], ['/', ''], $file->getPathname()), '/');
				if((strpos($path, '.git/HEAD') === false and strpos($path, '.git/refs/heads') === false) or strpos($path, '/.') !== false){
					continue;
				}
				$files[$path] = $file->getPathname();
				$sender->sendMessage("[makeserver] リストに追加中 $path");
			}
		}
		foreach(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($filePath . "src")) as $file){
			$path = ltrim(str_replace(['\\', $filePath], ['/', ''], $file->getPathname()), '/');
			if($path{0} === "." or strpos($path, "/.") !== false or substr($path, 0, 4) !== "src/" or $file->isFile() === false){
				continue;
			}
			$files[$path] = $file->getPathname();
			$sender->sendMessage("[makeserver] リストに追加中... $path");//スピードを求めるのであればコメントアウトするべき
		}
		$sender->sendMessage("[makeserver] 圧縮しています...");
		$phar->buildFromIterator(new \ArrayIterator($files));
		foreach($phar as $file => $finfo){
			/** @var \PharFileInfo $finfo */
			if($finfo->getSize() > (1024 * 512)){
				$finfo->compress(\Phar::GZ);
			}
		}
		$phar->stopBuffering();
		$license = "
  _____            _               _____           
 / ____|          (_)             |  __ \          
| |  __  ___ _ __  _ ___ _   _ ___| |__) | __ ___  
| | |_ |/ _ \ '_ \| / __| | | / __|  ___/ '__/ _ \ 
| |__| |  __/ | | | \__ \ |_| \__ \ |   | | | (_) |
 \_____|\___|_| |_|_|___/\__, |___/_|   |_|  \___/ 
                         __/ |                    
                        |___/         
 ";
		$sender->sendMessage($license);//genisys proより引用したコードが含まれている為です。
		$sender->sendMessage($server->getName() . " " . $server->getPocketMineVersion() . " Pharファイルが作成されました " . $pharPath);

				return true;
			break;
		}
	}
}
