USE taxdb;

CREATE TABLE cases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    case_name VARCHAR(255) NOT NULL DEFAULT 'utlandLoenn',
    tax_status VARCHAR(50) NOT NULL DEFAULT 'resident',
    tax_question TEXT NOT NULL DEFAULT 'tax liability for salary from Country B',
    conclusion TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE tax_residency_questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    case_id INT NOT NULL,
    question_key VARCHAR(50) NOT NULL,
    answer VARCHAR(20) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (case_id) REFERENCES cases(id) ON DELETE CASCADE
);

ALTER TABLE cases ADD COLUMN double_tax_residency VARCHAR(255) DEFAULT NULL;

ALTER TABLE cases ADD COLUMN answers text DEFAULT NULL;
