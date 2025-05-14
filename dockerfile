FROM php:8.2-apache

# Variáveis de ambiente para evitar prompts interativos durante a instalação
ENV DEBIAN_FRONTEND=noninteractive

# Instala as dependências do sistema necessárias e o Composer
RUN apt-get update && apt-get install -y \
    apt-utils \
    git \
    libzip-dev \
    zip \
    unzip \
    libicu-dev \
    libpq-dev \
    && rm -rf /var/lib/apt/lists/*

# Instala o Composer globalmente
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Instala as extensões PHP necessárias
RUN docker-php-ext-install -j$(nproc) intl opcache pdo pdo_pgsql pgsql zip

# Habilita o mod_rewrite do Apache
RUN a2enmod rewrite

# Define o diretório de trabalho dentro do container
WORKDIR /var/www/html

# Copia primeiro os arquivos do composer para aproveitar o cache do Docker
COPY composer.json composer.lock ./

# Instala as dependências do projeto com o Composer
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Copia o restante dos arquivos do seu projeto para o container
COPY . .

# Define as permissões de escrita para os diretórios necessários do CodeIgniter
# O diretório 'writable' é padrão do CI4 e precisa de permissões.
# Se você usa 'public/uploads' para uploads, crie-o e defina permissões.
# Os diretórios 'storage' e 'logs' DENTRO de 'writable' herdarão permissões.
RUN mkdir -p public/uploads && \
    chown -R www-data:www-data writable public/uploads && \
    chmod -R 775 writable public/uploads

# Expõe a porta 80
EXPOSE 8080

# Comando para iniciar o servidor de desenvolvimento do CodeIgniter
CMD ["php", "spark", "serve", "--host", "0.0.0.0", "--port", "8080"]