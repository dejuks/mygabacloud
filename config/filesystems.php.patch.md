# Add this disk to config/filesystems.php

Inside the `'disks' => [ ... ]` array, add:

```php
'private' => [
    'driver' => 'local',
    'root'   => storage_path('app/private'),
    'throw'  => false,
],
```

This is where sellable product ZIPs are stored. It has **no** public URL, so
the only way to reach a file is through the license-gated download route in
`LibraryController`.

Your `public` disk (already in Laravel by default) holds thumbnails and
preview images. Run `php artisan storage:link` once so those are servable.
