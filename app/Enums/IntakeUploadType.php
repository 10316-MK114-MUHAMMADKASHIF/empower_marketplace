<?php

namespace App\Enums;

enum IntakeUploadType: string
{
    // Retired 2026-08-21 — no longer offered in the questionnaire catalog, but kept as valid
    // backing values so historical IntakeUpload rows created under these types remain readable.
    case PracticeIntake = 'practice_intake';
    case OshaQuestionnaire = 'osha_questionnaire';
    case EmployeeHandbookQuestionnaire = 'employee_handbook_questionnaire';
    case RevenueCycleQuestionnaire = 'revenue_cycle_questionnaire';
    case EmergencyOperationsQuestionnaire = 'emergency_operations_questionnaire';

    case ComplianceEthicsQuestionnaire = 'compliance_ethics_questionnaire';
    case HipaaBusinessAssociateQuestionnaire = 'hipaa_business_associate_questionnaire';
    case HipaaPrivacyQuestionnaire = 'hipaa_privacy_questionnaire';
    case HipaaSecurityQuestionnaire = 'hipaa_security_questionnaire';

    public function promptLabel(): string
    {
        return match ($this) {
            self::PracticeIntake => 'practice intake',
            self::OshaQuestionnaire => 'OSHA questionnaire',
            self::EmployeeHandbookQuestionnaire => 'employee handbook questionnaire',
            self::RevenueCycleQuestionnaire => 'revenue cycle & billing compliance questionnaire',
            self::EmergencyOperationsQuestionnaire => 'emergency operations plan questionnaire',
            self::ComplianceEthicsQuestionnaire => 'compliance and ethics practice workflow questionnaire',
            self::HipaaBusinessAssociateQuestionnaire => 'HIPAA business associate practice workflow questionnaire',
            self::HipaaPrivacyQuestionnaire => 'HIPAA privacy practice workflow questionnaire',
            self::HipaaSecurityQuestionnaire => 'HIPAA security practice workflow questionnaire',
        };
    }
}
