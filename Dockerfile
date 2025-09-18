#此為一個環境安裝包

# 使用官方 PHP + Apache
FROM php:8.1-apache

# 安裝 mysqli (MySQL 連線用)
RUN docker-php-ext-install mysqli

# 複製專案檔案到 Apache 根目錄
COPY . /var/www/html/

# 設定工作目錄
WORKDIR /var/www/html/

# 開放 80 port
EXPOSE 80

# 啟動 Apache
CMD ["apache2-foreground"]

