<?php

/*
| Every feature a client can have, matching the public quote page
| (nexusdigitallabs.dev/p/class-manager/quote). "core" features come with
| every plan and are on by default; "addon" features are paid extras.
| A "locked" feature cannot be switched off because the rest depends on it.
| Prices are LKR and only used for the admin's monthly revenue estimate.
*/

return [
    'core' => [
        'students' => ['name' => 'Students and profiles', 'description' => 'Student registration, profiles and parent contacts', 'locked' => true],
        'classes' => ['name' => 'Classes and schedule', 'description' => 'Classes, batches, one-to-one lessons and weekly schedule', 'locked' => true],
        'fees' => ['name' => 'Fees and receipts', 'description' => 'Fee tracking, bank-slip approval, part payments and receipts'],
        'attendance' => ['name' => 'Attendance', 'description' => 'Attendance marking per class and day'],
        'online_links' => ['name' => 'Online class links', 'description' => 'Zoom or Meet links shown only to students who paid'],
        'materials' => ['name' => 'Study materials', 'description' => 'PDF notes and papers for paid students'],
        'reminders' => ['name' => 'WhatsApp reminders', 'description' => 'Fee reminders in your own words, shared on WhatsApp'],
        'reports' => ['name' => 'Reports', 'description' => 'Income by class and payment method, and outstanding fees'],
        'export' => ['name' => 'Data export', 'description' => 'Excel and CSV export any time'],
        'backup' => ['name' => 'Google Drive backup', 'description' => 'Nightly backup to the client\'s own Google Drive'],
    ],

    'addon' => [
        'exams' => ['name' => 'Exams, marks and rank lists', 'description' => 'Paper class marks, ranks and progress', 'monthly' => 1000, 'default_for' => ['tutor', 'institute']],
        'halls' => ['name' => 'Halls and timetable', 'description' => 'Book halls and avoid timetable clashes', 'monthly' => 1000, 'default_for' => ['institute']],
        'staff' => ['name' => 'Staff logins and permissions', 'description' => 'Separate access for counter staff, admins and teachers', 'monthly' => 1000, 'default_for' => ['institute']],
        'parents' => ['name' => 'Parent portal', 'description' => 'Parents see attendance, fees and results', 'monthly' => 1500],
        'sms' => ['name' => 'Automatic SMS', 'description' => 'Reminders, receipts and results by SMS', 'monthly' => 1000, 'per_student' => 20],
        'payments' => ['name' => 'Online card payments', 'description' => 'Students pay by card or online banking', 'monthly' => 0],
        'domain' => ['name' => 'Own web address', 'description' => 'Branded login on the client\'s own domain', 'monthly' => 500],
        'app' => ['name' => 'Branded mobile app', 'description' => 'Android and iPhone app with app notifications', 'monthly' => 5000],
    ],

    'pricing' => [
        'fixed_monthly' => 2500,
    ],
];
