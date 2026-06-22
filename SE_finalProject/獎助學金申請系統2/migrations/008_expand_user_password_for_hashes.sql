-- Allow password_hash() values while keeping existing plaintext passwords valid.

ALTER TABLE users
    MODIFY password VARCHAR(255) NOT NULL COMMENT '密碼雜湊或舊明文密碼';
