

-- =============================================
-- TRIGGERS FOR student_information_msdb
-- =============================================

DELIMITER $$

-- Trigger 1: Validate CGPA range before insert
CREATE TRIGGER `validate_cgpa`
BEFORE INSERT ON `academic_info`
FOR EACH ROW
BEGIN
    IF NEW.cgpa < 0 OR NEW.cgpa > 4.00 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Invalid CGPA value (must be between 0.00 and 4.00)';
    END IF;
END$$

-- Trigger 2: Ensure unique department name
CREATE TRIGGER `unique_department_name`
BEFORE INSERT ON `departments`
FOR EACH ROW
BEGIN
    IF EXISTS (SELECT 1 FROM Departments WHERE department_name = NEW.department_name) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Department name already exists';
    END IF;
END$$

-- Trigger 3: Prevent duplicate enrollment
CREATE TRIGGER `prevent_duplicate_enrollment`
BEFORE INSERT ON `enrollments`
FOR EACH ROW
BEGIN
    DECLARE enrollment_count INT;

    SELECT COUNT(*) INTO enrollment_count
    FROM Enrollments
    WHERE student_id = NEW.student_id
      AND course_code = NEW.course_code;

    IF enrollment_count > 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Student is already enrolled in this course';
    END IF;
END$$

-- Trigger 4: Auto-generate email if not provided
CREATE TRIGGER `auto_email_before_insert`
BEFORE INSERT ON `students`
FOR EACH ROW
BEGIN
  IF NEW.email IS NULL OR NEW.email = '' THEN
    SET NEW.email = CONCAT(LOWER(REPLACE(NEW.first_name, ' ', '')), NEW.roll_number, '@gmail.com');
  END IF;
END$$

-- Trigger 5: Update email when name or roll changes
CREATE TRIGGER `update_student_email`
BEFORE UPDATE ON `students`
FOR EACH ROW
BEGIN
    IF NEW.first_name <> OLD.first_name 
       OR NEW.roll_number <> OLD.roll_number THEN
        SET NEW.email = CONCAT(LOWER(NEW.first_name), '.', NEW.roll_number, '@university.com');
    END IF;
END$$

DELIMITER ;

