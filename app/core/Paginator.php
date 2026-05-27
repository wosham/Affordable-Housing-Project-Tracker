<?php
// Paginator — calculates pagination for data tables
class Paginator
{
    public int $total;
    public int $perPage;
    public int $currentPage;
    public int $lastPage;
    public int $offset;

    public function __construct(int $total, int $perPage = 20, int $currentPage = 1)
    {
        $this->total       = $total;
        $this->perPage     = $perPage;
        $this->currentPage = max(1, $currentPage);
        $this->lastPage    = max(1, (int) ceil($total / $perPage));
        $this->offset      = ($this->currentPage - 1) * $perPage;
    }

    public function hasPages(): bool  { return $this->lastPage > 1; }
    public function hasPrev(): bool   { return $this->currentPage > 1; }
    public function hasNext(): bool   { return $this->currentPage < $this->lastPage; }
    public function prevPage(): int   { return $this->currentPage - 1; }
    public function nextPage(): int   { return $this->currentPage + 1; }
}
