<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Employee;
use App\Traits\AttendanceTraits;
use App\Traits\BaseTraits;
use App\Traits\EmployeeTraits;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class AttendanceController extends Controller
{
    use EmployeeTraits, BaseTraits, AttendanceTraits;

    public function recordArrival($id)
    {
        try {
            $employee = $this->getEmployeeById($id);

            $alreadyRecorded = Attendance::where('employee_id', $id)
                ->whereDate('arrival_time', $this->getDateLimit())
                ->exits();

            if ($alreadyRecorded) {
                throw new BadRequestException('Arrival already recorded for the employee today');
            }

            $attendance = Attendance::create([
                'employee_id' => $id,
                'arrival_time' => now(),
            ]);

            $this->sendAttendanceEmail($employee, 'arrival');

            return $this->respondSuccess($attendance, 'Arrival recorded successfully', 201);
        } catch (\Exception $exception) {
            return $this->respondExceptionError($exception);
        }
    }

    public function recordDeparture(Request $request, $id)
    {
        try {
            $employee = $this->getEmployeeById($id);

            $attendance_id = $request->input('attendance_id');

            $alreadyRecorded = Attendance::where('employee_id', $id)
                ->whereDate('departure_time', $this->getDateLimit())
                ->exists();

            if ($alreadyRecorded) {
                throw new BadRequestException('Departure already recorded for the employee today');
            }

            $attendance = Attendance::where('employee_id', $id)
                ->when($attendance_id, fn ($query) => $query->where('id', $id))
                ->when(empty($attendance_id), fn ($query) => $query->whereNull('departure_time'))
                ->latest()
                ->first();

            if (empty($attendance)) {
                throw new BadRequestException('No arrival recorded for the employee');
            }

            $attendance->update([
                'departure_time' => now(),
            ]);

            $this->sendAttendanceEmail($employee, 'departure');

            return $this->respondSuccess($attendance, 'Arrival recorded successfully', 201);
        } catch (\Exception $exception) {
            return $this->respondExceptionError($exception);
        }
    }

    private function sendAttendanceEmail(Employee $employee, $type)
    {
        // You need to define your mail logic here, possibly using Laravel Mailables and queues
        // For simplicity, let's assume you have a Mailable named AttendanceNotification

        // Mail::to($employee->email)->queue(new AttendanceNotification($employee, $type));
    }
}
