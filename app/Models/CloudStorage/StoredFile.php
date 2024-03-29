<?php

namespace App\Models\CloudStorage;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Support\Facades\Storage;

class StoredFile extends Model
{
    use HasFactory;

    use Prunable;

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';


    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'folder',
        'delete_when',
    ];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'files';

    /**
     * Get the prunable model query.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function prunable()
    {
        return static::where('delete_when', '<=', now());
    }

    public function pruning()
    {
        Storage::delete($this->location);
        if (!StoredFile::where('folder', '=', $this->folder)->where('id', '!=', $this->id)->first()) {
            Storage::deleteDirectory(dirname($this->location));
        }
    }
}
