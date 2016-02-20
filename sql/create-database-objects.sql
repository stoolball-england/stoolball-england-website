-- -----------------------------------------------------
-- Table `nsa_activation`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `nsa_activation` (
  `activation_id` INT(5) UNSIGNED NOT NULL AUTO_INCREMENT,
  `item_id` INT(5) UNSIGNED NOT NULL DEFAULT '0',
  `request_time` INT(10) UNSIGNED NOT NULL DEFAULT '0',
  `hash` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`activation_id`),
  INDEX `person_id` (`item_id` ASC, `hash` ASC))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COMMENT = 'New sign-ups awaiting activation - Stoolball';


-- -----------------------------------------------------
-- Table `nsa_audit`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `nsa_audit` (
  `audit_id` INT(5) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(5) UNSIGNED NOT NULL DEFAULT '0',
  `ip_address` VARCHAR(20) NOT NULL,
  `date_changed` INT(10) UNSIGNED NOT NULL DEFAULT '0',
  `query_sql` TEXT NOT NULL,
  `request_url` VARCHAR(250) NOT NULL,
  PRIMARY KEY (`audit_id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COMMENT = 'Tracks updates made using public interface';


-- -----------------------------------------------------
-- Table `nsa_auto_sign_in`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `nsa_auto_sign_in` (
  `user_id` INT(10) UNSIGNED NOT NULL,
  `token` VARCHAR(255) NOT NULL,
  `device` TINYINT(3) UNSIGNED NOT NULL,
  `expires` INT(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`user_id`, `device`),
  INDEX `lookup` (`token` ASC, `device` ASC, `expires` ASC))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `nsa_batting`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `nsa_batting` (
  `batting_id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `match_team_id` INT(5) UNSIGNED NOT NULL,
  `player_id` INT(5) UNSIGNED NOT NULL,
  `position` TINYINT(2) UNSIGNED NOT NULL,
  `how_out` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `dismissed_by_id` INT(5) UNSIGNED NULL DEFAULT NULL,
  `bowler_id` INT(5) UNSIGNED NULL DEFAULT NULL,
  `runs` TINYINT(3) UNSIGNED NULL DEFAULT NULL,
  `balls_faced` TINYINT(3) UNSIGNED NULL DEFAULT NULL,
  `date_added` INT(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`batting_id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COMMENT = 'Batting performances in a stoolball match';


-- -----------------------------------------------------
-- Table `nsa_bowling`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `nsa_bowling` (
  `bowling_id` INT(5) UNSIGNED NOT NULL AUTO_INCREMENT,
  `match_team_id` INT(5) UNSIGNED NOT NULL,
  `player_id` INT(5) UNSIGNED NOT NULL,
  `position` TINYINT(2) UNSIGNED NULL DEFAULT NULL,
  `balls_bowled` INT(2) UNSIGNED NULL DEFAULT NULL,
  `no_balls` INT(2) UNSIGNED NULL DEFAULT NULL,
  `wides` INT(2) UNSIGNED NULL DEFAULT NULL,
  `runs_in_over` INT(2) UNSIGNED NULL DEFAULT NULL,
  `date_added` INT(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`bowling_id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COMMENT = 'Bowling performances in stoolball matches';


-- -----------------------------------------------------
-- Table `nsa_category`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `nsa_category` (
  `id` INT(5) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `parent` INT(5) UNSIGNED NULL DEFAULT NULL,
  `code` VARCHAR(20) NULL DEFAULT NULL,
  `sort_override` TINYINT(2) UNSIGNED NOT NULL DEFAULT '0',
  `navigate_url` VARCHAR(250) DEFAULT NULL,
  `hierarchy_level` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `hierarchy_sort` INT(4) UNSIGNED NOT NULL DEFAULT '0',
  `date_added` INT(10) UNSIGNED NOT NULL DEFAULT '0',
  `date_changed` INT(10) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  INDEX `sort_override` (`sort_override` ASC),
  INDEX `code` (`code` ASC),
  INDEX `parent` (`parent` ASC),
  INDEX `hierarchy_sort` (`hierarchy_sort` ASC))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `nsa_club`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `nsa_club` (
  `club_id` INT(5) UNSIGNED NOT NULL AUTO_INCREMENT,
  `club_name` VARCHAR(100) NOT NULL,
  `twitter` VARCHAR(16) NULL DEFAULT NULL,
  `clubmark` BIT(1) NOT NULL DEFAULT false,
  `short_url` VARCHAR(100) NULL DEFAULT NULL,
  `date_added` INT(10) UNSIGNED NOT NULL DEFAULT '0',
  `date_changed` INT(10) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`club_id`),
  UNIQUE INDEX `short_url` (`short_url` ASC))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COMMENT = 'Stoolball clubs: clubs can have many teams';


-- -----------------------------------------------------
-- Table `nsa_competition`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `nsa_competition` (
  `competition_id` INT(5) UNSIGNED NOT NULL AUTO_INCREMENT,
  `competition_name` VARCHAR(100) NOT NULL,
  `category_id` INT(5) NULL DEFAULT NULL,
  `intro` TEXT NULL DEFAULT NULL,
  `contact` TEXT NOT NULL,
  `notification_email` VARCHAR(200) NULL DEFAULT NULL,
  `website` VARCHAR(250) NULL DEFAULT NULL,
  `active` TINYINT(1) UNSIGNED NOT NULL DEFAULT '1',
  `player_type_id` INT(5) NOT NULL DEFAULT '2',
  `players_per_team` TINYINT(2) UNSIGNED NOT NULL DEFAULT '11',
  `overs` INT(2) UNSIGNED NOT NULL DEFAULT '16',
  `short_url` VARCHAR(100) NULL DEFAULT NULL,
  `update_search` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `date_added` INT(10) UNSIGNED NOT NULL DEFAULT '0',
  `date_changed` INT(10) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`competition_id`),
  UNIQUE INDEX `short_url` (`short_url` ASC),
  INDEX `active` (`active` ASC),
  INDEX `category_id` (`category_id` ASC))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COMMENT = 'Stoolball competitions: leagues, cups and tournaments';


-- -----------------------------------------------------
-- Table `nsa_email_subscription`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `nsa_email_subscription` (
  `item_id` INT(8) UNSIGNED NOT NULL DEFAULT '0',
  `user_id` INT(6) UNSIGNED NOT NULL DEFAULT '0',
  `item_type` SMALLINT(5) NOT NULL DEFAULT '0',
  `date_changed` INT(10) UNSIGNED NULL DEFAULT NULL,
  PRIMARY KEY (`item_id`, `user_id`, `item_type`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `nsa_forum_message`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `nsa_forum_message` (
  `id` INT(8) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(6) UNSIGNED NOT NULL DEFAULT '0',
  `date_added` INT(10) UNSIGNED NULL DEFAULT NULL,
  `date_changed` INT(10) UNSIGNED NULL DEFAULT NULL,
  `message` TEXT NOT NULL,
  `sort_override` INT(3) UNSIGNED NOT NULL DEFAULT '0',
  `ip` VARCHAR(15) NULL DEFAULT NULL,
  `item_id` int(5) DEFAULT NULL,
  `item_type` smallint(5) DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `user_id` (`user_id` ASC),
  INDEX `item_id` (`item_id` ASC),
  INDEX `item_type` (`item_type` ASC),
  INDEX `sort_override` (`sort_override` ASC))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COMMENT = 'Messages in stoolball forums';

-- -----------------------------------------------------
-- Table `nsa_ground`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `nsa_ground` (
  `ground_id` INT(5) UNSIGNED NOT NULL AUTO_INCREMENT,
  `sort_name` VARCHAR(100) NOT NULL,
  `saon` VARCHAR(100) NULL DEFAULT NULL,
  `paon` VARCHAR(250) NULL DEFAULT NULL,
  `street_descriptor` VARCHAR(250) NULL DEFAULT NULL,
  `locality` VARCHAR(250) NULL DEFAULT NULL,
  `town` VARCHAR(100) NULL DEFAULT NULL,
  `administrative_area` VARCHAR(100) NULL DEFAULT NULL,
  `postcode` VARCHAR(8) NULL DEFAULT NULL,
  `latitude` DOUBLE NULL DEFAULT NULL,
  `longitude` DOUBLE NULL DEFAULT NULL,
  `geo_precision` TINYINT(1) UNSIGNED NULL DEFAULT NULL,
  `directions` TEXT NULL DEFAULT NULL,
  `parking` TEXT NULL DEFAULT NULL,
  `facilities` TEXT NULL DEFAULT NULL,
  `short_url` VARCHAR(100) NULL DEFAULT NULL,
  `update_search` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `date_added` INT(10) UNSIGNED NOT NULL DEFAULT '0',
  `date_changed` INT(10) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`ground_id`),
  UNIQUE INDEX `short_url` (`short_url` ASC),
  INDEX `sort_name` (`sort_name` ASC))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COMMENT = 'Stoolball grounds';


-- -----------------------------------------------------
-- Table `nsa_match`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `nsa_match` (
  `match_id` INT(5) UNSIGNED NOT NULL AUTO_INCREMENT,
  `match_title` VARCHAR(200) NOT NULL DEFAULT '',
  `custom_title` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `ground_id` INT(5) UNSIGNED NULL DEFAULT '0',
  `match_type` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `qualification` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `player_type_id` INT(5) UNSIGNED NULL DEFAULT NULL,
  `players_per_team` TINYINT(2) UNSIGNED NULL DEFAULT NULL,
  `overs` INT(2) UNSIGNED NULL DEFAULT NULL,
  `tournament_match_id` INT(5) UNSIGNED NULL DEFAULT NULL,
  `max_tournament_teams` TINYINT(3) NULL DEFAULT NULL,
  `tournament_spaces` TINYINT(3) NULL DEFAULT NULL,
  `start_time` INT(10) UNSIGNED NOT NULL DEFAULT '0',
  `start_time_known` TINYINT(1) UNSIGNED NOT NULL DEFAULT '1',
  `home_bat_first` TINYINT(1) UNSIGNED NULL DEFAULT NULL,
  `home_runs` INT(5) UNSIGNED NULL DEFAULT NULL,
  `home_wickets` INT(3) NULL DEFAULT NULL,
  `away_runs` INT(5) UNSIGNED NULL DEFAULT NULL,
  `away_wickets` INT(3) NULL DEFAULT NULL,
  `match_result_id` INT(5) UNSIGNED NULL DEFAULT NULL,
  `player_of_match_id` INT(5) UNSIGNED NULL DEFAULT NULL,
  `player_of_match_home_id` INT(5) UNSIGNED NULL DEFAULT NULL,
  `player_of_match_away_id` INT(5) UNSIGNED NULL DEFAULT NULL,
  `match_notes` TEXT NULL DEFAULT NULL,
  `short_url` VARCHAR(100) NULL DEFAULT NULL,
  `update_search` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `added_by` INT(10) UNSIGNED NOT NULL DEFAULT '1',
  `date_added` INT(10) UNSIGNED NOT NULL DEFAULT '0',
  `date_changed` INT(10) UNSIGNED NOT NULL DEFAULT '0',
  `modified_by_id` INT(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`match_id`),
  UNIQUE INDEX `short_url` (`short_url` ASC),
  INDEX `competition_id` (`start_time` ASC),
  INDEX `tournament` (`match_type` ASC, `tournament_match_id` ASC),
  INDEX `player_type_id` (`player_type_id` ASC))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COMMENT = 'Stoolball matches';


-- -----------------------------------------------------
-- Table `nsa_match_team`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `nsa_match_team` (
  `match_team_id` INT(5) UNSIGNED NOT NULL AUTO_INCREMENT,
  `match_id` INT(5) UNSIGNED NOT NULL DEFAULT '0',
  `team_id` INT(5) UNSIGNED NOT NULL DEFAULT '0',
  `team_role` TINYINT(1) UNSIGNED NOT NULL DEFAULT '2',
  `date_added` INT(10) UNSIGNED NOT NULL DEFAULT '0',
  `date_changed` INT(10) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`match_team_id`),
  INDEX `match_id` (`match_id` ASC, `team_id` ASC))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COMMENT = 'Teams playing in a stoolball match or tournament';


-- -----------------------------------------------------
-- Table `nsa_permission_role`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `nsa_permission_role` (
  `permission_role_id` INT(5) UNSIGNED NOT NULL AUTO_INCREMENT,
  `permission_id` INT(5) UNSIGNED NOT NULL DEFAULT '0',
  `resource_uri` VARCHAR(250) NULL DEFAULT NULL,
  `role_id` INT(5) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`permission_role_id`),
  INDEX `role_id` (`role_id` ASC))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COMMENT = 'Permissions in roles for Stoolball site';


-- -----------------------------------------------------
-- Table `nsa_player`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `nsa_player` (
  `player_id` INT(5) UNSIGNED NOT NULL AUTO_INCREMENT,
  `player_name` VARCHAR(100) NOT NULL,
  `comparable_name` VARCHAR(100) NOT NULL,
  `team_id` INT(5) UNSIGNED NOT NULL,
  `user_id` INT(5) UNSIGNED NULL DEFAULT NULL,
  `first_played` INT(10) UNSIGNED NULL DEFAULT NULL,
  `last_played` INT(10) UNSIGNED NULL DEFAULT NULL,
  `total_matches` INT(5) UNSIGNED NULL DEFAULT NULL,
  `missed_matches` INT(5) UNSIGNED NULL DEFAULT NULL,
  `probability` INT(5) NOT NULL DEFAULT '0' COMMENT 'total_matches - missed_matches',
  `player_role` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `short_url` VARCHAR(200) NULL DEFAULT NULL,
  `update_search` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `date_added` INT(5) UNSIGNED NOT NULL,
  `date_changed` INT(5) UNSIGNED NOT NULL,
  PRIMARY KEY (`player_id`),
  INDEX `probability` (`probability` ASC),
  INDEX `team_id` (`team_id` ASC),
  INDEX `short_url` (`short_url` ASC))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COMMENT = 'Players named on match scorecards';


-- -----------------------------------------------------
-- Table `nsa_player_match`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `nsa_player_match` (
  `player_match_id` INT(5) UNSIGNED NOT NULL AUTO_INCREMENT,
  `player_id` INT(5) UNSIGNED NOT NULL,
  `player_role` TINYINT(1) UNSIGNED NULL DEFAULT NULL,
  `player_name` VARCHAR(100) NULL DEFAULT NULL,
  `player_url` VARCHAR(200) NULL DEFAULT NULL,
  `match_id` INT(5) UNSIGNED NOT NULL,
  `match_team_id` INT(5) UNSIGNED NULL DEFAULT NULL,
  `match_type` INT(1) UNSIGNED NULL DEFAULT NULL,
  `match_player_type` INT(1) UNSIGNED NULL DEFAULT NULL,
  `match_time` INT(10) UNSIGNED NULL DEFAULT NULL,
  `match_title` VARCHAR(200) NULL DEFAULT NULL,
  `match_url` VARCHAR(100) NULL DEFAULT NULL,
  `tournament_id` INT(5) UNSIGNED NULL DEFAULT NULL,
  `ground_id` INT(5) UNSIGNED NULL DEFAULT NULL,
  `team_id` INT(5) UNSIGNED NULL DEFAULT NULL,
  `team_name` VARCHAR(100) NULL DEFAULT NULL,
  `opposition_id` INT(5) UNSIGNED NULL DEFAULT NULL,
  `opposition_name` VARCHAR(100) NULL DEFAULT NULL,
  `batting_first` TINYINT(1) UNSIGNED NULL DEFAULT NULL,
  `first_over` INT(5) UNSIGNED NULL DEFAULT NULL,
  `balls_bowled` INT(10) UNSIGNED NULL DEFAULT NULL,
  `overs` DECIMAL(4,1) UNSIGNED NULL DEFAULT NULL,
  `overs_decimal` DECIMAL(5,3) UNSIGNED NULL DEFAULT NULL,
  `maidens` TINYINT(2) UNSIGNED NULL DEFAULT NULL,
  `runs_conceded` SMALLINT(3) UNSIGNED NULL DEFAULT NULL,
  `has_runs_conceded` TINYINT(1) UNSIGNED NULL DEFAULT NULL,
  `wickets` TINYINT(2) UNSIGNED NULL DEFAULT NULL,
  `wickets_with_bowling` TINYINT(2) UNSIGNED NULL DEFAULT NULL,
  `player_innings` TINYINT(1) UNSIGNED NULL DEFAULT NULL,
  `batting_position` TINYINT(2) UNSIGNED NULL DEFAULT NULL,
  `how_out` TINYINT(1) UNSIGNED NULL DEFAULT NULL,
  `dismissed` TINYINT(1) UNSIGNED NULL DEFAULT NULL,
  `bowled_by` INT(5) UNSIGNED NULL DEFAULT NULL,
  `caught_by` INT(5) UNSIGNED NULL DEFAULT NULL,
  `run_out_by` INT(5) UNSIGNED NULL DEFAULT NULL,
  `runs_scored` TINYINT(3) UNSIGNED NULL DEFAULT NULL,
  `balls_faced` TINYINT(3) UNSIGNED NULL DEFAULT NULL,
  `catches` TINYINT(1) UNSIGNED NULL DEFAULT NULL,
  `run_outs` TINYINT(1) UNSIGNED NULL DEFAULT NULL,
  `player_of_match` TINYINT(1) UNSIGNED NULL DEFAULT NULL,
  PRIMARY KEY (`player_match_id`),
  INDEX `player_id` (`player_id` ASC),
  INDEX `player_role` (`player_role` ASC),
  INDEX `player_name` (`player_name` ASC),
  INDEX `match_id` (`match_id` ASC),
  INDEX `match_time` (`match_time` ASC),
  INDEX `ground_id` (`ground_id` ASC),
  INDEX `team_id` (`team_id` ASC),
  INDEX `team_name` (`team_name` ASC),
  INDEX `opposition_id` (`opposition_id` ASC),
  INDEX `opposition_name` (`opposition_name` ASC),
  INDEX `batting_position` (`batting_position` ASC),
  INDEX `runs_scored` (`runs_scored` ASC),
  INDEX `balls_faced` (`balls_faced` ASC),
  INDEX `dismissed` (`dismissed` ASC),
  INDEX `wickets` (`wickets` ASC),
  INDEX `runs_conceded` (`runs_conceded` ASC),
  INDEX `wickets_with_bowling` (`wickets_with_bowling` ASC),
  INDEX `overs_decimal` (`overs_decimal` ASC),
  INDEX `balls_bowled` (`balls_bowled` ASC),
  INDEX `catches` (`catches` ASC),
  INDEX `run_outs` (`run_outs` ASC),
  INDEX `bowled_by` (`bowled_by` ASC),
  INDEX `player_of_match` (`player_of_match` ASC),
  INDEX `tournament_id` (`tournament_id` ASC),
  INDEX `match_type` (`match_type` ASC),
  INDEX `match_player_type` (`match_player_type` ASC),
  INDEX `batting_first` (`batting_first` ASC)
)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COMMENT = 'Derived statistics about players\' performances';


-- -----------------------------------------------------
-- Table `nsa_point`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `nsa_point` (
  `point_id` INT(5) UNSIGNED NOT NULL AUTO_INCREMENT,
  `points` INT(3) NOT NULL DEFAULT '1',
  `team_id` INT(5) UNSIGNED NOT NULL DEFAULT '0',
  `season_id` INT(5) UNSIGNED NOT NULL DEFAULT '0',
  `reason` VARCHAR(200) NULL DEFAULT NULL,
  `date_added` INT(10) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`point_id`),
  INDEX `team_id` (`team_id` ASC, `season_id` ASC))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COMMENT = 'Points added or deducted during a stoolball season';


-- -----------------------------------------------------
-- Table `nsa_queue`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `nsa_queue` (
  `queue_id` INT(5) UNSIGNED NOT NULL AUTO_INCREMENT,
  `data` TEXT NOT NULL,
  `action` TINYINT(2) UNSIGNED NOT NULL DEFAULT '1',
  `user_id` INT(5) UNSIGNED NULL DEFAULT NULL COMMENT 'The user who took the action',
  `date_added` INT(10) NOT NULL DEFAULT '0',
  PRIMARY KEY (`queue_id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COMMENT = 'Actions queued for later execution';


-- -----------------------------------------------------
-- Table `nsa_role`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `nsa_role` (
  `role_id` INT(5) UNSIGNED NOT NULL AUTO_INCREMENT,
  `role` VARCHAR(100) NOT NULL,
  PRIMARY KEY (`role_id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COMMENT = 'Permission roles for Stoolball site';


-- -----------------------------------------------------
-- Table `nsa_season`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `nsa_season` (
  `season_id` INT(5) UNSIGNED NOT NULL AUTO_INCREMENT,
  `season_name` VARCHAR(100) NOT NULL DEFAULT '',
  `competition_id` INT(5) UNSIGNED NOT NULL DEFAULT '0',
  `is_latest` TINYINT(1) UNSIGNED NOT NULL DEFAULT '1',
  `start_year` YEAR NOT NULL DEFAULT '0000',
  `end_year` YEAR NOT NULL DEFAULT '0000',
  `intro` TEXT NULL DEFAULT NULL,
  `results` TEXT NULL DEFAULT NULL,
  `show_table` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `show_runs_scored` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `show_runs_conceded` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `short_url` VARCHAR(100) NULL DEFAULT NULL,
  `date_added` INT(10) UNSIGNED NOT NULL DEFAULT '0',
  `date_changed` INT(10) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`season_id`),
  UNIQUE INDEX `short_url` (`short_url` ASC),
  INDEX `is_part_of` (`competition_id` ASC))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COMMENT = ' Seasons of stoolball competitions';


-- -----------------------------------------------------
-- Table `nsa_season_match`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `nsa_season_match` (
  `season_id` INT(5) UNSIGNED NOT NULL DEFAULT '0',
  `match_id` INT(5) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`season_id`, `match_id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COMMENT = 'Stoolball matches in stoolball seasons';


-- -----------------------------------------------------
-- Table `nsa_season_matchtype`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `nsa_season_matchtype` (
  `season_id` INT(5) UNSIGNED NOT NULL DEFAULT '0',
  `match_type` INT(5) UNSIGNED NOT NULL DEFAULT '0',
  `date_added` INT(10) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`season_id`, `match_type`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COMMENT = 'Which types of matches are in a season?';


-- -----------------------------------------------------
-- Table `nsa_season_rule`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `nsa_season_rule` (
  `season_rule_id` INT(5) UNSIGNED NOT NULL AUTO_INCREMENT,
  `season_id` INT(5) UNSIGNED NOT NULL DEFAULT '0',
  `match_result_id` INT(5) UNSIGNED NOT NULL DEFAULT '0',
  `home_points` TINYINT(3) UNSIGNED NOT NULL DEFAULT '1',
  `away_points` TINYINT(3) UNSIGNED NOT NULL DEFAULT '1',
  `date_added` INT(10) UNSIGNED NOT NULL DEFAULT '0',
  `date_changed` INT(10) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`season_rule_id`),
  INDEX `season_id` (`season_id` ASC, `match_result_id` ASC))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COMMENT = 'Rules to build season results tables';


-- -----------------------------------------------------
-- Table `nsa_short_url`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `nsa_short_url` (
  `short_url` VARCHAR(200) NOT NULL,
  `short_url_base` VARCHAR(200) NOT NULL,
  `script` VARCHAR(255) NOT NULL,
  `param_names` VARCHAR(255) NOT NULL,
  `param_values` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`short_url`),
  INDEX `short_url_base` (`short_url_base` ASC))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COMMENT = 'Query cache for short URLs';


-- -----------------------------------------------------
-- Table `nsa_team`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `nsa_team` (
  `team_id` INT(5) UNSIGNED NOT NULL AUTO_INCREMENT,
  `team_name` VARCHAR(100) NOT NULL DEFAULT '',
  `comparable_name` VARCHAR(100) NOT NULL,
  `intro` TEXT NULL DEFAULT NULL,
  `ground_id` INT(5) UNSIGNED NULL DEFAULT NULL,
  `website` VARCHAR(250) NOT NULL DEFAULT '',
  `active` TINYINT(1) UNSIGNED NOT NULL DEFAULT '1',
  `team_type` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `player_type_id` INT(5) UNSIGNED NOT NULL DEFAULT '2',
  `club_id` INT(5) UNSIGNED NULL DEFAULT NULL,
  `playing_times` TEXT NULL DEFAULT NULL,
  `cost` TEXT NULL DEFAULT NULL,
  `contact` TEXT NULL DEFAULT NULL,
  `contact_nsa` TEXT NULL DEFAULT NULL,
  `short_url` VARCHAR(100) NULL DEFAULT NULL,
  `update_search` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `owner_role_id` INT(5) UNSIGNED NULL DEFAULT NULL,
  `date_added` INT(10) UNSIGNED NOT NULL DEFAULT '0',
  `date_changed` INT(10) UNSIGNED NOT NULL DEFAULT '0',
  `modified_by_id` INT(5) UNSIGNED NOT NULL,
  PRIMARY KEY (`team_id`),
  UNIQUE INDEX `comparable_name` (`comparable_name` ASC),
  UNIQUE INDEX `short_url` (`short_url` ASC),
  INDEX `owner_role_id` (`owner_role_id` ASC))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COMMENT = 'Stoolball teams';


-- -----------------------------------------------------
-- Table `nsa_team_season`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `nsa_team_season` (
  `team_id` INT(5) UNSIGNED NOT NULL DEFAULT '0',
  `season_id` INT(5) UNSIGNED NOT NULL DEFAULT '0',
  `withdrawn_league` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `date_added` INT(10) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`team_id`, `season_id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COMMENT = 'Link between nsa_team and nsa_season';


-- -----------------------------------------------------
-- Table `nsa_user`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `nsa_user` (
  `user_id` INT(6) UNSIGNED NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(100) NULL DEFAULT NULL,
  `known_as` VARCHAR(210) NOT NULL DEFAULT '',
  `name_first` VARCHAR(100) NOT NULL DEFAULT '',
  `name_last` VARCHAR(100) NOT NULL DEFAULT '',
  `name_sort` VARCHAR(100) NOT NULL DEFAULT '',
  `password_md5` VARCHAR(32) NULL DEFAULT NULL,
  `salt` VARCHAR(256) NULL DEFAULT NULL,
  `activated` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `disabled` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `sign_in_count` INT(5) UNSIGNED NOT NULL DEFAULT '0',
  `last_signed_in` INT(10) UNSIGNED NULL DEFAULT NULL,
  `total_messages` INT(5) UNSIGNED NOT NULL DEFAULT '0',
  `date_added` INT(10) UNSIGNED NULL DEFAULT NULL,
  `date_changed` INT(10) UNSIGNED NULL DEFAULT NULL,
  `gender` ENUM('male', 'female') NULL DEFAULT NULL,
  `location` VARCHAR(100) NULL DEFAULT NULL,
  `occupation` VARCHAR(255) NULL DEFAULT NULL,
  `interests` TEXT NULL DEFAULT NULL,
  `requested_email` VARCHAR(100) NULL DEFAULT NULL,
  `requested_email_hash` VARCHAR(255) NULL DEFAULT NULL,
  `password_reset_request_date` INT(10) UNSIGNED NULL DEFAULT NULL,
  `password_reset_token` VARCHAR(255) NULL DEFAULT NULL,
  `password_hash` VARCHAR(255) NULL DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  INDEX `activated` (`activated` ASC),
  INDEX `name_sort` (`name_sort` ASC),
  INDEX `password_reset_request_date` (`password_reset_request_date` ASC, `password_reset_token` ASC))
ENGINE = InnoDB

DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `nsa_user_role`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `nsa_user_role` (
  `role_id` INT(6) UNSIGNED NOT NULL DEFAULT '0',
  `user_id` INT(6) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`role_id`, `user_id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `nsa_search_index`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `nsa_search_index` (
  `search_index_id` varchar(250) NOT NULL,
  `indexed_item_type` varchar(15) NOT NULL,
  `url` varchar(250) NOT NULL,
  `title` varchar(250) NOT NULL,
  `keywords` varchar(500) DEFAULT NULL,
  `description` varchar(1000) NOT NULL,
  `related_links_html` varchar(1000) DEFAULT NULL,
  `full_text` text,
  `weight_within_type` int(5) NOT NULL DEFAULT '1',
  `weight_of_type` int(5) NOT NULL DEFAULT '1',
  PRIMARY KEY (`search_index_id`),
  UNIQUE KEY `search_index_id_UNIQUE` (`search_index_id`),
  KEY `search` (`keywords`(255),`title`,`description`(255),`full_text`(255)),
  KEY `weight` (`weight_within_type`,`weight_of_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Derived data used to provide search results';
