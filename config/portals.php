<?php

return [
    'lms' => [
        'name' => 'Learning Management System',
        'key' => 'LMS',
        'url' => env('LMS_URL', 'https://lms.sits.edu.et'),
        'roles' => ['SUPERADMIN', 'ADMIN', 'EDITOR', 'TRAINER', 'STUDENT', 'STAFF', 'LIBRARIAN'],
        'icon' => 'fas fa-graduation-cap',
        'color' => 'indigo', // Indigo theme
        'description' => 'Access online courses, track academic progress, take quizzes, and interact with trainers.',
        'features' => [
            'Virtual Lectures & Video Modules',
            'Interactive Quizzes & Assignments',
            'Trainer Discussion Forums',
            'Progress Tracking & Certificates'
        ]
    ],
    'erp' => [
        'name' => 'Enterprise Resource Planning',
        'key' => 'ERP',
        'url' => env('ERP_URL', 'https://pms.sits.edu.et'),
        'roles' => ['SUPERADMIN', 'ADMIN', 'EDITOR', 'TRAINER', 'STAFF', 'LIBRARIAN'],
        'icon' => 'fas fa-users-cog',
        'color' => 'cyan', // Cyan theme
        'description' => 'Manage institutional resources, human resources, finance, and administrative operations.',
        'features' => [
            'Financial & Budget Management',
            'Human Resources & Payroll',
            'Inventory & Asset Tracking',
            'Administrative Workflows'
        ]
    ],
    'library' => [
        'name' => 'Digital Library Portal',
        'key' => 'Library',
        'url' => env('LIBRARY_URL', 'https://library.sits.edu.et'),
        'roles' => ['SUPERADMIN', 'ADMIN', 'EDITOR', 'TRAINER', 'STUDENT', 'STAFF', 'LIBRARIAN', 'USER'],
        'icon' => 'fas fa-book-reader',
        'color' => 'amber', // Amber theme
        'description' => 'Browse electronic resources, download research papers, search catalog indices, and access journals.',
        'features' => [
            'Online E-Books Catalog',
            'Scholarly Journal Databases',
            'Searchable Theological Papers',
            'Personal Bookmarking'
        ]
    ]
];
