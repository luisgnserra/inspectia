-- PostgreSQL schema for inspection system

-- Users table
CREATE TABLE IF NOT EXISTS users (
  id VARCHAR(255) PRIMARY KEY,
  email VARCHAR(255) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  plan VARCHAR(10) NOT NULL DEFAULT 'free' CHECK (plan IN ('free', 'pro')),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Password reset tokens table
CREATE TABLE IF NOT EXISTS password_reset_tokens (
  user_id VARCHAR(255) NOT NULL,
  token VARCHAR(255) NOT NULL,
  expiry INTEGER NOT NULL,
  PRIMARY KEY (user_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Companies table
CREATE TABLE IF NOT EXISTS companies (
  id VARCHAR(255) PRIMARY KEY,
  user_id VARCHAR(255) NOT NULL,
  name VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Inspections table
CREATE TABLE IF NOT EXISTS inspections (
  id VARCHAR(255) PRIMARY KEY,
  company_id VARCHAR(255) NOT NULL,
  title VARCHAR(255) NOT NULL,
  response_limit VARCHAR(10) NOT NULL DEFAULT 'unlimited' CHECK (response_limit IN ('single', 'multiple', 'unlimited')),
  max_responses INTEGER NULL,
  public_link VARCHAR(255) NOT NULL UNIQUE,
  status VARCHAR(10) NOT NULL DEFAULT 'draft' CHECK (status IN ('draft', 'published')),
  response_count INTEGER NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
);

-- Questions table
CREATE TABLE IF NOT EXISTS questions (
  id VARCHAR(255) PRIMARY KEY,
  inspection_id VARCHAR(255) NOT NULL,
  text TEXT NOT NULL,
  type VARCHAR(20) NOT NULL CHECK (type IN ('short_text', 'long_text', 'single_choice', 'multiple_choice', 'date', 'time')),
  is_required BOOLEAN NOT NULL DEFAULT false,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (inspection_id) REFERENCES inspections(id) ON DELETE CASCADE
);

-- Question options table
CREATE TABLE IF NOT EXISTS question_options (
  id VARCHAR(255) PRIMARY KEY,
  question_id VARCHAR(255) NOT NULL,
  text VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
);

-- Responses table
CREATE TABLE IF NOT EXISTS responses (
  id VARCHAR(255) PRIMARY KEY,
  inspection_id VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (inspection_id) REFERENCES inspections(id) ON DELETE CASCADE
);

-- Response answers table
CREATE TABLE IF NOT EXISTS response_answers (
  id VARCHAR(255) PRIMARY KEY,
  response_id VARCHAR(255) NOT NULL,
  question_id VARCHAR(255) NOT NULL,
  question_text TEXT NOT NULL,
  answer_text TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (response_id) REFERENCES responses(id) ON DELETE CASCADE,
  FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
);

-- Create indexes for better performance
CREATE INDEX idx_company_user ON companies(user_id);
CREATE INDEX idx_inspection_company ON inspections(company_id);
CREATE INDEX idx_question_inspection ON questions(inspection_id);
CREATE INDEX idx_option_question ON question_options(question_id);
CREATE INDEX idx_response_inspection ON responses(inspection_id);
CREATE INDEX idx_answer_response ON response_answers(response_id);
CREATE INDEX idx_answer_question ON response_answers(question_id);

-- Function and triggers for updating response count when a response is added
CREATE OR REPLACE FUNCTION update_response_count_insert()
RETURNS TRIGGER AS $$
BEGIN
  UPDATE inspections 
  SET response_count = response_count + 1 
  WHERE id = NEW.inspection_id;
  RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER after_response_insert
AFTER INSERT ON responses
FOR EACH ROW
EXECUTE FUNCTION update_response_count_insert();

-- Function and triggers for updating response count when a response is deleted
CREATE OR REPLACE FUNCTION update_response_count_delete()
RETURNS TRIGGER AS $$
BEGIN
  UPDATE inspections 
  SET response_count = response_count - 1 
  WHERE id = OLD.inspection_id;
  RETURN OLD;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER after_response_delete
AFTER DELETE ON responses
FOR EACH ROW
EXECUTE FUNCTION update_response_count_delete();

-- View for company usage report
CREATE OR REPLACE VIEW company_usage_report AS
SELECT 
  c.id as company_id,
  c.name as company_name,
  u.email as owner_email,
  u.plan as plan,
  COUNT(DISTINCT i.id) as total_inspections,
  COALESCE(SUM(i.response_count), 0) as total_responses
FROM 
  companies c
JOIN 
  users u ON c.user_id = u.id
LEFT JOIN 
  inspections i ON c.id = i.company_id
GROUP BY 
  c.id, c.name, u.email, u.plan;

-- Insert a test admin user (email: admin@example.com, password: password123)
-- Password hash is for 'password123'
INSERT INTO users (id, email, password_hash, plan) 
VALUES ('admin-user-id', 'admin@example.com', '$2y$10$e0MYzXdJIC8sKuEyYzLdN.LOg/5TfB8T0m9/I5oP1UaJkWLZ.Vx.K', 'pro');
