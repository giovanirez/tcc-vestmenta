# Front-end do ModaSys — serviço próprio no Cloud Run, separado da API.
# Build a partir da raiz do projeto:  docker build -t modasys-web .
#
# O front não acessa o banco: só serve as páginas (PHP monta o layout)
# e os arquivos estáticos. Todo dado vem da API, cujo endereço é
# passado na variável de ambiente API_URL no deploy.
FROM php:8.3-apache

# Porta do Cloud Run (PORT), fuso de Brasília, erros só no log, e abrir
# o domínio sem caminho cai direto na tela de login (index.php).
RUN sed -ri 's/Listen 80/Listen ${PORT}/' /etc/apache2/ports.conf \
    && sed -ri 's/:80>/:${PORT}>/' /etc/apache2/sites-available/000-default.conf \
    && { echo 'date.timezone=America/Sao_Paulo'; echo 'display_errors=Off'; echo 'log_errors=On'; echo 'error_log=/dev/stderr'; echo 'expose_php=Off'; } \
       > /usr/local/etc/php/conf.d/modasys.ini \
    && echo 'ServerTokens Prod' >> /etc/apache2/apache2.conf \
    && echo 'DirectoryIndex index.php' >> /etc/apache2/apache2.conf

ENV PORT=8080

# O .dockerignore deixa de fora o backend, o .env e os scripts SQL —
# nada disso deve existir no servidor do front.
COPY . /var/www/html/

EXPOSE 8080
