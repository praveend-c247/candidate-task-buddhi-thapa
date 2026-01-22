# Task Reminder Email Setup Guide

This guide explains how to set up and test the task reminder email system (7-day, 24-hour, and 12-hour reminders).

## Prerequisites

The reminder system is already implemented. You need to configure and run it.

---

## Step 1: Configure Mail Settings

### Option A: Using Mailpit (Development)

Edit your `.env` file:

```env
MAIL_MAILER=smtp
MAIL_HOST=127.0.0.1
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="noreply@example.com"
MAIL_FROM_NAME="${APP_NAME}"
```

**Install Mailpit:**
```bash
# Download Mailpit (Linux)
wget https://github.com/axllent/mailpit/releases/latest/download/mailpit-linux-amd64 -O mailpit
chmod +x mailpit
sudo mv mailpit /usr/local/bin/

# Start Mailpit
mailpit
```

Mailpit will be available at: `http://localhost:8025` (web interface)

### Option B: Using SMTP (Production)

Edit your `.env` file:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="your-email@gmail.com"
MAIL_FROM_NAME="${APP_NAME}"
```

**For Gmail:**
1. Enable 2-Step Verification
2. Generate an App Password: https://myaccount.google.com/apppasswords
3. Use the app password in `MAIL_PASSWORD`

### Option C: Using Mailtrap (Testing)

1. Sign up at https://mailtrap.io
2. Get your SMTP credentials
3. Update `.env`:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your-mailtrap-username
MAIL_PASSWORD=your-mailtrap-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@example.com"
MAIL_FROM_NAME="${APP_NAME}"
```

---

## Step 2: Configure Queue

Reminders use queues for better performance. Configure in `.env`:

```env
QUEUE_CONNECTION=database
```

**Create queue table:**
```bash
php artisan queue:table
php artisan migrate
```

---

## Step 3: Start Queue Worker

The queue worker processes reminder emails. Run in a separate terminal:

```bash
php artisan queue:work
```

**For production, use a process manager like Supervisor:**

Create `/etc/supervisor/conf.d/laravel-worker.conf`:

```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/html/pro/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/html/pro/storage/logs/worker.log
stopwaitsecs=3600
```

Then:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-worker:*
```

---

## Step 4: Set Up Scheduler (Cron)

The scheduler runs the reminder command daily at 10:00 AM.

**Add to crontab:**
```bash
crontab -e
```

Add this line:
```bash
* * * * * cd /var/www/html/pro && php artisan schedule:run >> /dev/null 2>&1
```

**Verify it's running:**
```bash
php artisan schedule:list
```

You should see:
```
tasks:send-reminders  ... 10:00  ...  Next Due: 2024-01-15 10:00:00
```

---

## Step 5: Test the Reminder System

### Manual Test

Run the command manually to test:

```bash
php artisan tasks:send-reminders
```

**Expected output:**
```
Sent 3 reminders and 1 overdue notifications.
```

### Create Test Tasks

Create tasks with specific due dates to test reminders:

```bash
php artisan tinker
```

```php
use App\Models\Task;
use App\Models\Project;
use App\Models\User;
use Carbon\Carbon;

// Get a user
$user = User::where(['id'=>1])->first();
$project = Project::first();

// 7-day reminder test (due in 7 days)
Task::create([
    'project_id' => $project->id,
    'user_id' => $user->id,
    'assigned_user_id' => $user->id,
    'title' => 'Test 7-Day Reminder',
    'description' => 'This will trigger 7-day reminder',
    'priority' => 'high',
    'due_date' => Carbon::now()->addDays(7),
    'status' => 'pending',
]);

// 24-hour reminder test (due tomorrow)
Task::create([
    'project_id' => $project->id,
    'user_id' => $user->id,
    'assigned_user_id' => $user->id,
    'title' => 'Test 24-Hour Reminder',
    'description' => 'This will trigger 24-hour reminder',
    'priority' => 'medium',
    'due_date' => Carbon::now()->addDay(),
    'status' => 'pending',
]);

// 12-hour reminder test (due in 12 hours)
Task::create([
    'project_id' => $project->id,
    'user_id' => $user->id,
    'assigned_user_id' => $user->id,
    'title' => 'Test 12-Hour Reminder',
    'description' => 'This will trigger 12-hour reminder',
    'priority' => 'high',
    'due_date' => Carbon::now()->addHours(12),
    'status' => 'pending',
]);

// Overdue test (past due date)
Task::create([
    'project_id' => $project->id,
    'user_id' => $user->id,
    'assigned_user_id' => $user->id,
    'title' => 'Test Overdue Task',
    'description' => 'This will trigger overdue notification',
    'priority' => 'high',
    'due_date' => Carbon::now()->subDays(2),
    'status' => 'pending',
]);
```

### Test Reminder Logic

**For 7-day reminder:**
- Create a task due exactly 7 days from now
- Run the command: `php artisan tasks:send-reminders`
- Check your mail inbox (Mailpit/Mailtrap)

**For 24-hour reminder:**
- Create a task due tomorrow
- Run the command: `php artisan tasks:send-reminders`
- Check your mail inbox

**For 12-hour reminder:**
- Create a task due in 12 hours
- Run the command: `php artisan tasks:send-reminders`
- Check your mail inbox

---

## How Reminders Work

### Reminder Schedule

1. **7-Day Reminder:**
   - Sent 7 days before due date
   - Example: Task due on Jan 15 → Reminder sent on Jan 8

2. **24-Hour Reminder:**
   - Sent 1 day before due date
   - Example: Task due on Jan 15 → Reminder sent on Jan 14

3. **12-Hour Reminder:**
   - Sent 12 hours before due date
   - Example: Task due on Jan 15 at 2:00 PM → Reminder sent on Jan 15 at 2:00 AM

4. **Overdue Notification:**
   - Sent when task is past due date
   - Only for incomplete tasks

### Working Days Rule

- Reminders are only sent on **Monday-Friday**
- If reminder date falls on Saturday → Sent on Friday
- If reminder date falls on Sunday → Sent on Friday
- Command runs daily at **10:00 AM UTC**

### Who Receives Reminders

- **Assigned User** (if task is assigned)
- **Task Owner** (if different from assigned user)

---

## Troubleshooting

### No Emails Being Sent

1. **Check queue worker is running:**
   ```bash
   php artisan queue:work
   ```

2. **Check queue jobs:**
   ```bash
   php artisan queue:failed
   ```

3. **Check mail configuration:**
   ```bash
   php artisan tinker
   ```
   ```php
   Mail::raw('Test email', function($message) {
       $message->to('your-email@example.com')->subject('Test');
   });
   ```

4. **Check logs:**
   ```bash
   tail -f storage/logs/laravel.log
   ```

### Scheduler Not Running

1. **Verify cron is set up:**
   ```bash
   crontab -l
   ```

2. **Test scheduler manually:**
   ```bash
   php artisan schedule:run
   ```

3. **Check schedule list:**
   ```bash
   php artisan schedule:list
   ```

### Reminders Not Triggering

1. **Check task due dates:**
   - Task must have a `due_date`
   - Task status must not be `completed`

2. **Check reminder timing:**
   - 7-day: Exactly 7 days before
   - 24-hour: Due date is tomorrow
   - 12-hour: Between 11-12 hours before

3. **Check working days:**
   - Reminders only sent Monday-Friday
   - Weekend adjustments are automatic

---

## Production Checklist

- [ ] Mail configuration set up (SMTP)
- [ ] Queue worker running (Supervisor)
- [ ] Scheduler cron job configured
- [ ] Queue table migrated
- [ ] Mail FROM address configured
- [ ] Test emails sent successfully
- [ ] Logs monitored for errors

---

## Quick Start Commands

```bash
# 1. Configure mail in .env
# 2. Create queue table
php artisan queue:table
php artisan migrate

# 3. Start queue worker (development)
php artisan queue:work

# 4. Test manually
php artisan tasks:send-reminders

# 5. Set up cron (production)
crontab -e
# Add: * * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

---

## Email Templates

Reminder emails include:
- Task title
- Project name
- Priority level
- Due date and time
- Current status
- Link to view task
- Reminder type message (7 days, 24 hours, or 12 hours)

Overdue emails include:
- Task title
- Project name
- Priority level
- Due date
- Days overdue
- Current status
- Link to view task

---

## Monitoring

**Check queue status:**
```bash
php artisan queue:monitor
```

**View failed jobs:**
```bash
php artisan queue:failed
```

**Retry failed jobs:**
```bash
php artisan queue:retry all
```

**Clear failed jobs:**
```bash
php artisan queue:flush
```

---

## Notes

- Reminders are queued (processed asynchronously)
- Emails are sent to the user's email address from the User model
- Only incomplete tasks receive reminders
- The command runs once daily at 10:00 AM
- Weekend adjustments ensure reminders are sent on working days only

