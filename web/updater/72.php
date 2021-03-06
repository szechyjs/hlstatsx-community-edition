<?php
    if ( !defined('IN_UPDATER') )
    {
        die('Do not access this file directly.');
    }

    $dbversion = 72;
    $version = "1.6.17";
    $tfgames = array();
    $result = $db->query("SELECT code FROM hlstats_Games WHERE realgame = 'tf'");
    while ($rowdata = $db->fetch_row($result))
    { 
    array_push($tfgames, $db->escape($rowdata[0]));
    }
    
    // 1584 - Fix TF2 award names on existing sites
    foreach ($tfgames as $game)
    {
        print "Fixing award names for TF2 gamecode $game.<br />";
        $db->query("UPDATE IGNORE `hlstats_Awards` SET `name` = 'Lookin Good' WHERE `game` = '$game' AND `name` = 'Pretty Boy\'s Pocket Pistol'");
        $db->query("UPDATE IGNORE `hlstats_Awards` SET `name` = 'Baby Face' WHERE `game` = '$game' AND `name` = 'Baby Face\'s Blaster'");
        $db->query("UPDATE IGNORE `hlstats_Awards` SET `name` = 'Don\'t Beg' WHERE `game` = '$game' AND `name` = 'Beggar\'s Bazooka'");
        $db->query("UPDATE IGNORE `hlstats_Awards` SET `name` = 'Cleaning Time' WHERE `game` = '$game' AND `name` = 'The Cleaner\'s Carbine'");
        $db->query("UPDATE IGNORE `hlstats_Awards` SET `name` = 'Making Heat' WHERE `game` = '$game' AND `name` = 'The Hitman\'s Heatmaker'");
        $db->query("UPDATE IGNORE `hlstats_Awards` SET `name` = 'The Rainman' WHERE `game` = '$game' AND `name` = 'The Rainblower'");
        $db->query("UPDATE IGNORE `hlstats_Awards` SET `name` = 'Free Candy' WHERE `game` = '$game' AND `name` = 'The Lollichop'");
        $db->query("UPDATE IGNORE `hlstats_Awards` SET `name` = 'Scorcher' WHERE `game` = '$game' AND `name` = 'The Scorch Shot'");
    }
    // End 1584
    
     // Tracker #1583 - Add Team Fortress 2 weapon The Escape Plan
    print "Adding new Team Fortress 2 weapons.";
    // Weapons
    // Name is the name of the weapon
    // Verb is the "action" described on the award
    // Modifier is used to adjust points given for a kill with the weapon
    // Award name sets the name of the award
    $weapons = array(
        array(
            "weapon_code" => "unique_pickaxe_escape",
            "weapon_name" => "The Escape Plan",
            "award_verb" => "The Escape Plan kills",
            "modifier" => "1.00",
            "award_name" => "The Great Escape")
    );

    foreach ($tfgames as $game)
    {
        // Get list of all Team Fortress 2 servers so we can update weapon counts later.
        $tfservers = array();
        $result = $db->query("SELECT serverId FROM hlstats_Servers WHERE game = '$game'");
        while ($rowdata = $db->fetch_row($result))
        {
            array_push($tfservers, $db->escape($rowdata[0]));
        }
        if (count($tfservers) > 0)
        {
            $serverstring = implode (',', $tfservers);
        }

        // Insert actions
        print "Adding new actions for game $game.<br />";
        if (isset($actions) && count($actions) > 0)
        {
            $action_query = "INSERT IGNORE INTO `hlstats_Actions` (`game`, `code`, `reward_player`, `reward_team`, `team`, `description`, `for_PlayerActions`, `for_PlayerPlayerActions`, `for_TeamActions`, `for_WorldActions`) VALUES ";
            $award_query = "INSERT IGNORE INTO `hlstats_Awards` (`awardType`, `game`, `code`, `name`, `verb`) VALUES ";
            $ribbon_query = "INSERT IGNORE INTO `hlstats_Ribbons` (`awardCode`, `awardCount`, `special`, `game`, `image`, `ribbonName`) VALUES ";
            // Insert actions
            foreach ($actions as $key => $action)
            {
                // Insert actions into Actions table
                $action_query .= "(
                    '$game',
                    '".$db->escape($action['code'])."',
                    '".$db->escape($action['reward_player'])."',
                    '".$db->escape($action['reward_team'])."',
                    '".$db->escape($action['team'])."',
                    '".$db->escape($action['description'])."',
                    '".$db->escape($action['for_PlayerActions'])."',
                    '".$db->escape($action['for_PlayerPlayerActions'])."',
                    '".$db->escape($action['for_TeamActions'])."',
                    '".$db->escape($action['for_WorldActions'])."')" .
                    // Check to see if we're on the last key -- if so finish the SQL statement, otherwise leave it open to append
                    ($key == count($actions)-1 ? ";" : ",");
                
                if ($action['award_name'] != "")
                {
                    $award_query .= "(
                        '".$db->escape($action['award_type'])."',
                        '$game',
                        '".$db->escape($action['code'])."',
                        '".$db->escape($action['award_name'])."',
                        '".$db->escape($action['award_verb'])."')" .
                        // Check to see if we're on the last key -- if so finish the SQL statement, otherwise leave it open to append
                        ($key == count($actions)-1 ? ";" : ",");
                    
                        // Insert actions into Ribbons table
                        for ($ribbon_count = 1; $ribbon_count <= 3; $ribbon_count++)
                        {
                            switch ($ribbon_count) {
                                case 1:
                                    $color = "Bronze";
                                    $award_count = 1;
                                    break;
                                case 2:
                                    $color = "Silver";
                                    $award_count = 5;
                                    break;
                                case 3:
                                    $color = "Gold";
                                    $award_count = 10;
                                    break;
                        }
                        $ribbon_query .= "(
                        '".$db->escape($action['code'])."',
                        $award_count,
                        0,
                        '$game',
                        '".$ribbon_count."_".$db->escape($action['code']).".png',
                        '$color " .$db->escape($action['description']) . "')" .
                        // Check to see if we're on the last key -- if so finish the SQL statement, otherwise leave it open to append
                        ($key == count($actions)-1 && $ribbon_count == 3 ? ";" : ",");
                        }
                }
            }
            $db->query($action_query);
            $db->query($award_query);
            $db->query($ribbon_query);
            unset($action_query);
            unset($award_query);
            unset($ribbon_query);
        }

        // Insert awards
        print "Adding new awards for game $game.<br />";
        if (isset($awards) && count($awards) > 0)
        {
            $award_query = "INSERT IGNORE INTO `hlstats_Awards` (`awardType`, `game`, `code`, `name`, `verb`) VALUES ";
            $ribbon_query = "INSERT IGNORE INTO `hlstats_Ribbons` (`awardCode`, `awardCount`, `special`, `game`, `image`, `ribbonName`) VALUES ";
            
            foreach ($awards as $key => $award)
            {
                // Insert awards into Awards table
                $award_query .= "(
                    '".$db->escape($award['type'])."',
                    '$game',
                    '".$db->escape($award['code'])."',
                    '".$db->escape($award['award_name'])."',
                    '".$db->escape($award['award_verb'])."')" .
                    // Check to see if we're on the last key -- if so finish the SQL statement, otherwise leave it open to append
                    ($key == count($awards)-1 ? ";" : ",");
                
                // Insert awards into Ribbons table
                for ($ribbon_count = 1; $ribbon_count <= 3; $ribbon_count++)
                {
                    switch ($ribbon_count) {
                            case 1:
                                $color = "Bronze";
                                $award_count = 1;
                                break;
                            case 2:
                                $color = "Silver";
                                $award_count = 5;
                                break;
                            case 3:
                                $color = "Gold";
                                $award_count = 10;
                                break;
                    }
                    $ribbon_query .= "(
                    '".$db->escape($award['code'])."',
                    $award_count,
                    0,
                    '$game',
                    '".$ribbon_count."_".$db->escape($award['code']).".png',
                    '$color " .$db->escape($award['award_name']) . "')" .
                    // Check to see if we're on the last key -- if so finish the SQL statement, otherwise leave it open to append
                    ($key == count($awards)-1 && $ribbon_count == 3 ? ";" : ",");
                }
            }
            $db->query($award_query);
            $db->query($ribbon_query);
            unset($award_query);
            unset($ribbon_query);
        }

        // Insert weapons
        print "Adding new weapons for game $game.<br />";
        if (isset($weapons) && count($weapons) > 0)
        {
            $award_query = "INSERT IGNORE INTO `hlstats_Awards` (`awardType`, `game`, `code`, `name`, `verb`) VALUES ";
            $ribbon_query = "INSERT IGNORE INTO `hlstats_Ribbons` (`awardCode`, `awardCount`, `special`, `game`, `image`, `ribbonName`) VALUES ";
            $weapon_query = "INSERT IGNORE INTO `hlstats_Weapons` (`game`, `code`, `name`, `modifier`) VALUES ";
            foreach ($weapons as $key => $weapon)
            {
                // Insert weapons into Weapons table
                $weapon_query .= "(
                    '$game',
                    '".$db->escape($weapon['weapon_code'])."',
                    '".$db->escape($weapon['weapon_name'])."',
                    '".$db->escape($weapon['modifier'])."')" .
                    // Check to see if we're on the last key -- if so finish the SQL statement, otherwise leave it open to append
                    ($key == count($weapons)-1 ? ";" : ",");

                    
                // Insert weapons into Awards table
                $award_query .= "(
                    'W',
                    '$game',
                    '".$db->escape($weapon['weapon_code'])."',
                    '".$db->escape($weapon['award_name'])."',
                    '".$db->escape($weapon['award_verb'])."')" .
                    // Check to see if we're on the last key -- if so finish the SQL statement, otherwise leave it open to append
                    ($key == count($weapons)-1 ? ";" : ",");
                
                // Insert weapons into Ribbons table
                for ($ribbon_count = 1; $ribbon_count <= 3; $ribbon_count++)
                {
                    switch ($ribbon_count) {
                            case 1:
                                $color = "Bronze";
                                $award_count = 1;
                                break;
                            case 2:
                                $color = "Silver";
                                $award_count = 5;
                                break;
                            case 3:
                                $color = "Gold";
                                $award_count = 10;
                                break;
                    }
                    $ribbon_query .= "(
                    '".$db->escape($weapon['weapon_code'])."',
                    $award_count,
                    0,
                    '$game',
                    '".$ribbon_count."_".$db->escape($weapon['weapon_code']).".png',
                    '$color ".$db->escape($weapon['weapon_name']) . "')" .
                    // Check to see if we're on the last key -- if so finish the SQL statement, otherwise leave it open to append
                    ($key == count($weapons)-1 && $ribbon_count == 3 ? ";" : ",");
                }
            }
            $db->query($weapon_query);
            $db->query($award_query);
            $db->query($ribbon_query);
            unset($weapon_query);
            unset($award_query);
            unset($ribbon_query);

            foreach ($weapons as $key => $weapon)
            {            
                // Update kill count for any weapons just added
                print "Updating weapon count for ".$db->escape($weapon['weapon_code'])." in game $game<br />";
                if ($serverstring)
                {
                    $weapon_count_query = " 
                        UPDATE IGNORE
                            hlstats_Weapons
                        SET
                            `kills` = `kills` + (
                                IFNULL((
                                    SELECT count(weapon)
                                        FROM
                                            hlstats_Events_Frags
                                        WHERE
                                            `weapon` = '".$db->escape($weapon['weapon_code'])."'
                                        AND
                                            `serverId` IN ($serverstring)
                                    ),0)
                            )
                        WHERE
                            `code` = '".$db->escape($weapon['weapon_code'])."'
                        AND
                            `game` = '$game';";
                    $db->query($weapon_count_query);
                }
            }
        }
    }    
    // End 1583   
     
    // Perform database schema update notification
    print "Updating database and verion schema numbers.<br />";
    $db->query("UPDATE hlstats_Options SET `value` = '$version' WHERE `keyname` = 'version'");
    $db->query("UPDATE hlstats_Options SET `value` = '$dbversion' WHERE `keyname` = 'dbversion'");
?>