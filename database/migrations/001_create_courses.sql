CREATE TABLE IF NOT EXISTS courses (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    description TEXT NOT NULL,
    theme TEXT NOT NULL CHECK (theme IN (
        'inovacao',
        'tecnologia',
        'marketing',
        'empreendedorismo',
        'agro'
    )),
    image_url TEXT NOT NULL,
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE INDEX IF NOT EXISTS idx_courses_theme ON courses(theme);
CREATE INDEX IF NOT EXISTS idx_courses_title ON courses(title);
