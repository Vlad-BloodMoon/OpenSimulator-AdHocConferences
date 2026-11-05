-- Experimental ad-hoc text conferences for OpenSimulator 0.9.3.x.
-- Install in a dedicated MySQL database, separate from ROBUST.
-- No chat content is persisted here.
CREATE TABLE IF NOT EXISTS adhoc_sessions (
    session_id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL PRIMARY KEY,
    owner_id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    INDEX idx_expiry (expires_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS adhoc_members (
    session_id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    agent_id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    joined TINYINT(1) NOT NULL DEFAULT 0,
    joined_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (session_id, agent_id),
    CONSTRAINT fk_adhoc_session FOREIGN KEY (session_id)
      REFERENCES adhoc_sessions(session_id) ON DELETE CASCADE
) ENGINE=InnoDB;
