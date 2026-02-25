-- Penanda alur langsung ke apoteker (tanpa dokter)
-- Jalankan sekali di database Anda. Setelah dijalankan, panel Direktur RS akan
-- mendeteksi pasien yang alurnya "perawat langsung ke apoteker" lewat kolom ini.

ALTER TABLE riwayat_kesehatan
ADD COLUMN alur_langsung_apoteker TINYINT(1) NOT NULL DEFAULT 0
COMMENT '1 = pasien langsung perawat ke apoteker (dokter tidak ada), 0 = lewat dokter'
AFTER status_akhir;
