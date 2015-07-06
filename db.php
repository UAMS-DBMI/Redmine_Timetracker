<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of db
 *
 * @author WangHanni
 */

class RedmineDB extends mysqli {

    /** single instance of self shared among all instances */
    private static $instance = null;

    /** DB connection config vars - NEW INSTANCE** */


    private $user = "";
    private $pass = "";
    private $dbName = "";
    private $dbHost = "";


    /** private constructor */
    public function __construct() {

        $config = require_once 'config.php';
        $this->user = $config['dbUser'];
        $this->pass = $config['dbPass'];
        $this->dbName = $config['dbName'];
        $this->dbHost = $config['dbHost'];

        parent::__construct($this->dbHost, $this->user, $this->pass, $this->dbName); // $this is used for referencing the obj itself. for non-static vars
        if (mysqli_connect_error()) {
            exit("Connection Error ( " . mysqli_connect_errno() . " ' " . mysqli_connect_error());
        }
        parent::set_charset("utf-8");
    }

    /** This method must be status, and must return an instance of the object if the object does not already exists */
    public static function getInstance() {
        if (!self::$instance instanceof self) { //self is used for referencing class itself. For static vars
            self::$instance = new self;
        }
        return self::$instance;
    }

    public function getConfig() {
        return $this->user;
    }

    // The clone and wakeup methods prevents external instantiation of copies of the Singleton class,
    // thus eliminating the possibility of duplicate objects.
    public function __clone() {
        trigger_error('Clone is not allowed.', E_USER_ERROR);
    }

    public function __wakeup() {
        trigger_error('Deserializing is not allowed.', E_USER_ERROR);
    }


    public function getUserIDFromUserName($user_name) {
        $user_id = $this->query("SELECT id FROM bitnami_redmine.users WHERE status = 1 AND login = '".$user_name."'; "); 
        $row = $user_id->fetch_row();
        return $row[0];
        //return $user_id;
    }

    //CONCAT(REPEAT('-----', proj.depth), proj.name) AS name,
    
    public function projectsByUsers($USER, $DATE) {
        $pList = $this->query("SELECT @row := @row + 1 as row, comb.* FROM 
(SELECT proj.project_id, proj.issue_id, 
       entries.id AS time_entry_id, entries.user_id,
       proj.name AS name, proj.depth,
       (SELECT CASE WHEN EXISTS (
          SELECT * FROM bitnami_redmine.enabled_modules
           WHERE project_id = proj.project_id AND name = 'time_tracking'
        ) THEN TRUE ELSE FALSE END) AS allowTimeInput,
       entries.hours, entries.spent_on, entries.comments,
       entries.activity_id, entries.activity_name, 
       entries.istri_cvid, entries.istri_value,
       entries.effort_cvids, entries.effort_values,
       entries.investigator_cvid, entries.investigator_value,
       CASE WHEN entries.hours IS NULL THEN 'INSERT' ELSE 'UPDATE' END AS action_type,
       0 AS action_edited
  FROM (
    SELECT issue.project_id , issue.id AS issue_id,
           CONCAT('Issue ', CAST(issue.id AS CHAR), ': ', issue.subject) AS name,
           (SELECT node.lft
              FROM bitnami_redmine.projects AS node
             WHERE node.id = issue.project_id) AS lft,       
           (SELECT (COUNT(parent.name) - 1) AS depth
              FROM bitnami_redmine.members
              JOIN bitnami_redmine.projects AS node 
                ON node.id = members.project_id
              JOIN bitnami_redmine.projects AS parent 
                ON node.lft BETWEEN parent.lft AND parent.rgt
             WHERE node.status = 1 
               AND node.id = issue.project_id 
               AND members.user_id = " . $USER .
                " GROUP BY node.name) + 1 AS depth
      FROM bitnami_redmine.issues AS issue
     WHERE issue.assigned_to_id = " . $USER .
                " OR EXISTS (SELECT id FROM bitnami_redmine.watchers 
                    WHERE watchers.watchable_type = 'Issue' 
                      AND watchers.watchable_id = issue.id 
                      AND watchers.user_id = " . $USER . " )
     UNION ALL
    SELECT node.id AS project_id, NULL AS issue_id,
           node.name, node.lft, (COUNT(parent.name) - 1) AS depth
      FROM bitnami_redmine.members
      JOIN bitnami_redmine.projects AS node 
        ON node.id = members.project_id
      JOIN bitnami_redmine.projects AS parent 
        ON node.lft BETWEEN parent.lft AND parent.rgt
     WHERE node.status = 1 AND members.user_id = " . $USER .
                " GROUP BY node.name) proj
  LEFT JOIN (SELECT time_entries.id, time_entries.project_id,
       time_entries.user_id, time_entries.issue_id,
       time_entries.hours, time_entries.spent_on, time_entries.activity_id, 
       IF(time_entries.comments = '', NULL, time_entries.comments) as comments,
       (SELECT name FROM bitnami_redmine.enumerations
         WHERE enumerations.type = 'TimeEntryActivity'
           AND enumerations.id = time_entries.activity_id) AS activity_name,
       (SELECT id
          FROM bitnami_redmine.custom_values
         WHERE custom_values.custom_field_id = 3
           AND custom_values.customized_id = time_entries.id) AS istri_cvid,           
       (SELECT value
          FROM bitnami_redmine.custom_values
         WHERE custom_values.custom_field_id = 3
           AND custom_values.customized_id = time_entries.id) AS istri_value,
       (SELECT GROUP_CONCAT(id ORDER BY id ASC SEPARATOR ', ')
          FROM bitnami_redmine.custom_values
         WHERE custom_values.custom_field_id = 4
           AND custom_values.customized_id = time_entries.id) AS effort_cvids,
       (SELECT GROUP_CONCAT(value ORDER BY id ASC SEPARATOR ', ')
          FROM bitnami_redmine.custom_values
         WHERE custom_values.custom_field_id = 4
           AND custom_values.customized_id = time_entries.id) AS effort_values,
       (SELECT id
          FROM bitnami_redmine.custom_values
         WHERE custom_values.custom_field_id = 10
           AND custom_values.customized_id = time_entries.id) AS investigator_cvid,
       (SELECT IF(value = '', NULL, value) as value
          FROM bitnami_redmine.custom_values
         WHERE custom_values.custom_field_id = 10
           AND custom_values.customized_id = time_entries.id) AS investigator_value           
  FROM bitnami_redmine.time_entries
 WHERE time_entries.user_id = " . $USER .
                " AND time_entries.spent_on = '" . $DATE . "') entries 
    ON entries.project_id = proj.project_id
   AND IFNULL(entries.issue_id,0) = IFNULL(proj.issue_id,0)
 ORDER BY proj.lft, proj.depth) comb,
 (SELECT @row := 0) r;");
        return $pList;
    }

    public function listActivities() {
        $activitylist = $this->query("SELECT id, name, active
                FROM bitnami_redmine.enumerations
                where type = 'TimeEntryActivity' and active = 1
                order by name;");
        return $activitylist;
    }

    public function listEfforts() {
        $effortlist = $this->query("SELECT replace(possible_values, '---', '')
                FROM bitnami_redmine.custom_fields where name = 'Effort';");
        $row = $effortlist->fetch_row();
        return $row[0];
    }

    public function insertSQL($sql) {
        $this->query($sql);
    }

    public function getLastInsertedID() {
        $lastTEID = $this->query("SELECT LAST_INSERT_ID();");
        $row = $lastTEID->fetch_row();
        return $row[0];
    }

    public function getWeekTotal($userid,$sunday,$today,$saturday) {
        $weekTotal =
            $this->query("SELECT SUM(time_entries.hours)" .
                          " FROM bitnami_redmine.time_entries " .
                         " WHERE time_entries.user_id = " . $userid .
                           " AND time_entries.spent_on BETWEEN '" . $sunday . "' AND '" . $saturday . "'" .
                           " AND time_entries.spent_on != '" . $today . "'");
        $row = $weekTotal->fetch_row();
        if ($row[0] == null) {
            return 0;
        } else {
            return $row[0];
        }
    }
}
