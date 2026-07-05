<?php

namespace RSE\DynaFields\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;
use RSE\DynaFields\Models\CustomField;

class CustomFieldsForm extends Component
{
    /** Existing field values for the subject (plucked collection, locked). */
    #[Locked]
    public Collection $values;

    /** Subject model class name (e.g. App\Models\Asset). */
    #[Locked]
    public string $subjectType;

    /** Subject model primary key (null for create action). */
    #[Locked]
    public ?string $subjectKey = null;

    /** Current owner model class name (can change on create). */
    #[Locked]
    public ?string $ownerType = null;

    /** Current owner model primary key (can change on create). */
    #[Locked]
    public ?string $ownerKey = null;

    /** 'create' or 'edit'. */
    #[Locked]
    public string $action = 'edit';

    public function mount(Model $subject, string $action, ?Model $owner = null): void
    {
        $this->subjectType = get_class($subject);
        $this->subjectKey  = $subject->exists ? $subject->getKey() : null;
        $this->action      = $action;
        $this->values      = $subject->exists ? $subject->customFieldValues : collect();

        $resolvedOwner     = $owner ?? $subject->customFieldOwner();
        $this->ownerType   = $resolvedOwner ? get_class($resolvedOwner) : null;
        $this->ownerKey    = $resolvedOwner?->getKey();
    }

    /**
     * Listen for owner changes (e.g. user picks a different group).
     * Dispatch this event from your form: $dispatch('dynafields:owner-change', {ownerType, ownerKey})
     */
    #[On('dynafields:owner-change')]
    public function handleOwnerChange(string $ownerType, string $ownerKey): void
    {
        $this->ownerType = $ownerType;
        $this->ownerKey  = $ownerKey;
        $this->values    = collect(); // reset values when owner changes on create
    }

    public function render(): View
    {
        $owner      = $this->resolveOwner();
        $fieldModel = config('dynafields.models.custom_field', CustomField::class);

        $fields = $fieldModel::forSubject($this->subjectType, $owner)
            ->orderBy('order')
            ->get();

        $fields = $this->annotateFields($fields, $owner);

        return view('dynafields::livewire.custom-fields-form', [
            'fields'     => $fields,
            'valueArray' => $this->values->pluck('value', 'custom_field_id'),
            'fileValues' => $this->resolveFileValues($fields),
            'action'     => $this->action,
        ]);
    }

    private function resolveFileValues(Collection $fields): array
    {
        $fileFieldIds = $fields->where('type', 'file')->pluck('id')->all();

        if (empty($fileFieldIds) || $this->action === 'create') {
            return [];
        }

        $relevant = $this->values->whereIn('custom_field_id', $fileFieldIds);

        if ($relevant->isEmpty()) {
            return [];
        }

        $relevant->loadMissing('field');

        $subject = $this->subjectKey ? ($this->subjectType)::find($this->subjectKey) : null;

        return $relevant
            ->mapWithKeys(fn ($cfv) => [$cfv->custom_field_id => $cfv->retrieveFileValue($subject)])
            ->all();
    }

    private function resolveOwner(): ?Model
    {
        if (! $this->ownerType || ! $this->ownerKey) {
            return null;
        }

        return ($this->ownerType)::find($this->ownerKey);
    }

    private function annotateFields(Collection $fields, ?Model $owner): Collection
    {
        $seenNames = [];

        return $fields->map(function ($field) use (&$seenNames, $owner) {
            $nameArr   = is_array($field->name) ? $field->name : [];
            $fieldName = $nameArr[app()->getLocale()] ?? reset($nameArr) ?: $field->name;

            $field->is_readonly    = isset($seenNames[$fieldName]);
            $field->is_global      = $field->owner_type === null;
            $field->is_type_scoped = $field->owner_id === null && $field->owner_type !== null;
            $field->is_inherited   = $owner
                && $field->owner_type === get_class($owner)
                && $field->owner_id !== $this->ownerKey;

            $seenNames[$fieldName] = true;

            return $field;
        });
    }
}
