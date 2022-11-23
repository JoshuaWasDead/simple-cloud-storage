<?php

namespace App\Providers;

use App\Models\CloudStorage\CloudStorageInterface;
use App\Models\CloudStorage\FileSystemStorage;
use Illuminate\Support\ServiceProvider;

class CloudStorageProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(CloudStorageInterface::class, FileSystemStorage::class);
    }
}
