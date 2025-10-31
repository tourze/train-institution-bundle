# train-institution-bundle

[English](README.md) | [中文](README.zh-CN.md)

[![PHP Version](https://img.shields.io/packagist/php-v/tourze/train-institution-bundle?style=flat-square)](
https://packagist.org/packages/tourze/train-institution-bundle)
[![License](https://img.shields.io/packagist/l/tourze/train-institution-bundle?style=flat-square)](
https://packagist.org/packages/tourze/train-institution-bundle)
[![Latest Version](https://img.shields.io/packagist/v/tourze/train-institution-bundle?style=flat-square)](
https://packagist.org/packages/tourze/train-institution-bundle)
[![Total Downloads](https://img.shields.io/packagist/dt/tourze/train-institution-bundle?style=flat-square)](
https://packagist.org/packages/tourze/train-institution-bundle)
[![Build Status](https://img.shields.io/github/actions/workflow/status/tourze/php-monorepo/test.yml?style=flat-square)](
https://github.com/tourze/php-monorepo/actions)
[![Code Coverage](https://img.shields.io/codecov/c/github/tourze/php-monorepo?style=flat-square)](
https://codecov.io/gh/tourze/php-monorepo)

Training institution management Bundle, providing features for institution information management, 
qualification management, facility management and change records, compliant with 
"Basic Conditions for Safety Production Training Institutions" (AQ8011-2023) standard.

## Table of Contents

- [Features](#features)
- [Installation](#installation)
- [Dependencies](#dependencies)
- [Configuration](#configuration)
- [Usage](#usage)
  - [Entity Models](#entity-models)
  - [Service Usage](#service-usage)
  - [Console Commands](#console-commands)
  - [Exception Handling](#exception-handling)
- [Advanced Usage](#advanced-usage)
- [Security](#security)
- [Testing](#testing)
- [Contributing](#contributing)
- [License](#license)
- [References](#references)

## Features

- Training institution information management (basic info, contact, business scope, etc.)
- Qualification certificate management (qualification type, validity period, issuing authority, etc.)
- Training facility management (classrooms, training grounds, equipment, etc.)
- Change record management (institution info changes, qualification changes, facility changes, etc.)
- Automated tasks (qualification expiry reminders, facility inspection scheduling, status monitoring, etc.)
- Complete exception handling mechanism

## Installation

```bash
composer require tourze/train-institution-bundle
```

## Dependencies

This Bundle requires the following dependencies:

### Required PHP Extensions
- `ext-filter` - Data filtering
- `ext-json` - JSON processing

### Symfony Components
- `symfony/config: ^7.3` - Configuration management
- `symfony/console: ^7.3` - Console command support
- `symfony/dependency-injection: ^7.3` - Service container
- `symfony/framework-bundle: ^7.3` - Core Symfony functionality
- `symfony/http-kernel: ^7.3` - HTTP kernel
- `symfony/routing: ^7.3` - Routing component
- `symfony/uid: ^7.3` - UID generation
- `symfony/yaml: ^7.3` - YAML processing

### Doctrine Components
- `doctrine/collections: ^2.3` - Collection utilities
- `doctrine/dbal: ^4.0` - Database abstraction layer
- `doctrine/doctrine-bundle: ^2.13` - Doctrine Bundle integration
- `doctrine/orm: ^3.0` - Object-relational mapping
- `doctrine/persistence: ^4.1` - Persistence abstraction

### Tourze Components
- `tourze/bundle-dependency: 0.0.*` - Bundle dependency management

### Development Dependencies
- `phpstan/phpstan: ^2.1` - Static analysis tool
- `phpunit/phpunit: ^11.5` - Testing framework

## Configuration

This Bundle follows Symfony's zero-configuration principle and is ready to use after 
installation. Services are automatically registered in the container.

## Usage

### Entity Models

#### Institution
```php
use Tourze\TrainInstitutionBundle\Entity\Institution;

// Create a training institution
$institution = Institution::create(
    'Beijing Safety Training Center',
    'BJAQPX001',
    'Enterprise Training Institution',
    'Zhang San',
    'Li Si',
    '13800138000',
    'contact@example.com',
    'No.1 Safety Road, Chaoyang District, Beijing',
    'Safety Production Training',
    new \DateTimeImmutable('2020-01-01'),
    'REG123456789'
);
```

#### InstitutionQualification
```php
use Tourze\TrainInstitutionBundle\Entity\InstitutionQualification;

// Create qualification certificate
$qualification = InstitutionQualification::create(
    $institution,
    'Safety Training Qualification',
    'Safety Production Training Institution Qualification Certificate',
    'CERT001',
    'Ministry of Emergency Management',
    new \DateTimeImmutable('2023-01-01'),
    new \DateTimeImmutable('2023-01-01'),
    new \DateTimeImmutable('2026-01-01'),
    ['Special Operations Training', 'Safety Manager Training']
);
```

#### InstitutionFacility
```php
use Tourze\TrainInstitutionBundle\Entity\InstitutionFacility;

// Create training facility
$facility = InstitutionFacility::create(
    $institution,
    'Classroom',
    'Multimedia Classroom A101',
    'Room 101, 1st Floor, Teaching Building',
    80.0,
    50,
    ['Projector', 'Sound System', 'Air Conditioning'],
    ['Meets fire safety requirements', 'Well ventilated']
);
```

#### InstitutionChangeRecord
```php
use Tourze\TrainInstitutionBundle\Entity\InstitutionChangeRecord;

// Create change record
$changeRecord = InstitutionChangeRecord::create(
    $institution,
    'Institution Information Change',
    ['field' => 'institutionName', 'oldValue' => 'Old Name', 'newValue' => 'New Name'],
    ['institutionName' => 'Old Name'],
    ['institutionName' => 'New Name'],
    'Brand Upgrade',
    'Administrator Zhang San'
);
```

### Service Usage

#### InstitutionService
```php
use Tourze\TrainInstitutionBundle\Service\InstitutionService;

// Inject service
public function __construct(private InstitutionService $institutionService) {}

// Create institution
$institution = $this->institutionService->createInstitution([
    'name' => 'Training Institution Name',
    'code' => 'CODE001',
    'type' => 'Enterprise Training Institution',
    // ...
]);

// Find institution
$institution = $this->institutionService->findByCode('CODE001');
$activeInstitutions = $this->institutionService->findActive();

// Update status
$this->institutionService->updateStatus($institution, 'Suspended');
```

#### QualificationService
```php
use Tourze\TrainInstitutionBundle\Service\QualificationService;

// Add qualification
$qualification = $this->qualificationService->addQualification($institution, [
    'type' => 'Safety Training Qualification',
    'name' => 'Qualification Name',
    'certificateNumber' => 'CERT001',
    // ...
]);

// Check qualification
$hasValid = $this->qualificationService->hasValidQualification($institution, 'Safety Training Qualification');
$expiring = $this->qualificationService->findExpiringSoon(30);

// Update qualification status
$this->qualificationService->updateStatus($qualification, 'Suspended');
```

#### FacilityService
```php
use Tourze\TrainInstitutionBundle\Service\FacilityService;

// Add facility
$facility = $this->facilityService->addFacility($institution, [
    'type' => 'Classroom',
    'name' => 'Classroom A101',
    'location' => '1st Floor, Teaching Building',
    'area' => 80.0,
    'capacity' => 50
]);

// Find facilities
$facilities = $this->facilityService->findByType('Classroom');
$needingInspection = $this->facilityService->findNeedingInspection();

// Schedule inspection
$this->facilityService->scheduleInspection($facility, new \DateTimeImmutable('+7 days'));
```

#### ChangeRecordService
```php
use Tourze\TrainInstitutionBundle\Service\ChangeRecordService;

// Record change
$record = $this->changeRecordService->recordChange(
    $institution,
    'Institution Information Change',
    ['field' => 'name', 'oldValue' => 'Old Name', 'newValue' => 'New Name'],
    'Brand Upgrade',
    'Administrator'
);

// Approve change
$this->changeRecordService->approveChange($record, 'Approver', 'Meets requirements');
```

## Console Commands

## Qualification Expiry Check
Check training institution qualification certificates that are about to expire and 
generate reminder reports.

```bash
# Check qualifications expiring within 30 days (default)
php bin/console institution:qualification:expiry-check

# Check qualifications expiring within 60 days
php bin/console institution:qualification:expiry-check --days=60

# Output in JSON format
php bin/console institution:qualification:expiry-check --format=json

# Dry run mode (no notifications sent)
php bin/console institution:qualification:expiry-check --dry-run
```

**Recommended cron configuration**:
```bash
# Execute at 9 AM every day
0 9 * * * cd /path/to/project && php bin/console institution:qualification:expiry-check
```

## Facility Inspection Schedule
Automatically schedule training facilities that need inspection to ensure facility 
safety and compliance.

```bash
# Auto-schedule all facilities needing inspection
php bin/console institution:facility:inspection-schedule --auto-schedule

# Set inspection start date
php bin/console institution:facility:inspection-schedule --start-date="2024-01-15"

# Set inspection interval (days)
php bin/console institution:facility:inspection-schedule --interval=14

# Dry run mode
php bin/console institution:facility:inspection-schedule --dry-run
```

**Recommended cron configuration**:
```bash
# Execute at 10 AM every Monday
0 10 * * 1 cd /path/to/project && php bin/console institution:facility:inspection-schedule -a
```

## Institution Status Check
Check training institution status and compliance to ensure institutions meet 
AQ8011-2023 standards.

```bash
# Check all institution statuses
php bin/console institution:status:check

# Check institutions with specific status
php bin/console institution:status:check --status="Pending Review"

# Check specific institution
php bin/console institution:status:check --institution-id=123

# Check compliance issues only
php bin/console institution:status:check --compliance-only

# Output in JSON format
php bin/console institution:status:check --format=json
```

**Recommended cron configuration**:
```bash
# Execute at 8 AM every day
0 8 * * * cd /path/to/project && php bin/console institution:status:check
```

## Institution Report Generation
Generate comprehensive reports for training institutions including institution status, 
qualifications, facilities, etc.

```bash
# Generate summary report for all institutions
php bin/console institution:report:generate

# Generate detailed report for specific institution
php bin/console institution:report:generate --institution-id=123 --type=detailed

# Generate compliance report
php bin/console institution:report:generate --type=compliance

# Generate statistics report and output to file
php bin/console institution:report:generate --type=statistics --output-file=/tmp/report.csv --format=csv

# Specify date range
php bin/console institution:report:generate --date-range="2024-01-01,2024-12-31"
```

**Recommended cron configuration**:
```bash
# Generate monthly report at 6 AM on the 1st of each month
0 6 1 * * cd /path/to/project && php bin/console institution:report:generate --type=statistics
```

## Data Sync
Sync training institution data to ensure data consistency and integrity.

```bash
# Sync from database (default)
php bin/console institution:data:sync

# Sync from API
php bin/console institution:data:sync --source=api

# Sync from file
php bin/console institution:data:sync --source=file

# Force sync (overwrite existing data)
php bin/console institution:data:sync --force

# Set batch size
php bin/console institution:data:sync --batch-size=50

# Dry run mode
php bin/console institution:data:sync --dry-run
```

**Recommended cron configuration**:
```bash
# Execute at 2 AM every day
0 2 * * * cd /path/to/project && php bin/console institution:data:sync
```

## Exception Handling

This Bundle provides a complete exception system:

- `TrainInstitutionException` - Base exception class
- `InstitutionNotFoundException` - Institution not found
- `DuplicateInstitutionCodeException` - Duplicate institution code
- `QualificationNotFoundException` - Qualification not found
- `QualificationExpiredException` - Qualification expired
- `FacilityNotFoundException` - Facility not found
- `InvalidInstitutionDataException` - Invalid institution data
- And more...

```php
try {
    $institution = $this->institutionService->findByCode('INVALID');
} catch (InstitutionNotFoundException $e) {
    // Handle institution not found case
}
```

## Advanced Usage

## Custom Event Listeners

You can listen to institution-related events to implement custom business logic:

```php
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Tourze\TrainInstitutionBundle\Event\InstitutionCreatedEvent;

class CustomInstitutionListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            InstitutionCreatedEvent::class => 'onInstitutionCreated',
        ];
    }
    
    public function onInstitutionCreated(InstitutionCreatedEvent $event): void
    {
        $institution = $event->getInstitution();
        // Custom logic when institution is created
    }
}
```

## Extending Entities

You can extend the base entities to add custom fields:

```php
use Doctrine\ORM\Mapping as ORM;
use Tourze\TrainInstitutionBundle\Entity\Institution as BaseInstitution;

#[ORM\Entity]
class CustomInstitution extends BaseInstitution
{
    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $customField = null;
    
    public function getCustomField(): ?string
    {
        return $this->customField;
    }
    
    public function setCustomField(?string $customField): void
    {
        $this->customField = $customField;
    }
}
```

## Custom Validation Rules

Implement custom validation for institution data:

```php
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class InstitutionCodeValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint): void
    {
        if (!preg_match('/^[A-Z]{2}[0-9]{6}$/', $value)) {
            $this->context->buildViolation('Invalid institution code format.')
                ->addViolation();
        }
    }
}
```

## Performance Optimization

### Database Optimization
```sql
-- Add indexes for better query performance
CREATE INDEX idx_institution_status ON institution (status);
CREATE INDEX idx_qualification_expiry ON institution_qualification (expiry_date);
CREATE INDEX idx_facility_type ON institution_facility (type);
```

### Caching Configuration
```yaml
# config/packages/cache.yaml
framework:
    cache:
        pools:
            institution.cache:
                adapter: cache.adapter.redis
                default_lifetime: 3600
```

## Security

## Data Protection

This Bundle handles sensitive training institution data. Please ensure:

1. **Database Security**: Use encrypted connections and proper access controls
2. **Input Validation**: All user inputs are validated using Symfony's validation component
3. **Access Control**: Implement proper authentication and authorization
4. **Audit Logging**: All changes are logged through the change record system

## Recommended Security Practices

```php
// Use parameterized queries (already implemented in services)
$query = $this->entityManager->createQuery(
    'SELECT i FROM Institution i WHERE i.code = :code'
);
$query->setParameter('code', $institutionCode);

// Validate all inputs
use Symfony\Component\Validator\Validator\ValidatorInterface;

public function createInstitution(array $data, ValidatorInterface $validator): Institution
{
    $violations = $validator->validate($data, $this->getValidationConstraints());
    if (count($violations) > 0) {
        throw new InvalidInstitutionDataException('Validation failed');
    }
    // Create institution...
}
```

## Security Considerations

- Sensitive qualification data should be encrypted at rest
- Implement rate limiting for API endpoints
- Use HTTPS for all communications
- Regular security audits of institution data access
- Implement proper backup and disaster recovery procedures

## Testing

Run tests:
```bash
# Run all tests
./vendor/bin/phpunit packages/train-institution-bundle/tests

# Run specific test
./vendor/bin/phpunit packages/train-institution-bundle/tests/Service/InstitutionServiceTest.php
```

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

Please make sure to update tests as appropriate and follow the coding standards.

## License

MIT

## References

- [Basic Conditions for Safety Production Training Institutions AQ8011-2023](https://www.mem.gov.cn/)
- [Symfony Bundle Documentation](https://symfony.com/doc/current/bundles.html)
- [Doctrine ORM Documentation](https://www.doctrine-project.org/projects/orm.html)