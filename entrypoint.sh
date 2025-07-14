#!/bin/bash
# استخدم البورت اللي Railway يمرره، أو 80 كافتراضي
PORT=${PORT:-80}

# استبدل البورت في إعدادات Apache
sed -i "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

# شغّل Apache في الواجهة الأمامية
apache2-foreground
