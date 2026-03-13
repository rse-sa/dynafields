<?php

namespace RSE\DynaFields\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use RSE\DynaFields\Http\Requests\CustomFieldRequest;
use RSE\DynaFields\Models\CustomField;

class CustomFieldController extends Controller
{
    public function create(): Response
    {
        $field = new (config('dynafields.models.custom_field', CustomField::class));

        $field->owner_type = request('owner_type');
        $field->owner_id   = request('owner_id');

        return response()->view('dynafields::field-form', [
            'r'           => $field,
            'title'       => __('dynafields::dynafields.add_field'),
            'action'      => 'create',
            'form_action' => route(config('dynafields.routes.name') . 'store'),
            'back_to'     => url()->previous(),
        ]);
    }

    public function store(CustomFieldRequest $request): Response
    {
        $validated = $request->validated();

        if (! empty($validated['options'])) {
            $validated['data'] = ['options' => $validated['options']];
            unset($validated['options']);
        }

        $fieldModel = config('dynafields.models.custom_field', CustomField::class);
        $fieldModel::create($validated);

        return response('', 204);
    }

    public function edit(CustomField $customField): Response
    {
        return response()->view('dynafields::field-form', [
            'r'           => $customField,
            'title'       => __('dynafields::dynafields.edit_field', ['name' => $customField->name]),
            'action'      => 'edit',
            'form_action' => route(config('dynafields.routes.name') . 'update', [$customField]),
            'back_to'     => url()->previous(),
        ]);
    }

    public function update(CustomFieldRequest $request, CustomField $customField): Response
    {
        $validated = $request->validated();

        if (! empty($validated['options'])) {
            $validated['data'] = ['options' => $validated['options']];
            unset($validated['options']);
        }

        $customField->update($validated);

        return response('', 204);
    }

    public function destroy(CustomField $customField): RedirectResponse
    {
        $customField->delete();

        return redirect()->back();
    }
}
