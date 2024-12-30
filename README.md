# 🎫 Bilet Satış Sistemi

Bu proje, çekiliş biletlerinin satışını ve yönetimini sağlayan kapsamlı bir web uygulamasıdır. Modern ve kullanıcı dostu arayüzü ile bilet satışlarını kolayca yönetebilir, ödemeleri takip edebilir ve müşterilerinizle otomatik iletişim kurabilirsiniz.

## 📋 İçerik

- [Özellikler](#özellikler)
- [Sistem Gereksinimleri](#sistem-gereksinimleri)
- [Kurulum](#kurulum)
- [Yapılandırma](#yapılandırma)
- [Veritabanı](#veritabanı)
- [E-posta Sistemi](#e-posta-sistemi)
- [Yönetici Paneli](#yönetici-paneli)
- [Güvenlik](#güvenlik)
- [Sorun Giderme](#sorun-giderme)
- [Sık Sorulan Sorular](#sık-sorulan-sorular)

## ✨ Özellikler

### 🎯 Çekiliş Yönetimi
- Sınırsız çekiliş oluşturma
- Her çekiliş için özel bilet numaraları
- Çekiliş durumu takibi (aktif/pasif)
- Çekiliş tarihi ve saat yönetimi

### 🎟️ Bilet İşlemleri
- Otomatik bilet numarası oluşturma
- Bilet durumu takibi (müsait/satıldı/beklemede)
- Toplu bilet oluşturma
- Bilet iptal ve iade yönetimi

### 💳 Ödeme Sistemi
- Güvenli ödeme takibi
- Benzersiz ödeme kodları
- 10 dakikalık ödeme süresi
- Otomatik bilet aktivasyonu

### 📧 Bildirim Sistemi
- Otomatik e-posta bildirimleri
- Özelleştirilebilir e-posta şablonları
- Bilet ve ödeme bilgilerinin otomatik gönderimi
- SMTP entegrasyonu

## 🔧 Sistem Gereksinimleri

### Sunucu Gereksinimleri
- PHP 7.4 veya üzeri
- MySQL 5.7 veya üzeri
- Apache/Nginx web sunucusu
- mod_rewrite modülü aktif
- SSL sertifikası (önerilen)

### PHP Eklentileri
- PDO PHP Extension
- OpenSSL PHP Extension
- Mbstring PHP Extension
- Tokenizer PHP Extension
- XML PHP Extension
- Ctype PHP Extension
- JSON PHP Extension

### Yazılım Gereksinimleri
- Composer (Bağımlılık yönetimi için)
- Git (Versiyon kontrolü için)
- SMTP sunucusu (E-posta gönderimi için)

## 🚀 Kurulum

### 1. Projeyi İndirme
```bash
# Projeyi klonlayın
git clone https://github.com/kullaniciadi/bilet-sistemi.git

# Proje dizinine girin
cd bilet-sistemi
```

### 2. Bağımlılıkları Yükleme
```bash
# Composer bağımlılıklarını yükleyin
composer install

# PHPMailer'ı yükleyin
composer require phpmailer/phpmailer
```

### 3. Ortam Yapılandırması
```bash
# .env dosyasını oluşturun
cp .env.example .env

# .env dosyasını düzenleyin
nano .env
```

### 4. Veritabanı Kurulumu
```bash
# MySQL'e bağlanın
mysql -u kullanici -p

# Veritabanını oluşturun
CREATE DATABASE bilet_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# SQL dosyasını içe aktarın
mysql -u kullanici -p bilet_db < sql/database.sql
```

### 5. Dosya İzinleri
```bash
# Dosya izinlerini ayarlayın
chmod 755 -R *
chmod 777 -R storage/
chmod 777 -R error.log
```

## ⚙️ Yapılandırma

### .env Dosyası Ayarları
```env
# Veritabanı Ayarları
DB_HOST=localhost
DB_NAME=bilet_db
DB_USER=kullanici
DB_PASS=sifre

# SMTP Ayarları
SMTP_HOST=smtp.example.com
SMTP_PORT=465
SMTP_USER=user@example.com
SMTP_PASS=password
```

### E-posta Sistemi (PHPMailer)

PHPMailer yapılandırması `admin/approve_payment.php` dosyasında bulunmaktadır. Temel ayarlar:

```php
$mail = new PHPMailer(true);
$mail->isSMTP();
$mail->Host = $env['SMTP_HOST'];
$mail->SMTPAuth = true;
$mail->Username = $env['SMTP_USER'];
$mail->Password = $env['SMTP_PASS'];
$mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
$mail->Port = $env['SMTP_PORT'];
$mail->CharSet = 'UTF-8';
```

## 👨‍💼 Yönetici Paneli

### Erişim Bilgileri
- URL: `https://siteadi.com/admin`
- Varsayılan Kullanıcı: `admin`
- Varsayılan Şifre: `admin123`

### Temel İşlemler
1. Çekiliş Yönetimi
   - Yeni çekiliş oluşturma
   - Mevcut çekilişleri düzenleme
   - Çekiliş durumunu değiştirme

2. Bilet İşlemleri
   - Bilet satışlarını görüntüleme
   - Bilet durumlarını güncelleme
   - Satış raporları alma

3. Ödeme Takibi
   - Bekleyen ödemeleri görüntüleme
   - Ödemeleri onaylama/reddetme
   - Ödeme geçmişini inceleme

## 🔒 Güvenlik

### Önemli Güvenlik Önlemleri
1. `.env` dosyasını public dizinin dışında tutun
2. Varsayılan yönetici şifresini hemen değiştirin
3. SSL sertifikası kullanın
4. Düzenli güvenlik güncellemeleri yapın
5. Veritabanı yedeklerini alın

### Güvenlik Kontrol Listesi
- [ ] `.env` dosyası güvende
- [ ] Yönetici şifresi değiştirildi
- [ ] SSL sertifikası aktif
- [ ] Dosya izinleri doğru ayarlandı
- [ ] Hata günlükleri kontrol ediliyor

## 🔍 Sorun Giderme

### Sık Karşılaşılan Sorunlar

1. SMTP Hataları
```bash
# SMTP bağlantısını test edin
php tests/smtp_test.php
```

2. Veritabanı Bağlantı Sorunları
```bash
# Veritabanı bağlantısını test edin
php tests/db_test.php
```

3. Dosya İzinleri
```bash
# İzinleri kontrol edin
ls -la
```

### Hata Günlükleri
```bash
# Son hataları görüntüleyin
tail -f error.log
```

## 📘 Sık Sorulan Sorular

1. **Bilet numaraları nasıl oluşturuluyor?**
   - Benzersiz ve sıralı bilet numaraları otomatik oluşturulur

2. **Ödeme süresi nasıl ayarlanır?**
   - `.env` dosyasından `PAYMENT_TIMEOUT` değerini değiştirin

3. **E-posta şablonları nasıl özelleştirilir?**
   - `admin/approve_payment.php` dosyasındaki HTML şablonunu düzenleyin

## 📞 Destek

Sorunlarınız için:
1. GitHub Issues bölümünü kullanın
2. Dokümantasyonu kontrol edin
3. E-posta ile iletişime geçin

## 📄 Lisans

Bu proje MIT lisansı altında lisanslanmıştır. Detaylar için `LICENSE` dosyasına bakın. 
