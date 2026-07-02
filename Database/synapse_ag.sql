-- ============================================================
-- SYNAPSE Database Schema
-- A Unified Approach to Campus Clinic Management
-- MySQL 8.x / MariaDB 10.6+
-- ============================================================
-- 
-- CHANGELOG:
--   v1.0 - Initial schema (22 tables)
--   v1.1 - Schema gap fixes + AI feature tables
--          * Fixed: refresh_tokens.user_id type mismatch (INT → BIGINT)
--          * Added: crisis_alerts table (PHQ-9 Item 9 tracking)
--          * Added: consecutive_no_shows to students
--          * Added: hash + previous_hash to audit_logs
--          * Added: offline_checkin_buffer table
--          * Added: triage_priority to consultations
--          * Added: AI tables (triage, forecasts, risk scores, summaries)
--          * Added: scheduling_analytics table
--          * Added: volunteer_workload_scores table (removed July 2026 with PASIMEO module)
-- ============================================================

CREATE DATABASE IF NOT EXISTS synapse_ag
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE synapse_ag;

-- ============================================================
-- 1. AUTH & RBAC
-- ============================================================

CREATE TABLE users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(255) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  first_name VARCHAR(100) NOT NULL,
  last_name VARCHAR(100) NOT NULL,
  middle_name VARCHAR(100) NULL,
  avatar_url LONGTEXT NULL,
  phone VARCHAR(20) NULL,
  is_active BOOLEAN DEFAULT TRUE,
  email_verified_at TIMESTAMP NULL,
  totp_secret VARCHAR(64) NULL COMMENT 'Base32 TOTP secret',
  two_factor_enabled BOOLEAN DEFAULT FALSE,
  backup_codes JSON NULL COMMENT 'Hashed backup codes array',
  last_login_at TIMESTAMP NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_users_active (is_active),
  INDEX idx_users_email (email)
) ENGINE=InnoDB;

CREATE TABLE roles (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50) NOT NULL UNIQUE,
  display_name VARCHAR(100) NOT NULL,
  description TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE permissions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL UNIQUE,
  module VARCHAR(50) NOT NULL,
  description TEXT NULL,
  INDEX idx_permissions_module (module)
) ENGINE=InnoDB;

CREATE TABLE role_permissions (
  role_id INT UNSIGNED NOT NULL,
  permission_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (role_id, permission_id),
  FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
  FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE user_roles (
  user_id BIGINT UNSIGNED NOT NULL,
  role_id INT UNSIGNED NOT NULL,
  assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, role_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- 2. STUDENT IDENTITY
-- ============================================================

CREATE TABLE students (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL UNIQUE,
  student_number VARCHAR(50) NOT NULL UNIQUE,
  qr_code VARCHAR(255) NULL UNIQUE,
  rfid_tag VARCHAR(255) NULL UNIQUE,
  course VARCHAR(100) NULL,
  year_level TINYINT NULL,
  section VARCHAR(20) NULL,
  date_of_birth DATE NULL,
  gender ENUM('male','female','other') NULL,
  address TEXT NULL,
  blood_type VARCHAR(5) NULL,
  consecutive_no_shows INT DEFAULT 0 COMMENT 'FIX: Three-strike no-show counter for counselling',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_students_qr (qr_code),
  INDEX idx_students_rfid (rfid_tag)
) ENGINE=InnoDB;

CREATE TABLE emergency_contacts (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  student_id BIGINT UNSIGNED NOT NULL,
  contact_name VARCHAR(150) NOT NULL,
  relationship VARCHAR(50) NOT NULL,
  phone VARCHAR(20) NOT NULL,
  is_primary BOOLEAN DEFAULT FALSE,
  FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  INDEX idx_emergency_student (student_id)
) ENGINE=InnoDB;

-- ============================================================
-- 3. CLINIC MANAGEMENT CORE
-- ============================================================

CREATE TABLE clinic_staff_schedules (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  day_of_week TINYINT NOT NULL COMMENT '0=Sun, 1=Mon ... 6=Sat',
  shift_start TIME NOT NULL,
  shift_end TIME NOT NULL,
  schedule_type ENUM('regular','on_call','substitute') DEFAULT 'regular',
  effective_from DATE NULL,
  effective_until DATE NULL,
  is_active BOOLEAN DEFAULT TRUE,
  notes TEXT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_clinic_schedule_user (user_id, day_of_week)
) ENGINE=InnoDB;

CREATE TABLE consultations (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  student_id BIGINT UNSIGNED NOT NULL,
  attending_user_id BIGINT UNSIGNED NOT NULL,
  chief_complaint TEXT NOT NULL,
  diagnosis TEXT NULL,
  notes TEXT NULL,
  /* Lifecycle:
       in_progress  — patient has checked in, waiting in lobby
       called       — staff pressed "Call Next"; patient name on lobby screen
       in_session   — staff pressed "Start"; patient is in the room
       completed    — diagnosis + notes saved, consultation closed
       follow_up    — closed but needs a return visit; still in stats */
  status ENUM('in_progress','called','in_session','completed','follow_up') DEFAULT 'in_progress',
  check_in_method ENUM('qr','rfid','manual') DEFAULT 'manual',
  triage_priority ENUM('low','medium','high','urgent') NULL COMMENT 'AI Feature A: AI-suggested triage level',
  triage_override BOOLEAN DEFAULT FALSE COMMENT 'TRUE if staff overrode AI suggestion',
  consultation_date DATETIME NOT NULL,
  /* Queue-specific columns. queue_position is the patient's order
     in today's in_progress list (1-based) so the printed slip and the
     lobby display agree on the same number even after reorders. */
  queue_position INT NULL COMMENT 'Stable 1-based position in today\'s queue',
  called_at DATETIME NULL COMMENT 'When staff pressed "Call Next" for this patient',
  called_by_user_id BIGINT UNSIGNED NULL COMMENT 'Staff who pressed Call Next',
  started_at DATETIME NULL COMMENT 'When staff pressed Start (patient in room)',
  completed_at DATETIME NULL COMMENT 'When staff pressed Complete',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  FOREIGN KEY (attending_user_id) REFERENCES users(id),
  FOREIGN KEY (called_by_user_id) REFERENCES users(id),
  INDEX idx_consult_student_date (student_id, consultation_date),
  INDEX idx_consult_attending (attending_user_id),
  INDEX idx_consult_status (status),
  INDEX idx_consult_triage (triage_priority),
  INDEX idx_consult_queue (status, consultation_date, queue_position)
) ENGINE=InnoDB;

CREATE TABLE consultation_vitals (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  consultation_id BIGINT UNSIGNED NOT NULL,
  temperature DECIMAL(4,1) NULL,
  blood_pressure_sys SMALLINT NULL,
  blood_pressure_dia SMALLINT NULL,
  heart_rate SMALLINT NULL,
  respiratory_rate SMALLINT NULL,
  weight_kg DECIMAL(5,2) NULL,
  height_cm DECIMAL(5,2) NULL,
  recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (consultation_id) REFERENCES consultations(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE allergies (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  student_id BIGINT UNSIGNED NOT NULL,
  allergen VARCHAR(200) NOT NULL,
  severity ENUM('mild','moderate','severe') DEFAULT 'mild',
  reaction TEXT NULL,
  noted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  INDEX idx_allergies_student (student_id)
) ENGINE=InnoDB;

-- ============================================================
-- 4. MEDICINE INVENTORY (Batch-Level)
-- ============================================================

CREATE TABLE medicines (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  generic_name VARCHAR(200) NOT NULL,
  brand_name VARCHAR(200) NULL,
  category VARCHAR(100) NULL,
  dosage_form VARCHAR(100) NULL,
  dosage_strength VARCHAR(100) NULL,
  unit VARCHAR(50) NOT NULL,
  reorder_threshold INT DEFAULT 10,
  description TEXT NULL,
  is_active BOOLEAN DEFAULT TRUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_medicines_name (generic_name),
  INDEX idx_medicines_category (category),
  INDEX idx_medicines_active (is_active)
) ENGINE=InnoDB;

CREATE TABLE medicine_batches (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  medicine_id BIGINT UNSIGNED NOT NULL,
  batch_number VARCHAR(100) NOT NULL,
  quantity_received INT NOT NULL,
  quantity_remaining INT NOT NULL,
  expiration_date DATE NOT NULL,
  received_date DATE NOT NULL,
  supplier VARCHAR(200) NULL,
  notes TEXT NULL,
  status ENUM('active','depleted','expired','recalled') DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (medicine_id) REFERENCES medicines(id) ON DELETE CASCADE,
  INDEX idx_batch_medicine_status (medicine_id, status),
  INDEX idx_batch_expiry (expiration_date)
) ENGINE=InnoDB;

CREATE TABLE treatments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  consultation_id BIGINT UNSIGNED NOT NULL,
  medicine_batch_id BIGINT UNSIGNED NULL,
  treatment_type ENUM('medication','first_aid','procedure','referral','other') NOT NULL,
  description TEXT NOT NULL,
  quantity_used INT NULL,
  administered_by BIGINT UNSIGNED NOT NULL,
  administered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (consultation_id) REFERENCES consultations(id) ON DELETE CASCADE,
  FOREIGN KEY (medicine_batch_id) REFERENCES medicine_batches(id) ON DELETE SET NULL,
  FOREIGN KEY (administered_by) REFERENCES users(id),
  INDEX idx_treatments_consult (consultation_id)
) ENGINE=InnoDB;

CREATE TABLE inventory_transactions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  medicine_batch_id BIGINT UNSIGNED NOT NULL,
  transaction_type ENUM('received','dispensed','expired','adjusted','returned') NOT NULL,
  quantity INT NOT NULL,
  reference_type VARCHAR(50) NULL COMMENT 'e.g. consultation, adjustment',
  reference_id BIGINT UNSIGNED NULL,
  performed_by BIGINT UNSIGNED NOT NULL,
  notes TEXT NULL,
  transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (medicine_batch_id) REFERENCES medicine_batches(id) ON DELETE CASCADE,
  FOREIGN KEY (performed_by) REFERENCES users(id),
  INDEX idx_inv_trans_batch (medicine_batch_id),
  INDEX idx_inv_trans_date (transaction_date)
) ENGINE=InnoDB;

-- ============================================================
-- 5. COUNSELLING CORE
-- ============================================================

CREATE TABLE counsellor_availability (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  counsellor_id BIGINT UNSIGNED NOT NULL,
  day_of_week TINYINT NOT NULL COMMENT '0=Sun, 1=Mon ... 6=Sat',
  start_time TIME NOT NULL,
  end_time TIME NOT NULL,
  max_slots INT DEFAULT 1,
  is_active BOOLEAN DEFAULT TRUE,
  FOREIGN KEY (counsellor_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_avail_counsellor (counsellor_id, day_of_week)
) ENGINE=InnoDB;

CREATE TABLE counselling_appointments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  student_id BIGINT UNSIGNED NOT NULL,
  counsellor_id BIGINT UNSIGNED NOT NULL,
  appointment_date DATE NOT NULL,
  start_time TIME NOT NULL,
  end_time TIME NOT NULL,
  type ENUM('initial','follow_up','crisis','referral_based') DEFAULT 'initial',
  status ENUM('scheduled','confirmed','completed','cancelled','no_show') DEFAULT 'scheduled',
  reason TEXT NULL,
  session_notes TEXT NULL,
  cancellation_reason TEXT NULL,
  no_show_probability DECIMAL(5,4) NULL COMMENT 'AI Feature D: predicted no-show probability 0.0000-1.0000',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  FOREIGN KEY (counsellor_id) REFERENCES users(id),
  INDEX idx_appt_student_date (student_id, appointment_date),
  INDEX idx_appt_counsellor_date (counsellor_id, appointment_date),
  INDEX idx_appt_status (status)
) ENGINE=InnoDB;

CREATE TABLE assessment_templates (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  description TEXT NULL,
  type ENUM('screening','survey','intake','follow_up') NOT NULL,
  is_active BOOLEAN DEFAULT TRUE,
  created_by BIGINT UNSIGNED NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE assessment_questions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  template_id BIGINT UNSIGNED NOT NULL,
  question_text TEXT NOT NULL,
  question_type ENUM('likert','multiple_choice','text','yes_no','scale') NOT NULL,
  options JSON NULL,
  order_index INT DEFAULT 0,
  is_required BOOLEAN DEFAULT TRUE,
  FOREIGN KEY (template_id) REFERENCES assessment_templates(id) ON DELETE CASCADE,
  INDEX idx_questions_template (template_id, order_index)
) ENGINE=InnoDB;

CREATE TABLE assessment_responses (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  template_id BIGINT UNSIGNED NOT NULL,
  student_id BIGINT UNSIGNED NOT NULL,
  appointment_id BIGINT UNSIGNED NULL,
  responses JSON NOT NULL,
  total_score INT NULL,
  submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (template_id) REFERENCES assessment_templates(id),
  FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  FOREIGN KEY (appointment_id) REFERENCES counselling_appointments(id) ON DELETE SET NULL,
  INDEX idx_responses_student (student_id),
  INDEX idx_responses_template (template_id)
) ENGINE=InnoDB;

CREATE TABLE referrals (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  student_id BIGINT UNSIGNED NOT NULL,
  referred_by BIGINT UNSIGNED NOT NULL,
  referred_to BIGINT UNSIGNED NOT NULL,
  direction ENUM('clinic_to_counselling','counselling_to_clinic') NOT NULL,
  reason TEXT NOT NULL,
  priority ENUM('routine','urgent','emergency') DEFAULT 'routine',
  status ENUM('pending','accepted','in_progress','completed','declined') DEFAULT 'pending',
  notes TEXT NULL,
  source_consultation_id BIGINT UNSIGNED NULL,
  source_appointment_id BIGINT UNSIGNED NULL,
  resolved_at TIMESTAMP NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  FOREIGN KEY (referred_by) REFERENCES users(id),
  FOREIGN KEY (referred_to) REFERENCES users(id),
  FOREIGN KEY (source_consultation_id) REFERENCES consultations(id) ON DELETE SET NULL,
  FOREIGN KEY (source_appointment_id) REFERENCES counselling_appointments(id) ON DELETE SET NULL,
  INDEX idx_referrals_student (student_id),
  INDEX idx_referrals_status (status),
  INDEX idx_referrals_direction (direction)
) ENGINE=InnoDB;

-- ============================================================
-- 5.1 CRISIS ALERTS (GAP FIX: PHQ-9 Item 9 tracking)
-- ============================================================

CREATE TABLE crisis_alerts (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  student_id BIGINT UNSIGNED NOT NULL,
  assessment_response_id BIGINT UNSIGNED NOT NULL,
  trigger_source ENUM('phq9_item9','gad7_threshold','manual','ai_risk_score') NOT NULL
    COMMENT 'What triggered the alert',
  severity ENUM('moderate','high','critical') NOT NULL DEFAULT 'high',
  status ENUM('triggered','acknowledged','in_progress','resolved','escalated') DEFAULT 'triggered',
  assigned_counsellor_id BIGINT UNSIGNED NULL,
  acknowledged_at TIMESTAMP NULL COMMENT 'Must be within 30 min per protocol',
  acknowledged_by BIGINT UNSIGNED NULL,
  resolution_notes TEXT NULL,
  resolved_at TIMESTAMP NULL,
  escalated_to BIGINT UNSIGNED NULL COMMENT 'Head counsellor on escalation',
  escalated_at TIMESTAMP NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  FOREIGN KEY (assessment_response_id) REFERENCES assessment_responses(id) ON DELETE CASCADE,
  FOREIGN KEY (assigned_counsellor_id) REFERENCES users(id),
  FOREIGN KEY (acknowledged_by) REFERENCES users(id),
  FOREIGN KEY (escalated_to) REFERENCES users(id),
  INDEX idx_crisis_student (student_id),
  INDEX idx_crisis_status (status),
  INDEX idx_crisis_created (created_at)
) ENGINE=InnoDB;

-- ============================================================
-- 7. SHARED / CROSS-CUTTING
-- ============================================================

CREATE TABLE audit_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NULL,
  action VARCHAR(100) NOT NULL,
  module VARCHAR(50) NOT NULL,
  entity_type VARCHAR(100) NULL,
  entity_id BIGINT UNSIGNED NULL,
  old_values JSON NULL,
  new_values JSON NULL,
  ip_address VARCHAR(45) NULL,
  user_agent VARCHAR(500) NULL,
  hash VARCHAR(64) NULL COMMENT 'FIX: SHA-256 hash of this log entry for integrity',
  previous_hash VARCHAR(64) NULL COMMENT 'FIX: Hash of previous entry for chain verification',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_audit_module_date (module, created_at),
  INDEX idx_audit_user_date (user_id, created_at),
  INDEX idx_audit_entity (entity_type, entity_id)
) ENGINE=InnoDB;

CREATE TABLE notifications (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  type VARCHAR(100) NOT NULL,
  title VARCHAR(255) NOT NULL,
  message TEXT NULL,
  data JSON NULL,
  is_read BOOLEAN DEFAULT FALSE,
  read_at TIMESTAMP NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_notif_user_read (user_id, is_read, created_at)
) ENGINE=InnoDB;

CREATE TABLE departments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL UNIQUE,
  code VARCHAR(20) NOT NULL UNIQUE,
  description TEXT NULL,
  is_active BOOLEAN DEFAULT TRUE
) ENGINE=InnoDB;

CREATE TABLE report_configurations (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  module VARCHAR(50) NOT NULL,
  report_type ENUM('clinic_stats','inventory_summary','appointment_trends','outreach_summary','referral_report','custom') NOT NULL,
  parameters JSON NULL,
  schedule_cron VARCHAR(100) NULL,
  is_active BOOLEAN DEFAULT TRUE,
  created_by BIGINT UNSIGNED NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE generated_reports (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  config_id BIGINT UNSIGNED NULL,
  name VARCHAR(255) NOT NULL,
  module VARCHAR(50) NOT NULL,
  file_path VARCHAR(500) NULL,
  format ENUM('pdf','csv','xlsx','json') DEFAULT 'pdf',
  parameters_used JSON NULL,
  ai_summary TEXT NULL COMMENT 'AI Feature E: NLP-generated narrative summary',
  generated_by BIGINT UNSIGNED NULL,
  generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (config_id) REFERENCES report_configurations(id) ON DELETE SET NULL,
  FOREIGN KEY (generated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE system_modules (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL UNIQUE,
  display_name VARCHAR(150) NOT NULL,
  description TEXT NULL,
  version VARCHAR(20) NULL,
  is_enabled BOOLEAN DEFAULT TRUE,
  config JSON NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- FIX: user_id type corrected from INT UNSIGNED to BIGINT UNSIGNED
CREATE TABLE IF NOT EXISTS refresh_tokens (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  token_hash VARCHAR(64) NOT NULL COMMENT 'SHA-256 hash of refresh token',
  family VARCHAR(36) NOT NULL COMMENT 'UUID linking rotation chain',
  expires_at DATETIME NOT NULL,
  is_revoked BOOLEAN DEFAULT FALSE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_rt_user_id (user_id),
  INDEX idx_rt_token_hash (token_hash),
  INDEX idx_rt_family (family),
  INDEX idx_rt_expires (expires_at),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- 7.1 OFFLINE IoT BUFFER (GAP FIX)
-- ============================================================

CREATE TABLE offline_checkin_buffer (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  student_identifier VARCHAR(255) NOT NULL COMMENT 'QR code or RFID tag value',
  scan_method ENUM('qr','rfid') NOT NULL,
  station_id VARCHAR(50) NULL COMMENT 'ID of the scanner station',
  scanned_at DATETIME NOT NULL COMMENT 'Local time of actual scan',
  sync_status ENUM('pending','synced','failed','duplicate') DEFAULT 'pending',
  synced_at TIMESTAMP NULL,
  error_message TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_offline_sync_status (sync_status),
  INDEX idx_offline_scanned (scanned_at)
) ENGINE=InnoDB;

-- ============================================================
-- 8. AI FEATURE TABLES
-- ============================================================

-- 8.1 AI Feature A: Smart Triage Predictions
CREATE TABLE ai_triage_predictions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  consultation_id BIGINT UNSIGNED NOT NULL,
  student_id BIGINT UNSIGNED NOT NULL,
  input_text TEXT NOT NULL COMMENT 'Chief complaint text analyzed',
  predicted_priority ENUM('low','medium','high','urgent') NOT NULL,
  confidence_score DECIMAL(5,4) NOT NULL COMMENT '0.0000-1.0000',
  model_version VARCHAR(50) NOT NULL COMMENT 'e.g. tfidf_lr_v1, gemini_1.5',
  features_used JSON NULL COMMENT 'Feature vector: allergy count, visit history, vitals flags',
  staff_decision ENUM('accepted','overridden') NULL,
  staff_priority ENUM('low','medium','high','urgent') NULL COMMENT 'Final priority if overridden',
  decided_by BIGINT UNSIGNED NULL,
  decided_at TIMESTAMP NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (consultation_id) REFERENCES consultations(id) ON DELETE CASCADE,
  FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  FOREIGN KEY (decided_by) REFERENCES users(id),
  INDEX idx_triage_consult (consultation_id),
  INDEX idx_triage_student (student_id),
  INDEX idx_triage_priority (predicted_priority),
  INDEX idx_triage_staff_decision (staff_decision)
) ENGINE=InnoDB;

-- 8.2 AI Feature B: Predictive Inventory Forecasts
CREATE TABLE ai_inventory_forecasts (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  medicine_id BIGINT UNSIGNED NOT NULL,
  forecast_date DATE NOT NULL COMMENT 'Date this forecast was generated',
  forecast_period_start DATE NOT NULL,
  forecast_period_end DATE NOT NULL,
  predicted_daily_usage DECIMAL(10,4) NOT NULL,
  predicted_stockout_date DATE NULL COMMENT 'Estimated date stock reaches 0',
  predicted_reorder_date DATE NULL COMMENT 'Estimated date stock reaches reorder threshold',
  current_stock INT NOT NULL COMMENT 'Stock at time of forecast',
  reorder_threshold INT NOT NULL COMMENT 'Threshold at time of forecast',
  model_type ENUM('moving_average','exponential_smoothing','arima','linear_regression') NOT NULL,
  seasonality_factor DECIMAL(5,4) NULL COMMENT 'Seasonal multiplier applied',
  confidence_interval_lower DECIMAL(10,4) NULL,
  confidence_interval_upper DECIMAL(10,4) NULL,
  accuracy_metrics JSON NULL COMMENT 'MAE, RMSE, MAPE from backtest',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (medicine_id) REFERENCES medicines(id) ON DELETE CASCADE,
  INDEX idx_forecast_medicine (medicine_id),
  INDEX idx_forecast_date (forecast_date),
  INDEX idx_forecast_stockout (predicted_stockout_date),
  UNIQUE INDEX idx_forecast_medicine_date (medicine_id, forecast_date)
) ENGINE=InnoDB;

-- 8.3 AI Feature C: Mental Health Risk Scores
CREATE TABLE ai_risk_scores (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  student_id BIGINT UNSIGNED NOT NULL,
  assessment_response_id BIGINT UNSIGNED NULL COMMENT 'Triggering response, if any',
  score_type ENUM('phq9_trend','gad7_trend','composite','anomaly') NOT NULL,
  risk_level ENUM('low','moderate','elevated','high','critical') NOT NULL,
  current_score INT NULL COMMENT 'Latest screening score',
  trend_slope DECIMAL(8,4) NULL COMMENT 'Linear regression slope (positive = worsening)',
  trend_direction ENUM('improving','stable','worsening','rapid_decline') NOT NULL,
  anomaly_detected BOOLEAN DEFAULT FALSE COMMENT 'TRUE if score jump exceeds 2 SD',
  anomaly_magnitude DECIMAL(8,4) NULL COMMENT 'Z-score of the jump',
  data_points_used INT NOT NULL COMMENT 'Number of historical scores analyzed',
  prediction_window_days INT DEFAULT 30 COMMENT 'How far ahead the trend projects',
  projected_score INT NULL COMMENT 'Projected score at end of prediction window',
  model_version VARCHAR(50) NOT NULL,
  counsellor_notified BOOLEAN DEFAULT FALSE,
  notified_at TIMESTAMP NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  FOREIGN KEY (assessment_response_id) REFERENCES assessment_responses(id) ON DELETE SET NULL,
  INDEX idx_risk_student (student_id),
  INDEX idx_risk_level (risk_level),
  INDEX idx_risk_trend (trend_direction),
  INDEX idx_risk_created (created_at),
  INDEX idx_risk_student_type (student_id, score_type)
) ENGINE=InnoDB;

-- 8.4 AI Feature D: Scheduling Analytics
CREATE TABLE scheduling_analytics (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  counsellor_id BIGINT UNSIGNED NOT NULL,
  day_of_week TINYINT NOT NULL COMMENT '0=Sun ... 6=Sat',
  time_slot TIME NOT NULL,
  total_appointments INT DEFAULT 0,
  total_no_shows INT DEFAULT 0,
  no_show_rate DECIMAL(5,4) DEFAULT 0.0000 COMMENT 'Calculated no-show rate for this slot',
  avg_utilization DECIMAL(5,4) DEFAULT 0.0000 COMMENT 'Slot fill rate',
  recommended_overbooking INT DEFAULT 0 COMMENT 'Suggested extra slots based on no-show rate',
  last_calculated_at TIMESTAMP NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (counsellor_id) REFERENCES users(id) ON DELETE CASCADE,
  UNIQUE INDEX idx_sched_analytics_unique (counsellor_id, day_of_week, time_slot),
  INDEX idx_sched_no_show_rate (no_show_rate DESC)
) ENGINE=InnoDB;

-- 8.5 AI Feature E: NLP-Generated Report Summaries
CREATE TABLE ai_generated_summaries (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  report_id BIGINT UNSIGNED NULL COMMENT 'Links to generated_reports if applicable',
  module VARCHAR(50) NOT NULL,
  period_start DATE NOT NULL,
  period_end DATE NOT NULL,
  input_data JSON NOT NULL COMMENT 'Structured data fed to NLG engine',
  generated_summary TEXT NOT NULL COMMENT 'Natural language output',
  generation_method ENUM('template_nlg','llm_api','hybrid') NOT NULL,
  model_used VARCHAR(100) NULL COMMENT 'e.g. gemini-1.5-flash, template_v2',
  tokens_used INT NULL COMMENT 'API token count if LLM used',
  generated_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (report_id) REFERENCES generated_reports(id) ON DELETE SET NULL,
  FOREIGN KEY (generated_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_summary_module (module),
  INDEX idx_summary_period (period_start, period_end)
) ENGINE=InnoDB;

-- 8.6 (reserved — formerly PASIMEO Volunteer Workload Scores; removed when
-- the PASIMEO module was dropped from capstone scope in July 2026)

-- ============================================================
-- 9. CHECK CONSTRAINTS (MySQL 8.0.16+ / MariaDB 10.2+)
-- ============================================================
-- 
-- These enforce data validity at the engine level.
-- v1.2 addition: ~30 CHECK constraints across all domains.
-- ============================================================

-- 9.1 Vital Signs — physiologically valid ranges
ALTER TABLE consultation_vitals
  ADD CONSTRAINT chk_vitals_temp CHECK (temperature BETWEEN 30.0 AND 45.0),
  ADD CONSTRAINT chk_vitals_bp_sys CHECK (blood_pressure_sys BETWEEN 50 AND 300),
  ADD CONSTRAINT chk_vitals_bp_dia CHECK (blood_pressure_dia BETWEEN 20 AND 200),
  ADD CONSTRAINT chk_vitals_hr CHECK (heart_rate BETWEEN 20 AND 300),
  ADD CONSTRAINT chk_vitals_rr CHECK (respiratory_rate BETWEEN 5 AND 80),
  ADD CONSTRAINT chk_vitals_weight CHECK (weight_kg BETWEEN 1.00 AND 500.00),
  ADD CONSTRAINT chk_vitals_height CHECK (height_cm BETWEEN 30.00 AND 300.00);

-- 9.2 Day-of-week must be 0-6 (Sun-Sat)
ALTER TABLE clinic_staff_schedules
  ADD CONSTRAINT chk_css_dow CHECK (day_of_week BETWEEN 0 AND 6);

ALTER TABLE counsellor_availability
  ADD CONSTRAINT chk_ca_dow CHECK (day_of_week BETWEEN 0 AND 6);

ALTER TABLE scheduling_analytics
  ADD CONSTRAINT chk_sa_dow CHECK (day_of_week BETWEEN 0 AND 6);

-- 9.3 Time ordering — start must be before end
ALTER TABLE clinic_staff_schedules
  ADD CONSTRAINT chk_css_time_order CHECK (shift_start < shift_end);

ALTER TABLE counsellor_availability
  ADD CONSTRAINT chk_ca_time_order CHECK (start_time < end_time);

ALTER TABLE counselling_appointments
  ADD CONSTRAINT chk_appt_time_order CHECK (start_time < end_time);

-- 9.4 Quantities must be non-negative
ALTER TABLE medicine_batches
  ADD CONSTRAINT chk_batch_qty_received CHECK (quantity_received >= 0),
  ADD CONSTRAINT chk_batch_qty_remaining CHECK (quantity_remaining >= 0),
  ADD CONSTRAINT chk_batch_qty_logic CHECK (quantity_remaining <= quantity_received);

ALTER TABLE inventory_transactions
  ADD CONSTRAINT chk_inv_qty_positive CHECK (quantity > 0);

ALTER TABLE treatments
  ADD CONSTRAINT chk_treat_qty CHECK (quantity_used > 0 OR quantity_used IS NULL);

-- 9.5 Student field ranges
ALTER TABLE students
  ADD CONSTRAINT chk_student_no_shows CHECK (consecutive_no_shows >= 0),
  ADD CONSTRAINT chk_student_year CHECK (year_level BETWEEN 1 AND 6 OR year_level IS NULL);

-- 9.6 Assessment & AI score ranges
ALTER TABLE assessment_responses
  ADD CONSTRAINT chk_assess_score CHECK (total_score >= 0 OR total_score IS NULL);

ALTER TABLE ai_triage_predictions
  ADD CONSTRAINT chk_triage_confidence CHECK (confidence_score BETWEEN 0.0000 AND 1.0000);

ALTER TABLE counselling_appointments
  ADD CONSTRAINT chk_appt_noshow_prob CHECK (no_show_probability BETWEEN 0.0000 AND 1.0000 OR no_show_probability IS NULL);

ALTER TABLE scheduling_analytics
  ADD CONSTRAINT chk_sa_noshow_rate CHECK (no_show_rate BETWEEN 0.0000 AND 1.0000),
  ADD CONSTRAINT chk_sa_utilization CHECK (avg_utilization BETWEEN 0.0000 AND 1.0000);

ALTER TABLE ai_risk_scores
  ADD CONSTRAINT chk_risk_data_pts CHECK (data_points_used >= 1),
  ADD CONSTRAINT chk_risk_window CHECK (prediction_window_days >= 1);

-- 9.7 Slot and max constraints
ALTER TABLE counsellor_availability
  ADD CONSTRAINT chk_ca_max_slots CHECK (max_slots >= 1);

-- 9.8 Date ordering
ALTER TABLE medicine_batches
  ADD CONSTRAINT chk_batch_date_order CHECK (expiration_date > received_date);

ALTER TABLE ai_inventory_forecasts
  ADD CONSTRAINT chk_forecast_period CHECK (forecast_period_end >= forecast_
-- ============================================================
-- END OF SCHEMA
-- ============================================================
-- 
-- CHANGELOG:
--   v1.0 - Initial schema (22 tables)
--   v1.1 - Schema gap fixes + AI feature tables (28 tables)
--   v1.2 - Added ~30 CHECK constraints for data validation:
--          * Vital sign physiological ranges
--          * Day-of-week 0-6 validation
--          * Time ordering (start < end)
--          * Non-negative quantities & scores
--          * Score bounds (0-1 for probabilities, 0-100 for workload)
--          * Date ordering (expiration > received, end >= start)
--          * Slot/max minimums (≥1)
--   v1.3 - Added performance indexes for AI tables:
--          * Index on staff_decision in ai_triage_predictions
--          * Unique index on medicine_id + forecast_date in ai_inventory_forecasts
--          * Index on student_id + score_type in ai_risk_scores
--          * Index on period_start + period_end (no longer applicable; PASIMEO removed)
-- ============================================================