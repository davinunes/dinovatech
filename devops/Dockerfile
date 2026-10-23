# Dockerfile - Dinovatech Web Application
# Suporte a PHP 8.2+ com extensões necessárias e compatibilidade com o deploy atual (SSH/Python)
# Preparado para execução em Docker / Portainer e transição para Kubernetes

ARG PHP_VERSION=8.3
FROM php:${PHP_VERSION}-apache

LABEL maintainer="Dinovatech Team"
LABEL description="Container PHP para Dinovatech com suporte a Apache, MySQLi, SOAP, cURL e SSH/Paramiko Deploy"

# 1. Instalação de dependências do sistema operacional
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    curl \
    unzip \
    zip \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libxml2-dev \
    libzip-dev \
    libonig-dev \
    libssl-dev \
    # Dependências para deploy SSH local atual (dinovatech/py/ssh.py)
    # [OBS: Podem ser removidas na futura migração definitiva para Kubernetes]
    openssh-client \
    python3 \
    python3-pip \
    python3-paramiko \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        mysqli \
        pdo_mysql \
        mbstring \
        xml \
        soap \
        gd \
        zip \
        bcmath \
        opcache \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# 2. Habilitação de módulos do Apache
RUN a2enmod rewrite headers ssl

# 3. Configurações recomendadas do PHP (php.ini)
RUN { \
    echo 'memory_limit = 256M'; \
    echo 'upload_max_filesize = 50M'; \
    echo 'post_max_size = 50M'; \
    echo 'max_execution_time = 120'; \
    echo 'date.timezone = America/Sao_Paulo'; \
    echo 'display_errors = Off'; \
    echo 'display_startup_errors = Off'; \
    echo 'log_errors = On'; \
    echo 'error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT'; \
    echo 'session.cookie_httponly = 1'; \
    echo 'expose_php = Off'; \
} > /usr/local/etc/php/conf.d/dinovatech.ini

# 4. Configuração do Apache VirtualHost para permitir .htaccess
RUN { \
    echo '<Directory /var/www/html>'; \
    echo '    Options -Indexes +FollowSymLinks'; \
    echo '    AllowOverride All'; \
    echo '    Require all granted'; \
    echo '</Directory>'; \
} > /etc/apache2/conf-available/dinovatech-security.conf \
    && a2enconf dinovatech-security

WORKDIR /var/www/html

# Exposição da porta 80 do Apache
EXPOSE 80

CMD ["apache2-foreground"]
