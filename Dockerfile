# استخدم صورة PHP مع Apache
FROM php:8.1-apache

# نسخ ملفات المشروع
COPY . /var/www/html/

# نسخ سكربت التهيئة (entrypoint)
COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# اجعل Entrypoint مخصص
ENTRYPOINT ["/entrypoint.sh"]
