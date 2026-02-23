# Production Deployment Checklist

## Pre-Deployment

### Code Quality
- [x] All files created and tested
- [x] No syntax errors (PSR-12 coding standards)
- [x] Error handling implemented throughout
- [x] Logging configured via `error_log()`
- [x] Security: Prepared statements for all queries
- [x] Security: Input validation on all endpoints
- [x] Database migrations included
- [x] Composer autoloader generated

### Testing
- [x] Unit logic tested (service layer)
- [x] Database connectivity verified
- [x] ACID transactions validated
- [x] Error cases handled
- [x] API endpoints documented

---

## Docker Deployment

### Pre-Build Checklist
```bash
# Verify files exist
ls -la Dockerfile docker-compose.yaml schema.sql .env

# Validate YAML syntax
docker-compose config

# Check permissions
chmod +x Dockerfile
chmod +x docker-compose.yaml
```

### Build & Run
```bash
# Build images
docker-compose build

# Start services
docker-compose up -d

# Verify all containers running
docker-compose ps
# Expected: 3 containers (db, php, phpmyadmin) in "Up" status

# Check logs for errors
docker-compose logs php
docker-compose logs db
```

### Post-Deployment Verification
```bash
# Test API endpoint
curl http://localhost/api/users
# Expected: {"error":"Route not found"} or empty list (before creating users)

# SSH into PHP container
docker-compose exec php bash
cd /var/www/html
php -v  # Should show PHP 8.2.x

# Test database connectivity
docker-compose exec db mysql -u bank_user -p bank_db -e "SHOW TABLES;"
# Expected: Shows users, accounts, transactions tables
```

---

## Production Configuration

### Environment Variables (.env)
```env
# Database Configuration
DB_HOST=production-db-host
DB_USER=bank_user_prod
DB_PASSWORD=<strong-password>
DB_NAME=bank_db_prod

# Application
APP_ENV=production
```

**CRITICAL**: 
- Never commit `.env` to version control
- Use strong database password
- Restrict database access to application only

### Apache Configuration (.htaccess)
```apache
# Ensure mod_rewrite is enabled
a2enmod rewrite

# Verify DocumentRoot points to public/
# Example: /var/www/html/public
```

### MySQL Configuration
```sql
-- Create production database
CREATE DATABASE bank_db_prod CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Create limited user (not root)
CREATE USER 'bank_user_prod'@'production-server' IDENTIFIED BY '<strong-password>';
GRANT SELECT, INSERT, UPDATE, DELETE ON bank_db_prod.* TO 'bank_user_prod'@'production-server';
FLUSH PRIVILEGES;

-- Load schema
mysql -u bank_user_prod -p bank_db_prod < schema.sql
```

### SSL/HTTPS
```apache
# Redirect HTTP to HTTPS
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Add security headers
Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
Header always set X-Content-Type-Options "nosniff"
Header always set X-Frame-Options "SAMEORIGIN"
```

---

## Backup & Recovery

### Database Backup
```bash
# Regular backup schedule (daily)
docker-compose exec db mysqldump -u bank_user -p bank_db | gzip > backup-$(date +%Y%m%d).sql.gz

# Restore from backup
docker-compose exec db mysql -u bank_user -p bank_db < backup-20240223.sql
```

### Volume Backup
```bash
# Backup MySQL persistent volume
docker run --rm -v bank_api_db_data:/data -v $(pwd):/backup \
  alpine tar czf /backup/mysql-backup.tar.gz /data
```

---

## Monitoring

### Application Logs
```bash
# Monitor real-time logs
docker-compose logs -f php

# View error log
docker-compose exec php tail -f /var/log/apache2/error.log

# PHP error log
docker-compose exec php tail -f /var/log/php_errors.log
```

### Database Monitoring
```bash
# Check database status
docker-compose exec db mysqladmin -u bank_user -p status

# Monitor slow queries
docker-compose exec db mysql -u bank_user -p -e "SHOW FULL PROCESSLIST;"
```

### Container Health
```bash
# Check container resource usage
docker stats

# View container logs
docker-compose logs <service-name>

# Restart service if needed
docker-compose restart php
```

---

## Security Checklist

### To Verify Before Production
- [ ] Database password is strong (12+ chars, mixed case, special chars)
- [ ] `.env` file is not in git (check `.gitignore`)
- [ ] `composer.lock` is committed (locked dependencies)
- [ ] Prepared statements used for all queries
- [ ] Input validation on all endpoints
- [ ] Error messages don't expose internal details
- [ ] HTTPS/SSL configured
- [ ] CORS headers configured if needed
- [ ] Rate limiting implemented
- [ ] Database user has minimal permissions
- [ ] File permissions: 644 (files), 755 (directories)
- [ ] No debug mode in production
- [ ] Log files outside web root

### File Permissions
```bash
# Set correct permissions
chmod 644 /var/www/html/**/*.php
chmod 644 /var/www/html/*.json
chmod 755 /var/www/html/public
chmod 755 /var/www/html/src

# Restrict sensitive files
chmod 600 /var/www/html/.env
```

---

## Performance Optimization

### Database
```sql
-- Verify indexes exist
SHOW INDEX FROM accounts;
SHOW INDEX FROM transactions;

-- Analyze query performance
EXPLAIN SELECT * FROM accounts WHERE user_id = 1;
```

### PHP
```php
// In production .env or config
// Enable opcache for performance
php.ini: opcache.enable=1
php.ini: opcache.memory_consumption=128
```

### Caching (Optional)
```bash
# If using Redis for caching
docker run -d -p 6379:6379 redis:latest
```

---

## Load Testing

### Before going live, test under load
```bash
# Using Apache Bench
ab -n 1000 -c 10 http://localhost/api/users

# Using hey (Go-based)
hey -n 1000 -c 10 http://localhost/api/users

# Using wrk (Lua-based)
wrk -t4 -c100 -d30s http://localhost/api/users
```

**Expected Metrics:**
- Response time: < 200ms
- Throughput: > 100 req/s
- Success rate: 99.9%+

---

## Troubleshooting

### 502 Bad Gateway
```bash
# Check PHP-FPM status
docker-compose logs php

# Restart PHP service
docker-compose restart php

# Check Apache error log
docker-compose exec php tail /var/log/apache2/error.log
```

### Slow Queries
```bash
# Enable MySQL slow query log
docker-compose exec db mysql -u root -p -e "SET GLOBAL slow_query_log = 'ON';"
docker-compose exec db mysql -u root -p -e "SET GLOBAL long_query_time = 2;"

# View slow queries
docker-compose exec db tail /var/log/mysql/slow.log
```

### Database Connection Issues
```bash
# Verify database is running
docker-compose ps db

# Test connection
docker-compose exec php php -r "
require 'vendor/autoload.php';
\$db = new \Core\Database();
echo 'Connected: ' . (\$db ? 'Yes' : 'No');
"

# Check credentials in .env
cat .env | grep DB_
```

---

## Maintenance Schedule

### Daily
- [ ] Monitor application logs
- [ ] Check disk space (especially DB volume)
- [ ] Verify all containers are running

### Weekly
- [ ] Review error logs for patterns
- [ ] Backup database
- [ ] Test backup restoration
- [ ] Monitor transaction volume

### Monthly
- [ ] Review performance metrics
- [ ] Check for security updates
  ```bash
  docker image ls
  docker pull mysql:8.0
  docker pull php:8.2-apache
  ```
- [ ] Vacuum/optimize database
  ```sql
  ANALYZE TABLE users;
  ANALYZE TABLE accounts;
  ANALYZE TABLE transactions;
  ```

### Quarterly
- [ ] Load test the system
- [ ] Security audit
- [ ] Capacity planning
- [ ] Disaster recovery drill

---

## Rollback Plan

If something goes wrong:

```bash
# Stop current deployment
docker-compose down

# Restore from backup
docker run --rm -v bank_api_db_data:/data -v $(pwd):/backup \
  alpine tar xzf /backup/mysql-backup.tar.gz

# Restart
docker-compose up -d

# Verify
curl http://localhost/api/users
```

---

## Escalation Contacts

Create contact list for:
- [ ] DevOps/Infrastructure team
- [ ] Database administrator
- [ ] Security team
- [ ] Product owner

---

## Post-Deployment Report

After successful deployment:
- [ ] Document deployment date/time
- [ ] Record deployment version/commit
- [ ] Confirm all tests passed
- [ ] Document any deviations from checklist
- [ ] Update documentation if needed
- [ ] Brief team on deployment
- [ ] Schedule follow-up monitoring

---

## Sign-Off

**Deployed By**: _________________
**Date**: ________________________
**Version**: ______________________
**Environment**: __________________

---

**This checklist ensures production-ready deployment with security, monitoring, and rollback capabilities.**
