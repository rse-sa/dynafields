<?php

namespace RSE\DynaFields\Tests;

use Illuminate\Database\Eloquent\Model;
use Orchestra\Testbench\TestCase as BaseTestCase;
use RSE\DynaFields\Contracts\SupportsFieldInheritance;
use RSE\DynaFields\DynaFieldsServiceProvider;
use RSE\DynaFields\Traits\DefinesCustomFields;
use RSE\DynaFields\Traits\HasCustomFields;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [DynaFieldsServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
    }
}

// ---------------------------------------------------------------------------
// Fixture models — used in feature tests
// ---------------------------------------------------------------------------

/**
 * A plain subject model with no owner.
 */
class Post extends Model
{
    use HasCustomFields;

    protected $table      = 'posts';
    protected $guarded    = [];
    public $incrementing  = false;
    protected $keyType    = 'string';
    public $timestamps    = false;
}

/**
 * A subject model that belongs to a Category (owner).
 */
class Product extends Model
{
    use HasCustomFields;

    protected $table      = 'products';
    protected $guarded    = [];
    public $incrementing  = false;
    protected $keyType    = 'string';
    public $timestamps    = false;

    public function customFieldOwner(): ?Model
    {
        return $this->category ?? null;
    }

    public function category(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}

/**
 * An owner model with flat (no inheritance) structure.
 */
class Category extends Model
{
    use DefinesCustomFields;

    protected $table      = 'categories';
    protected $guarded    = [];
    public $incrementing  = false;
    protected $keyType    = 'string';
    public $timestamps    = false;
}

/**
 * An owner model that supports field inheritance.
 */
class Folder extends Model implements SupportsFieldInheritance
{
    use DefinesCustomFields;

    protected $table      = 'folders';
    protected $guarded    = [];
    public $incrementing  = false;
    protected $keyType    = 'string';
    public $timestamps    = false;

    /** @var array Parent chain for test setup */
    public array $ancestorIds = [];

    public function getAncestorOwnerIds(): array
    {
        return array_merge($this->ancestorIds, [$this->getKey()]);
    }
}

/**
 * A subject scoped by a Folder (owner with inheritance).
 */
class Document extends Model
{
    use HasCustomFields;

    protected $table      = 'documents';
    protected $guarded    = [];
    public $incrementing  = false;
    protected $keyType    = 'string';
    public $timestamps    = false;

    public function customFieldOwner(): ?Model
    {
        return $this->folder ?? null;
    }

    public function folder(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Folder::class);
    }
}
