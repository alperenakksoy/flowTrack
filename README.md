Here is a comprehensive `README.md` file for your project, based on the code you've provided.

-----

# FlowTrack: Employee Performance & Task Tracker

FlowTrack is a comprehensive, full-stack web application built with **Symfony 7** designed to manage employee tasks, weekly goals, and automated performance reviews in a corporate environment. It provides separate, data-rich dashboards for managers and employees, allowing for clear assignment of responsibilities and real-time tracking of progress.

This project uses a server-rendered **Twig** and **Bootstrap** frontend, enhanced with **Symfony UX** (Stimulus, Turbo, and Chart.js) for a dynamic and responsive user experience.

-----

## Core Features

  * **Role-Based Dashboards:**

      * **Manager Dashboard:** Provides a high-level overview of team members, team-wide pending tasks, and average goal progress. Managers can assign new tasks and create goals directly from their dashboard.
      * **Employee Dashboard:** A personal view of assigned open/in-progress tasks, weekly goal progress, and a history of all recent goals.

  * **Task & Goal Management:**

      * Full CRUD functionality for managers to create, assign, and edit tasks with details like priority, due dates, and rich-text descriptions (via CKEditor).
      * Managers can set weekly, measurable goals for their team members (e.g., "Complete 10 tasks", "Achieve 80% sales quota").

  * **Performance Analytics & Reporting:**

      * A robust `ScoringService` automatically calculates weekly, monthly, and overall performance scores for all users.
      * Scores are weighted based on task completion rates, on-time delivery, and successful completion of high-priority tasks.
      * Dynamic performance reports are generated with detailed charts (using Chart.js) to visualize metrics.

  * **PDF Report Generation:**

      * All performance reports (weekly, monthly, overall) can be downloaded as professional PDF documents for offline reviews or HR records. This is powered by `KnpSnappyBundle`.

  * **Email Notifications:**

      * The system automatically sends emails using `Symfony Mailer` for key events:
          * When a new task is assigned.
          * When an assigned task is completed by an employee (notifying the manager).
          * (Future enhancement) When a task's deadline is approaching.

  * **Full Admin Backend:**

      * A complete and secure admin panel built with the **Sonata Admin Bundle** allows administrators to manage all core data models, including Users, Teams, Tasks, Goals, and Performance Reports.

## Tech Stack

| Category | Technology |
| :--- | :--- |
| **Backend** | Symfony 7, PHP 8.3 |
| **Database** | Doctrine ORM, PostgreSQL |
| **Frontend** | Twig, Bootstrap 5, Symfony UX (Stimulus, Turbo, Chart.js) |
| **Admin Panel** | Sonata Admin Bundle |
| **PDF Generation** | `KnpSnappyBundle` (using wkhtmltopdf) |
| **Email** | Symfony Mailer, Mailpit (local testing) |
| **Local Dev** | Docker, Docker Compose |

-----

## 🚀 Getting Started

### Prerequisites

  * PHP 8.3 or higher
  * [Composer](https://getcomposer.org/)
  * [Symfony CLI](https://symfony.com/download)
  * [Docker](https://www.docker.com/get-started/)
  * `wkhtmltopdf` (for PDF generation)
      * Install locally: `brew install wkhtmltopdf` (macOS) or `apt-get install wkhtmltopdf` (Ubuntu)

### Installation

1.  **Clone the repository:**

    ```bash
    git clone https://github.com/your-username/FlowTrack.git
    cd FlowTrack
    ```

2.  **Install backend dependencies:**

    ```bash
    composer install
    ```

3.  **Configure your environment:**

      * Copy the example `.env` file.
        ```bash
        cp .env .env.local
        ```
      * Edit `.env.local` and update the `DATABASE_URL` to match your local setup. The default from `compose.yaml` is:
        ```dotenv
        # .env.local
        DATABASE_URL="postgresql://app:!ChangeMe!@127.0.0.1:5432/app?serverVersion=16&charset=utf8"
        ```

4.  **Start the local services (Postgres & Mailpit):**

    ```bash
    docker-compose up -d
    ```

5.  **Run database migrations:**

    ```bash
    php bin/console doctrine:migrations:migrate
    ```

6.  **Install frontend assets and bundles:**

    ```bash
    php bin/console assets:install public
    php bin/console ckeditor:install
    php bin/console elfinder:install
    ```

7.  **Run the application:**

    ```bash
    symfony server:start
    ```

### Creating Your First Admin User

This project uses Sonata Admin, but does not include data fixtures. You must create an admin user manually.

1.  **Generate a password hash:**
    Run this command and enter a password (e.g., `admin`).

    ```bash
    php bin/console security:hash-password
    ```

    Copy the resulting hash.

2.  **Insert the admin user into the database:**
    Connect to your Postgres database (e.g., using `psql` or a GUI like DBeaver) and run the following SQL. Replace the `password` value with the hash you just generated.

    ```sql
    INSERT INTO "user" 
    (first_name, last_name, email, roles, password, created_at, updated_at) 
    VALUES 
    ('Admin', 'User', 'admin@flowtrack.com', '["ROLE_ADMIN"]', '$2y$13$your-copied-hash-goes-here', NOW(), NOW());
    ```

### Application Access

  * **Main Application:** [http://127.0.0.1:8000/](https://www.google.com/search?q=http://127.0.0.1:8000/)
  * **Login Page:** [http://127.0.0.1:8000/login](https://www.google.com/search?q=http://127.0.0.1:8000/login)
      * **User:** `admin@flowtrack.com`
      * **Pass:** `admin` (or whatever you set in step 2)
  * **Sonata Admin:** [http://127.0.0.1:8000/admin](https://www.google.com/search?q=http://127.0.0.1:8000/admin)
  * **Local Mail Catcher (Mailpit):** [http://127.0.0.1:8025/](https://www.google.com/search?q=http://127.0.0.1:8025/)

### Running Tests

To run the application's PHPUnit test suite:

```bash
php bin/phpunit
```

-----

## License

This project is proprietary.
