# ============================================================
# Dockerfile - Frontend Arludent (Vue 3 + Vite + Nginx)
# ============================================================

# --- Etapa 1: Build ---
FROM node:22-alpine AS builder

WORKDIR /app

# Copiar archivos de dependencias
COPY package.json package-lock.json* ./

# Instalar dependencias
RUN npm ci

# Copiar código fuente
COPY . .

# Variables de entorno para el build
# (se inyectan en build-time porque Vite las embebe en el bundle)
ARG VITE_API_URL=http://localhost:8080/api
ARG VITE_APP_NAME="Arludent"
ARG VITE_RECAPTCHA_SITE_KEY
ARG VITE_AI_MICROSERVICE_URL=http://localhost:8001/api/v1

ENV VITE_API_URL=${VITE_API_URL}
ENV VITE_APP_NAME=${VITE_APP_NAME}
ENV VITE_RECAPTCHA_SITE_KEY=${VITE_RECAPTCHA_SITE_KEY}
ENV VITE_AI_MICROSERVICE_URL=${VITE_AI_MICROSERVICE_URL}

# Eliminar archivo .env local para que Vite tome las variables del SO (inyectadas arriba)
RUN rm -f .env

# Build de producción
RUN npm run build-only

# --- Etapa 2: Servir con Nginx ---
FROM nginx:alpine AS production

# Copiar build de la etapa anterior
COPY --from=builder /app/dist /usr/share/nginx/html

# Copiar configuración de Nginx para SPA
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf

# Exponer puerto
EXPOSE 80

# Health check
HEALTHCHECK --interval=30s --timeout=3s \
    CMD wget --no-verbose --tries=1 --spider http://localhost/ || exit 1

CMD ["nginx", "-g", "daemon off;"]
