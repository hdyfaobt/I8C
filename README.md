# I8C

Webapplicatie voor bestel- en klantenbeheer, gebouwd met Laravel. De app centraliseert klanten, producten en bestellingen, begeleidt elke bestelling doorheen een volledige workflow (aanmaak → review → picking → levering → betaling) en synchroniseert automatisch met Salesforce als CRM via een RabbitMQ-wachtrij.

## Inhoud

- [Functionaliteiten](#functionaliteiten)
- [Rollen en rechten](#rollen-en-rechten)
- [Technische stack](#technische-stack)
- [Architectuur](#architectuur)
- [Projectstructuur](#projectstructuur)
- [Installatie](#installatie)
- [Omgevingsvariabelen](#omgevingsvariabelen)
- [Ontwikkelserver](#ontwikkelserver)
- [Testen](#testen)
- [Standaardaccount](#standaardaccount)

## Functionaliteiten

- **Klantenbeheer** — klanten aanmaken, zoeken, bewerken en hun bestelhistoriek raadplegen.
- **Productcatalogus** — producten beheren en de verkoophistoriek per product bekijken.
- **Bestellingen** — een volledige levenscyclus: aangemaakt → geaccepteerd/geweigerd → in behandeling (picking) → klaar voor afhaling → geleverd → betaald.
- **Picking-workflow** — items afzonderlijk picken, als niet-op-voorraad markeren, een pickingopmerking toevoegen en de bestelling klaarmelden.
- **Terugbetalingen** — betaalde bestellingen die geweigerd of mislukt zijn, komen automatisch op de terugbetalingslijst.
- **Schuldenbeheer** — bestellingen die geleverd maar niet betaald zijn, worden opgevolgd als openstaande schuld per klant.
- **Salesforce-synchronisatie** — elke bestelling en klant wordt asynchroon gesynchroniseerd naar Salesforce (Account + Opportunity).
- **Gebruikers- en rolbeheer** — admins en managers beheren gebruikersaccounts en hun rol.

## Rollen en rechten

Toegangscontrole wordt afgedwongen via [spatie/laravel-permission](https://spatie.be/docs/laravel-permission), met middleware op elke route.

| Rol | Rechten |
|---|---|
| **admin** | Volledige controle: gebruikers aanmaken/bewerken/verwijderen, bestellingen en klanten definitief verwijderen. |
| **manager** | Zoals admin, behalve gebruikers aanmaken en definitief verwijderen. |
| **receptionist** (onthaal) | Klanten en bestellingen aanmaken, bestellingen accepteren/weigeren, betalingen en terugbetalingen beheren, productcatalogus beheren. |
| **orderpicker** (magazijnier) | Bestellingen bekijken, items picken, niet-op-voorraad melden, pickingopmerkingen toevoegen, bestelling klaarmelden. |

## Technische stack

- **Backend:** PHP 8.4, Laravel 13
- **Frontend:** Blade, Alpine.js, Tailwind CSS, Vite
- **Database:** SQLite (standaard, configureerbaar via `.env`)
- **Wachtrij:** RabbitMQ (`php-amqplib`)
- **CRM-integratie:** Salesforce REST API (OAuth 2.0 Client Credentials flow)
- **Autorisatie:** spatie/laravel-permission
- **Testen:** Pest (feature tests)
- **Codestijl:** Laravel Pint

## Architectuur

De koppeling met Salesforce is bewust **asynchroon** opgebouwd, zodat de gebruiker nooit moet wachten op een externe API:

```
Laravel-app  →  RabbitMQ-wachtrij  →  Consumer  →  Salesforce REST API
(bestelling      (duurzaam bericht,   (verwerkt      (OAuth 2.0,
 aangemaakt)       persistent)         één voor één)   Account + Opportunity)
```

1. Bij het aanmaken van een bestelling publiceert `RabbitMQPublisher` een bericht op de `orders`-wachtrij (`app/Services/RabbitMQPublisher.php`).
2. Het `rabbitmq:consume` Artisan-commando (`app/Console/Commands/ConsumeOrders.php`) luistert continu naar die wachtrij en verwerkt binnenkomende berichten één voor één.
3. `OrderSyncService` (`app/Services/OrderSyncService.php`) roept `SalesforceService` (`app/Services/SalesforceService.php`) aan om de klant als Account en de bestelling als Opportunity in Salesforce te registreren.
4. Als de synchronisatie mislukt, blijft de bestelling op status `failed` staan voor manuele herverwerking — er gaat geen data verloren.

## Projectstructuur

```
app/
├── Console/Commands/       # Artisan-commando's (o.a. rabbitmq:consume)
├── Http/Controllers/
│   ├── Admin/              # Gebruikersbeheer
│   └── Userzone/           # Klanten, producten, bestellingen, refunds, schulden
├── Models/                 # Customer, Order, OrderItem, Product, User
├── Services/                # SalesforceService, RabbitMQPublisher, OrderSyncService
└── View/Components/        # Blade-componenten (layouts)

config/
├── rabbitmq.php            # RabbitMQ-configuratie
└── salesforce.php          # Salesforce OAuth-configuratie

database/
├── migrations/              # Volledige schema-historiek
└── seeders/                 # Rollen, admin-account, demo-producten/bestellingen

routes/
├── web.php                  # Applicatieroutes, gegroepeerd per rol
└── auth.php                 # Authenticatieroutes (Breeze)

tests/Feature/               # Pest feature tests per domein
```

## Installatie

Vereisten: PHP 8.4+, Composer, en een lokale RabbitMQ-server (optioneel voor basiswerking, vereist voor Salesforce-sync).

```bash
git clone https://github.com/hdyfaobt/I8C.git
cd I8C

composer setup
```

Het `composer setup`-script (zie `composer.json`) doet automatisch:

1. `composer install`
2. `.env` aanmaken vanuit `.env.example` (indien nog niet aanwezig)
3. `php artisan key:generate`
4. `php artisan migrate --force`

Vul daarna de database met basisdata (rollen, standaard admin-account, productcatalogus):

```bash
php artisan db:seed
```

## Omgevingsvariabelen

Naast de standaard Laravel-variabelen in `.env`, gebruikt dit project:

| Variabele | Beschrijving | Standaardwaarde |
|---|---|---|
| `RABBITMQ_HOST` | Hostnaam van de RabbitMQ-server | `localhost` |
| `RABBITMQ_PORT` | Poort | `5672` |
| `RABBITMQ_USER` | Gebruikersnaam | `guest` |
| `RABBITMQ_PASSWORD` | Wachtwoord | `guest` |
| `RABBITMQ_VHOST` | Virtuele host | `/` |
| `RABBITMQ_QUEUE` | Naam van de wachtrij | `orders` |
| `RABBITMQ_SSL` | SSL gebruiken (bv. voor CloudAMQP) | `false` |
| `SALESFORCE_CLIENT_ID` | Client ID van de Salesforce External Client App | — |
| `SALESFORCE_CLIENT_SECRET` | Client Secret | — |
| `SALESFORCE_LOGIN_URL` | `login.salesforce.com` (productie) of `test.salesforce.com` (sandbox) | `https://login.salesforce.com` |
| `SALESFORCE_API_VERSION` | Versie van de Salesforce REST API | `62.0` |

Voor de Salesforce-koppeling: maak in Salesforce een **External Client App** aan met OAuth-scope *"Access and manage your data (api)"*, activeer de **Client Credentials Flow** en stel een *Run As*-gebruiker in (zie commentaar in `config/salesforce.php`).

## Ontwikkelserver

```bash
composer dev
```

Dit start gelijktijdig, via `concurrently`:

- `php artisan serve` — de webserver
- `php artisan queue:listen` — de standaard Laravel-jobqueue
- `php artisan pail` — live logs
- `php artisan rabbitmq:consume` — de RabbitMQ-consumer voor Salesforce-sync

## Testen

```bash
composer test
```

Dit wist eerst de configuratiecache en draait vervolgens de volledige Pest-testsuite (`tests/Feature/`), met o.a. tests voor authenticatie, bestellingen, de picking-workflow, terugbetalingen, schulden, gebruikersbeheer en de Salesforce-synchronisatie.

## Standaardaccount

Na `php artisan db:seed` is er een standaard admin-account beschikbaar:

- **E-mail:** `admin@be`
- **Wachtwoord:** `password`

> Wijzig dit wachtwoord voordat de applicatie in een gedeelde of publieke omgeving draait.
