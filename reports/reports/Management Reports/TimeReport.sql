-- Time Report
-- This report will let you retrieve time for all projects using multiple
-- filters on project, user, dates, activity and efforts.
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


SELECT p.project_id, p.name AS 'Project', p.lft, p.depth,
       t.time_entry_id, t.user_id, t.user_name AS 'User',
       t.issue_id, t.issue_subject AS 'Issue', t.hours AS 'Hours',
       t.spent_on AS 'Date', t.activity_id, t.activity_name AS 'Activity',
       t.effort_values AS 'Effort', t.comments AS 'Notes'
  FROM project_info p
  LEFT JOIN time_entry_info t
    ON t.project_id = p.project_id
 WHERE 1 = 1
   AND (t.spent_on BETWEEN "{{ date_range.start }}" AND "{{ date_range.end }}")
   AND ("{{ project_filter }}" = "ALL" OR p.name = "{{ project_filter }}")
   AND ("{{ user_filter }}" = "ALL" OR t.user_name = "{{ user_filter }}")
   AND ("{{ activity_filter }}" = "ALL" OR t.activity_name = "{{ activity_filter }}")
/*
 WHERE 1 = 1
   AND (t.spent_on BETWEEN "{{ date_range.start }}" AND "{{ date_range.end }}")
   AND ("{{ project }}" IS NULL OR p.project_id = "{{ project }}")
   AND ("{{ user }}" IS NULL OR t.user_id = "{{ user }}")
   AND ("{{ activity }}" IS NULL OR t.activity_id = "{{ activity }}")
   AND ("{{ efforts }}" IS NULL OR t.effort_values REGEXP "{{ efforts }}");  #must have single quotes and | between values for regexp



SELECT
    order_id as `Order Id`,
    created_at as `Order Date`,
    CONCAT(customer_fname, " ", customer_lname) as `Customer Name`,
    customer_id as `Customer Id`,
    grand_total as `Grand Total`,
    status as `Order Status`
FROM
    orders
WHERE
    created_at BETWEEN "{{ range.start }}" AND "{{ range.end }}"

-- FILTER: {
--      column: "user_id",
--      filter: "drilldown",
--      params: {
--          report: "drilldown/UserDrillDown.sql"
--      }
-- }
*/