-- Experimental WolfConference-compatible registry (separate MySQL database).
-- This schema replaces the early schema.sql prototype for the Wolf API.
CREATE TABLE IF NOT EXISTS wolf_conf_sessions (
    id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
    name VARCHAR(128) NOT NULL,
    voice TINYINT(1) NOT NULL DEFAULT 0,
    expires_at DATETIME NOT NULL,
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS wolf_conf_members (
    session_id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    agent_id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    display_name VARCHAR(128) NOT NULL DEFAULT '',
    state ENUM('invited','joined') NOT NULL DEFAULT 'invited',
    PRIMARY KEY (session_id, agent_id),
    CONSTRAINT fk_wolf_conf FOREIGN KEY (session_id)
      REFERENCES wolf_conf_sessions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
