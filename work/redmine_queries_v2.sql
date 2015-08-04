# FINAL BASE QUERY - (using vars $USER and $DATE)


SELECT @row := @row + 1 as row, comb.* FROM 
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
       0 AS action_edited, proj.closed_on
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
                " GROUP BY node.name) + 1 AS depth,
           issue.closed_on
      FROM bitnami_redmine.issues AS issue
     WHERE issue.assigned_to_id = " . $USER .
                " OR EXISTS (SELECT id FROM bitnami_redmine.watchers 
                    WHERE watchers.watchable_type = 'Issue' 
                      AND watchers.watchable_id = issue.id 
                      AND watchers.user_id = " . $USER . " )
     UNION ALL
    SELECT node.id AS project_id, NULL AS issue_id,
           node.name, node.lft, (COUNT(parent.name) - 1) AS depth,
           NULL AS closed_on
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
 WHERE proj.closed_on IS NULL
    OR '" . $DATE . "' < DATE_ADD(proj.closed_on, INTERVAL 7 DAY)   
 ORDER BY proj.lft, proj.depth) comb,
 (SELECT @row := 0) r;




#-----------------------------------
# INSERT 
# For each row with action_edited = 1 (set when changes made to row) and action_type = 'INSERT'

# Insert to time_entries table and return new id 
INSERT INTO bitnami_redmine.time_entries 
(project_id, user_id, issue_id, hours, comments, activity_id, spent_on, tyear, tmonth, tweek, created_on, updated_on) 
VALUES ($project_id, $user_id, $issue_id, $hours, $comments, $activity_id, $spent_on, YEAR($spent_on), MONTH($spent_on), WEEK($spent_on), NOW(), NOW());
SELECT LAST_INSERT_ID();
 
# Insert custom_field values to custom_values table
# Effort (For each selected in multi-selection, value is effort text value)
INSERT INTO bitnami_redmine.custom_values
(customized_type, customized_id, custom_field_id, value) 
VALUES ('TimeEntry', $time_entry_id, 4, $value);

# IsTRI (1 or 0)
INSERT INTO bitnami_redmine.custom_values
(customized_type, customized_id, custom_field_id, value) 
VALUES ('TimeEntry', $time_entry_id, 3, $value);

# Investigator (user_id of investigator)
INSERT INTO bitnami_redmine.custom_values
(customized_type, customized_id, custom_field_id, value) 
VALUES ('TimeEntry', $time_entry_id, 10, $value); 

#--------------------------------------- 
# UPDATE 
# For each row with action_edited = 1 and action_type = 'UPDATE'

# Update the time_entries table
UPDATE bitnami_redmine.time_entries 
SET project_id = $project_id , user_id = $user_id, issue_id = $issue_id, 
    hours = $hours, comments = $comments, activity_id = $activity_id, 
    spent_on = $spent_on, tyear = YEAR($spent_on), tmonth = MONTH($spent_on), 
    tweek = WEEK($spent_on), updated_on = NOW()
WHERE id = $time_entry_id;

# Update Effort
# Easiest method is to delete current values and reinsert.
# Will think about this further.
DELETE FROM bitnami_redmine.custom_values
 WHERE id IN ($effort_cvids);
# For each
INSERT INTO bitnami_redmine.custom_values
(customized_type, customized_id, custom_field_id, value) 
VALUES ('TimeEntry', $time_entry_id, 4, $value);


# Update IsTRI
UPDATE bitnami_redmine.custom_values 
SET value = $istri_value
WHERE id = $istri_cvid;

# Update Investigator
UPDATE bitnami_redmine.custom_values 
SET value = $investigator_value
WHERE id = $investigator_cvid;










#---------------------------------------------------------------------------------------------------------
#---------------------------------------------------------------------------------------------------------
#---------------------------------------------------------------------------------------------------------
#---------------------------------------------------------------------------------------------------------
#---------------------------------------------------------------------------------------------------------

# Work stuff for building up base query

#basic hierarchy list

SELECT node.id, node.name
  FROM bitnami_redmine.projects AS node,
       bitnami_redmine.projects AS parent
 WHERE node.lft BETWEEN parent.lft AND parent.rgt
   AND parent.name IN ('Time','Administration','Education','Projects')
 ORDER BY node.lft;
 
# with formated name

SELECT node.id AS project_id, NULL AS issue_id,
       CONCAT( REPEAT('----', COUNT(parent.name) - 1), node.name) AS name,node.lft,node.rgt
FROM bitnami_redmine.projects AS node,
     bitnami_redmine.projects AS parent
WHERE node.lft BETWEEN parent.lft AND parent.rgt
GROUP BY node.name
ORDER BY node.lft;

# with issues pulled in
SELECT comb.project_id, comb.issue_id,
       CONCAT(REPEAT('----', comb.depth), comb.name) AS name
  FROM (
SELECT issue.project_id , issue.id AS issue_id,
       CONCAT("Issue ", CAST(issue.id AS CHAR), ": ", issue.subject) AS name,
       (SELECT node.lft
          FROM bitnami_redmine.projects AS node
         WHERE node.id = issue.project_id) AS lft,       
       (SELECT (COUNT(parent.name) - 1) AS depth
          FROM bitnami_redmine.projects AS node,
               bitnami_redmine.projects AS parent
         WHERE node.lft BETWEEN parent.lft AND parent.rgt
           AND node.id = issue.project_id         
         GROUP BY node.name) + 1 AS depth
FROM bitnami_redmine.issues AS issue
UNION ALL
SELECT node.id AS project_id, NULL AS issue_id,
       node.name, node.lft, (COUNT(parent.name) - 1) AS depth
FROM bitnami_redmine.projects AS node,
     bitnami_redmine.projects AS parent
WHERE node.lft BETWEEN parent.lft AND parent.rgt
GROUP BY node.name) comb
ORDER BY comb.lft, comb.depth

# pull in needed attributes


SELECT comb.project_id, comb.issue_id,
       CONCAT(REPEAT('     ', comb.depth), comb.name) AS name,
       (SELECT CASE WHEN EXISTS (
          SELECT * FROM bitnami_redmine.enabled_modules
           WHERE project_id = comb.project_id AND name = 'time_tracking'
        ) THEN TRUE ELSE FALSE END) AS allowTimeInput
  FROM (
    SELECT issue.project_id , issue.id AS issue_id,
           CONCAT("Issue ", CAST(issue.id AS CHAR), ": ", issue.subject) AS name,
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
               AND members.user_id = 30
             GROUP BY node.name) + 1 AS depth
      FROM bitnami_redmine.issues AS issue
     WHERE issue.assigned_to_id = 30
        OR EXISTS (SELECT id FROM bitnami_redmine.watchers 
                    WHERE watchers.watchable_type = 'Issue' 
                      AND watchers.watchable_id = issue.id 
                      AND watchers.user_id = 30)
     UNION ALL
    SELECT node.id AS project_id, NULL AS issue_id,
           node.name, node.lft, (COUNT(parent.name) - 1) AS depth
      FROM bitnami_redmine.members
      JOIN bitnami_redmine.projects AS node 
        ON node.id = members.project_id
      JOIN bitnami_redmine.projects AS parent 
        ON node.lft BETWEEN parent.lft AND parent.rgt
     WHERE node.status = 1 AND members.user_id = 30
     GROUP BY node.name) comb
  JOIN bitnami_redmine.time_entries entries 
    ON entries.project_id = comb.project_id
   AND IFNULL(entries.issue_id,0) = IFNULL(comb.issue_id,0)
   AND entries.user_id = 30 AND spent_on = '2015-05-31'
 ORDER BY comb.lft, comb.depth


# Pull time_entries for user

SELECT time_entries.id, time_entries.project_id,
       time_entries.user_id, time_entries.issue_id,
       time_entries.hours, time_entries.spent_on,
       time_entries.activity_id,
       (SELECT name FROM bitnami_redmine.enumerations
         WHERE enumerations.type = 'TimeEntryActivity'
           AND enumerations.id = time_entries.activity_id) AS activity_name,
       (SELECT value
          FROM bitnami_redmine.custom_values
         WHERE custom_values.custom_field_id = 3
           AND custom_values.customized_id = time_entries.id) AS isTRI,
       (SELECT GROUP_CONCAT(value ORDER BY value ASC SEPARATOR ', ')
          FROM bitnami_redmine.custom_values
         WHERE custom_values.custom_field_id = 4
           AND custom_values.customized_id = time_entries.id) AS Effort,
       (SELECT IF(value = '', NULL, value) as value
          FROM bitnami_redmine.custom_values
         WHERE custom_values.custom_field_id = 10
           AND custom_values.customized_id = time_entries.id) AS Investigator      
  FROM bitnami_redmine.time_entries
 WHERE time_entries.user_id = 30 
   AND time_entries.spent_on = '2015-05-31'


# FINAL BASE QUERY! -pull in needed attributes and add filters

SELECT @row := @row + 1 as row, comb.* FROM 
(SELECT proj.project_id, proj.issue_id, 
       entries.id AS time_entry_id, entries.user_id,
       CONCAT(REPEAT('     ', proj.depth), proj.name) AS name,
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
       0 AS action_edited, proj.closed_on
  FROM (
    SELECT issue.project_id , issue.id AS issue_id,
           CONCAT("Issue ", CAST(issue.id AS CHAR), ": ", issue.subject) AS name,
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
               AND members.user_id = 41
             GROUP BY node.name) + 1 AS depth,
           issue.closed_on
      FROM bitnami_redmine.issues AS issue
     WHERE issue.assigned_to_id = 41
        OR EXISTS (SELECT id FROM bitnami_redmine.watchers 
                    WHERE watchers.watchable_type = 'Issue' 
                      AND watchers.watchable_id = issue.id 
                      AND watchers.user_id = 41)
    UNION ALL
    SELECT node.id AS project_id, NULL AS issue_id,
           node.name, node.lft, (COUNT(parent.name) - 1) AS depth,
           NULL AS closed_on
      FROM bitnami_redmine.members
      JOIN bitnami_redmine.projects AS node 
        ON node.id = members.project_id
      JOIN bitnami_redmine.projects AS parent 
        ON node.lft BETWEEN parent.lft AND parent.rgt
     WHERE node.status = 1 AND members.user_id = 41
     GROUP BY node.name) proj
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
 WHERE time_entries.user_id = 41 
   AND time_entries.spent_on = '2015-07-31') entries 
    ON entries.project_id = proj.project_id
   AND IFNULL(entries.issue_id,0) = IFNULL(proj.issue_id,0)
 WHERE proj.closed_on IS NULL
    OR '2015-07-31' < DATE_ADD(proj.closed_on, INTERVAL 7 DAY)
 ORDER BY proj.lft, proj.depth) comb,
 (SELECT @row := 0) r 




# REPORTS

# Project View

CREATE VIEW project_info AS
SELECT node.id AS project_id, node.name, 
       longname.project_longname AS longname,
       node.lft, (COUNT(parent.name) - 1) AS depth,
       (SELECT CASE WHEN EXISTS (
          SELECT * FROM bitnami_redmine.enabled_modules
           WHERE project_id = node.id AND name = 'time_tracking'
        ) THEN TRUE ELSE FALSE END) AS allowTimeInput       
  FROM bitnami_redmine.projects AS node 
  JOIN bitnami_redmine.projects AS parent 
    ON node.lft BETWEEN parent.lft AND parent.rgt
  JOIN bitnami_redmine.project_longname AS longname
    ON longname.project_id = node.id
 WHERE node.status = 1
 GROUP BY node.name
 ORDER BY node.lft;

# Time Entry View

CREATE VIEW time_entry_info AS
SELECT time_entries.id AS time_entry_id, time_entries.project_id,
       time_entries.user_id, CONCAT(users.firstname, ' ', users.lastname) AS user_name,
       time_entries.issue_id, issues.subject AS issue_subject,
       time_entries.hours, time_entries.spent_on, time_entries.activity_id, 
       (SELECT name FROM bitnami_redmine.enumerations
         WHERE enumerations.type = 'TimeEntryActivity'
           AND enumerations.id = time_entries.activity_id) AS activity_name,
       (SELECT id
          FROM bitnami_redmine.custom_values
         WHERE custom_values.custom_field_id = 3
           AND custom_values.customized_id = time_entries.id) AS istri_cvid,           
        IFNULL((SELECT CASE WHEN value = 0 THEN 'false'
                    WHEN value = NULL THEN 'false'
                    ELSE 'true' END
          FROM bitnami_redmine.custom_values
         WHERE custom_values.custom_field_id = 3
           AND custom_values.customized_id = time_entries.id),'false') AS istri_value,
        (SELECT CASE WHEN value = 0 THEN 'false'
                    WHEN value = NULL THEN 'false'
                    ELSE 'true' END
          FROM bitnami_redmine.custom_values
         WHERE custom_values.custom_field_id = 3
           AND custom_values.customized_id = time_entries.id) AS istri_value2,           
       (SELECT GROUP_CONCAT(id ORDER BY id ASC SEPARATOR ', ')
          FROM bitnami_redmine.custom_values
         WHERE custom_values.custom_field_id = 4
           AND custom_values.customized_id = time_entries.id) AS effort_cvids,
       (SELECT GROUP_CONCAT(value ORDER BY id ASC SEPARATOR ', ')
          FROM bitnami_redmine.custom_values
         WHERE custom_values.custom_field_id = 4
           AND custom_values.customized_id = time_entries.id) AS effort_values,
       IF(time_entries.comments = '', NULL, time_entries.comments) as comments       
  FROM bitnami_redmine.time_entries
  LEFT JOIN bitnami_redmine.users
    ON users.id = time_entries.user_id
  LEFT JOIN bitnami_redmine.issues
    ON issues.id = time_entries.issue_id



# Project drop-down
CREATE VIEW project_drop AS
SELECT p.longname FROM project_info p
  JOIN time_entry_info t
    ON t.project_id = p.project_id
 WHERE p.allowTimeInput = 1
 GROUP BY p.longname

# User drop-down
CREATE VIEW user_drop AS
SELECT user_name FROM time_entry_info ORDER BY user_name

# Activity drop-down
CREATE VIEW activity_drop AS
SELECT activity_name FROM time_entry_info ORDER BY activity_name

# Project long name (with parents)
CREATE VIEW project_parents AS
SELECT node.id AS 'group_id', parent.*  
  FROM projects node
  JOIN projects parent
    ON node.lft BETWEEN parent.lft AND parent.rgt
 ORDER BY parent.lft

# Project long name (with parents)
CREATE VIEW project_longname AS
SELECT project_parents.group_id AS project_id, 
       GROUP_CONCAT(project_parents.name ORDER BY project_parents.lft SEPARATOR ' | ') AS project_longname
  FROM project_parents
 GROUP BY project_parents.group_id



# --------------------------------REPORTS----------------------------------------------

# Time Entry Report
SELECT p.project_id, p.longname AS 'Project', p.lft, p.depth,
       t.time_entry_id, t.user_id, t.user_name AS 'User',
       t.issue_id, t.issue_subject AS 'Issue', t.hours AS 'Hours',
       t.spent_on AS 'Date', t.activity_id, t.activity_name AS 'Activity',
       t.effort_values AS 'Effort', t.istri_value AS 'TRI', t.comments AS 'Notes'
  FROM project_info p
  LEFT JOIN time_entry_info t
    ON t.project_id = p.project_id
 WHERE 1 = 1
   AND (t.spent_on BETWEEN "{{ date_range.start }}" AND "{{ date_range.end }}")
   AND ("{{ project_filter }}" = "ALL" OR p.longname = "{{ project_filter }}")
   AND ("{{ user_filter }}" = "ALL" OR t.user_name = "{{ user_filter }}")
   AND ("{{ activity_filter }}" = "ALL" OR t.activity_name = "{{ activity_filter }}")
   AND ("{{ tri_filter }}" = "ALL" OR t.istri_value = "{{ tri_filter }}")
 ORDER BY p.lft,t.user_id,t.spent_on
/*
   AND ("{{ efforts }}" IS NULL OR t.effort_values REGEXP "{{ efforts }}");  #must have single quotes and | between values for regexp
*/


# Project Totals Reports

SELECT p.longname AS 'Project', SUM(t.hours) AS 'Hours'
  FROM project_info p
  JOIN time_entry_info t
    ON t.project_id = p.project_id
 WHERE 1 = 1
   AND (t.spent_on BETWEEN "{{ date_range.start }}" AND "{{ date_range.end }}")
   AND ("{{ project_filter }}" = "ALL" OR p.longname = "{{ project_filter }}")
   AND ("{{ user_filter }}" = "ALL" OR t.user_name = "{{ user_filter }}")
   AND ("{{ tri_filter }}" = "ALL" OR t.istri_value = "{{ tri_filter }}")
 GROUP BY p.name
 
# Missing Hours
-- Week

SELECT t.user_name AS 'User', 
       SUM(t.hours) AS 'Hours',
       YEARWEEK(t.spent_on) AS 'Week'
  FROM time_entry_info t
 WHERE t.user_id = 41
 GROUP BY YEARWEEK(t.spent_on), t.user_name  

-- Daily

SELECT t.user_name AS 'User', 
       SUM(t.hours) AS 'Hours', 
       t.spent_on AS 'Date',
       DAYNAME(t.spent_on) AS 'Day'
  FROM time_entry_info t
 WHERE t.user_id = 41
 GROUP BY t.spent_on, t.user_name  

     