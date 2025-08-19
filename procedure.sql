

-- =============================================
-- STORED PROCEDURES FOR student_information_msdb
-- =============================================

DELIMITER $$

CREATE DEFINER=`root`@`localhost` PROCEDURE `AddCourse` (
    IN `p_course_code` VARCHAR(50),
    IN `p_course_name` VARCHAR(100),
    IN `p_department_id` INT
)
BEGIN
    IF NOT EXISTS (SELECT 1 FROM departments WHERE department_id = p_department_id) THEN
        SELECT '⚠️ Invalid Department ID. Course not added.' AS message;
    ELSE
        INSERT INTO courses (course_code, course_name, department_id)
        VALUES (p_course_code, p_course_name, p_department_id);
        SELECT '✅ Course added successfully!' AS message;
    END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `AddGrade` (
    IN `p_student_id` INT,
    IN `p_course_code` VARCHAR(50),
    IN `p_semester` VARCHAR(20),
    IN `p_grade` VARCHAR(5)
)
BEGIN
    IF NOT EXISTS (SELECT 1 FROM students WHERE student_id = p_student_id) THEN
        SELECT '⚠️ Invalid Student ID. Grade not added.' AS message;
    ELSEIF NOT EXISTS (SELECT 1 FROM courses WHERE course_code = p_course_code) THEN
        SELECT '⚠️ Invalid Course Code. Grade not added.' AS message;
    ELSEIF EXISTS (
        SELECT 1 FROM grades
        WHERE student_id = p_student_id
          AND course_code = p_course_code
          AND semester = p_semester
    ) THEN
        SELECT '⚠️ Grade already exists for this student in this course and semester.' AS message;
    ELSE
        INSERT INTO grades (student_id, course_code, semester, grade)
        VALUES (p_student_id, p_course_code, p_semester, p_grade);
        SELECT '✅ Grade added successfully!' AS message;
    END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `AddStudent` (
    IN `p_roll_number` VARCHAR(20),
    IN `p_first_name` VARCHAR(50),
    IN `p_last_name` VARCHAR(50),
    IN `p_dob` DATE,
    IN `p_contact_number` VARCHAR(15),
    IN `p_email` VARCHAR(100),
    IN `p_address` TEXT,
    IN `p_department_id` INT
)
BEGIN
    DECLARE v_email  VARCHAR(100);
    DECLARE v_count  INT;

    SELECT COUNT(*) INTO v_count
    FROM Departments
    WHERE department_id = p_department_id;
    IF v_count = 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Invalid department_id';
    END IF;

    SELECT COUNT(*) INTO v_count
    FROM Students
    WHERE roll_number = p_roll_number;
    IF v_count > 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Duplicate roll_number';
    END IF;

    SET v_email = NULLIF(TRIM(p_email), '');
    IF v_email IS NULL THEN
        SET v_email = CONCAT(LOWER(REPLACE(p_first_name,' ','')),
                             p_roll_number, '@gmail.com');
    END IF;

    SELECT COUNT(*) INTO v_count
    FROM Students
    WHERE email = v_email;
    IF v_count > 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Duplicate email';
    END IF;

    INSERT INTO Students
        (roll_number, first_name, last_name, dob, contact_number, email, address, department_id)
    VALUES
        (p_roll_number, p_first_name, p_last_name, p_dob, p_contact_number, v_email, p_address, p_department_id);
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `DeleteStudent` (IN `p_student_id` INT)
BEGIN
    DECLARE enrollment_count INT;
    SELECT COUNT(*) INTO enrollment_count
    FROM Enrollments
    WHERE student_id = p_student_id;

    IF enrollment_count > 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Cannot delete a student who is enrolled in a course';
    ELSE
        DELETE FROM Academic_Info WHERE student_id = p_student_id;
        DELETE FROM Attendance WHERE student_id = p_student_id;
        DELETE FROM Grades WHERE student_id = p_student_id;
        DELETE FROM Students WHERE student_id = p_student_id;
    END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `EnrollStudentByRoll` (
    IN `p_roll_number` VARCHAR(20),
    IN `p_course_code` VARCHAR(10),
    IN `p_semester` VARCHAR(20)
)
BEGIN
    DECLARE v_student_id INT;
    SELECT student_id INTO v_student_id
    FROM Students
    WHERE roll_number = p_roll_number;
    IF v_student_id IS NULL THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Invalid roll number: student not found';
    END IF;
    INSERT INTO Enrollments (student_id, course_code, semester)
    VALUES (v_student_id, p_course_code, p_semester);
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `GetStudentCGPA` (IN `p_roll_number` VARCHAR(20))
BEGIN
    SELECT 
        s.roll_number,
        CONCAT(s.first_name, ' ', s.last_name) AS full_name,
        d.department_name,
        a.semester,
        a.cgpa
    FROM Students s
    JOIN Academic_Info a ON s.student_id = a.student_id
    JOIN Departments d ON s.department_id = d.department_id
    WHERE s.roll_number = p_roll_number;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `GetStudentGrades` (IN `p_roll_number` VARCHAR(20))
BEGIN
    SELECT 
        s.roll_number,
        CONCAT(s.first_name, ' ', s.last_name) AS full_name,
        g.semester,
        c.course_code,
        c.course_name,
        g.grade
    FROM Students s
    JOIN Grades g ON s.student_id = g.student_id
    JOIN Courses c ON g.course_code = c.course_code
    WHERE s.roll_number = p_roll_number;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `GetStudentsByCourse` (IN `p_course_code` VARCHAR(10))
BEGIN
    SELECT *
    FROM view_student_enrollments
    WHERE course_code = p_course_code;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `GetStudentsByDepartment` (IN `p_department_name` VARCHAR(100))
BEGIN
    SELECT *
    FROM view_students_with_department
    WHERE department_name = p_department_name;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SearchStudentById` (IN `p_student_id` INT)
BEGIN
    SELECT s.student_id,
           s.roll_number,
           s.first_name,
           s.last_name,
           s.dob,
           s.contact_number,
           s.email,
           s.address,
           d.department_name
    FROM Students s
    LEFT JOIN Departments d ON s.department_id = d.department_id
    WHERE s.student_id = p_student_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SearchStudentByRoll` (IN `p_roll_number` VARCHAR(20))
BEGIN
    SELECT s.student_id,
           s.roll_number,
           s.first_name,
           s.last_name,
           s.dob,
           s.contact_number,
           s.email,
           s.address,
           d.department_name
    FROM Students s
    LEFT JOIN Departments d ON s.department_id = d.department_id
    WHERE s.roll_number = p_roll_number;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `UpdateCGPAByRoll` (
    IN `p_roll_number` VARCHAR(20),
    IN `p_semester` VARCHAR(20),
    IN `p_cgpa` DECIMAL(3,2)
)
BEGIN
    DECLARE v_student_id INT;
    SELECT student_id INTO v_student_id
    FROM Students
    WHERE roll_number = p_roll_number;
    IF v_student_id IS NULL THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Invalid roll number: student not found';
    END IF;
    INSERT INTO Academic_Info (academic_id, student_id, semester, cgpa)
    VALUES (NULL, v_student_id, p_semester, p_cgpa)
    ON DUPLICATE KEY UPDATE cgpa = p_cgpa;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `UpdateStudent` (
    IN `p_student_id` INT,
    IN `p_roll_number` VARCHAR(20),
    IN `p_first_name` VARCHAR(50),
    IN `p_last_name` VARCHAR(50),
    IN `p_dob` DATE,
    IN `p_contact_number` VARCHAR(15),
    IN `p_address` TEXT,
    IN `p_department_id` INT
)
BEGIN
    UPDATE Students
    SET roll_number = p_roll_number,
        first_name = p_first_name,
        last_name = p_last_name,
        dob = p_dob,
        contact_number = p_contact_number,
        address = p_address,
        department_id = p_department_id
    WHERE student_id = p_student_id;
END$$

DELIMITER ;

