# TO Report Field Requirements Analysis for Dual-Domain System

This document provides a comprehensive analysis of all 33 TO (Tape Out) report fields in the dual-domain system, identifying their source domains, data types, and characteristics.

## Executive Summary

The TO Report system integrates data from two domains:
- **IT Domain**: Contains project management, personnel assignments, and design specifications
- **NX Domain**: Contains DV regression results, version control, and coverage metrics

All 33 fields are aggregated into a final TO Summary report that provides a complete view of tape-out readiness.

## Field Analysis by Domain

### IT Domain Fields (17 fields)

| Field Name | Data Type | Constraints | Entry Type | Notes |
|------------|-----------|-------------|------------|-------|
| **Index** | VARCHAR(50) | Not null | Auto-generated | Task index from dv_tasks table |
| **Project** | VARCHAR(100) | Not null, Unique | Manually entered | Primary project identifier |
| **SPIP_IP** | VARCHAR(100) | - | Manually entered | IP classification from allproject.xlsx |
| **IP** | VARCHAR(100) | - | Manually entered | IP name/identifier |
| **IP Postfix** | VARCHAR(50) | - | Manually entered | IP variant identifier (e.g., "2x1", "support 4/4") |
| **IP Subtype** | VARCHAR(50) | Default: 'default' | Manually entered | IP subtype classification |
| **Alternative Name** | VARCHAR(100) | - | Manually entered | Alternative IP designation |
| **DV** | VARCHAR(100) | - | Manually entered | DV Engineer name/ID |
| **DD** | VARCHAR(100) | - | Manually entered | Digital Designer name |
| **BU** | VARCHAR(10) | - | Manually entered | Business Unit (e.g., "CN", "PC") |
| **AD** | VARCHAR(100) | - | Manually entered | Analog Designer name |
| **SPIP url** | VARCHAR(500) | - | Manually entered | JIRA/SPIP tracking URL |
| **Wiki url** | VARCHAR(500) | - | Manually entered | Project wiki documentation URL |
| **spec version** | VARCHAR(50) | - | Manually entered | Specification version number |
| **spec path** | VARCHAR(500) | - | Manually entered | Path to specification document |
| **Inherit from IP** | VARCHAR(100) | - | Manually entered | Parent IP for inheritance |
| **re-use IP** | VARCHAR(100) | - | Manually entered | IP reuse indicator ("Y"/"N") |

### NX Domain Fields (16 fields)

| Field Name | Data Type | Constraints | Entry Type | Notes |
|------------|-----------|-------------|------------|-------|
| **Line Coverage** | DECIMAL(5,2) | 0-100% | Auto-generated | Code line coverage percentage |
| **FSM Coverage** | DECIMAL(5,2) | 0-100% | Auto-generated | Finite State Machine coverage |
| **Interface Toggle Coverage** | DECIMAL(5,2) | 0-100% | Auto-generated | Interface signal toggle coverage |
| **Toggle Coverage** | DECIMAL(5,2) | 0-100% | Auto-generated | General toggle coverage |
| **Coverage Report Path** | VARCHAR(500) | - | Auto-generated | Path to coverage HTML report |
| **sanity SVN** | VARCHAR(500) | - | Manually entered | SVN path for sanity tests |
| **sanity SVN ver** | VARCHAR(100) | - | Auto-generated | SVN revision for sanity |
| **release SVN** | VARCHAR(500) | - | Manually entered | SVN path for release |
| **release SVN ver** | VARCHAR(100) | - | Auto-generated | SVN revision for release |
| **git path** | VARCHAR(500) | - | Manually entered | Git repository URL |
| **git version** | VARCHAR(100) | - | Auto-generated | Git commit hash (40 chars) |
| **golden checklist** | VARCHAR(500) | - | Manually entered | Path to golden checklist file |
| **golden checklist version** | VARCHAR(100) | - | Manually entered | Checklist version number |
| **TO Date** | DATE/TIMESTAMP | - | Manually entered | Tape-out target date |
| **RTL last update timestamp** | TIMESTAMP | - | Auto-generated | Last RTL modification time |
| **TO report creation timestamp** | TIMESTAMP | - | Auto-generated | Report generation timestamp |

## Data Flow Architecture

### IT Domain Sources:
1. **dv_tasks.xlsx** - Primary source for task assignments and project tracking
2. **allproject.xlsx** - Source for SPIP_IP classifications
3. **it-domain-to-be-added.json** - Supplemental data for additional fields

### NX Domain Sources:
1. **MySQL_rdc_dv_tape_out_lookup.csv** - TO date information
2. **MySQL_rdc_dv_report_hist.csv** - Historical DV report data
3. **nx-domain-to-be-added.json** - Coverage metrics and version control data

## Special Handling Requirements

### Cross-Domain Dependencies:
- **Project** field serves as the primary key linking data between domains
- **TO Date** appears in both domains but NX domain takes precedence

### Data Type Considerations:
1. **Coverage Metrics** - Must validate percentage values (0-100)
2. **URLs** - Should validate format for SPIP, Wiki, SVN, and Git paths
3. **Timestamps** - Consistent format required (YYYY-MM-DD HH:MM:SS)
4. **Version Numbers** - Git versions are 40-character hashes
5. **NaN Handling** - Alternative Name field often contains NaN values

### Manual vs Automated Fields:
- **17 fields** require manual entry (primarily IT domain)
- **16 fields** are automatically generated or extracted (primarily NX domain)

### Database Storage:
- IT Domain uses MySQL with separate `projects` and `dv_tasks` tables
- NX Domain uses MySQL with `coverage_reports`, `version_control`, and `imported_it_data` tables
- Final TO Summary aggregates data through database views

## Implementation Notes

1. **Data Validation**: Implement checks for:
   - Valid URL formats
   - Coverage percentages within 0-100 range
   - Required fields not null
   - Git hash length validation (40 characters)

2. **Data Integration**: 
   - Use project name as primary join key
   - Handle missing NX domain data gracefully
   - Preserve IT domain data integrity during imports

3. **Reporting**:
   - Generate TO Summary by joining IT and NX domain data
   - Include timestamp for report generation
   - Support export to multiple formats (JSON, CSV, Excel)

## Conclusion

The TO Report system successfully integrates 33 fields from dual domains, providing comprehensive tape-out readiness information. The architecture supports both manual project management data (IT domain) and automated DV regression results (NX domain), ensuring complete visibility into the tape-out process.