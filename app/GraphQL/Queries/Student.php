<?php

namespace App\GraphQL\Queries;

use App\Services\StudentsService;

final class Student
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
        return $this->studentsService->findOne($args['id']);
    }
}
