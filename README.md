# The Grand Horizon Hotel & Banquet
## Complete Hotel Event & Banquet Management System

A web-based Hotel Event Management System developed with **PHP 8.x, MySQL, Bootstrap 5.3, JavaScript (Fetch API & FullCalendar)**. Designed as a real-world software engineering campus academic project.

---

## 1. Project Overview & Features

The system replaces manual booking registers with a unified digital platform for hotel administration, banquet managers, and event customers. It supports:
- **Weddings & Receptions**
- **Corporate Summits & Business Meetings**
- **Milestone Birthday Celebrations**
- **Anniversaries & Heritage Banquets**
- **Cocktail Soirées & Networking Receptions**

### Core Capabilities:
1. **Real-time Hall Conflict Prevention**: Prevents double-booking a venue during overlapping time intervals (`start_time < booked_end AND end_time > booked_start`).
2. **Dynamic Live Pricing Engine**: Automatically computes rental charges based on hourly/half-day/full-day rates, package price per guest, base setup fees, and 10% tax.
3. **Multi-tier Ledger & Advance Management**: Tracks 30% advance deposits, updates remaining balances automatically, issues unique receipt numbers (`RCPT-YYYY-XXXX`), and transitions booking states.
4. **Interactive Availability Calendar**: Visual schedule color-coding confirmed banquets (green), approved reservations (blue), and pending requests (amber).
5. **Role-Based Access Control (RBAC)**: Enforced session gating with CSRF protection, prepared PDO statements, and bcrypt password hashing.

---

## 2. User Roles & Default Test Credentials

| Role | Email Address | Password | Primary Capabilities |
|---|---|---|---|
| **Administrator** | `admin@grandhorizon.com` | `admin123` | Analytics dashboard, Hall CRUD, Package CRUD, Staff accounts, Customer accounts, Payment ledger, Financial reports, Hotel settings |
| **Event Manager / Staff** | `staff@grandhorizon.com` | `staff123` | Pending review queue, Approve/Decline bookings, Record payments, Hall availability calendar, Customer contact directory |
| **Event Coordinator** | `coordinator@grandhorizon.com` | `staff123` | View schedules, review requests, inspect venue capacities |
| **Customer** | `customer@gmail.com` | `customer123` | Browse venues, check live calendar, submit booking requests, review balance due, print invoices & contracts |

*(Note: You can also use the **"Academic Demo One-Click Fill"** buttons on the login page for rapid viva demonstrations!)*

---

## 3. How to Set Up and Run on XAMPP (Windows)

### Step 1: Copy Project Folder to XAMPP
Copy the entire `Hotel Event Management System` folder into your XAMPP web root directory:
```
C:\xampp\htdocs\hotel-event-system
```
*(Or keep the folder name as `Hotel Event Management System` inside `C:\xampp\htdocs\`)*

### Step 2: Start Apache and MySQL in XAMPP
1. Open the **XAMPP Control Panel**.
2. Click **Start** for **Apache**.
3. Click **Start** for **MySQL**.

### Step 3: Run the 1-Click Web Installer
Open your web browser and navigate to:
```
http://localhost/hotel-event-system/install.php
```
*(Or `http://localhost/Hotel%20Event%20Management%20System/install.php`)*

Click the **"Initialize Database & Demo Data"** button. The installer will automatically:
1. Connect to MySQL (`root` with blank password by default).
2. Create the `hotel_event_db` database.
3. Import the complete relational schema with primary and foreign keys.
4. Seed realistic hotel data (ballrooms, packages, sample reservations, payments).
5. Initialize demo account passwords with your local PHP environment's native `password_hash()`.

### Alternative Manual Database Setup (phpMyAdmin)
If you prefer importing manually via phpMyAdmin:
1. Open `http://localhost/phpmyadmin/`.
2. Create a new database named `hotel_event_db` with collation `utf8mb4_unicode_ci`.
3. Click **Import** and select `database/schema.sql`.
4. Click **Import** and select `database/seed_data.sql`.

---

## 4. Project Directory Structure

```
Hotel Event Management System/
│
├── config/
│   ├── database.php            # Secure PDO connection singleton
│   ├── constants.php           # App metadata, base URL detector, status badges
│   └── session.php             # Session hardening, RBAC middleware, CSRF tokens
│
├── includes/
│   ├── header.php              # HTML5 head, Bootstrap 5.3, luxury fonts, FullCalendar CSS
│   ├── footer.php              # Shared luxury footer, Bootstrap bundle, app scripts
│   ├── navbar.php              # Public & customer top navigation
│   ├── admin_sidebar.php       # Navy & gold sidebar for Administrator portal
│   ├── staff_sidebar.php       # Workflow-focused sidebar for Staff / Event Managers
│   ├── alerts.php              # Flash notifications renderer
│   └── functions.php           # Input sanitizer, currency formatters, date helpers
│
├── services/
│   ├── BookingService.php      # Conflict prevention, live pricing calculation, lifecycle transitions
│   └── PaymentService.php      # Transaction recording, receipt generator, balance reconciler
│
├── api/
│   ├── check_availability.php  # AJAX endpoint: instant hall availability checker
│   ├── calculate_price.php     # AJAX endpoint: live pricing & tax estimator
│   └── get_hall_events.php     # AJAX endpoint: JSON feed for FullCalendar
│
├── admin/                      # Administrator Portal
│   ├── dashboard.php           # Executive analytics & revenue distribution
│   ├── halls.php               # Add, Edit, Delete, Configure Function Halls
│   ├── packages.php            # Add, Edit, Configure Event Packages & Menus
│   ├── staff.php               # Staff member account management
│   ├── customers.php           # Customer accounts & account suspension
│   ├── bookings.php            # Master bookings register with multi-filters
│   ├── payments.php            # Payment ledger with receipts & methods
│   ├── reports.php             # Date-range revenue & hall utilization reports (printable)
│   └── settings.php            # Hotel name, tax rate %, deposit % configuration
│
├── staff/                      # Event Manager / Staff Portal
│   ├── dashboard.php           # Pending approvals queue & upcoming 7-day schedule
│   ├── bookings.php            # Booking requests review list
│   ├── booking_detail.php      # Dossier view with Approve / Reject / Confirm modals
│   ├── record_payment.php      # Counter payment recorder with auto-balance calculation
│   ├── calendar.php            # Master schedule calendar with hall filter
│   └── customers.php           # Client contact roster
│
├── customer/                   # Customer Portal
│   ├── dashboard.php           # Reservation status counters, active bookings table
│   ├── book.php                # Multi-step booking request form with live estimator
│   ├── my_bookings.php         # Complete reservation history with status filters
│   ├── booking_detail.php      # Official invoice contract & payment receipts (printable)
│   └── profile.php             # Profile update & password change
│
├── assets/
│   ├── css/
│   │   └── style.css           # Master luxury hotel theme (Navy #0b1a30, Gold #c5a880)
│   └── js/
│       ├── main.js             # Dynamic live pricing calculation & availability AJAX
│       └── calendar.js         # FullCalendar interactive rendering & modals
│
├── database/
│   ├── schema.sql              # Clean 3NF MySQL relational DDL
│   └── seed_data.sql           # Realistic hotel venues, packages, and bookings
│
├── index.php                   # Luxury public homepage & venue showcase
├── halls.php                   # Public venue directory with capacity filter
├── packages.php                # Public event packages & dining menus
├── availability.php            # Public interactive calendar & quick time-slot checker
├── login.php                   # Unified login portal with quick-fill demo buttons
├── register.php                # Customer self-registration
├── logout.php                  # Session termination & clean redirect
├── install.php                 # 1-Click web installer & database diagnostics
└── README.md                   # Complete academic documentation & viva guide
```

---

## 5. Academic Viva Defense Guide: Key Questions & Answers

### Q1: How does the system prevent double-booking function halls?
**Answer:** Conflict prevention is implemented at two layers:
1. **Frontend AJAX Verification:** When a user selects a hall, date, and time range on `customer/book.php` or `availability.php`, a background request is dispatched to `api/check_availability.php`. If an overlapping booking exists, the form immediately warns the user and disables submission.
2. **Backend Server-Side Guard:** Inside `BookingService::checkHallAvailability()`, an explicit SQL prepared statement checks for conflicting reservations:
   ```sql
   WHERE b.hall_id = :hall_id
     AND b.event_date = :event_date
     AND b.booking_status IN ('confirmed', 'approved')
     AND (:start_time < b.end_time AND :end_time > b.start_time)
   ```
   This mathematical interval overlap check guarantees that no two events can overlap on the same venue.

### Q2: How does the system handle financial balances and advance deposits?
**Answer:** In `PaymentService::recordPayment()`, a database transaction is opened with row-locking (`FOR UPDATE`). When an advance or partial payment is received:
- A new record is inserted into the `payments` table with a unique receipt reference (`RCPT-YYYY-XXXX`).
- The sum of all payments for that booking is recalculated (`SUM(amount)`).
- The remaining balance is computed: `balance_due = total_amount - total_paid`.
- If the payment covers the required advance deposit (e.g., 30%), the booking status can automatically transition from `approved` to `confirmed`.
- The `payment_status` is updated to `unpaid`, `partially_paid`, or `fully_paid`.

### Q3: What security measures protect user data and system pages?
**Answer:**
- **SQL Injection Prevention:** 100% of database queries use PDO prepared statements with parameter binding.
- **Cross-Site Scripting (XSS) Prevention:** All dynamic variables rendered in HTML are sanitized using `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')` wrapped in a helper function `e()`.
- **Cross-Site Request Forgery (CSRF):** Form submissions validate a cryptographically secure random token (`bin2hex(random_bytes(32))`) stored in the session.
- **Session Security:** Session cookies use `httponly=true`, `samesite=Lax`, and `session_regenerate_id(true)` upon login.
- **Password Protection:** Passwords are never stored in plain text; they are hashed using PHP's native `password_hash($password, PASSWORD_DEFAULT)`.

---

## 6. Technologies Used

- **Backend:** PHP 8.x (Native PDO, Object-Oriented Services, Procedural Controllers)
- **Database:** MySQL / MariaDB (InnoDB, Foreign Key Constraints, Indexes)
- **Frontend:** HTML5, CSS3, Bootstrap 5.3, Bootstrap Icons 1.11, FullCalendar 6.1
- **Client Scripting:** Vanilla JavaScript (ES6+), Fetch API
- **Deployment Platform:** XAMPP (Apache + MySQL on Windows)
