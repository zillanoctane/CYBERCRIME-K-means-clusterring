-- Inisialisasi MySQL untuk SIANCEK
-- Dijalankan sekali saat container MySQL pertama kali dibuat.

ALTER DATABASE siancek CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
SET GLOBAL local_infile = 1;

-- Beri hak penuh ke user 'siancek' dari host mana pun (termasuk container
-- phpMyAdmin & app) agar dapat mengelola seluruh database tanpa kendala
-- "No privileges". User ini sudah dibuat otomatis oleh image MySQL via
-- variabel MYSQL_USER/MYSQL_PASSWORD sebelum skrip ini berjalan.
GRANT ALL PRIVILEGES ON *.* TO 'siancek'@'%' WITH GRANT OPTION;
FLUSH PRIVILEGES;
