<?php

namespace App\Models\Concerns;

use App\Models\Customer;
use App\Support\AuditContext;
use Spatie\Activitylog\Contracts\Activity;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Adds activity logging with audit-source enrichment to a model.
 *
 * Models using this trait MUST implement:
 *   - public static function auditLogName(): string  (e.g. "customer", "product")
 *   - public function auditSubjectLabel(): string    (e.g. "Customer Acme Corp")
 *
 * Models MAY override getDescriptionForEvent() for fully custom phrasing.
 */
trait LogsAuditActivity
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName(static::auditLogName());
    }

    public function getDescriptionForEvent(string $eventName): string
    {
        $verb = match ($eventName) {
            'created' => 'created',
            'updated' => 'updated',
            'deleted' => 'deleted',
            'restored' => 'restored',
            default => $eventName,
        };

        $actor = auth()->user()?->name ?? 'System';

        return "{$actor} {$verb} ".$this->auditSubjectLabel();
    }

    public function beforeActivityLogged(Activity $activity, string $eventName): void
    {
        /** @var AuditContext $ctx */
        $ctx = app(AuditContext::class);
        $data = $ctx->toArray();

        $activity->source = $data['source'];
        $activity->request_id = $data['request_id'];
        $activity->ip_address = $data['ip_address'];
        $activity->user_agent = $data['user_agent'];

        // Stamp the owning customer: prefer the subject's own customer_id (Sale, Product,
        // or Customer itself) and otherwise fall back to the actor's first linked customer
        // in the pivot so customer-role actions remain scoped.
        $activity->customer_id = $this->resolveAuditCustomerId() ?? self::actorCustomerId();
    }

    protected function resolveAuditCustomerId(): ?int
    {
        if (isset($this->customer_id)) {
            return (int) $this->customer_id;
        }
        if ($this instanceof Customer) {
            return (int) $this->id;
        }

        return null;
    }

    private static function actorCustomerId(): ?int
    {
        $user = auth()->user();
        if (! $user || ! method_exists($user, 'customerIds')) {
            return null;
        }
        $ids = $user->customerIds();

        return $ids[0] ?? null;
    }
}
