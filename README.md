CREATE DATABASE fuden_stream;
USE fuden_stream;

CREATE TABLE videos (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    title         VARCHAR(255)    NOT NULL,
    description   TEXT            NULL,
    category      ENUM('movies','series','documentaries','anime','live') NOT NULL,
    thumb_url     VARCHAR(255)    NOT NULL,   -- رابط الصورة المُصغَّرة
    video_url     VARCHAR(255)    NOT NULL,   -- مسار الملف أو رابط ڤيميو
    is_vimeo      TINYINT(1)      DEFAULT 0,  -- 1 = Vimeo, 0 = محلي
    duration      VARCHAR(20)     NOT NULL,
    views         INT             DEFAULT 0,
    created_at    TIMESTAMP       DEFAULT CURRENT_TIMESTAMP
);




ps phpMyAdmin
my-secret-pw
 ssh server_fuden@192.168.0.249
