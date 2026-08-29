<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>


# 🎫 TicketFlow

**TicketFlow** is an open-source, modular support ticket management system built with **Laravel 12** and designed around **Clean Architecture, separation of responsibilities, RESTful APIs, and real-world backend engineering practices**.

The project goes beyond a basic CRUD ticket system by implementing authentication, role-based access control, ticket assignment, conversations, notifications, filtering, activity logging, SLA-based escalation, automated background jobs, and a rule-driven decision layer.

The main goal of TicketFlow is not only to provide a working ticketing system, but also to demonstrate how a maintainable and scalable backend can be designed using Laravel.

[![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php\&logoColor=white)](https://www.php.net/)
[![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20?logo=laravel\&logoColor=white)](https://laravel.com/)
[![Sanctum](https://img.shields.io/badge/Auth-Laravel%20Sanctum-FF2D20?logo=laravel\&logoColor=white)](https://laravel.com/docs/sanctum)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

---

## ✨ Overview

TicketFlow is designed to model a real-world customer support environment where users can create tickets, support agents can handle conversations, tickets can be assigned and reassigned, and operational rules can automatically intervene when response deadlines are exceeded.

Instead of putting all business logic inside controllers, the application separates responsibilities into dedicated layers and components.

This makes the codebase easier to:

* understand
* test
* extend
* refactor
* maintain
* develop collaboratively

---

## 🚀 Features

### 🔐 Authentication & Authorization

* User registration and authentication
* OTP-based verification flow
* Token authentication using Laravel Sanctum
* Role-based access control
* Permission-based authorization
* Ticket policies
* Protected API endpoints

---

### 🎫 Ticket Management

* Create tickets
* View tickets
* Update tickets
* Delete tickets
* Assign tickets to support agents
* Reassign tickets
* Close tickets
* Manage ticket priorities
* Manage ticket statuses
* Associate tickets with user categories
* Track ticket ownership

Ticket status and priority are modeled independently instead of being hard-coded directly into the ticket entity.

---

### 💬 Conversations

Tickets support conversation-based communication between users and support agents.

The conversation layer allows the system to keep communication attached to the ticket lifecycle rather than treating a ticket as a single static record.

---

### 👤 User & Expert Management

TicketFlow supports different user responsibilities through roles, permissions, and ticket assignment.

Experts can be selected based on business rules such as:

* matching ticket category
* current workload
* number of open tickets
* assignment eligibility

This allows ticket assignment to become a business decision instead of simply selecting a random user.

---

### 🔎 Ticket Filtering

TicketFlow provides a dedicated filtering layer for querying tickets.

Tickets can be filtered using criteria such as:

* status
* priority
* subject
* user category
* assigned expert
* date range

Filters are separated from the core ticket logic so additional filtering rules can be introduced without turning controllers or services into large query classes.

---

### 🔔 Notifications

The project includes a notification layer for handling application notifications.

Notification logic is separated from the main ticket workflow so notification behavior can evolve independently from ticket business logic.

---

### 📋 Activity Logging

Important system actions can be recorded through the activity layer.

Examples include:

* ticket assignment
* ticket reassignment
* important ticket state changes
* automated escalation actions

This provides an audit trail for operationally important events.

---

## ⏱️ SLA & Automatic Ticket Escalation

One of the more important features of TicketFlow is its automated escalation mechanism.

A ticket can be considered for escalation when:

1. The ticket is still open.
2. The allowed response time has been exceeded.
3. The ticket meets the required priority/SLA conditions.

When escalation is triggered, TicketFlow evaluates eligible experts.

The assignment process considers factors such as:

* ticket category
* expert category
* number of currently open tickets
* assignment eligibility

If eligible experts are available, the ticket can be reassigned and the action is recorded in the activity log.

### Example flow

```text
Open Ticket
     │
     ▼
Check SLA
     │
     ├── Within allowed time ──► Keep current assignment
     │
     ▼
Response time exceeded
     │
     ▼
Find eligible experts
     │
     ├── No eligible expert ──► Keep current assignment
     │
     ▼
Select eligible expert
     │
     ▼
Reassign ticket
     │
     ▼
Create activity log
```

The escalation process is designed as a background operation rather than something that depends on a user manually opening a dashboard.

---

## 🧠 Rule-Driven Decision Layer

TicketFlow integrates **MilliRulePilot** as a rule-based decision layer.

This allows business decisions to be separated from the surrounding application workflow.

Instead of embedding complex conditions directly inside controllers or large service methods, decision logic can be represented as independent rules and evaluated through the rule engine.

This approach is useful for scenarios such as:

* SLA decisions
* ticket escalation
* assignment rules
* priority-based behavior
* future business decision workflows

The project currently uses:

```text
milirezai/milirulepilot
```

This separation allows the application to evolve its business rules without tightly coupling those rules to HTTP or database code.

---

## 🏗️ Architecture

TicketFlow follows a modular, layered architecture inspired by **Clean Architecture** principles.

The goal is to keep responsibilities separated and prevent business logic from becoming tightly coupled to controllers, HTTP requests, or framework-specific concerns.

### High-level structure

```text
                ┌─────────────────────┐
                │      HTTP / API     │
                │ Controllers/Routes  │
                └──────────┬──────────┘
                           │
                           ▼
                ┌─────────────────────┐
                │      Services       │
                │ Application Logic   │
                └──────────┬──────────┘
                           │
                           ▼
                ┌─────────────────────┐
                │ Decisions / Rules   │
                │ Business Decisions  │
                └──────────┬──────────┘
                           │
                           ▼
                ┌─────────────────────┐
                │ Models / Persistence│
                │      Database       │
                └─────────────────────┘
```

The application also contains dedicated areas for filters, jobs, notifications, policies, events, listeners, and providers.

---

## 📁 Project Structure

```text
app/
├── Decisions/
├── Events/
│   └── Activity/
├── Filters/
├── Http/
├── Jobs/
├── Listeners/
├── Models/
├── Notifications/
├── Policies/
│   └── Ticket/
├── Providers/
├── Rules/
└── Services/

database/
├── factories/
├── migrations/
└── seeders/

routes/
├── Api/
│   └── V1/
├── api.php
├── console.php
└── web.php

tests/
```

The application structure intentionally separates responsibilities rather than grouping everything into a large controller/service/model layer.

---

## 🧩 Main Architectural Components

### Controllers

Controllers are responsible for handling HTTP concerns and coordinating application operations.

They should not become the place where the application's entire business logic lives.

---

### Services

Services encapsulate application-level operations that may involve multiple models, business rules, or workflows.

Examples include ticket operations, assignment logic, and other reusable application workflows.

---

### Rules

Rules contain reusable business conditions.

This allows business logic to be expressed independently from HTTP and persistence concerns.

---

### Decisions

The decision layer is used for higher-level business decisions where multiple rules may need to be evaluated together.

This is especially useful for rule-driven workflows such as escalation and assignment.

---

### Filters

Filtering logic is isolated from controllers so complex ticket queries remain maintainable.

Instead of creating increasingly large controller methods, individual filters can be composed as needed.

---

### Policies

Policies handle authorization decisions around protected resources such as tickets.

This keeps authorization rules out of controllers and makes them easier to test and maintain.

---

### Jobs

Background jobs are used for operations that should not depend on a user's HTTP request.

The ticket escalation workflow is one example of a background operation.

---

### Events & Listeners

Events and listeners provide a way to react to important system actions without tightly coupling the original operation to every side effect.

---

## 🗄️ Data Model

The system separates several concepts into dedicated database entities instead of placing everything directly inside the ticket table.

The project includes concepts such as:

```text
User
 │
 ├── Roles
 │    └── Permissions
 │
 └── Tickets
       │
       ├── Status
       ├── Priority
       ├── Category
       ├── Expert
       ├── Conversations
       └── Activity Logs
```

This separation allows ticket state and business configuration to evolve independently.

---

## 🔍 API

TicketFlow exposes RESTful API endpoints organized under an API version structure.

```text
routes/
└── Api/
    └── V1/
```

The API layer is protected using Laravel Sanctum where authentication is required.

API documentation is supported through **L5-Swagger / OpenAPI**.

---

## 📚 API Documentation

The project uses:

```text
darkaonline/l5-swagger
```

for API documentation generation.

After installing and configuring the project, Swagger documentation can be generated through the Laravel application.

---

## ⚙️ Requirements

Before running TicketFlow, make sure the environment has:

* PHP 8.2+
* Composer
* Laravel 12
* MySQL or another supported relational database
* Node.js
* npm
* Git

---

## 📦 Installation

### 1. Clone the repository

```bash
git clone https://github.com/milirezai/ticketFlow.git

cd ticketFlow
```

### 2. Install PHP dependencies

```bash
composer install
```

### 3. Create the environment file

```bash
cp .env.example .env
```

### 4. Generate the application key

```bash
php artisan key:generate
```

### 5. Configure the database

Update your `.env` file:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ticketflow
DB_USERNAME=root
DB_PASSWORD=
```

### 6. Run migrations

```bash
php artisan migrate
```

### 7. Install frontend dependencies

```bash
npm install
```

### 8. Build frontend assets

```bash
npm run build
```

### 9. Start the application

```bash
php artisan serve
```

---

## 🧪 Testing

TicketFlow uses PHPUnit through Laravel's testing infrastructure.

Run the test suite with:

```bash
php artisan test
```

Or:

```bash
composer test
```

Tests are located inside:

```text
tests/
```

Testing is an important part of the project because business rules such as ticket assignment, authorization, filtering, and escalation can become complicated quickly.

---

## 🛠️ Development

For local development, the project provides a Composer development script that can run the application server, queue worker, logs, and Vite development server together.

```bash
composer run dev
```

This makes the development workflow easier by starting the required processes from one command.

---

## 🔄 Background Processing

TicketFlow uses Laravel's queue and job infrastructure for background processing.

The application can run a queue worker with:

```bash
php artisan queue:listen
```

Background processing is especially useful for automated workflows such as escalation and other operations that should not block an HTTP request.

---

## ⏰ Scheduled Escalation

Ticket escalation is designed to run automatically through Laravel's scheduler.

The scheduler periodically evaluates tickets and identifies those that require escalation.

A typical production setup can run Laravel's scheduler every minute:

```bash
php artisan schedule:work
```

The escalation job itself can then control how frequently escalation checks are performed.

---

## 🔒 Authorization Model

TicketFlow separates authentication from authorization.

### Authentication

Laravel Sanctum handles API token authentication.

### Authorization

Roles, permissions, and policies are used to determine what an authenticated user is allowed to do.

Conceptually:

```text
Authentication
      │
      ▼
Authenticated User
      │
      ▼
Role / Permission
      │
      ▼
Policy
      │
      ▼
Authorized Action
```

This makes access control explicit rather than relying on scattered checks throughout controllers.

---

## 🧱 Design Principles

The project follows several backend engineering principles:

### Single Responsibility

Each component should have a focused responsibility.

### Separation of Concerns

HTTP, business logic, authorization, persistence, filtering, notifications, and background processing are kept separate.

### Dependency Injection

Dependencies are injected instead of being manually instantiated inside business logic.

### Reusability

Business rules and application services are designed to be reusable.

### Testability

Logic is structured so important behavior can be tested independently.

### Maintainability

The architecture is designed to make future changes less expensive.

---

## 🧑‍💻 Technology Stack

| Technology      | Purpose                    |
| --------------- | -------------------------- |
| PHP 8.2+        | Backend language           |
| Laravel 12      | Application framework      |
| Laravel Sanctum | API authentication         |
| MySQL           | Relational database        |
| PHPUnit         | Automated testing          |
| L5-Swagger      | API documentation          |
| MilliRulePilot  | Rule-based decision engine |
| Composer        | PHP dependency management  |
| Vite / npm      | Frontend asset tooling     |
| Git             | Version control            |

The repository currently declares Laravel 12, PHP 8.2+, Sanctum, L5-Swagger, PHPUnit, and `milirezai/milirulepilot` as project dependencies.

---

## 🎯 Why TicketFlow?

TicketFlow was built as more than a CRUD exercise.

The project focuses on solving backend problems that appear in real systems:

* How should business logic be separated from controllers?
* How should authorization scale?
* How should complex ticket queries remain maintainable?
* How can automated workflows operate without HTTP requests?
* How should SLA violations be detected?
* How can ticket assignment be driven by business rules?
* How can important system actions be audited?
* How can business decisions be separated from infrastructure?

The architecture is intentionally designed around these questions.

---

## 🤝 Contributing

Contributions are welcome.

If you want to improve TicketFlow:

1. Fork the repository.
2. Create a feature branch.
3. Implement your changes.
4. Add or update tests.
5. Commit your changes.
6. Open a pull request.

Example:

```bash
git checkout -b feature/my-feature

git add .

git commit -m "Add my feature"

git push origin feature/my-feature
```

---

## 📄 License

TicketFlow is open-source software licensed under the **MIT License**.

See the [LICENSE](LICENSE) file for more information.

---

## ⭐ Support

If you find the project useful, consider giving it a ⭐ on GitHub.

Every star tells the algorithm that this repository is not merely another lonely folder containing `UserController.php`.

---
