# Contact Manager API

This is a Laravel API for managing contacts with image upload and processing using queues.

## 🚀 Requirements

- Docker & Docker Compose
- PHP 8.2+ (inside container)

## 🛠 Installation

```bash
git clone https://github.com/Ozodbek4252/task.git
cd task
cp .env.example .env
docker-compose up -d --build
docker exec -it app php artisan migrate
