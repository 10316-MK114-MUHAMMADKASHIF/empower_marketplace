<?php

namespace App\Enums;

enum IntakeUploadType: string
{
    case PracticeIntake = 'practice_intake';
    case OshaQuestionnaire = 'osha_questionnaire';
    case EmployeeHandbookQuestionnaire = 'employee_handbook_questionnaire';
    case RevenueCycleQuestionnaire = 'revenue_cycle_questionnaire';
    case EmergencyOperationsQuestionnaire = 'emergency_operations_questionnaire';

    public function promptLabel(): string
    {
        return match ($this) {
            self::PracticeIntake => 'practice intake',
            self::OshaQuestionnaire => 'OSHA questionnaire',
            self::EmployeeHandbookQuestionnaire => 'employee handbook questionnaire',
            self::RevenueCycleQuestionnaire => 'revenue cycle & billing compliance questionnaire',
            self::EmergencyOperationsQuestionnaire => 'emergency operations plan questionnaire',
        };
    }
}
