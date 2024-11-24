
# Laravel with Nginx, MySQL, and Adminer using Docker

## Prerequisites
- Docker & Docker Compose installed on your system.

## Getting Started

1. **Clone the Repository**  
   Clone your Laravel project to your local machine:
   ```bash
   git clone <repository-url>
   cd <project-folder>
   ```

2. **Setup Environment Variables**  
   Create a `.env` file in the Laravel project root. Use `.env.example` as a template:
   ```bash
   cp .env.example .env
   ```
   Update database-related fields:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=mysql
   DB_PORT=3306
   DB_DATABASE=<your_database_name>
   DB_USERNAME=<your_database_username>
   DB_PASSWORD=<your_password>
   ```

3. **Build and Start the Docker Containers**  
   Run the following command to build and start the containers:
   ```bash
   docker-compose up -d --build
   ```

4. **Install Laravel Dependencies**  
   Access the PHP container:
   ```bash
   docker exec -it laravel_app bash
   ```
   Inside the container, run:
   ```bash
   composer install
   php artisan key:generate
   php artisan migrate
   ```

5. **Access the Application**
   - **Laravel Application:** [http://localhost](http://localhost)
   - **Adminer (Database Management):** [http://localhost:8080](http://localhost:8080)  
     Use the following credentials:
     - **Server:** `mysql`
     - **Username:** `root`
     - **Password:** `your_password`
     - **Database:** `chat_app`

6. **Stop the Containers**  
   To stop the containers:
   ```bash
   docker-compose down
   ```

## Project Structure
- `Dockerfile` – Defines the PHP environment.
- `docker-compose.yml` – Manages the multi-container setup.
- `nginx/default.conf` – Nginx configuration file.
- `src` – Laravel application source code.

## Notes
- Ensure the MySQL container service name (`mysql`) matches the `DB_HOST` in `.env`.
- Rebuild the containers after modifying the `Dockerfile` or `docker-compose.yml`:
  ```bash
  docker-compose up -d --build
  ```
