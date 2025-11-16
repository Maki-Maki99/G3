-- Module9/module9_schema.sql
-- Add to DB (safe, additive).

CREATE TABLE IF NOT EXISTS projects (
    project_id INT AUTO_INCREMENT PRIMARY KEY,
    project_code VARCHAR(50) UNIQUE,
    project_name VARCHAR(150) NOT NULL,
    description TEXT,
    start_date DATE,
    end_date DATE,
    status ENUM('Planned','In Progress','Completed','On Hold','Cancelled') DEFAULT 'Planned',
    budget_planned DECIMAL(14,2) DEFAULT 0.00,
    budget_actual DECIMAL(14,2) DEFAULT 0.00,
    percent_complete TINYINT UNSIGNED DEFAULT 0,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS project_tasks (
    task_id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    task_code VARCHAR(50),
    task_name VARCHAR(150) NOT NULL,
    description TEXT,
    assigned_employee_id INT NULL,
    assigned_resource VARCHAR(150) NULL,
    start_date DATE,
    end_date DATE,
    status ENUM('Pending','In Progress','Completed','Blocked') DEFAULT 'Pending',
    percent_complete TINYINT UNSIGNED DEFAULT 0,
    estimated_hours DECIMAL(6,2) DEFAULT 0,
    actual_hours DECIMAL(6,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(project_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS resource_assignments (
    assignment_id INT AUTO_INCREMENT PRIMARY KEY,
    task_id INT NOT NULL,
    employee_id INT NOT NULL,
    role VARCHAR(80),
    allocation_percent TINYINT UNSIGNED DEFAULT 100,
    assigned_on TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    released_on TIMESTAMP NULL,
    note TEXT,
    FOREIGN KEY (task_id) REFERENCES project_tasks(task_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;