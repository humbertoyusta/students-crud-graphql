<?php

namespace App\Services;

use App\Exceptions\CustomException;
use App\Models\Student;

class StudentsService
{
    /**
     * @param $id
     * @return the student with given $id
     * @throws CustomException 'Not Found (404)' when user is not found
     */
    public function findOne($id)
    {
        $student = Student::find($id);

        if ($student)
            return $student;
        else
            throw new CustomException('Not Found (404)', 'User with id '.$id.' not found');
    }

    /**
     * @param $email
     * @return the student with given $email or null if it doesn't exists
     */
    public function findOneByEmail($email)
    {
        return Student::where('email', $email)->first();
    }

    /**
     * @return a list of all students
     */
    public function findAll()
    {
        return Student::all();
    }

    /**
     * @param array $studentData -- an array with all the data of the student
     * @return $student -- the student added to the database
     * @throws a validation error if validation fails
     * @throws CustomException 'Conflict (409)' - if there is another user with the same email
     */
    public function create(array $studentData)
    {
        if ($this->findOneByEmail($studentData['email']))
        {
            throw new CustomException('Conflict (409)', 'There is another user with the same email');
        }

        $student = new Student($studentData);

        $student -> save();
        return $student;
    }

    /**
     * @param $id -- id of the student to edit
     * @param array $studentData -- an array with all the data of the student
     * @return $student -- the student added to the database
     * @throws a validation error if validation fails
     * @throws CustomException 'Conflict (409)' - if there is another user with the same new email
     */
    public function update(array $studentData)
    {
        $student = $this -> findOne($studentData['id']);

        if (array_key_exists('email', $studentData))
        {
            $studentWithSameEmail = $this -> findOneByEmail($studentData['email']);
            if ($studentWithSameEmail && $studentWithSameEmail->id != $studentData['id'])
            {
                throw new CustomException('Conflict (409)', 'There is another user with the same email');
            }
        }

        $student -> update($studentData);

        return $this -> findOne($studentData['id']);
    }

    /**
     * deletes a student
     * @param $id
     * @throws CustomException 'Not Found (404)' when user is not found
     */
    public function delete($id)
    {
        $student = $this -> findOne($id);

        $student->delete();

        return $student;
    }
}