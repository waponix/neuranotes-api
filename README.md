> This repository only contains the source code for the API service, the Client service can be cloned separately from [NeuraNotes](https://github.com/waponix/neuranotes-client)

### Stack Involved:
- **Nginx** - alphine
- **PHP** - v8.2.27
- **Laravel** - v10.48.24
- **MySQL** - v8.0.40
- **Redis** - v7.4.1
- **Ollama** - v0.4.7
- **Docker**

### Setting it up:
I. **Laravel**
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

II. **Redis**

1. Add the following lines inside the `redis/redis.conf` file from the project's root. And replace the `<username>` and `<password>` with your own values. Also don't forget to update the redis configuration inside the `.env` file.
```config
user <username> on ><password> ~* +@all
requirepass <password>
```

III. **Ollama**

1. Install qwen2.5 in ollama for the LLM
```linux
$ docker exec -it ollama ollama pull qwen2.5
```

### Account Creation:
The application currently don't have a user-facing interface to register an account, but you can make a `POST` request to the `/api/register` endpoint to create one.

**Request Body**
```json
{
    "name": "<your_name>",
    "email": "<your_email>",
    "password": "<your_password>"
}
```
