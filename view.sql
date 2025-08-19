CREATE VIEW view_courses AS
SELECT c.course_code, c.course_name, d.department_name
FROM courses c
LEFT JOIN departments d ON c.department_id = d.department_id;

CREATE VIEW view_courses_with_department AS
SELECT c.course_code, c.course_name, d.department_name
FROM courses c
JOIN departments d ON c.department_id = d.department_id;

CREATE VIEW view_students_with_department AS
SELECT s.student_id, s.roll_number, s.first_name, s.last_name, s.dob,
       s.contact_number, s.email, s.address, d.department_name
FROM students s
LEFT JOIN departments d ON s.department_id = d.department_id;

CREATE VIEW view_student_cgpa AS
SELECT s.student_id, s.roll_number, CONCAT(s.first_name,' ',s.last_name) AS full_name,
       d.department_name, a.semester, a.cgpa
FROM students s
JOIN academic_info a ON s.student_id = a.student_id
LEFT JOIN departments d ON s.department_id = d.department_id;

CREATE VIEW view_student_enrollments AS
SELECT s.student_id, s.roll_number, CONCAT(s.first_name,' ',s.last_name) AS full_name,
       d.department_name, e.semester, c.course_code, c.course_name
FROM students s
JOIN enrollments e ON s.student_id = e.student_id
JOIN courses c ON e.course_code = c.course_code
LEFT JOIN departments d ON s.department_id = d.department_id;