# PadelHub

Aplikacja do prowadzenia turniejów padlowych w formatach Americano i Mexicano.

## Struktura

```
backend/      Symfony 7.4 (REST API)
frontend/     React + Vite
compose.yaml  MariaDB, backend, worker Messengera, frontend, Mailpit
```

## Uruchomienie

1. Skopiuj `.env.example` do `.env` i uzupełnij hasła do bazy.
2. Utwórz `backend/.env.local`:

   ```
   APP_SECRET=<losowy ciąg>
   JWT_PASSPHRASE=<losowy ciąg>
   DATABASE_URL="mysql://padelhub:<DB_PASSWORD>@127.0.0.1:3306/padelhub?serverVersion=11.4.0-MariaDB&charset=utf8mb4"
   ```

   `DATABASE_URL` jest potrzebny tylko przy uruchamianiu konsoli poza Dockerem.

3. Zainstaluj zależności, wygeneruj klucze JWT i uruchom kontenery:

   ```bash
   cd backend && composer install && php bin/console lexik:jwt:generate-keypair && cd ..
   docker compose up -d --build
   docker compose exec backend php bin/console doctrine:migrations:migrate --no-interaction
   ```

   Przy pierwszym starcie MariaDB tworzy też bazę testową `padelhub_test` (skrypt `docker/mariadb/create-test-database.sh`).

| Usługa | Adres |
|---|---|
| Frontend | http://localhost:5173 |
| API | http://localhost:8000/api |
| Swagger UI | http://localhost:8000/api/doc |
| Mailpit (podgląd e-maili) | http://localhost:8025 |

## Testy

Testy uruchamiamy w kontenerze, bo testy integracyjne korzystają z bazy `padelhub_test`:

```bash
docker compose exec backend php bin/console doctrine:migrations:migrate --env=test --no-interaction
docker compose exec backend php bin/phpunit
```

Sekrety (`APP_SECRET`, `JWT_PASSPHRASE`, hasła) są tylko w `.env` i `backend/.env.local`, które nie trafiają do repozytorium.
