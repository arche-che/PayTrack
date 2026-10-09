# PAYTRACK v2 — Database Setup Guide

Para sa mga ka-group at testers: narito ang gabay kung paano i-setup at i-import ang database ng **PayTrack** sa XAMPP.

---

## ⚡ Quick 1-Click Import (Inirerekomenda)

Ginamit na natin ang **`paytrack.sql`** na naglalaman ng parehong schema (tables) at seed data (default accounts at sample assessment). Isang import lang ang kailangan!

### Hakbang sa phpMyAdmin:
1. Buksan ang **XAMPP Control Panel** at i-click ang **Start** sa **Apache** at **MySQL**.
2. Buksan ang browser at pumunta sa: `http://localhost/phpmyadmin/`
3. Sa kaliwang sidebar o sa top menu, i-click ang **Import** tab.
4. I-click ang **Choose File** (o Browse) at piliin ang:
   ```
   database/paytrack.sql
   ```
5. Mag-scroll pababa at i-click ang **Import** (o Go) button sa bandang ibaba.
6. Awtomatikong lilikhain ang database na `paytrack` kasama ang lahat ng 7 tables at sample records nang walang error!

---

## 🔑 Default Login Accounts

Pagkatapos i-import ang database, buksan ang login page sa browser:
`http://localhost/<folder-name>/public/`  
*(Halimbawa kung pinangalanang `paytrack` ang folder: `http://localhost/paytrack/public/`, o kung `SystemProposal`: `http://localhost/SystemProposal/public/`)*

### 1. Administrator Account
- **Username / Role:** `admin`
- **Password:** `admin123`
- **Access:** Buong Admin Dashboard, Create Student, Record Payments, Manage Fees, System Reports, at Email Logs.

### 2. Sample Student Account
- **Student ID / Username:** `2023-53512`
- **Password (Default):** `DELACRUZ` *(case-insensitive: tinatanggap din ang `delacruz` o `Dela Cruz`)*
- **Student Name:** Juan Dela Cruz (Grade 11 - STEM)
- **First Login Notice:** Pagka-login, hihingan ang estudyante na magpalit ng bagong personal password.
- **Tuition Assessment:** May nakalaan nang ₱21,000 tuition assessment na may ₱5,000 bayad (Remaining Balance: ₱16,000) para ma-test agad ang student balance viewing at download receipt.

---

## 📁 Nilalaman ng `database/` Folder

| File | Deskripsyon |
| :--- | :--- |
| **`paytrack.sql`** | **Inirerekomenda para sa groupmates.** All-in-one file (Schema + Initial Seed Data) na may auto-create database statement. |
| **`schema.sql`** | Table definitions lamang (users, students, fee_categories, tuition_fees, tuition_fee_items, payments, email_logs). |
| **`seed.sql`** | Initial data lamang kung may existing schema ka na. |

---

## 🛠️ Database Configuration sa System

Kung mayroon ka nang PayTrack database, patakbuhin muna ang `database/migrations/2026_10_09_payment_subject_snapshot.sql` sa phpMyAdmin bago gamitin ang updated payment history. Iniimbak nito ang listahan ng subjects sa bawat bagong bayad upang maipakita sa kasaysayan kung alin ang inalis kalaunan.

Kung default ang XAMPP setup mo:
- **Host:** `localhost`
- **Port:** `3306`
- **Database Name:** `paytrack`
- **Username:** `root`
- **Password:** *(walang password / blank)*

Naka-configure na ito sa `config/database.php`.
