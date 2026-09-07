<?php

namespace App\Tables;

use App\Models\Category;
use App\Models\Product;
use App\ProductStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Musing\InertiaTable\Actions\Action;
use Musing\InertiaTable\Columns\ActionColumn;
use Musing\InertiaTable\Columns\BadgeColumn;
use Musing\InertiaTable\Columns\BooleanColumn;
use Musing\InertiaTable\Columns\DateColumn;
use Musing\InertiaTable\Columns\DateTimeColumn;
use Musing\InertiaTable\Columns\NumberColumn;
use Musing\InertiaTable\Columns\TextColumn;
use Musing\InertiaTable\EmptyState;
use Musing\InertiaTable\Exports\Export;
use Musing\InertiaTable\Filters\BooleanFilter;
use Musing\InertiaTable\Filters\DateFilter;
use Musing\InertiaTable\Filters\Filter;
use Musing\InertiaTable\Filters\FilterOptionRequest;
use Musing\InertiaTable\Filters\NumericFilter;
use Musing\InertiaTable\Filters\SetFilter;
use Musing\InertiaTable\Filters\TextFilter;
use Musing\InertiaTable\PaginationType;
use Musing\InertiaTable\Selection;
use Musing\InertiaTable\Table;
use Musing\InertiaTable\Variant;
use Musing\InertiaTable\Views;

final class ProductsTable extends Table
{
    protected ?string $defaultSort = '-id';

    protected ?int $perPage = 10;

    protected ?array $perPageOptions = [10, 25, 50, 100];

    protected ?PaginationType $paginationType = PaginationType::Cursor;

    protected ?bool $stickyHeader = true;

    protected ?bool $stickyFooter = true;

    /** @return Builder<Product> */
    public function query(): Builder
    {
        return Product::query()->with('category:id,name');
    }

    public function columns(): array
    {
        return [
            NumberColumn::make('id', 'ID')
                ->sortable()
                ->toggleable(false)
                ->stickable()
                ->summary('count'),
            TextColumn::make('name', 'Sản phẩm')
                ->searchable()
                ->sortable()
                ->stickable()
                ->wrap()
                ->truncate(2)
                ->cellClass('min-w-72')
                ->width(280) // kích thước mặc định
                ->minWidth(180) // giới hạn khi kéo
                ->maxWidth(480)
                ->resizable(), // bật tay kéo
            TextColumn::make('sku', 'SKU')
                ->searchable()
                ->sortable()
                ->stickable()
                ->cellClass('font-mono text-xs'),
            TextColumn::make('category.name', 'Danh mục')
                ->searchable()
                ->stickable()
                ->summaryUsing(
                    fn (Builder $query): int => (clone $query)
                        ->distinct()
                        ->count('category_id'),
                ),
            BadgeColumn::make('status', 'Trạng thái')
                ->mapAs(fn (ProductStatus $status): string => $status->label())
                ->variant(fn (ProductStatus $status): Variant => match ($status) {
                    ProductStatus::Active => Variant::Success,
                    ProductStatus::Draft => Variant::Warning,
                    ProductStatus::Archived => Variant::Default,
                })
                ->icon(fn (ProductStatus $status): string => match ($status) {
                    ProductStatus::Active => 'CircleCheck',
                    ProductStatus::Draft => 'Clock3',
                    ProductStatus::Archived => 'Archive',
                })
                ->sortable(),
            NumberColumn::make('price', 'Giá')
                ->sortable()
                ->rightAligned()
                ->cellClass('font-medium tabular-nums')
                ->exportFormat('#,##0.00')
                ->summary('sum')
                ->summaryFormat('#,##0.00'),
            NumberColumn::make('stock', 'Tồn kho')
                ->sortable()
                ->rightAligned()
                ->cellClass('tabular-nums')
                ->summary('sum'),
            NumberColumn::make('rating', 'Đánh giá')
                ->sortable()
                ->rightAligned()
                ->summary('avg'),
            BooleanColumn::make('featured', 'Nổi bật')
                ->sortable()
                ->trueLabel('Có')
                ->falseLabel('Không')
                ->trueIcon('Star')
                ->summary('sum'),
            DateColumn::make('launched_at', 'Ngày mở bán')
                ->format('d/m/Y')
                ->sortable(),
            DateTimeColumn::make('updated_at', 'Cập nhật')
                ->format('d/m/Y H:i')
                ->sortable()
                ->visible(false),
            ActionColumn::new()->asDropdown()->header('Thao tác'),
        ];
    }

    /** @return array<int, Filter> */
    public function filters(): array
    {
        return [
            TextFilter::make('name', 'Tên sản phẩm'),
            SetFilter::make('category_id', 'Danh mục')
                ->optionsUsing(function (FilterOptionRequest $request): Builder {
                    $statuses = array_values(array_filter(
                        (array) $request->dependency('status'),
                        fn (mixed $status): bool => is_string($status),
                    ));

                    return Category::query()
                        ->when(
                            $statuses !== [],
                            fn (Builder $query): Builder => $query->whereHas(
                                'products',
                                fn (Builder $products): Builder => $products->whereIn('status', $statuses),
                            ),
                        )
                        ->orderBy('name');
                })
                ->optionValue('id')
                ->optionLabel('name')
                ->searchableOptions()
                ->dependsOn(['status'])
                ->withCounts()
                ->optionPageSize(3)
                ->multiple()
                ->compactDisplay(':count danh mục'),
            SetFilter::make('status', 'Trạng thái')
                ->options(collect(ProductStatus::cases())->mapWithKeys(
                    fn (ProductStatus $status): array => [$status->value => $status->label()],
                )->all())
                ->multiple(),
            NumericFilter::make('price', 'Giá'),
            NumericFilter::make('stock', 'Tồn kho'),
            BooleanFilter::make('featured', 'Nổi bật'),
            DateFilter::make('launched_at', 'Ngày mở bán'),
        ];
    }

    /** @return array<int, Action> */
    public function actions(): array
    {
        return [
            Action::make(
                'toggle-featured',
                fn (?Product $product): string => $product?->featured
                    ? 'Bỏ nổi bật'
                    : 'Đánh dấu nổi bật',
            )
                ->row()
                ->icon('Star')
                ->handle(function (Model $model, Selection $_selection): bool {
                    if (! $model instanceof Product) {
                        return false;
                    }

                    return $model->update([
                        'featured' => ! $model->featured,
                    ]);
                }),
            Action::make('feature-selected', 'Đánh dấu nổi bật')
                ->bulk()
                ->icon('Stars')
                ->handleSelection(fn (Selection $selection) => $selection
                    ->query()
                    ->update(['featured' => true])),
            Action::make('archive', 'Đưa vào lưu trữ')
                ->rowAndBulk()
                ->icon('Archive')
                ->destructive()
                ->handleSelection(fn (Selection $selection) => $selection
                    ->query()
                    ->update(['status' => ProductStatus::Archived->value]))
                ->confirm(
                    [
                        'Lưu trữ sản phẩm này?',
                        'Lưu trữ :count sản phẩm?',
                        'Lưu trữ toàn bộ :count sản phẩm phù hợp?',
                    ],
                    [
                        'Sản phẩm sẽ chuyển sang trạng thái lưu trữ.',
                        ':count sản phẩm sẽ chuyển sang trạng thái lưu trữ.',
                        'Toàn bộ :count kết quả theo bộ lọc sẽ được lưu trữ.',
                    ],
                    'Lưu trữ :count',
                    'Hủy',
                ),
            Action::make('restore', 'Khôi phục')
                ->row()
                ->icon('ArchiveRestore')
                ->hidden(fn (?Product $product): bool => $product?->status !== ProductStatus::Archived)
                ->handle(function (Model $model, Selection $_selection): bool {
                    if (! $model instanceof Product) {
                        return false;
                    }

                    return $model->update([
                        'status' => ProductStatus::Active,
                    ]);
                }),
        ];
    }

    /** @return array<int, Export> */
    public function exports(): array
    {
        return [
            Export::make('all', 'Toàn bộ dữ liệu + tổng', 'products-all.csv')
                ->withSummaries(),
            Export::make('filtered', 'Kết quả theo bộ lọc + tổng', 'products-filtered.csv')
                ->filtered()
                ->visibleColumnsOnly()
                ->withSummaries(),
            Export::make('selected', 'Các dòng đã chọn', 'products-selected.csv')
                ->selected(),
            Export::make('queued', 'Theo bộ lọc + tổng · xử lý nền', 'products-queued.csv')
                ->filtered()
                ->withSummaries()
                ->chunkSize(250)
                ->queue(disk: 'public', expiresAfter: 86_400),
        ];
    }

    public function views(): Views
    {
        return Views::make()
            ->scopeUser(false)
            ->scopeTableName()
            ->includeSearch();
    }

    /**
     * @param  Builder<Model>  $query
     * @return Builder<Model>
     */
    public function selectableQuery(Builder $query): Builder
    {
        return $query->whereNull('locked_at');
    }

    public function isSelectable(Model $model): bool
    {
        return $model instanceof Product && $model->locked_at === null;
    }

    public function emptyState(): EmptyState
    {
        return EmptyState::make(
            'Chưa có sản phẩm',
            'Chạy seeder để tạo bộ dữ liệu demo cho bảng.',
            'Inbox',
        );
    }

    public function dataAttributesForModel(Model $model, array $data): array
    {
        return [
            'product-status' => $model instanceof Product ? $model->status->value : null,
            'locked' => $model instanceof Product && $model->locked_at !== null,
        ];
    }
}
