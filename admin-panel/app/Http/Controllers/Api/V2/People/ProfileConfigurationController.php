<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\People;

use App\Http\Controllers\Controller;
use App\Http\Requests\People\ProfileConfigurationRequest;
use App\Models\Platform\ProfileFieldCategory;
use App\Services\People\Profiles\ProfileDefinitions;
use App\Services\People\Profiles\ProfileForms;
use App\Services\ResponseService;
use Illuminate\Http\JsonResponse;

class ProfileConfigurationController extends Controller
{
    public function index(ProfileConfigurationRequest $request): JsonResponse
    {
        $categories = ProfileFieldCategory::query()->where('profile_type', $request->validated('profile_type', 'learner'))->with(['definitions' => fn ($query) => $query->orderBy('sort_order')->orderBy('id')])->orderBy('sort_order')->orderBy('id')->get();

        return ResponseService::successResponse('data_fetch_successfully', $categories->map(fn ($category) => $this->categoryData($category))->all());
    }

    public function category(ProfileConfigurationRequest $request, ProfileDefinitions $definitions, ?int $category = null): JsonResponse
    {
        $saved = $definitions->category($request->user(), $request->validated(), $category)->refresh();

        return ResponseService::successResponse('data_update_successfully', $this->categoryData($saved));
    }

    public function field(ProfileConfigurationRequest $request, ProfileDefinitions $definitions, ?int $definition = null): JsonResponse
    {
        $saved = $definitions->field($request->user(), $request->validated(), $definition)->refresh();

        return ResponseService::successResponse('data_update_successfully', $this->fieldData($saved));
    }

    public function removeCategory(ProfileConfigurationRequest $request, ProfileDefinitions $definitions, int $category): JsonResponse
    {
        $definitions->removeCategory($request->user(), $category);

        return ResponseService::successResponse('data_delete_successfully');
    }

    public function removeField(ProfileConfigurationRequest $request, ProfileDefinitions $definitions, int $definition): JsonResponse
    {
        $definitions->removeField($request->user(), $definition);

        return ResponseService::successResponse('data_delete_successfully');
    }

    private function categoryData($category): array
    {
        return ['id' => (int) $category->id, 'profile_type' => $category->profile_type, 'key' => $category->key, 'label' => $category->label,
            'sort_order' => $category->sort_order, 'fields' => $category->definitions->map(fn ($field) => $this->fieldData($field))->all()];
    }

    private function fieldData($field): array
    {
        return ['id' => (int) $field->id, 'category_id' => (int) $field->category_id, 'key' => $field->key, 'label' => $field->label,
            'type' => $field->type, 'validation' => (object) app(ProfileForms::class)->validationFor($field), 'options' => $field->options,
            'visible_to' => $field->visible_to, 'editable_by' => $field->editable_by, 'required' => $field->required,
            'locked' => $field->platform_locked, 'sensitive' => $field->sensitive, 'encrypted' => $field->encrypted,
            'hidden' => $field->hidden, 'sort_order' => $field->sort_order];
    }
}
