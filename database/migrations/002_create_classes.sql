CREATE TABLE IF NOT EXISTS classes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    course_id INTEGER NOT NULL,
    title TEXT NOT NULL,
    description TEXT NOT NULL,
    seats INTEGER NOT NULL CHECK (seats >= 0),
    status TEXT NOT NULL CHECK (status IN ('disponivel', 'encerrado')),
    start_date TEXT NOT NULL,
    end_date TEXT NOT NULL,
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at TEXT NOT NULL DEFAULT (datetime('now')),
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    CHECK (date(start_date) <= date(end_date))
);

CREATE INDEX IF NOT EXISTS idx_classes_course_id ON classes(course_id);
CREATE INDEX IF NOT EXISTS idx_classes_status ON classes(status);
