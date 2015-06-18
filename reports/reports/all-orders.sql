-- Time Report
-- This report will let you retrieve time for all projects using multiple
-- filters on project, user, dates, activity and efforts.
-- VARIABLE: {
--      name: "date_range",
--      display: "Date Range",
--      type: "daterange", 
--      default: { start: "yesterday", end: "yesterday" }
-- }
-- FILTER: { 
--      column: "Customer Name", 
--      filter: "drilldown",
--      params: {
--          macros: { "id": { column: "Customer Id" } },
--          report: "drilldown/customer-orders.sql"
--      }
-- }

SELECT p.project_id, p.name, p.lft, p.depth,
       t.time_entry_id, t.user_id, t.issue_id,
       t.hours, t.spent_on, t.activity_id,
       t.effort_values, t.comments
  FROM project_info p
  LEFT JOIN time_entry_info t
    ON t.project_id = p.project_id
/*
 WHERE 1 = 1
   AND ($project_id IS NULL OR p.project_id = $project_id)
   AND ($user_id IS NULL OR t.user_id = $user_id)
   AND ($spent_on_start IS NULL OR t.spent_on BETWEEN $spent_on_start AND $spent_on_end)
   AND (NULL IS NULL OR t.activity_id = NULL)
   AND ($effort_values IS NULL OR t.effort_values REGEXP $effort_values);  --must have single quotes and | between values for regexp



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
*/