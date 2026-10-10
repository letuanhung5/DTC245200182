<?php
header('Content-Type: text/plain; version=0.0.4; charset=utf-8');
header('Cache-Control: no-store');

date_default_timezone_set('Asia/Ho_Chi_Minh');

$host = getenv('DB_HOST') ?: 'mysql';
$dbname = getenv('DB_NAME') ?: 'attendance';
$username = getenv('DB_USER') ?: 'attendance_user';
$password = getenv('DB_PASSWORD') ?: '';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );

    $employeeTotal = (int) $pdo->query(
        'SELECT COUNT(*) FROM employees'
    )->fetchColumn();

    $attendanceStats = $pdo->query(
        'SELECT COUNT(*) AS record_total,
                COALESCE(SUM(check_in IS NOT NULL), 0) AS checkin_total,
                COALESCE(SUM(check_out IS NOT NULL), 0) AS checkout_total
         FROM attendance'
    )->fetch();

    $metrics = [
        '# HELP attendance_app_up Whether the attendance application and database are responding.',
        '# TYPE attendance_app_up gauge',
        'attendance_app_up 1',
        '# HELP attendance_employee_total Total number of employees.',
        '# TYPE attendance_employee_total gauge',
        'attendance_employee_total ' . $employeeTotal,
        '# HELP attendance_record_total Total number of attendance records.',
        '# TYPE attendance_record_total gauge',
        'attendance_record_total ' . (int) $attendanceStats['record_total'],
        '# HELP attendance_checkin_total Attendance records with a check-in time.',
        '# TYPE attendance_checkin_total gauge',
        'attendance_checkin_total ' . (int) $attendanceStats['checkin_total'],
        '# HELP attendance_checkout_total Attendance records with a check-out time.',
        '# TYPE attendance_checkout_total gauge',
        'attendance_checkout_total ' . (int) $attendanceStats['checkout_total'],
    ];

    http_response_code(200);
    echo implode("\n", $metrics) . "\n";
} catch (Throwable $e) {
    http_response_code(500);
    echo "# HELP attendance_app_up Whether the attendance application and database are responding.\n";
    echo "# TYPE attendance_app_up gauge\n";
    echo "attendance_app_up 0\n";
    error_log('Metrics endpoint database query failed: ' . $e->getMessage());
}
