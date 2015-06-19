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
--      name: "user_filter",
--      display: "User",
--      type: "select",
--      database_options: {
--          table: "time_entry_info",
--          column: "user_name",
--          all: true
--      },
--      multiple: true
-- }


SELECT p.project_id, p.name, p.lft, p.depth,
       t.time_entry_id, t.user_id, t.user_name,
       t.issue_id, t.issue_subject,
       t.hours, t.spent_on, t.activity_id,
       t.effort_values, t.comments
  FROM project_info p
  LEFT JOIN time_entry_info t
    ON t.project_id = p.project_id
 WHERE 1 = 1
   AND (t.spent_on BETWEEN "{{ date_range.start }}" AND "{{ date_range.end }}")
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