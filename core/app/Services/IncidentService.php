<?php

namespace App\Services;

use App\Data\IncidentData;
use App\Enums\AlertSeverity;
use App\Enums\IncidentPriority;
use App\Enums\IncidentStatus;
use App\Events\IncidentUpdated;
use App\Models\Alert;
use App\Models\Incident;
use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

/**
 * IncidentService — cycle de vie des incidents ORION.
 *
 * Machine a etats :
 *   open → assigned → in_progress → resolved → closed
 *   open peut aussi passer directement a in_progress (prise en charge rapide).
 */
class IncidentService
{
    /** @var array<string, array<int, IncidentStatus>> */
    private const ALLOWED_TRANSITIONS = [
        'open' => [IncidentStatus::ASSIGNED, IncidentStatus::IN_PROGRESS],
        'assigned' => [IncidentStatus::IN_PROGRESS, IncidentStatus::RESOLVED],
        'in_progress' => [IncidentStatus::RESOLVED],
        'resolved' => [IncidentStatus::CLOSED],
        'closed' => [],
    ];

    /**
     * @param  array{status?: string, priority?: string, assigned_to?: int, device_id?: int}  $filters
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->applyFilters(
            Incident::query()->with(['device', 'alert', 'creator', 'assignee', 'resolver', 'closer']),
            $filters
        )
            ->latest('opened_at')
            ->paginate($perPage);
    }

    public function create(IncidentData $data, User $creator): Incident
    {
        if ($data->alert_id && Incident::where('alert_id', $data->alert_id)->exists()) {
            throw ValidationException::withMessages([
                'alert_id' => 'Cette alerte est deja liee a un incident.',
            ]);
        }

        $incident = Incident::create([
            'title' => $data->title,
            'description' => $data->description,
            'status' => IncidentStatus::OPEN,
            'priority' => $data->priority->value,
            'device_id' => $data->device_id,
            'alert_id' => $data->alert_id,
            'created_by' => $creator->id,
            'opened_at' => now(),
        ]);

        IncidentUpdated::dispatch($incident->fresh(['device', 'alert']), 'created');

        return $incident->fresh(['device', 'alert', 'creator']);
    }

    /**
     * Escalade manuelle ou automatique d'une alerte vers un incident.
     */
    public function createFromAlert(Alert $alert, User $creator, bool $auto = false): Incident
    {
        if (Incident::where('alert_id', $alert->id)->exists()) {
            throw ValidationException::withMessages([
                'alert_id' => 'Un incident existe deja pour cette alerte.',
            ]);
        }

        $incident = Incident::create([
            'title' => $auto
                ? "[Auto] {$alert->title}"
                : "Incident — {$alert->title}",
            'description' => $alert->message,
            'status' => IncidentStatus::OPEN,
            'priority' => IncidentPriority::fromAlertSeverity($alert->severity)->value,
            'device_id' => $alert->device_id,
            'alert_id' => $alert->id,
            'created_by' => $creator->id,
            'opened_at' => now(),
        ]);

        IncidentUpdated::dispatch(
            $incident->fresh(['device', 'alert']),
            $auto ? 'auto_escalated' : 'escalated'
        );

        return $incident->fresh(['device', 'alert', 'creator']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Incident $incident, array $data): Incident
    {
        if ($incident->status === IncidentStatus::CLOSED) {
            abort(422, 'Impossible de modifier un incident ferme.');
        }

        $incident->fill(array_filter([
            'title' => $data['title'] ?? null,
            'description' => array_key_exists('description', $data) ? $data['description'] : null,
            'priority' => $data['priority'] ?? null,
            'device_id' => array_key_exists('device_id', $data) ? $data['device_id'] : null,
        ], fn ($value) => ! is_null($value)));

        $incident->save();

        IncidentUpdated::dispatch($incident->fresh(['device', 'alert']), 'updated');

        return $incident->fresh(['device', 'alert', 'creator', 'assignee']);
    }

    public function assign(Incident $incident, User $assignee, User $actor): Incident
    {
        $this->assertTransition($incident, IncidentStatus::ASSIGNED);

        $incident->update([
            'status' => IncidentStatus::ASSIGNED,
            'assigned_to' => $assignee->id,
            'assigned_at' => now(),
        ]);

        IncidentUpdated::dispatch($incident->fresh(['device', 'assignee']), 'assigned');

        return $incident->fresh(['device', 'alert', 'assignee']);
    }

    public function start(Incident $incident, User $actor): Incident
    {
        $this->assertTransition($incident, IncidentStatus::IN_PROGRESS);

        $incident->update([
            'status' => IncidentStatus::IN_PROGRESS,
            'started_at' => now(),
            'assigned_to' => $incident->assigned_to ?? $actor->id,
        ]);

        IncidentUpdated::dispatch($incident->fresh(['device', 'assignee']), 'started');

        return $incident->fresh(['device', 'alert', 'assignee']);
    }

    public function resolve(Incident $incident, User $actor, ?string $notes = null): Incident
    {
        $this->assertTransition($incident, IncidentStatus::RESOLVED);

        $incident->update([
            'status' => IncidentStatus::RESOLVED,
            'resolved_at' => now(),
            'resolved_by' => $actor->id,
            'resolution_notes' => $notes,
        ]);

        IncidentUpdated::dispatch($incident->fresh(['device', 'resolver']), 'resolved');

        return $incident->fresh(['device', 'alert', 'resolver']);
    }

    public function close(Incident $incident, User $actor): Incident
    {
        $this->assertTransition($incident, IncidentStatus::CLOSED);

        $incident->update([
            'status' => IncidentStatus::CLOSED,
            'closed_at' => now(),
            'closed_by' => $actor->id,
        ]);

        IncidentUpdated::dispatch($incident->fresh(['device', 'closer']), 'closed');

        return $incident->fresh(['device', 'alert', 'closer']);
    }

    public function delete(Incident $incident): void
    {
        if ($incident->status !== IncidentStatus::CLOSED) {
            abort(422, 'Seuls les incidents fermes peuvent etre supprimes.');
        }

        $incident->delete();
    }

    /**
     * Escalade auto : uniquement alertes critical sans incident existant.
     */
    public function shouldAutoEscalate(Alert $alert): bool
    {
        return $alert->severity === AlertSeverity::CRITICAL
            && ! Incident::where('alert_id', $alert->id)->exists();
    }

    /**
     * Utilisateur systeme pour les escalades automatiques (admin par defaut).
     */
    public function systemUser(): User
    {
        return User::query()->role(RoleName::ADMIN->value)->first()
            ?? User::query()->firstOrFail();
    }

    private function assertTransition(Incident $incident, IncidentStatus $target): void
    {
        $current = $incident->status->value;
        $allowed = self::ALLOWED_TRANSITIONS[$current] ?? [];

        if (! in_array($target, $allowed, true)) {
            throw ValidationException::withMessages([
                'status' => "Transition impossible : {$current} → {$target->value}.",
            ]);
        }
    }

    /**
     * @param  Builder<Incident>  $query
     * @param  array{status?: string, priority?: string, assigned_to?: int, device_id?: int}  $filters
     * @return Builder<Incident>
     */
    private function applyFilters(Builder $query, array $filters): Builder
    {
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (! empty($filters['assigned_to'])) {
            $query->where('assigned_to', $filters['assigned_to']);
        }

        if (! empty($filters['device_id'])) {
            $query->where('device_id', $filters['device_id']);
        }

        return $query;
    }
}
