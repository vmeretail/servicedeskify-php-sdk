<?php

declare(strict_types=1);

namespace VmeRetail\ServiceDeskify\Data;

use Carbon\CarbonImmutable;
use VmeRetail\ServiceDeskify\Enums\IncidentPriority;
use VmeRetail\ServiceDeskify\Exceptions\UnexpectedResponseException;

final readonly class IncidentData
{
    /**
     * @param  array<int, AttachmentData>  $attachments
     * @param  CarbonImmutable|null  $createdDate  The API returns this as a naive UTC timestamp.
     */
    public function __construct(
        public int $id,
        public string $title,
        public string $summary,
        public ?UserData $reporter,
        public ?LocationSummaryData $location,
        public ?UserData $assignee,
        public ?string $status,
        public ?string $workflowState,
        public ?CarbonImmutable $createdDate,
        public ?IncidentPriority $priority,
        public ?string $symptom,
        public ?string $symptomSolution,
        public ?string $cause,
        public ?string $permanentResolution,
        public array $attachments,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        if (! array_key_exists('id', $data) || ! is_numeric($data['id'])) {
            throw new UnexpectedResponseException('The incident response is missing a valid [id].');
        }

        $reporter = DataMapper::array($data['reporter'] ?? null);
        $location = DataMapper::array($data['location'] ?? null);
        $assignee = DataMapper::array($data['assignee'] ?? null);
        $attachments = is_array($data['attachments'] ?? null) ? $data['attachments'] : [];

        return new self(
            id: (int) $data['id'],
            title: (string) ($data['title'] ?? ''),
            summary: (string) ($data['summary'] ?? ''),
            reporter: $reporter === null ? null : UserData::fromArray($reporter),
            location: $location === null ? null : LocationSummaryData::fromArray($location),
            assignee: $assignee === null ? null : UserData::fromArray($assignee),
            status: DataMapper::string($data['status'] ?? null),
            workflowState: DataMapper::string($data['workflow_state'] ?? null),
            createdDate: DataMapper::date($data['created_date'] ?? null),
            priority: IncidentPriority::fromApiValue($data['priority'] ?? null),
            symptom: DataMapper::string($data['symptom'] ?? null),
            symptomSolution: DataMapper::string($data['symptom_solution'] ?? null),
            cause: DataMapper::string($data['cause'] ?? null),
            permanentResolution: DataMapper::string($data['permanent_resolution'] ?? null),
            attachments: array_values(array_map(
                static fn (array $attachment): AttachmentData => AttachmentData::fromArray($attachment),
                array_filter($attachments, 'is_array'),
            )),
        );
    }
}
