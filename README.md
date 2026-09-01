# Inertia Table Playground

Ứng dụng Laravel/Inertia Vue dùng để phát triển và kiểm thử package
`musing/inertia-table` ngay từ source local ở thư mục anh em
`../toolbelt-inertia-table`.

## Chạy local

```bash
composer install
npm install
php artisan migrate:fresh --seed
php artisan storage:link
composer run dev
```

Để thử export xử lý nền, chạy thêm worker:

```bash
php artisan queue:work
```

## Phạm vi demo

- Full, simple và cursor pagination trên cùng một resource.
- Search, text/set/numeric/boolean/date filters và sort server-side.
- Column visibility, sticky header, pin cột trái/phải và Saved Views.
- Header select-all trên toàn bộ kết quả theo filter, partial state,
  Shift-click range và dòng không đủ điều kiện bulk-select.
- Row actions, bulk actions, confirmation theo đúng số lượng selection.
- CSV cho toàn bộ, filtered, selected và queued filtered export.
- Cell slots, Vietnamese locale và icon resolver của host application.

Source PHP và Vue đều được symlink từ package local, còn Tailwind scan renderer
qua đường dẫn package trong `node_modules` giống một consumer cài từ release.

## Kiểm tra

```bash
php artisan test --compact
npm run types:check
npm run check
npm run build
```
