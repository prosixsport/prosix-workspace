<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FactoryBoardCustomField;
use App\Models\FactoryBoardSetting;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class FactoryBoardController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | BOARD CONFIG
    |--------------------------------------------------------------------------
    | AllOrdersView.vue expects:
    |
    | data.settings
    | data.custom_columns
    | data.custom_values
    |--------------------------------------------------------------------------
    */
    public function config(Request $request)
    {
        $setting = FactoryBoardSetting::singleton();

        $columns = FactoryBoardCustomField::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(function (FactoryBoardCustomField $field) {
                return $this->serializeColumn($field, true);
            })
            ->values();

        /*
         * Existing Order model stores custom selections in:
         *
         * custom_fields = {
         *     "priority": 100001,
         *     "order_type": 200003
         * }
         *
         * Convert that to the structure AllOrdersView.vue expects.
         */
        $columnsBySlug = $columns->keyBy('slug');

        $customValues = [];

        Order::query()
            ->select(['id', 'custom_fields'])
            ->whereNotNull('custom_fields')
            ->chunkById(250, function ($orders) use (&$customValues, $columnsBySlug) {
                foreach ($orders as $order) {
                    $stored = is_array($order->custom_fields)
                        ? $order->custom_fields
                        : (json_decode($order->custom_fields ?: '{}', true) ?: []);

                    $rowValues = [];

                    foreach ($stored as $slug => $storedValue) {
                        $column = $columnsBySlug->get($slug);

                        if (!$column) {
                            continue;
                        }

                        $type = $column['type'] ?? 'dropdown';

                        if (in_array($type, ['text', 'notes'], true)) {
                            $rowValues[] = [
                                'column_id' => (int) $column['id'],
                                'type' => $type,
                                'value' => (string) $storedValue,
                            ];

                            continue;
                        }

                        $option = collect($column['options'] ?? [])
                            ->first(function ($item) use ($storedValue) {
                                return (string) ($item['id'] ?? '') === (string) $storedValue
                                    || mb_strtolower((string) ($item['label'] ?? '')) === mb_strtolower((string) $storedValue);
                            });

                        if (!$option) {
                            continue;
                        }

                        $rowValues[] = [
                            'column_id' => (int) $column['id'],
                            'type' => 'dropdown',
                            'option_id' => (int) $option['id'],
                            'value_option_id' => (int) $option['id'],
                            'option' => $option,
                        ];
                    }

                    if ($rowValues) {
                        $customValues[(int) $order->id] = $rowValues;
                    }
                }
            });

        return response()->json([
            'data' => [
                'settings' => [
                    'auto_assign_all_owners' =>
                        (bool) $setting->auto_assign_all_owners,

                    'hidden_columns' =>
                        $setting->hidden_columns ?: [],
                ],

                'custom_columns' => $columns,

                'custom_values' => $customValues,
            ],
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | SETTINGS
    |--------------------------------------------------------------------------
    */
    public function updateSettings(Request $request)
    {
        $this->ensureSuperAdmin($request);

        $validated = $request->validate([
            /*
             * Do NOT require both values on every request.
             * If frontend changes only one setting, preserve the other.
             */
            'auto_assign_all_owners' => [
                'sometimes',
                'boolean',
            ],

            'hidden_columns' => [
                'sometimes',
                'array',
            ],

            'hidden_columns.*' => [
                'string',
                Rule::in([
                    'status',
                    'owner',
                    'files',
                    'packing',
                    'notes',
                    'chat',
                    'payment',
                    'address',
                    'track',
                ]),
            ],
        ]);

        $setting = FactoryBoardSetting::singleton();

        $autoAssign = array_key_exists(
            'auto_assign_all_owners',
            $validated
        )
            ? (bool) $validated['auto_assign_all_owners']
            : (bool) $setting->auto_assign_all_owners;

        $hiddenColumns = array_key_exists(
            'hidden_columns',
            $validated
        )
            ? array_values(
                array_unique(
                    $validated['hidden_columns'] ?? []
                )
            )
            : ($setting->hidden_columns ?: []);

        $setting->update([
            'auto_assign_all_owners' => $autoAssign,
            'hidden_columns' => $hiddenColumns,
        ]);

        $setting->refresh();

        return response()->json([
            'message' => 'Board settings updated successfully.',

            /*
             * Return both shapes so old/new AllOrdersView versions work.
             */
            'settings' => [
                'auto_assign_all_owners' =>
                    (bool) $setting->auto_assign_all_owners,

                'hidden_columns' =>
                    $setting->hidden_columns ?: [],
            ],

            'data' => [
                'auto_assign_all_owners' =>
                    (bool) $setting->auto_assign_all_owners,

                'hidden_columns' =>
                    $setting->hidden_columns ?: [],
            ],
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | GET CUSTOM COLUMNS
    |--------------------------------------------------------------------------
    */
    public function customColumns(Request $request)
    {
        $columns = FactoryBoardCustomField::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(
                fn (FactoryBoardCustomField $field) =>
                    $this->serializeColumn($field, true)
            )
            ->values();

        return response()->json([
            'data' => $columns,
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | CREATE CUSTOM COLUMN
    |--------------------------------------------------------------------------
    | Admin only enters column NAME first.
    | Dropdown labels are added afterward manually.
    |--------------------------------------------------------------------------
    */
    public function storeCustomColumn(Request $request)
    {
        $this->ensureSuperAdmin($request);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:120',
            ],

            'type' => [
                'required',
                Rule::in(['dropdown', 'text', 'notes']),
            ],
        ]);

        $name = trim($validated['name']);

        $slugBase =
            Str::slug($name, '_')
            ?: 'custom_column';

        $slug = $slugBase;
        $counter = 2;

        while (
            FactoryBoardCustomField::query()
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug =
                $slugBase . '_' . $counter;

            $counter++;
        }

        $nextSortOrder =
            ((int) FactoryBoardCustomField::max('sort_order'))
            + 1;

        $column =
            FactoryBoardCustomField::create([
                'name' => $name,
                'slug' => $slug,
                'type' => $validated['type'],
                'options' => [],
                'active' => true,
                'sort_order' => $nextSortOrder,
            ]);

        return response()->json([
            'message' =>
                'Custom dropdown column created successfully.',

            'data' =>
                $this->serializeColumn(
                    $column,
                    true
                ),
        ], 201);
    }


    /*
    |--------------------------------------------------------------------------
    | RENAME CUSTOM COLUMN
    |--------------------------------------------------------------------------
    */
public function updateCustomColumn(
        Request $request,
        FactoryBoardCustomField $column
    ) {
        $this->ensureSuperAdmin($request);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:120',
            ],

            'type' => [
                'sometimes',
                Rule::in(['dropdown', 'text', 'notes']),
            ],

            'active' => [
                'sometimes',
                'boolean',
            ],
        ]);

        /*
         * IMPORTANT:
         * slug does NOT change when name changes.
         * Existing order selections therefore stay attached.
         */
        $column->update([
            'name' =>
                trim($validated['name']),

            'type' =>
                array_key_exists('type', $validated)
                    ? $validated['type']
                    : ($column->type ?: 'dropdown'),

            'active' =>
                array_key_exists('active', $validated)
                    ? (bool) $validated['active']
                    : (bool) $column->active,
        ]);

        return response()->json([
            'message' =>
                'Custom column updated successfully.',

            'data' =>
                $this->serializeColumn(
                    $column->fresh(),
                    true
                ),
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | DELETE CUSTOM COLUMN
    |--------------------------------------------------------------------------
    */
    public function destroyCustomColumn(
        Request $request,
        FactoryBoardCustomField $column
    ) {
        $this->ensureSuperAdmin($request);

        $slug = $column->slug;

        /*
         * Remove this column selection from every order.
         */
        Order::query()
            ->whereNotNull('custom_fields')
            ->chunkById(
                200,
                function ($orders) use ($slug) {
                    foreach ($orders as $order) {
                        $values =
                            is_array($order->custom_fields)
                                ? $order->custom_fields
                                : (
                                    json_decode(
                                        $order->custom_fields ?: '{}',
                                        true
                                    ) ?: []
                                );

                        if (
                            !array_key_exists(
                                $slug,
                                $values
                            )
                        ) {
                            continue;
                        }

                        unset(
                            $values[$slug]
                        );

                        $order->forceFill([
                            'custom_fields' =>
                                $values,
                        ])->save();
                    }
                }
            );

        $column->delete();

        return response()->json([
            'message' =>
                'Custom column deleted successfully.',
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | ADD MANUAL DROPDOWN LABEL
    |--------------------------------------------------------------------------
    | Example:
    | label = Urgent
    | color = #ff3b30
    |--------------------------------------------------------------------------
    */
    public function storeCustomColumnOption(
        Request $request,
        FactoryBoardCustomField $column
    ) {

        if (($column->type ?: 'dropdown') !== 'dropdown') {
            return response()->json([
                'message' => 'Options can only be added to dropdown columns.',
            ], 422);
        }

        $this->ensureSuperAdmin($request);

        $validated = $request->validate([
            'label' => [
                'required',
                'string',
                'max:120',
            ],

            'color' => [
                'nullable',
                'regex:/^#[0-9A-Fa-f]{6}$/',
            ],
        ]);

        $options =
            $this->normalizeOptions(
                $column,
                true
            );

        $label =
            trim(
                $validated['label']
            );

        $duplicate =
            collect($options)->contains(
                fn ($option) =>
                    mb_strtolower(
                        trim(
                            (string) (
                                $option['label']
                                ?? ''
                            )
                        )
                    )
                    ===
                    mb_strtolower(
                        $label
                    )
            );

        if ($duplicate) {
            return response()->json([
                'message' =>
                    'This dropdown label already exists.',
            ], 422);
        }

        $nextId =
            $this->nextOptionId(
                $column,
                $options
            );

        $option = [
            'id' =>
                $nextId,

            'label' =>
                $label,

            'color' =>
                $validated['color']
                ?? $this->defaultOptionColor(
                    $column,
                    $options
                ),
        ];

        $options[] =
            $option;

        $column->update([
            'options' =>
                array_values(
                    $options
                ),
        ]);

        return response()->json([
            'message' =>
                'Dropdown label added successfully.',

            'data' =>
                $option,

            'column' =>
                $this->serializeColumn(
                    $column->fresh(),
                    true
                ),
        ], 201);
    }


    /*
    |--------------------------------------------------------------------------
    | EDIT DROPDOWN LABEL + COLOR
    |--------------------------------------------------------------------------
    | Route passes only option ID.
    | Since options are stored as JSON in FactoryBoardCustomField,
    | find the owning column first.
    |--------------------------------------------------------------------------
    */
    public function updateCustomColumnOption(
        Request $request,
        $option
    ) {
        $this->ensureSuperAdmin($request);

        $validated = $request->validate([
            'label' => [
                'required',
                'string',
                'max:120',
            ],

            'color' => [
                'required',
                'regex:/^#[0-9A-Fa-f]{6}$/',
            ],
        ]);

        [$column, $options, $index] =
            $this->findOption(
                (int) $option
            );

        if (!$column || $index === null) {
            abort(
                404,
                'Dropdown option not found.'
            );
        }

        $newLabel =
            trim(
                $validated['label']
            );

        $duplicate =
            collect($options)
                ->reject(
                    fn ($item, $key) =>
                        (int) $key === (int) $index
                )
                ->contains(
                    fn ($item) =>
                        mb_strtolower(
                            trim(
                                (string) (
                                    $item['label']
                                    ?? ''
                                )
                            )
                        )
                        ===
                        mb_strtolower(
                            $newLabel
                        )
                );

        if ($duplicate) {
            return response()->json([
                'message' =>
                    'This dropdown label already exists.',
            ], 422);
        }

        $options[$index] = [
            'id' =>
                (int) $options[$index]['id'],

            'label' =>
                $newLabel,

            'color' =>
                $validated['color'],
        ];

        $column->update([
            'options' =>
                array_values(
                    $options
                ),
        ]);

        return response()->json([
            'message' =>
                'Dropdown label updated successfully.',

            'data' =>
                $options[$index],
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | DELETE DROPDOWN LABEL
    |--------------------------------------------------------------------------
    */
    public function destroyCustomColumnOption(
        Request $request,
        $option
    ) {
        $this->ensureSuperAdmin($request);

        [$column, $options, $index] =
            $this->findOption(
                (int) $option
            );

        if (!$column || $index === null) {
            abort(
                404,
                'Dropdown option not found.'
            );
        }

        $deletedOption =
            $options[$index];

        unset(
            $options[$index]
        );

        $column->update([
            'options' =>
                array_values(
                    $options
                ),
        ]);

        /*
         * Any order currently using this option becomes Not Set.
         */
        $slug = $column->slug;
        $deletedId =
            (int) (
                $deletedOption['id']
                ?? 0
            );

        $deletedLabel =
            (string) (
                $deletedOption['label']
                ?? ''
            );

        Order::query()
            ->whereNotNull('custom_fields')
            ->chunkById(
                200,
                function ($orders) use (
                    $slug,
                    $deletedId,
                    $deletedLabel
                ) {
                    foreach ($orders as $order) {
                        $values =
                            is_array($order->custom_fields)
                                ? $order->custom_fields
                                : (
                                    json_decode(
                                        $order->custom_fields ?: '{}',
                                        true
                                    ) ?: []
                                );

                        if (
                            !array_key_exists(
                                $slug,
                                $values
                            )
                        ) {
                            continue;
                        }

                        $current =
                            $values[$slug];

                        if (
                            (string) $current
                                !==
                                (string) $deletedId

                            &&
                            mb_strtolower(
                                (string) $current
                            )
                                !==
                                mb_strtolower(
                                    $deletedLabel
                                )
                        ) {
                            continue;
                        }

                        unset(
                            $values[$slug]
                        );

                        $order->forceFill([
                            'custom_fields' =>
                                $values,
                        ])->save();
                    }
                }
            );

        return response()->json([
            'message' =>
                'Dropdown label deleted successfully.',
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | SAVE DROPDOWN VALUE ON ORDER
    |--------------------------------------------------------------------------
    | Route:
    | PUT /api/orders/{order}/custom-values/{column}
    |
    | Body:
    | { option_id: 123 }
    |--------------------------------------------------------------------------
    */
    public function updateOrderCustomValue(
        Request $request,
        Order $order,
        FactoryBoardCustomField $column
    ) {
        $this->ensureCanEditOrderField($request);

        $type = $column->type ?: 'dropdown';

        if ($type === 'dropdown') {
            $validated = $request->validate([
                'option_id' => [
                    'nullable',
                    'integer',
                ],
            ]);

            $options = $this->normalizeOptions($column, true);
            $optionId = $validated['option_id'] ?? null;

            $selectedOption = $optionId
                ? collect($options)->first(
                    fn ($item) =>
                        (int) ($item['id'] ?? 0) === (int) $optionId
                )
                : null;

            if ($optionId !== null && !$selectedOption) {
                return response()->json([
                    'message' => 'Selected dropdown option is invalid.',
                ], 422);
            }

            $values = is_array($order->custom_fields)
                ? $order->custom_fields
                : (json_decode($order->custom_fields ?: '{}', true) ?: []);

            if (!$selectedOption) {
                unset($values[$column->slug]);
            } else {
                $values[$column->slug] = (int) $selectedOption['id'];
            }

            $order->forceFill([
                'custom_fields' => $values,
            ])->save();

            return response()->json([
                'message' => $selectedOption
                    ? 'Custom value updated successfully.'
                    : 'Custom value cleared.',

                'value' => $selectedOption
                    ? [
                        'column_id' => (int) $column->id,
                        'type' => 'dropdown',
                        'option_id' => (int) $selectedOption['id'],
                        'value_option_id' => (int) $selectedOption['id'],
                        'option' => $selectedOption,
                    ]
                    : null,
            ]);
        }

        $validated = $request->validate([
            'value' => [
                'nullable',
                'string',
                $type === 'notes' ? 'max:5000' : 'max:255',
            ],
        ]);

        $value = (string) ($validated['value'] ?? '');

        if ($type === 'text') {
            $value = trim(preg_replace('/\s+/', ' ', $value));
        } else {
            $value = trim($value);
        }

        $values = is_array($order->custom_fields)
            ? $order->custom_fields
            : (json_decode($order->custom_fields ?: '{}', true) ?: []);

        if ($value === '') {
            unset($values[$column->slug]);
        } else {
            $values[$column->slug] = $value;
        }

        $order->forceFill([
            'custom_fields' => $values,
        ])->save();

        return response()->json([
            'message' => $value === ''
                ? 'Custom value cleared.'
                : 'Custom value updated successfully.',

            'value' => $value === ''
                ? null
                : [
                    'column_id' => (int) $column->id,
                    'type' => $type,
                    'value' => $value,
                ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | OLD /fields ROUTES - BACKWARD COMPATIBILITY
    |--------------------------------------------------------------------------
    */
    public function storeField(Request $request)
    {
        $this->ensureSuperAdmin($request);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:120',
            ],

            'options' => [
                'nullable',
                'array',
            ],

            'options.*' => [
                'nullable',
            ],

            'active' => [
                'nullable',
                'boolean',
            ],
        ]);

        $slugBase =
            Str::slug(
                $validated['name'],
                '_'
            )
            ?: 'custom_field';

        $slug =
            $slugBase;

        $counter =
            2;

        while (
            FactoryBoardCustomField::query()
                ->where(
                    'slug',
                    $slug
                )
                ->exists()
        ) {
            $slug =
                $slugBase
                . '_'
                . $counter;

            $counter++;
        }

        $nextSortOrder =
            ((int) FactoryBoardCustomField::max('sort_order'))
            + 1;

        $field =
            FactoryBoardCustomField::create([
                'name' =>
                    trim(
                        $validated['name']
                    ),

                'slug' =>
                    $slug,

                'type' =>
                    'dropdown',

                'options' =>
                    [],

                'active' =>
                    $validated['active']
                    ?? true,

                'sort_order' =>
                    $nextSortOrder,
            ]);

        /*
         * Convert legacy strings to colored option objects.
         */
        $legacyOptions =
            $validated['options']
            ?? [];

        foreach (
            $legacyOptions
            as $legacyOption
        ) {
            $label =
                is_array($legacyOption)
                    ? (
                        $legacyOption['label']
                        ?? ''
                    )
                    : $legacyOption;

            $label =
                trim(
                    (string) $label
                );

            if (!$label) {
                continue;
            }

            $requestLike =
                new Request([
                    'label' =>
                        $label,

                    'color' =>
                        is_array($legacyOption)
                            ? (
                                $legacyOption['color']
                                ?? '#fdab3d'
                            )
                            : '#fdab3d',
                ]);

            $requestLike->setUserResolver(
                fn () =>
                    $request->user()
            );

            $this->storeCustomColumnOption(
                $requestLike,
                $field
            );
        }

        return response()->json([
            'message' =>
                'Custom board column created.',

            'data' =>
                $this->serializeColumn(
                    $field->fresh(),
                    true
                ),
        ], 201);
    }


    public function updateField(
        Request $request,
        FactoryBoardCustomField $field
    ) {
        $this->ensureSuperAdmin($request);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:120',
            ],

            'active' => [
                'nullable',
                'boolean',
            ],
        ]);

        $field->update([
            'name' =>
                trim(
                    $validated['name']
                ),

            'active' =>
                $validated['active']
                ?? $field->active,
        ]);

        return response()->json([
            'message' =>
                'Custom board column updated.',

            'data' =>
                $this->serializeColumn(
                    $field->fresh(),
                    true
                ),
        ]);
    }


    public function destroyField(
        Request $request,
        FactoryBoardCustomField $field
    ) {
        return $this->destroyCustomColumn(
            $request,
            $field
        );
    }


    public function updateOrderCustomField(
        Request $request,
        Order $order
    ) {
        $this->ensureCanEditOrderField(
            $request
        );

        $validated = $request->validate([
            'field_slug' => [
                'required',
                'string',
                'max:140',
            ],

            'value' => [
                'nullable',
            ],
        ]);

        $column =
            FactoryBoardCustomField::query()
                ->where(
                    'slug',
                    $validated['field_slug']
                )
                ->where(
                    'active',
                    true
                )
                ->firstOrFail();

        $options =
            $this->normalizeOptions(
                $column,
                true
            );

        $rawValue =
            $validated['value']
            ?? null;

        $selected =
            collect($options)->first(
                function ($item) use ($rawValue) {
                    return (string) (
                        $item['id']
                        ?? ''
                    )
                        ===
                        (string) $rawValue

                        ||

                        mb_strtolower(
                            (string) (
                                $item['label']
                                ?? ''
                            )
                        )
                        ===
                        mb_strtolower(
                            (string) $rawValue
                        );
                }
            );

        $values =
            is_array($order->custom_fields)
                ? $order->custom_fields
                : (
                    json_decode(
                        $order->custom_fields ?: '{}',
                        true
                    ) ?: []
                );

        if (
            $rawValue === null
            ||
            trim(
                (string) $rawValue
            ) === ''
        ) {
            unset(
                $values[$column->slug]
            );
        } elseif (!$selected) {
            return response()->json([
                'message' =>
                    'Selected value is not valid for this field.',
            ], 422);
        } else {
            $values[$column->slug] =
                (int) $selected['id'];
        }

        $order->forceFill([
            'custom_fields' =>
                $values,
        ])->save();

        return response()->json([
            'message' =>
                'Custom field updated.',

            'custom_fields' =>
                $values,
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */
    private function serializeColumn(
        FactoryBoardCustomField $field,
        bool $persistNormalizedOptions = false
    ): array {
        return [
            'id' =>
                (int) $field->id,

            'name' =>
                $field->name,

            'slug' =>
                $field->slug,

            'type' =>
                $field->type ?: 'dropdown',

            'is_active' =>
                (bool) $field->active,

            'active' =>
                (bool) $field->active,

            'position' =>
                (int) $field->sort_order,

            'sort_order' =>
                (int) $field->sort_order,

            'options' =>
                $this->normalizeOptions(
                    $field,
                    $persistNormalizedOptions
                ),
        ];
    }


    private function normalizeOptions(
        FactoryBoardCustomField $field,
        bool $persist = false
    ): array {
        $raw =
            is_array($field->options)
                ? $field->options
                : (
                    json_decode(
                        $field->options ?: '[]',
                        true
                    ) ?: []
                );

        $normalized = [];
        $changed = false;

        foreach (
            array_values($raw)
            as $index => $item
        ) {
            if (is_array($item)) {
                $id =
                    (int) (
                        $item['id']
                        ?? 0
                    );

                if ($id <= 0) {
                    $id =
                        ((int) $field->id * 100000)
                        + $index
                        + 1;

                    $changed = true;
                }

                $label =
                    trim(
                        (string) (
                            $item['label']
                            ?? $item['name']
                            ?? ''
                        )
                    );

                if ($label === '') {
                    $changed = true;
                    continue;
                }

                $color =
                    (string) (
                        $item['color']
                        ?? '#6161ff'
                    );

                if (
                    !preg_match(
                        '/^#[0-9A-Fa-f]{6}$/',
                        $color
                    )
                ) {
                    $color =
                        '#6161ff';

                    $changed = true;
                }

                $normalized[] = [
                    'id' =>
                        $id,

                    'label' =>
                        $label,

                    'color' =>
                        $color,
                ];

                continue;
            }

            /*
             * Old database:
             * options = ["Urgent", "Normal"]
             *
             * Upgrade automatically to objects.
             */
            $label =
                trim(
                    (string) $item
                );

            if ($label === '') {
                $changed = true;
                continue;
            }

            $normalized[] = [
                'id' =>
                    ((int) $field->id * 100000)
                    + $index
                    + 1,

                'label' =>
                    $label,

                'color' =>
                    '#6161ff',
            ];

            $changed = true;
        }

        if (
            $persist
            &&
            $changed
        ) {
            $field->forceFill([
                'options' =>
                    array_values(
                        $normalized
                    ),
            ])->save();

            $field->refresh();
        }

        return array_values(
            $normalized
        );
    }


    private function nextOptionId(
        FactoryBoardCustomField $column,
        array $options
    ): int {
        $base =
            (int) $column->id
            * 100000;

        $max =
            collect($options)
                ->map(
                    fn ($item) =>
                        (int) (
                            $item['id']
                            ?? 0
                        )
                )
                ->max();

        if (
            !$max
            ||
            $max < $base
        ) {
            return $base + 1;
        }

        return $max + 1;
    }


    private function findOption(
        int $optionId
    ): array {
        $fields =
            FactoryBoardCustomField::query()
                ->orderBy('id')
                ->get();

        foreach (
            $fields
            as $field
        ) {
            $options =
                $this->normalizeOptions(
                    $field,
                    true
                );

            foreach (
                $options
                as $index => $option
            ) {
                if (
                    (int) (
                        $option['id']
                        ?? 0
                    )
                    ===
                    $optionId
                ) {
                    return [
                        $field,
                        $options,
                        $index,
                    ];
                }
            }
        }

        return [
            null,
            [],
            null,
        ];
    }


    private function defaultOptionColor(
        FactoryBoardCustomField $column,
        array $options
    ): string {
        $palette = [
            '#fdab3d',
            '#00c875',
            '#579bfc',
            '#e2445c',
            '#a25ddc',
            '#00c2ff',
            '#ff642e',
            '#037f4c',
        ];

        $index =
            count($options)
            % count($palette);

        return $palette[$index];
    }


    private function ensureSuperAdmin(
        Request $request
    ): void {
        $user =
            $request->user();

        if (
            !$user
            ||
            $user->role
                !==
                'super_admin'
        ) {
            abort(
                403,
                'Only Super Admin can manage board settings.'
            );
        }
    }


    private function ensureCanEditOrderField(
        Request $request
    ): void {
        $user =
            $request->user();

        if (
            !$user
            ||
            !in_array(
                $user->role,
                [
                    'super_admin',
                    'admin',
                    'member',
                ],
                true
            )
        ) {
            abort(
                403,
                'You cannot edit custom order fields.'
            );
        }
    }
}
