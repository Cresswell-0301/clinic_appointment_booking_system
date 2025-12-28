# Clinic Appointment Booking System  
**CCS6344 – Database & Cloud Security (T2530) – Assignment 1**

## Project Overview
This project implements a **secure web-based Clinic Appointment Booking System** developed using **PHP** and **Microsoft SQL Server (MSSQL)**.  

The system supports **Patients, Doctors, Administrators, and SuperAdmin** roles and focuses strongly on **backend security, access control, and database protection**, in line with the objectives of the CCS6344 course.

The primary goal of this project is not only functional correctness, but also **demonstrating secure database and backend design against internal and external threats**.

---

## Key Objectives
- Provide a secure platform for booking and managing clinic appointments
- Enforce **role-based and ownership-based access control**
- Protect sensitive personal and appointment data stored in MSSQL
- Apply **database security concepts** learned in CCS6344
- Demonstrate compliance with **PDPA 2010 security principles**

---

## System Architecture
The system follows a **three-tier architecture**:

1. **Presentation Layer**
   - HTML, CSS, JavaScript
   - Handles user interaction and input

2. **Application Layer**
   - PHP
   - Implements business logic, authentication, authorization, and security checks

3. **Database Layer**
   - Microsoft SQL Server (MSSQL)
   - Stores users, appointments, availability, audit logs, and login attempts

Security controls are enforced **at the backend**, not solely at the UI level.

---

## User Roles
- **Patient** – Book, view, modify, and cancel own appointments
- **Doctor** – Manage schedules and view assigned patient information
- **Administrator** – Manage users, appointments, and doctor availability
- **SuperAdmin** – Full administrative privileges, including audit log access

---

## Backend Security Features

### Authentication & Session Security
- Password hashing using `password_hash()` (bcrypt)
- Secure login verification using `password_verify()`
- Session ID regeneration after login (prevents session fixation)
- Secure session cookies:
  - `HttpOnly`
  - `Secure`
  - `SameSite=Strict`
- CSRF token validation for all state-changing requests

---

### Role-Based Access Control (RBAC)
- Enforced on **every protected backend page**
- Role validation occurs **before any database query**
- SuperAdmin-only access to sensitive operations such as audit logs
- Prevents privilege escalation and unauthorized access

---

### SQL Injection Prevention
- **100% parameterized queries** (PDO / SQLSRV prepared statements)
- No dynamic SQL concatenation
- Malicious input cannot alter query structure

---

### Ownership & Row-Level Protection (Application Layer)
- Patients can only access records where:
- Doctors can only access appointments linked to their `doctor_id`
- Admin actions are role-restricted and logged
- Prevents **horizontal privilege escalation**

---

### Least-Privilege Database Access
- Application connects using a **restricted SQL account**
- No use of `sa` or administrative credentials
- Application account cannot:
- DROP tables
- ALTER schema
- Access system-level objects

Even if the application layer is compromised, database damage is limited.

---

### Audit Logging & Monitoring
- Security-relevant actions recorded in `AuditLogs` table:
- Login attempts
- User management
- Appointment creation, update, cancellation
- Each log entry includes:
- User ID
- Role
- Action type
- Affected entity
- IP address
- Timestamp
- Supports accountability, repudiation prevention, and forensic review

---

## Threat Modelling
The system is evaluated using **STRIDE** and **DREAD** threat modelling:

- **Spoofing** → Strong password hashing & rate-limiting
- **Tampering** → Ownership checks & CSRF protection
- **Repudiation** → Comprehensive audit logging
- **Information Disclosure** → Row-level access enforcement
- **Denial of Service** → Login attempt monitoring
- **Elevation of Privilege** → RBAC enforcement

Each identified threat is mitigated through **concrete backend controls**.

---

## PDPA 2010 Compliance
Backend controls support PDPA 2010 principles:
- **Security Principle** – Encrypted credentials, session security, RBAC
- **Disclosure Principle** – Role-based and ownership-based data access
- **Access Principle** – Users can only access authorized personal data
- **Accountability** – Audit logs record sensitive operations

---

## Testing
The system has been tested for:
- Insert new appointment
- Modify existing appointment
- Cancel appointment
- Role-restricted access validation
- Audit log generation

---

## Notes
This project was developed for **academic purposes** as part of the  
**CCS6344 – Database & Cloud Security** course at Multimedia University (MMU).

---