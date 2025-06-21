<?php

namespace Mateo;

use pocketmine\plugin\PluginBase;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\Config;
use pocketmine\Player;

class Main extends PluginBase {

    public $streams = [];

    public function onEnable() {
        @mkdir($this->getDataFolder() . "data/");
        $this->saveResource("config.yml");
        $this->streams = new Config($this->getDataFolder() . "data/streams.yml", Config::YAML);
    }

    public function onCommand(CommandSender $s, Command $cmd, $label, array $args) {
        if(strtolower($cmd->getName()) == "stream") {
            if(!$s instanceof Player){
                $s->sendMessage("§cUse este comando no jogo.");
                return true;
            }

            if(!$s->hasPermission("stream.use")){
                $s->sendMessage("§cVocê não tem permissão para isso.");
                return true;
            }

            $cfg = new Config($this->getDataFolder() . "config.yml", Config::YAML);
            $name = $s->getName();

            if(!isset($args[0])){
                $s->sendMessage("§cUse: /stream on <yt/twitch/other> <url> | off | list");
                return true;
            }

            if(strtolower($args[0]) == "on"){
                if(isset($this->streams->getAll()[$name])){
                    $s->sendMessage("§eVocê já está em modo STREAMING.");
                    return true;
                }

                if(!isset($args[1]) or !isset($args[2])){
                    $s->sendMessage("§cUse: /stream on <yt/twitch/other> <url>");
                    return true;
                }

                $plataforma = strtoupper($args[1]);
                $url = $args[2];

                $this->streams->set($name, ["platform" => $plataforma, "url" => $url]);
                $this->streams->save();

                $msg = str_replace(
                    ["{player}", "{platform}", "{url}"],
                    [$name, $plataforma, $url],
                    $cfg->get("start_message")
                );

                $this->getServer()->broadcastMessage($msg);

                $tag = $s->getName();
                $pureChat = $this->getServer()->getPluginManager()->getPlugin("PureChat");

                if($pureChat !== null){
                    $tag = $pureChat->getNametag($s);
                }

                $s->setNameTag($cfg->get("stream_prefix") . "\n" . $tag);
                return true;

            } elseif(strtolower($args[0]) == "off") {
                if(!isset($this->streams->getAll()[$name])){
                    $s->sendMessage("§cVocê não está transmitindo.");
                    return true;
                }

                $this->streams->remove($name);
                $this->streams->save();

                $msg = str_replace("{player}", $name, $cfg->get("stop_message"));
                $this->getServer()->broadcastMessage($msg);

                $tag = $s->getName();
                $pureChat = $this->getServer()->getPluginManager()->getPlugin("PureChat");

                if($pureChat !== null){
                    $tag = $pureChat->getNametag($s);
                }

                $s->setNameTag($tag);
                return true;

            } elseif(strtolower($args[0]) == "list") {
                $all = $this->streams->getAll();

                if(empty($all)){
                    $s->sendMessage("§c» Nenhum jogador está transmitindo no momento.");
                    return true;
                }

                $s->sendMessage("§e» Jogadores transmitindo agora:");
                foreach($all as $streamer => $data){
                    $s->sendMessage("§a• §f" . $streamer . " §7(" . $data["platform"] . "): §b" . $data["url"]);
                }
                return true;

            } else {
                $s->sendMessage("§cUse: /stream on <yt/twitch/other> <url> | off | list");
                return true;
            }
        }

        return false;
    }
}
