# syntax=docker/dockerfile:1

# ---------------------------------------------------------------------------
# deps: install node modules once for every later stage
# ---------------------------------------------------------------------------
FROM node:24-alpine AS deps

WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund

# ---------------------------------------------------------------------------
# dev: Vite dev server with hot reload (source is bind-mounted)
# ---------------------------------------------------------------------------
FROM deps AS dev

COPY . .
EXPOSE 5173
CMD ["npm", "run", "dev", "--", "--host", "0.0.0.0"]

# ---------------------------------------------------------------------------
# build: type-check and bundle the app
# ---------------------------------------------------------------------------
FROM deps AS build

COPY . .
RUN npm run build

# ---------------------------------------------------------------------------
# prod: nginx serves the bundle and hands /api and /sanctum to PHP-FPM
# ---------------------------------------------------------------------------
FROM nginx:1.29-alpine AS prod

COPY docker/nginx.conf /etc/nginx/conf.d/default.conf
COPY --from=build /app/dist /usr/share/nginx/html

EXPOSE 80
