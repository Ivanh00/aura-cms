<?php

use Aura\Base\Aura;
use Aura\Base\Resource;
use Aura\Base\Widgets\Donut;
use Aura\Base\Widgets\Pie;
use Aura\Base\Widgets\ValueWidget;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;

afterEach(function () {
    Schema::dropIfExists('custom_widget_projects');
});

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());

    Schema::create('custom_widget_projects', function (Blueprint $table) {
        $table->id();
        $table->string('status')->nullable();
        $table->foreignId('user_id');
        $table->foreignId('team_id')->nullable();
        $table->timestamps();
    });

    CustomTableWidgetModel::create([
        'status' => 'active',
        'score' => 10,
        'user_id' => $this->user->id,
        ...config('aura.teams') ? ['team_id' => $this->user->current_team_id] : [],
        'created_at' => Carbon::now()->subDays(5),
    ]);

    CustomTableWidgetModel::create([
        'status' => 'active',
        'score' => 20,
        'user_id' => $this->user->id,
        ...config('aura.teams') ? ['team_id' => $this->user->current_team_id] : [],
        'created_at' => Carbon::now()->subDays(10),
    ]);

    CustomTableWidgetModel::create([
        'status' => 'draft',
        'score' => 10,
        'user_id' => $this->user->id,
        ...config('aura.teams') ? ['team_id' => $this->user->current_team_id] : [],
        'created_at' => Carbon::now()->subDays(15),
    ]);
});

class CustomTableWidgetModel extends Resource
{
    public static $customTable = true;

    public static $singularName = 'Widget Project';

    public static ?string $slug = 'widget-project';

    public static string $type = 'WidgetProject';

    protected $fillable = [
        'status',
        'user_id',
        'team_id',
        'created_at',
        'updated_at',
    ];

    protected $table = 'custom_widget_projects';

    public static function getFields()
    {
        return [
            [
                'name' => 'Status',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'conditional_logic' => [],
                'slug' => 'status',
            ],
            [
                'name' => 'Score',
                'type' => 'Aura\\Base\\Fields\\Number',
                'validation' => '',
                'conditional_logic' => [],
                'slug' => 'score',
            ],
        ];
    }
}

class CustomTableStatusWidgetModel extends CustomTableWidgetModel
{
    public static ?string $slug = 'widget-status-project';

    public static string $type = 'WidgetStatusProject';

    public static function getFields()
    {
        return [
            [
                'name' => 'Status',
                'type' => 'Aura\\Base\\Fields\\Status',
                'validation' => '',
                'conditional_logic' => [],
                'slug' => 'status',
                'options' => [
                    ['key' => 'active', 'value' => 'Active'],
                    ['key' => 'draft', 'value' => 'Draft'],
                ],
            ],
        ];
    }
}

test('value widget aggregates a meta-backed field on a custom table resource', function () {
    $widgetTest = Livewire::test(ValueWidget::class, [
        'widget' => ['method' => 'sum', 'column' => 'score', 'name' => 'Score Sum', 'slug' => 'score_sum'],
        'model' => new CustomTableWidgetModel,
    ])
        ->set('start', Carbon::now()->subDays(30))
        ->set('end', Carbon::now());

    $widget = $widgetTest->instance();

    expect($widget->getValue($widget->start, $widget->end))->toBe(40);
});

test('pie widget aggregates deterministic meta-backed distribution on a custom table resource', function () {
    $widgetTest = Livewire::test(Pie::class, [
        'widget' => ['method' => 'count', 'column' => 'score', 'name' => 'Score Pie', 'slug' => 'score_pie'],
        'model' => new CustomTableWidgetModel,
    ])
        ->set('start', Carbon::now()->subDays(30))
        ->set('end', Carbon::now());

    $widget = $widgetTest->instance();

    expect($widget->getValue($widget->start, $widget->end))
        ->toBe(['10' => 2, '20' => 1])
        ->not->toHaveKey('tag-1');
});

test('donut widget aggregates deterministic table-column distribution on a custom table resource', function () {
    $widgetTest = Livewire::test(Donut::class, [
        'widget' => ['method' => 'count', 'column' => 'status', 'name' => 'Status Donut', 'slug' => 'status_donut'],
        'model' => new CustomTableWidgetModel,
    ])
        ->set('start', Carbon::now()->subDays(30))
        ->set('end', Carbon::now());

    $widget = $widgetTest->instance();

    expect($widget->getValue($widget->start, $widget->end))
        ->toBe(['active' => 2, 'draft' => 1])
        ->not->toHaveKey('tag-1');
});

test('pie widget groups a physical status column without throwing', function () {
    app(Aura::class)->registerResources([CustomTableStatusWidgetModel::class]);

    $widgetTest = Livewire::test(Pie::class, [
        'widget' => ['method' => 'count', 'column' => 'status', 'name' => 'Status Pie', 'slug' => 'status_pie'],
        'model' => new CustomTableStatusWidgetModel,
    ])
        ->set('start', Carbon::now()->subDays(30))
        ->set('end', Carbon::now());

    $widget = $widgetTest->instance();

    expect($widget->getValue($widget->start, $widget->end))
        ->toBe(['Active' => 2, 'Draft' => 1]);
});
