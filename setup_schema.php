<?php

require_once __DIR__ . '/includes/db.php';

$conn = getDbConnection();
if (!$conn) {
    die("DB connection failed: " . print_r(sqlsrv_errors(), true));
}

$queries = [];

// USERS
$queries[] = "
IF OBJECT_ID('dbo.Users', 'U') IS NULL
BEGIN
    CREATE TABLE dbo.Users (
        user_id        INT IDENTITY(1,1) PRIMARY KEY,
        username       VARCHAR(50)  NOT NULL UNIQUE,
        password_hash  VARCHAR(255) NOT NULL,
        full_name      VARCHAR(100) NOT NULL,
        email          VARCHAR(100) NOT NULL,
        phone_number   VARCHAR(20)  NULL,
        role           VARCHAR(20)  NOT NULL
            CHECK (role IN ('Patient','Doctor','Admin'))
    );
END
";

// DOCTORS
$queries[] = "
IF OBJECT_ID('dbo.Doctors', 'U') IS NULL
BEGIN
    CREATE TABLE dbo.Doctors (
        doctor_id      INT IDENTITY(1,1) PRIMARY KEY,
        user_id        INT NOT NULL,
        specialization VARCHAR(100) NULL,

        CONSTRAINT FK_Doctors_Users
            FOREIGN KEY (user_id) REFERENCES dbo.Users(user_id)
    );
END
";

// DOCTOR AVAILABILITY
$queries[] = "
IF OBJECT_ID('dbo.DoctorAvailability', 'U') IS NULL
BEGIN
    CREATE TABLE dbo.DoctorAvailability (
        availability_id INT IDENTITY(1,1) PRIMARY KEY,
        doctor_id       INT  NOT NULL,
        available_date  DATE NOT NULL,
        available_time  TIME NOT NULL,

        CONSTRAINT FK_DoctorAvailability_Doctors
            FOREIGN KEY (doctor_id) REFERENCES dbo.Doctors(doctor_id)
    );
END
";

// APPOINTMENTS
$queries[] = "
IF OBJECT_ID('dbo.Appointments', 'U') IS NULL
BEGIN
    CREATE TABLE dbo.Appointments (
        appointment_id   INT IDENTITY(1,1) PRIMARY KEY,
        patient_id       INT  NOT NULL,
        doctor_id        INT  NOT NULL,
        appointment_date DATE NOT NULL,
        appointment_time TIME NOT NULL,
        status           VARCHAR(20) NOT NULL
            CHECK (status IN ('Booked','Completed','Cancelled')),

        CONSTRAINT FK_Appointments_Patient
            FOREIGN KEY (patient_id) REFERENCES dbo.Users(user_id),
        CONSTRAINT FK_Appointments_Doctor
            FOREIGN KEY (doctor_id)  REFERENCES dbo.Doctors(doctor_id)
    );
END
";

foreach ($queries as $sql) {
    $stmt = sqlsrv_query($conn, $sql);
    if ($stmt === false) {
        echo "<pre>ERROR:\n" . print_r(sqlsrv_errors(), true) . "</pre>";
        exit;
    }
}

echo "Schema setup completed. Tables are ready.";
