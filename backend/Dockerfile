# Backend (API) do ModaSys — serviço próprio no Cloud Run.
# Build a partir da pasta backend/:  docker build -t modasys-api backend
FROM php:8.3-apache

# pdo_pgsql pra falar com o Supabase (Postgres). libpq é a biblioteca
# cliente do Postgres que a extensão precisa pra compilar e rodar.
RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev \
    && docker-php-ext-install pdo_pgsql \
    && apt-get purge -y --auto-remove \
    && rm -rf /var/lib/apt/lists/*

# Só public/ fica exposto na web; src/ fica fora do DocumentRoot, então
# nenhum arquivo de código pode ser acessado direto pela URL.
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

# - DocumentRoot aponta pra public/ e libera o .htaccess (URLs limpas)
# - Cloud Run diz em qual porta escutar pela variável PORT (padrão 8080)
# - fuso de Brasília também no PHP (o index.php já define, isto é reforço)
# - erros vão pro log do Cloud Run (stderr), nunca pra resposta
RUN a2enmod rewrite headers \
    && sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri 's!AllowOverride None!AllowOverride All!g' /etc/apache2/apache2.conf \
    && sed -ri 's/Listen 80/Listen ${PORT}/' /etc/apache2/ports.conf \
    && sed -ri 's/:80>/:${PORT}>/' /etc/apache2/sites-available/000-default.conf \
    && { echo 'date.timezone=America/Sao_Paulo'; echo 'display_errors=Off'; echo 'log_errors=On'; echo 'error_log=/dev/stderr'; echo 'expose_php=Off'; } \
       > /usr/local/etc/php/conf.d/modasys.ini \
    && echo 'ServerTokens Prod' >> /etc/apache2/apache2.conf

ENV PORT=8080

COPY src/ /var/www/html/src/
COPY public/ /var/www/html/public/

EXPOSE 8080
