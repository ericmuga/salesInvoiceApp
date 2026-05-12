# Database Structure - Laravel Sales Invoice Application

## Tables

1. customers
2. items
3. sales_headers
4. sales_lines
5. posted_sales_headers
6. posted_sales_lines

---

# 1. customers

| Column | Type | Attributes |
|---|---|---|
| id | bigint | PK, Auto Increment |
| customer_no | string | Unique |
| name | string | |
| email | string | Nullable |
| phone | string | Nullable |
| address | text | Nullable |
| created_at | timestamp | |
| updated_at | timestamp | |

---

# 2. items

| Column | Type | Attributes |
|---|---|---|
| id | bigint | PK, Auto Increment |
| item_no | string | Unique |
| name | string | |
| description | text | Nullable |
| unit_price | decimal(18,2) | |
| unit_of_measure | string | |
| created_at | timestamp | |
| updated_at | timestamp | |

---

# 3. sales_headers

| Column | Type | Attributes |
|---|---|---|
| id | bigint | PK, Auto Increment |
| invoice_no | string | Unique |
| customer_id | bigint | FK -> customers.id |
| invoice_date | date | |
| due_date | date | Nullable |
| status | string | draft/released/posted/cancelled |
| subtotal | decimal(18,2) | |
| tax_amount | decimal(18,2) | |
| discount_amount | decimal(18,2) | |
| total_amount | decimal(18,2) | |
| created_at | timestamp | |
| updated_at | timestamp | |

---

# 4. sales_lines

| Column | Type | Attributes |
|---|---|---|
| id | bigint | PK, Auto Increment |
| sales_header_id | bigint | FK -> sales_headers.id |
| line_no | integer | |
| item_id | bigint | FK -> items.id |
| description | text | |
| quantity | decimal(18,2) | |
| unit_price | decimal(18,2) | |
| discount_amount | decimal(18,2) | |
| tax_amount | decimal(18,2) | |
| line_amount | decimal(18,2) | |
| created_at | timestamp | |
| updated_at | timestamp | |

---

# 5. posted_sales_headers

| Column | Type | Attributes |
|---|---|---|
| id | bigint | PK, Auto Increment |
| posted_invoice_no | string | Unique |
| source_invoice_no | string | Original invoice |
| customer_id | bigint | FK -> customers.id |
| customer_no | string | Snapshot |
| customer_name | string | Snapshot |
| invoice_date | date | |
| posting_date | date | |
| due_date | date | Nullable |
| subtotal | decimal(18,2) | |
| tax_amount | decimal(18,2) | |
| discount_amount | decimal(18,2) | |
| total_amount | decimal(18,2) | |
| created_at | timestamp | |
| updated_at | timestamp | |

---

# 6. posted_sales_lines

| Column | Type | Attributes |
|---|---|---|
| id | bigint | PK, Auto Increment |
| posted_sales_header_id | bigint | FK -> posted_sales_headers.id |
| item_id | bigint | Nullable FK -> items.id |
| item_no | string | Snapshot |
| description | text | |
| quantity | decimal(18,2) | |
| unit_price | decimal(18,2) | |
| discount_amount | decimal(18,2) | |
| tax_amount | decimal(18,2) | |
| line_amount | decimal(18,2) | |
| created_at | timestamp | |
| updated_at | timestamp | |

---

# Relationships

## customers

- hasMany sales_headers
- hasMany posted_sales_headers

## items

- hasMany sales_lines
- hasMany posted_sales_lines

## sales_headers

- belongsTo customers
- hasMany sales_lines

## sales_lines

- belongsTo sales_headers
- belongsTo items

## posted_sales_headers

- belongsTo customers
- hasMany posted_sales_lines

## posted_sales_lines

- belongsTo posted_sales_headers
- belongsTo items

---

# Posting Flow

1. Create sales invoice
2. Add sales lines
3. Post invoice
4. Copy records to posted tables
5. Mark original invoice as posted

---

# Suggested Status Values

```text
draft
released
posted
cancelled
