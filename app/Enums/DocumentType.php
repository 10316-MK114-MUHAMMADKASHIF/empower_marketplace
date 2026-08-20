<?php

namespace App\Enums;

enum DocumentType: string
{
    case EmployeeHandbookBasic = 'employee_handbook_basic';
    case EmployeeHandbookFull = 'employee_handbook_full';
    case OshaSafetyPlan = 'osha_safety_plan';
    case HrPolicyManual = 'hr_policy_manual';
    case HipaaPrivacyPolicy = 'hipaa_privacy_policy';
    case OshaLocationReport = 'osha_location_report';
    case CustomComplianceDocument = 'custom_compliance_document';
    case RevenueCycleBillingManual = 'revenue_cycle_billing_manual';
    case ComplianceEthicsManual = 'compliance_ethics_manual';
    case EmergencyOperationsPlan = 'emergency_operations_plan';
    case HipaaBusinessAssociateManual = 'hipaa_business_associate_manual';
    case HipaaSecurityManual = 'hipaa_security_manual';

    public function label(): string
    {
        return match ($this) {
            self::EmployeeHandbookBasic => 'Employee Handbook (Basic)',
            self::EmployeeHandbookFull => 'Employee Handbook (Full)',
            self::OshaSafetyPlan => 'OSHA Safety Plan',
            self::HrPolicyManual => 'HR Policy Manual',
            self::HipaaPrivacyPolicy => 'HIPAA Privacy Policy',
            self::OshaLocationReport => 'OSHA Location Report',
            self::CustomComplianceDocument => 'Custom Compliance Document',
            self::RevenueCycleBillingManual => 'Revenue Cycle & Billing Compliance Manual',
            self::ComplianceEthicsManual => 'Compliance & Ethics Manual',
            self::EmergencyOperationsPlan => 'Emergency Operations Plan',
            self::HipaaBusinessAssociateManual => 'HIPAA Business Associate Manual',
            self::HipaaSecurityManual => 'HIPAA Security Manual',
        };
    }

    /** Whether this document's deliverable is the merged .docx template itself, with no PDF conversion. */
    public function isDocxOnly(): bool
    {
        return match ($this) {
            self::RevenueCycleBillingManual,
            self::ComplianceEthicsManual,
            self::EmergencyOperationsPlan,
            self::HipaaBusinessAssociateManual,
            self::HipaaSecurityManual => true,
            default => false,
        };
    }

    /** @return array<int, self> */
    public static function forTier(PackageTier $tier): array
    {
        return match ($tier) {
            PackageTier::Essential => [
                self::EmployeeHandbookBasic,
                self::OshaSafetyPlan,
            ],
            PackageTier::Professional => [
                self::EmployeeHandbookFull,
                self::OshaSafetyPlan,
                self::HrPolicyManual,
            ],
            PackageTier::Advanced => [
                self::EmployeeHandbookFull,
                self::OshaSafetyPlan,
                self::HrPolicyManual,
                self::HipaaPrivacyPolicy,
                self::OshaLocationReport,
            ],
            PackageTier::Complete => [
                self::EmployeeHandbookFull,
                self::OshaSafetyPlan,
                self::HrPolicyManual,
                self::HipaaPrivacyPolicy,
                self::OshaLocationReport,
                self::CustomComplianceDocument,
                self::RevenueCycleBillingManual,
                self::ComplianceEthicsManual,
                self::EmergencyOperationsPlan,
                self::HipaaBusinessAssociateManual,
                self::HipaaSecurityManual,
            ],
        };
    }

    /** Whether this document is generated once per OSHA location */
    public function isPerLocation(): bool
    {
        return $this === self::OshaLocationReport;
    }
}
