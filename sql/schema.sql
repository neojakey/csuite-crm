CREATE DATABASE IF NOT EXISTS csuite_crm CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE csuite_crm;

CREATE TABLE IF NOT EXISTS contacts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_name VARCHAR(255) NOT NULL,
    contact_name VARCHAR(255),
    email VARCHAR(255),
    phone VARCHAR(50),
    website VARCHAR(255),
    source VARCHAR(100),
    status ENUM('prospect','warm','active','customer','dormant','lost') DEFAULT 'prospect',
    pipeline_stage VARCHAR(100),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS agent_sessions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    agent_role ENUM('CEO','CTO','CFO','CMO','CPO','COO') NOT NULL,
    mode VARCHAR(100),
    provider VARCHAR(20) NOT NULL DEFAULT 'claude',
    user_prompt TEXT NOT NULL,
    agent_output LONGTEXT,
    contact_id INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (contact_id) REFERENCES contacts(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS notes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255),
    body TEXT NOT NULL,
    contact_id INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (contact_id) REFERENCES contacts(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS tasks (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    status ENUM('todo','in_progress','done') DEFAULT 'todo',
    priority ENUM('low','medium','high') DEFAULT 'medium',
    due_date DATE NULL,
    contact_id INT UNSIGNED NULL,
    agent_session_id INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (contact_id) REFERENCES contacts(id) ON DELETE SET NULL,
    FOREIGN KEY (agent_session_id) REFERENCES agent_sessions(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS settings (
    setting_key VARCHAR(100) PRIMARY KEY,
    setting_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

INSERT IGNORE INTO settings (setting_key, setting_value) VALUES
('app_version', '1.0.0'),
('sprint_week', '1'),
('sprint_total_weeks', '12'),
('checkpoint_date', ''),
('checkpoint_inbound', '0'),
('checkpoint_product', '0'),
('checkpoint_energy', '0'),
('default_lang', 'en'),
('anthropic_api_key', ''),
('gemini_api_key', ''),
('perplexity_api_key', '');

-- Demo contacts — generic, no real personal data
INSERT IGNORE INTO contacts (id, company_name, contact_name, email, source, status, pipeline_stage, notes) VALUES
(1, 'Demo Company Ltd', 'Demo Contact', 'demo@example.com', 'inbound', 'prospect', 'Initial contact', 'Replace this with a real contact.'),
(2, 'Sample Prospect GmbH', 'Sample Lead', 'sample@example.com', 'linkedin', 'warm', 'Discovery call booked', 'Replace this with a real contact.');
