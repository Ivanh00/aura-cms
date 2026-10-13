<?php

namespace Aura\Base\Fields;

use Aura\Base\Contracts\PreloadsTableDisplay;
use Aura\Base\Resource;
use Aura\Base\Resources\Attachment;
use Illuminate\Database\Eloquent\Collection;

class Image extends Field implements PreloadsTableDisplay
{
    public $edit = 'aura::fields.image';

    public $optionGroup = 'Media Fields';

    public $view = 'aura::fields.view-value';

    public function display($field, $value, $model)
    {
        if (! $value) {
            return;
        }

        try {
            $values = is_array($value) ? $value : [$value];

            $firstImageValue = array_shift($values);
            $attachment = $this->resolveDisplayAttachment($field, $firstImageValue, $model);

            if ($attachment) {
                $url = $attachment->thumbnail('xs');
                $imageClasses = $field['display_image_classes'] ?? 'object-cover rounded-lg shadow-lg';
                $imageStyle = $field['display_image_style'] ?? 'width: 6rem;';
                $imageHtml = "<img src='{$url}' class='{$imageClasses}' style='{$imageStyle}'>";
            } else {
                return $value;
            }

            $additionalImagesCount = count($values);
            $circleHtml = '';
            if ($additionalImagesCount > 0) {
                $circleHtml = "<div class='flex justify-center items-center w-10 h-10 font-bold text-center text-gray-800 bg-gray-200 rounded-full'>+{$additionalImagesCount}</div>";
            }

            return "<div class='flex items-center space-x-2'>{$imageHtml}{$circleHtml}</div>";
        } catch (\Exception $e) {
            // Handle the exception or return a default value
            // Log the error message if logging is desired
            // error_log($e->getMessage());
        }
    }

    public function get($class, $value, $field = null)
    {
        if (is_string($value)) {
            return json_decode($value, true);
        }

        return $value;
    }

    public function getFields()
    {
        return array_merge(parent::getFields(), [
            [
                'label' => 'Image',
                'name' => 'Image',
                'type' => 'Aura\\Base\\Fields\\Tab',
                'slug' => 'image-tab',
                'style' => [],
            ],

            [
                'name' => 'Use Media Manager',
                'type' => 'Aura\\Base\\Fields\\Boolean',
                'slug' => 'use_media_manager',
            ],

            // min and max numbers for allowed number of files
            [
                'name' => 'Min Files',
                'type' => 'Aura\\Base\\Fields\\Number',
                'validation' => '',
                'slug' => 'min_files',
                'instructions' => 'Minimum number of files allowed',
            ],
            [
                'name' => 'Max Files',
                'type' => 'Aura\\Base\\Fields\\Number',
                'validation' => '',
                'slug' => 'max_files',
                'instructions' => 'Maximum number of files allowed',
            ],

            // allowed file types
            [
                'name' => 'Allowed File Types',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'slug' => 'allowed_file_types',
                'instructions' => 'Comma separated list of allowed file types. Example: jpg, png, gif',
            ],

        ]);
    }

    public function preloadTableDisplay(Collection $rows, array $field): void
    {
        $slug = $field['slug'];
        $attachmentClass = config('aura.resources.attachment', Attachment::class);

        $ids = [];

        foreach ($rows as $row) {
            if (! $row instanceof Resource) {
                continue;
            }

            foreach ($this->tableDisplayImageIds($row, $slug) as $id) {
                if ($id !== null && $id !== '') {
                    $ids[$id] = $id;
                }
            }
        }

        $attachments = new Collection;

        if (! empty($ids)) {
            $keyName = (new $attachmentClass)->getKeyName();

            // Scoped query: mirrors the per-row ::find() (no withoutGlobalScopes)
            // so team/permission scopes still apply.
            $attachments = $attachmentClass::query()
                ->whereKey(array_values($ids))
                ->get()
                ->keyBy($keyName);
        }

        foreach ($rows as $row) {
            if (! $row instanceof Resource) {
                continue;
            }

            $row->setTableDisplayValue($slug, $attachments);
        }
    }

    public function set($post, $field, $value)
    {
        return json_encode($value);
    }

    protected function resolveDisplayAttachment($field, $id, $model)
    {
        if ($id === null || $id === '') {
            return;
        }

        $slug = $field['slug'] ?? null;

        if ($slug && $model instanceof Resource && $model->hasTableDisplayValue($slug)) {
            $attachments = $model->getTableDisplayValue($slug);

            if ($attachments instanceof \Illuminate\Support\Collection) {
                return $attachments->get($id);
            }
        }

        $attachmentClass = config('aura.resources.attachment', Attachment::class);

        return $attachmentClass::find($id);
    }

    protected function tableDisplayImageIds(Resource $row, string $slug): array
    {
        $field = $row->fieldBySlug($slug);

        // Match the table display fast path: building all fields also resolves
        // unrelated, hidden relationship columns for every row.
        $value = $field && empty($field['conditional_logic'])
            && ! in_array($slug, $row->getHidden(), true)
            && ! str_contains($slug, '.')
                ? $row->resolveFieldValue($slug)
                : ($row->fields[$slug] ?? null);

        if ($value === null) {
            return [];
        }

        return is_array($value) ? $value : [$value];
    }
}
