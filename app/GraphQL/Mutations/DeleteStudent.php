<?php

namespace App\GraphQL\Mutations;

use App\Services\StudentsService;

final class DeleteStudent
{
    private StudentsService $studentsService;

    public function __construct(StudentsService $studentsService)
    {
        $this->studentsService = $studentsService;
    }

    /**
     * @param  null  $_
     * @param  array{}  $args
     */
    public function __invoke($_, array $args)
    {
        return $this->studentsService->delete($args['id']);
    }
}
