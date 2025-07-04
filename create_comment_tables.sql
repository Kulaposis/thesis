-- Create document_highlights table
CREATE TABLE IF NOT EXISTS document_highlights (
    id INT AUTO_INCREMENT PRIMARY KEY,
    chapter_id INT NOT NULL,
    adviser_id INT NOT NULL,
    start_offset INT NOT NULL,
    end_offset INT NOT NULL,
    highlighted_text TEXT NOT NULL,
    highlight_color VARCHAR(10) DEFAULT '#ffeb3b',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_chapter_id (chapter_id),
    INDEX idx_adviser_id (adviser_id),
    FOREIGN KEY (chapter_id) REFERENCES chapters(id) ON DELETE CASCADE,
    FOREIGN KEY (adviser_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Create document_comments table
CREATE TABLE IF NOT EXISTS document_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    chapter_id INT NOT NULL,
    adviser_id INT NOT NULL,
    comment_text TEXT NOT NULL,
    highlight_id INT NULL,
    start_offset INT NULL,
    end_offset INT NULL,
    position_x INT NULL,
    position_y INT NULL,
    metadata TEXT NULL,
    status ENUM('active', 'resolved') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_chapter_id (chapter_id),
    INDEX idx_adviser_id (adviser_id),
    FOREIGN KEY (chapter_id) REFERENCES chapters(id) ON DELETE CASCADE,
    FOREIGN KEY (adviser_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (highlight_id) REFERENCES document_highlights(id) ON DELETE CASCADE
); 