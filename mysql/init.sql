CREATE DATABASE IF NOT EXISTS attendance
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE attendance;

CREATE TABLE employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_code VARCHAR(20) NOT NULL UNIQUE,
    full_name VARCHAR(100) NOT NULL,
    department VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    attendance_date DATE NOT NULL,
    check_in TIME,
    check_out TIME,
    status VARCHAR(20) DEFAULT 'Present',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id)
);

INSERT INTO employees (employee_code, full_name, department) VALUES
('NV001', 'Nguyen Van An', 'Phong Ky thuat'),
('NV002', 'Tran Thi Binh', 'Phong Ke toan'),
('NV003', 'Le Van Cuong', 'Phong Hanh chinh');
