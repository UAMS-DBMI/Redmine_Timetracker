-- Time Entry Report
-- This report will let you retrieve time entries for all projects using multiple
-- filters on project, user, dates and activity.
-- VARIABLE: {
--      name: "date_range",
--      display: "Date Range",
--      type: "daterange",
--      default: { start: "today", end: "today" }
-- }
-- VARIABLE: {
--      name: "project_filter",
--      display: "Project",
--      type: "select",
--      database_options: {
--          table: "project_drop",
--          column: "longname",
--          all: true
--      },
--      multiple: false
-- }
-- VARIABLE: {
--      name: "user_filter",
--      display: "User",
--      type: "select",
--      database_options: {
--          table: "user_drop",
--          column: "user_name",
--          all: true
--      },
--      multiple: false
-- }
-- VARIABLE: {
--      name: "activity_filter",
--      display: "Activity",
--      type: "select",
--      database_options: {
--          table: "activity_drop",
--          column: "activity_name",
--          all: true
--      },
--      multiple: false
-- }
-- VARIABLE: {
--      name: "tri_filter",
--      display: "TRI",
--      type: "select",
--      options: ["ALL","true","false"]
-- }
-- FILTER: {
--      filter: "hide",
--      column: "project_id"
-- }
-- FILTER: {
--      filter: "hide",
--      column: "lft"
-- }
-- FILTER: {
--      filter: "hide",
--      column: "depth"
-- }
-- FILTER: {
--      filter: "hide",
--      column: "time_entry_id"
-- }
-- FILTER: {
--      filter: "hide",
--      column: "user_id"
-- }
-- FILTER: {
--      filter: "hide",
--      column: "issue_id"
-- }
-- FILTER: {
--      filter: "hide",
--      column: "activity_id"
-- }


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
/*
   AND ("{{ efforts }}" IS NULL OR t.effort_values REGEXP "{{ efforts }}");  #must have single quotes and | between values for regexp
*/