<?php

namespace App\Traits;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

trait LogsActivity
{
    protected static function bootLogsActivity()
    {
        static::created(function ($model) {
            $model->logActivity('created', null, $model->getAttributes());
        });

        static::updated(function ($model) {
            $model->logActivity('updated', $model->getOriginal(), $model->getChanges());
        });

        static::deleted(function ($model) {
            $modelId = $model->id ?? $model->getOriginal('id');
            $modelAttributes = $model->getAttributes();
            if (empty($modelAttributes)) {
                $modelAttributes = $model->getOriginal();
            }
            $model->logActivity('deleted', $modelAttributes, null, $modelId);
        });
    }

    public function logActivity(string $action, $oldValue = null, $newValue = null, $modelId = null)
    {
        $actionMap = [
            'created' => $this->getCreatedAction(),
            'updated' => $this->getUpdatedAction(),
            'deleted' => $this->getDeletedAction(),
        ];

        $mappedAction = $actionMap[$action] ?? $action;

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => $mappedAction,
            'model_type' => get_class($this),
            'model_id' => $modelId ?? $this->id,
            'old_value' => $oldValue,
            'new_value' => $newValue,
        ]);
    }

    protected function getCreatedAction(): string
    {
        if ($this instanceof \App\Models\Task) {
            return 'task_created';
        }
        if ($this instanceof \App\Models\TaskComment) {
            return 'comment_added';
        }
        if ($this instanceof \App\Models\TaskImage || $this instanceof \App\Models\CommentImage) {
            return 'image_uploaded';
        }
        return 'created';
    }

    protected function getUpdatedAction(): string
    {
        if ($this instanceof \App\Models\Task) {
            if (isset($this->getChanges()['status'])) {
                return 'task_status_changed';
            }
            if (isset($this->getChanges()['assigned_user_id'])) {
                return 'task_assigned';
            }
        }
        return 'updated';
    }

    protected function getDeletedAction(): string
    {
        if ($this instanceof \App\Models\Task) {
            return 'task_deleted';
        }
        return 'deleted';
    }
}

