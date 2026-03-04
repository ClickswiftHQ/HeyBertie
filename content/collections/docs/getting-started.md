---
id: d0c5a001-0001-4000-8000-000000000001
title: 'Getting Started'
excerpt: 'Overview of the heyBertie platform architecture, local development setup, and key conventions.'
content: '<h2>Welcome</h2><p>This guide covers everything you need to get up and running with the heyBertie codebase.</p><h3>Architecture</h3><p>heyBertie is a Laravel 12 application with a hybrid frontend:</p><ul><li><strong>Public pages</strong> — Blade templates with Alpine.js (listing, booking flow, marketing)</li><li><strong>Dashboard</strong> — Inertia.js v2 with React 19 (business management)</li><li><strong>CMS</strong> — Statamic v6 for blog, guides, help centre, and docs</li></ul><h3>Local Development</h3><p>The application uses Laravel Herd for local serving and SQLite for the development database. Run <code>composer run dev</code> to start the Vite dev server alongside the application.</p><h3>Key Conventions</h3><ul><li>Pest 4 for testing — always write tests for new features</li><li>Pint for code formatting — run <code>vendor/bin/pint</code> before committing</li><li>Form Request classes for validation — never inline in controllers</li></ul>'
categories:
  - getting-started
tags:
  - setup
  - architecture
updated_by: 1
updated_at: 1709568000
---
