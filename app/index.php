<?php
// Prometheus metrics endpoint
if (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) === '/metrics') {
    header('Content-Type: text/plain; version=0.0.4; charset=utf-8');
    header('Cache-Control: no-store');

    $metrics = [
        '# HELP attendance_app_up Whether the attendance application is responding.',
        '# TYPE attendance_app_up gauge',
        'attendance_app_up 1',
    ];

    http_response_code(200);
    echo implode("\n", $metrics) . "\n";
    exit;
}

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
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    die('Không thể kết nối cơ sở dữ liệu. Vui lòng thử lại sau.');
}

function h($value)
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

$message = '';
$messageType = 'success';
$today = date('Y-m-d');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employeeId = filter_input(
        INPUT_POST,
        'employee_id',
        FILTER_VALIDATE_INT
    );

    $action = $_POST['action'] ?? '';

    if (!$employeeId || !in_array($action, ['check_in', 'check_out'], true)) {
        $message = 'Thông tin điểm danh không hợp lệ.';
        $messageType = 'error';
    } else {
        try {
            $stmt = $pdo->prepare(
                'SELECT id FROM employees WHERE id = ?'
            );
            $stmt->execute([$employeeId]);

            if (!$stmt->fetch()) {
                $message = 'Không tìm thấy nhân viên.';
                $messageType = 'error';
            } else {
                $stmt = $pdo->prepare(
                    'SELECT id, check_in, check_out
                     FROM attendance
                     WHERE employee_id = ? AND attendance_date = ?
                     ORDER BY id DESC
                     LIMIT 1'
                );
                $stmt->execute([$employeeId, $today]);
                $record = $stmt->fetch();

                $now = date('H:i:s');

                if ($action === 'check_in') {
                    if ($record && $record['check_in'] !== null) {
                        $message = 'Nhân viên đã điểm danh vào hôm nay.';
                        $messageType = 'error';
                    } elseif ($record) {
                        $stmt = $pdo->prepare(
                            "UPDATE attendance
                             SET check_in = ?, status = 'Present'
                             WHERE id = ?"
                        );
                        $stmt->execute([$now, $record['id']]);

                        $message = 'Điểm danh vào thành công lúc ' . $now . '.';
                    } else {
                        $stmt = $pdo->prepare(
                            "INSERT INTO attendance
                             (employee_id, attendance_date, check_in, status)
                             VALUES (?, ?, ?, 'Present')"
                        );
                        $stmt->execute([$employeeId, $today, $now]);

                        $message = 'Điểm danh vào thành công lúc ' . $now . '.';
                    }
                } elseif ($action === 'check_out') {
                    if (!$record || !$record['check_in']) {
                        $message = 'Nhân viên chưa điểm danh vào hôm nay.';
                        $messageType = 'error';
                    } elseif ($record['check_out'] !== null) {
                        $message = 'Nhân viên đã điểm danh ra hôm nay.';
                        $messageType = 'error';
                    } else {
                        $stmt = $pdo->prepare(
                            'UPDATE attendance SET check_out = ? WHERE id = ?'
                        );
                        $stmt->execute([$now, $record['id']]);

                        $message = 'Điểm danh ra thành công lúc ' . $now . '.';
                    }
                }
            }
        } catch (PDOException $e) {
            $message = 'Không thể lưu thông tin điểm danh. Vui lòng thử lại.';
            $messageType = 'error';
        }
    }
}

/* Danh sách nhân viên và trạng thái điểm danh hôm nay */
$stmt = $pdo->prepare(
    'SELECT
        e.id,
        e.employee_code,
        e.full_name,
        e.department,
        a.check_in,
        a.check_out,
        a.status
     FROM employees e
     LEFT JOIN attendance a
        ON e.id = a.employee_id
        AND a.attendance_date = ?
     ORDER BY e.id'
);
$stmt->execute([$today]);
$employees = $stmt->fetchAll();

/* Lịch sử điểm danh gần đây */
$stmt = $pdo->query(
    'SELECT
        e.employee_code,
        e.full_name,
        a.attendance_date,
        a.check_in,
        a.check_out,
        a.status
     FROM attendance a
     JOIN employees e ON e.id = a.employee_id
     ORDER BY a.attendance_date DESC, a.id DESC
     LIMIT 20'
);
$history = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Hệ thống Điểm danh</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f1f5f9;
            margin: 0;
            padding: 30px;
            color: #1e293b;
        }

        .container {
            max-width: 1200px;
            margin: auto;
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.06);
        }

        h1, h2 {
            color: #1d4ed8;
        }

        h1 {
            text-align: center;
            margin-bottom: 10px;
        }

        h2 {
            margin-top: 35px;
        }

        .date {
            text-align: center;
            color: #64748b;
            margin-bottom: 25px;
        }

        .message {
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .success {
            background: #dcfce7;
            color: #166534;
        }

        .error {
            background: #fee2e2;
            color: #991b1b;
        }

        .table-wrapper {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th, td {
            padding: 12px;
            border-bottom: 1px solid #e2e8f0;
            text-align: left;
            white-space: nowrap;
        }

        th {
            background: #1d4ed8;
            color: white;
        }

        tr:hover td {
            background: #f8fafc;
        }

        .present {
            color: #15803d;
            font-weight: bold;
        }

        .absent {
            color: #dc2626;
            font-weight: bold;
        }

        .completed {
            color: #1d4ed8;
            font-weight: bold;
        }

        button {
            border: none;
            padding: 9px 12px;
            border-radius: 5px;
            cursor: pointer;
            color: white;
            font-size: 13px;
        }

        .check-in {
            background: #2563eb;
        }

        .check-out {
            background: #059669;
        }

        button:disabled {
            background: #94a3b8;
            cursor: not-allowed;
        }

        .actions {
            display: flex;
            gap: 6px;
        }

        .empty {
            text-align: center;
            color: #64748b;
            padding: 20px;
        }

        footer {
            text-align: center;
            margin-top: 25px;
            color: #64748b;
            font-size: 13px;
        }

        @media (max-width: 700px) {
            body {
                padding: 10px;
            }

            .container {
                padding: 12px;
            }
        }
    </style>
</head>

<body>
<div class="container">

    <h1>Hệ thống Điểm danh</h1>

    <div class="date">
        Ngày điểm danh: <?= h(date('d/m/Y')) ?>
    </div>

    <?php if ($message !== ''): ?>
        <div class="message <?= h($messageType) ?>">
            <?= h($message) ?>
        </div>
    <?php endif; ?>

    <h2>1. Danh sách điểm danh hôm nay</h2>

    <div class="table-wrapper">
        <table>
            <thead>
            <tr>
                <th>Mã nhân viên</th>
                <th>Họ và tên</th>
                <th>Phòng ban</th>
                <th>Giờ vào</th>
                <th>Giờ ra</th>
                <th>Trạng thái</th>
                <th>Thao tác</th>
            </tr>
            </thead>

            <tbody>
            <?php foreach ($employees as $employee): ?>
                <tr>
                    <td><?= h($employee['employee_code']) ?></td>
                    <td><?= h($employee['full_name']) ?></td>
                    <td><?= h($employee['department']) ?></td>

                    <td>
                        <?= h($employee['check_in'] ?: '--:--:--') ?>
                    </td>

                    <td>
                        <?= h($employee['check_out'] ?: '--:--:--') ?>
                    </td>

                    <td>
                        <?php if (!$employee['check_in']): ?>
                            <span class="absent">Chưa vào</span>
                        <?php elseif (!$employee['check_out']): ?>
                            <span class="present">Đã vào</span>
                        <?php else: ?>
                            <span class="completed">Đã hoàn thành</span>
                        <?php endif; ?>
                    </td>

                    <td>
                        <div class="actions">
                            <form method="POST">
                                <input
                                    type="hidden"
                                    name="employee_id"
                                    value="<?= (int)$employee['id'] ?>"
                                >
                                <input
                                    type="hidden"
                                    name="action"
                                    value="check_in"
                                >

                                <button
                                    type="submit"
                                    class="check-in"
                                    <?= $employee['check_in'] ? 'disabled' : '' ?>
                                >
                                    Điểm danh vào
                                </button>
                            </form>

                            <form method="POST">
                                <input
                                    type="hidden"
                                    name="employee_id"
                                    value="<?= (int)$employee['id'] ?>"
                                >
                                <input
                                    type="hidden"
                                    name="action"
                                    value="check_out"
                                >

                                <button
                                    type="submit"
                                    class="check-out"
                                    <?= (
                                        !$employee['check_in']
                                        || $employee['check_out']
                                    ) ? 'disabled' : '' ?>
                                >
                                    Điểm danh ra
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>

            <?php if (!$employees): ?>
                <tr>
                    <td colspan="7" class="empty">
                        Chưa có nhân viên trong hệ thống.
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <h2>2. Lịch sử điểm danh gần đây</h2>

    <div class="table-wrapper">
        <table>
            <thead>
            <tr>
                <th>Mã nhân viên</th>
                <th>Họ và tên</th>
                <th>Ngày</th>
                <th>Giờ vào</th>
                <th>Giờ ra</th>
                <th>Trạng thái</th>
            </tr>
            </thead>

            <tbody>
            <?php foreach ($history as $item): ?>
                <tr>
                    <td><?= h($item['employee_code']) ?></td>
                    <td><?= h($item['full_name']) ?></td>
                    <td>
                        <?= h(date(
                            'd/m/Y',
                            strtotime($item['attendance_date'])
                        )) ?>
                    </td>
                    <td><?= h($item['check_in'] ?: '--:--:--') ?></td>
                    <td><?= h($item['check_out'] ?: '--:--:--') ?></td>
                    <td>
                        <?= h($item['status'] ?: 'Chưa xác định') ?>
                    </td>
                </tr>
            <?php endforeach; ?>

            <?php if (!$history): ?>
                <tr>
                    <td colspan="6" class="empty">
                        Chưa có lịch sử điểm danh.
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <footer>
        Hệ thống Điểm danh | PHP - MySQL - Docker
    </footer>

</div>
</body>
</html>
