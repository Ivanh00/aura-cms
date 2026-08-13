<?php

use Aura\Base\Aura;
use Aura\Base\Contracts\DeclaresReportingQueryScopes;
use Aura\Base\Fields\Boolean;
use Aura\Base\Fields\Number;
use Aura\Base\Fields\Select;
use Aura\Base\Fields\Status;
use Aura\Base\Fields\Text;
use Aura\Base\Reporting\AggregateDefinition;
use Aura\Base\Reporting\AggregateOperation;
use Aura\Base\Reporting\DateBucket;
use Aura\Base\Reporting\DateRange;
use Aura\Base\Reporting\ResourceAggregateEngine;
use Aura\Base\Resource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;

final class Core29AggregateResource extends Resource implements DeclaresReportingQueryScopes
{
    public static $customTable = true;

    public static ?string $slug = 'core29-aggregate-resource';

    public static string $type = 'Core29AggregateResource';

    public static bool $usesMeta = false;

    protected $fillable = ['amount', 'stage', 'visible', 'user_id', 'team_id'];

    protected $table = 'core29_aggregate_resources';

    public static function getFields(): array
    {
        return [
            ['name' => 'Amount', 'slug' => 'amount', 'type' => Number::class, 'number_type' => 'decimal', 'precision' => 18, 'scale' => 6],
            ['name' => 'Stage', 'slug' => 'stage', 'type' => Select::class, 'options' => [['key' => 'open', 'value' => 'Open'], ['key' => 'won', 'value' => 'Won']]],
            ['name' => 'Visible', 'slug' => 'visible', 'type' => Boolean::class],
            ['name' => 'Ignored', 'slug' => 'ignored', 'type' => Text::class],
        ];
    }

    /** @param Builder<Core29AggregateResource> $query */
    public function indexQuery(Builder $query): Builder
    {
        return $query->where($this->qualifyColumn('visible'), true);
    }

    public static function reportingQueryScopes(): array
    {
        return ['openStage'];
    }

    /** @param Builder<Core29AggregateResource> $query */
    public function scopeOpenStage(Builder $query): Builder
    {
        return $query->where($this->qualifyColumn('stage'), 'open');
    }
}

final class Core29StatusGroupResource extends Resource
{
    public static $customTable = true;

    public static ?string $slug = 'core29-status-group-resource';

    public static string $type = 'Core29StatusGroupResource';

    public static bool $usesMeta = false;

    protected $fillable = ['stage', 'visible', 'user_id', 'team_id'];

    protected $table = 'core29_aggregate_resources';

    public static function getFields(): array
    {
        return [
            ['name' => 'Stage', 'slug' => 'stage', 'type' => Status::class, 'options' => [['key' => 'open', 'value' => 'Open'], ['key' => 'won', 'value' => 'Won']]],
            ['name' => 'Visible', 'slug' => 'visible', 'type' => Boolean::class],
        ];
    }

    /** @param Builder<Core29StatusGroupResource> $query */
    public function indexQuery(Builder $query): Builder
    {
        return $query->where($this->qualifyColumn('visible'), true);
    }
}

beforeEach(function (): void {
    Schema::create('core29_aggregate_resources', function (Blueprint $table): void {
        $table->id();
        $table->text('amount')->nullable();
        $table->string('stage')->nullable();
        $table->boolean('visible');
        $table->unsignedBigInteger('user_id')->nullable();
        $table->unsignedBigInteger('team_id')->nullable();
        $table->timestamps();
    });
    $this->actingAs($this->user = createSuperAdmin());
    app(Aura::class)->registerResources([Core29AggregateResource::class, Core29StatusGroupResource::class]);
    Gate::before(static fn (): bool => true);
});

afterEach(function (): void {
    Schema::dropIfExists('core29_aggregate_resources');
});

test('aggregates exact physical values through the authorized resource query', function (): void {
    $team = ['user_id' => $this->user->id, 'team_id' => $this->user->current_team_id];
    Core29AggregateResource::withoutGlobalScopes()->create(['amount' => '1.250000', 'stage' => 'open', 'visible' => true, ...$team]);
    Core29AggregateResource::withoutGlobalScopes()->create(['amount' => '2.500000', 'stage' => 'won', 'visible' => true, ...$team]);
    Core29AggregateResource::withoutGlobalScopes()->create(['amount' => '99.000000', 'stage' => 'won', 'visible' => false, ...$team]);

    expect(Core29AggregateResource::withoutGlobalScopes()->count())->toBe(3);

    $result = (new ResourceAggregateEngine)->run(new AggregateDefinition(
        Core29AggregateResource::class,
        AggregateOperation::Average,
        'amount',
    ));

    expect($result->value)->toBe('1.875000');
});

test('returns immutable null-last formatted group points and rejects unregistered resources', function (): void {
    $team = ['user_id' => $this->user->id, 'team_id' => $this->user->current_team_id];
    Core29AggregateResource::withoutGlobalScopes()->create(['amount' => '2.000000', 'stage' => 'won', 'visible' => true, ...$team]);
    Core29AggregateResource::withoutGlobalScopes()->create(['amount' => '1.000000', 'stage' => null, 'visible' => true, ...$team]);
    Core29AggregateResource::withoutGlobalScopes()->create(['amount' => '1.000000', 'stage' => 'open', 'visible' => true, ...$team]);

    $result = (new ResourceAggregateEngine)->run(new AggregateDefinition(
        Core29AggregateResource::class,
        AggregateOperation::Sum,
        'amount',
        'stage',
    ));

    expect($result->points)->toHaveCount(3)
        ->and($result->points[0]->key)->toBe('open')
        ->and($result->points[0]->label)->toBe('Open')
        ->and($result->points[2]->key)->toBeNull()
        ->and($result->points[2]->label)->toBe('Empty')
        ->and(fn () => $result->points[0]->label = 'nope')->toThrow(Error::class);
});

test('uses half-open ranges', function (): void {
    DB::table('core29_aggregate_resources')->insert([
        ['amount' => '1.000000', 'stage' => 'open', 'visible' => true, 'user_id' => $this->user->id, 'team_id' => $this->user->current_team_id, 'created_at' => '2026-01-01 00:00:00', 'updated_at' => '2026-01-01 00:00:00'],
        ['amount' => '2.000000', 'stage' => 'open', 'visible' => true, 'user_id' => $this->user->id, 'team_id' => $this->user->current_team_id, 'created_at' => '2026-01-02 00:00:00', 'updated_at' => '2026-01-02 00:00:00'],
    ]);

    expect(Core29AggregateResource::withoutGlobalScopes()->count())->toBe(2)
        ->and(Core29AggregateResource::withoutGlobalScopes()
            ->where('created_at', '>=', '2026-01-01 00:00:00')
            ->where('created_at', '<', '2026-01-02 00:00:00')
            ->count())->toBe(1);

    $result = (new ResourceAggregateEngine)->run(new AggregateDefinition(
        Core29AggregateResource::class,
        AggregateOperation::Count,
        range: new DateRange(new DateTimeImmutable('2026-01-01 00:00:00 UTC'), new DateTimeImmutable('2026-01-02 00:00:00 UTC')),
    ));

    expect($result->value)->toBe(1);
});

test('only explicitly allowlisted reporting query scopes can run', function (): void {
    $team = ['user_id' => $this->user->id, 'team_id' => $this->user->current_team_id];
    Core29AggregateResource::withoutGlobalScopes()->create(['amount' => '1.000000', 'stage' => 'open', 'visible' => true, ...$team]);
    Core29AggregateResource::withoutGlobalScopes()->create(['amount' => '2.000000', 'stage' => 'won', 'visible' => true, ...$team]);
    $engine = new ResourceAggregateEngine;

    expect($engine->run(new AggregateDefinition(
        Core29AggregateResource::class,
        AggregateOperation::Count,
        queryScope: 'openStage',
    ))->value)->toBe(1)
        ->and(fn () => $engine->run(new AggregateDefinition(
            Core29AggregateResource::class,
            AggregateOperation::Count,
            queryScope: 'indexQuery',
        )))->toThrow(InvalidArgumentException::class, 'explicitly allowlisted');
});

test('rounds exact averages half away from zero and returns null for all-null values', function (): void {
    $team = ['user_id' => $this->user->id, 'team_id' => $this->user->current_team_id];
    Core29AggregateResource::withoutGlobalScopes()->create(['amount' => '0.000001', 'stage' => 'open', 'visible' => true, ...$team]);
    Core29AggregateResource::withoutGlobalScopes()->create(['amount' => '0.000002', 'stage' => 'open', 'visible' => true, ...$team]);
    $engine = new ResourceAggregateEngine;

    expect($engine->run(new AggregateDefinition(Core29AggregateResource::class, AggregateOperation::Average, 'amount'))->value)
        ->toBe('0.000002');

    DB::table('core29_aggregate_resources')->delete();
    Core29AggregateResource::withoutGlobalScopes()->create(['amount' => '-0.000001', 'stage' => 'open', 'visible' => true, ...$team]);
    Core29AggregateResource::withoutGlobalScopes()->create(['amount' => '-0.000002', 'stage' => 'open', 'visible' => true, ...$team]);

    expect($engine->run(new AggregateDefinition(Core29AggregateResource::class, AggregateOperation::Average, 'amount'))->value)
        ->toBe('-0.000002');

    DB::table('core29_aggregate_resources')->delete();
    Core29AggregateResource::withoutGlobalScopes()->create(['amount' => null, 'stage' => 'open', 'visible' => true, ...$team]);

    expect($engine->run(new AggregateDefinition(Core29AggregateResource::class, AggregateOperation::Sum, 'amount'))->value)->toBeNull();
});

test('uses portable local calendar keys for every bucket and both Zurich DST transitions', function (): void {
    $attributes = ['visible' => true, 'user_id' => $this->user->id, 'team_id' => $this->user->current_team_id];
    DB::table('core29_aggregate_resources')->insert([
        [...$attributes, 'amount' => '1.000000', 'stage' => 'open', 'created_at' => '2024-02-29 12:00:00', 'updated_at' => '2024-02-29 12:00:00'],
        [...$attributes, 'amount' => '1.000000', 'stage' => 'open', 'created_at' => '2024-03-31 00:30:00', 'updated_at' => '2024-03-31 00:30:00'],
        [...$attributes, 'amount' => '1.000000', 'stage' => 'open', 'created_at' => '2024-03-31 01:30:00', 'updated_at' => '2024-03-31 01:30:00'],
        [...$attributes, 'amount' => '1.000000', 'stage' => 'open', 'created_at' => '2024-10-27 00:30:00', 'updated_at' => '2024-10-27 00:30:00'],
        [...$attributes, 'amount' => '1.000000', 'stage' => 'open', 'created_at' => '2024-10-27 01:30:00', 'updated_at' => '2024-10-27 01:30:00'],
    ]);
    $engine = new ResourceAggregateEngine;
    $bucket = static function (DateBucket $bucket, string $start, string $end) use ($engine): array {
        $result = $engine->run(new AggregateDefinition(
            Core29AggregateResource::class,
            AggregateOperation::Count,
            range: new DateRange(new DateTimeImmutable($start, new DateTimeZone('Europe/Zurich')), new DateTimeImmutable($end, new DateTimeZone('Europe/Zurich'))),
            bucket: $bucket,
            timezone: 'Europe/Zurich',
        ));

        return array_column($result->points, 'value', 'key');
    };

    expect($bucket(DateBucket::Day, '2024-03-30', '2024-04-02'))->toBe(['2024-03-31' => 2])
        ->and($bucket(DateBucket::Day, '2024-10-26', '2024-10-29'))->toBe(['2024-10-27' => 2])
        ->and($bucket(DateBucket::Week, '2024-03-25', '2024-04-01'))->toBe(['2024-W13' => 2])
        ->and($bucket(DateBucket::Month, '2024-02-01', '2024-04-01'))->toBe(['2024-02' => 1, '2024-03' => 2])
        ->and($bucket(DateBucket::Quarter, '2024-01-01', '2025-01-01'))->toBe(['2024-Q1' => 3, '2024-Q4' => 2])
        ->and($bucket(DateBucket::Year, '2024-01-01', '2025-01-01'))->toBe(['2024' => 5]);
});

test('rejects invalid timezones and more than four hundred bucket points', function (): void {
    $engine = new ResourceAggregateEngine;

    expect(fn () => $engine->run(new AggregateDefinition(
        Core29AggregateResource::class,
        AggregateOperation::Count,
        range: new DateRange(new DateTimeImmutable('2024-01-01'), new DateTimeImmutable('2024-01-02')),
        bucket: DateBucket::Day,
        timezone: 'Invalid/Timezone',
    )))->toThrow(InvalidArgumentException::class, 'valid IANA timezone')
        ->and(fn () => $engine->run(new AggregateDefinition(
            Core29AggregateResource::class,
            AggregateOperation::Count,
            range: new DateRange(new DateTimeImmutable('2024-01-01'), new DateTimeImmutable('2025-03-01')),
            bucket: DateBucket::Day,
        )))->toThrow(InvalidArgumentException::class, 'limited to 400 points');
});

test('rejects meta-backed metrics without projection reads', function (): void {
    $engine = new ResourceAggregateEngine;

    // Create a temporary resource class is heavy; use a custom-table resource that marks amount as meta.
    $metaResource = new class extends Resource
    {
        public static $customTable = true;

        public static ?string $slug = 'core29-meta-metric';

        public static string $type = 'Core29MetaMetric';

        public static bool $usesMeta = true;

        protected $fillable = ['user_id', 'team_id'];

        protected $table = 'core29_aggregate_resources';

        public static function getFields(): array
        {
            return [
                ['name' => 'Amount', 'slug' => 'amount', 'type' => Number::class],
            ];
        }
    };

    app(Aura::class)->registerResources([$metaResource::class]);

    expect(fn () => $engine->run(new AggregateDefinition(
        $metaResource::class,
        AggregateOperation::Sum,
        'amount',
    )))->toThrow(InvalidArgumentException::class, 'Meta-backed reporting metrics');
});

test('groups by a physical Status field without throwing and returns points', function (): void {
    $team = ['user_id' => $this->user->id, 'team_id' => $this->user->current_team_id];
    Core29StatusGroupResource::withoutGlobalScopes()->create(['stage' => 'open', 'visible' => true, ...$team]);
    Core29StatusGroupResource::withoutGlobalScopes()->create(['stage' => 'won', 'visible' => true, ...$team]);
    Core29StatusGroupResource::withoutGlobalScopes()->create(['stage' => 'won', 'visible' => true, ...$team]);

    $result = (new ResourceAggregateEngine)->run(new AggregateDefinition(
        Core29StatusGroupResource::class,
        AggregateOperation::Count,
        groupBy: 'stage',
    ));

    expect($result->points)->toHaveCount(2)
        ->and($result->points[0]->key)->toBe('open')
        ->and($result->points[0]->label)->toBe('Open')
        ->and($result->points[0]->value)->toBe(1)
        ->and($result->points[1]->key)->toBe('won')
        ->and($result->points[1]->label)->toBe('Won')
        ->and($result->points[1]->value)->toBe(2);
});
