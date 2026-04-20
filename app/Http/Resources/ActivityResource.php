<?php

namespace App\Http\Resources;

use App\Enums\AuditSource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Spatie\Activitylog\Models\Activity;

/**
 * Transforms a Spatie activity log entry into its API payload shape.
 *
 * @mixin Activity
 */
class ActivityResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $source = $this->source ? AuditSource::from($this->source) : null;

        return [
            'id' => $this->id,
            'log_name' => $this->log_name,
            'event' => $this->event,
            'description' => $this->description,
            'source' => $source?->value,
            'source_label' => $source?->label(),
            'request_id' => $this->request_id,
            'ip_address' => $this->ip_address,
            'user_agent' => $this->user_agent,
            'subject_type' => $this->subject_type,
            'subject_id' => $this->subject_id,
            'causer' => $this->whenLoaded('causer', fn () => $this->causer ? [
                'id' => $this->causer->id,
                'name' => $this->causer->name,
                'email' => $this->causer->email,
            ] : null),
            'changes' => $this->getAttribute('attribute_changes') ?? $this->properties?->toArray(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
