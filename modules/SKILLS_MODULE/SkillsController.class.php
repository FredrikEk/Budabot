<?php

namespace Budabot\User\Modules;

use DOMDocument;

/**
 * Authors: 
 *  - Tyrence (RK2)
 *
 * @Instance
 *
 * Commands this controller contains:
 *	@DefineCommand(
 *		command     = 'aggdef',
 *		accessLevel = 'all', 
 *		description = 'Agg/Def: Calculates weapon inits for your Agg/Def bar', 
 *		help        = 'aggdef.txt'
 *	)
 *	@DefineCommand(
 *		command     = 'aimshot',
 *		accessLevel = 'all', 
 *		description = 'Aim Shot: Calculates Aimed Shot', 
 *		help        = 'aimshot.txt'
 *	)
 *	@DefineCommand(
 *		command     = 'nanoinit',
 *		accessLevel = 'all', 
 *		description = 'Nanoinit: Calculates Nano Init', 
 *		help        = 'nanoinit.txt'
 *	)
 *	@DefineCommand(
 *		command     = 'fullauto',
 *		accessLevel = 'all', 
 *		description = 'Fullauto: Calculates Full Auto recharge', 
 *		help        = 'fullauto.txt'
 *	)
 *	@DefineCommand(
 *		command     = 'burst',
 *		accessLevel = 'all', 
 *		description = 'Burst: Calculates Burst', 
 *		help        = 'burst.txt'
 *	)
 *	@DefineCommand(
 *		command     = 'fling',
 *		accessLevel = 'all', 
 *		description = 'Fling: Calculates Fling', 
 *		help        = 'fling.txt'
 *	)
 *	@DefineCommand(
 *		command     = 'mafist',
 *		accessLevel = 'all', 
 *		description = 'MA Fist: Calculates your fist speed', 
 *		help        = 'mafist.txt'
 *	)
 *	@DefineCommand(
 *		command     = 'dimach',
 *		accessLevel = 'all', 
 *		description = 'Dimach: Calculates dimach facts', 
 *		help        = 'dimach.txt'
 *	)
 *	@DefineCommand(
 *		command     = 'brawl',
 *		accessLevel = 'all', 
 *		description = 'Brawl: Calculates brawl facts', 
 *		help        = 'brawl.txt'
 *	)
 *	@DefineCommand(
 *		command     = 'fastattack',
 *		accessLevel = 'all', 
 *		description = 'Fastattack: Calculates Fast Attack recharge', 
 *		help        = 'fastattack.txt'
 *	)
 *	@DefineCommand(
 *		command     = 'weapon',
 *		accessLevel = 'all', 
 *		description = 'Shows weapon info (skill cap specials recycle and aggdef positions)', 
 *		help        = 'weapon.txt'
 *	)
 */
class SkillsController {

	/**
	 * Name of the module.
	 * Set automatically by module loader.
	 */
	public $moduleName;
	
	/** @Inject */
	public $db;

	/** @Inject */
	public $http;

	/** @Inject */
	public $text;
	
	/** @Inject */
	public $util;
	
	/** @Inject */
	public $itemsController;
	
	/** @Inject */
	public $commandAlias;
	
	/**
	 * This handler is called on bot startup.
	 * @Setup
	 */
	public function setup() {
		$this->db->loadSQLFile($this->moduleName, "weapon_attributes");
	
		$this->commandAlias->register($this->moduleName, "weapon", "specials");
		$this->commandAlias->register($this->moduleName, "weapon", "inits");
		$this->commandAlias->register($this->moduleName, "aimshot", "as");
		$this->commandAlias->register($this->moduleName, "aimedshot", "as");
	}
	
	/**
	 * @HandlesCommand("aggdef")
	 * @Matches("/^aggdef ([0-9]*\.?[0-9]+) ([0-9]*\.?[0-9]+) ([0-9]+)$/i")
	 */
	public function aggdefCommand($message, $channel, $sender, $sendto, $args) {
		$AttTim = $args[1];
		$RechT = $args[2];
		$InitS = $args[3];

		$blob = $this->getAggDefOutput($AttTim, $RechT, $InitS);

		$msg = $this->text->makeBlob("Agg/Def Results", $blob);
		$sendto->reply($msg);
	}
	
	public function getAggDefOutput($AttTim, $RechT, $InitS) {
		if ($InitS < 1200) {
			$AttCalc	= round(((($AttTim - ($InitS / 600)) - 1)/0.02) + 87.5, 0);
			$RechCalc	= round(((($RechT - ($InitS / 300)) - 1)/0.02) + 87.5, 0);
		} else {
			$InitSk = $InitS - 1200;
			$AttCalc = round(((($AttTim - (1200/600) - ($InitSk / 600 / 3)) - 1)/0.02) + 87.5, 0);
			$RechCalc = round(((($RechT - (1200/300) - ($InitSk / 300 / 3)) - 1)/0.02) + 87.5, 0);
		}

		if ($AttCalc < $RechCalc) {
			$InitResult = $RechCalc;
		} else {
			$InitResult = $AttCalc;
		}
		if ($InitResult < 0) {
			$InitResult = 0;
		} else if ($InitResult > 100 ) {
			$InitResult = 100;
		}

		$initsFullAgg = $this->getInitsNeededFullAgg($AttTim, $RechT);
		$initsNeutral = $this->getInitsNeededNeutral($AttTim, $RechT);
		$initsFullDef = $this->getInitsNeededFullDef($AttTim, $RechT);

		$blob = "Attack:<highlight> ". $AttTim ." <end>second(s)\n";
		$blob .= "Recharge: <highlight>". $RechT ." <end>second(s)\n";
		$blob .= "Init Skill: <highlight>". $InitS ."<end>\n";
		$blob .= "Def/Agg: <highlight>". $InitResult ."%<end>\n";
		$blob .= "You must set your AGG bar at <highlight>". $InitResult ."% (". round($InitResult*8/100,2) .") <end>to wield your weapon at 1/1.\n\n";
		$blob .= "Init needed for max speed at Full Agg (100%): <highlight>". $initsFullAgg ." <end>inits\n";
		$blob .= "Init needed for max speed at Neutral (88%): <highlight>". $initsNeutral ." <end>inits\n";
		$blob .= "Init needed for max speed at Full Def (0%): <highlight>". $initsFullDef ." <end>inits\n\n";
		$blob .= "Note that at the neutral position (88%), your attack and recharge time will match that of the weapon you are using.";
		$blob .= "\n\nBased upon a RINGBOT module made by NoGoal(RK2)\n";
		$blob .= "Modified for Budabot by Healnjoo(RK2)";
		
		return $blob;
	}
	
	public function getInitsNeededFullAgg($AttTim, $RechT) {
		$Initatta1 = round((((100 - 87.5) * 0.02) + 1 - $AttTim) * (-600), 0);
		$Initrech1 = round((((100 - 87.5) * 0.02) + 1 - $RechT) * (-300), 0);
		if ($Initatta1 > 1200) {
			$Initatta1 = round(((((100 - 87.5) * 0.02) + 1 - $AttTim + 2) * (-1800)) + 1200, 0);
		}
		if ($Initrech1 > 1200) {
			$Initrech1 = round(((((100 - 87.5) * 0.02) + 1 - $AttTim + 4) * (-900)) + 1200, 0);
		}
		if ($Initatta1 < $Initrech1) {
			$Init1 = $Initrech1;
		} else {
			$Init1 = $Initatta1;
		}
		return $Init1;
	}
	
	public function getInitsNeededNeutral($AttTim, $RechT) {
		$Initatta2 = round((((87.5 - 87.5) * 0.02) + 1 - $AttTim) * (-600), 0);
		$Initrech2 = round((((87.5 - 87.5) * 0.02) + 1 - $RechT) * (-300), 0);
		if ($Initatta2 > 1200) {
			$Initatta2 = round(((((87.5 - 87.5) * 0.02) + 1 - $AttTim + 2) * (-1800)) + 1200, 0);
		}
		if ($Initrech2 > 1200) {
			$Initrech2 = round(((((87.5 - 87.5) * 0.02) + 1 - $AttTim + 4) * (-900)) + 1200, 0);
		}
		if ($Initatta2 < $Initrech2) {
			$Init2 = $Initrech2;
		} else {
			$Init2 = $Initatta2;
		}
		return $Init2;
	}
	
	public function getInitsNeededFullDef($AttTim, $RechT) {
		$Initatta3 = round((((-87.5) * 0.02) + 1 - $AttTim) * (-600), 0);
		$Initrech3 = round((((-87.5) * 0.02) + 1 - $RechT) * (-300), 0);
		if ($Initatta3 > 1200) {
			$Initatta3 = round(((((-87.5) * 0.02) + 1 - $AttTim + 2) * (-1800)) + 1200, 0);
		}
		if ($Initrech3 > 1200) {
			$Initrech3 = round(((((-87.5) * 0.02) + 1 - $AttTim + 4) * (-900)) + 1200, 0);
		}
		if ($Initatta3 < $Initrech3) {
			$Init3 = $Initrech3;
		} else {
			$Init3 = $Initatta3;
		}
		return $Init3;
	}
	
	/**
	 * @HandlesCommand("aimshot")
	 * @Matches("/^aimshot ([0-9]*\.?[0-9]+) ([0-9]*\.?[0-9]+) ([0-9]+)$/i")
	 */
	public function aimshotCommand($message, $channel, $sender, $sendto, $args) {
		$AttTim = $args[1];
		$RechT = $args[2];
		$InitS = $args[3];

		list($cap, $ASCap) = $this->cap_aimed_shot($AttTim, $RechT);

		$ASRech	= ceil(($RechT * 40) - ($InitS * 3 / 100) + $AttTim - 1);
		if ($ASRech < $cap) {
			$ASRech = $cap;
		}
		$MultiP	= round($InitS / 95,0);

		$blob = "Attack: <highlight>". $AttTim ." <end>second(s)\n";
		$blob .= "Recharge: <highlight>". $RechT ." <end>second(s)\n";
		$blob .= "Aim Shot Skill: <highlight>". $InitS ."<end>\n\n";
		$blob .= "Aim Shot Multiplier:<highlight> 1-". $MultiP ."x<end>\n";
		$blob .= "Aim Shot Recharge: <highlight>". $ASRech ."<end> seconds\n";
		$blob .= "With your weap, your Aim Shot recharge will cap at <highlight>".$cap."<end>s.\n";
		$blob .= "You need <highlight>".$ASCap."<end> Aim Shot skill to cap your recharge.";

		$msg = $this->text->makeBlob("Aim Shot Results", $blob);
		$sendto->reply($msg);
	}
	
	/**
	 * @HandlesCommand("brawl")
	 * @Matches("/^brawl ([0-9]+)$/i")
	 */
	public function brawlCommand($message, $channel, $sender, $sendto, $args) {
		$brawl_skill = $args[1];

		$skill_list = array( 1, 1000, 1001, 2000, 2001, 3000);
		$min_list	= array( 1,  100,  101,  170,  171,  235);
		$max_list	= array( 2,  500,  501,  850,  851, 1145);
		$crit_list	= array( 3,  500,  501,  600,  601,  725);

		if ($brawl_skill < 1001) {
			$i = 0;
		} else if ($brawl_skill < 2001) {
			$i = 2;
		} else {
			$i = 4;
		}

		$min  = $this->util->interpolate($skill_list[$i], $skill_list[($i+1)], $min_list[$i], $min_list[($i+1)], $brawl_skill);
		$max  = $this->util->interpolate($skill_list[$i], $skill_list[($i+1)], $max_list[$i], $max_list[($i+1)], $brawl_skill);
		$crit = $this->util->interpolate($skill_list[$i], $skill_list[($i+1)], $crit_list[$i], $crit_list[($i+1)], $brawl_skill);
		$stunC = (($brawl_skill < 1000) ? "<orange>10<end>%, <font color=#cccccc>will become </font>20<font color=#cccccc>% above </font>1000<font color=#cccccc> brawl skill</font>" : "<orange>20<end>%");
		$stunD = (($brawl_skill < 2001) ?  "<orange>3<end>s, <font color=#cccccc>will become </font>4<font color=#cccccc>s above </font>2001<font color=#cccccc> brawl skill</font>" :  "<orange>4<end>s");

		$blob = "Brawl Skill: <highlight>".$brawl_skill."<end>\n";
		$blob .= "Brawl recharge: <highlight>15<end> seconds <font color=#ccccc>(constant)</font>\n";
		$blob .= "Damage: <highlight>".$min."<end>-<highlight>".$max."<end>(<highlight>".$crit."<end>)\n";
		$blob .= "Stun chance: ".$stunC."\n";
		$blob .= "Stun duration: ".$stunD."\n";
		$blob .= "\n\nby Imoutochan, RK1";

		$msg = $this->text->makeBlob("Brawl Results", $blob);
		$sendto->reply($msg);
	}
	
	/**
	 * @HandlesCommand("burst")
	 * @Matches("/^burst ([0-9]*\.?[0-9]+) ([0-9]*\.?[0-9]+) ([0-9]+) ([0-9]+)$/i")
	 */
	public function burstCommand($message, $channel, $sender, $sendto, $args) {
		$AttTim = $args[1];
		$RechT = $args[2];
		$BurstDelay = $args[3];
		$BurstSkill = $args[4];

		list($cap, $burstskillcap) = $this->cap_burst($AttTim, $RechT, $BurstDelay);

		$burstrech = floor(($RechT * 20) + ($BurstDelay / 100) - ($BurstSkill / 25) + $AttTim);
		if ($burstrech <= $cap) {
			$burstrech = $cap;
		}

		$blob = "Attack: <highlight>". $AttTim ." <end>second(s)\n";
		$blob .= "Recharge: <highlight>". $RechT ." <end>second(s)\n";
		$blob .= "Burst Delay: <highlight>". $BurstDelay ."<end>\n";
		$blob .= "Burst Skill: <highlight>". $BurstSkill ."<end>\n\n";
		$blob .= "Your Burst Recharge:<highlight> ". $burstrech ."<end>s\n";
		$blob .= "With your weap, your burst recharge will cap at <highlight>".$cap."<end>s.\n";
		$blob .= "You need <highlight>".$burstskillcap."<end> Burst Skill to cap your recharge.";

		$msg = $this->text->makeBlob("Burst Results", $blob);
		$sendto->reply($msg);
	}
	
	/**
	 * @HandlesCommand("dimach")
	 * @Matches("/^dimach ([0-9]+)$/i")
	 */
	public function dimachCommand($message, $channel, $sender, $sendto, $args) {
		$dim_skill = $args[1];

		$skill_list	= array(   1, 1000, 1001, 2000, 2001, 3000);
		$gen_dmg_list	= array(   1, 2000, 2001, 2500, 2501, 2850);
		$MA_rech_list	= array(1800, 1800, 1188,  600,  600,  300);
		$MA_dmg_list	= array(   1, 2000, 2001, 2340, 2341, 2550);
		$shad_rech_list = array( 300,  300,  300,  300,  240,  200);
		$shad_dmg_list	= array(   1,  920,  921, 1872, 1873, 2750);
		$shad_rec_list	= array(  70,   70,   70,   75,   75,   80);
		$keep_heal_list = array(   1, 3000, 3001,10500,10501,30000);

		if ($dim_skill < 1001) {
			$i = 0;
		} else if ($dim_skill < 2001) {
			$i = 2;
		} else {
			$i = 4;
		}

		$blob = "Dimach Skill: <highlight>".$dim_skill."<end>\n\n";

		$MA_dmg = $this->util->interpolate($skill_list[$i], $skill_list[($i+1)], $MA_dmg_list[$i],  $MA_dmg_list[($i+1)],  $dim_skill);
		$MA_dim_rech = $this->util->interpolate($skill_list[$i], $skill_list[($i+1)], $MA_rech_list[$i], $MA_rech_list[($i+1)], $dim_skill);
		$blob .= "Profession: <highlight>Martial Artist<end>\n";
		$blob .= "Damage: <highlight>".$MA_dmg."<end>-<highlight>".$MA_dmg."<end>(<highlight>1<end>)\n";
		$blob .= "Recharge ".$this->util->unixtimeToReadable($MA_dim_rech)."\n\n";

		$keep_heal	= $this->util->interpolate($skill_list[$i], $skill_list[($i+1)], $keep_heal_list[$i],$keep_heal_list[($i+1)], $dim_skill);
		$blob .= "Profession: <highlight>Keeper<end>\n";
		$blob .= "Self heal: <font color=#ff9999>".$keep_heal."</font> HP\n";
		$blob .= "Recharge: <highlight>5<end> minutes <font color=#ccccc>(constant)</font>\n\n";

		$shad_dmg	= $this->util->interpolate($skill_list[$i], $skill_list[($i+1)], $shad_dmg_list[$i], $shad_dmg_list[($i+1)],  $dim_skill);
		$shad_rec	= $this->util->interpolate($skill_list[$i], $skill_list[($i+1)], $shad_rec_list[$i], $shad_rec_list[($i+1)],  $dim_skill);
		$shad_dim_rech	= $this->util->interpolate($skill_list[$i], $skill_list[($i+1)], $shad_rech_list[$i], $shad_rech_list[($i+1)], $dim_skill);
		$blob .= "Profession: <highlight>Shade<end>\n";
		$blob .= "Damage: <highlight>".$shad_dmg."<end>-<highlight>".$shad_dmg."<end>(<highlight>1<end>)\n";
		$blob .= "HP drain: <font color=#ff9999>".$shad_rec."</font>%\n";
		$blob .= "Recharge ".$this->util->unixtimeToReadable($shad_dim_rech)."\n\n";

		$gen_dmg = $this->util->interpolate($skill_list[$i], $skill_list[($i+1)], $gen_dmg_list[$i],  $gen_dmg_list[($i+1)], $dim_skill);
		$blob .= "Profession: <highlight>All professions besides MA, Shade and Keeper<end>\n";
		$blob .= "Damage: <highlight>".$gen_dmg."<end>-<highlight>".$gen_dmg."<end>(<highlight>1<end>)\n";
		$blob .= "Recharge: <highlight>30<end> minutes <font color=#ccccc>(constant)</font>\n\n";

		$blob .= "by Imoutochan, RK1";

		$msg = $this->text->makeBlob("Dimach Results", $blob);
		$sendto->reply($msg);
	}
	
	/**
	 * @HandlesCommand("fastattack")
	 * @Matches("/^fastattack ([0-9]*\.?[0-9]+) ([0-9]+)$/i")
	 */
	public function fastattackCommand($message, $channel, $sender, $sendto, $args) {
		$AttTim = $args[1];
		$fastSkill = $args[2];

		list($fasthardcap, $fastskillcap) = $this->cap_fast_attack($AttTim);

		$fastrech =  round(($AttTim * 16) - ($fastSkill / 100));

		if ($fastrech < $fasthardcap) {
			$fastrech = $fasthardcap;
		}

		$blob = "Attack: <highlight>". $AttTim ." <end>second(s)\n";
		$blob .= "Fast Attack Skill: <highlight>". $fastSkill ."<end>\n";
		$blob .= "Fast Attack Recharge: <highlight>". $fastrech ."<end>s\n";
		$blob .= "You need <highlight>".$fastskillcap."<end> Fast Attack Skill to cap your fast attack at <highlight>".$fasthardcap."<end>s.";

		$msg = $this->text->makeBlob("Fast Attack Results", $blob);
		$sendto->reply($msg);
	}
	
	/**
	 * @HandlesCommand("fling")
	 * @Matches("/^fling ([0-9]*\.?[0-9]+) ([0-9]+)$/i")
	 */
	public function flingCommand($message, $channel, $sender, $sendto, $args) {
		$AttTim = $args[1];
		$FlingSkill = $args[2];

		list($flinghardcap, $flingskillcap) = $this->cap_fling_shot($AttTim);

		$flingrech =  round(($AttTim * 16) - ($FlingSkill / 100));

		if ($flingrech < $flinghardcap) {
			$flingrech = $flinghardcap;
		}

		$blob = "Attack: <highlight>{$AttTim}<end> second(s)\n";
		$blob .= "Fling Shot Skill: <highlight>{$FlingSkill}<end>\n";
		$blob .= "Fling Shot Recharge: <highlight>{$flingrech}<end> second(s)\n";
		$blob .= "You need <highlight>{$flingskillcap}<end> Fling Shot skill to cap your fling at <highlight>{$flinghardcap}<end> second(s).";

		$msg = $this->text->makeBlob("Fling Results", $blob);
		$sendto->reply($msg);
	}
	
	/**
	 * @HandlesCommand("fullauto")
	 * @Matches("/^fullauto ([0-9]*\.?[0-9]+) ([0-9]*\.?[0-9]+) ([0-9]+) ([0-9]+)$/i")
	 */
	public function fullautoCommand($message, $channel, $sender, $sendto, $args) {
		$AttTim = $args[1];
		$RechT = $args[2];
		$FARecharge = $args[3];
		$FullAutoSkill = $args[4];

		list($FACap, $FA_Skill_Cap) = $this->cap_full_auto($AttTim, $RechT, $FARecharge);

		$FA_Recharge = round(($RechT * 40) + ($FARecharge / 100) - ($FullAutoSkill / 25) + round($AttTim - 1));
		if ($FA_Recharge < $FACap) {
			$FA_Recharge = $FACap;
		}

		$MaxBullets = 5 + floor($FullAutoSkill / 100);

		$blob = "Weapon Attack: <highlight>". $AttTim ."<end>s\n";
		$blob .= "Weapon Recharge: <highlight>". $RechT ."<end>s\n";
		$blob .= "Full Auto Recharge value: <highlight>". $FARecharge ."<end>\n";
		$blob .= "FA Skill: <highlight>". $FullAutoSkill ."<end>\n\n";
		$blob .= "Your Full Auto recharge:<highlight> ". $FA_Recharge ."s<end>\n";
		$blob .= "Your Full Auto can fire a maximum of <highlight>".$MaxBullets." bullets<end>.\n";
		$blob .= "Full Auto recharge always caps at <highlight>".$FACap."<end>s.\n";
		$blob .= "You will need at least <highlight>".$FA_Skill_Cap."<end> Full Auto skill to cap your recharge.\n\n";
		$blob .= "From <highlight>0 to 10K<end> damage, the bullet damage is unchanged.\n";
		$blob .= "From <highlight>10K to 11.5K<end> damage, each bullet damage is halved.\n";
		$blob .= "From <highlight>11K to 15K<end> damage, each bullet damage is halved again.\n";
		$blob .= "<highlight>15K<end> is the damage cap.";

		$msg = $this->text->makeBlob("Full Auto Results", $blob);
		$sendto->reply($msg);
	}
	
	/**
	 * @HandlesCommand("mafist")
	 * @Matches("/^mafist ([0-9]+)$/i")
	 */
	public function mafistCommand($message, $channel, $sender, $sendto, $args) {
		$MaSkill = $args[1];

		// MA templates
		$skill_list = array(1,200,1000,1001,2000,2001,3000);

		$MA_min_list = array (4,45,125,130,220,225,450);
		$MA_max_list = array (8,75,400,405,830,831,1300);
		$MA_crit_list = array(3,50,500,501,560,561,800);
		$MA_fist_speed = array(1.15,1.25,1.25,1.30,1.35,1.45,1.50);

		$shade_min_list = array (3,25,55,56,130,131,280);
		$shade_max_list = array (5,60,258,259,682,683,890);
		$shade_crit_list = array(3,50,250,251,275,276,300);

		$gen_min_list = array (3,25,65,66,140,141,300);
		$gen_max_list = array (5,60,280,281,715,716,990);
		$gen_crit_list = array(3,50,500,501,605,605,630);

		if ($MaSkill < 200) {
			$i = 0;
		} else if ($MaSkill < 1001) {
			$i = 1;
		} else if ($MaSkill < 2001) {
			$i = 3;
		} else {
			$i = 5;
		}

		$fistql = round($MaSkill / 2, 0);
		if ($fistql <= 200) {
			$speed = 1.25;
		} else if ($fistql <= 500) {
			$speed = 1.25 + (0.2 * (($fistql - 200) / 300));
		} else if ($fistql <= 1000)	{
			$speed = 1.45 + (0.2 * (($fistql - 500) / 500));
		} else if ($fistql <= 1500)	{
			$speed = 1.65 + (0.2 * (($fistql - 1000) / 500));
		}
		$speed = round($speed,2);

		$blob = "MA Skill: <highlight>". $MaSkill ."<end>\n\n";
		
		$min = $this->util->interpolate($skill_list[$i], $skill_list[($i + 1)], $MA_min_list[$i], $MA_min_list[($i + 1)], $MaSkill);
		$max = $this->util->interpolate($skill_list[$i], $skill_list[($i + 1)], $MA_max_list[$i], $MA_max_list[($i + 1)], $MaSkill);
		$crit = $this->util->interpolate($skill_list[$i], $skill_list[($i + 1)], $MA_crit_list[$i], $MA_crit_list[($i + 1)], $MaSkill);
		//$ma_speed = $this->util->interpolate($skill_list[$i], $skill_list[($i + 1)], $MA_fist_speed[$i], $MA_fist_speed[($i + 1)], $MaSkill);
		$ma_spd = (($maskill - $skill_list[$i]) * ($MA_fist_speed[($i + 1)] - $MA_fist_speed[$i])) / ($skill_list[($i + 1)] - $skill_list[$i]) + $MA_fist_speed[$i];
		$ma_speed = round($ma_spd, 2);
		$dmg = "<highlight>".$min."<end>-<highlight>".$max."<end>(<highlight>".$crit."<end>)";
		$blob .= "Profession: <highlight>Martial Artist<end>\n";
		$blob .= "Fist speed: <highlight>".$ma_speed."<end>s/<highlight>".$ma_speed."<end>s\n";
		$blob .= "Fist damage: ".$dmg."\n\n\n";

		$blob .= "Fist speed for other professions: <highlight>".$speed."<end>s/<highlight>".$speed."<end>s\n\n";
		$min = $this->util->interpolate($skill_list[$i], $skill_list[($i + 1)], $shade_min_list[$i], $shade_min_list[($i + 1)], $MaSkill);
		$max = $this->util->interpolate($skill_list[$i], $skill_list[($i + 1)], $shade_max_list[$i], $shade_max_list[($i + 1)], $MaSkill);
		$crit = $this->util->interpolate($skill_list[$i], $skill_list[($i + 1)], $shade_crit_list[$i], $shade_crit_list[($i + 1)], $MaSkill);
		$dmg = "<highlight>".$min."<end>-<highlight>".$max."<end>(<highlight>".$crit."<end>)";
		$blob .= "Profession: <highlight>Shade<end>\n";
		$blob .= "Fist damage: ".$dmg."\n\n";

		$min = $this->util->interpolate($skill_list[$i], $skill_list[($i + 1)], $gen_min_list[$i], $gen_min_list[($i + 1)], $MaSkill);
		$max = $this->util->interpolate($skill_list[$i], $skill_list[($i + 1)], $gen_max_list[$i], $gen_max_list[($i + 1)], $MaSkill);
		$crit = $this->util->interpolate($skill_list[$i], $skill_list[($i + 1)], $gen_crit_list[$i], $gen_crit_list[($i + 1)], $MaSkill);
		$dmg = "<highlight>".$min."<end>-<highlight>".$max."<end>(<highlight>".$crit."<end>)";
		$blob .= "Profession: <highlight>All professions besides MA and Shade<end>\n";
		$blob .= "Fist damage: ".$dmg."\n\n";

		$msg = $this->text->makeBlob("Martial Arts Results", $blob);
		$sendto->reply($msg);
	}
	
	/**
	 * @HandlesCommand("nanoinit")
	 * @Matches("/^nanoinit ([0-9]*\.?[0-9]+) ([0-9]+)$/i")
	 */
	public function nanoinitCommand($message, $channel, $sender, $sendto, $args) {
		$attack_time = $args[1];
		$init_skill = $args[2];

		$attack_time_reduction = $this->calc_attack_time_reduction($init_skill);
		$effective_attack_time = $attack_time - $attack_time_reduction;

		$bar_setting = $this->calc_bar_setting($effective_attack_time);
		if ($bar_setting < 0) {
			$bar_setting = 0;
		}
		if ($bar_setting > 100) {
			$bar_setting = 100;
		}

		$Init1 = $this->calc_inits($attack_time - 1);
		$Init2 = $this->calc_inits($attack_time);
		$Init3 = $this->calc_inits($attack_time + 1);

		$blob = "Attack: <highlight>". $attack_time ." <end>second(s)\n";
		$blob .= "Init Skill: <highlight>". $init_skill ."<end>\n";
		$blob .= "Def/Agg: <highlight>". $bar_setting ."%<end>\n";
		$blob .= "You must set your AGG bar at <highlight>". $bar_setting ."% (". round($bar_setting * 8 / 100,2) .") <end>to instacast your nano.\n\n";
		$blob .= "NanoC. Init needed to instacast at Full Agg (100%):<highlight> ". $Init1 ." <end>inits\n";
		$blob .= "NanoC. Init needed to instacast at Neutral (88%):<highlight> ". $Init2 ." <end>inits\n";
		$blob .= "NanoC. Init needed to instacast at Full Def (0%):<highlight> ". $Init3 ." <end>inits";

		$msg = $this->text->makeBlob("Nano Init Results", $blob);
		$sendto->reply($msg);
	}
	
	/**
	 * @HandlesCommand("weapon")
	 * @Matches('|^weapon <a href="itemref://(\d+)/(\d+)/(\d+)">|i')
	 * @Matches('|^weapon (\d+) (\d+)|i')
	 */
	public function weaponCommand($message, $channel, $sender, $sendto, $args) {
		if (count($args) == 4) {
			$highid = $args[2];
			$ql = $args[3];
		} else {
			$highid = $args[1];
			$ql = $args[2];
		}

		// this is a hack since Worn Soft Pepper Pistol has its high and low ids reversed in-game
		// there may be others
		$sql = "SELECT *, 1 AS order_col FROM aodb WHERE highid = ? AND lowql <= ? AND highql >= ? 
				UNION
				SELECT *, 2 AS order_col FROM aodb WHERE lowid = ? AND lowql <= ? AND highql >= ?
				ORDER BY order_col ASC";
		$row = $this->db->queryRow($sql, $highid, $ql, $ql, $highid, $ql, $ql);

		if ($row === null) {
			$msg = "Item does not exist in the items database.";
			$sendto->reply($msg);
			return;
		}

		$lowAttributes = $this->db->queryRow("SELECT * FROM weapon_attributes WHERE id = ?", $row->lowid);
		$highAttributes = $this->db->queryRow("SELECT * FROM weapon_attributes WHERE id = ?", $row->highid);

		if ($lowAttributes === null || $highAttributes === null) {
			$msg = "Could not find any weapon info for this item.";
			$sendto->reply($msg);
			return;
		}

		$name = $row->name;
		$attack_time = $this->util->interpolate($row->lowql, $row->highql, $lowAttributes->attack_time, $highAttributes->attack_time, $ql);
		$recharge_time = $this->util->interpolate($row->lowql, $row->highql, $lowAttributes->recharge_time, $highAttributes->recharge_time, $ql);
		$recharge_time /= 100;
		$attack_time /= 100;

		$blob = '';

		$blob .= "Attack: <highlight>$attack_time<end>\n";
		$blob .= "Recharge: <highlight>$recharge_time<end>\n\n";

		// inits
		$blob .= $this->getInitDisplay($attack_time, $recharge_time);
		$blob .= "\n\n";

		if ($highAttributes->full_auto !== null) {
			$full_auto_recharge = $this->util->interpolate($row->lowql, $row->highql, $lowAttributes->full_auto, $highAttributes->full_auto, $ql);
			list($hard_cap, $skill_cap) = $this->cap_full_auto($attack_time, $recharge_time, $full_auto_recharge);
			$blob .= "FullAutoRecharge: $full_auto_recharge -- You need <highlight>".$skill_cap."<end> Full Auto skill to cap your recharge at <highlight>".$hard_cap."<end>s.\n\n";
			$found = true;
		}
		if ($highAttributes->burst !== null) {
			$burst_recharge = $this->util->interpolate($row->lowql, $row->highql, $lowAttributes->burst, $highAttributes->burst, $ql);
			list($hard_cap, $skill_cap) = $this->cap_burst($attack_time, $recharge_time, $burst_recharge);
			$blob .= "BurstRecharge: $burst_recharge -- You need <highlight>".$skill_cap."<end> Burst skill to cap your recharge at <highlight>".$hard_cap."<end>s.\n\n";
			$found = true;
		}
		if ($highAttributes->fling_shot == 1) {
			list($hard_cap, $skill_cap) = $this->cap_fling_shot($attack_time);
			$blob .= "FlingRecharge: You need <highlight>".$skill_cap."<end> Fling Shot skill to cap your recharge at <highlight>".$hard_cap."<end>s.\n\n";
			$found = true;
		}
		if ($highAttributes->fast_attack == 1) {
			list($hard_cap, $skill_cap) = $this->cap_fast_attack($attack_time);
			$blob .= "FastAttackRecharge: You need <highlight>".$skill_cap."<end> Fast Attack skill to cap your recharge at <highlight>".$hard_cap."<end>s.\n\n";
			$found = true;
		}
		if ($highAttributes->aimed_shot == 1) {
			list($hard_cap, $skill_cap) = $this->cap_aimed_shot($attack_time, $recharge_time);
			$blob .= "AimedShotRecharge: You need <highlight>".$skill_cap."<end> Aimed Shot skill to cap your recharge at <highlight>".$hard_cap."<end>s.\n\n";
			$found = true;
		}

		// brawl and dimach don't depend on weapon at all
		// we don't have a formula for sneak attack

		if (!$found) {
			$blob .= "There are no specials on this weapon that could be calculated.\n\n";
		}

		$blob .= "Written by Tyrence (RK2)\n";
		$msg = $this->text->makeBlob("Weapon Info for $name", $blob);

		$sendto->reply($msg);
	}

	public function calc_attack_time_reduction($init_skill) {
		if ($init_skill > 1200) {
			$RechTk = $init_skill - 1200;
			$attack_time_reduction = ($RechTk / 600) + 6;
		} else {
			$attack_time_reduction = ($init_skill / 200);
		}

		return $attack_time_reduction;
	}

	public function calc_bar_setting($effective_attack_time) {
		if ($effective_attack_time < 0) {
			return 88 + (88 * $effective_attack_time);
		} else if ($effective_attack_time > 0) {
			return 88 + (12 * $effective_attack_time);
		} else {
			return 88;
		}
	}

	public function calc_inits($attack_time) {
		if ($attack_time < 0) {
			return 0;
		} else if ($attack_time < 6) {
			return round($attack_time * 200, 2);
		} else {
			return round(1200 + ($attack_time - 6) * 600, 2);
		}
	}

	public function cap_full_auto($attack_time, $recharge_time, $full_auto_recharge) {
		$hard_cap = floor(10 + $attack_time);
		$skill_cap = ((40 * $recharge_time) + ($full_auto_recharge / 100) - 11) * 25;

		return array($hard_cap, $skill_cap);
	}

	public function cap_burst($attack_time, $recharge_time, $burst_recharge) {
		$hard_cap = round($attack_time + 8,0);
		$skill_cap = floor((($recharge_time * 20) + ($burst_recharge / 100) - 8) * 25);

		return array($hard_cap, $skill_cap);
	}

	public function cap_fling_shot($attack_time) {
		$hard_cap = 5 + $attack_time;
		$skill_cap = (($attack_time * 16) - $hard_cap) * 100;

		return array($hard_cap, $skill_cap);
	}

	public function cap_fast_attack($attack_time) {
		$hard_cap = 5 + $attack_time;
		$skill_cap = (($attack_time * 16) - $hard_cap) * 100;

		return array($hard_cap, $skill_cap);
	}

	public function cap_aimed_shot($attack_time, $recharge_time) {
		$hard_cap = floor($attack_time + 10);
		$skill_cap = ceil((4000 * $recharge_time - 1100) / 3);
		//$skill_cap = round((($recharge_time * 4000) - ($attack_time * 100) - 1000) / 3);
		//$skill_cap = ceil(((4000 * $recharge_time) - 1000) / 3);

		return array($hard_cap, $skill_cap);
	}

    public function fireinit($n) {
		if ($n < 0) {
			return 1;
		} else {
			return round($n * 600);
		}
	}

    public function rechargeinit($n) {
		if ($n < 0) {
			return 1;
		} else {
			return round($n * 300);
		}
	}
	
	// taken from: https://bitbucket.org/Kilmanagh/ao-central/src/233fc3d9ce77d5004ef97d858136a21b87f50e8c/inits/inits.php?at=default
	public function getInitDisplay($attack, $recharge) {
		// 12 positions...
		$blob = '';
		for ($i = 11; $i > -1; $i--) {
			$perc = floor((100 / 11) * $i);
			$diminish = 1 / 3;
			$scale = 2 / 12;
			$factor = -1.25 + ($scale * (12 - $i));
			$init = max($this->fireinit($attack + $factor), $this->rechargeinit($recharge + $factor));
			if ($init > 1200) {
				$init = 1200 + (($init - 1200) / $diminish);
			}
			$init = ceil($init);

			$blob .= "DEF&gt;";
			for ($x = 0; $x < $i; $x++) {
				$blob .= "=";
			}
			$blob .= "][";
			for ($x = 12; $x > ($i + 1); $x--) {
				$blob .= "=";
			}
			$blob .= "&lt;AGG";
			$blob .= " $init ($perc%)\n";
		}
		return $blob;
	}
}
