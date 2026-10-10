# DTC245200182 – Hệ thống điểm danh (Attendance)

## 1. Giới thiệu

Đề tài xây dựng hệ thống điểm danh sử dụng Docker Compose, kết hợp cơ sở dữ liệu MySQL và các công cụ giám sát, thu thập nhật ký hệ thống.

## 2. Công nghệ sử dụng

- **PHP, Apache:** Xây dựng ứng dụng điểm danh.
- **MySQL:** Lưu trữ dữ liệu.
- **phpMyAdmin:** Quản lý cơ sở dữ liệu qua giao diện web.
- **Nginx:** Reverse proxy và thiết lập các HTTP security headers.
- **Prometheus:** Thu thập các chỉ số giám sát.
- **Grafana:** Hiển thị số liệu trên dashboard.
- **Loki:** Lưu trữ và truy vấn nhật ký.
- **Promtail:** Thu thập và chuyển nhật ký đến Loki.
- **Docker Compose:** Khởi chạy và quản lý các dịch vụ.

## 3. Kiến trúc hệ thống

Các dịch vụ được triển khai bằng Docker Compose và kết nối qua mạng Docker nội bộ.

Luồng truy cập ứng dụng:

Người dùng → Nginx → Ứng dụng PHP/Apache → MySQL.

Prometheus thu thập các chỉ số của ứng dụng để Grafana hiển thị. Promtail thu thập nhật ký container và gửi đến Loki để truy vấn bằng LogQL.

## 4. Cấu trúc thư mục

- `app/`: Mã nguồn ứng dụng và Dockerfile.
- `mysql/`: Cấu hình khởi tạo cơ sở dữ liệu.
- `nginx/`: Cấu hình reverse proxy và HTTP security headers.
- `prometheus/`: Cấu hình thu thập chỉ số.
- `loki/`: Cấu hình lưu trữ nhật ký.
- `promtail/`: Cấu hình thu thập nhật ký.
- `docker-compose.yml`: Cấu hình các dịch vụ.
- `.env.example`: Mẫu biến môi trường.
- `.gitignore`: Quy tắc loại trừ tệp không đưa lên Git.

## 5. Cấu hình và triển khai

### Yêu cầu

- Ubuntu hoặc hệ điều hành tương thích.
- Docker Engine.
- Docker Compose plugin.

### Cấu hình môi trường

Tạo tệp `.env` từ `.env.example`, sau đó thay các giá trị mẫu bằng mật khẩu phù hợp cho môi trường triển khai.

Không đưa tệp `.env` chứa thông tin bí mật lên GitHub.

### Khởi động hệ thống

```bash
docker compose up -d --build
```

### Kiểm tra trạng thái

```bash
docker compose ps
```

### Xem nhật ký dịch vụ

```bash
docker compose logs --tail=100
```

### Dừng hệ thống

```bash
docker compose down
```

Lệnh trên dừng và xóa các container thuộc dự án nhưng không chủ động xóa các volume dữ liệu.

## 6. Địa chỉ truy cập

Khi các dịch vụ đang chạy trên máy Ubuntu, có thể truy cập qua:

| Dịch vụ | Địa chỉ |
|---|---|
| Ứng dụng điểm danh | http://localhost:8081 |
| phpMyAdmin | http://localhost:8082 |
| Nginx reverse proxy | http://localhost:8083 |
| Prometheus | http://localhost:9091 |
| Grafana | http://localhost:3001 |

Loki và Promtail được sử dụng nội bộ trong mạng Docker.

## 7. Giám sát và nhật ký

- Sử dụng Prometheus để truy vấn các chỉ số của ứng dụng.
- Sử dụng Grafana để theo dõi số liệu trên dashboard.
- Sử dụng Loki và Promtail để thu thập, lưu trữ và truy vấn nhật ký bằng LogQL.

## 8. An toàn thông tin

- Loại trừ `.env` và các tệp sao lưu khỏi Git.
- Sử dụng biến môi trường để truyền thông tin kết nối cơ sở dữ liệu.
- Sử dụng Nginx để thiết lập các HTTP security headers.
- Hạn chế công khai thông tin xác thực và không đưa mật khẩu thật vào mã nguồn.

## 9. Tác giả

- Mã số sinh viên: DTC245200182
- Tài khoản GitHub: [letuanhung5](https://github.com/letuanhung5)
- Repository: [DTC245200182](https://github.com/letuanhung5/DTC245200182)

