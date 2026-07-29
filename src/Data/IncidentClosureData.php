<?php

declare(strict_types=1);

namespace VmeRetail\ServiceDeskify\Data;

final readonly class IncidentClosureData
{
    public function __construct(
        public string $symptom,
        public string $symptomSolution,
        public string $cause,
        public string $permanentResolution,
    ) {}

    /**
     * @return array{symptom: string, symptomSolution: string, cause: string, permanentResolution: string}
     */
    public function payload(): array
    {
        return [
            'symptom' => $this->symptom,
            'symptomSolution' => $this->symptomSolution,
            'cause' => $this->cause,
            'permanentResolution' => $this->permanentResolution,
        ];
    }
}
