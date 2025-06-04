CREATE TABLE IF NOT EXISTS verifications (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    first_name TEXT,
    last_name TEXT,
    email TEXT,
    phone TEXT,
    street TEXT,
    city TEXT,
    state TEXT,
    zip TEXT,
    dob TEXT,
    result_url TEXT,
    status TEXT DEFAULT 'Pending',
    message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    plaid_id TEXT
);

CREATE TABLE IF NOT EXISTS webhook_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    event_id TEXT,
    lead_status TEXT,
    message TEXT,
    info TEXT,
    details TEXT,
    event_time TEXT,
    received_at TEXT DEFAULT CURRENT_TIMESTAMP
);
