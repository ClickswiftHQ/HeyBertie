---
id: d0c5a001-0002-4000-8000-000000000002
title: 'Deployment Guide'
excerpt: 'Step-by-step process for deploying heyBertie to production, including environment setup and post-deploy checks.'
content: '<h2>Deployment Process</h2><p>This guide covers the standard deployment workflow for heyBertie.</p><h3>Pre-deployment Checklist</h3><ul><li>All tests pass locally — <code>php artisan test</code></li><li>Frontend assets build cleanly — <code>npm run build</code></li><li>Migrations are reviewed and tested</li><li>Environment variables are updated if needed</li></ul><h3>Deploy Steps</h3><ol><li>Merge your PR to the <code>main</code> branch</li><li>The CI pipeline runs tests and builds assets</li><li>On success, the deployment is triggered automatically</li><li>Post-deploy: run <code>php artisan migrate --force</code> and clear caches</li></ol><h3>Rollback</h3><p>If something goes wrong, revert the merge commit and trigger a fresh deploy. Database rollbacks should be handled with a new migration rather than <code>migrate:rollback</code> in production.</p>'
categories:
  - deployment
tags:
  - devops
  - production
updated_by: 1
updated_at: 1709568000
---
