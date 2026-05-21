CREATE DATABASE IF NOT EXISTS db_majujaya
CHARACTER SET utf8mb4
COLLATE utf8mb4_general_ci;

USE db_majujaya;

CREATE TABLE m_user (
    id_user     INT             NOT NULL AUTO_INCREMENT,
    username    VARCHAR(50)     NOT NULL,
    password    VARCHAR(255)    NOT NULL,
    role        ENUM('Admin','Kasir') NOT NULL,
    PRIMARY KEY (id_user),
    UNIQUE KEY uk_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE m_produk (
    id_produk   INT             NOT NULL AUTO_INCREMENT,
    nama_produk VARCHAR(100)    NOT NULL,
    harga_jual  DECIMAL(10,2)   NOT NULL,
    stok        INT             NOT NULL DEFAULT 0,
    PRIMARY KEY (id_produk)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE t_penjualan (
    id_penjualan    INT             NOT NULL AUTO_INCREMENT,
    nomor_nota      VARCHAR(20)     NOT NULL,
    tgl_transaksi   DATETIME        NOT NULL,
    total_bayar     DECIMAL(12,2)   NOT NULL,
    id_user         INT             NOT NULL,
    PRIMARY KEY (id_penjualan),
    UNIQUE KEY uk_nomor_nota (nomor_nota),
    CONSTRAINT fk_penjualan_user
        FOREIGN KEY (id_user) REFERENCES m_user(id_user)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE t_penjualan_detail (
    id_detail       INT             NOT NULL AUTO_INCREMENT,
    id_penjualan    INT             NOT NULL,
    id_produk       INT             NOT NULL,
    qty             INT             NOT NULL,
    subtotal        DECIMAL(12,2)   NOT NULL,
    PRIMARY KEY (id_detail),
    CONSTRAINT fk_detail_penjualan
        FOREIGN KEY (id_penjualan) REFERENCES t_penjualan(id_penjualan)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_detail_produk
        FOREIGN KEY (id_produk) REFERENCES m_produk(id_produk)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE t_log_stok (
    id_log      INT                     NOT NULL AUTO_INCREMENT,
    id_produk   INT                     NOT NULL,
    jumlah      INT                     NOT NULL,
    tipe        ENUM('Masuk','Keluar')  NOT NULL,
    keterangan  VARCHAR(255),
    waktu_log   TIMESTAMP               NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_log),
    CONSTRAINT fk_log_produk
        FOREIGN KEY (id_produk) REFERENCES m_produk(id_produk)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- DATA AWAL (Seeder)

-- Insert Admin dan Kasir password default : password
INSERT INTO m_user (username, password, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin'),
('kasir1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Kasir');

INSERT INTO m_produk (nama_produk, harga_jual, stok) VALUES
('Indomie Goreng', 3500.00, 100),
('Aqua 600ml', 4000.00, 200),
('Roti Tawar Sari Roti', 15000.00, 50),
('Minyak Goreng Bimoli 1L', 18000.00, 30),
('Gula Pasir 1kg', 14000.00, 40),
('Susu Ultra Milk 1L', 16500.00, 25),
('Sabun Lifebuoy', 5500.00, 80),
('Shampo Pantene Sachet', 1500.00, 150),
('Teh Pucuk Harum 350ml', 4500.00, 120),
('Kopi Kapal Api Sachet', 2000.00, 200);

INSERT INTO t_log_stok (id_produk, jumlah, tipe, keterangan) VALUES
(1, 100, 'Masuk', 'Saldo awal produk'),
(2, 200, 'Masuk', 'Saldo awal produk'),
(3, 50, 'Masuk', 'Saldo awal produk'),
(4, 30, 'Masuk', 'Saldo awal produk'),
(5, 40, 'Masuk', 'Saldo awal produk'),
(6, 25, 'Masuk', 'Saldo awal produk'),
(7, 80, 'Masuk', 'Saldo awal produk'),
(8, 150, 'Masuk', 'Saldo awal produk'),
(9, 120, 'Masuk', 'Saldo awal produk'),
(10, 200, 'Masuk', 'Saldo awal produk');
