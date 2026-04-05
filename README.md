# 🗳️ VoteApp — Student Voting System
## Design: Silver glassmorphism + Carousel vote page

---

## ⚡ Setup (3 steps)

### 1. Place files
```
C:\xampp\htdocs\project\
```

### 2. Create database
- Open phpMyAdmin → SQL tab
- Copy/paste entire contents of **database.sql** → Execute

### 3. Add candidate photos
Place photos in `images/candidats/`:
- `candidate1.jpg` — Amina Diallo
- `candidate2.jpg` — Ibrahima Ndiaye
- `candidate3.jpg` — Mariama Sow
- `default.png` — fallback

Then open: **http://localhost/project/**

---

## 🔑 Default Credentials

| Role  | Email          | Password   |
|-------|----------------|------------|
| Admin | admin@vote.com | Admin@123  |

---

## 📁 Files

| File | Purpose |
|------|---------|
| `login.php` | Silver glass login card with particles |
| `register.php` | Account creation |
| `forgot-password.php` | 4-step OTP reset |
| `vote.php` | **Carousel** — big center + side thumbnails + speech bubble |
| `results.php` | Live results (auto-refresh 5s) |
| `admin-users.php` | User management |
| `admin-results.php` | Results + reset votes |

---

## 🎨 Design Details

- **Background**: Gray gradient (`#808080` base) with floating white particles
- **Cards**: Silver glassmorphism with `backdrop-filter:blur`
- **Vote page**: Carousel with ← → arrows, big center candidate, side thumbnails, terracotta speech bubble for programme, black pill vote button
- **Fonts**: Inter (UI) + Playfair Display (headings)

---

## ⚙️ config.php
```php
$host     = 'localhost';
$dbname   = 'vote';
$username = 'root';
$password = '';   // ← set your MySQL password
```
