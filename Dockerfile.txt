# استخدم صورة PHP مع Apache
FROM php:8.1-apache

# نسخ ملفات المشروع للمسار الرئيسي في الخادم
COPY . /var/www/html/

# فتح منفذ 80
EXPOSE 80
