# Bilet Satış Sistemi

Bu proje, çekiliş biletlerinin satışını ve yönetimini sağlayan bir web uygulamasıdır. Kullanıcılar bilet satın alabilir, ödemelerini yapabilir ve bilet bilgilerini e-posta ile alabilirler. Ayrıca, yöneticiler çekilişleri ve bilet satışlarını yönetebilirler.

## Özellikler

- Çekiliş oluşturma ve yönetme
- Bilet satın alma sistemi
- Otomatik ödeme takibi
- E-posta bildirimleri
- Yönetici paneli
- Bilet durumu takibi
- Ödeme onaylama sistemi

## Gereksinimler

- PHP 7.4 veya üzeri
- MySQL 5.7 veya üzeri
- Composer
- SMTP sunucusu

## Kurulum

1. Projeyi klonlayın:
```bash
git clone https://github.com/kullaniciadi/bilet-sistemi.git
cd bilet-sistemi
```

2. Composer bağımlılıklarını yükleyin:
```bash
composer install
```

3. `.env.example` dosyasını `.env` olarak kopyalayın:
```bash
cp .env.example .env
```

4. `.env` dosyasını düzenleyin:
- Veritabanı bilgilerinizi girin
- SMTP ayarlarınızı yapılandırın
- Uygulama URL'sini belirleyin

5. Veritabanını oluşturun:
- `sql/database.sql` dosyasındaki SQL komutlarını veritabanınızda çalıştırın

6. Dosya izinlerini ayarlayın:
```bash
chmod 755 -R *
chmod 777 -R error.log
```

## Yönetici Paneli

- URL: `{APP_URL}/admin`
- Varsayılan kullanıcı adı: `admin`
- Varsayılan şifre: `admin123`

İlk girişten sonra şifrenizi değiştirmeyi unutmayın!

## Güvenlik Önlemleri

1. `.env` dosyasını güvenli bir şekilde saklayın
2. Varsayılan yönetici şifresini değiştirin
3. Düzenli olarak yedek alın
4. Hata günlüklerini kontrol edin

## Özelleştirme

### E-posta Şablonları

E-posta şablonları `admin/approve_payment.php` dosyasında bulunmaktadır. HTML formatını ve içeriği ihtiyaçlarınıza göre düzenleyebilirsiniz.

### Tasarım

CSS stilleri her sayfanın başında bulunmaktadır. İhtiyaçlarınıza göre özelleştirebilirsiniz.

## Sorun Giderme

1. Hata günlüklerini kontrol edin:
```bash
tail -f error.log
```

2. Veritabanı bağlantısını test edin:
```php
php -r "include '.env'; $pdo = new PDO(...);"
```

3. SMTP ayarlarını test edin:
```php
php -r "include 'test_smtp.php';"
```

## Lisans

Bu proje MIT lisansı altında lisanslanmıştır. Detaylar için `LICENSE` dosyasına bakın.

## Destek

Sorunlar için Issues bölümünü kullanabilir veya doğrudan iletişime geçebilirsiniz. 