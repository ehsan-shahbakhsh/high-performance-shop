<?php

namespace App\Traits;

use RuntimeException;

trait RestoreOrFail
{
    /**
     * Restore the model or throw an exception on failure.
     */
    public function restoreOrFail(): bool
    {
        if (!method_exists($this, 'restore')) {
            throw new RuntimeException(
                'Model [' . static::class . '] does not use SoftDeletes.'
            );
        }

        $restored = $this->restore();

        if ($restored === false) {
            throw new RuntimeException(
                'Failed to restore model [' . static::class . '] with ID: ' . $this->getKey()
            );
        }

        return true;
    }
}
