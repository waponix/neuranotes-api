### The Stack:
- **Nginx** - alphine
- **PHP** - v8.2.27
- **Laravel** - v10.48.24
- **MySQL** - v8.0.40
- **Redis** - v7.4.1
- **Ollama** - v0.4.7

### Setting it up:
1. Run the containers
```linux
$ docker compose up --build
```
2. Install the dependencies
```linux
$ docker exec -it api_app composer install
```
3. Run Laravel setup commands. But before doing so, populate the `.env` file with the correct values for your setup.
```linux
$ docker exec -it api_app php artisan key:generate
$ docker exec -it api_app php artisan migrate
```
4. Install qwen2.5 in ollama for the LLM
```linux
$ docker exec -it ollama ollama pull qwen2.5
```
