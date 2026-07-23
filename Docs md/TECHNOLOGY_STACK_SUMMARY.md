# Technology Stack Summary
## Medication Reconciliation Prototype - SASMEC @IIUM
### Laravel + MySQL (Local Server Deployment)

---

## TECHNOLOGY STACK OVERVIEW

### Why Laravel + MySQL for This Project?

✅ **Ideal for Local Server Deployment**
- Single server architecture (Web server + PHP + MySQL all on one machine)
- Minimal infrastructure requirements
- No cloud dependencies
- Easy to manage and maintain locally

✅ **Rapid Development**
- Laravel is a full-stack framework with built-in features
- Eloquent ORM reduces database query complexity
- Built-in authentication (Sanctum) and authorization (Policies)
- Blade templating for dynamic UI without separate JavaScript frameworks

✅ **Suitable for Healthcare/Clinical Systems**
- Strong validation and security features (critical for healthcare)
- Excellent audit trail capabilities
- User-friendly deployment
- Active community support

✅ **Cost-Effective**
- Open-source (free)
- Uses MySQL (free and widely supported)
- Minimal hosting costs (local server only)
- Easy to hire PHP/Laravel developers in Malaysia

✅ **Malaysian Context**
- Laravel is popular in Malaysian development community
- MySQL is ubiquitous in Malaysian hospitals/clinics
- XAMPP/WAMP stack widely available and supported
- Easy to find local technical support

---

## COMPLETE TECHNOLOGY STACK

### FRONTEND LAYER

**Framework & UI:**
- **Server-Side Templating:** Laravel Blade (PHP templating engine)
- **CSS Framework:** Bootstrap 5 or Tailwind CSS (responsive design)
- **JavaScript:** Vanilla JS or Alpine.js (lightweight, minimal dependencies)
- **Charts:** Chart.js or ApexCharts (interactive data visualization)
- **Forms:** Laravel Blade forms with validation feedback

**Key Advantages:**
- No separate JavaScript build process (SPA not needed)
- All rendering on server (faster for CRUD operations)
- Mobile-responsive out-of-the-box
- Simpler deployment (no Node.js build server needed)

### BACKEND LAYER

**Core Framework:**
- **Framework:** Laravel 10.x or 11.x (latest stable versions)
- **PHP Version:** PHP 8.1+ (with Composer package manager)
- **Architecture:** MVC (Model-View-Controller) pattern

**Key Components:**

1. **Routing**
   - Web routes (traditional HTTP requests for browser)
   - API routes (optional, for future integrations)
   - Route grouping by functionality

2. **Controllers**
   - PatientController
   - MedicationHistoryController
   - ReconciliationController
   - DiscrepancyController
   - RecommendationController
   - ReportController
   - UserController

3. **Models (Eloquent ORM)**
   - Patient
   - MedicationHistory
   - Reconciliation
   - Discrepancy
   - Recommendation
   - User
   - AuditLog

4. **Middleware**
   - Authentication (verify user is logged in)
   - Authorization (verify user has permission)
   - CSRF protection
   - Session handling
   - Request validation

5. **Services/Business Logic**
   - PatientService (risk calculation, search)
   - MedicationHistoryService (BPMH compilation)
   - ReconciliationEngine (discrepancy detection algorithm)
   - ClinicalDecisionSupportService (drug interactions, contraindications)
   - RecommendationService (pharmacist recommendations)
   - ReportService (metrics calculation, export)

6. **Authentication & Authorization**
   - Laravel Sanctum (session-based authentication)
   - Database-driven role management
   - Policy classes for resource authorization
   - Password hashing with bcrypt

### DATABASE LAYER

**Primary Database:**
- **Database Engine:** MySQL 8.0+ (recommended) or MariaDB 10.6+
- **Connection:** mysqli or PDO (via Laravel ORM)
- **Tables:** 7 main tables + audit logs table (see SRS Section 5.2)

**Data Storage:**
- Patient demographics and clinical data
- Medication history from interviews
- Reconciliation records and statuses
- Identified discrepancies and resolutions
- User accounts and roles
- Audit trail of all system actions

**Database Size Estimates:**
- Prototype Phase: <1 GB
- Pilot Phase (50-bed ward): 5-10 GB
- Full Implementation (350-bed hospital): 50+ GB

### LOCAL SERVER INFRASTRUCTURE

**Operating System Options:**

1. **Windows Server 2019/2022**
   - IIS or Apache web server
   - PHP 8.1+ via FastCGI or direct installation
   - MySQL Community Edition
   - XAMPP/WAMP for easy setup

2. **Linux (Ubuntu 20.04 LTS or newer)**
   - Apache 2.4+ or Nginx 1.20+
   - PHP 8.1+ with required extensions
   - MySQL 8.0+
   - LAMP stack (Linux, Apache, MySQL, PHP)
   - Recommended for production stability

3. **MacOS (if using as development/small server)**
   - Nginx or Apache
   - PHP via Homebrew or MAMP
   - MySQL via Homebrew or MAMP

**Minimum Server Requirements:**
- CPU: 2 cores (4 cores recommended)
- RAM: 4 GB (8 GB for pilot phase)
- Storage: 50 GB (SSD recommended)
- Network: Static IP address, reliable internet
- Backup: External drive or NAS

**Web Server Configuration:**
```
Apache:
├─ mod_rewrite enabled (for Laravel routing)
├─ .htaccess support
└─ PHP 8.1+ module installed

OR

Nginx:
├─ PHP-FPM running
├─ Location blocks configured for Laravel
└─ Rewrite rules for routing
```

**PHP Configuration:**
```
Required Extensions:
- mysqli (MySQL connection)
- PDO (Database abstraction)
- OpenSSL (HTTPS/encryption)
- JSON (data handling)
- Tokenizer (Laravel requirement)
- XML (data parsing)
- Ctype (character type checking)
- Fileinfo (file type detection)

Recommended Settings:
- memory_limit = 256M (or higher)
- upload_max_filesize = 20M
- post_max_size = 20M
- max_execution_time = 60 seconds
- session.cookie_secure = On (HTTPS)
- session.cookie_httponly = On
```

---

## DEVELOPMENT & DEPLOYMENT WORKFLOW

### Local Development Setup

**For Developers on Individual Machines:**

1. **Install XAMPP/WAMP Stack**
   ```
   XAMPP 8.0+ includes:
   - Apache web server
   - PHP 8.0+
   - MySQL 8.0
   - phpMyAdmin
   ```

2. **Clone Project Repository**
   ```
   git clone [repository-url]
   cd sasmec-medreconciliation
   ```

3. **Install Dependencies**
   ```
   composer install
   npm install (for any frontend build tools)
   ```

4. **Configure Environment**
   ```
   cp .env.example .env
   php artisan key:generate
   php artisan migrate
   php artisan db:seed (optional test data)
   ```

5. **Run Development Server**
   ```
   php artisan serve
   # Access at http://localhost:8000
   ```

### Production Deployment (Local Server)

**Step 1: Server Setup**
- Install OS (Ubuntu 20.04 LTS recommended)
- Configure static IP address
- Setup firewall rules
- Enable SSH access for administration

**Step 2: Install Stack**
```bash
# Ubuntu/Debian
sudo apt update
sudo apt install apache2 php8.1 mysql-server composer

# Enable required PHP extensions
sudo apt install php8.1-mysql php8.1-curl php8.1-json php8.1-tokenizer php8.1-xml

# Enable Apache modules
sudo a2enmod rewrite
sudo a2enmod ssl
```

**Step 3: Deploy Application**
```bash
# Clone repository
cd /var/www/
git clone [repository-url] sasmec-medreconciliation

# Install dependencies
cd sasmec-medreconciliation
composer install --no-dev

# Set permissions
sudo chown -R www-data:www-data .
sudo chmod -R 775 storage bootstrap/cache

# Copy environment file
cp .env.example .env
# Edit .env with database credentials, app name, etc.

# Generate application key
php artisan key:generate

# Run migrations (creates database tables)
php artisan migrate --force

# Seed initial data (users, roles, test data)
php artisan db:seed
```

**Step 4: Configure Web Server**

For Apache (create `/etc/apache2/sites-available/sasmec.conf`):
```apache
<VirtualHost *:80>
    ServerName sasmec.local
    DocumentRoot /var/www/sasmec-medreconciliation/public
    
    <Directory /var/www/sasmec-medreconciliation/public>
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/sasmec-error.log
    CustomLog ${APACHE_LOG_DIR}/sasmec-access.log combined
</VirtualHost>
```

Enable site and restart Apache:
```bash
sudo a2ensite sasmec
sudo systemctl restart apache2
```

**Step 5: Setup Backups**
```bash
# Daily MySQL backup script
#!/bin/bash
BACKUP_DIR="/backups/mysql"
DATE=$(date +%Y%m%d_%H%M%S)
mysqldump -u root -p[password] sasmec_medreconciliation > $BACKUP_DIR/backup_$DATE.sql
gzip $BACKUP_DIR/backup_$DATE.sql
```

Schedule with cron:
```bash
0 2 * * * /scripts/backup-database.sh
```

---

## DEVELOPMENT ENVIRONMENT SETUP

### For Individual Developers

**Required Software:**

1. **XAMPP 8.0 or newer**
   - All-in-one package with Apache, PHP, MySQL
   - Download from: https://www.apachefriends.org/

2. **Code Editor/IDE**
   - Visual Studio Code (free, popular)
   - PhpStorm (commercial, full-featured)
   - Sublime Text (lightweight)

3. **Git Version Control**
   - Git client for Windows/Mac/Linux
   - GitHub Desktop or command-line Git

4. **Composer**
   - PHP dependency manager
   - Download from: https://getcomposer.org/

5. **Laravel Artisan CLI**
   - Command-line tool for Laravel
   - Installed via Composer

### Development Workflow

**Creating New Feature:**
```bash
# Create database migration
php artisan make:migration create_reconciliations_table

# Create model
php artisan make:model Reconciliation

# Create controller
php artisan make:controller ReconciliationController

# Create form validation
php artisan make:request ReconciliationRequest

# Run migrations
php artisan migrate

# Start development server
php artisan serve
```

**Testing During Development:**
```bash
# Run unit tests
php artisan test

# Run specific test
php artisan test --filter ReconciliationTest

# Generate test coverage report
php artisan test --coverage
```

---

## SECURITY IMPLEMENTATIONS (BUILT-IN)

**Laravel Security Features:**

1. **SQL Injection Prevention**
   - Parameterized queries via Eloquent ORM
   - Query builder escapes all values

2. **Cross-Site Scripting (XSS) Protection**
   - Blade templating auto-escapes output
   - CSRF tokens on all forms
   - Content Security Policy headers

3. **Authentication & Authorization**
   - Password hashing with bcrypt
   - Session management with secure cookies
   - Role-based access control via Policies
   - User permissions middleware

4. **Data Encryption**
   - Application key for encryption/decryption
   - HTTPS recommended for production
   - Sensitive data encrypted in database (optional)

5. **Rate Limiting**
   - Prevent brute force attacks
   - API rate limiting for protection
   - Configurable per endpoint

6. **Security Headers**
   - X-Frame-Options (clickjacking prevention)
   - X-Content-Type-Options (MIME sniffing prevention)
   - X-XSS-Protection
   - Strict-Transport-Security (HTTPS only)

---

## TESTING FRAMEWORK

**Unit & Feature Testing:**

```bash
# PHPUnit / Laravel Testing
php artisan make:test ReconciliationTest

# Run tests
php artisan test

# Test with coverage
php artisan test --coverage

# Specific test class
php artisan test tests/Feature/ReconciliationTest.php
```

**Test Database:**
- Automatic SQLite in-memory database for tests
- No test data pollution
- Fast execution
- Automatic cleanup

---

## DEPLOYMENT CHECKLIST

Before going live:

- [ ] Server OS installed and hardened
- [ ] PHP 8.1+ installed with required extensions
- [ ] MySQL 8.0+ installed and secured
- [ ] Apache/Nginx configured with SSL
- [ ] Application cloned and dependencies installed
- [ ] Environment variables configured (.env file)
- [ ] Database migrations run successfully
- [ ] Initial user accounts created
- [ ] Backups configured and tested
- [ ] Monitoring set up (system logs, error logs)
- [ ] SSL certificate installed (self-signed for local)
- [ ] Firewall configured (allow SSH, HTTP, HTTPS only)
- [ ] Application tested end-to-end
- [ ] User training completed
- [ ] Go-live documentation prepared

---

## MONITORING & MAINTENANCE

**Ongoing Maintenance Tasks:**

**Daily:**
- Monitor application error logs
- Check system disk space
- Verify backup completion

**Weekly:**
- Review audit logs
- Check for critical PHP/security updates
- Verify database integrity

**Monthly:**
- Database optimization
- Review system performance metrics
- Backup recovery testing
- User access review

**Quarterly:**
- Security audit
- Performance tuning
- Database maintenance (optimize tables, etc.)
- Disaster recovery drill

**Log Locations:**
```
Apache: /var/log/apache2/sasmec-error.log
        /var/log/apache2/sasmec-access.log
PHP: /var/log/php-errors.log
Laravel: storage/logs/laravel.log
MySQL: /var/log/mysql/error.log
```

---

## COST ANALYSIS

### One-Time Costs
- Server hardware: RM 3,000-5,000 (if not using existing)
- Initial setup & deployment: RM 5,000-10,000 (labor)
- SSL certificate: RM 0 (self-signed) to RM 500 (commercial)
- **Total: RM 8,000-15,500**

### Annual Operating Costs
- Server electricity: RM 1,000-2,000/year
- Internet connection: RM 500-1,500/year
- Backup storage: RM 200-500/year
- Maintenance & support: RM 3,000-5,000/year
- **Total: RM 4,700-9,000/year**

### No Additional Costs
- Software licenses (all open-source)
- Database licensing (MySQL is free)
- Development tools
- Version control (GitHub free tier available)

---

## COMPARISON: LARAVEL VS ALTERNATIVES

| Aspect | Laravel | React+Node | Django | Others |
|--------|---------|------------|--------|--------|
| Setup Time | Very Fast | Moderate | Moderate | Variable |
| Learning Curve | Gentle | Steep | Moderate | Varies |
| Local Deploy | Easiest | Hard | Easy | Varies |
| Healthcare Suitability | Excellent | Good | Good | Good |
| Dev Community Malaysia | Strong | Strong | Moderate | Varies |
| Cost | Free | Free | Free | Free |
| Maintenance | Easy | Moderate | Easy | Varies |
| Scalability Path | Good | Excellent | Good | Varies |

**Best Choice for SASMEC Prototype: LARAVEL**
- Fastest to prototype
- Easiest to deploy locally
- Minimal infrastructure
- Strong security features
- Easy to find local support

---

## NEXT STEPS

1. **Confirm Technology Stack Approval**
   - Present this summary to IT Director
   - Get sign-off on Laravel + MySQL choice

2. **Prepare Development Environment**
   - Install XAMPP on developer machines
   - Create Git repository on local server/GitHub
   - Set up development guidelines

3. **Begin Development**
   - Start with database schema (migrations)
   - Build models and controllers
   - Implement authentication first
   - Then implement modules in priority order

4. **Testing & Deployment**
   - Comprehensive unit/feature testing
   - User Acceptance Testing (UAT) with pharmacy staff
   - Deploy to local server following checklist
   - Monitor and iterate based on feedback

---

## SUPPORT & RESOURCES

### Official Documentation
- Laravel: https://laravel.com/docs
- PHP: https://www.php.net/docs.php
- MySQL: https://dev.mysql.com/doc/
- Bootstrap: https://getbootstrap.com/docs/

### Malaysian Laravel Community
- Laravel Malaysia Facebook Group
- Laravel.my website/forum
- Local meetups (Kuala Lumpur, Selangor)

### Recommended Learning Resources
- Laravel Bootcamp: https://bootcamp.laravel.com
- Laracasts: https://laracasts.com
- PHP: The Right Way: https://phptherightway.com

---

**Document prepared for:** SASMEC @IIUM Pharmacy Department  
**Prepared by:** System Analyst  
**Date:** June 2026  
**Status:** Final Technology Stack Recommendation

