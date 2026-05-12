# Laravel Sales Invoice Application Plan

## Overview

This is a simple sales invoice application built using Laravel.

The application will support:

1. Customers
2. Items
3. Sales Invoices (Header + Lines)
4. Posted Sales Invoices (Header + Lines)

The workflow is:

- User creates a sales invoice
- User adds invoice lines
- User posts the invoice
- System copies the invoice into posted tables
- Original invoice is marked as posted

---

# Database Structure

---

# 1. Customers Table

## Table Name

```text
customers
