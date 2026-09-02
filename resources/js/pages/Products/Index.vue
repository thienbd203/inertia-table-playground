<script setup lang="ts">
import {
    Archive,
    Bookmark,
    Boxes,
    CheckCheck,
    Columns3,
    Download,
    Filter,
    LockKeyhole,
    MousePointer2,
    Search,
    Sparkles,
} from '@lucide/vue';
import { Head } from '@inertiajs/vue3';
import {
    DataTable,
    type TableItem,
    type TableResource,
} from '@musing/inertia-table-vue';
import type { Component } from 'vue';

type Product = TableItem & {
    id: number;
    name: string;
    sku: string;
    description: string | null;
    status: string;
    price: string;
    stock: number;
    rating: string;
    featured: boolean;
    locked_at: string | null;
    category: {
        id: number;
        name: string;
    };
};

type Summary = {
    total: number;
    active: number;
    featured: number;
    locked: number;
};

const props = defineProps<{
    products: TableResource<Product>;
    summary: Summary;
}>();

const summaryCards: Array<{
    key: keyof Summary;
    label: string;
    icon: Component;
}> = [
    { key: 'total', label: 'Tổng sản phẩm', icon: Boxes },
    { key: 'active', label: 'Đang bán', icon: CheckCheck },
    { key: 'featured', label: 'Nổi bật', icon: Sparkles },
    { key: 'locked', label: 'Không thể bulk-select', icon: LockKeyhole },
];

const features: Array<{ label: string; icon: Component }> = [
    { label: 'Search đa cột', icon: Search },
    { label: '7 kiểu filter', icon: Filter },
    { label: 'Ẩn và pin cột', icon: Columns3 },
    { label: 'Select all theo filter', icon: CheckCheck },
    { label: 'Shift-click range', icon: MousePointer2 },
    { label: 'Saved views', icon: Bookmark },
    { label: 'Bulk actions', icon: Archive },
    { label: 'CSV sync và queued', icon: Download },
];

const currency = new Intl.NumberFormat('vi-VN', {
    style: 'currency',
    currency: 'VND',
    maximumFractionDigits: 0,
});

function formatCurrency(value: string): string {
    return currency.format(Number(value));
}
</script>

<template>
    <Head title="Product table feature gallery" />

    <main class="bg-background text-foreground min-h-screen">
        <div
            class="mx-auto flex w-full max-w-[1600px] flex-col gap-8 px-4 py-8 sm:px-6 lg:px-8"
        >
            <header
                class="overflow-hidden rounded-3xl border border-teal-950/10 bg-teal-950 px-6 py-8 text-white shadow-xl shadow-teal-950/10 sm:px-8 lg:px-10"
            >
                <div
                    class="flex flex-col gap-8 lg:flex-row lg:items-end lg:justify-between"
                >
                    <div class="flex max-w-3xl flex-col gap-4">
                        <div
                            class="flex w-fit items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs font-medium tracking-wide text-teal-50"
                        >
                            <Sparkles class="size-3.5" />
                            Local package playground
                        </div>
                        <div class="flex flex-col gap-3">
                            <h1
                                class="text-3xl font-semibold tracking-tight sm:text-4xl lg:text-5xl"
                            >
                                Inertia Table feature gallery
                            </h1>
                            <p
                                class="max-w-2xl text-sm leading-6 text-teal-50/75 sm:text-base"
                            >
                                Một resource sản phẩm thật để thử toàn bộ vòng
                                đời của bảng: query server-side, selection xuyên
                                trang, thao tác hàng loạt, saved views và
                                export.
                            </p>
                        </div>
                    </div>

                    <div
                        class="grid grid-cols-2 gap-2 sm:grid-cols-4 lg:min-w-[520px]"
                    >
                        <div
                            v-for="card in summaryCards"
                            :key="card.key"
                            class="flex flex-col gap-3 rounded-2xl border border-white/10 bg-white/8 p-4 backdrop-blur"
                        >
                            <component
                                :is="card.icon"
                                class="size-4 text-teal-200"
                            />
                            <div>
                                <p class="text-2xl font-semibold tabular-nums">
                                    {{ props.summary[card.key] }}
                                </p>
                                <p class="text-xs text-teal-50/65">
                                    {{ card.label }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <section class="flex flex-wrap gap-2" aria-label="Tính năng demo">
                <span
                    v-for="feature in features"
                    :key="feature.label"
                    class="bg-card text-muted-foreground inline-flex items-center gap-2 rounded-full border px-3 py-1.5 text-xs font-medium shadow-sm"
                >
                    <component
                        :is="feature.icon"
                        class="text-primary size-3.5"
                    />
                    {{ feature.label }}
                </span>
            </section>

            <section>
                <div class="products-demo">
                    <DataTable :resource="props.products">
                        <template #cell(name)="{ item }">
                            <div class="flex min-w-0 flex-col gap-1 py-0.5">
                                <span class="text-foreground font-medium">
                                    {{ item.name }}
                                </span>
                                <span
                                    v-if="item.description"
                                    class="text-muted-foreground line-clamp-1 text-xs"
                                >
                                    {{ item.description }}
                                </span>
                            </div>
                        </template>

                        <template #cell(price)="{ item }">
                            <span class="font-medium tabular-nums">
                                {{ formatCurrency(item.price) }}
                            </span>
                        </template>

                        <template #cell(stock)="{ item }">
                            <span
                                class="tabular-nums"
                                :class="
                                    item.stock < 20
                                        ? 'font-semibold text-amber-700'
                                        : 'text-foreground'
                                "
                            >
                                {{ item.stock }}
                            </span>
                        </template>
                    </DataTable>
                </div>
            </section>

            <aside
                class="bg-card/70 text-muted-foreground flex flex-col gap-3 rounded-2xl border border-dashed p-5 text-sm sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="flex items-start gap-3">
                    <Download class="text-primary mt-0.5 size-4 shrink-0" />
                    <p class="leading-6">
                        Export nền dùng queue <code>database</code>, chunk 250
                        dòng và tự polling trạng thái. Chạy worker để thử luồng
                        hoàn chỉnh.
                    </p>
                </div>
                <code
                    class="bg-muted text-foreground shrink-0 rounded-lg px-3 py-2 text-xs"
                    >php artisan queue:work</code
                >
            </aside>
        </div>
    </main>
</template>
