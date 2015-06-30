-- Project Totals Report
-- This report will list each project and the total hours entered for each
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
--          column: "name",
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


SELECT p.name AS 'Project', SUM(t.hours) AS 'Hours'
  FROM project_info p
  JOIN time_entry_info t
    ON t.project_id = p.project_id
 WHERE 1 = 1
   AND (t.spent_on BETWEEN "{{ date_range.start }}" AND "{{ date_range.end }}")
   AND ("{{ project_filter }}" = "ALL" OR p.name = "{{ project_filter }}")
   AND ("{{ user_filter }}" = "ALL" OR t.user_name = "{{ user_filter }}")
   AND ("{{ tri_filter }}" = "ALL" OR t.istri_value = "{{ tri_filter }}")
 GROUP BY p.name