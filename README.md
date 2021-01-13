[![Maintainability](https://api.codeclimate.com/v1/badges/75815a8684536816ebff/maintainability)](https://codeclimate.com/github/sjeguedes/symfonyBM_API/maintainability)
[![Test Coverage](https://api.codeclimate.com/v1/badges/75815a8684536816ebff/test_coverage)](https://codeclimate.com/github/sjeguedes/symfonyBM_API/test_coverage)
[![Codacy Badge](https://app.codacy.com/project/badge/Grade/34e88a3a8f0649e3b951f8c3aea6a443)](https://www.codacy.com/gh/sjeguedes/symfonyBM_API/dashboard?utm_source=github.com&amp;utm_medium=referral&amp;utm_content=sjeguedes/symfonyBM_API&amp;utm_campaign=Badge_Grade)
# symfonyBM_API

## REST API with Symfony
- This API manages phones (Phone instances) products.  
- These phones are associated to partners (API users). Each partner (Partner instance) has his own clients (customers).  
- Each association between a phone and a partner is represented by an offer (Offer instance).  
- "GET" requests are stored in HTTP cache. Each "GET" request Cache expiration, validation and differentiation is also stored in database (HTTPCache instance).  
- All Doctrine metadata, queries and results (HTTPCache instances results are excluded to avoid complicated side effect) to find instances used in dependency injection are stored in cache with SQLite database thanks to corresponding cache driver.  
- A refresh token (JWTRefreshToken instance) can be used to simplify API JWT Authentication instead of partner credentials.

###### *Please note that this project uses these libraries or Symfony bundles:*
Ramsey uuid library
> https://github.com/ramsey/uuid

Faker PHP library
> https://github.com/FakerPHP/Faker

JMS Serializer bundle
> https://github.com/schmittjoh/JMSSerializerBundle
> https://github.com/schmittjoh/serializer
  
HAL HATEOAS bundle (Richardson Model Level 3)
> https://github.com/willdurand/BazingaHateoasBundle
> https://github.com/willdurand/Hateoas

Lexik JWT Stateless authentication bundle
> https://github.com/lexik/LexikJWTAuthenticationBundle

Gesdinet JWT refresh token bundle (to simplify authentication)
> https://github.com/markitosgv/JWTRefreshTokenBundle

Nelmio OpenAPI documentation bundle
> https://github.com/nelmio/NelmioApiDocBundle

### Local installation (can be used on deployment with ssh access with some adaptations for environment variables)

#### 1. Clone project repository master branch on GitHub with:
```
$ git clone https://github.com/sjeguedes/symfonyBM_API.git
```

#### 2. Configure particular project needed data and your own database parameters with environment variables in `env.local` file using `.env` provided example file:
###### *Prefer use a `env.<your_environment>.local` file per environment combined to `env.local.php` to manage each environment.*
```
# API application environnement and secret
APP_ENV=your_environment # e.g. "dev" or "prod"
APP_SECRET=your_secret

# API Database configuration (example here with MySQL)
DATABASE_URL=mysql://db_user:db_password@127.0.0.1:3306/db_name?serverVersion=5.7

# API JWT Authentication paths configuration to public and private key and also passphrase
JWT_SECRET_KEY=%kernel.project_dir%/your_path_to_private_key/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/your_path_to_private_key/public.pem
JWT_PASSPHRASE=your_custom_passphrase

# API custom domain to make OpenAPI documentation work:
$ API_DOC_SERVER_URL=https://www.your-custom-domain.tld
```
#### 3. Adapt Doctrine "dbal" section configuration (driver and server_version) to your system requirements in `config/packages/doctrine.yaml` file if needed:

#### 4. Install dependencies defined in composer.json:
```
# Development (dev) environment
$ composer install
# Production (prod) environnement
$ composer install --no-dev --no-scripts --optimize-autoloader
```

#### 5. Create database and schema with Doctrine migrations located in `migrations` folder:
```
# Create database
$ php bin/console doctrine:database:create
```
###### *Use migrations instead of updating schema!*
```
# Create schema
$ php bin/console doctrine:migrations:migrate
```

#### 6. Add starting set of data with Doctrine fixtures located in `src/DataFixtures`:
```
$ php bin/console doctrine:fixtures:load
```
###### *You can log (with Monolog) generated credentials in `src/DataFixtures/PartnerFixtures.php` file thanks to a constant (activated by default)*
```
class PartnerFixtures extends BaseFixture
{
    /**
     * Define log state to look at generated partner credentials.
     */
    const LOG_PARTNER_CREDENTIALS = true;
    
    // ...
{
```
#### 7. API accesses for first version "v1":
Base URL to request API as a consumer (simple partner):
> https://www.your-custom-domain.tld/api/v1/  
*Example to get associated clients :*  
*https://www.your-custom-domain.tld/api/v1/clients* 

Base URL to request API as an administrator (special partner):
> https://www.your-custom-domain.tld/api/v1/admin/  
*Example to get all registered partners :*  
*https://www.your-custom-domain.tld/api/v1/admin/partners* 

OpenAPI Documentation is accessible and divided in two parts:
- Simple consumer available requests: 
> https://www.your-custom-domain.tld/api/v1/consumer/doc 

- Administrator consumer (who manages API data) available requests: 
> https://www.your-custom-domain.tld/api/v1/administrator/doc

#### 8. API functional tests:
###### *Please note that a functional test suite was made in addition, to maintain correctly API requests. You can have a look at `tests/Functional` folder and `env.test` example file:*
For local installation:
```
# Switch to test environnement and install "test" particular database thanks to ".env.test.local" file configuration.
# e.g. with ".env.local.php"
$ composer dump-env test
# Then follow the same process as above to install "test" database and generate fixtures for this environment.
# Execute all existing functional tests after "test" particular database installation:
$ php bin/phpunit
```

