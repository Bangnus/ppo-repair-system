-- ===================================
-- Migration: Add repair type columns
-- Date: 2025-12-25
-- ===================================
-- เพิ่มคอลัมน์สำหรับประเภทการซ่อม, หมายเหตุ และรายละเอียดการซ่อม

USE repair_system;

ALTER TABLE repairs 
ADD COLUMN repair_type ENUM('self_repair', 'outsource') DEFAULT NULL AFTER status,
ADD COLUMN repair_notes TEXT DEFAULT NULL AFTER repair_type,
ADD COLUMN repair_details TEXT DEFAULT NULL AFTER repair_notes;
