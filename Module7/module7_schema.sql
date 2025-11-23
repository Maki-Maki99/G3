CREATE DATABASE IF NOT EXISTS module7_bi;
USE module7_bi;

CREATE TABLE IF NOT EXISTS daily_summary (
  id INT AUTO_INCREMENT PRIMARY KEY,
  date DATE NOT NULL UNIQUE,
  total_sales DECIMAL(12,2) DEFAULT 0,
  total_orders INT DEFAULT 0,
  total_customers INT DEFAULT 0,
  total_inventory_items INT DEFAULT 0,
  other_metrics JSON,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS module_pull_log (
  id INT AUTO_INCREMENT PRIMARY KEY,
  module_name VARCHAR(50),
  endpoint VARCHAR(255),
  pulled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  record_count INT DEFAULT 0,
  raw_response JSON,
  status VARCHAR(20)
);
